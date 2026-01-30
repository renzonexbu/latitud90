<?php

namespace App\Services\VirtualPos;

use App\Models\ProgramSubscription;
use App\Models\InstallmentPlan;
use App\Models\Payment;
use App\Services\Subscription\VirtualPosSubscriptionService;
use App\Jobs\SyncSubscriptionPaymentsJob;
use App\Traits\SystemLogging;
use Illuminate\Support\Facades\Log;
use Exception;

class SyncSubscriptionService
{
    use SystemLogging;

    private VirtualPosSubscriptionService $virtualPosService;

    public function __construct(VirtualPosSubscriptionService $virtualPosService)
    {
        $this->virtualPosService = $virtualPosService;
    }

    /**
     * Sincronizar una suscripción con VirtualPos
     */
    public function sync(ProgramSubscription $subscription): array
    {
        try {
            Log::channel('daily')->info('=== SYNC: Iniciando sincronización con VirtualPos ===', [
                'subscription_id' => $subscription->id,
                'virtualpos_subscription_id' => $subscription->virtualpos_subscription_id,
                'current_status' => $subscription->status,
            ]);

            if (empty($subscription->virtualpos_subscription_id)) {
                Log::channel('daily')->warning('SYNC: Suscripción sin ID de VirtualPos', [
                    'subscription_id' => $subscription->id,
                ]);
                return [
                    'success' => false,
                    'message' => 'La suscripción no tiene ID de VirtualPos asociado.'
                ];
            }

            Log::channel('daily')->info('SYNC: Llamando a VirtualPos API getSubscription...', [
                'virtualpos_subscription_id' => $subscription->virtualpos_subscription_id,
            ]);

            // Obtener datos de VirtualPos
            $virtualPosData = $this->virtualPosService->getSubscription($subscription->virtualpos_subscription_id);

            Log::channel('daily')->info('SYNC: Respuesta de VirtualPos API', [
                'data_received' => !empty($virtualPosData),
                'response_keys' => is_array($virtualPosData) ? array_keys($virtualPosData) : 'no es array',
                'raw_response' => $virtualPosData,
            ]);

            if (empty($virtualPosData)) {
                Log::channel('daily')->error('SYNC: No se recibieron datos de VirtualPos');
                return [
                    'success' => false,
                    'message' => 'No se pudo obtener información de VirtualPos.'
                ];
            }

            // Los datos vienen bajo la clave 'suscription'
            $subscriptionData = $virtualPosData['suscription'] ?? $virtualPosData;

            Log::channel('daily')->info('SYNC: Datos de suscripción extraídos', [
                'status_virtualpos' => $subscriptionData['status'] ?? 'no definido',
                'charge_program_count' => isset($subscriptionData['charge_program']) ? count($subscriptionData['charge_program']) : 0,
            ]);

            // Actualizar estado de la suscripción
            $newStatus = $this->mapVirtualPosStatus($subscriptionData['status'] ?? null);
            $oldStatus = $subscription->status;

            Log::channel('daily')->info('SYNC: Actualizando estado de suscripción', [
                'old_status' => $oldStatus,
                'new_status' => $newStatus,
                'status_changed' => $oldStatus !== $newStatus,
            ]);

            $subscription->update([
                'status' => $newStatus,
                'api_response' => $virtualPosData,
                'last_synced_at' => now(),
            ]);

            // Sincronizar cuotas si hay programa de cargos
            $chargeProgram = $subscriptionData['charge_program'] ?? [];
            $installmentsSynced = 0;
            $additionalCharges = [];
            $pendingPayments = [];

            if (!empty($chargeProgram)) {
                Log::channel('daily')->info('SYNC: Sincronizando cuotas...', [
                    'charge_program_count' => count($chargeProgram),
                ]);
                $syncResult = $this->syncInstallments($subscription, $chargeProgram);
                $installmentsSynced = $syncResult['synced'];
                $additionalCharges = $syncResult['additional_charges'];
                $pendingPayments = $syncResult['pending_payments'] ?? [];
            }

            // Guardar cargos adicionales en api_response para que estén disponibles
            if (!empty($additionalCharges)) {
                $apiResponse = $subscription->api_response ?? [];
                $apiResponse['additional_charges'] = $additionalCharges;
                $subscription->update(['api_response' => $apiResponse]);
            }

            // Si hay pagos pendientes de procesar, disparar el job de sincronización de pagos
            // NOTA: No se envían emails cuando se llama desde sync() para evitar envíos duplicados
            $jobDispatched = false;
            if (!empty($pendingPayments)) {
                Log::channel('daily')->info('SYNC: Disparando SyncSubscriptionPaymentsJob para procesar pagos pendientes', [
                    'subscription_id' => $subscription->id,
                    'pending_payments_count' => count($pendingPayments),
                ]);

                // Usar dispatchSync para ejecutar de forma síncrona
                // No enviar emails para evitar duplicados (el email se enviará manualmente o por el comando)
                SyncSubscriptionPaymentsJob::dispatchSync($subscription->id, false);
                $jobDispatched = true;
            }

            Log::channel('daily')->info('=== SYNC: Sincronización completada ===', [
                'subscription_id' => $subscription->id,
                'old_status' => $oldStatus,
                'new_status' => $newStatus,
                'installments_synced' => $installmentsSynced,
                'additional_charges_count' => count($additionalCharges),
                'pending_payments_count' => count($pendingPayments),
                'sync_payments_job_dispatched' => $jobDispatched,
            ]);

            $this->logOperationSuccess(
                "Sincronización de suscripción {$subscription->virtualpos_subscription_id}",
                [
                    'subscription_id' => $subscription->id,
                    'old_status' => $oldStatus,
                    'new_status' => $newStatus,
                    'installments_synced' => $installmentsSynced,
                    'additional_charges_count' => count($additionalCharges),
                ]
            );

            return [
                'success' => true,
                'message' => 'Suscripción sincronizada correctamente.',
                'data' => [
                    'old_status' => $oldStatus,
                    'new_status' => $newStatus,
                    'installments_synced' => $installmentsSynced,
                    'additional_charges' => $additionalCharges,
                    'virtualpos_data' => $subscriptionData,
                ]
            ];

        } catch (Exception $e) {
            Log::channel('daily')->error('=== SYNC: ERROR en sincronización ===', [
                'subscription_id' => $subscription->id,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'message' => 'Error al sincronizar: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Mapear estado de VirtualPos a estado interno
     */
    private function mapVirtualPosStatus(?string $virtualPosStatus): string
    {
        $statusMap = [
            'ACTIVA' => 'ACTIVA',
            'SUSCRIBIENDO' => 'SUSCRIBIENDO',
            'CANCELADA' => 'CANCELADA',
            'FINALIZADA' => 'FINALIZADA',
            'PENDIENTE' => 'PENDIENTE',
            'RECHAZADA' => 'RECHAZADA',
            'SUSCRIPCION_FALLIDA' => 'SUSCRIPCION_FALLIDA',
        ];

        return $statusMap[strtoupper($virtualPosStatus ?? '')] ?? $virtualPosStatus ?? 'PENDIENTE';
    }

    /**
     * Sincronizar cuotas desde el programa de cargos de VirtualPos
     * Retorna array con cantidad sincronizada, cargos adicionales y pagos pendientes de procesar
     */
    private function syncInstallments(ProgramSubscription $subscription, array $chargeProgram): array
    {
        Log::channel('daily')->info('SYNC: Buscando plan de cuotas...', [
            'participant_id' => $subscription->participant_id,
            'program_id' => $subscription->program_id,
        ]);

        $installmentPlan = InstallmentPlan::where('participant_id', $subscription->participant_id)
            ->where('program_id', $subscription->program_id)
            ->first();

        if (!$installmentPlan) {
            Log::channel('daily')->warning('SYNC: No se encontró plan de cuotas para sincronizar', [
                'subscription_id' => $subscription->id,
                'participant_id' => $subscription->participant_id,
                'program_id' => $subscription->program_id,
            ]);
            return ['synced' => 0, 'additional_charges' => [], 'pending_payments' => []];
        }

        Log::channel('daily')->info('SYNC: Plan de cuotas encontrado', [
            'installment_plan_id' => $installmentPlan->id,
            'total_installments' => $installmentPlan->total_installments,
        ]);

        $syncedCount = 0;
        $additionalCharges = [];
        $pendingPayments = []; // Pagos en VirtualPOS sin Payment local

        foreach ($chargeProgram as $index => $charge) {
            $chargeId = $charge['id'] ?? null;
            $chargeStatus = $charge['status'] ?? null;

            // VirtualPos puede enviar el número en 'num_charge' o en la descripción "Cargo X de Y"
            $chargeNumber = $charge['num_charge'] ?? null;

            // Si no hay num_charge, intentar extraerlo de la descripción
            if (!$chargeNumber && !empty($charge['description'])) {
                // Buscar patrón "Cargo X de Y" o similar
                if (preg_match('/Cargo\s+(\d+)\s+de\s+\d+/i', $charge['description'], $matches)) {
                    $chargeNumber = (int) $matches[1];
                }
            }

            Log::channel('daily')->debug("SYNC: Procesando cargo #{$index}", [
                'charge_id' => $chargeId,
                'charge_status' => $chargeStatus,
                'charge_number' => $chargeNumber,
                'description' => $charge['description'] ?? null,
                'amount' => $charge['amount'] ?? null,
            ]);

            if (!$chargeId) {
                Log::channel('daily')->debug("SYNC: Cargo #{$index} omitido - sin ID");
                continue;
            }

            // Si no tiene número de cargo, es un cargo adicional
            if (!$chargeNumber) {
                Log::channel('daily')->info("SYNC: Cargo adicional detectado", [
                    'charge_id' => $chargeId,
                    'description' => $charge['description'] ?? null,
                    'amount' => $charge['amount'] ?? null,
                ]);
                $additionalCharges[] = $charge;
                continue;
            }

            // Buscar la cuota correspondiente
            $installment = $installmentPlan->installments()
                ->where('installment_number', $chargeNumber)
                ->first();

            if (!$installment) {
                Log::channel('daily')->debug("SYNC: No se encontró cuota local para cargo #{$chargeNumber}");
                // También es un cargo adicional si no hay cuota local
                $additionalCharges[] = $charge;
                continue;
            }

            // Actualizar solo el virtualpos_charge_id
            // IMPORTANTE: NO marcar cuotas como pagadas aquí
            // Esa responsabilidad es del SyncSubscriptionPaymentsJob que crea:
            // Order, OrderDetail, Payment y luego marca la cuota como pagada
            $isPaid = in_array(strtolower($chargeStatus), ['paid', 'pagado', 'approved', 'aprobado']);
            $isCancelled = in_array(strtolower($chargeStatus), ['cancelled', 'cancelado']);

            $updateData = [
                'virtualpos_charge_id' => $chargeId,
            ];

            // Solo marcar como cancelada (no requiere crear Payment)
            if ($isCancelled && $installment->status !== 'cancelled') {
                $updateData['status'] = 'cancelled';
                Log::channel('daily')->info("SYNC: Cuota #{$chargeNumber} marcada como CANCELADA", [
                    'installment_id' => $installment->id,
                    'charge_id' => $chargeId,
                ]);
            }

            // Si está pagado en VirtualPOS pero no localmente, verificar si existe Payment
            if ($isPaid && !$installment->is_paid) {
                // Verificar si ya existe un Payment con este charge_id
                $existingPayment = Payment::where('external_payment_id', $chargeId)->exists();

                if (!$existingPayment) {
                    Log::channel('daily')->info("SYNC: Cuota #{$chargeNumber} PAGADA en VirtualPOS pero sin Payment local", [
                        'installment_id' => $installment->id,
                        'charge_id' => $chargeId,
                    ]);
                    $pendingPayments[] = [
                        'charge' => $charge,
                        'installment_number' => $chargeNumber,
                        'installment_id' => $installment->id,
                    ];
                }
            }

            $installment->update($updateData);
            $syncedCount++;
        }

        Log::channel('daily')->info('SYNC: Sincronización de cuotas completada', [
            'total_charges_processed' => count($chargeProgram),
            'installments_synced' => $syncedCount,
            'additional_charges_count' => count($additionalCharges),
            'pending_payments_count' => count($pendingPayments),
        ]);

        return ['synced' => $syncedCount, 'additional_charges' => $additionalCharges, 'pending_payments' => $pendingPayments];
    }
}
