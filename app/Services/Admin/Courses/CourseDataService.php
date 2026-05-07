<?php

namespace App\Services\Admin\Courses;

use App\Models\Course;
use App\Models\Institution;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Program;
use App\Helpers\ParticipantPriceHelper;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;

class CourseDataService
{
    public function getCourseList(array $filters = [])
    {
        return Course::with(['programCourses.program', 'programCourses.salesExecutive', 'createdBy', 'institution', 'participants'])
            ->when($filters['search'] ?? null, function ($query, $search) {
                $query->whereHas('institution', function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%");
                })
                ->orWhere('education_level', 'like', "%{$search}%")
                ->orWhere('course_name', 'like', "%{$search}%");
            })
            ->when($filters['status'] ?? null, function ($query, $status) {
                $query->where('status', $status);
            })
            ->orderBy('created_at', 'desc')
            ->paginate(10)
            ->withQueryString();
    }

    /**
     * Get all courses without pagination (for frontend filtering)
     * Ordenados por fecha de inicio (departure_date) del programa más cercana
     *
     * @return \Illuminate\Support\Collection
     */
    public function getAllCourses(): Collection
    {
        return Course::with(['programCourses.program', 'programCourses.salesExecutive', 'createdBy', 'institution', 'participants'])
            ->leftJoin('program_courses', 'courses.id', '=', 'program_courses.course_id')
            ->orderBy('program_courses.departure_date', 'asc')
            ->orderBy('courses.created_at', 'desc')
            ->select('courses.*')
            ->get();
    }

    /**
     * Calculate metrics for a collection or paginator of courses
     *
     * @param  \Illuminate\Support\Collection|\Illuminate\Pagination\LengthAwarePaginator  $courses
     * @return \Illuminate\Support\Collection|\Illuminate\Pagination\LengthAwarePaginator
     */
    public function calculateCourseMetrics($courses)
    {
        $transform = function ($course) {
            // Get primary program from first programCourse
            $programCourse = $course->programCourses->first();
            $program = $programCourse?->program;

            if ($program) {
                $program->makeVisible(['trip_price', 'name', 'destination']);
            }

            $participants = $course->participants ?? collect();

            // Filtrar solo cancelled (no baja - se manejan aparte en calculateTotalAmount)
            $nonCancelledParticipants = $participants->filter(fn($p) =>
                ($p->pivot->status ?? 'active') !== 'cancelled'
            );

            // Contar participantes activos (excluyendo baja) para mostrar en la tabla
            $bajaParticipantIds = $programCourse
                ? DB::table('participant_program')
                    ->where('program_id', $programCourse->id)
                    ->where('is_active', false)
                    ->pluck('participant_id')
                    ->toArray()
                : [];

            $activeCount = $nonCancelledParticipants->filter(fn($p) =>
                !in_array($p->id, $bajaParticipantIds)
            )->count();

            $courseTotalAmount = $this->calculateTotalAmount($course, $nonCancelledParticipants, $program, $programCourse);
            $coursePaidAmount = $this->calculatePaidAmount($course, $program, $programCourse);
            $coursePaymentPercentage = $this->calculatePaymentPercentage($courseTotalAmount, $coursePaidAmount);

            $course->course_total_amount = $courseTotalAmount;
            $course->course_paid_amount = $coursePaidAmount;
            $course->course_payment_percentage = $coursePaymentPercentage;
            $course->total_students = $activeCount;

            // Agregar código del programa directamente para facilitar búsqueda en frontend
            $course->program_code = $programCourse?->code ?? '';

            return $course;
        };

        // Handle both Collection and LengthAwarePaginator
        if ($courses instanceof \Illuminate\Pagination\AbstractPaginator) {
            $courses->getCollection()->transform($transform);
            return $courses;
        }

        return $courses->map($transform);
    }

    /**
     * Calcula el total esperado usando la misma lógica del reporte Estado de Cuenta Parcial:
     * Total Esperado = Sum(Precio) - Sum(Liberado)
     * Donde Precio = basePrice - descuentos simples (sin beca ni liberado)
     * Para participantes de baja: Precio ajustado según abono
     */
    private function calculateTotalAmount($course, $participants, $program = null, $programCourse = null): float
    {
        if (!$programCourse) {
            return 0.0;
        }

        // Obtener IDs de participantes "de baja"
        $bajaParticipantIds = DB::table('participant_program')
            ->where('program_id', $programCourse->id)
            ->where('is_active', false)
            ->pluck('participant_id')
            ->toArray();

        $totalAmount = 0.0;

        foreach ($participants as $participant) {
            $priceData = ParticipantPriceHelper::calculateParticipantPrice($participant, $programCourse);
            $basePrice = $priceData['base_price'];

            // Obtener participant_program para desglosar descuentos por tipo
            $pp = DB::table('participant_program')
                ->where('participant_id', $participant->id)
                ->where('program_id', $programCourse->id)
                ->first();

            $simpleDiscounts = 0.0;
            $released = 0.0;

            if ($pp) {
                $discounts = DB::table('participant_program_discounts')
                    ->where('participant_program_id', $pp->id)
                    ->get();

                foreach ($discounts as $disc) {
                    $discAmount = 0.0;
                    if ($disc->percent && $disc->percent > 0) {
                        $discAmount += ($basePrice * $disc->percent) / 100;
                    }
                    if ($disc->amount && $disc->amount > 0) {
                        $discAmount += (float) $disc->amount;
                    }

                    if ($disc->discount_type === 'released') {
                        $released += $discAmount;
                    } elseif ($disc->discount_type !== 'scholarship') {
                        $simpleDiscounts += $discAmount;
                    }
                    // scholarship no se resta del precio (se cuenta como aporte en recaudado)
                }
            }

            // Precio = basePrice - descuentos simples
            $precio = $basePrice - $simpleDiscounts;

            // Ajuste para participantes de baja
            if (in_array($participant->id, $bajaParticipantIds)) {
                $abono = $this->calculateParticipantAbono($participant->id, $programCourse->id);
                $precio = min($precio, $abono);
            }

            $totalAmount += $precio - $released;
        }

        return $totalAmount;
    }

    /**
     * Calcula el abono (pagos sin aportes) de un participante para un programa
     */
    private function calculateParticipantAbono(int $participantId, int $programCourseId): float
    {
        $orderIds = Order::where('participant_id', $participantId)
            ->where('program_id', $programCourseId)
            ->pluck('id')->all();

        if (empty($orderIds)) {
            return 0.0;
        }

        // Pagos normales (sin aportes ni suscripciones)
        $normalPayments = (float) Payment::whereIn('order_id', $orderIds)
            ->whereIn('status', ['approved', 'completed'])
            ->where(function($q) {
                $q->whereNull('payment_source')
                  ->orWhere('payment_source', '!=', 'subscription');
            })
            ->where(function($q) {
                $q->whereNull('payment_option_id')
                  ->orWhereHas('paymentOption', function($sq) {
                      $sq->where('report_code', '!=', 'AP');
                  });
            })
            ->sum('amount');

        // Cuotas de suscripción pagadas - solo de planes activos
        $subscriptionPayments = (float) DB::table('installments')
            ->join('installment_plans', 'installments.installment_plan_id', '=', 'installment_plans.id')
            ->where('installment_plans.participant_id', $participantId)
            ->where('installment_plans.program_id', $programCourseId)
            ->where('installment_plans.status', '!=', 'cancelled')
            ->where('installments.status', 'paid')
            ->sum('installments.amount');

        return $normalPayments + $subscriptionPayments;
    }

    private function calculatePaidAmount($course, $program = null, $programCourse = null): float
    {
        if (!$programCourse) {
            return 0.0;
        }

        // Sumar pagos normales completados (excluyendo aportes y pagos de suscripción)
        // IMPORTANTE: orders.program_id hace referencia a program_courses.id, NO a programs.id
        // Excluir payment_source='subscription' para evitar doble conteo con installments
        $normalPayments = (float) DB::table('payments')
            ->join('orders', 'payments.order_id', '=', 'orders.id')
            ->leftJoin('payment_options', 'payments.payment_option_id', '=', 'payment_options.id')
            ->where('orders.program_id', $programCourse->id)
            ->whereIn('payments.status', ['approved', 'completed'])
            ->where(function($query) {
                $query->whereNull('payments.payment_source')
                      ->orWhere('payments.payment_source', '!=', 'subscription');
            })
            ->where(function($query) {
                $query->whereNull('payment_options.code')
                      ->orWhere('payment_options.code', '!=', 'presential_aporte');
            })
            ->sum('payments.amount');

        // Sumar cuotas de suscripciones pagadas (installments) - solo de planes activos
        // IMPORTANTE: installment_plans.program_id hace referencia a program_courses.id, NO a programs.id
        $subscriptionPayments = (float) DB::table('installments')
            ->join('installment_plans', 'installments.installment_plan_id', '=', 'installment_plans.id')
            ->where('installment_plans.program_id', $programCourse->id)
            ->where('installment_plans.status', '!=', 'cancelled')
            ->where('installments.status', 'paid')
            ->sum('installments.amount');

        // Sumar aportes/becas (pagos con código presential_aporte)
        // Real Recaudado = Abono Pagadores + Aportes
        $aportePayments = (float) DB::table('payments')
            ->join('orders', 'payments.order_id', '=', 'orders.id')
            ->join('payment_options', 'payments.payment_option_id', '=', 'payment_options.id')
            ->where('orders.program_id', $programCourse->id)
            ->whereIn('payments.status', ['approved', 'completed'])
            ->where('payment_options.code', 'presential_aporte')
            ->sum('payments.amount');

        return $normalPayments + $subscriptionPayments + $aportePayments;
    }

    private function calculatePaymentPercentage(float $total, float $paid): int
    {
        // - Sin excedente (≤100%): floor() para no mostrar 100% si aún falta cobrar.
        //   (ej: 99.6% debe mostrarse como 99%, no 100%, para que ejecutivos no abandonen).
        // - Con excedente (>100%): ceil() para que el sobrepago se vea (ej: 100.003% → 101%).
        if ($total <= 0) {
            return 0;
        }
        if ($paid > $total) {
            return (int) ceil(($paid / $total) * 100);
        }
        return (int) floor(($paid / $total) * 100);
    }

    public function getCourseForEdit(Course $course): array
    {
        $course->load(['institution', 'programCourses.program', 'participants']);

        // Get primary program from first programCourse
        $programCourse = $course->programCourses->first();
        $program = $programCourse?->program;

        // Calculate payment metrics if program exists
        if ($program && $programCourse) {
            $program->makeVisible(['trip_price', 'name', 'destination']);

            // Calculate total amount using same logic as Estado de Cuenta Parcial report
            $participants = $course->participants ?? collect();
            $nonCancelledParticipants = $participants->filter(fn($p) =>
                ($p->pivot->status ?? 'active') !== 'cancelled'
            );

            $courseTotalAmount = $this->calculateTotalAmount($course, $nonCancelledParticipants, $program, $programCourse);

            // Calculate paid amount (pagos normales + cuotas de suscripciones + aportes)
            // IMPORTANTE: orders.program_id hace referencia a program_courses.id, NO a programs.id
            $normalPayments = (float) \App\Models\Payment::whereHas('order', function($q) use ($programCourse) {
                $q->where('program_id', $programCourse->id);
            })
            ->whereIn('status', ['approved', 'completed'])
            ->where(function($query) {
                $query->whereNull('payment_source')
                      ->orWhere('payment_source', '!=', 'subscription');
            })
            ->where(function($query) {
                $query->whereDoesntHave('paymentOption')
                      ->orWhereHas('paymentOption', function($q) {
                          $q->where('code', '!=', 'presential_aporte');
                      });
            })
            ->sum('amount');

            // Sumar cuotas de suscripciones pagadas - solo de planes activos
            $subscriptionPayments = (float) DB::table('installments')
                ->join('installment_plans', 'installments.installment_plan_id', '=', 'installment_plans.id')
                ->where('installment_plans.program_id', $programCourse->id)
                ->where('installment_plans.status', '!=', 'cancelled')
                ->where('installments.status', 'paid')
                ->sum('installments.amount');

            // Sumar aportes/becas (pagos con código presential_aporte)
            $aportePayments = (float) \App\Models\Payment::whereHas('order', function($q) use ($programCourse) {
                $q->where('program_id', $programCourse->id);
            })
            ->whereIn('status', ['approved', 'completed'])
            ->whereHas('paymentOption', function($q) {
                $q->where('code', 'presential_aporte');
            })
            ->sum('amount');

            $coursePaidAmount = round($normalPayments + $subscriptionPayments + $aportePayments, 2);

            // Calculate payment percentage
            $coursePaymentPercentage = $courseTotalAmount > 0
                ? round(($coursePaidAmount / $courseTotalAmount) * 100, 0)
                : 0;

            // Attach metrics to program
            $program->payment_percentage = $coursePaymentPercentage;
            $program->paid_amount = $coursePaidAmount;
            $program->total_amount = $courseTotalAmount > 0 ? $courseTotalAmount : ($program->trip_price ?? 0);
        }

        return [
            'course' => $course,
            'institution' => $course->institution,
            'program' => $program,
            'participants' => $course->participants
        ];
    }

    /**
     * Get header information for a course
     */
    public function getCourseHeaderInfo(Course $course): array
    {
        return [
            'institution_name' => $course->institution?->name ?? 'Sin institución',
            'year' => $course->year,
            'grade' => $course->course_display,
            'shift' => null,
            'education_level' => $course->education_level,
        ];
    }

    public function getFilteredPayments(Course $course, array $filters = [])
    {
        // Ensure programCourses relationship is loaded
        $course->loadMissing('programCourses');

        // Get the program_id from the first programCourse
        $programCourse = $course->programCourses->first();
        $programId = $programCourse?->program_id;

        if (!$programId) {
            // Return empty paginator if no program is associated
            return new \Illuminate\Pagination\LengthAwarePaginator([], 0, 10);
        }

        $query = \App\Models\Payment::query()
            ->with([
                'order.program.course.institution',
                'order.participant.documentType',
                'orderDetail.country',
                'orderDetail.region',
                'orderDetail.city',
                'paymentGateway',
                'paymentOption'
            ])
            ->whereHas('order', function($query) use ($programId) {
                $query->where('program_id', $programId);
            });

        if (!empty($filters['participant_name'])) {
            $query->whereHas('order.participant', function($q) use ($filters) {
                $q->where('first_name', 'like', '%' . $filters['participant_name'] . '%')
                  ->orWhere('last_name', 'like', '%' . $filters['participant_name'] . '%');
            });
        }

        if (!empty($filters['payment_status']) && $filters['payment_status'] !== 'all') {
            $query->where('status', $filters['payment_status']);
        }

        if (!empty($filters['program_id'])) {
            $query->whereHas('order.program', fn($q) => $q->where('id', $filters['program_id']));
        }

        if (!empty($filters['payment_method']) && $filters['payment_method'] !== 'all') {
            if ($filters['payment_method'] === 'presencial') {
                $query->where(fn($q) => 
                    $q->whereHas('paymentOption', fn($sq) => $sq->where('gateway_code', 'presencial'))
                      ->orWhereNull('payment_option_id')
                );
            } else {
                $query->whereHas('paymentOption', fn($q) => 
                    $q->where('gateway_code', $filters['payment_method'])
                );
            }
        }

        if (!empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        return $query->orderBy('created_at', 'desc')
            ->paginate(10, ['*'], 'page', $filters['page'] ?? 1);
    }
}
