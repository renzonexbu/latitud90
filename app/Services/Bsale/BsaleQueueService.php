<?php

namespace App\Services\Bsale;

use App\Jobs\SendBsaleEmailJob;
use App\Models\BsaleRequest;
use App\Models\Payment;
use App\Models\OrderDetail;
use App\Services\Client\Integration\BsaleService;
use App\Helpers\PaymentDocumentTypeHelper;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Exception;

class BsaleQueueService
{
    protected BsaleService $bsaleService;

    public function __construct(BsaleService $bsaleService)
    {
        $this->bsaleService = $bsaleService;
    }

    /**
     * Encolar una solicitud de boleta para un payment
     * Valida duplicados antes de encolar
     *
     * @return BsaleRequest|null Retorna null si ya existe o no debe generar boleta
     */
    public function queueBoleta(
        Payment $payment,
        string $source = 'payment_confirmation',
        ?array $metadata = null
    ): ?BsaleRequest {
        $documentType = BsaleRequest::DOC_TYPE_BOLETA;

        Log::channel('bsale')->info('BsaleQueueService: Intentando encolar boleta', [
            'payment_id' => $payment->id,
            'source' => $source,
        ]);

        // VALIDACIÓN 1: Verificar si BSale está habilitado globalmente
        if (!config('services.bsale.enabled', true)) {
            Log::channel('bsale')->info('BsaleQueueService: BSale deshabilitado globalmente', [
                'payment_id' => $payment->id,
            ]);
            return null;
        }

        // VALIDACIÓN 2: Verificar si el payment ya tiene boleta generada
        if (!empty($payment->bsale_document_id)) {
            Log::channel('bsale')->info('BsaleQueueService: Payment ya tiene boleta BSale', [
                'payment_id' => $payment->id,
                'bsale_document_id' => $payment->bsale_document_id,
                'bsale_number' => $payment->bsale_number,
            ]);
            return null;
        }

        // VALIDACIÓN 3: Verificar si ya existe solicitud activa (pending, processing, completed)
        if (BsaleRequest::existsForPayment($payment->id, $documentType)) {
            $existingRequest = BsaleRequest::getActiveForPayment($payment->id, $documentType);
            Log::channel('bsale')->info('BsaleQueueService: Ya existe solicitud activa para este payment', [
                'payment_id' => $payment->id,
                'existing_request_id' => $existingRequest?->id,
                'existing_status' => $existingRequest?->status,
            ]);
            return $existingRequest;
        }

        // VALIDACIÓN 4: Verificar que el payment esté confirmado
        $confirmedStatuses = ['completed', 'approved'];
        if (!in_array($payment->status, $confirmedStatuses)) {
            Log::channel('bsale')->warning('BsaleQueueService: Payment no está confirmado', [
                'payment_id' => $payment->id,
                'payment_status' => $payment->status,
            ]);
            return null;
        }

        // VALIDACIÓN 5: Verificar tipo de documento según PaymentDocumentTypeHelper.
        // Excepción: si el pago tiene document_type = 'B2' explícito (ej: conversión AC→Boleta),
        // se genera la boleta sin importar lo que calcule el helper por fecha de programa.
        $orderDetail = $payment->orderDetail;
        if ($orderDetail && $payment->document_type !== 'B2') {
            $documentTypes = PaymentDocumentTypeHelper::determineDocumentTypes($payment, $orderDetail);
            if (!in_array(PaymentDocumentTypeHelper::TYPE_BOLETA, $documentTypes)) {
                Log::channel('bsale')->info('BsaleQueueService: Este payment no requiere boleta BSale', [
                    'payment_id' => $payment->id,
                    'required_types' => $documentTypes,
                ]);
                return null;
            }
        }

        // VALIDACIÓN 5.5: Créditos Temporales (CT) NUNCA generan boleta
        if ($payment->paymentOption && $payment->paymentOption->code === 'presential_credit_temp') {
            Log::channel('bsale')->info('BsaleQueueService: Crédito Temporal (CT) no genera boleta BSale', [
                'payment_id' => $payment->id,
                'payment_option' => $payment->paymentOption->code,
            ]);
            return null;
        }

        // VALIDACIÓN 6: Verificar flags específicos (suscripción vs total)
        $order = $orderDetail?->order;
        if ($order) {
            $isSubscription = $this->isSubscriptionPayment($payment, $order);

            if ($isSubscription && !config('services.bsale.subscription_enabled', true)) {
                Log::channel('bsale')->info('BsaleQueueService: BSale deshabilitado para suscripciones', [
                    'payment_id' => $payment->id,
                ]);
                return null;
            }

            if (!$isSubscription && !config('services.bsale.total_enabled', true)) {
                Log::channel('bsale')->info('BsaleQueueService: BSale deshabilitado para pagos totales', [
                    'payment_id' => $payment->id,
                ]);
                return null;
            }
        }

        // Crear la solicitud en cola
        try {
            $request = BsaleRequest::create([
                'payment_id' => $payment->id,
                'order_detail_id' => $payment->order_detail_id,
                'status' => BsaleRequest::STATUS_PENDING,
                'document_type' => $documentType,
                'source' => $source,
                'metadata' => $metadata ?? [
                    'payment_amount' => $payment->amount,
                    'payment_status' => $payment->status,
                    'created_at' => now()->toIso8601String(),
                ],
                'scheduled_at' => now(),
            ]);

            Log::channel('bsale')->info('BsaleQueueService: Solicitud de boleta encolada exitosamente', [
                'payment_id' => $payment->id,
                'bsale_request_id' => $request->id,
            ]);

            return $request;

        } catch (Exception $e) {
            Log::channel('bsale')->error('BsaleQueueService: Error al encolar solicitud', [
                'payment_id' => $payment->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Procesar una solicitud de BSale
     */
    public function processRequest(BsaleRequest $request): bool
    {
        Log::channel('bsale')->info('BsaleQueueService: Procesando solicitud', [
            'bsale_request_id' => $request->id,
            'payment_id' => $request->payment_id,
            'attempt' => $request->attempts + 1,
        ]);

        // Marcar como en proceso
        $request->markAsProcessing();

        try {
            $payment = $request->payment;
            $orderDetail = $request->orderDetail ?? $payment->orderDetail;

            if (!$payment || !$orderDetail) {
                $request->markAsFailed('Payment u OrderDetail no encontrado');
                return false;
            }

            // VALIDACIÓN: Verificar nuevamente si el payment ya tiene boleta (pudo generarse por otro proceso)
            $payment->refresh();
            if (!empty($payment->bsale_document_id)) {
                $request->markAsSkipped('Payment ya tiene boleta BSale: ' . $payment->bsale_document_id);
                Log::channel('bsale')->info('BsaleQueueService: Payment ya tiene boleta, omitiendo', [
                    'bsale_request_id' => $request->id,
                    'bsale_document_id' => $payment->bsale_document_id,
                ]);
                return true;
            }

            // Guardar datos de la solicitud
            $request->update([
                'request_data' => [
                    'payment_id' => $payment->id,
                    'order_detail_id' => $orderDetail->id,
                    'amount' => $payment->amount,
                    'participant_name' => $orderDetail->order?->participant?->full_name,
                    'timestamp' => now()->toIso8601String(),
                ],
            ]);

            // Llamar a BSale para generar la boleta
            $bsaleResult = $this->bsaleService->generateInvoice($orderDetail, $payment);

            if ($bsaleResult && !empty($bsaleResult['id'])) {
                // Éxito: Actualizar payment con datos de BSale
                $payment->update([
                    'bsale_document_id' => $bsaleResult['id'] ?? null,
                    'bsale_number' => $bsaleResult['number'] ?? null,
                    'bsale_token' => $bsaleResult['token'] ?? null,
                ]);

                // Marcar solicitud como completada
                $request->markAsCompleted(
                    $bsaleResult,
                    $bsaleResult['id'] ?? null,
                    $bsaleResult['number'] ?? null,
                    $bsaleResult['token'] ?? null
                );

                // Despachar email de boleta con 1 hora de delay
                $payment->refresh();
                SendBsaleEmailJob::dispatch($payment->id, $orderDetail->id)
                    ->delay(now()->addHour());

                Log::channel('bsale')->info('BsaleQueueService: Boleta generada exitosamente', [
                    'bsale_request_id' => $request->id,
                    'payment_id' => $payment->id,
                    'bsale_document_id' => $bsaleResult['id'] ?? null,
                    'bsale_number' => $bsaleResult['number'] ?? null,
                ]);

                return true;

            } else {
                // BSale retornó respuesta pero sin ID válido
                $request->markAsFailed(
                    'BSale no retornó ID de documento válido',
                    'INVALID_RESPONSE',
                    $bsaleResult
                );

                Log::channel('bsale')->warning('BsaleQueueService: BSale no retornó ID válido', [
                    'bsale_request_id' => $request->id,
                    'payment_id' => $payment->id,
                    'response' => $bsaleResult,
                ]);

                return false;
            }

        } catch (Exception $e) {
            // Error en el proceso
            $errorMessage = $e->getMessage();
            $errorCode = $e->getCode() ?: 'EXCEPTION';

            $request->markAsFailed($errorMessage, (string) $errorCode);

            Log::channel('bsale')->error('BsaleQueueService: Error al procesar solicitud', [
                'bsale_request_id' => $request->id,
                'payment_id' => $request->payment_id,
                'error' => $errorMessage,
                'trace' => $e->getTraceAsString(),
            ]);

            return false;
        }
    }

    /**
     * Procesar todas las solicitudes pendientes
     */
    public function processPendingRequests(int $limit = 50): array
    {
        $results = [
            'processed' => 0,
            'success' => 0,
            'failed' => 0,
            'skipped' => 0,
        ];

        $requests = BsaleRequest::readyToProcess()
            ->orderBy('scheduled_at', 'asc')
            ->orderBy('created_at', 'asc')
            ->limit($limit)
            ->get();

        Log::channel('bsale')->info('BsaleQueueService: Iniciando procesamiento de cola', [
            'pending_count' => $requests->count(),
            'limit' => $limit,
        ]);

        foreach ($requests as $request) {
            $results['processed']++;

            try {
                $success = $this->processRequest($request);

                // Recargar para obtener estado actualizado
                $request->refresh();

                if ($request->status === BsaleRequest::STATUS_COMPLETED) {
                    $results['success']++;
                } elseif ($request->status === BsaleRequest::STATUS_SKIPPED) {
                    $results['skipped']++;
                } else {
                    $results['failed']++;
                }

            } catch (Exception $e) {
                $results['failed']++;
                Log::channel('bsale')->error('BsaleQueueService: Error procesando solicitud', [
                    'bsale_request_id' => $request->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        Log::channel('bsale')->info('BsaleQueueService: Procesamiento de cola completado', $results);

        return $results;
    }

    /**
     * Reintentar una solicitud fallida
     */
    public function retryRequest(BsaleRequest $request, ?int $userId = null): bool
    {
        if (!$request->canRetry()) {
            Log::channel('bsale')->warning('BsaleQueueService: Solicitud no puede reintentarse', [
                'bsale_request_id' => $request->id,
                'status' => $request->status,
                'attempts' => $request->attempts,
                'max_attempts' => $request->max_attempts,
            ]);
            return false;
        }

        // Resetear para reintentar
        $request->update([
            'status' => BsaleRequest::STATUS_PENDING,
            'error_message' => null,
            'error_code' => null,
            'processing_started_at' => null,
            'scheduled_at' => now(),
            'metadata' => array_merge($request->metadata ?? [], [
                'manual_retry_at' => now()->toIso8601String(),
                'manual_retry_by' => $userId,
            ]),
        ]);

        Log::channel('bsale')->info('BsaleQueueService: Solicitud programada para reintento', [
            'bsale_request_id' => $request->id,
            'user_id' => $userId,
        ]);

        return true;
    }

    /**
     * Forzar reprocesamiento de una solicitud (ignorando validaciones)
     */
    public function forceReprocess(BsaleRequest $request, ?int $userId = null): bool
    {
        // Resetear completamente
        $request->update([
            'status' => BsaleRequest::STATUS_PENDING,
            'attempts' => 0,
            'error_message' => null,
            'error_code' => null,
            'processing_started_at' => null,
            'processed_at' => null,
            'bsale_document_id' => null,
            'bsale_number' => null,
            'bsale_token' => null,
            'response_data' => null,
            'scheduled_at' => now(),
            'metadata' => array_merge($request->metadata ?? [], [
                'force_reprocess_at' => now()->toIso8601String(),
                'force_reprocess_by' => $userId,
            ]),
        ]);

        Log::channel('bsale')->info('BsaleQueueService: Solicitud forzada para reprocesamiento', [
            'bsale_request_id' => $request->id,
            'user_id' => $userId,
        ]);

        return true;
    }

    /**
     * Obtener estadísticas de la cola
     */
    public function getQueueStats(): array
    {
        return [
            'pending' => BsaleRequest::pending()->count(),
            'processing' => BsaleRequest::where('status', BsaleRequest::STATUS_PROCESSING)->count(),
            'completed_today' => BsaleRequest::completed()
                ->whereDate('processed_at', today())
                ->count(),
            'failed' => BsaleRequest::failed()->count(),
            'total_today' => BsaleRequest::whereDate('created_at', today())->count(),
        ];
    }

    /**
     * Determinar si un pago es de suscripción
     */
    protected function isSubscriptionPayment(Payment $payment, $order): bool
    {
        // 1. Si payment_type es 'total', NUNCA es suscripción (salir inmediatamente)
        $paymentType = $order->payment_type ?? null;
        if ($paymentType === 'total') {
            return false;
        }

        // 2. Verificar payment_type (monthly, subscription, pat)
        if (in_array($paymentType, ['monthly', 'subscription', 'pat'])) {
            return true;
        }

        // 3. Verificar external_payment_id (solo si NO es 'total')
        if (!empty($payment->external_payment_id)) {
            return true;
        }

        // 4. Verificar order_number
        $orderNumber = $order->order_number ?? '';
        if (str_starts_with($orderNumber, 'SUB-')) {
            return true;
        }

        return false;
    }
}
