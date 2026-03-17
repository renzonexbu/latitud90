<?php

namespace App\Services\Client\PaymentGateway;

use App\Models\OrderDetail;
use App\Models\Payment;
use App\Models\Installment;
use App\Models\PendingPayment;
use App\Services\Client\PaymentGateway\TransbankService;
use App\Services\Client\PaymentGateway\KhipuService;
use App\Services\Client\PaymentGateway\VirtualPosService;
use App\Services\Mail\SuccessPaymentEmailService;
use App\Services\Client\Integration\BsaleService;
use App\Services\EcommerceAnalyticsService;
use App\Helpers\PaymentDocumentTypeHelper;
use App\Traits\SystemLogging;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PaymentConfirmationService
{
    use SystemLogging;
    private $transbankService;
    private $khipuService;
    private $virtualPosService;
    private $emailService;
    private $bsaleService;
    private $analyticsService;

    public function __construct(
        TransbankService $transbankService,
        KhipuService $khipuService,
        VirtualPosService $virtualPosService,
        SuccessPaymentEmailService $emailService,
        BsaleService $bsaleService,
        EcommerceAnalyticsService $analyticsService
    ) {
        $this->transbankService = $transbankService;
        $this->khipuService = $khipuService;
        $this->virtualPosService = $virtualPosService;
        $this->emailService = $emailService;
        $this->bsaleService = $bsaleService;
        $this->analyticsService = $analyticsService;
    }

    /**
     * Confirmar pago según el tipo de pasarela con validaciones específicas
     */
    public function confirmPayment(int $orderDetailId, string $gatewayType, array $gatewayData = [], string $sessionId = null): array
    {
        try {
            $orderDetail = OrderDetail::findOrFail($orderDetailId);

            // Verificar si ya se procesó este pago para evitar duplicación
            if ($orderDetail->is_paid && $orderDetail->status === 'paid') {
                $this->logInfo('PaymentConfirmationService: Pago ya procesado anteriormente, omitiendo confirmación duplicada', [
                    'order_detail_id' => $orderDetailId,
                    'gateway_type' => $gatewayType,
                    'order_detail_status' => $orderDetail->status,
                    'order_detail_is_paid' => $orderDetail->is_paid,
                    'paid_at' => $orderDetail->paid_at,
                ]);

                return [
                    'success' => true,
                    'message' => 'Pago ya confirmado anteriormente',
                    'status' => 'already_confirmed',
                    'data' => null,
                ];
            }

            $this->logInfo('PaymentConfirmationService: confirmPayment', [
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
                $this->logInfo('PaymentConfirmationService: Intento de confirmación', [
                    'order_detail_id' => $orderDetailId,
                    'gateway_type' => $gatewayType,
                    'attempt' => $attempt,
                    'max_attempts' => $maxAttempts,
                ]);

                switch ($gatewayType) {
                    case 'transbank':
                    case 'virtualpos':
                        // Verificar flag para usar VirtualPOS (producción) o Transbank (pruebas)
                        $useVirtualPos = config('lat90.payment.use_virtualpos', true);

                        if ($useVirtualPos) {
                            $lastResult = $this->confirmVirtualPosPayment($orderDetail, $gatewayData, $pendingPayment, $sessionId);
                        } else {
                            $lastResult = $this->confirmTransbankPayment($orderDetail, $gatewayData, $pendingPayment, $sessionId);
                        }
                        break;

                    case 'khipu':
                        $lastResult = $this->confirmKhipuPayment($orderDetail, $gatewayData, $pendingPayment, $sessionId);
                        break;

                    default:
                        throw new \Exception("Tipo de pasarela no soportado: {$gatewayType}");
                }

                // Si el pago fue aprobado, rechazado, cancelado o error, no continuar
                if (in_array($lastResult['status'], ['approved', 'rejected', 'canceled', 'error'])) {
                    $this->logInfo('PaymentConfirmationService: Confirmación finalizada', [
                        'order_detail_id' => $orderDetailId,
                        'status' => $lastResult['status'],
                        'attempt' => $attempt,
                    ]);
                    return $lastResult;
                }

                // Si es pending y no es el último intento, esperar 2 segundos y continuar
                if ($lastResult['status'] === 'pending' && $attempt < $maxAttempts) {
                    $this->logInfo('PaymentConfirmationService: Pago pendiente, esperando 2 segundos antes del siguiente intento', [
                        'order_detail_id' => $orderDetailId,
                        'attempt' => $attempt,
                    ]);
                    sleep(2);
                }

                $attempt++;
            }

            // Si llegamos aquí, se agotaron los intentos
            $this->logInfo('PaymentConfirmationService: Se agotaron los intentos de confirmación', [
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
                'trace' => $e->getTraceAsString(),
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
     * Confirmar pago de VirtualPOS (reemplaza a Transbank)
     */
    private function confirmVirtualPosPayment(OrderDetail $orderDetail, array $gatewayData, PendingPayment $pendingPayment, string $sessionId = null): array
    {
        // VirtualPos puede recibir el ID como 'payment_id', 'token', o 'token_ws'
        $paymentId = $gatewayData['payment_id'] ?? $gatewayData['token'] ?? $gatewayData['token_ws'] ?? null;

        $this->logInfo('=== GATEWAY: Iniciando confirmación VirtualPOS ===', [
            'order_detail_id' => $orderDetail->id,
            'payment_id' => $paymentId,
            'gateway_data_keys' => array_keys($gatewayData),
        ]);

        if (!$paymentId) {
            $this->logError('=== GATEWAY ERROR: ID de pago VirtualPOS no proporcionado ===', [
                'order_detail_id' => $orderDetail->id,
                'gateway_data' => $gatewayData,
            ]);
            $pendingPayment->markAsFailed('Error al procesar el pago');
            return [
                'success' => false,
                'message' => 'Ha ocurrido un error durante el procesamiento del pago.',
                'status' => 'error',
            ];
        }

        try {
            $this->logInfo('=== GATEWAY: Consultando estado en VirtualPOS ===', [
                'payment_id' => $paymentId,
            ]);

            $result = $this->virtualPosService->confirmTransaction($paymentId);

            $this->logInfo('=== GATEWAY: Respuesta de VirtualPOS recibida ===', [
                'success' => $result['success'] ?? false,
                'status' => $result['status'] ?? 'unknown',
                'authorization_code' => $result['authorization_code'] ?? null,
                'amount' => $result['amount'] ?? null,
                'error' => $result['error'] ?? null,
            ]);

            // Validar status según documentación de VirtualPOS
            if ($result['success'] && in_array($result['status'], \App\Services\Client\PaymentGateway\VirtualPosService::APPROVED_STATUSES)) {
                $this->logInfo('=== GATEWAY: Pago APROBADO - Iniciando processSuccessfulPayment ===', [
                    'order_detail_id' => $orderDetail->id,
                    'virtualpos_status' => $result['status'],
                ]);

                $this->processSuccessfulPayment($orderDetail, $result, 'virtualpos', $sessionId);
                $pendingPayment->markAsConfirmed();

                $this->logInfo('=== GATEWAY: processSuccessfulPayment completado ===', [
                    'order_detail_id' => $orderDetail->id,
                ]);

                return [
                    'success' => true,
                    'message' => 'Pago confirmado exitosamente',
                    'status' => 'approved',
                    'data' => $result,
                ];
            } else {
                // Pago rechazado por VirtualPOS
                $errorMessage = $result['error'] ?? 'Pago rechazado por VirtualPOS';

                $this->logWarning('=== GATEWAY: Pago RECHAZADO por VirtualPOS ===', [
                    'order_detail_id' => $orderDetail->id,
                    'virtualpos_status' => $result['status'] ?? 'unknown',
                    'error_message' => $errorMessage,
                ]);

                $this->processFailedPayment($orderDetail, $result, 'virtualpos', $errorMessage);
                $pendingPayment->markAsFailed($errorMessage);

                return [
                    'success' => false,
                    'message' => $errorMessage,
                    'status' => 'rejected',
                    'data' => $result,
                ];
            }
        } catch (\Exception $e) {
            $this->logError('=== GATEWAY ERROR: Excepción en confirmación VirtualPOS ===', [
                'error' => $e->getMessage(),
                'error_file' => $e->getFile(),
                'error_line' => $e->getLine(),
                'order_detail_id' => $orderDetail->id,
                'payment_id' => $paymentId,
            ], $e);

            $pendingPayment->markAsFailed('Error técnico: ' . $e->getMessage());

            return [
                'success' => false,
                'message' => 'Ha ocurrido un error durante el procesamiento del pago.',
                'status' => 'error',
            ];
        }
    }

    /**
     * Confirmar pago de Transbank (pruebas)
     */
    private function confirmTransbankPayment(OrderDetail $orderDetail, array $gatewayData, PendingPayment $pendingPayment, string $sessionId = null): array
    {
        // Transbank puede recibir el token como 'token' o 'token_ws'
        $token = $gatewayData['token'] ?? $gatewayData['token_ws'] ?? null;

        $this->logInfo('PaymentConfirmationService: confirmTransbankPayment', [
            'order_detail_id' => $orderDetail->id,
            'gateway_data' => $gatewayData,
            'token' => $token,
        ]);

        if (!$token) {
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
            $result = $this->transbankService->confirmTransaction($token);

            // Validar status según documentación de Transbank
            if ($result['success'] && $result['response_code'] === 0) {
                // Pago aprobado
                $this->processSuccessfulPayment($orderDetail, $result, 'transbank', $sessionId);
                $pendingPayment->markAsConfirmed();

                return [
                    'success' => true,
                    'message' => 'Pago confirmado exitosamente',
                    'status' => 'approved',
                    'data' => $result,
                ];
            } else {
                // Pago rechazado por Transbank
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
                'token' => $token,
                'trace' => $e->getTraceAsString(),
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
    private function confirmKhipuPayment(OrderDetail $orderDetail, array $gatewayData, PendingPayment $pendingPayment, string $sessionId = null): array
    {
        $paymentId = $gatewayData['payment_id'] ?? null;

        $this->logInfo('PaymentConfirmationService: confirmKhipuPayment', [
            'order_detail_id' => $orderDetail->id,
            'gateway_data' => $gatewayData,
            'payment_id' => $paymentId,
        ]);

        if (!$paymentId) {
            $this->logError('PaymentConfirmationService: ID de pago de Khipu no proporcionado', [
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
                    $this->processSuccessfulPayment($orderDetail, $result, 'khipu', $sessionId);
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
            $this->logError('PaymentConfirmationService: Error confirming Khipu payment', [
                'error' => $e->getMessage(),
                'order_detail_id' => $orderDetail->id,
                'payment_id' => $paymentId,
            ], $e);

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
    private function processSuccessfulPayment(OrderDetail $orderDetail, array $result, string $gatewayType, string $sessionId = null): void
    {
        $this->logInfo('=== PASO 1/10: INICIO processSuccessfulPayment ===', [
            'order_detail_id' => $orderDetail->id,
            'gateway_type' => $gatewayType,
            'order_detail_status' => $orderDetail->status,
            'order_detail_is_paid' => $orderDetail->is_paid,
            'session_id' => $sessionId,
        ]);

        // === VALIDACIÓN ANTI-DUPLICADO: Verificar saldo antes de procesar ===
        $order = $orderDetail->order;
        if ($order && $order->participant_id && $order->program_id) {
            $programCourse = \App\Models\ProgramCourse::with(['course.participants'])->find($order->program_id);
            $participant = \App\Models\Participant::find($order->participant_id);

            if ($programCourse && $participant) {
                $priceData = \App\Helpers\ParticipantPriceHelper::calculateParticipantPrice($participant, $programCourse);
                $totalPrice = $priceData['final_price'];

                $alreadyPaid = (float) Payment::whereHas('order', function ($q) use ($participant, $programCourse) {
                    $q->where('participant_id', $participant->id)
                      ->where('program_id', $programCourse->id);
                })
                    ->whereIn('status', ['approved', 'completed'])
                    ->sum('amount');

                $remainingBalance = max($totalPrice - $alreadyPaid, 0);

                if ($remainingBalance <= 0) {
                    $this->logInfo('=== PAGO BLOQUEADO: Saldo ya cubierto por otro pago ===', [
                        'order_detail_id' => $orderDetail->id,
                        'participant_id' => $participant->id,
                        'total_price' => $totalPrice,
                        'already_paid' => $alreadyPaid,
                        'remaining_balance' => $remainingBalance,
                        'amount_this_payment' => $orderDetail->amount,
                    ]);

                    $orderDetail->update([
                        'status' => 'overpaid',
                        'gateway_response' => $result,
                    ]);

                    $order->update(['status' => 'overpaid']);

                    return;
                }
            }
        }

        // PASO 2: Buscar pago existente
        $this->logInfo('=== PASO 2/10: Buscando pago existente ===', [
            'order_detail_id' => $orderDetail->id,
        ]);

        $payment = Payment::where('order_detail_id', $orderDetail->id)
            ->latest()
            ->first();

        $this->logInfo('=== PASO 2/10: Resultado búsqueda pago ===', [
            'payment_found' => $payment ? true : false,
            'payment_id' => $payment?->id,
            'payment_status' => $payment?->status,
        ]);

        // PASO 3: Determinar la fecha de transacción
        $transactionDate = null;
        if (isset($result['transaction_date'])) {
            $transactionDate = $this->parseTransactionDate($result['transaction_date']);
        } elseif (isset($result['paid_at'])) {
            $transactionDate = $this->parseTransactionDate($result['paid_at']);
        } elseif (isset($result['updated_at'])) {
            $transactionDate = $this->parseTransactionDate($result['updated_at']);
        } else {
            $transactionDate = now()->setTimezone('America/Santiago');
        }

        // PASO 4: Extraer y sanitizar datos del gateway
        $this->logInfo('=== PASO 3/10: Extrayendo datos del gateway ===', [
            'gateway_type' => $gatewayType,
        ]);

        $authorizationCode = $result['authorization_code'] ?? $result['auth_code'] ?? null;
        $cardType = null;
        $installmentsNumber = null;
        $installmentAmount = null;
        $vci = $result['vci'] ?? null;
        $cardNumber = $result['card_number'] ?? null;

        // Mapear datos específicos según el gateway
        if ($gatewayType === 'virtualpos') {
            $cardType = $result['payment_method'] ?? $result['payment_type'] ?? null;
            $installmentsNumber = $result['installments'] ?? null;

            // Sanitizar valores que pueden venir como string vacío
            $rawInstallmentAmount = $result['installment_amount'] ?? null;
            $installmentAmount = ($rawInstallmentAmount !== '' && $rawInstallmentAmount !== null) ? $rawInstallmentAmount : null;

            // Extraer datos de la estructura anidada de VirtualPos
            $data = $result['data'] ?? [];
            $paymentData = $data['payment'] ?? [];
            $orderData = $paymentData['order'] ?? [];

            if (!$authorizationCode && !empty($orderData['auth_code'])) {
                $authorizationCode = $orderData['auth_code'];
            }
            if (!$cardNumber && !empty($orderData['card_number'])) {
                $cardNumber = $orderData['card_number'];
            }
            if (!$cardType && !empty($orderData['payment_type_code'])) {
                $cardType = $orderData['payment_type_code'];
            }
            if (!$installmentsNumber && !empty($orderData['installments'])) {
                $installmentsNumber = $orderData['installments'];
            }

            // Sanitizar más valores que pueden venir como string vacío
            $authorizationCode = ($authorizationCode !== '' && $authorizationCode !== null) ? $authorizationCode : null;
            $cardNumber = ($cardNumber !== '' && $cardNumber !== null) ? $cardNumber : null;
            $cardType = ($cardType !== '' && $cardType !== null) ? $cardType : null;
            $installmentsNumber = ($installmentsNumber !== '' && $installmentsNumber !== null) ? $installmentsNumber : null;

        } elseif ($gatewayType === 'transbank') {
            $fullResponse = $result['full_response'] ?? [];
            $details = $fullResponse['details'] ?? [];
            $firstDetail = $details[0] ?? null;

            if ($firstDetail) {
                $cardType = $firstDetail['payment_type_code'] ?? null;
                $installmentsNumber = $firstDetail['installments_number'] ?? null;
                $cardNumber = $fullResponse['card_detail']['card_number'] ?? null;
            }
        } elseif ($gatewayType === 'khipu') {
            $isVirtualPosMode = $this->khipuService->isProductionMode();

            if ($isVirtualPosMode) {
                $data = $result['data'] ?? [];
                $paymentData = $data['payment'] ?? [];
                $orderData = $paymentData['order'] ?? [];

                if (!$authorizationCode) {
                    $authorizationCode = $result['auth_code'] ?? $orderData['auth_code'] ?? $paymentData['auth_code'] ?? null;
                }
                if (!$cardNumber) {
                    $cardNumber = $result['card_number'] ?? $orderData['card_number'] ?? null;
                }
                if (!$cardType) {
                    $cardType = $result['payment_type'] ?? $orderData['payment_type_code'] ?? $orderData['payment_method'] ?? 'KHIPU';
                }
                if (!$installmentsNumber && !empty($orderData['installments'])) {
                    $installmentsNumber = $orderData['installments'];
                }
            } else {
                $cardType = 'KHIPU';
                $data = $result['data'] ?? [];
                if (!empty($data['bank'])) {
                    $cardNumber = $data['bank'];
                }
            }
        }

        // Normalizar card_type para consistencia
        if ($cardType) {
            $cardType = $this->normalizeCardType($cardType);
        }

        $this->logInfo('=== PASO 3/10: Datos extraídos del gateway ===', [
            'authorization_code' => $authorizationCode,
            'card_type' => $cardType,
            'card_number' => $cardNumber,
            'installments_number' => $installmentsNumber,
            'installment_amount' => $installmentAmount,
            'transaction_date' => $transactionDate,
        ]);

        // PASO 5: Crear o actualizar Payment
        $this->logInfo('=== PASO 4/10: Creando/actualizando Payment ===', [
            'action' => $payment ? 'update' : 'create',
            'payment_id' => $payment?->id,
        ]);

        try {
            if (!$payment) {
                $paymentData = [
                    'order_id' => $orderDetail->order_id,
                    'order_detail_id' => $orderDetail->id,
                    'payment_gateway_id' => $orderDetail->payment_gateway_id,
                    'payment_option_id' => $orderDetail->payment_option_id,
                    'buy_order' => $orderDetail->order->order_number,
                    'session_id' => $orderDetail->order->session_id ?? null,
                    'external_payment_id' => $result['transaction_id'] ?? $result['payment_id'] ?? null,
                    'amount' => $orderDetail->amount,
                    'status' => 'completed',
                    'payment_source' => 'online', // Pago total online
                    'transaction_date' => $transactionDate,
                    'authorization_code' => $authorizationCode,
                    'card_type' => $cardType,
                    'installments_number' => $installmentsNumber,
                    'installment_amount' => $installmentAmount,
                    'vci' => $vci,
                    'card_number' => $cardNumber,
                    'gateway_response' => $result,
                    'email_sent' => false,
                    'document_type' => PaymentDocumentTypeHelper::determineDocumentType($orderDetail->order->program_id),
                ];

                $payment = Payment::create($paymentData);

                $this->logInfo('=== PASO 4/10: Payment CREADO exitosamente ===', [
                    'payment_id' => $payment->id,
                ]);
            } else {
                $updateData = [
                    'status' => 'completed',
                    'external_payment_id' => $result['transaction_id'] ?? $result['payment_id'] ?? $payment->external_payment_id,
                    'authorization_code' => $authorizationCode,
                    'card_type' => $cardType,
                    'installments_number' => $installmentsNumber,
                    'installment_amount' => $installmentAmount,
                    'vci' => $vci,
                    'card_number' => $cardNumber,
                    'gateway_response' => $result,
                    'email_sent' => false,
                    'document_type' => PaymentDocumentTypeHelper::determineDocumentType($orderDetail->order->program_id),
                ];

                if (!$payment->transaction_date || isset($result['transaction_date']) || isset($result['paid_at'])) {
                    $updateData['transaction_date'] = $transactionDate;
                }

                $payment->update($updateData);

                $this->logInfo('=== PASO 4/10: Payment ACTUALIZADO exitosamente ===', [
                    'payment_id' => $payment->id,
                ]);
            }
        } catch (\Exception $e) {
            $this->logError('=== PASO 4/10: ERROR creando/actualizando Payment ===', [
                'error' => $e->getMessage(),
                'order_detail_id' => $orderDetail->id,
            ], $e);
            throw $e;
        }

        // PASO 6: Actualizar OrderDetail
        $this->logInfo('=== PASO 5/10: Actualizando OrderDetail ===', [
            'order_detail_id' => $orderDetail->id,
        ]);

        try {
            $orderDetail->update([
                'status' => 'paid',
                'is_paid' => true,
                'paid_at' => now()->setTimezone('America/Santiago'),
            ]);

            $this->logInfo('=== PASO 5/10: OrderDetail actualizado exitosamente ===', [
                'order_detail_id' => $orderDetail->id,
                'new_status' => 'paid',
            ]);
        } catch (\Exception $e) {
            $this->logError('=== PASO 5/10: ERROR actualizando OrderDetail ===', [
                'error' => $e->getMessage(),
                'order_detail_id' => $orderDetail->id,
            ], $e);
            throw $e;
        }

        // PASO 7: Procesar cuotas
        $this->logInfo('=== PASO 6/10: Procesando cuotas ===', [
            'order_detail_id' => $orderDetail->id,
            'payment_id' => $payment->id,
        ]);

        try {
            $this->processInstallments($orderDetail, $payment);
            $this->logInfo('=== PASO 6/10: Cuotas procesadas exitosamente ===');
        } catch (\Exception $e) {
            $this->logError('=== PASO 6/10: ERROR procesando cuotas ===', [
                'error' => $e->getMessage(),
            ], $e);
            // No lanzar excepción para no interrumpir el flujo
        }

        // ======================================================================
        // PASO 7/10: DIFERIDO - Generación de documentos (BSale/Contrato/Anticipo)
        // ======================================================================
        // NO se genera documento aquí. El Job payments:send-pending-emails se encarga de:
        // 1. Re-verificar el estado del pago con la pasarela
        // 2. Determinar el tipo de documento según año del programa vs año del pago
        // 3. Generar el documento apropiado (Boleta BSale, Contrato, o Anticipo)
        // 4. Enviar el email con los documentos adjuntos
        // ======================================================================
        $this->logInfo('=== PASO 7/10: Documento DIFERIDO al Job ===', [
            'order_detail_id' => $orderDetail->id,
            'payment_id' => $payment->id,
            'document_type' => $payment->document_type,
            'note' => 'La generación de documentos se hará en el Job payments:send-pending-emails',
        ]);

        // ======================================================================
        // PASO 8/10: DIFERIDO - Email de confirmación
        // ======================================================================
        // NO se envía email aquí. El Job payments:send-pending-emails se encarga de:
        // 1. Esperar el delay configurado (default 10 min)
        // 2. Re-verificar confirmación del pago
        // 3. Generar documentos apropiados
        // 4. Enviar email con adjuntos
        // ======================================================================
        $this->logInfo('=== PASO 8/10: Email DIFERIDO al Job ===', [
            'order_detail_id' => $orderDetail->id,
            'payment_id' => $payment->id,
            'customer_email' => $orderDetail->email,
            'note' => 'El email se enviará desde el Job payments:send-pending-emails después del delay configurado',
        ]);

        // PASO 10: Actualizar estado de la orden
        $this->logInfo('=== PASO 9/10: Actualizando estado de la orden ===', [
            'order_id' => $orderDetail->order_id,
        ]);

        try {
            $orderDetail->order->refreshStatus();
            $this->logInfo('=== PASO 9/10: Estado de orden actualizado ===', [
                'order_status' => $orderDetail->order->status,
            ]);
        } catch (\Exception $e) {
            $this->logError('=== PASO 9/10: ERROR actualizando estado de orden ===', [
                'error' => $e->getMessage(),
            ], $e);
        }

        // PASO 11: Analytics
        $this->logInfo('=== PASO 10/10: Registrando en analytics ===');

        try {
            $this->analyticsService->recordPaymentCompletedFromBackend($orderDetail, [
                'payment_method' => $orderDetail->paymentGateway->name ?? null,
                'session_id' => $sessionId,
            ], null);
            $this->logInfo('=== PASO 10/10: Analytics registrado exitosamente ===');
        } catch (\Exception $e) {
            $this->logError('=== PASO 10/10: ERROR registrando analytics ===', [
                'error' => $e->getMessage(),
            ], $e);
        }

        $this->logInfo('=== PROCESO COMPLETADO: processSuccessfulPayment ===', [
            'order_detail_id' => $orderDetail->id,
            'payment_id' => $payment->id,
            'gateway_type' => $gatewayType,
            'order_detail_status' => $orderDetail->status,
            'order_detail_is_paid' => $orderDetail->is_paid,
            'order_status' => $orderDetail->order->status,
            'note' => 'Documentos y email se generarán/enviarán desde el Job payments:send-pending-emails',
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

        // Determinar la fecha de transacción para pagos fallidos
        $transactionDate = null;
        if (isset($result['transaction_date'])) {
            $transactionDate = $this->parseTransactionDate($result['transaction_date']);
        } elseif (isset($result['updated_at'])) {
            $transactionDate = $this->parseTransactionDate($result['updated_at']);
        } else {
            // Si no hay fecha específica en la respuesta, usar la fecha actual
            $transactionDate = now()->setTimezone('America/Santiago');
        }

        if (!$payment) {
            // Crear registro de pago fallido solo si no existe
            $paymentData = [
                'order_id' => $orderDetail->order_id,
                'order_detail_id' => $orderDetail->id,
                'payment_gateway_id' => $orderDetail->payment_gateway_id,
                'payment_option_id' => $orderDetail->payment_option_id,
                'buy_order' => $orderDetail->order->order_number,
                'session_id' => $orderDetail->order->session_id ?? null,
                'external_payment_id' => $result['transaction_id'] ?? $result['payment_id'] ?? null,
                'amount' => $orderDetail->amount,
                'status' => 'failed',
                'payment_source' => 'online', // Pago total online (fallido)
                'transaction_date' => $transactionDate,
                'gateway_response' => $result,
                'document_type' => PaymentDocumentTypeHelper::determineDocumentType($orderDetail->order->program_id),
            ];

            \Log::info('=== CREATING PAYMENT (FAILED) ===', $paymentData);

            $payment = Payment::create($paymentData);
        } else {
            // Actualizar el pago existente
            $updateData = [
                'status' => 'failed',
                'external_payment_id' => $result['transaction_id'] ?? $result['payment_id'] ?? $payment->external_payment_id,
                'gateway_response' => $result,
                'document_type' => PaymentDocumentTypeHelper::determineDocumentType($orderDetail->order->program_id),
            ];

            // Solo actualizar transaction_date si no está establecido o si viene en la respuesta
            if (!$payment->transaction_date || isset($result['transaction_date']) || isset($result['updated_at'])) {
                $updateData['transaction_date'] = $transactionDate;
            }

            $payment->update($updateData);
        }

        // Actualizar estado del order detail como fallido
        $orderDetail->update([
            'status' => 'failed',
            'is_paid' => false,
            'paid_at' => null,
        ]);

        // NO actualizar installments - el usuario puede reintentar el pago de la misma cuota

        // Registrar pago fallido en analytics
        $this->analyticsService->recordPaymentFailedFromBackend($orderDetail, $errorMessage);

        $this->logInfo('PaymentConfirmationService: Failed payment processed', [
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
        // IMPORTANTE: Solo vincular a planes de la MISMA orden para no cruzar
        // pagos por tarjeta con planes de suscripción del mismo participante/programa
        $installments = Installment::where('installment_number', $orderDetail->installment_number)
            ->whereHas('installmentPlan', function ($query) use ($orderDetail) {
                $query->where('participant_id', $orderDetail->order->participant_id)
                    ->where('program_id', $orderDetail->order->program_id)
                    ->where(function ($q) use ($orderDetail) {
                        // Priorizar plan vinculado a la misma orden
                        $q->where('order_id', $orderDetail->order_id)
                          // O planes sin suscripción (pago directo) cuya orden no esté cancelada
                          ->orWhere(function ($q2) use ($orderDetail) {
                              $q2->whereNull('program_subscription_id')
                                 ->whereHas('order', function ($q3) {
                                     $q3->whereNotIn('status', ['cancelled']);
                                 });
                          });
                    });
            })
            ->where('status', 'pending')
            ->get();

        // Debug: buscar todas las cuotas relacionadas
        $allInstallments = Installment::where('installment_number', $orderDetail->installment_number)
            ->whereHas('installmentPlan', function ($query) use ($orderDetail) {
                $query->where('participant_id', $orderDetail->order->participant_id)
                    ->where('program_id', $orderDetail->order->program_id);
            })
            ->get();

        $this->logInfo('PaymentConfirmationService: Processing installments - Debug', [
            'order_detail_id' => $orderDetail->id,
            'payment_id' => $payment->id,
            'installment_number' => $orderDetail->installment_number,
            'participant_id' => $orderDetail->order->participant_id,
            'program_id' => $orderDetail->order->program_id,
            'all_installments_count' => $allInstallments->count(),
            'pending_installments_count' => $installments->count(),
            'all_installments' => $allInstallments->map(function ($installment) {
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

        $this->logInfo('PaymentConfirmationService: Processing installments', [
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
        $orderDetail = OrderDetail::with(['order.program', 'order.programCourse', 'payments', 'paymentGateway'])->findOrFail($orderDetailId);

        $payment = $orderDetail->payments->first();

        // En la nueva arquitectura, program_id apunta a program_courses
        // Priorizar programCourse si existe, si no, usar program
        $programCourse = $orderDetail->order->programCourse ?? null;
        $program = $orderDetail->order->program ?? null;

        // Construir objeto de programa para la vista
        // Si existe programCourse, usarlo. Si no, usar program como fallback.
        $programData = null;
        if ($programCourse) {
            $programData = (object) [
                'id' => $programCourse->id,
                'name' => $programCourse->name ?? $program->name ?? null,
                'destination' => $programCourse->destination,
                'departure_date' => $programCourse->departure_date,
            ];
        } elseif ($program) {
            $programData = $program;
        }

        return [
            'order_detail' => $orderDetail,
            'order' => $orderDetail->order,
            'program' => $programData,
            'program_course' => $programCourse,
            'payment' => $payment,
            // Campos derivados para la vista
            'gateway_type' => optional($orderDetail->paymentGateway)->code ?? optional($orderDetail->paymentGateway)->name ?? null,
            'amount' => $payment->amount ?? $orderDetail->amount,
            'transaction_id' => $payment->external_payment_id ?? $orderDetail->transaction_id,
            'total_installments' => $orderDetail->installments_number ?? 1,
            'installment_number' => $orderDetail->installment_number ?? 1,
            'status' => $payment->status ?? $orderDetail->status ?? 'unknown',
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
                $this->logError('PaymentConfirmationService: Error processing pending payment', [
                    'pending_payment_id' => $pendingPayment->id,
                    'error' => $e->getMessage(),
                ], $e);
            }
        }
    }

    /**
     * Generar boleta en Bsale para programas de entrega el mismo año
     */
    private function generateBsaleInvoice(OrderDetail $orderDetail, Payment $payment): void
    {
        try {
            // CRÍTICO: Solo generar boleta si el pago está CONFIRMADO
            $confirmedStatuses = ['completed', 'approved'];
            if (!in_array($payment->status, $confirmedStatuses)) {
                $this->logWarning('PaymentConfirmationService: NO se genera boleta - pago NO está confirmado', [
                    'payment_id' => $payment->id,
                    'payment_status' => $payment->status,
                    'required_statuses' => $confirmedStatuses,
                ]);
                return;
            }

            $bsaleResult = $this->bsaleService->generateInvoice($orderDetail, $payment);

            if ($bsaleResult) {
                // Guardar información de la boleta en el pago
                $payment->update([
                    'bsale_document_id' => $bsaleResult['id'] ?? null,
                    'bsale_number' => $bsaleResult['number'] ?? null,
                    'bsale_token' => $bsaleResult['token'] ?? null,
                ]);

                $this->logInfo('PaymentConfirmationService: Boleta Bsale generada exitosamente', [
                    'order_detail_id' => $orderDetail->id,
                    'payment_id' => $payment->id,
                    'bsale_document_id' => $bsaleResult['id'] ?? null,
                    'bsale_number' => $bsaleResult['number'] ?? null,
                ]);
            }
        } catch (\Exception $e) {
            $this->logError('PaymentConfirmationService: Error generando boleta Bsale', [
                'order_detail_id' => $orderDetail->id,
                'payment_id' => $payment->id,
                'error' => $e->getMessage(),
            ], $e);
            // No lanzar excepción para no interrumpir el flujo de pago
        }
    }

    /**
     * Registrar email de confirmación de pago para envío diferido
     *
     * NOTA: El email NO se envía inmediatamente. El comando payments:send-pending-emails
     * se encarga de enviar los emails después de un delay configurable (default 10 min).
     * Esto permite que BSale genere la boleta antes de enviar el email al cliente.
     */
    private function sendSuccessEmail(OrderDetail $orderDetail, Payment $payment): void
    {
        try {
            // Recargar el pago desde la base de datos para obtener el estado más reciente
            $payment->refresh();

            // Verificar si ya se envió un email para este pago
            if ($payment->email_sent) {
                $this->logInfo('PaymentConfirmationService: Email ya enviado anteriormente, omitiendo registro', [
                    'order_detail_id' => $orderDetail->id,
                    'payment_id' => $payment->id,
                    'customer_email' => $orderDetail->email,
                ]);
                return;
            }

            // Verificación adicional: solo registrar email si el pago está realmente completado
            if ($payment->status !== 'completed') {
                $this->logWarning('PaymentConfirmationService: No se registra email - pago no está completado', [
                    'order_detail_id' => $orderDetail->id,
                    'payment_id' => $payment->id,
                    'payment_status' => $payment->status,
                ]);
                return;
            }

            // Verificar que el order detail esté pagado
            if (!$orderDetail->is_paid || $orderDetail->status !== 'paid') {
                $this->logWarning('PaymentConfirmationService: No se registra email - order detail no está pagado', [
                    'order_detail_id' => $orderDetail->id,
                    'payment_id' => $payment->id,
                    'order_detail_status' => $orderDetail->status,
                    'order_detail_is_paid' => $orderDetail->is_paid,
                ]);
                return;
            }

            // NO enviar email inmediatamente - dejar email_sent = false
            // El comando payments:send-pending-emails enviará el email después del delay configurado
            // Esto permite que BSale genere la boleta antes de enviar el correo al cliente
            $delayMinutes = config('lat90.payment.email_delay_minutes', 10);

            $this->logInfo('PaymentConfirmationService: Email registrado para envío diferido', [
                'order_detail_id' => $orderDetail->id,
                'payment_id' => $payment->id,
                'customer_email' => $orderDetail->email,
                'delay_minutes' => $delayMinutes,
                'scheduled_send_at' => now()->addMinutes($delayMinutes)->toDateTimeString(),
            ]);

        } catch (\Exception $e) {
            $this->logError('PaymentConfirmationService: Excepción al registrar email', [
                'order_detail_id' => $orderDetail->id,
                'payment_id' => $payment->id,
                'customer_email' => $orderDetail->email,
                'error' => $e->getMessage(),
            ], $e);
        }
    }

    /**
     * Parsear la fecha de transacción de Khipu para que sea compatible con MySQL
     */
    private function parseTransactionDate(string $dateString): string
    {
        // Khipu devuelve fechas en formato ISO 8601, por ejemplo: "2023-10-27T10:00:00Z"
        // MySQL espera un formato como "YYYY-MM-DD HH:MM:SS"
        // Para simplificar, podemos extraer la fecha y hora, y formatearla
        $date = \Carbon\Carbon::parse($dateString);
        return $date->format('Y-m-d H:i:s');
    }

    /**
     * Normalizar el tipo de tarjeta/método de pago para almacenamiento consistente
     *
     * @param string $paymentType Tipo de pago del gateway
     * @return string Tipo normalizado
     */
    private function normalizeCardType(string $paymentType): string
    {
        $typeMap = [
            // Khipu / Transferencia bancaria
            'khipu' => 'KHIPU',
            'transferencia' => 'KHIPU',
            'transfer' => 'KHIPU',
            // Transbank códigos
            'VD' => 'DEBIT',       // Venta Débito
            'VN' => 'CREDIT',      // Venta Normal (crédito sin cuotas)
            'VC' => 'CREDIT',      // Venta en Cuotas
            'SI' => 'CREDIT',      // Sin Interés
            'S2' => 'CREDIT',      // Sin Interés 2 cuotas
            'NC' => 'CREDIT',      // N Cuotas
            'VP' => 'PREPAID',     // Venta Prepago
            // Nombres descriptivos
            'debit' => 'DEBIT',
            'credit' => 'CREDIT',
            'prepaid' => 'PREPAID',
            'internacional' => 'INTERNATIONAL',
            'international' => 'INTERNATIONAL',
        ];

        $lowerType = strtolower($paymentType);
        return $typeMap[$lowerType] ?? $typeMap[$paymentType] ?? strtoupper($paymentType);
    }
}
