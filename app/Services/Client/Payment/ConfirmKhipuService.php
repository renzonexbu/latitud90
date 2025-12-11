<?php

namespace App\Services\Client\Payment;

use App\Models\OrderDetail;
use App\Models\Payment;
use App\Services\Client\PaymentGateway\KhipuService;
use App\Helpers\PaymentDocumentTypeHelper;
use App\Traits\SystemLogging;
use Illuminate\Http\Request;
use Carbon\Carbon;

class ConfirmKhipuService
{
    use SystemLogging;
    public function __construct(
        private KhipuService $khipuService
    ) {}

    /**
     * Confirmar transacción de Khipu
     *
     * @param Request $request
     * @return array
     */
    public function execute(Request $request): array
    {
        $request->validate([
            'orderDetailId' => 'required|integer',
            'payment_id' => 'nullable|string',
        ]);

        $orderDetailId = (int) $request->input('orderDetailId');
        $paymentId = $request->input('payment_id');

        try {
            // Resolver payment_id si no viene en el request
            if (empty($paymentId)) {
                $last = Payment::where('order_detail_id', $orderDetailId)
                    ->whereNotNull('external_payment_id')
                    ->latest()->first();
                $paymentId = $last?->external_payment_id;
            }

            if (empty($paymentId)) {
                $this->logWarning('ConfirmKhipuService: confirmKhipu missing payment_id', [
                    'order_detail_id' => $orderDetailId,
                ]);
                return [
                    'success' => false,
                    'status' => 'missing_payment_id'
                ];
            }

            $this->logInfo('ConfirmKhipuService: confirmKhipu start', [
                'order_detail_id' => $orderDetailId,
                'payment_id' => $paymentId,
            ]);

            // Backend polling: hasta 5 intentos con espera de 2s
            $attempts = 0;
            $maxAttempts = 5;
            $status = null;
            $approved = false;

            while ($attempts < $maxAttempts) {
                $attempts++;
                $this->logInfo('ConfirmKhipuService: confirmKhipu attempt', [
                    'attempt' => $attempts,
                    'order_detail_id' => $orderDetailId,
                    'payment_id' => $paymentId,
                ]);
                
                $status = $this->khipuService->getPaymentStatus($paymentId);

                // Buscar pago existente por order_detail_id (sin importar external_payment_id)
                $payment = Payment::where('order_detail_id', $orderDetailId)
                    ->latest()
                    ->first();

                if (!$payment) {
                    $orderDetail = OrderDetail::findOrFail($orderDetailId);
                    $payment = Payment::create([
                        'order_id' => $orderDetail->order_id,
                        'order_detail_id' => $orderDetail->id,
                        'payment_gateway_id' => $orderDetail->payment_gateway_id,
                        'payment_option_id' => $orderDetail->payment_option_id,
                        'amount' => $orderDetail->amount,
                        'currency' => 'CLP',
                        'status' => 'pending',
                        'buy_order' => $orderDetail->order_id . '-' . $orderDetail->installment_number,
                        'external_payment_id' => $paymentId,
                        'transaction_date' => now('America/Santiago'),
                        'document_type' => PaymentDocumentTypeHelper::determineDocumentType($orderDetail->order->program_id),
                    ]);
                } else {
                    // Actualizar el external_payment_id y transaction_date si no los tienen
                    $updateFields = [];
                    if (!$payment->external_payment_id) {
                        $updateFields['external_payment_id'] = $paymentId;
                    }
                    if (!$payment->transaction_date) {
                        $updateFields['transaction_date'] = now('America/Santiago');
                    }
                    if (!empty($updateFields)) {
                        $payment->update($updateFields);
                    }
                }

                $approved = $status['success'] === true && in_array(($status['status'] ?? ''), ['done', 'paid', 'approved', 'completed']);
                $this->logInfo('ConfirmKhipuService: confirmKhipu attempt result', [
                    'attempt' => $attempts,
                    'approved' => $approved,
                    'status' => $status['status'] ?? null,
                ]);

                $updateData = [
                    'status' => $approved ? 'approved' : ($status['status'] ?? 'pending'),
                    'gateway_response' => $status['data'] ?? $status,
                    'raw_notification' => $status['data'] ?? $status,
                ];

                // Agregar transaction_date si el pago es aprobado y viene en la respuesta
                if ($approved && isset($status['transaction_date'])) {
                    $updateData['transaction_date'] = $this->parseTransactionDate($status['transaction_date']);
                } elseif ($approved && !$payment->transaction_date) {
                    // Si no hay transaction_date pero el pago es aprobado, usar la fecha actual
                    $updateData['transaction_date'] = now('America/Santiago');
                }

                // Mapear campos adicionales según el gateway (VirtualPos vs Khipu nativo)
                $updateData = $this->mapGatewaySpecificFields($updateData, $status, $approved);
                
                $payment->update($updateData);

                $orderDetail = $payment->orderDetail;
                if ($orderDetail) {
                    $orderDetail->update([
                        'is_paid' => $approved,
                        'status' => $approved ? 'paid' : 'pending',
                        'paid_at' => $approved ? now() : $orderDetail->paid_at,
                        'transaction_id' => $paymentId,
                        'gateway_response' => $status['data'] ?? $status,
                    ]);
                    
                    // Si el pago fue aprobado y es una cuota, marcarla como pagada
                    if ($approved && $orderDetail->installment_number >= 1) {
                        $this->markInstallmentAsPaid($orderDetail);
                    }
                    
                    // Recalcular estado de la orden
                    if ($orderDetail->order) {
                        $orderDetail->order->refreshStatus();
                    }
                }

                if ($approved) {
                    $this->logInfo('ConfirmKhipuService: confirmKhipu approved', [
                        'order_detail_id' => $orderDetailId,
                        'payment_id' => $paymentId,
                        'attempt' => $attempts,
                    ]);
                    return [
                        'success' => true,
                        'redirect' => route('payment.success', $orderDetailId),
                        'status' => $status['status'] ?? 'done',
                    ];
                }

                // Esperar antes del siguiente intento
                sleep(2);
            }

            // No aprobado tras reintentos: redirigir a vista informativa (verificación)
            $this->logWarning('ConfirmKhipuService: confirmKhipu exhausted attempts without approval', [
                'order_detail_id' => $orderDetailId,
                'payment_id' => $paymentId,
                'attempts' => $attempts,
                'last_status' => $status['status'] ?? null,
            ]);
            
            return [
                'success' => false,
                'redirect' => route('khipu.callback', ['orderDetailId' => $orderDetailId]),
                'status' => $status['status'] ?? 'pending',
            ];
        } catch (\Throwable $e) {
            $this->logError('ConfirmKhipuService: Error confirming Khipu transaction', [
                'error' => $e->getMessage(),
                'order_detail_id' => $orderDetailId,
                'payment_id' => $paymentId,
            ], $e);
            
            return [
                'success' => false,
                'redirect' => route('payment.failure', $orderDetailId),
                'message' => 'No se pudo verificar la transacción en Khipu.'
            ];
        }
    }

    /**
     * Marcar una cuota como pagada cuando se confirma el pago
     *
     * @param OrderDetail $orderDetail
     * @return void
     */
    private function markInstallmentAsPaid(OrderDetail $orderDetail): void
    {
        try {
            // Buscar la cuota correspondiente usando el installment_number
            $installment = \App\Models\Installment::where('installment_number', $orderDetail->installment_number)
                ->whereHas('installmentPlan', function($query) use ($orderDetail) {
                    $query->where('participant_id', $orderDetail->order->participant_id)
                          ->where('program_id', $orderDetail->order->program_id);
                })
                ->first();

            if ($installment) {
                $installment->markAsPaid(
                    $orderDetail->order_id,
                    $orderDetail->id,
                    $orderDetail->payments()->latest()->first()->id
                );

                $this->logInfo('ConfirmKhipuService: Installment marked as paid after payment confirmation', [
                    'installment_id' => $installment->id,
                    'installment_number' => $installment->installment_number,
                    'order_id' => $orderDetail->order_id,
                    'order_detail_id' => $orderDetail->id,
                    'payment_id' => $orderDetail->payments()->latest()->first()->id
                ]);
            } else {
                $this->logWarning('ConfirmKhipuService: Installment not found for marking as paid', [
                    'installment_number' => $orderDetail->installment_number,
                    'participant_id' => $orderDetail->order->participant_id,
                    'program_id' => $orderDetail->order->program_id
                ]);
            }
        } catch (\Exception $e) {
            $this->logError('ConfirmKhipuService: Error marking installment as paid', [
                'error' => $e->getMessage(),
                'order_detail_id' => $orderDetail->id,
                'installment_number' => $orderDetail->installment_number
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
     * Mapear campos específicos del gateway a los campos del modelo Payment
     *
     * VirtualPos (producción) devuelve campos diferentes a Khipu nativo (QA/test)
     *
     * @param array $updateData Datos base a actualizar
     * @param array $status Respuesta del gateway
     * @param bool $approved Si el pago fue aprobado
     * @return array Datos actualizados con campos específicos del gateway
     */
    private function mapGatewaySpecificFields(array $updateData, array $status, bool $approved): array
    {
        $isVirtualPos = $this->khipuService->isProductionMode();
        $data = $status['data'] ?? [];

        $this->logInfo('ConfirmKhipuService: Mapping gateway-specific fields', [
            'is_virtualpos' => $isVirtualPos,
            'approved' => $approved,
            'status_keys' => array_keys($status),
            'data_keys' => array_keys($data),
        ]);

        if ($isVirtualPos) {
            // VirtualPos (producción) - Mapear campos específicos
            // Estructura: $status contiene auth_code, card_number, payment_type directamente
            // y $data contiene la respuesta completa con payment.order

            $paymentData = $data['payment'] ?? [];
            $orderData = $paymentData['order'] ?? [];

            // authorization_code: código de autorización del banco
            if (!empty($status['auth_code'])) {
                $updateData['authorization_code'] = $status['auth_code'];
            } elseif (!empty($orderData['auth_code'])) {
                $updateData['authorization_code'] = $orderData['auth_code'];
            } elseif (!empty($paymentData['auth_code'])) {
                $updateData['authorization_code'] = $paymentData['auth_code'];
            }

            // card_number: últimos 4 dígitos de la tarjeta (si aplica)
            if (!empty($status['card_number'])) {
                $updateData['card_number'] = $status['card_number'];
            } elseif (!empty($orderData['card_number'])) {
                $updateData['card_number'] = $orderData['card_number'];
            }

            // card_type: tipo de tarjeta/método de pago
            if (!empty($status['payment_type'])) {
                $updateData['card_type'] = $this->normalizeCardType($status['payment_type']);
            } elseif (!empty($orderData['payment_type_code'])) {
                $updateData['card_type'] = $this->normalizeCardType($orderData['payment_type_code']);
            } elseif (!empty($orderData['payment_method'])) {
                $updateData['card_type'] = $this->normalizeCardType($orderData['payment_method']);
            }

            // installments_number: número de cuotas (si aplica)
            if (!empty($orderData['installments'])) {
                $updateData['installments_number'] = (int) $orderData['installments'];
            } elseif (!empty($orderData['installments_number'])) {
                $updateData['installments_number'] = (int) $orderData['installments_number'];
            }

            // response_code: código de respuesta de la transacción
            if (!empty($orderData['response_code'])) {
                $updateData['response_code'] = $orderData['response_code'];
            }

            // commerce_code: código del comercio
            if (!empty($orderData['commerce_code'])) {
                $updateData['commerce_code'] = $orderData['commerce_code'];
            }

            // external_payment_id: UUID del pago en VirtualPos
            if (!empty($orderData['uuid'])) {
                $updateData['external_payment_id'] = $orderData['uuid'];
            }

            $this->logInfo('ConfirmKhipuService: VirtualPos fields mapped', [
                'authorization_code' => $updateData['authorization_code'] ?? null,
                'card_number' => $updateData['card_number'] ?? null,
                'card_type' => $updateData['card_type'] ?? null,
                'installments_number' => $updateData['installments_number'] ?? null,
            ]);

        } else {
            // Khipu nativo (QA/test) - Mapear campos específicos
            // Khipu devuelve: payment_id, status, conciliation_date, etc.

            // Para Khipu, el card_type es siempre 'khipu' (transferencia bancaria)
            if ($approved) {
                $updateData['card_type'] = 'khipu';
            }

            // bank: banco desde donde se hizo la transferencia
            if (!empty($data['bank'])) {
                // Guardar el banco en gateway_response si no hay campo específico
                $updateData['commerce_code'] = $data['bank'];
            }

            // conciliation_date: fecha de conciliación
            if (!empty($data['conciliation_date'])) {
                $updateData['accounting_date'] = $this->parseTransactionDate($data['conciliation_date']);
            }

            // payer_email: email del pagador
            if (!empty($data['payer_email'])) {
                // Este dato se guarda en gateway_response (ya incluido arriba)
            }

            $this->logInfo('ConfirmKhipuService: Khipu native fields mapped', [
                'card_type' => $updateData['card_type'] ?? null,
                'bank' => $data['bank'] ?? null,
            ]);
        }

        return $updateData;
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
            'khipu' => 'KHIPU',
            'transferencia' => 'KHIPU',
            'transfer' => 'KHIPU',
            'VD' => 'DEBIT',
            'VN' => 'CREDIT',
            'VC' => 'CREDIT',
            'SI' => 'CREDIT',
            'S2' => 'CREDIT',
            'NC' => 'CREDIT',
            'VP' => 'PREPAID',
            'debit' => 'DEBIT',
            'credit' => 'CREDIT',
            'prepaid' => 'PREPAID',
        ];

        return $typeMap[strtolower($paymentType)] ?? strtoupper($paymentType);
    }
}
