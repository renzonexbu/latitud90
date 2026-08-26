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

        // Buscar el plan de ESTA suscripción. Antes se buscaba por participant_id +
        // program_id con ->first(): como cada reintento de suscripción crea un plan
        // nuevo (hay participantes con más de 10), escribía los charge_id de esta
        // suscripción sobre el plan de otra.
        $orderIds = \App\Models\Order::where('subscription_id', $subscription->id)->pluck('id');

        $installmentPlan = InstallmentPlan::where(function ($q) use ($subscription, $orderIds) {
                $q->where('program_subscription_id', $subscription->id);

                if ($orderIds->isNotEmpty()) {
                    $q->orWhere(function ($legacy) use ($orderIds) {
                        $legacy->whereNull('program_subscription_id')
                            ->whereIn('order_id', $orderIds);
                    });
                }
            })
            ->orderByDesc('id')
            ->first();

        // Fallback para datos antiguos sin vínculo: solo si NO hay ambigüedad.
        // Con varios planes en el mismo programa preferimos no sincronizar antes
        // que escribir sobre el plan equivocado.
        if (!$installmentPlan) {
            $legacyPlans = InstallmentPlan::where('participant_id', $subscription->participant_id)
                ->where('program_id', $subscription->program_id)
                ->get();

            if ($legacyPlans->count() === 1) {
                $installmentPlan = $legacyPlans->first();
            } elseif ($legacyPlans->count() > 1) {
                Log::channel('daily')->warning('SYNC: Varios planes sin vínculo a la suscripción, no se sincroniza para no pisar el equivocado', [
                    'subscription_id' => $subscription->id,
                    'participant_id' => $subscription->participant_id,
                    'program_id' => $subscription->program_id,
                    'plan_ids' => $legacyPlans->pluck('id')->all(),
                ]);
            }
        }

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
        $restoredAny = false;  // Alguna cuota volvió de 'cancelled' a 'pending'

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

            // Solo marcar como cancelada si NO hay cobro real local.
            // Tras cancelar una suscripción en VirtualPos, cargos YA PAGADOS a veces
            // aparecen como "cancelado" en charge_program; si pisamos status='cancelled'
            // aquí, ProgramDetailService deja de contarlos y el PAT infla el "Pagarás"
            // (caso Blanca Del Real / cuotas con payment_id intacto).
            $hasLocalPayment = $installment->is_paid
                || $installment->payment_id
                || $installment->status === 'paid';

            if ($isCancelled && $installment->status !== 'cancelled' && !$hasLocalPayment) {
                $updateData['status'] = 'cancelled';
                Log::channel('daily')->info("SYNC: Cuota #{$chargeNumber} marcada como CANCELADA", [
                    'installment_id' => $installment->id,
                    'charge_id' => $chargeId,
                ]);
            } elseif ($isCancelled && $hasLocalPayment) {
                Log::channel('daily')->info("SYNC: Cuota #{$chargeNumber} NO cancelada (tiene pago local)", [
                    'installment_id' => $installment->id,
                    'charge_id' => $chargeId,
                    'payment_id' => $installment->payment_id,
                    'status' => $installment->status,
                    'is_paid' => $installment->is_paid,
                ]);
            }

            // Reverso simétrico de la regla anterior: si VirtualPos mantiene el cargo
            // vivo (pendiente/procesando) pero la cuota local está cancelada y sin
            // cobro, devolverla a 'pending'. Sin esto una cancelación errónea dejaba
            // la cuota muerta para siempre: VirtualPos la cobraba y el sistema no
            // actualizaba el estado ni emitía la boleta hasta refrescar a mano.
            $isAlive = in_array(strtolower($chargeStatus), ['pendiente', 'pending', 'procesando', 'processing']);

            if ($isAlive && $installment->status === 'cancelled' && !$hasLocalPayment) {
                $updateData['status'] = 'pending';
                $restoredAny = true;
                Log::channel('daily')->warning("SYNC: Cuota #{$chargeNumber} RESTAURADA a pendiente (cargo vivo en VirtualPos)", [
                    'installment_id' => $installment->id,
                    'charge_id' => $chargeId,
                    'charge_status' => $chargeStatus,
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

        // Un plan cancelado con cuotas vivas en VirtualPos es inconsistente: si se
        // restauró alguna cuota, el plan tiene que volver a 'active' o el portal
        // sigue tratando al participante como si no tuviera plan de cuotas.
        if ($restoredAny && $installmentPlan->status === 'cancelled') {
            $installmentPlan->update(['status' => 'active']);
            Log::channel('daily')->warning('SYNC: Plan de cuotas REACTIVADO (tenía cuotas vivas en VirtualPos)', [
                'installment_plan_id' => $installmentPlan->id,
                'subscription_id' => $subscription->id,
            ]);
        }

        Log::channel('daily')->info('SYNC: Sincronización de cuotas completada', [
            'total_charges_processed' => count($chargeProgram),
            'installments_synced' => $syncedCount,
            'additional_charges_count' => count($additionalCharges),
            'pending_payments_count' => count($pendingPayments),
            'installments_restored' => $restoredAny,
        ]);

        return ['synced' => $syncedCount, 'additional_charges' => $additionalCharges, 'pending_payments' => $pendingPayments];
    }
}
