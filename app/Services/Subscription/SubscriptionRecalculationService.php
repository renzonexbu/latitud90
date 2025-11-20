<?php

namespace App\Services\Subscription;

use App\Models\ProgramSubscription;
use App\Models\Participant;
use App\Models\ProgramCourse;
use App\Models\InstallmentPlan;
use App\Services\Subscription\VirtualPosSubscriptionService;
use App\Services\Subscription\VirtualPosPlanService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Exception;

/**
 * Servicio para recalcular y recrear suscripciones cuando se realizan pagos presenciales o devoluciones
 */
class SubscriptionRecalculationService
{
    protected $virtualPosService;
    protected $virtualPosPlanService;

    public function __construct(
        VirtualPosSubscriptionService $virtualPosService,
        VirtualPosPlanService $virtualPosPlanService
    ) {
        $this->virtualPosService = $virtualPosService;
        $this->virtualPosPlanService = $virtualPosPlanService;
    }

    /**
     * Procesar pago presencial o devolución ajustando suscripción si existe
     *
     * @param int $participantId
     * @param int $programId
     * @param float $amount Monto del pago (positivo para pago, negativo para devolución)
     * @param string $type 'payment' o 'refund'
     * @return array Resultado del proceso
     */
    public function processPaymentWithSubscriptionAdjustment(
        int $participantId,
        int $programId,
        float $amount,
        string $type = 'payment'
    ): array {
        Log::info('SubscriptionRecalculation: Iniciando proceso', [
            'participant_id' => $participantId,
            'program_id' => $programId,
            'amount' => $amount,
            'type' => $type
        ]);

        try {
            // 1. Verificar si existe suscripción activa
            $subscription = $this->getActiveSubscription($participantId, $programId);

            if (!$subscription) {
                Log::info('SubscriptionRecalculation: No hay suscripción activa', [
                    'participant_id' => $participantId,
                    'program_id' => $programId
                ]);

                return [
                    'success' => true,
                    'has_subscription' => false,
                    'message' => 'No hay suscripción activa, procesar pago normalmente'
                ];
            }

            // 2. Consultar estado actual en VirtualPos
            $virtualPosData = $this->getSubscriptionFromVirtualPos($subscription);

            // 3. Analizar mensualidades y calcular montos
            $analysis = $this->analyzeSubscriptionCharges($virtualPosData);

            Log::info('SubscriptionRecalculation: Análisis de suscripción', [
                'subscription_id' => $subscription->id,
                'paid_charges' => $analysis['paid_charges_count'],
                'pending_charges' => $analysis['pending_charges_count'],
                'paid_amount' => $analysis['paid_amount'],
                'pending_amount' => $analysis['pending_amount']
            ]);

            // 4. Calcular nuevo monto pendiente
            $newPendingAmount = $this->calculateNewPendingAmount(
                $analysis['pending_amount'],
                $amount,
                $type
            );

            if ($newPendingAmount <= 0) {
                Log::warning('SubscriptionRecalculation: El nuevo monto pendiente es <= 0', [
                    'pending_amount' => $analysis['pending_amount'],
                    'adjustment_amount' => $amount,
                    'new_pending_amount' => $newPendingAmount
                ]);

                return [
                    'success' => false,
                    'has_subscription' => true,
                    'error' => 'El pago excede el monto pendiente de la suscripción',
                    'pending_amount' => $analysis['pending_amount']
                ];
            }

            // 5. Calcular número de cuotas para la nueva suscripción
            $newInstallments = $this->calculateNewInstallments($analysis);

            Log::info('SubscriptionRecalculation: Calculando nueva suscripción', [
                'old_pending_amount' => $analysis['pending_amount'],
                'adjustment' => $amount,
                'type' => $type,
                'new_pending_amount' => $newPendingAmount,
                'new_installments' => $newInstallments
            ]);

            // 6. Crear nuevo plan en VirtualPos con el número correcto de cuotas
            $newPlan = $this->createDynamicPlan(
                $subscription->participant_id,
                $subscription->program_id,
                $newPendingAmount,
                $newInstallments
            );

            if (!$newPlan['success']) {
                throw new Exception('Error creando plan en VirtualPos: ' . ($newPlan['error'] ?? 'Unknown'));
            }

            // 7. Cancelar suscripción actual
            $this->cancelSubscription($subscription);

            // 8. Crear nueva suscripción con el nuevo plan
            $newSubscription = $this->createNewSubscription(
                $participantId,
                $programId,
                $newPendingAmount,
                $newInstallments,
                $newPlan['plan_id']
            );

            if (!$newSubscription['success']) {
                throw new Exception('Error creando nueva suscripción: ' . ($newSubscription['error'] ?? 'Unknown'));
            }

            // 9. Retornar resultado exitoso
            return [
                'success' => true,
                'has_subscription' => true,
                'subscription_cancelled' => true,
                'old_subscription_id' => $subscription->id,
                'new_subscription_id' => $newSubscription['subscription']->id,
                'new_virtualpos_id' => $newSubscription['subscription']->virtualpos_subscription_id,
                'new_plan_id' => $newPlan['plan_id'],
                'payment_amount' => $amount,
                'new_pending_amount' => $newPendingAmount,
                'new_installments' => $newInstallments,
                'paid_charges' => $analysis['paid_charges_count'],
                'pending_charges' => $analysis['pending_charges_count'],
                'message' => 'Suscripción cancelada y recreada con nuevo plan de ' . $newInstallments . ' cuotas.'
            ];

        } catch (Exception $e) {
            Log::error('SubscriptionRecalculation: Error en proceso', [
                'participant_id' => $participantId,
                'program_id' => $programId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            throw $e;
        }
    }

    /**
     * Obtener suscripción activa para un participante y programa
     */
    protected function getActiveSubscription(int $participantId, int $programId): ?ProgramSubscription
    {
        return ProgramSubscription::where('participant_id', $participantId)
            ->where('program_id', $programId)
            ->where('status', 'ACTIVA')
            ->whereNotNull('virtualpos_subscription_id')
            ->first();
    }

    /**
     * Consultar estado actual de la suscripción en VirtualPos
     */
    protected function getSubscriptionFromVirtualPos(ProgramSubscription $subscription): array
    {
        try {
            $virtualPosData = $this->virtualPosService->getSubscription($subscription->virtualpos_subscription_id);

            if (!$virtualPosData) {
                throw new Exception('No se pudo obtener datos de VirtualPos');
            }

            // Si VirtualPos no devuelve charge_program o está vacío, usar datos locales como fallback
            $chargeProgram = $virtualPosData['charge_program'] ?? [];
            if (empty($chargeProgram) && !empty($subscription->charge_program)) {
                Log::info('SubscriptionRecalculation: Usando charge_program local como fallback', [
                    'subscription_id' => $subscription->id,
                    'local_charges_count' => count($subscription->charge_program)
                ]);
                $chargeProgram = $subscription->charge_program;
            }

            // Actualizar charge_program local con datos actualizados (si los hay)
            if (!empty($virtualPosData['charge_program'])) {
                $subscription->update([
                    'charge_program' => $virtualPosData['charge_program']
                ]);
            }

            // Retornar datos con el charge_program correcto
            return array_merge($virtualPosData, ['charge_program' => $chargeProgram]);

        } catch (Exception $e) {
            Log::error('SubscriptionRecalculation: Error obteniendo suscripción de VirtualPos', [
                'subscription_id' => $subscription->id,
                'virtualpos_id' => $subscription->virtualpos_subscription_id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Analizar charges de la suscripción
     */
    protected function analyzeSubscriptionCharges(array $virtualPosData): array
    {
        $chargeProgram = $virtualPosData['charge_program'] ?? [];

        $paidCharges = [];
        $pendingCharges = [];
        $paidAmount = 0;
        $pendingAmount = 0;

        foreach ($chargeProgram as $charge) {
            $status = strtolower($charge['status'] ?? '');
            $amount = $charge['amount'] ?? 0;

            if ($status === 'pagado') {
                $paidCharges[] = $charge;
                $paidAmount += $amount;
            } else if (in_array($status, ['pendiente', 'procesando'])) {
                $pendingCharges[] = $charge;
                $pendingAmount += $amount;
            }
        }

        return [
            'total_charges' => count($chargeProgram),
            'paid_charges' => $paidCharges,
            'paid_charges_count' => count($paidCharges),
            'pending_charges' => $pendingCharges,
            'pending_charges_count' => count($pendingCharges),
            'paid_amount' => $paidAmount,
            'pending_amount' => $pendingAmount,
        ];
    }

    /**
     * Calcular nuevo monto pendiente
     */
    protected function calculateNewPendingAmount(float $currentPending, float $amount, string $type): float
    {
        if ($type === 'payment') {
            // Pago presencial reduce el monto pendiente
            return $currentPending - $amount;
        } else if ($type === 'refund') {
            // Devolución aumenta el monto pendiente
            return $currentPending + $amount;
        }

        return $currentPending;
    }

    /**
     * Calcular número de cuotas para la nueva suscripción
     * Mantiene el número de cuotas pendientes que quedaban
     */
    protected function calculateNewInstallments(array $analysis): int
    {
        $pendingCount = $analysis['pending_charges_count'];

        // Si no hay cuotas pendientes pero hay cuotas pagadas, usar el total
        if ($pendingCount === 0 && $analysis['paid_charges_count'] > 0) {
            return $analysis['total_charges'];
        }

        // Si no hay cuotas pendientes ni pagadas, por defecto 1 cuota
        if ($pendingCount === 0) {
            return 1;
        }

        return $pendingCount;
    }

    /**
     * Cancelar suscripción actual en VirtualPos
     */
    protected function cancelSubscription(ProgramSubscription $subscription): void
    {
        try {
            Log::info('SubscriptionRecalculation: Cancelando suscripción', [
                'subscription_id' => $subscription->id,
                'virtualpos_id' => $subscription->virtualpos_subscription_id
            ]);

            // Cancelar en VirtualPos
            $this->virtualPosService->cancelSubscription($subscription->virtualpos_subscription_id);

            // Actualizar estado local
            $subscription->update([
                'status' => 'CANCELADA',
                'cancelled_at' => now()
            ]);

            Log::info('SubscriptionRecalculation: Suscripción cancelada exitosamente', [
                'subscription_id' => $subscription->id
            ]);

        } catch (Exception $e) {
            Log::error('SubscriptionRecalculation: Error cancelando suscripción', [
                'subscription_id' => $subscription->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Crear nueva suscripción con los nuevos montos
     *
     * @param int $participantId
     * @param int $programId
     * @param float $amount Nuevo monto total
     * @param int $installments Número de cuotas
     * @param string|null $planId Plan ID de VirtualPos (opcional, se obtiene de la suscripción anterior si no se proporciona)
     * @return array Datos de la nueva suscripción
     */
    public function createNewSubscription(
        int $participantId,
        int $programId,
        float $amount,
        int $installments,
        ?string $planId = null
    ): array {
        try {
            Log::info('SubscriptionRecalculation: Creando nueva suscripción', [
                'participant_id' => $participantId,
                'program_id' => $programId,
                'amount' => $amount,
                'installments' => $installments
            ]);

            // Obtener datos del participante
            $participant = Participant::findOrFail($participantId);
            $programCourse = ProgramCourse::findOrFail($programId);

            // Obtener contacto de emergencia para datos de suscripción
            $emergencyContact = $participant->emergencyContacts()->first();

            if (!$emergencyContact) {
                throw new Exception('No se encontró contacto de emergencia para el participante');
            }

            // Obtener plan_id: usar el proporcionado, o buscar de suscripción cancelada, o del programa
            if (!$planId) {
                $planId = $this->getPlanIdForProgram($participantId, $programId);
            }

            // Preparar datos para la nueva suscripción
            $subscriptionData = [
                'plan_id' => $planId,
                'service_id' => config('virtualpos.service_id'),
                'email' => $emergencyContact->email,
                'return_url' => base64_encode(route('api.subscription.return')),
                'callback_url' => base64_encode(route('api.subscription.callback')),
                'first_name' => $participant->first_name,
                'last_name' => $participant->first_last_name,
                'social_id' => $participant->document_number,
                'phone_number' => $emergencyContact->phone ?? '',
            ];

            // Crear suscripción en VirtualPos
            $virtualPosResponse = $this->virtualPosService->createSubscription($subscriptionData);

            // Crear registro local
            $newSubscription = ProgramSubscription::create([
                'participant_id' => $participantId,
                'program_id' => $programId,
                'virtualpos_subscription_id' => $virtualPosResponse['id'] ?? null,
                'virtualpos_plan_id' => $planId,
                'status' => 'SUSCRIBIENDO',
                'amount' => $amount / $installments, // Monto por cuota
                'automatic_renewal' => 'F',
                'subscription_date' => now(),
                'charge_program' => $virtualPosResponse['charge_program'] ?? [],
                'api_response' => $virtualPosResponse,
            ]);

            Log::info('SubscriptionRecalculation: Nueva suscripción creada', [
                'subscription_id' => $newSubscription->id,
                'virtualpos_id' => $newSubscription->virtualpos_subscription_id,
                'amount_per_installment' => $amount / $installments,
                'installments' => $installments
            ]);

            return [
                'success' => true,
                'subscription' => $newSubscription,
                'virtualpos_response' => $virtualPosResponse
            ];

        } catch (Exception $e) {
            Log::error('SubscriptionRecalculation: Error creando nueva suscripción', [
                'participant_id' => $participantId,
                'program_id' => $programId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Obtener plan_id del programa
     * Busca primero de la suscripción cancelada más reciente, luego del programa
     */
    protected function getPlanIdForProgram(int $participantId, int $programId): string
    {
        // Buscar suscripción cancelada más reciente para obtener el plan_id
        $cancelledSubscription = ProgramSubscription::where('participant_id', $participantId)
            ->where('program_id', $programId)
            ->where('status', 'CANCELADA')
            ->whereNotNull('virtualpos_plan_id')
            ->orderBy('cancelled_at', 'desc')
            ->first();

        if ($cancelledSubscription && $cancelledSubscription->virtualpos_plan_id) {
            Log::info('SubscriptionRecalculation: Plan ID obtenido de suscripción cancelada', [
                'plan_id' => $cancelledSubscription->virtualpos_plan_id,
                'subscription_id' => $cancelledSubscription->id
            ]);
            return $cancelledSubscription->virtualpos_plan_id;
        }

        // Si no hay suscripción cancelada, buscar del programa/curso
        $programCourse = ProgramCourse::find($programId);
        if ($programCourse && $programCourse->virtualpos_plan_id) {
            Log::info('SubscriptionRecalculation: Plan ID obtenido del programa', [
                'plan_id' => $programCourse->virtualpos_plan_id,
                'program_id' => $programId
            ]);
            return $programCourse->virtualpos_plan_id;
        }

        // Fallback: buscar cualquier suscripción del programa para obtener el plan
        $anySubscription = ProgramSubscription::where('program_id', $programId)
            ->whereNotNull('virtualpos_plan_id')
            ->first();

        if ($anySubscription && $anySubscription->virtualpos_plan_id) {
            Log::info('SubscriptionRecalculation: Plan ID obtenido de otra suscripción', [
                'plan_id' => $anySubscription->virtualpos_plan_id,
                'subscription_id' => $anySubscription->id
            ]);
            return $anySubscription->virtualpos_plan_id;
        }

        throw new Exception('No se pudo obtener plan_id para crear la suscripción');
    }

    /**
     * Crear un plan dinámico en VirtualPos para la recalculación
     *
     * @param int $participantId
     * @param int $programId
     * @param float $totalAmount Monto total pendiente
     * @param int $installments Número de cuotas
     * @return array
     */
    protected function createDynamicPlan(
        int $participantId,
        int $programId,
        float $totalAmount,
        int $installments
    ): array {
        try {
            // Obtener datos del programa para el plan
            $programCourse = ProgramCourse::findOrFail($programId);
            $participant = Participant::findOrFail($participantId);

            // Generar ID único para el plan
            $planCode = sprintf(
                'RECALC_%s_%s_%s',
                $programCourse->code ?? $programId,
                $participant->document_number,
                time()
            );

            // Calcular monto mensual
            $monthlyAmount = $totalAmount / $installments;

            // Preparar datos del plan
            $planData = [
                'code' => $planCode,
                'name' => sprintf(
                    '%s - Recalc %d cuotas',
                    $programCourse->name ?? 'Programa',
                    $installments
                ),
                'trip_description' => sprintf(
                    'Plan recalculado: %d cuotas de $%s',
                    $installments,
                    number_format($monthlyAmount, 0, ',', '.')
                ),
                'trip_price' => $totalAmount,
                'max_installments' => $installments
            ];

            Log::info('SubscriptionRecalculation: Creando plan dinámico', [
                'plan_code' => $planCode,
                'total_amount' => $totalAmount,
                'installments' => $installments,
                'monthly_amount' => $monthlyAmount
            ]);

            // Crear plan en VirtualPos
            $result = $this->virtualPosPlanService->createPlan($planData);

            if (!$result['success']) {
                Log::error('SubscriptionRecalculation: Error creando plan dinámico', [
                    'plan_code' => $planCode,
                    'error' => $result['error'] ?? 'Unknown',
                    'full_result' => $result
                ]);
                return $result;
            }

            Log::info('SubscriptionRecalculation: Plan dinámico creado exitosamente', [
                'plan_id' => $result['plan_id'],
                'plan_code' => $planCode
            ]);

            return $result;

        } catch (Exception $e) {
            Log::error('SubscriptionRecalculation: Excepción creando plan dinámico', [
                'participant_id' => $participantId,
                'program_id' => $programId,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
}
