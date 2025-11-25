<?php

namespace App\Services\Client\Programs;

use App\Models\Program;
use App\Models\ProgramCourse;
use App\Models\Participant;
use App\Models\Institution;
use App\Models\Course;
use App\Models\Feature;
use App\Models\Requirement;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\Payment;
use App\Models\InstallmentPlan;
use App\Models\ProgramSubscription;
use App\Helpers\ParticipantPriceHelper;
use App\Traits\SystemLogging;
use Illuminate\Support\Facades\DB;

class ProgramDetailService
{
    use SystemLogging;
    public function getProgramDetails($programCourseId, $participantId)
    {
        // Cargar el program_course (plan específico) con su plantilla y relaciones
        $programCourse = ProgramCourse::with([
            'program.features',
            'program.requirements',
            'course.participants'
        ])->find($programCourseId);

        if (!$programCourse) {
            return null;
        }

        // Obtener la plantilla del programa
        $program = $programCourse->program;

        if (!$program) {
            return null;
        }

        // Obtener el participante
        $participant = Participant::find($participantId);

        // Verificar si el participante ya está inscrito en este programa y calcular montos
        $isEnrolled = false;
        $participantAmount = null;
        $participantAdjustments = 0.0;
        $participantTotalAmount = (float) $programCourse->trip_price;
        $paidAmount = 0.0;
        $participantBalance = (float) $programCourse->trip_price;
        $paymentPercentage = 0.0;

        if ($participant && $programCourse->course) {
            $pivotParticipant = $programCourse->course->participants
                ->firstWhere('id', $participant->id);
            if ($pivotParticipant) {
                $isEnrolled = true;

                // Calcular precio usando la nueva arquitectura (igual que ProgramService)
                $basePrice = $pivotParticipant->pivot->individual_price ?? $programCourse->trip_price ?? 0;
                $adjustments = $pivotParticipant->pivot->price_adjustments ?? 0;
                $discounts = 0; // Por ahora no hay descuentos (participant_program ya no se usa)
                $finalPrice = max(0, $basePrice + $adjustments - $discounts);

                $participantAmount = $basePrice;
                $participantAdjustments = $adjustments;
                $participantTotalAmount = $finalPrice;

                // Calcular monto pagado desde las cuotas del installment_plan
                $paidAmount = 0.0;
                $installmentPlans = InstallmentPlan::where('participant_id', $participant->id)
                    ->where('program_id', $programCourse->id)
                    ->with(['installments'])
                    ->get();

                foreach ($installmentPlans as $plan) {
                    foreach ($plan->installments as $installment) {
                        // Sumar solo cuotas realmente pagadas
                        if ($installment->status === 'paid' && $installment->is_paid) {
                            $paidAmount += (float) $installment->amount;
                        }
                    }
                }

                // Si no hay installment plans, buscar pagos en orders (excluyendo suscripciones)
                if ($installmentPlans->isEmpty()) {
                    $paidAmount = (float) Payment::whereHas('order', function ($q) use ($participant, $programCourse) {
                            $q->where('participant_id', $participant->id)
                              ->where('program_id', $programCourse->id)
                              ->where('order_number', 'NOT LIKE', 'SUB-%'); // Excluir órdenes de suscripción
                        })
                        ->whereIn('status', ['approved', 'completed'])
                        ->sum('amount');
                }

                $paidAmount = round($paidAmount, 2);
                $participantBalance = max(round($participantTotalAmount - $paidAmount, 2), 0);
                $paymentPercentage = $participantTotalAmount > 0
                    ? round(($paidAmount / $participantTotalAmount) * 100, 2)
                    : 0.0;
            }
        }

        // Verificar si existe una suscripción activa
        $activeSubscription = null;
        $hasActiveSubscription = false;
        if ($participant) {
            $subscription = ProgramSubscription::where('participant_id', $participant->id)
                ->where('program_id', $programCourse->id)
                ->whereIn('status', ['ACTIVA', 'SUSCRIBIENDO'])
                ->first();

            if ($subscription) {
                $hasActiveSubscription = true;
                $activeSubscription = [
                    'id' => $subscription->id,
                    'status' => $subscription->status,
                    'virtualpos_subscription_id' => $subscription->virtualpos_subscription_id,
                    'payment_method' => $subscription->payment_method,
                    'created_at' => $subscription->created_at->toDateString(),
                ];
            }
        }

        // Buscar plan de cuotas activo usando la nueva arquitectura
        $activeInstallment = null;
        $paymentPlanLocked = false;
        if ($participant) {
            $installmentPlan = InstallmentPlan::where('participant_id', $participant->id)
                ->where('program_id', $programCourse->id) // program_id ahora apunta a program_courses
                ->where('status', 'active')
                ->first();

            if ($installmentPlan) {
                // Verificar si hay cuotas pagadas
                $paidInstallments = $installmentPlan->installments()->where('status', 'paid')->count();
                $paymentPlanLocked = $paidInstallments > 0;

                if ($paymentPlanLocked) {
                    // Obtener la próxima cuota pendiente
                    $nextInstallment = $installmentPlan->installments()
                        ->where('status', 'pending')
                        ->orderBy('due_date')
                        ->first();

                    if ($nextInstallment) {
                        $activeInstallment = [
                            'number' => (int) $nextInstallment->installment_number,
                            'total' => (int) $installmentPlan->total_installments,
                            'amount' => round(((float) $nextInstallment->amount), 2),
                            'due_date' => optional($nextInstallment->due_date)->toDateString(),
                        ];
                    }
                }
            }
        }

        // Cargar opciones de pago habilitadas (configuradas en el curso específico)
        $fullPaymentOptionCodes = DB::table('program_course_payment_option as pcpo')
            ->join('payment_options as po', 'po.id', '=', 'pcpo.payment_option_id')
            ->where('pcpo.program_course_id', $programCourse->id)
            ->where('pcpo.enabled', true)
            ->where('po.code', 'LIKE', 'full_%')
            ->pluck('po.code')
            ->toArray();

        // Opciones de pago en cuotas (lat90 -> subscription)
        $subscriptionPaymentOptionCodes = DB::table('program_course_payment_option as pcpo')
            ->join('payment_options as po', 'po.id', '=', 'pcpo.payment_option_id')
            ->select(['po.code', 'po.label'])
            ->where('pcpo.program_course_id', $programCourse->id)
            ->where('pcpo.enabled', true)
            ->where('po.code', 'LIKE', 'subscription_%')
            ->get()
            ->map(function ($row) {
                return ['code' => $row->code, 'label' => $row->label];
            })
            ->toArray();

        // FALLBACK: Si no hay opciones en la BD, usar opciones por defecto
        if (empty($fullPaymentOptionCodes) && $programCourse->enable_total_payment) {
            $fullPaymentOptionCodes = [
                'khipu',
                'debit_credit_0',
                'debit_credit_3',
                'debit_credit_6',
                'international'
            ];
        }

        if (empty($subscriptionPaymentOptionCodes) && $programCourse->enable_subscription_payment) {
            $subscriptionPaymentOptionCodes = [
                ['code' => 'subscription_virtualpos', 'label' => 'Suscripción VirtualPos']
            ];
        }

        // Obtener información completa del programa combinando programCourse y program
        $programData = [
            'id' => $programCourse->id, // ID del plan específico
            'name' => $programCourse->name, // Nombre del plan específico
            'destination' => $program->destination, // De la plantilla
            'trip_description' => $program->trip_description, // De la plantilla
            'trip_price' => $programCourse->trip_price, // Del plan específico
            'departure_date' => $programCourse->departure_date, // Del plan específico
            'final_payment_date' => $programCourse->final_payment_date, // Del plan específico
            'seller_name' => $programCourse->seller_name, // Del plan específico
            'active' => $programCourse->active, // Del plan específico
            'is_enrolled' => $isEnrolled,
            // Montos por participante
            'participant_amount' => $participantAmount,
            'participant_adjustments' => $participantAdjustments,
            'participant_total_due' => $participantTotalAmount,
            'paidAmount' => $paidAmount,
            'participant_balance' => $participantBalance,
            'paymentPercentage' => $paymentPercentage,
            'payment_plan_locked' => $paymentPlanLocked,
            'active_installment' => $activeInstallment,
            // Suscripción
            'has_active_subscription' => $hasActiveSubscription,
            'active_subscription' => $activeSubscription,

            // Archivos PDF (de la plantilla)
            'itinerary_file' => $program->itinerary_file_url,
            'travel_assistance_coverage' => $program->travel_assistance_coverage_url,
            'equipment_list' => $program->equipment_list_url,

            // Información adicional (de la plantilla)
            'itinerary_description' => $program->itinerary_description,
            'pillars' => $program->pillars,
            'images' => $program->images, // Accessor de la plantilla
            'images_folder' => $program->images_folder,

            // Información de pago (del plan específico, con campos renombrados)
            'enable_total_payment' => $programCourse->enable_total_payment,
            'full_payment_options' => $fullPaymentOptionCodes,
            'enable_lat90_payment' => $programCourse->enable_subscription_payment, // Renombrado
            'lat90_payment_options' => $subscriptionPaymentOptionCodes,
            'lat90_max_installments' => $programCourse->subscription_max_months, // Renombrado
            'discount_type' => $programCourse->discount_type,
            'discount_value' => $programCourse->discount_value,

            // Datos de la plantilla del programa (para referencias)
            'program' => [
                'id' => $program->id,
                'name' => $program->name,
                'destination' => $program->destination,
            ],

            // Características (de la plantilla)
            'features' => $program->features->map(function ($feature) {
                return [
                    'id' => $feature->id,
                    'name' => $feature->name,
                    'description' => $feature->description ?? '',
                    'icon' => $feature->icon ?? '✓',
                ];
            }),

            // Requisitos (de la plantilla)
            'requirements' => $program->requirements->map(function ($requirement) {
                return [
                    'id' => $requirement->id,
                    'name' => $requirement->name,
                    'description' => $requirement->description ?? '',
                    'is_mandatory' => $requirement->pivot->type === 'mandatory',
                ];
            }),
        ];

        return $programData;
    }

    private function calculateDuration($departureDate)
    {
        if (!$departureDate) return 'Duración no especificada';

        $today = new \DateTime();
        $departure = new \DateTime($departureDate);
        $diff = $today->diff($departure);

        if ($diff->days === 0) {
            return 'Hoy';
        } elseif ($diff->days === 1) {
            return '1 día';
        } elseif ($diff->days < 7) {
            return $diff->days . ' días';
        } elseif ($diff->days < 30) {
            $weeks = ceil($diff->days / 7);
            return $weeks . ' semana' . ($weeks > 1 ? 's' : '');
        } else {
            $months = ceil($diff->days / 30);
            return $months . ' mes' . ($months > 1 ? 'es' : '');
        }
    }
}
