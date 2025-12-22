<?php

namespace App\Services\Subscription;

use App\Models\Participant;
use App\Models\ProgramCourse;
use App\Models\ProgramSubscription;
use App\Models\VirtualPosPlan;
use App\Helpers\ParticipantPriceHelper;
use Illuminate\Support\Facades\Log;

class ParticipantPlanService
{
    protected VirtualPosPlanService $virtualPosPlanService;

    public function __construct(VirtualPosPlanService $virtualPosPlanService)
    {
        $this->virtualPosPlanService = $virtualPosPlanService;
    }

    /**
     * Crear un plan personalizado para un participante con descuento
     */
    public function createPersonalizedPlan(
        Participant $participant,
        ProgramCourse $programCourse,
        string $discountType,
        ?string $discountReason = null
    ): array {
        try {
            Log::channel('daily')->info('=== PLAN PERSONALIZADO: Iniciando creación ===', [
                'participant_id' => $participant->id,
                'participant_name' => $participant->full_name,
                'program_course_id' => $programCourse->id,
                'program_course_name' => $programCourse->name,
                'discount_type' => $discountType,
                'discount_reason' => $discountReason,
                'enable_subscription_payment' => $programCourse->enable_subscription_payment,
            ]);

            // Verificar que el program_course tiene suscripción habilitada
            if (!$programCourse->enable_subscription_payment) {
                Log::channel('daily')->warning('PLAN PERSONALIZADO: Programa sin suscripción habilitada', [
                    'program_course_id' => $programCourse->id,
                ]);
                return [
                    'success' => false,
                    'message' => 'Este programa no tiene pago por suscripción habilitado.'
                ];
            }

            // Verificar si el participante ya tiene una suscripción activa para este program_course
            $activeSubscription = ProgramSubscription::where('participant_id', $participant->id)
                ->where('program_id', $programCourse->id)
                ->whereIn('status', ['ACTIVA', 'SUSCRIBIENDO'])
                ->first();

            if ($activeSubscription) {
                Log::channel('daily')->warning('PLAN PERSONALIZADO: Participante ya tiene suscripción activa', [
                    'participant_id' => $participant->id,
                    'program_course_id' => $programCourse->id,
                    'subscription_id' => $activeSubscription->id,
                    'subscription_status' => $activeSubscription->status,
                    'virtualpos_subscription_id' => $activeSubscription->virtualpos_subscription_id,
                ]);
                return [
                    'success' => false,
                    'message' => 'El participante ya tiene una suscripción activa para este programa. No se puede crear un plan personalizado.',
                    'existing_subscription' => $activeSubscription
                ];
            }

            // Verificar si ya existe un plan personalizado activo
            $existingPlan = VirtualPosPlan::findForParticipant($participant->id, $programCourse->id);
            if ($existingPlan) {
                Log::channel('daily')->warning('PLAN PERSONALIZADO: Ya existe un plan activo', [
                    'existing_plan_id' => $existingPlan->id,
                    'virtualpos_plan_id' => $existingPlan->virtualpos_plan_id,
                ]);
                return [
                    'success' => false,
                    'message' => 'Ya existe un plan personalizado activo para este participante.',
                    'existing_plan' => $existingPlan
                ];
            }

            // Calcular el precio con descuentos
            $priceData = ParticipantPriceHelper::calculateParticipantPrice($participant, $programCourse);
            $originalPrice = $priceData['base_price'];
            $discountAmount = $priceData['discounts'];
            $finalPrice = $priceData['final_price'];

            Log::channel('daily')->info('PLAN PERSONALIZADO: Cálculo de precios', [
                'participant_id' => $participant->id,
                'base_price' => $originalPrice,
                'discounts' => $discountAmount,
                'final_price' => $finalPrice,
                'price_data_full' => $priceData,
            ]);

            // Si no hay descuento, no crear plan personalizado
            if ($discountAmount <= 0) {
                Log::channel('daily')->info('PLAN PERSONALIZADO: Sin descuentos, no se crea plan', [
                    'participant_id' => $participant->id,
                    'discount_amount' => $discountAmount,
                ]);
                return [
                    'success' => false,
                    'message' => 'El participante no tiene descuentos aplicados. Use el plan general.'
                ];
            }

            // Generar código único para el plan
            $planCode = $this->generatePlanCode($programCourse, $participant);

            // Generar nombre del plan incluyendo al participante
            $planName = $this->generatePlanName($programCourse, $participant, $discountType);

            // Calcular monto mensual (convertir a enteros para VirtualPos - CLP no acepta decimales)
            $maxInstallments = $programCourse->subscription_max_months;
            $finalPriceInt = (int) round($finalPrice);
            $monthlyAmount = (int) ceil($finalPriceInt / $maxInstallments);

            // Preparar datos para VirtualPos
            $planData = [
                'code' => $planCode,
                'name' => $planName,
                'trip_price' => $finalPriceInt, // Precio con descuento (entero para CLP)
                'max_installments' => $maxInstallments,
                'immediate_first_charge' => $programCourse->immediate_first_charge,
                'trip_description' => "Plan personalizado para {$participant->full_name} - {$programCourse->name}",
            ];

            Log::channel('daily')->info('PLAN PERSONALIZADO: Datos a enviar a VirtualPos', [
                'participant_id' => $participant->id,
                'participant_name' => $participant->full_name,
                'program_course_id' => $programCourse->id,
                'original_price' => $originalPrice,
                'discount_amount' => $discountAmount,
                'final_price' => $finalPrice,
                'plan_code' => $planCode,
                'plan_name' => $planName,
                'monthly_amount' => $monthlyAmount,
                'max_installments' => $maxInstallments,
                'plan_data' => $planData,
            ]);

            // Crear plan en VirtualPos
            $result = $this->virtualPosPlanService->createPlan($planData);

            Log::channel('daily')->info('PLAN PERSONALIZADO: Respuesta de VirtualPos', [
                'participant_id' => $participant->id,
                'result' => $result,
            ]);

            if (!$result || !$result['success'] || !isset($result['plan_id'])) {
                Log::channel('daily')->error('PLAN PERSONALIZADO: Error al crear plan en VirtualPos', [
                    'participant_id' => $participant->id,
                    'error' => $result['error'] ?? 'Error desconocido',
                    'full_result' => $result,
                ]);

                return [
                    'success' => false,
                    'message' => 'Error al crear el plan en VirtualPos: ' . ($result['error'] ?? 'Error desconocido'),
                    'error_code' => $result['error_code'] ?? null,
                ];
            }

            // Guardar plan en la base de datos (convertir montos a enteros para consistencia)
            $originalPriceInt = (int) round($originalPrice);
            $discountAmountInt = (int) round($discountAmount);

            $virtualPosPlan = VirtualPosPlan::create([
                'virtualpos_plan_id' => $result['plan_id'],
                'program_course_id' => $programCourse->id,
                'participant_id' => $participant->id,
                'code' => $planCode,
                'name' => $planName,
                'description' => "Plan personalizado - {$discountType}",
                'trip_price' => $finalPriceInt,
                'original_price' => $originalPriceInt,
                'discount_amount' => $discountAmountInt,
                'discount_type' => $discountType,
                'discount_reason' => $discountReason,
                'monthly_amount' => $monthlyAmount,
                'max_installments' => $maxInstallments,
                'immediate_first_charge' => $programCourse->immediate_first_charge,
                'currency' => 'CLP',
                'frequency_type' => 'Mensual',
                'plan_type' => 'PROGRAMA_DE_PAGOS',
                'is_active' => true,
                'api_response' => $result['data'] ?? null,
                'created_by' => auth()->id(),
            ]);

            Log::channel('daily')->info('=== PLAN PERSONALIZADO: CREADO EXITOSAMENTE ===', [
                'virtualpos_plan_id' => $result['plan_id'],
                'local_plan_id' => $virtualPosPlan->id,
                'participant_id' => $participant->id,
                'participant_name' => $participant->full_name,
                'program_course_id' => $programCourse->id,
                'original_price' => $originalPriceInt,
                'discount_amount' => $discountAmountInt,
                'final_price' => $finalPriceInt,
                'monthly_amount' => $monthlyAmount,
            ]);

            return [
                'success' => true,
                'message' => 'Plan personalizado creado exitosamente.',
                'plan' => $virtualPosPlan,
                'virtualpos_plan_id' => $result['plan_id'],
                'price_details' => [
                    'original_price' => $originalPriceInt,
                    'discount_amount' => $discountAmountInt,
                    'final_price' => $finalPriceInt,
                    'monthly_amount' => $monthlyAmount,
                    'max_installments' => $maxInstallments,
                ]
            ];

        } catch (\Exception $e) {
            Log::channel('daily')->error('PLAN PERSONALIZADO: Excepción al crear plan', [
                'participant_id' => $participant->id,
                'program_course_id' => $programCourse->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'message' => 'Error al crear el plan: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Desactivar plan personalizado de un participante
     */
    public function deactivatePlan(int $participantId, int $programCourseId): bool
    {
        $plan = VirtualPosPlan::findForParticipant($participantId, $programCourseId);

        if ($plan) {
            $plan->update(['is_active' => false]);

            Log::channel('daily')->info('PLAN PERSONALIZADO: Plan desactivado para recreación', [
                'plan_id' => $plan->id,
                'virtualpos_plan_id' => $plan->virtualpos_plan_id,
                'participant_id' => $participantId,
                'program_course_id' => $programCourseId,
            ]);

            return true;
        }

        Log::channel('daily')->info('PLAN PERSONALIZADO: No se encontró plan para desactivar', [
            'participant_id' => $participantId,
            'program_course_id' => $programCourseId,
        ]);

        return false;
    }

    /**
     * Verificar si un participante necesita plan personalizado (tiene descuentos)
     */
    public function needsPersonalizedPlan(Participant $participant, ProgramCourse $programCourse): array
    {
        $priceData = ParticipantPriceHelper::calculateParticipantPrice($participant, $programCourse);

        $hasDiscount = $priceData['discounts'] > 0;
        $existingPlan = VirtualPosPlan::findForParticipant($participant->id, $programCourse->id);

        // Verificar si tiene suscripción activa
        $activeSubscription = ProgramSubscription::where('participant_id', $participant->id)
            ->where('program_id', $programCourse->id)
            ->whereIn('status', ['ACTIVA', 'SUSCRIBIENDO'])
            ->first();

        Log::channel('daily')->info('PLAN PERSONALIZADO: Verificación de necesidad', [
            'participant_id' => $participant->id,
            'participant_name' => $participant->full_name,
            'program_course_id' => $programCourse->id,
            'has_discount' => $hasDiscount,
            'discount_amount' => $priceData['discounts'],
            'base_price' => $priceData['base_price'],
            'final_price' => $priceData['final_price'],
            'has_existing_plan' => !is_null($existingPlan),
            'existing_plan_id' => $existingPlan?->id,
            'has_active_subscription' => !is_null($activeSubscription),
            'active_subscription_id' => $activeSubscription?->id,
        ]);

        return [
            // Solo necesita plan si tiene descuento, no tiene plan existente, y NO tiene suscripción activa
            'needs_plan' => $hasDiscount && !$existingPlan && !$activeSubscription,
            'has_discount' => $hasDiscount,
            'has_existing_plan' => !is_null($existingPlan),
            'has_active_subscription' => !is_null($activeSubscription),
            'price_data' => $priceData,
            'existing_plan' => $existingPlan,
            'active_subscription' => $activeSubscription,
        ];
    }

    /**
     * Generar código único para el plan
     */
    private function generatePlanCode(ProgramCourse $programCourse, Participant $participant): string
    {
        // Formato: PC{programCourseId}-P{participantId}
        return "PC{$programCourse->id}-P{$participant->id}";
    }

    /**
     * Generar nombre descriptivo del plan
     */
    private function generatePlanName(
        ProgramCourse $programCourse,
        Participant $participant,
        string $discountType
    ): string {
        $discountLabel = match($discountType) {
            'scholarship' => 'Beca',
            'released' => 'Desc. Especial',
            default => 'Descuento'
        };

        // Formato: "{NombrePrograma} - {NombreParticipante} ({TipoDescuento})"
        $participantName = $participant->first_name . ' ' . $participant->first_last_name;

        return "{$programCourse->name} - {$participantName} ({$discountLabel})";
    }

    /**
     * Obtener información del plan para mostrar confirmación
     */
    public function getPlanPreview(Participant $participant, ProgramCourse $programCourse): array
    {
        $priceData = ParticipantPriceHelper::calculateParticipantPrice($participant, $programCourse);
        $maxInstallments = $programCourse->subscription_max_months;
        $monthlyAmount = $priceData['final_price'] / $maxInstallments;

        return [
            'participant' => [
                'id' => $participant->id,
                'name' => $participant->full_name,
                'document' => $participant->document_number,
            ],
            'program_course' => [
                'id' => $programCourse->id,
                'name' => $programCourse->name,
                'code' => $programCourse->code,
            ],
            'pricing' => [
                'original_price' => $priceData['base_price'],
                'discount_amount' => $priceData['discounts'],
                'final_price' => $priceData['final_price'],
                'monthly_amount' => round($monthlyAmount),
                'max_installments' => $maxInstallments,
            ],
            'plan_name_preview' => $this->generatePlanName($programCourse, $participant, 'scholarship'),
            'plan_code_preview' => $this->generatePlanCode($programCourse, $participant),
        ];
    }
}
