<?php

namespace App\Jobs;

use App\Models\ProgramSubscription;
use App\Models\Installment;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\Payment;
use App\Models\PaymentGateway;
use App\Services\Subscription\VirtualPosSubscriptionService;
use App\Services\Mail\SuccessPaymentEmailService;
use App\Services\Client\Integration\BsaleService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Exception;

class SyncSubscriptionPaymentsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $subscriptionId;
    protected $virtualPosService;
    protected $emailService;
    protected $bsaleService;

    /**
     * Constructor del Job
     *
     * @param int|null $subscriptionId ID de suscripción específica, null para procesar todas
     */
    public function __construct(?int $subscriptionId = null)
    {
        $this->subscriptionId = $subscriptionId;
    }

    /**
     * Execute the job.
     */
    public function handle(
        VirtualPosSubscriptionService $virtualPosService,
        SuccessPaymentEmailService $emailService,
        BsaleService $bsaleService
    ): void
    {
        $this->virtualPosService = $virtualPosService;
        $this->emailService = $emailService;
        $this->bsaleService = $bsaleService;

        Log::info('SyncSubscriptionPayments: Iniciando sincronización de pagos de suscripciones');

        try {
            // Obtener suscripciones a procesar
            $subscriptions = $this->getSubscriptionsToProcess();

            Log::info('SyncSubscriptionPayments: Suscripciones a procesar', [
                'count' => $subscriptions->count()
            ]);

            $processedCount = 0;
            $errorCount = 0;
            $newPaymentsCount = 0;

            foreach ($subscriptions as $subscription) {
                try {
                    $newPayments = $this->syncSubscription($subscription);
                    $newPaymentsCount += $newPayments;
                    $processedCount++;
                } catch (Exception $e) {
                    $errorCount++;
                    Log::error('SyncSubscriptionPayments: Error procesando suscripción', [
                        'subscription_id' => $subscription->id,
                        'virtualpos_subscription_id' => $subscription->virtualpos_subscription_id,
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString()
                    ]);
                }
            }

            Log::info('SyncSubscriptionPayments: Sincronización completada', [
                'processed' => $processedCount,
                'errors' => $errorCount,
                'new_payments' => $newPaymentsCount
            ]);

        } catch (Exception $e) {
            Log::error('SyncSubscriptionPayments: Error fatal en job', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    /**
     * Obtener suscripciones a procesar
     */
    protected function getSubscriptionsToProcess()
    {
        $query = ProgramSubscription::query()
            ->whereNotNull('virtualpos_subscription_id')
            ->where('status', 'ACTIVA');

        if ($this->subscriptionId) {
            $query->where('id', $this->subscriptionId);
        }

        return $query->get();
    }

    /**
     * Sincronizar una suscripción específica
     *
     * @return int Número de nuevos pagos detectados
     */
    protected function syncSubscription(ProgramSubscription $subscription): int
    {
        Log::info('SyncSubscriptionPayments: Procesando suscripción', [
            'subscription_id' => $subscription->id,
            'virtualpos_subscription_id' => $subscription->virtualpos_subscription_id
        ]);

        // Obtener estado actual desde VirtualPos
        $virtualPosData = $this->virtualPosService->getSubscription($subscription->virtualpos_subscription_id);

        if (!$virtualPosData) {
            Log::warning('SyncSubscriptionPayments: No se pudo obtener datos de VirtualPos', [
                'subscription_id' => $subscription->id
            ]);
            return 0;
        }

        // Actualizar estado de la suscripción
        $this->updateSubscriptionStatus($subscription, $virtualPosData);

        // Obtener charges actuales vs guardados
        $currentCharges = $virtualPosData['charge_program'] ?? [];
        $savedCharges = $subscription->charge_program ?? [];

        // Detectar nuevos pagos
        $newPayments = $this->detectNewPayments($currentCharges, $savedCharges);

        Log::info('SyncSubscriptionPayments: Nuevos pagos detectados', [
            'subscription_id' => $subscription->id,
            'count' => count($newPayments)
        ]);

        // Procesar cada nuevo pago
        foreach ($newPayments as $charge) {
            try {
                $this->processCharge($subscription, $charge);
            } catch (Exception $e) {
                Log::error('SyncSubscriptionPayments: Error procesando charge', [
                    'subscription_id' => $subscription->id,
                    'charge_id' => $charge['id'] ?? 'unknown',
                    'error' => $e->getMessage()
                ]);
            }
        }

        // Detectar y procesar charges rechazados
        $failedCharges = $this->detectFailedCharges($currentCharges, $subscription);

        Log::info('SyncSubscriptionPayments: Charges rechazados detectados', [
            'subscription_id' => $subscription->id,
            'count' => count($failedCharges)
        ]);

        foreach ($failedCharges as $failedCharge) {
            try {
                $this->processFailedCharge($subscription, $failedCharge);
            } catch (Exception $e) {
                Log::error('SyncSubscriptionPayments: Error procesando charge rechazado', [
                    'subscription_id' => $subscription->id,
                    'charge_id' => $failedCharge['id'] ?? 'unknown',
                    'error' => $e->getMessage()
                ]);
            }
        }

        // Actualizar charge_program guardado
        $subscription->update([
            'charge_program' => $currentCharges
        ]);

        return count($newPayments);
    }

    /**
     * Actualizar estado de la suscripción
     */
    protected function updateSubscriptionStatus(ProgramSubscription $subscription, array $virtualPosData): void
    {
        $virtualPosStatus = $virtualPosData['status'] ?? $subscription->status;
        $newStatus = $this->mapVirtualPosStatus($virtualPosStatus);

        if ($newStatus !== $subscription->status) {
            Log::info('SyncSubscriptionPayments: Actualizando estado de suscripción', [
                'subscription_id' => $subscription->id,
                'old_status' => $subscription->status,
                'virtualpos_status' => $virtualPosStatus,
                'mapped_status' => $newStatus
            ]);

            $subscription->update(['status' => $newStatus]);
        }
    }

    /**
     * Mapear status de VirtualPos a status del modelo
     */
    protected function mapVirtualPosStatus(string $virtualPosStatus): string
    {
        $statusMap = [
            'OK' => 'ACTIVA',
            'ACTIVA' => 'ACTIVA',
            'ACTIVE' => 'ACTIVA',
            'SUSCRIBIENDO' => 'SUSCRIBIENDO',
            'SUBSCRIBING' => 'SUSCRIBIENDO',
            'SUSCRIPCION_FALLIDA' => 'SUSCRIPCION_FALLIDA',
            'FAILED' => 'SUSCRIPCION_FALLIDA',
            'CANCELADA' => 'CANCELADA',
            'CANCELLED' => 'CANCELADA',
            'CANCELED' => 'CANCELADA',
            'FINALIZADA' => 'FINALIZADA',
            'FINISHED' => 'FINALIZADA',
            'COMPLETED' => 'FINALIZADA',
        ];

        return $statusMap[strtoupper($virtualPosStatus)] ?? 'ACTIVA';
    }

    /**
     * Detectar nuevos pagos comparando charges actuales vs guardados
     *
     * @return array Charges que ahora están pagados y no lo estaban antes
     */
    protected function detectNewPayments(array $currentCharges, array $savedCharges): array
    {
        $newPayments = [];

        // Crear mapa de charges guardados para fácil búsqueda
        $savedChargesMap = [];
        foreach ($savedCharges as $savedCharge) {
            $chargeId = $savedCharge['id'] ?? null;
            if ($chargeId) {
                $savedChargesMap[$chargeId] = $savedCharge;
            }
        }

        // Buscar charges que cambiaron a pagado
        foreach ($currentCharges as $currentCharge) {
            $chargeId = $currentCharge['id'] ?? null;
            $currentStatus = strtolower($currentCharge['status'] ?? '');

            if (!$chargeId || $currentStatus !== 'pagado') {
                continue;
            }

            // Verificar si es un pago nuevo
            $isNewPayment = false;

            if (!isset($savedChargesMap[$chargeId])) {
                // Charge completamente nuevo que ya viene pagado
                $isNewPayment = true;
            } else {
                // Charge existente que cambió de estado
                $savedStatus = strtolower($savedChargesMap[$chargeId]['status'] ?? '');
                if ($savedStatus !== 'pagado') {
                    $isNewPayment = true;
                }
            }

            if ($isNewPayment) {
                // Verificar que no esté ya registrado en la BD
                if (!$this->isChargeAlreadyProcessed($chargeId)) {
                    $newPayments[] = $currentCharge;
                }
            }
        }

        return $newPayments;
    }

    /**
     * Verificar si un charge ya fue procesado (existe un Payment con ese external_payment_id)
     */
    protected function isChargeAlreadyProcessed(string $chargeId): bool
    {
        return Payment::where('external_payment_id', $chargeId)->exists();
    }

    /**
     * Procesar un charge pagado
     */
    protected function processCharge(ProgramSubscription $subscription, array $charge): void
    {
        Log::info('SyncSubscriptionPayments: Procesando charge pagado', [
            'subscription_id' => $subscription->id,
            'charge_id' => $charge['id'],
            'amount' => $charge['amount'] ?? 0,
            'charge_date' => $charge['charge_date'] ?? null
        ]);

        DB::beginTransaction();

        try {
            // 1. Obtener o crear Order
            $order = $this->getOrCreateOrder($subscription);

            // 2. Determinar número de cuota desde la descripción
            $installmentNumber = $this->extractInstallmentNumber($charge['description'] ?? '');

            // 3. Crear OrderDetail para este pago
            $orderDetail = $this->createOrderDetail($order, $charge, $installmentNumber);

            // 4. Crear Payment
            $payment = $this->createPayment($orderDetail, $charge, $subscription);

            // 5. Marcar Installment como pagada
            $this->markInstallmentAsPaid($subscription, $installmentNumber, $order, $orderDetail, $payment);

            // 6. Generar factura BSale si aplica
            $this->generateBsaleInvoiceIfNeeded($subscription, $payment, $orderDetail);

            // 7. Enviar email con PDFs
            $this->sendSuccessEmail($subscription, $orderDetail, $payment, $installmentNumber);

            DB::commit();

            Log::info('SyncSubscriptionPayments: Charge procesado exitosamente', [
                'subscription_id' => $subscription->id,
                'charge_id' => $charge['id'],
                'payment_id' => $payment->id
            ]);

        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Obtener o crear Order para la suscripción
     */
    protected function getOrCreateOrder(ProgramSubscription $subscription): Order
    {
        // Buscar orden existente para esta suscripción
        $order = Order::where('participant_id', $subscription->participant_id)
            ->where('program_id', $subscription->program_id)
            ->whereIn('status', ['pending', 'paid', 'partial'])
            ->first();

        if (!$order) {
            // Crear nueva orden
            $order = Order::create([
                'participant_id' => $subscription->participant_id,
                'program_id' => $subscription->program_id,
                'total_amount' => 0, // Se actualizará con cada pago
                'status' => 'partial',
                'payment_type' => 'subscription',
                'created_at' => now(),
            ]);

            Log::info('SyncSubscriptionPayments: Orden creada', [
                'order_id' => $order->id,
                'subscription_id' => $subscription->id
            ]);
        }

        return $order;
    }

    /**
     * Extraer número de cuota desde la descripción del charge
     * Ejemplo: "Cargo 1 de 12" -> 1
     */
    protected function extractInstallmentNumber(string $description): int
    {
        // Buscar patrón "Cargo X de Y" o "X de Y"
        if (preg_match('/(\d+)\s+de\s+(\d+)/i', $description, $matches)) {
            return (int) $matches[1];
        }

        return 1; // Por defecto primera cuota
    }

    /**
     * Crear OrderDetail para este pago
     */
    protected function createOrderDetail(Order $order, array $charge, int $installmentNumber): OrderDetail
    {
        $amount = $charge['amount'] ?? 0;

        // Obtener gateway de VirtualPos
        $gateway = \App\Models\PaymentGateway::where('code', 'virtualpos')->first();

        $orderDetail = OrderDetail::create([
            'order_id' => $order->id,
            'base_amount' => $amount,
            'discount_amount' => 0,
            'amount' => $amount,
            'installment_number' => $installmentNumber,
            'installments_number' => $this->getTotalInstallments($charge['description'] ?? ''),
            'status' => 'paid',
            'is_paid' => true,
            'paid_at' => now(),
            'payment_gateway_id' => $gateway ? $gateway->id : null,
        ]);

        // Actualizar total de la orden
        $order->increment('total_amount', $amount);

        return $orderDetail;
    }

    /**
     * Extraer total de cuotas desde la descripción
     * Ejemplo: "Cargo 1 de 12" -> 12
     */
    protected function getTotalInstallments(string $description): int
    {
        if (preg_match('/(\d+)\s+de\s+(\d+)/i', $description, $matches)) {
            return (int) $matches[2];
        }

        return 1;
    }

    /**
     * Crear Payment desde el charge
     */
    protected function createPayment(OrderDetail $orderDetail, array $charge, ProgramSubscription $subscription): Payment
    {
        // Obtener gateway de VirtualPos
        $gateway = PaymentGateway::where('code', 'virtualpos')->first();

        $paymentData = $charge['payment'] ?? [];

        $payment = Payment::create([
            'order_id' => $orderDetail->order_id,
            'order_detail_id' => $orderDetail->id,
            'payment_gateway_id' => $gateway ? $gateway->id : null,
            'external_payment_id' => $charge['id'],
            'status' => 'completed',
            'amount' => $charge['amount'] ?? 0,
            'installments_number' => $paymentData['installment_number'] ?? 1,
            'installment_amount' => $paymentData['installment_amount'] ?? ($charge['amount'] ?? 0),
            'transaction_date' => $this->parseTransactionDate($charge),
            'authorization_code' => $paymentData['auth_code'] ?? null,
            'card_type' => $subscription->payment_method['brand'] ?? null,
            'card_number' => $paymentData['card_number'] ?? null,
            'gateway_response' => json_encode($charge),
            'email_sent' => false,
        ]);

        return $payment;
    }

    /**
     * Parsear fecha de transacción desde el charge
     */
    protected function parseTransactionDate(array $charge): ?Carbon
    {
        $paymentData = $charge['payment'] ?? [];

        if (isset($paymentData['authorized_at'])) {
            try {
                return Carbon::parse($paymentData['authorized_at']);
            } catch (Exception $e) {
                Log::warning('SyncSubscriptionPayments: Error parseando authorized_at', [
                    'authorized_at' => $paymentData['authorized_at']
                ]);
            }
        }

        if (isset($charge['charge_date'])) {
            try {
                return Carbon::parse($charge['charge_date']);
            } catch (Exception $e) {
                Log::warning('SyncSubscriptionPayments: Error parseando charge_date', [
                    'charge_date' => $charge['charge_date']
                ]);
            }
        }

        return now();
    }

    /**
     * Marcar Installment como pagada
     */
    protected function markInstallmentAsPaid(
        ProgramSubscription $subscription,
        int $installmentNumber,
        Order $order,
        OrderDetail $orderDetail,
        Payment $payment
    ): void
    {
        // Buscar installments asociadas a este participant y programa
        $installments = Installment::where('installment_number', $installmentNumber)
            ->whereHas('installmentPlan', function ($query) use ($subscription) {
                $query->where('participant_id', $subscription->participant_id)
                    ->where('program_id', $subscription->program_id);
            })
            ->whereIn('status', ['pending', 'overdue']) // Incluir 'overdue' para casos donde check-overdue ya corrió
            ->get();

        foreach ($installments as $installment) {
            $installment->markAsPaid(
                $order->id,
                $orderDetail->id,
                $payment->id
            );

            Log::info('SyncSubscriptionPayments: Installment marcada como pagada', [
                'installment_id' => $installment->id,
                'installment_number' => $installmentNumber,
                'payment_id' => $payment->id
            ]);
        }
    }

    /**
     * Generar factura BSale si el viaje es en el mismo año
     */
    protected function generateBsaleInvoiceIfNeeded(
        ProgramSubscription $subscription,
        Payment $payment,
        OrderDetail $orderDetail
    ): void
    {
        try {
            // Verificar si el programa es en el mismo año
            $programCourse = $subscription->programCourse;
            if (!$programCourse || !$programCourse->departure_date) {
                return;
            }

            $departureYear = Carbon::parse($programCourse->departure_date)->year;
            $currentYear = Carbon::now()->year;

            if ($departureYear !== $currentYear) {
                Log::info('SyncSubscriptionPayments: No generar BSale (viaje en año diferente)', [
                    'departure_year' => $departureYear,
                    'current_year' => $currentYear
                ]);
                return;
            }

            // Generar factura
            $bsaleResponse = $this->bsaleService->generateInvoice($orderDetail, $payment);

            if ($bsaleResponse && isset($bsaleResponse['id'])) {
                $payment->update([
                    'bsale_document_id' => $bsaleResponse['id'],
                    'bsale_number' => $bsaleResponse['number'] ?? null,
                    'bsale_token' => $bsaleResponse['token'] ?? null,
                ]);

                Log::info('SyncSubscriptionPayments: Factura BSale generada', [
                    'payment_id' => $payment->id,
                    'bsale_document_id' => $bsaleResponse['id']
                ]);
            }

        } catch (Exception $e) {
            Log::error('SyncSubscriptionPayments: Error generando factura BSale', [
                'payment_id' => $payment->id,
                'error' => $e->getMessage()
            ]);
            // No lanzar excepción, continuar con el proceso
        }
    }

    /**
     * Enviar email de pago exitoso con PDFs
     */
    protected function sendSuccessEmail(
        ProgramSubscription $subscription,
        OrderDetail $orderDetail,
        Payment $payment,
        int $installmentNumber
    ): void
    {
        try {
            // Determinar si debe enviar contrato (solo primera cuota)
            $sendContract = ($installmentNumber === 1);

            // Enviar email usando el servicio existente
            $this->emailService->sendSuccessPaymentEmail(
                $orderDetail,
                $payment,
                $sendContract
            );

            // Marcar email como enviado
            $payment->update(['email_sent' => true]);

            Log::info('SyncSubscriptionPayments: Email de pago exitoso enviado', [
                'payment_id' => $payment->id,
                'installment_number' => $installmentNumber,
                'contract_sent' => $sendContract
            ]);

        } catch (Exception $e) {
            Log::error('SyncSubscriptionPayments: Error enviando email', [
                'payment_id' => $payment->id,
                'error' => $e->getMessage()
            ]);
            // No lanzar excepción, continuar con el proceso
        }
    }

    /**
     * Detectar charges rechazados que no han sido procesados
     *
     * @return array Charges rechazados que aún no se han notificado
     */
    protected function detectFailedCharges(array $currentCharges, ProgramSubscription $subscription): array
    {
        $failedCharges = [];
        $processedIds = $subscription->processed_failed_charge_ids ?? [];

        foreach ($currentCharges as $charge) {
            $chargeId = $charge['id'] ?? null;
            $status = strtolower($charge['status'] ?? '');

            // Verificar si el charge está rechazado y no ha sido procesado
            if ($chargeId && $status === 'rechazado' && !in_array($chargeId, $processedIds)) {
                $failedCharges[] = $charge;
            }
        }

        return $failedCharges;
    }

    /**
     * Procesar un charge rechazado
     */
    protected function processFailedCharge(ProgramSubscription $subscription, array $charge): void
    {
        $chargeId = $charge['id'];

        Log::info('SyncSubscriptionPayments: Procesando charge rechazado', [
            'subscription_id' => $subscription->id,
            'charge_id' => $chargeId,
            'amount' => $charge['amount'] ?? 0,
            'charge_date' => $charge['charge_date'] ?? null
        ]);

        // 1. Generar link de actualización de tarjeta si no existe o está vencido
        $cardUpdateLink = $this->generateCardUpdateLink($subscription);

        // 2. Obtener datos del participante y apoderados
        $participant = $subscription->participant;
        $emergencyContacts = $participant->emergencyContacts ?? collect();

        if ($emergencyContacts->isEmpty()) {
            Log::warning('SyncSubscriptionPayments: No hay contactos de emergencia para notificar', [
                'subscription_id' => $subscription->id,
                'participant_id' => $participant->id
            ]);
            return;
        }

        // 3. Preparar datos para el email
        $installmentNumber = $this->extractInstallmentNumber($charge['description'] ?? '');
        $amount = number_format($charge['amount'] ?? 0, 0, ',', '.');
        $chargeDate = $charge['charge_date'] ?? 'N/A';

        // 4. Enviar email a todos los contactos de emergencia
        foreach ($emergencyContacts as $contact) {
            if (empty($contact->email)) {
                continue;
            }

            try {
                \Illuminate\Support\Facades\Mail::to($contact->email)->send(
                    new \App\Mail\FailedPaymentMail(
                        $participant->full_name,
                        $installmentNumber,
                        $amount,
                        $chargeDate,
                        $cardUpdateLink,
                        $subscription->program->name ?? 'Programa'
                    )
                );

                Log::info('SyncSubscriptionPayments: Email de cobro rechazado enviado', [
                    'subscription_id' => $subscription->id,
                    'charge_id' => $chargeId,
                    'contact_email' => $contact->email
                ]);
            } catch (Exception $e) {
                Log::error('SyncSubscriptionPayments: Error enviando email de cobro rechazado', [
                    'subscription_id' => $subscription->id,
                    'charge_id' => $chargeId,
                    'contact_email' => $contact->email,
                    'error' => $e->getMessage()
                ]);
            }
        }

        // 5. Marcar el charge como procesado
        $processedIds = $subscription->processed_failed_charge_ids ?? [];
        $processedIds[] = $chargeId;

        $subscription->update([
            'processed_failed_charge_ids' => $processedIds
        ]);

        Log::info('SyncSubscriptionPayments: Charge rechazado procesado exitosamente', [
            'subscription_id' => $subscription->id,
            'charge_id' => $chargeId
        ]);
    }

    /**
     * Generar link de actualización de tarjeta
     */
    protected function generateCardUpdateLink(ProgramSubscription $subscription): string
    {
        // Verificar si ya existe un link válido (generado en las últimas 24 horas)
        if ($subscription->card_change_link && $subscription->card_change_link_generated_at) {
            $linkAge = Carbon::now()->diffInHours($subscription->card_change_link_generated_at);
            if ($linkAge < 24) {
                return $subscription->card_change_link;
            }
        }

        // Generar nuevo link vía API de VirtualPOS
        try {
            $response = $this->virtualPosService->generateCardChangeLink($subscription->virtualpos_subscription_id);

            if ($response && isset($response['change_card_url'])) {
                $subscription->update([
                    'card_change_link' => $response['change_card_url'],
                    'card_change_link_generated_at' => Carbon::now()
                ]);

                return $response['change_card_url'];
            }
        } catch (Exception $e) {
            Log::error('SyncSubscriptionPayments: Error generando link de cambio de tarjeta', [
                'subscription_id' => $subscription->id,
                'error' => $e->getMessage()
            ]);
        }

        // Fallback: retornar URL genérica al portal
        return route('guardian.dashboard');
    }
}
