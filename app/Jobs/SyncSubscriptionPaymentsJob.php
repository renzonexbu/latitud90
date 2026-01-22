<?php

namespace App\Jobs;

use App\Models\ProgramSubscription;
use App\Models\Installment;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\Payment;
use App\Models\PaymentGateway;
use App\Models\ChargeAttempt;
use App\Services\Subscription\VirtualPosSubscriptionService;
use App\Services\Mail\SuccessPaymentEmailService;
use App\Services\Client\Integration\BsaleService;
use App\Services\VirtualPos\CreateChargeService;
use App\Services\Client\PaymentGateway\VirtualPosService;
use App\Helpers\PaymentDocumentTypeHelper;
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
    protected $createChargeService;

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
        BsaleService $bsaleService,
        CreateChargeService $createChargeService
    ): void
    {
        $this->virtualPosService = $virtualPosService;
        $this->emailService = $emailService;
        $this->bsaleService = $bsaleService;
        $this->createChargeService = $createChargeService;

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
        // NOTA: VirtualPOS devuelve los datos bajo la clave 'suscription'
        $subscriptionData = $virtualPosData['suscription'] ?? $virtualPosData;
        $currentCharges = $subscriptionData['charge_program'] ?? [];
        $savedCharges = $subscription->charge_program ?? [];

        Log::info('SyncSubscriptionPayments: Charges recibidos de VirtualPOS', [
            'subscription_id' => $subscription->id,
            'total_charges' => count($currentCharges),
            'charges' => $currentCharges
        ]);

        // Detectar nuevos pagos
        $newPayments = $this->detectNewPayments($currentCharges, $savedCharges);

        Log::info('SyncSubscriptionPayments: Nuevos pagos detectados', [
            'subscription_id' => $subscription->id,
            'count' => count($newPayments),
            'payments' => $newPayments
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

        // Actualizar pagos existentes que no tengan authorization_code
        $updatedAuthCodes = $this->updateExistingPaymentsAuthCodes($subscription, $currentCharges);

        // Actualizar charge_program guardado
        $subscription->update([
            'charge_program' => $currentCharges
        ]);

        return count($newPayments);
    }

    /**
     * Actualizar authorization_code de pagos existentes que no lo tengan
     *
     * @return int Número de pagos actualizados
     */
    protected function updateExistingPaymentsAuthCodes(ProgramSubscription $subscription, array $currentCharges): int
    {
        $updatedCount = 0;

        // Buscar pagos de esta suscripción que no tengan authorization_code
        $paymentsWithoutAuthCode = Payment::whereNull('authorization_code')
            ->where('external_payment_id', 'like', 'cid_%')
            ->whereHas('order', function ($query) use ($subscription) {
                $query->where('participant_id', $subscription->participant_id)
                    ->where('program_id', $subscription->program_id);
            })
            ->get();

        if ($paymentsWithoutAuthCode->isEmpty()) {
            return 0;
        }

        Log::info('SyncSubscriptionPayments: Pagos sin auth_code encontrados', [
            'subscription_id' => $subscription->id,
            'count' => $paymentsWithoutAuthCode->count()
        ]);

        foreach ($paymentsWithoutAuthCode as $payment) {
            $chargeId = $payment->external_payment_id;

            try {
                // Obtener detalle del cargo desde VirtualPOS
                $chargeDetail = $this->virtualPosService->getCharge($chargeId);

                if (isset($chargeDetail['charge']['payment']['order']['auth_code'])) {
                    $authCode = $chargeDetail['charge']['payment']['order']['auth_code'];

                    $payment->update([
                        'authorization_code' => $authCode
                    ]);

                    Log::info('SyncSubscriptionPayments: auth_code actualizado en pago existente', [
                        'payment_id' => $payment->id,
                        'charge_id' => $chargeId,
                        'auth_code' => $authCode
                    ]);

                    $updatedCount++;
                }
            } catch (Exception $e) {
                Log::warning('SyncSubscriptionPayments: No se pudo obtener auth_code para pago existente', [
                    'payment_id' => $payment->id,
                    'charge_id' => $chargeId,
                    'error' => $e->getMessage()
                ]);
            }
        }

        return $updatedCount;
    }

    /**
     * Actualizar estado de la suscripción
     */
    protected function updateSubscriptionStatus(ProgramSubscription $subscription, array $virtualPosData): void
    {
        // VirtualPOS devuelve los datos bajo la clave 'suscription'
        $subscriptionData = $virtualPosData['suscription'] ?? $virtualPosData;
        $virtualPosStatus = $subscriptionData['status'] ?? $subscription->status;
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

        // También actualizar el charge_program guardado
        if (isset($subscriptionData['charge_program'])) {
            $subscription->update(['charge_program' => $subscriptionData['charge_program']]);
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
     * @return array Charges que están pagados pero no tienen Payment en BD
     */
    protected function detectNewPayments(array $currentCharges, array $savedCharges): array
    {
        $newPayments = [];

        // Estados válidos que indican un pago exitoso/procesado
        // Incluye 'procesando' porque VirtualPOS lo usa para cobros inmediatos exitosos
        $approvedStatuses = array_map('strtolower', VirtualPosService::APPROVED_STATUSES);
        $approvedStatuses[] = 'procesando'; // Estado de cobro inmediato exitoso

        // Buscar charges que están pagados en VirtualPOS
        foreach ($currentCharges as $currentCharge) {
            $chargeId = $currentCharge['id'] ?? null;
            $currentStatus = strtolower($currentCharge['status'] ?? '');

            if (!$chargeId || !in_array($currentStatus, $approvedStatuses)) {
                continue;
            }

            // LÓGICA CORREGIDA: Si el charge está pagado/procesando en VirtualPOS pero NO existe
            // en nuestra BD, entonces es un pago nuevo que debemos procesar.
            // No importa si ya estaba pagado en el charge_program guardado anteriormente,
            // lo importante es si tenemos el Payment registrado en la BD.
            // NOTA: 'procesando' se incluye porque VirtualPOS lo usa para cobros inmediatos exitosos.
            if (!$this->isChargeAlreadyProcessed($chargeId)) {
                Log::info('SyncSubscriptionPayments: Pago nuevo detectado', [
                    'charge_id' => $chargeId,
                    'amount' => $currentCharge['amount'] ?? 0,
                    'charge_date' => $currentCharge['charge_date'] ?? null,
                    'status' => $currentStatus,
                    'is_immediate_charge' => $currentStatus === 'procesando'
                ]);
                $newPayments[] = $currentCharge;
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

        // Obtener detalle del cargo desde VirtualPOS para obtener el auth_code
        try {
            $chargeDetail = $this->virtualPosService->getCharge($charge['id']);
            // Fusionar los datos del detalle con el charge original
            if (isset($chargeDetail['charge'])) {
                $charge = array_merge($charge, $chargeDetail['charge']);
                Log::info('SyncSubscriptionPayments: Detalle del cargo obtenido', [
                    'charge_id' => $charge['id'],
                    'auth_code' => $charge['payment']['order']['auth_code'] ?? 'N/A'
                ]);
            }
        } catch (Exception $e) {
            Log::warning('SyncSubscriptionPayments: No se pudo obtener detalle del cargo, continuando sin auth_code', [
                'charge_id' => $charge['id'],
                'error' => $e->getMessage()
            ]);
        }

        DB::beginTransaction();

        try {
            // 1. Obtener o crear Order
            $order = $this->getOrCreateOrder($subscription);

            // 2. Determinar número de cuota desde la descripción
            $installmentNumber = $this->extractInstallmentNumber($charge['description'] ?? '');

            // 3. Crear OrderDetail para este pago
            $orderDetail = $this->createOrderDetail($order, $charge, $installmentNumber, $subscription);

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
            ->whereIn('status', ['pending', 'paid', 'partial', 'processing'])
            ->first();

        if (!$order) {
            // Obtener el participant_program_id si existe
            $participantProgram = DB::table('participant_program')
                ->where('participant_id', $subscription->participant_id)
                ->where('program_id', $subscription->program_id)
                ->first();

            // Obtener course_id desde el program_course
            $programCourse = $subscription->programCourse;
            $courseId = $programCourse ? $programCourse->course_id : null;

            // Crear nueva orden con todos los campos requeridos
            $order = Order::create([
                'participant_id' => $subscription->participant_id,
                'program_id' => $subscription->program_id,
                'course_id' => $courseId,
                'participant_program_id' => $participantProgram ? $participantProgram->id : null,
                'order_number' => 'SUB-' . str_pad($subscription->id, 8, '0', STR_PAD_LEFT),
                'session_id' => $subscription->virtualpos_subscription_id, // Usar subscription ID de VirtualPos
                'total_amount' => 0, // Se actualizará con cada pago
                'discount' => 0,
                'final_amount' => $subscription->total_amount, // Monto total de la suscripción
                'total_installments' => $subscription->installments,
                'payment_type' => 'monthly', // Usar 'monthly' igual que en SubscriptionController
                'status' => 'pending',
                'notes' => 'Orden de suscripción VirtualPOS - ' . $subscription->installments . ' cuotas',
                'created_at' => now(),
            ]);

            Log::info('SyncSubscriptionPayments: Orden creada', [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'subscription_id' => $subscription->id,
                'total_installments' => $subscription->installments
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
     * Crear o actualizar OrderDetail para este pago
     */
    protected function createOrderDetail(Order $order, array $charge, int $installmentNumber, ProgramSubscription $subscription): OrderDetail
    {
        $amount = $charge['amount'] ?? 0;

        // Obtener gateway de VirtualPos
        $gateway = \App\Models\PaymentGateway::where('code', 'virtualpos')->first();

        // Obtener payment_option_id para suscripción
        $paymentOption = \App\Models\PaymentOption::where('code', 'subscription_virtualpos')->first();

        // VERIFICAR SI YA EXISTE UN ORDERDETAIL PARA ESTA CUOTA
        $existingOrderDetail = OrderDetail::where('order_id', $order->id)
            ->where('installment_number', $installmentNumber)
            ->first();

        if ($existingOrderDetail) {
            // Actualizar OrderDetail existente a pagado
            Log::info('SyncSubscriptionPayments: OrderDetail existente encontrado, actualizando a paid', [
                'order_detail_id' => $existingOrderDetail->id,
                'installment_number' => $installmentNumber
            ]);

            $existingOrderDetail->update([
                'status' => 'paid',
                'is_paid' => true,
                'paid_at' => now(),
                'amount' => $amount,
                'base_amount' => $amount,
                'payment_gateway_id' => $gateway ? $gateway->id : null,
                'payment_option_id' => $paymentOption ? $paymentOption->id : null,
                'due_date' => $charge['charge_date'] ? \Carbon\Carbon::parse($charge['charge_date']) : now(),
            ]);

            // Actualizar total de la orden
            $order->increment('total_amount', $amount);

            Log::info('=== UPDATED EXISTING ORDER DETAIL FOR SUBSCRIPTION CHARGE ===', [
                'order_detail_id' => $existingOrderDetail->id,
                'installment_number' => $installmentNumber,
            ]);

            return $existingOrderDetail;
        }

        // Si no existe, crear nuevo OrderDetail
        // IMPORTANTE: Usar datos del comprador guardados en la suscripción
        $buyerData = [];
        $firstOrderDetail = null;

        // 1. Primero intentar obtener de buyer_data guardado en la suscripción
        if (!empty($subscription->buyer_data)) {
            $savedBuyerData = $subscription->buyer_data;

            // Construir nombre completo del comprador
            $buyerFullName = trim(
                ($savedBuyerData['first_name'] ?? '') . ' ' .
                ($savedBuyerData['second_name'] ?? '') . ' ' .
                ($savedBuyerData['first_last_name'] ?? '') . ' ' .
                ($savedBuyerData['second_last_name'] ?? '')
            );
            $buyerFullName = preg_replace('/\s+/', ' ', $buyerFullName);

            // Normalizar IDs usando métodos auxiliares
            $buyerData = [
                'name' => $buyerFullName,
                'email' => $savedBuyerData['email'],
                'country' => $this->resolveCountryId($savedBuyerData['country_id'] ?? $savedBuyerData['country'] ?? null),
                'region' => $this->resolveRegionId($savedBuyerData['region_id'] ?? $savedBuyerData['region'] ?? null),
                'city' => $this->resolveCityId($savedBuyerData['city_id'] ?? $savedBuyerData['city'] ?? null),
                'code_phone' => $savedBuyerData['code_phone'] ?? null,
                'phone' => $savedBuyerData['phone'] ?? null,
                'document_type' => $this->resolveDocumentTypeId($savedBuyerData['document_type'] ?? null),
                'document_number' => $savedBuyerData['original_document_number'] ?? $savedBuyerData['document_number'],
                'billing_address' => null,
                'billing_country' => $savedBuyerData['country'] ?? 'Chile',
                'billing_city' => $savedBuyerData['city'] ?? null,
                'billing_postal_code' => null,
                'terms_accepted' => true,
                'marketing_accepted' => false,
                'terms_accepted_confirmation' => true,
                'terms_accepted_at' => now(),
            ];

            Log::info('SyncSubscriptionPayments: Usando buyer_data de la suscripción', [
                'subscription_id' => $subscription->id,
                'buyer_name' => $buyerFullName
            ]);

        } else {
            // 2. Fallback: buscar primer OrderDetail si existe (para suscripciones antiguas)
            $firstOrderDetail = OrderDetail::where('order_id', $order->id)
                ->orderBy('id', 'asc')
                ->first();

            if ($firstOrderDetail) {
                $buyerData = [
                    'name' => $firstOrderDetail->name,
                    'email' => $firstOrderDetail->email,
                    'country' => $firstOrderDetail->country,
                    'region' => $firstOrderDetail->region,
                    'city' => $firstOrderDetail->city,
                    'code_phone' => $firstOrderDetail->code_phone,
                    'phone' => $firstOrderDetail->phone,
                    'document_type' => $firstOrderDetail->document_type,
                    'document_number' => $firstOrderDetail->document_number,
                    'billing_address' => $firstOrderDetail->billing_address,
                    'billing_country' => $firstOrderDetail->billing_country,
                    'billing_city' => $firstOrderDetail->billing_city,
                    'billing_postal_code' => $firstOrderDetail->billing_postal_code,
                    'terms_accepted' => $firstOrderDetail->terms_accepted,
                    'marketing_accepted' => $firstOrderDetail->marketing_accepted,
                    'terms_accepted_confirmation' => $firstOrderDetail->terms_accepted_confirmation,
                    'terms_accepted_at' => $firstOrderDetail->terms_accepted_at ?? now(),
                ];

                Log::info('SyncSubscriptionPayments: Usando datos del primer OrderDetail (suscripción antigua)', [
                    'subscription_id' => $subscription->id,
                    'order_detail_id' => $firstOrderDetail->id
                ]);

            } else {
                // 3. Último fallback: datos del participante
                Log::warning('SyncSubscriptionPayments: No hay buyer_data ni OrderDetail, usando datos del participante', [
                    'subscription_id' => $subscription->id,
                    'order_id' => $order->id
                ]);

                $participant = $order->participant;
                if ($participant) {
                    $buyerData = [
                        'name' => $participant->full_name,
                        'email' => $participant->email,
                        'country' => $participant->country ?? null,
                        'region' => null,
                        'city' => null,
                        'code_phone' => $participant->code_phone ?? null,
                        'phone' => $participant->phone ?? null,
                        'document_type' => \App\Models\Document::where('name', 'RUT')->value('id'),
                        'document_number' => $participant->document_number,
                        'billing_address' => null,
                        'billing_country' => $participant->country ?? 'Chile',
                        'billing_city' => null,
                        'billing_postal_code' => null,
                        'terms_accepted' => true,
                        'marketing_accepted' => false,
                        'terms_accepted_confirmation' => true,
                        'terms_accepted_at' => now(),
                    ];
                }
            }
        }

        $orderDetail = OrderDetail::create(array_merge([
            'order_id' => $order->id,
            'payment_option_id' => $paymentOption ? $paymentOption->id : null,
            'payment_gateway_id' => $gateway ? $gateway->id : null,
            'base_amount' => $amount,
            'discount_amount' => 0,
            'amount' => $amount,
            'installment_number' => $installmentNumber,
            'status' => 'paid',
            'is_paid' => true,
            'paid_at' => now(),
            'due_date' => $charge['charge_date'] ? \Carbon\Carbon::parse($charge['charge_date']) : now(),
        ], $buyerData));

        Log::info('=== CREATING ORDER DETAIL FOR SUBSCRIPTION CHARGE ===', [
            'order_detail_data' => $orderDetail->toArray(),
            'copied_from_first_detail' => $firstOrderDetail ? true : false,
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

        // Obtener payment_option_id para suscripción
        $paymentOption = \App\Models\PaymentOption::where('code', 'subscription_virtualpos')->first();

        $paymentData = $charge['payment'] ?? [];
        $orderData = $paymentData['order'] ?? [];

        // Obtener datos de pago desde el charge y la suscripción
        $paymentMethod = $subscription->payment_method ?? [];
        $cardBrand = $paymentData['card_type'] ?? ($paymentMethod['brand'] ?? null);
        $cardLast4 = $orderData['card_number'] ?? ($paymentData['card_number'] ?? ($paymentMethod['last4'] ?? null));

        // Extraer auth_code desde la ubicación correcta (payment.order.auth_code)
        $authCode = $orderData['auth_code'] ?? ($paymentData['auth_code'] ?? ($paymentData['authorization_code'] ?? null));

        $paymentRecord = [
            'order_id' => $orderDetail->order_id,
            'order_detail_id' => $orderDetail->id,
            'payment_gateway_id' => $gateway ? $gateway->id : null,
            'payment_option_id' => $paymentOption ? $paymentOption->id : null,
            'external_payment_id' => $charge['id'],
            'buy_order' => $subscription->virtualpos_subscription_id ?? null, // Usar subscription ID como buy_order
            'session_id' => null, // No aplica para suscripciones recurrentes
            'token' => $paymentData['token'] ?? $charge['id'], // Usar charge ID como token
            'status' => 'completed',
            'amount' => $charge['amount'] ?? 0,
            'currency' => 'CLP',
            'installments_number' => $orderData['installment_number'] ?? ($paymentData['installments_number'] ?? 1),
            'installment_amount' => $orderData['installment_amount'] ?? ($paymentData['installment_amount'] ?? ($charge['amount'] ?? 0)),
            'transaction_date' => $this->parseTransactionDate($charge),
            'accounting_date' => $this->parseTransactionDate($charge),
            'authorization_code' => $authCode,
            'response_code' => $paymentData['response_code'] ?? '0', // 0 = aprobado
            'vci' => $paymentData['vci'] ?? null,
            'card_type' => $cardBrand,
            'card_number' => $cardLast4,
            'commerce_code' => config('services.virtualpos.commerce_code') ?? null,
            'gateway_response' => $charge, // Array, se casteará automáticamente
            'raw_notification' => $charge, // Array, se casteará automáticamente
            'email_sent' => false,
            'balance' => 0, // Sin balance pendiente ya que es pago completo
            'document_type' => \App\Helpers\PaymentDocumentTypeHelper::determineDocumentType($orderDetail->order->program_id),
        ];

        Log::info('=== CREATING PAYMENT FOR SUBSCRIPTION CHARGE ===', [
            'payment_data' => $paymentRecord,
            'charge_id' => $charge['id'],
            'subscription_id' => $subscription->id,
        ]);

        $payment = Payment::create($paymentRecord);

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
     * El servicio SuccessPaymentEmailService determina automáticamente si enviar contrato
     * basándose en: si es primera cuota Y si el programa NO es del mismo año
     */
    protected function sendSuccessEmail(
        ProgramSubscription $subscription,
        OrderDetail $orderDetail,
        Payment $payment,
        int $installmentNumber
    ): void
    {
        try {
            // Enviar email usando el servicio existente
            // El servicio determina automáticamente si incluir contrato/boleta
            $this->emailService->sendSuccessPaymentEmail(
                $orderDetail,
                $payment
            );

            // Marcar email como enviado
            $payment->update(['email_sent' => true]);

            Log::info('SyncSubscriptionPayments: Email de pago exitoso enviado', [
                'payment_id' => $payment->id,
                'installment_number' => $installmentNumber
            ]);

        } catch (Exception $e) {
            Log::error('SyncSubscriptionPayments: Error enviando email', [
                'payment_id' => $payment->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
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
        $amount = $charge['amount'] ?? 0;

        Log::info('SyncSubscriptionPayments: Procesando charge rechazado', [
            'subscription_id' => $subscription->id,
            'charge_id' => $chargeId,
            'amount' => $amount,
            'charge_date' => $charge['charge_date'] ?? null
        ]);

        // Obtener configuración de reintentos
        $retryEnabled = config('lat90.subscriptions.retry.enabled', true);
        $maxAttempts = config('lat90.subscriptions.retry.max_attempts', 3);
        $delayHours = config('lat90.subscriptions.retry.delay_hours', 24);
        $notifyAfterAllRetries = config('lat90.subscriptions.retry.notify_after_all_retries', true);

        // Registrar el intento fallido original en la tabla charge_attempts
        $this->registerChargeAttempt($subscription, $charge, 'failed');

        // Verificar si los reintentos están habilitados
        if (!$retryEnabled) {
            Log::info('SyncSubscriptionPayments: Reintentos automáticos deshabilitados', [
                'subscription_id' => $subscription->id,
                'charge_id' => $chargeId
            ]);
            $this->notifyFailedPayment($subscription, $charge);
            $this->markChargeAsProcessed($subscription, $chargeId);
            return;
        }

        // Contar intentos previos para este cargo original
        $attemptCount = ChargeAttempt::getAttemptCountForOriginalCharge(
            $subscription->id,
            $chargeId
        );

        Log::info('SyncSubscriptionPayments: Verificando reintentos', [
            'subscription_id' => $subscription->id,
            'charge_id' => $chargeId,
            'attempt_count' => $attemptCount,
            'max_attempts' => $maxAttempts
        ]);

        // Verificar si ya alcanzamos el máximo de intentos
        if ($attemptCount >= $maxAttempts) {
            Log::info('SyncSubscriptionPayments: Máximo de reintentos alcanzado', [
                'subscription_id' => $subscription->id,
                'charge_id' => $chargeId,
                'attempts' => $attemptCount,
                'max_attempts' => $maxAttempts
            ]);

            if ($notifyAfterAllRetries) {
                $this->notifyFailedPayment($subscription, $charge);
            }

            $this->markChargeAsProcessed($subscription, $chargeId);
            return;
        }

        // Verificar si ha pasado suficiente tiempo desde el último intento
        $lastAttempt = ChargeAttempt::where('program_subscription_id', $subscription->id)
            ->where('original_charge_id', $chargeId)
            ->orderBy('attempted_at', 'desc')
            ->first();

        if ($lastAttempt && $lastAttempt->attempted_at) {
            $hoursSinceLastAttempt = Carbon::now()->diffInHours($lastAttempt->attempted_at);

            if ($hoursSinceLastAttempt < $delayHours) {
                Log::info('SyncSubscriptionPayments: Esperando delay entre reintentos', [
                    'subscription_id' => $subscription->id,
                    'charge_id' => $chargeId,
                    'hours_since_last' => $hoursSinceLastAttempt,
                    'delay_hours' => $delayHours,
                    'next_retry_in_hours' => $delayHours - $hoursSinceLastAttempt
                ]);
                return; // No marcar como procesado, se reintentará en la próxima ejecución
            }
        }

        // Intentar crear un nuevo cargo (reintento automático)
        $this->attemptAutomaticRetry($subscription, $charge, $attemptCount + 1, $maxAttempts);
    }

    /**
     * Registrar un intento de cargo en la base de datos
     */
    protected function registerChargeAttempt(
        ProgramSubscription $subscription,
        array $charge,
        string $status,
        string $type = 'automatic',
        ?string $newChargeId = null,
        ?string $failureReason = null,
        ?array $apiResponse = null
    ): ChargeAttempt
    {
        // Buscar la cuota asociada si existe
        $installmentNumber = $this->extractInstallmentNumber($charge['description'] ?? '');
        $installment = null;

        if ($installmentNumber > 0) {
            $installment = Installment::whereHas('installmentPlan', function ($query) use ($subscription) {
                $query->where('participant_id', $subscription->participant_id)
                    ->where('program_id', $subscription->program_id);
            })->where('installment_number', $installmentNumber)->first();
        }

        // Contar intentos previos para determinar el número de intento
        $attemptNumber = ChargeAttempt::where('program_subscription_id', $subscription->id)
            ->where('original_charge_id', $charge['id'])
            ->count() + 1;

        $chargeAttempt = ChargeAttempt::create([
            'program_subscription_id' => $subscription->id,
            'installment_id' => $installment?->id,
            'virtualpos_charge_id' => $newChargeId,
            'original_charge_id' => $charge['id'],
            'attempt_number' => $attemptNumber,
            'amount' => $charge['amount'] ?? 0,
            'description' => $charge['description'] ?? null,
            'type' => $type,
            'status' => $status,
            'failure_reason' => $failureReason ?? ($status === 'failed' ? 'Cargo rechazado por VirtualPos' : null),
            'virtualpos_status' => $charge['status'] ?? null,
            'api_response' => $apiResponse ?? $charge,
            'attempted_at' => now(),
            'resolved_at' => in_array($status, ['success', 'failed']) ? now() : null,
        ]);

        Log::info('SyncSubscriptionPayments: Intento de cargo registrado', [
            'charge_attempt_id' => $chargeAttempt->id,
            'subscription_id' => $subscription->id,
            'original_charge_id' => $charge['id'],
            'new_charge_id' => $newChargeId,
            'attempt_number' => $attemptNumber,
            'status' => $status,
            'type' => $type
        ]);

        return $chargeAttempt;
    }

    /**
     * Intentar un reintento automático de cargo
     */
    protected function attemptAutomaticRetry(
        ProgramSubscription $subscription,
        array $originalCharge,
        int $attemptNumber,
        int $maxAttempts
    ): void
    {
        $chargeId = $originalCharge['id'];
        $amount = $originalCharge['amount'] ?? 0;
        $description = $originalCharge['description'] ?? '';

        Log::info('SyncSubscriptionPayments: Intentando reintento automático', [
            'subscription_id' => $subscription->id,
            'original_charge_id' => $chargeId,
            'attempt_number' => $attemptNumber,
            'max_attempts' => $maxAttempts,
            'amount' => $amount
        ]);

        // Crear nuevo cargo vía VirtualPOS
        $retryDescription = "Reintento {$attemptNumber}/{$maxAttempts} - {$description}";

        $result = $this->createChargeService->createNewChargeForSubscription(
            $subscription,
            (int) $amount,
            $retryDescription
        );

        if ($result['success']) {
            $newChargeId = $result['charge_id'] ?? null;

            Log::info('SyncSubscriptionPayments: Reintento automático creado exitosamente', [
                'subscription_id' => $subscription->id,
                'original_charge_id' => $chargeId,
                'new_charge_id' => $newChargeId,
                'attempt_number' => $attemptNumber
            ]);

            // Registrar el intento como pendiente (se procesará cuando VirtualPOS lo procese)
            $this->registerChargeAttempt(
                $subscription,
                $originalCharge,
                'pending',
                'automatic',
                $newChargeId,
                null,
                $result['data'] ?? null
            );

            // Marcar el cargo original como procesado
            $this->markChargeAsProcessed($subscription, $chargeId);

        } else {
            Log::error('SyncSubscriptionPayments: Error en reintento automático', [
                'subscription_id' => $subscription->id,
                'original_charge_id' => $chargeId,
                'attempt_number' => $attemptNumber,
                'error' => $result['message'] ?? 'Error desconocido'
            ]);

            // Registrar el intento fallido
            $this->registerChargeAttempt(
                $subscription,
                $originalCharge,
                'failed',
                'automatic',
                null,
                $result['message'] ?? 'Error al crear cargo de reintento',
                $result
            );

            // Si este fue el último intento, notificar al usuario
            if ($attemptNumber >= $maxAttempts) {
                $notifyAfterAllRetries = config('lat90.subscriptions.retry.notify_after_all_retries', true);
                if ($notifyAfterAllRetries) {
                    $this->notifyFailedPayment($subscription, $originalCharge);
                }
                $this->markChargeAsProcessed($subscription, $chargeId);
            }
        }
    }

    /**
     * Notificar pago fallido a los contactos de emergencia
     */
    protected function notifyFailedPayment(ProgramSubscription $subscription, array $charge): void
    {
        $chargeId = $charge['id'];

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

        Log::info('SyncSubscriptionPayments: Notificación de pago fallido completada', [
            'subscription_id' => $subscription->id,
            'charge_id' => $chargeId
        ]);
    }

    /**
     * Marcar un charge como procesado para no volver a notificar
     */
    protected function markChargeAsProcessed(ProgramSubscription $subscription, string $chargeId): void
    {
        $processedIds = $subscription->processed_failed_charge_ids ?? [];
        $processedIds[] = $chargeId;

        $subscription->update([
            'processed_failed_charge_ids' => $processedIds
        ]);

        Log::info('SyncSubscriptionPayments: Charge marcado como procesado', [
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

    /**
     * Métodos auxiliares para resolver IDs de ubicaciones y documentos
     */
    protected function resolveCountryId($value): ?int
    {
        if (empty($value)) { return null; }
        if (is_numeric($value)) { return (int) $value; }
        $string = trim((string) $value);
        // Probar por código (CL, etc.)
        if (strlen($string) <= 3) {
            $id = \App\Models\Country::where('code', $string)->value('id');
            if ($id) { return (int) $id; }
        }
        // Fallback por nombre exacto
        $id = \App\Models\Country::where('name', $string)->value('id');
        if ($id) { return (int) $id; }
        // Fallback por like
        $id = \App\Models\Country::where('name', 'like', $string)->value('id');
        return $id ? (int) $id : null;
    }

    protected function resolveRegionId($value): ?int
    {
        if (empty($value)) { return null; }
        if (is_numeric($value)) { return (int) $value; }
        $string = trim((string) $value);
        $id = \App\Models\Region::where('name', $string)->value('id');
        if ($id) { return (int) $id; }
        $id = \App\Models\Region::where('name', 'like', $string)->value('id');
        return $id ? (int) $id : null;
    }

    protected function resolveCityId($value): ?int
    {
        if (empty($value)) { return null; }
        if (is_numeric($value)) { return (int) $value; }
        $string = trim((string) $value);
        $id = \App\Models\Comune::where('name', $string)->value('id');
        if ($id) { return (int) $id; }
        $id = \App\Models\Comune::where('name', 'like', $string)->value('id');
        return $id ? (int) $id : null;
    }

    protected function resolveDocumentTypeId($value): ?int
    {
        if (empty($value)) { return null; }
        if (is_numeric($value)) { return (int) $value; }
        $string = trim((string) $value);
        $id = \App\Models\Document::where('name', $string)->value('id');
        if ($id) { return (int) $id; }
        $id = \App\Models\Document::where('name', 'like', $string)->value('id');
        return $id ? (int) $id : null;
    }
}
