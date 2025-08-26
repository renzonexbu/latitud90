<?php

namespace App\Services\Client;

use App\Models\Program;
use App\Models\Participant;
use App\Models\Institution;
use App\Models\Course;
use App\Models\Feature;
use App\Models\Requirement;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\Payment;
use App\Models\InstallmentPlan;
use App\Helpers\ParticipantPriceHelper;
use App\Traits\SystemLogging;
use Illuminate\Support\Facades\DB;

class ProgramDetailService
{
    use SystemLogging;
    public function getProgramDetails($programId, $participantId)
    {
        $program = Program::with([
            'features',
            'requirements',
            // Relaciones legacy (pueden no existir según migraciones actuales)
            'course.participants'
        ])->find($programId);

        if (!$program) {
            return null;
        }

        // Obtener el participante
        $participant = Participant::find($participantId);

        // Verificar si el participante ya está inscrito en este programa y calcular montos
        $isEnrolled = false;
        $participantAmount = null;
        $participantAdjustments = 0.0;
        $participantTotalAmount = (float) $program->trip_price;
        $paidAmount = 0.0;
        $participantBalance = (float) $program->trip_price;
        $paymentPercentage = 0.0;

        if ($participant && $program->course) {
            $pivotParticipant = $program->course->participants
                ->firstWhere('id', $participant->id);
            if ($pivotParticipant) {
                $isEnrolled = true;
                
                // Usar el helper para calcular el precio final con descuentos
                $priceData = ParticipantPriceHelper::calculateParticipantPrice($participant, $program);
                $participantTotalAmount = $priceData['final_price'];

                // Sumar pagos aprobados del participante para este programa
                $paidAmount = (float) Payment::whereHas('order', function ($q) use ($participant, $program) {
                        $q->where('participant_id', $participant->id)
                          ->where('program_id', $program->id);
                    })
                    ->where('status', 'approved')
                    ->sum('amount');
                $paidAmount = round($paidAmount, 2);
                $participantBalance = max(round($participantTotalAmount - $paidAmount, 2), 0);
                $paymentPercentage = $participantTotalAmount > 0
                    ? round(($paidAmount / $participantTotalAmount) * 100, 2)
                    : 0.0;
            }
        }

        // Buscar plan de cuotas activo usando la nueva arquitectura
        $activeInstallment = null;
        $paymentPlanLocked = false;
        if ($participant) {
            $installmentPlan = InstallmentPlan::where('participant_id', $participant->id)
                ->where('program_id', $program->id)
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

        // Cargar opciones de pago habilitadas (nuevo esquema payment_options + pivote)
        $fullPaymentOptionCodes = DB::table('program_payment_option as ppo')
            ->join('payment_options as po', 'po.id', '=', 'ppo.payment_option_id')
            ->where('ppo.program_id', $program->id)
            ->where('ppo.enabled', true)
            ->where('po.mode', 'full')
            ->pluck('po.code')
            ->toArray();

        $lat90PaymentOptionCodes = DB::table('program_payment_option as ppo')
            ->join('payment_options as po', 'po.id', '=', 'ppo.payment_option_id')
            ->select(['po.code', 'po.label'])
            ->where('ppo.program_id', $program->id)
            ->where('ppo.enabled', true)
            ->where('po.mode', 'lat90')
            ->get()
            ->map(function ($row) {
                return ['code' => $row->code, 'label' => $row->label];
            })
            ->toArray();

        // Obtener información completa del programa
        $programData = [
            'id' => $program->id,
            'name' => $program->name,
            'destination' => $program->destination,
            'trip_description' => $program->trip_description,
            'trip_price' => $program->trip_price,
            'departure_date' => $program->departure_date,
            'final_payment_date' => $program->final_payment_date,
            'seller_name' => $program->seller_name,
            'active' => $program->active,
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

            // Archivos PDF
            'itinerary_file' => $program->itinerary_file_url,
            'travel_assistance_coverage' => $program->travel_assistance_coverage_url,
            'equipment_list' => $program->equipment_list_url,

            // Información adicional
            'itinerary_description' => $program->itinerary_description,
            'pillars' => $program->pillars,
            'images' => $program->images,
            'images_folder' => $program->images_folder,

            // Información de pago (nuevo)
            'enable_total_payment' => $program->enable_total_payment,
            'full_payment_options' => $fullPaymentOptionCodes,
            'enable_lat90_payment' => $program->enable_lat90_payment,
            'lat90_payment_options' => $lat90PaymentOptionCodes,
            'lat90_max_installments' => $program->lat90_max_installments,
            'discount_type' => $program->discount_type,
            'discount_value' => $program->discount_value,





            // Características
            'features' => $program->features->map(function ($feature) {
                return [
                    'id' => $feature->id,
                    'name' => $feature->name,
                    'description' => $feature->description ?? '',
                    'icon' => $feature->icon ?? '✓',
                ];
            }),

            // Requisitos
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
