<?php

namespace App\Services\Client\PaymentProcessing;

use App\Models\OrderDetail;
use App\Services\Client\PaymentGateway\VirtualPosService;
use App\Services\Client\PaymentGateway\KhipuService;
use App\Traits\SystemLogging;

class CreateGatewayTransactionService
{
    use SystemLogging;

    public function __construct(
        private VirtualPosService $virtualPosService,
        private KhipuService $khipuService
    ) {}

    /**
     * Crear transacción en el gateway
     *
     * @param OrderDetail $orderDetail
     * @param array $paymentData
     * @return array
     */
    public function execute(OrderDetail $orderDetail, array $paymentData): array
    {
        $amount = $orderDetail->amount;
        // Usar el número de orden completo de la relación Order
        $orderId = $orderDetail->order->order_number ?? ($orderDetail->order_id . '-' . $orderDetail->installment_number);

        // Log para verificar el estado del OrderDetail antes de crear la transacción
        $this->logInfo('Creating gateway transaction', [
            'order_detail_id' => $orderDetail->id,
            'installment_number' => $orderDetail->installment_number,
            'current_payment_gateway_id' => $orderDetail->payment_gateway_id,
            'payment_method' => $paymentData['paymentMethod'] ?? 'unknown',
            'amount' => $amount,
            'order_id' => $orderId
        ]);

        // URLs de retorno con parámetro gateway explícito
        $virtualPosCallbackUrl = route('payment.callback', ['orderDetailId' => $orderDetail->id, 'gateway' => 'virtualpos']);
        $virtualPosNotificationUrl = route('api.payment.notification.virtualpos');
        $khipuCallbackUrl = route('payment.callback', ['orderDetailId' => $orderDetail->id, 'gateway' => 'khipu']);
        $failureUrl = route('payment.failure', ['orderDetailId' => $orderDetail->id]);

        $method = (string) ($paymentData['paymentMethod'] ?? '');
        $normalizedMethod = $method;
        $installmentsOverride = null;

        if ($method !== '') {
            // VirtualPOS: debit_credit_0, debit_credit_3, debit_credit_6, debit_credit_9, debit_credit_12
            if (stripos($method, 'debit_credit') !== false) {
                $normalizedMethod = 'credit';
                if (preg_match('/(\d+)/', $method, $m)) {
                    $n = (int) $m[1];
                    if ($n > 0) {
                        $installmentsOverride = $n;
                    }
                }
                if ($installmentsOverride === null) {
                    $installmentsOverride = 1;
                }
            }
            // Legacy Transbank: credit_0, credit_3, etc.
            else if (stripos($method, 'credit') !== false) {
                $normalizedMethod = 'credit';
                if (preg_match('/(\d+)/', $method, $m)) {
                    $n = (int) $m[1];
                    if ($n > 0) {
                        $installmentsOverride = $n;
                    }
                }
                if ($installmentsOverride === null) {
                    $installmentsOverride = 1;
                }
            }
        }

        $this->logInfo('createGatewayTransaction normalized method', [
            'original' => $method,
            'normalized' => $normalizedMethod,
            'installmentsOverride' => $installmentsOverride,
        ]);

        switch ($normalizedMethod) {
            case 'debit':
            case 'credit':
                $paymentType = $paymentData['paymentType'] ?? null;
                $installments = $installmentsOverride ?? ($paymentData['installments'] ?? null);

                // Obtener datos del cliente desde el OrderDetail
                $customerEmail = $orderDetail->email;
                $customerDocument = $orderDetail->document_number;
                $customerName = $orderDetail->name;
                $customerPhone = $orderDetail->phone;

                return $this->virtualPosService->createTransaction(
                    $orderId,
                    $amount,
                    $virtualPosCallbackUrl,
                    $virtualPosNotificationUrl, // notification URL
                    $paymentType,
                    $installments,
                    $customerEmail,
                    $customerDocument,
                    $customerName,
                    $customerPhone
                );

            case 'khipu':
                // Para Khipu, necesitamos crear la transacción primero para obtener el payment_id
                $khipuResult = $this->khipuService->createTransaction($orderId, $amount, $khipuCallbackUrl, null);

                if ($khipuResult['success']) {
                    // Agregar el payment_id a la URL de retorno
                    $paymentId = $khipuResult['payment_id'] ?? null;
                    if ($paymentId) {
                        $khipuCallbackUrlWithPaymentId = $khipuCallbackUrl . '&payment_id=' . urlencode($paymentId);
                        // Actualizar el resultado con la URL corregida
                        $khipuResult['return_url'] = $khipuCallbackUrlWithPaymentId;
                    }
                }

                return $khipuResult;

            default:
                return [
                    'success' => false,
                    'error' => 'Método de pago no soportado'
                ];
        }
    }
}
