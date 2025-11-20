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
}
