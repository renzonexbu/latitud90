<?php

namespace App\Services\Admin\Courses;

use App\Models\Course;
use App\Models\Institution;
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
     *
     * @return \Illuminate\Support\Collection
     */
    public function getAllCourses(): Collection
    {
        return Course::with(['programCourses.program', 'programCourses.salesExecutive', 'createdBy', 'institution', 'participants'])
            ->orderBy('created_at', 'desc')
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
            $activeParticipants = $participants->filter(fn($p) => ($p->pivot->status ?? 'active') !== 'cancelled');

            $courseTotalAmount = $this->calculateTotalAmount($course, $activeParticipants, $program, $programCourse);
            $coursePaidAmount = $this->calculatePaidAmount($course, $program, $programCourse);
            $coursePaymentPercentage = $this->calculatePaymentPercentage($courseTotalAmount, $coursePaidAmount);

            $course->course_total_amount = $courseTotalAmount;
            $course->course_paid_amount = $coursePaidAmount;
            $course->course_payment_percentage = $coursePaymentPercentage;
            $course->total_students = $activeParticipants->count();

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

    private function calculateTotalAmount($course, $activeParticipants, $program = null, $programCourse = null): float
    {
        if (!$programCourse) {
            return 0.0;
        }

        // Calcular el total sumando el precio final de cada participante (considerando descuentos)
        $totalAmount = 0.0;

        foreach ($activeParticipants as $participant) {
            $priceData = ParticipantPriceHelper::calculateParticipantPrice($participant, $programCourse);
            $totalAmount += $priceData['final_price'] ?? 0;
        }

        return $totalAmount;
    }

    private function calculatePaidAmount($course, $program = null, $programCourse = null): float
    {
        if (!$programCourse) {
            return 0.0;
        }

        // Sumar pagos normales completados (EXCLUYENDO APORTES)
        // IMPORTANTE: orders.program_id hace referencia a program_courses.id, NO a programs.id
        // Los aportes (presential_aporte) son contribuciones adicionales que NO reducen la deuda
        $normalPayments = (float) DB::table('payments')
            ->join('orders', 'payments.order_id', '=', 'orders.id')
            ->leftJoin('payment_options', 'payments.payment_option_id', '=', 'payment_options.id')
            ->where('orders.program_id', $programCourse->id)
            ->whereIn('payments.status', ['approved', 'completed'])
            ->where(function($query) {
                $query->whereNull('payment_options.code')
                      ->orWhere('payment_options.code', '!=', 'presential_aporte');
            })
            ->sum('payments.amount');

        // Sumar cuotas de suscripciones pagadas (installments)
        // IMPORTANTE: installment_plans.program_id hace referencia a program_courses.id, NO a programs.id
        // Usar status = 'paid' porque is_paid puede no estar sincronizado
        $subscriptionPayments = (float) DB::table('installments')
            ->join('installment_plans', 'installments.installment_plan_id', '=', 'installment_plans.id')
            ->where('installment_plans.program_id', $programCourse->id)
            ->where('installments.status', 'paid')
            ->sum('installments.amount');

        return $normalPayments + $subscriptionPayments;
    }

    private function calculatePaymentPercentage(float $total, float $paid): int
    {
        return $total > 0 ? (int) round(($paid / $total) * 100, 0) : 0;
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

            // Calculate total amount from active participants (considering discounts)
            $participants = $course->participants ?? collect();
            $activeParticipants = $participants->filter(fn($p) => ($p->pivot->status ?? 'active') !== 'cancelled');

            // Total del curso = suma del precio final de cada participante (con descuentos)
            $courseTotalAmount = 0.0;
            foreach ($activeParticipants as $participant) {
                $priceData = ParticipantPriceHelper::calculateParticipantPrice($participant, $programCourse);
                $courseTotalAmount += $priceData['final_price'] ?? 0;
            }

            // Calculate paid amount (pagos normales + cuotas de suscripciones)
            // IMPORTANTE: orders.program_id hace referencia a program_courses.id, NO a programs.id
            // EXCLUYE APORTES: Los aportes (presential_aporte) son contribuciones adicionales que NO reducen la deuda
            $normalPayments = (float) \App\Models\Payment::whereHas('order', function($q) use ($programCourse) {
                $q->where('program_id', $programCourse->id);
            })
            ->whereIn('status', ['approved', 'completed'])
            ->where(function($query) {
                $query->whereDoesntHave('paymentOption')
                      ->orWhereHas('paymentOption', function($q) {
                          $q->where('code', '!=', 'presential_aporte');
                      });
            })
            ->sum('amount');

            // Sumar cuotas de suscripciones pagadas
            // IMPORTANTE: installment_plans.program_id hace referencia a program_courses.id, NO a programs.id
            // Usar status = 'paid' porque is_paid puede no estar sincronizado
            $subscriptionPayments = (float) DB::table('installments')
                ->join('installment_plans', 'installments.installment_plan_id', '=', 'installment_plans.id')
                ->where('installment_plans.program_id', $programCourse->id)
                ->where('installments.status', 'paid')
                ->sum('installments.amount');

            $coursePaidAmount = round($normalPayments + $subscriptionPayments, 2);

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
