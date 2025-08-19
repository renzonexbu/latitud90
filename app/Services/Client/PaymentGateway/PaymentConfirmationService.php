<?php

namespace App\Services\Client\PaymentGateway;

use App\Models\OrderDetail;
use App\Models\Payment;
use App\Models\Installment;
use App\Models\PendingPayment;
use App\Services\Client\PaymentGateway\TransbankService;
use App\Services\Client\PaymentGateway\KhipuService;
use App\Services\Mail\SuccessPaymentEmailService;
use App\Services\Client\BsaleService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class PaymentConfirmationService
{
    private $transbankService;
    private $khipuService;
    private $emailService;
    private $bsaleService;

    public function __construct(
        TransbankService $transbankService,
        KhipuService $khipuService,
        SuccessPaymentEmailService $emailService,
        BsaleService $bsaleService
    ) {
        $this->transbankService = $transbankService;
        $this->khipuService = $khipuService;
        $this->emailService = $emailService;
        $this->bsaleService = $bsaleService;
    }

    /**
     * Confirmar pago según el tipo de pasarela con validaciones específicas
     */
    public function confirmPayment(int $orderDetailId, string $gatewayType, array $gatewayData = []): array
    {
        try {
            $orderDetail = OrderDetail::findOrFail($orderDetailId);

            Log::info('PaymentConfirmationService: confirmPayment', [
                'order_detail_id' => $orderDetailId,
                'gateway_type' => $gatewayType,
                'gateway_data' => $gatewayData,
            ]);

            // Crear o actualizar registro de pago pendiente
            $pendingPayment = $this->createOrUpdatePendingPayment($orderDetail, $gatewayType, $gatewayData);

            // Realizar hasta 5 intentos de confirmación
            $maxAttempts = 5;
            $attempt = 1;
            $lastResult = null;

            while ($attempt <= $maxAttempts) {
                Log::info('PaymentConfirmationService: Intento de confirmación', [
                    'order_detail_id' => $orderDetailId,
                    'gateway_type' => $gatewayType,
                    'attempt' => $attempt,
                    'max_attempts' => $maxAttempts,
                ]);

                switch ($gatewayType) {
                    case 'transbank':
                        $lastResult = $this->confirmTransbankPayment($orderDetail, $gatewayData, $pendingPayment);
                        break;

                    case 'khipu':
                        $lastResult = $this->confirmKhipuPayment($orderDetail, $gatewayData, $pendingPayment);
                        break;

                    default:
                        throw new \Exception("Tipo de pasarela no soportado: {$gatewayType}");
                }

                // Si el pago fue aprobado, rechazado, cancelado o error, no continuar
                if (in_array($lastResult['status'], ['approved', 'rejected', 'canceled', 'error'])) {
                    Log::info('PaymentConfirmationService: Confirmación finalizada', [
                        'order_detail_id' => $orderDetailId,
                        'status' => $lastResult['status'],
                        'attempt' => $attempt,
                    ]);
                    return $lastResult;
                }

                // Si es pending y no es el último intento, esperar 2 segundos y continuar
                if ($lastResult['status'] === 'pending' && $attempt < $maxAttempts) {
                    Log::info('PaymentConfirmationService: Pago pendiente, esperando 2 segundos antes del siguiente intento', [
                        'order_detail_id' => $orderDetailId,
                        'attempt' => $attempt,
                    ]);
                    sleep(2);
                }

                $attempt++;
            }

            // Si llegamos aquí, se agotaron los intentos
            Log::info('PaymentConfirmationService: Se agotaron los intentos de confirmación', [
                'order_detail_id' => $orderDetailId,
                'gateway_type' => $gatewayType,
                'final_status' => $lastResult['status'] ?? 'unknown',
            ]);

            // Marcar como pendiente de validación
            $pendingPayment->markAsFailed('No se pudo confirmar el pago después de 5 intentos');

            return [
                'success' => false,
                'message' => 'No se pudo confirmar el pago después de varios intentos. El pago quedará pendiente de validación.',
                'status' => 'pending_validation',
                'data' => $lastResult['data'] ?? null,
            ];
        } catch (\Exception $e) {
            Log::error('PaymentConfirmationService: Error confirming payment', [
                'error' => $e->getMessage(),
                'order_detail_id' => $orderDetailId,
                'gateway_type' => $gatewayType,
            ]);

            return [
                'success' => false,
                'message' => 'Error al confirmar el pago: ' . $e->getMessage(),
                'status' => 'error',
            ];
        }
    }

    /**
     * Crear o actualizar registro de pago pendiente
     */
    private function createOrUpdatePendingPayment(OrderDetail $orderDetail, string $gatewayType, array $gatewayData): PendingPayment
    {
        return PendingPayment::updateOrCreate(
            [
                'order_detail_id' => $orderDetail->id,
                'gateway_type' => $gatewayType,
            ],
            [
                'gateway_data' => $gatewayData,
                'status' => 'pending',
                'attempts' => DB::raw('attempts + 1'),
                'last_attempt_at' => now()->setTimezone('America/Santiago'),
            ]
        );
    }

    /**
     * Confirmar pago de Transbank con validaciones específicas según documentación
     */
    private function confirmTransbankPayment(OrderDetail $orderDetail, array $gatewayData, PendingPayment $pendingPayment): array
    {
        $tokenWs = $gatewayData['token_ws'] ?? null;

        Log::info('PaymentConfirmationService: confirmTransbankPayment', [
            'order_detail_id' => $orderDetail->id,
            'gateway_data' => $gatewayData,
            'token_ws' => $tokenWs,
        ]);

        if (!$tokenWs) {
            Log::error('PaymentConfirmationService: Token de Transbank no proporcionado', [
                'order_detail_id' => $orderDetail->id,
                'gateway_data' => $gatewayData,
            ]);
            // No crear registro de pago fallido para errores técnicos de retroceso
            $pendingPayment->markAsFailed('Error al procesar el pago'); 
            return [
                'success' => false,
                'message' => 'Ha ocurrido un error durante el procesamiento del pago.',
                'status' => 'error',
            ];
        }

        try {
            $result = $this->transbankService->confirmTransaction($tokenWs);

            // Validar responseCode según documentación de Transbank
            if ($result['success'] && $result['response_code'] === 0) {
                // Pago aprobado - responseCode = 0
                $this->processSuccessfulPayment($orderDetail, $result, 'transbank');
                $pendingPayment->markAsConfirmed();

                return [
                    'success' => true,
                    'message' => 'Pago confirmado exitosamente',
                    'status' => 'approved',
                    'data' => $result,
                ];
            } else {
                // Pago rechazado por Transbank - responseCode ≠ 0
                $errorMessage = $result['error'] ?? 'Pago rechazado por Transbank';
                $this->processFailedPayment($orderDetail, $result, 'transbank', $errorMessage);
                $pendingPayment->markAsFailed($errorMessage);

                return [
                    'success' => false,
                    'message' => $errorMessage,
                    'status' => 'rejected',
                    'data' => $result,
                ];
            }
        } catch (\Exception $e) {
            Log::error('PaymentConfirmationService: Error confirming Transbank payment', [
                'error' => $e->getMessage(),
                'order_detail_id' => $orderDetail->id,
                'token_ws' => $tokenWs,
            ]);

            // No crear registro de pago fallido para errores técnicos
            $pendingPayment->markAsFailed('Error técnico: ' . $e->getMessage());

            return [
                'success' => false,
                'message' => 'Ha ocurrido un error durante el procesamiento del pago.',
                'status' => 'error',
            ];
        }
    }

    /**
     * Confirmar pago de Khipu con validaciones específicas según documentación
     */
    private function confirmKhipuPayment(OrderDetail $orderDetail, array $gatewayData, PendingPayment $pendingPayment): array
    {
        $paymentId = $gatewayData['payment_id'] ?? null;

        Log::info('PaymentConfirmationService: confirmKhipuPayment', [
            'order_detail_id' => $orderDetail->id,
            'gateway_data' => $gatewayData,
            'payment_id' => $paymentId,
        ]);

        if (!$paymentId) {
            Log::error('PaymentConfirmationService: ID de pago de Khipu no proporcionado', [
                'order_detail_id' => $orderDetail->id,
                'gateway_data' => $gatewayData,
            ]);
            // No crear registro de pago fallido para errores técnicos de retroceso
            $pendingPayment->markAsFailed('Error técnico en el procesamiento');
            return [
                'success' => false,
                'message' => 'Ha ocurrido un error durante el procesamiento del pago.',
                'status' => 'error',
            ];
        }

        try {
            $result = $this->khipuService->getPaymentStatus($paymentId);

            // Validar status según documentación de Khipu
            switch ($result['status']) {
                case 'done':
                    // Pago realizado y confirmado
                    $this->processSuccessfulPayment($orderDetail, $result, 'khipu');
                    $pendingPayment->markAsConfirmed();

                    return [
                        'success' => true,
                        'message' => 'Pago confirmado exitosamente',
                        'status' => 'approved',
                        'data' => $result,
                    ];

                case 'rejected':
                    // El banco o el flujo rechazó el pago
                    $this->processFailedPayment($orderDetail, $result, 'khipu', 'El banco o el flujo rechazó el pago');
                    $pendingPayment->markAsFailed('El banco o el flujo rechazó el pago');
                    return [
                        'success' => false,
                        'message' => 'El banco o el flujo rechazó el pago',
                        'status' => 'rejected',
                        'data' => $result,
                    ];

                case 'error':
                    // Problema técnico o validación de Khipu
                    $this->processFailedPayment($orderDetail, $result, 'khipu', 'Problema técnico o validación de Khipu');
                    $pendingPayment->markAsFailed('Problema técnico o validación de Khipu');
                    return [
                        'success' => false,
                        'message' => 'Problema técnico o validación de Khipu',
                        'status' => 'error',
                        'data' => $result,
                    ];

                case 'canceled':
                    // Cliente abortó desde la interfaz
                    $this->processFailedPayment($orderDetail, $result, 'khipu', 'Cliente abortó desde la interfaz');
                    $pendingPayment->markAsFailed('Cliente abortó desde la interfaz');
                    return [
                        'success' => false,
                        'message' => 'Cliente abortó desde la interfaz',
                        'status' => 'canceled',
                        'data' => $result,
                    ];

                case 'verifying':
                case 'pending':
                    // Cliente aún no finaliza o pago en verificación
                    return [
                        'success' => false,
                        'message' => $result['status'] === 'verifying' ? 'Pago en verificación' : 'Cliente aún no finaliza el pago',
                        'status' => 'pending',
                        'data' => $result,
                    ];

                default:
                    $pendingPayment->markAsFailed('Estado de pago desconocido: ' . $result['status']);
                    return [
                        'success' => false,
                        'message' => 'Estado de pago desconocido: ' . $result['status'],
                        'status' => 'unknown',
                        'data' => $result,
                    ];
            }
        } catch (\Exception $e) {
            Log::error('PaymentConfirmationService: Error confirming Khipu payment', [
                'error' => $e->getMessage(),
                'order_detail_id' => $orderDetail->id,
                'payment_id' => $paymentId,
            ]);

            // No crear registro de pago fallido para errores técnicos
            $pendingPayment->markAsFailed('Error técnico: ' . $e->getMessage());

            return [
                'success' => false,
                'message' => 'Ha ocurrido un error durante el procesamiento del pago.',
                'status' => 'error',
            ];
        }
    }

    /**
     * Procesar pago exitoso
     */
    private function processSuccessfulPayment(OrderDetail $orderDetail, array $result, string $gatewayType): void
    {
        // Buscar pago existente por order_detail_id (sin importar external_payment_id)
        $payment = Payment::where('order_detail_id', $orderDetail->id)
            ->latest()
            ->first();

        if (!$payment) {
            // Crear registro de pago solo si no existe
            $payment = Payment::create([
                'order_id' => $orderDetail->order_id,
                'order_detail_id' => $orderDetail->id,
                'payment_gateway_id' => $orderDetail->payment_gateway_id,
                'payment_option_id' => $orderDetail->payment_option_id,
                'buy_order' => $orderDetail->order->buy_order ?? null,
                'session_id' => $orderDetail->order->session_id ?? null,
                'external_payment_id' => $result['transaction_id'] ?? $result['payment_id'] ?? null,
                'amount' => $orderDetail->amount,
                'status' => 'completed',
                'gateway_response' => $result,
                'email_sent' => false, // Marcar que aún no se ha enviado el email
            ]);
        } else {
            // Actualizar el pago existente
            $payment->update([
                'status' => 'completed',
                'external_payment_id' => $result['transaction_id'] ?? $result['payment_id'] ?? $payment->external_payment_id,
                'gateway_response' => $result,
                'email_sent' => false,
            ]);
        }

        // Actualizar estado del order detail
        $orderDetail->update([
            'status' => 'paid',
            'is_paid' => true,
            'paid_at' => now()->setTimezone('America/Santiago'),
        ]);

        // Procesar cuotas si aplica
        $this->processInstallments($orderDetail, $payment);

        // Generar boleta en Bsale si es programa de entrega el mismo año
        $this->generateBsaleInvoice($orderDetail, $payment);

        // Enviar email de confirmación
        $this->sendSuccessEmail($orderDetail, $payment);

        Log::info('PaymentConfirmationService: Payment processed successfully', [
            'order_detail_id' => $orderDetail->id,
            'payment_id' => $payment->id,
            'gateway_type' => $gatewayType,
        ]);
    }

    /**
     * Procesar pago fallido (rechazado, cancelado, error)
     */
    private function processFailedPayment(OrderDetail $orderDetail, array $result, string $gatewayType, string $errorMessage): void
    {
        // Buscar pago existente por order_detail_id (sin importar external_payment_id)
        $payment = Payment::where('order_detail_id', $orderDetail->id)
            ->latest()
            ->first();

        if (!$payment) {
            // Crear registro de pago fallido solo si no existe
            $payment = Payment::create([
                'order_id' => $orderDetail->order_id,
                'order_detail_id' => $orderDetail->id,
                'payment_gateway_id' => $orderDetail->payment_gateway_id,
                'payment_option_id' => $orderDetail->payment_option_id,
                'buy_order' => $orderDetail->order->buy_order ?? null,
                'session_id' => $orderDetail->order->session_id ?? null,
                'external_payment_id' => $result['transaction_id'] ?? $result['payment_id'] ?? null,
                'amount' => $orderDetail->amount,
                'status' => 'failed',
                'gateway_response' => $result,
            ]);
        } else {
            // Actualizar el pago existente
            $payment->update([
                'status' => 'failed',
                'external_payment_id' => $result['transaction_id'] ?? $result['payment_id'] ?? $payment->external_payment_id,
                'gateway_response' => $result,
            ]);
        }

        // Actualizar estado del order detail como cancelado (fallido)
        $orderDetail->update([
            'status' => 'cancelled',
            'is_paid' => false,
            'paid_at' => null,
        ]);

        // NO actualizar installments - el usuario puede reintentar el pago de la misma cuota

        Log::info('PaymentConfirmationService: Failed payment processed', [
            'order_detail_id' => $orderDetail->id,
            'payment_id' => $payment->id,
            'gateway_type' => $gatewayType,
            'error_message' => $errorMessage,
        ]);
    }

    /**
     * Procesar cuotas del pago
     */
    private function processInstallments(OrderDetail $orderDetail, Payment $payment): void
    {
        // Buscar cuotas asociadas a este order detail usando installment_number
        // ya que las cuotas se crean inicialmente sin payment_order_detail_id
        $installments = Installment::where('installment_number', $orderDetail->installment_number)
            ->whereHas('installmentPlan', function($query) use ($orderDetail) {
                $query->where('participant_id', $orderDetail->order->participant_id)
                      ->where('program_id', $orderDetail->order->program_id);
            })
            ->where('status', 'pending')
            ->get();

        // Debug: buscar todas las cuotas relacionadas
        $allInstallments = Installment::where('installment_number', $orderDetail->installment_number)
            ->whereHas('installmentPlan', function($query) use ($orderDetail) {
                $query->where('participant_id', $orderDetail->order->participant_id)
                      ->where('program_id', $orderDetail->order->program_id);
            })
            ->get();
        
        Log::info('PaymentConfirmationService: Processing installments - Debug', [
            'order_detail_id' => $orderDetail->id,
            'payment_id' => $payment->id,
            'installment_number' => $orderDetail->installment_number,
            'participant_id' => $orderDetail->order->participant_id,
            'program_id' => $orderDetail->order->program_id,
            'all_installments_count' => $allInstallments->count(),
            'pending_installments_count' => $installments->count(),
            'all_installments' => $allInstallments->map(function($installment) {
                return [
                    'id' => $installment->id,
                    'installment_number' => $installment->installment_number,
                    'status' => $installment->status,
                    'payment_order_detail_id' => $installment->payment_order_detail_id,
                    'amount' => $installment->amount,
                    'installment_plan_id' => $installment->installment_plan_id,
                ];
            })->toArray(),
        ]);

        foreach ($installments as $installment) {
            $installment->markAsPaid(
                $orderDetail->order_id,
                $orderDetail->id,
                $payment->id
            );
        }

        Log::info('PaymentConfirmationService: Processing installments', [
            'order_detail_id' => $orderDetail->id,
            'payment_id' => $payment->id,
            'installments_processed' => $installments->count(),
        ]);
    }

    /**
     * Obtener datos del pago para las vistas
     */
    public function getPaymentData(int $orderDetailId): array
    {
        $orderDetail = OrderDetail::with(['order.program', 'payments', 'paymentGateway'])->findOrFail($orderDetailId);

        $payment = $orderDetail->payments->first();

        return [
            'order_detail' => $orderDetail,
            'order' => $orderDetail->order,
            'program' => $orderDetail->order->program ?? null,
            'payment' => $payment,
            // Campos derivados para la vista
            'gateway_type' => optional($orderDetail->paymentGateway)->code ?? optional($orderDetail->paymentGateway)->name ?? null,
            'amount' => $payment->amount ?? $orderDetail->amount,
            'transaction_id' => $payment->external_payment_id ?? $orderDetail->transaction_id,
            'total_installments' => $orderDetail->installments_number ?? 1,
            'installment_number' => $orderDetail->installment_number ?? 1,
        ];
    }

    /**
     * Obtener pagos pendientes de validación
     */
    public function getPendingPayments()
    {
        return PendingPayment::where('status', 'pending')
            ->where('attempts', '<', 5)
            ->with('orderDetail')
            ->get();
    }

    /**
     * Procesar pagos pendientes (para el job)
     */
    public function processPendingPayments(): void
    {
        $pendingPayments = $this->getPendingPayments();

        foreach ($pendingPayments as $pendingPayment) {
            try {
                $this->confirmPayment(
                    $pendingPayment->order_detail_id,
                    $pendingPayment->gateway_type,
                    $pendingPayment->gateway_data
                );
            } catch (\Exception $e) {
                Log::error('PaymentConfirmationService: Error processing pending payment', [
                    'pending_payment_id' => $pendingPayment->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Generar boleta en Bsale para programas de entrega el mismo año
     */
    private function generateBsaleInvoice(OrderDetail $orderDetail, Payment $payment): void
    {
        try {
            $bsaleResult = $this->bsaleService->generateInvoice($orderDetail, $payment);
            
            if ($bsaleResult) {
                // Guardar información de la boleta en el pago
                $payment->update([
                    'bsale_document_id' => $bsaleResult['id'] ?? null,
                    'bsale_number' => $bsaleResult['number'] ?? null,
                ]);
                
                Log::info('PaymentConfirmationService: Boleta Bsale generada exitosamente', [
                    'order_detail_id' => $orderDetail->id,
                    'payment_id' => $payment->id,
                    'bsale_document_id' => $bsaleResult['id'] ?? null,
                    'bsale_number' => $bsaleResult['number'] ?? null,
                ]);
            }
        } catch (\Exception $e) {
            Log::error('PaymentConfirmationService: Error generando boleta Bsale', [
                'order_detail_id' => $orderDetail->id,
                'payment_id' => $payment->id,
                'error' => $e->getMessage(),
            ]);
            // No lanzar excepción para no interrumpir el flujo de pago
        }
    }

    /**
     * Enviar email de confirmación de pago exitoso
     */
    private function sendSuccessEmail(OrderDetail $orderDetail, Payment $payment): void
    {
        // Verificar si ya se envió un email para este pago
        if ($payment->email_sent) {
            Log::info('PaymentConfirmationService: Email ya enviado anteriormente, omitiendo envío duplicado', [
                'order_detail_id' => $orderDetail->id,
                'payment_id' => $payment->id,
                'customer_email' => $orderDetail->email,
            ]);
            return;
        }
        
        try {
            $emailSent = $this->emailService->sendSuccessPaymentEmail($orderDetail, $payment);
            
            if ($emailSent) {
                // Marcar que se envió el email
                $payment->update(['email_sent' => true]);
                
                Log::info('PaymentConfirmationService: Email de confirmación enviado', [
                    'order_detail_id' => $orderDetail->id,
                    'payment_id' => $payment->id,
                    'customer_email' => $orderDetail->email,
                ]);
            } else {
                Log::warning('PaymentConfirmationService: Error al enviar email de confirmación', [
                    'order_detail_id' => $orderDetail->id,
                    'payment_id' => $payment->id,
                    'customer_email' => $orderDetail->email,
                ]);
            }
        } catch (\Exception $e) {
            Log::error('PaymentConfirmationService: Excepción al enviar email de confirmación', [
                'order_detail_id' => $orderDetail->id,
                'payment_id' => $payment->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
