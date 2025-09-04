<?php

namespace App\Services\Client\PaymentProcessing;

use App\Models\OrderDetail;
use App\Services\Client\PaymentGateway\VirtualPosService;
use App\Services\Client\PaymentGateway\TransbankService;
use App\Services\Client\PaymentGateway\KhipuService;
use App\Traits\SystemLogging;
use Illuminate\Support\Facades\Log;

class CreateGatewayTransactionService
{
    use SystemLogging;

    public function __construct(
        private VirtualPosService $virtualPosService,
        private TransbankService $transbankService,
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
            'order_id' => $orderId,
            'use_virtualpos' => config('lat90.payment.use_virtualpos', true)
        ]);

        // URLs de retorno con parámetro gateway explícito
        $virtualPosCallbackUrl = route('payment.callback', ['orderDetailId' => $orderDetail->id, 'gateway' => 'virtualpos']);
        $virtualPosNotificationUrl = route('api.payment.notification.virtualpos');
        $transbankCallbackUrl = route('payment.callback', ['orderDetailId' => $orderDetail->id, 'gateway' => 'transbank']);
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
            // Pago internacional
            else if (stripos($method, 'international') !== false) {
                $normalizedMethod = 'international';
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
            'use_virtualpos' => config('lat90.payment.use_virtualpos', true)
        ]);

        switch ($normalizedMethod) {
            case 'debit':
            case 'credit':
            case 'international':
                $paymentType = $paymentData['paymentType'] ?? null;
                $installments = $installmentsOverride ?? ($paymentData['installments'] ?? null);

                // Obtener datos del cliente desde el OrderDetail
                $customerEmail = $orderDetail->email;
                $customerDocument = $orderDetail->document_number;
                $customerName = $orderDetail->name;
                $customerPhone = $orderDetail->phone;

                // LÓGICA DINÁMICA: Decidir qué servicio usar según el flag de configuración
                $useVirtualPos = config('lat90.payment.use_virtualpos', true);
                
                // LOG PRINCIPAL PARA VERIFICAR QUÉ GATEWAY SE ESTÁ USANDO
                Log::info('🚀 GATEWAY SELECTION - Config value: ' . ($useVirtualPos ? 'true' : 'false'), [
                    'gateway_selected' => $useVirtualPos ? 'VIRTUALPOS (PRODUCCIÓN)' : 'TRANSBANK (PRUEBAS)',
                    'order_detail_id' => $orderDetail->id,
                    'payment_method' => $normalizedMethod,
                    'installments' => $installments,
                    'config_file' => 'config/lat90.php',
                    'config_key' => 'lat90.payment.use_virtualpos'
                ]);
                
                if ($useVirtualPos) {
                    $this->logInfo('Using VirtualPOS for payment (PRODUCCIÓN)', [
                        'order_detail_id' => $orderDetail->id,
                        'payment_method' => $normalizedMethod,
                        'installments' => $installments
                    ]);
                    
                    Log::info('🟢 VIRTUALPOS SELECTED - Using VirtualPOS for payment (PRODUCCIÓN)', [
                        'order_detail_id' => $orderDetail->id,
                        'payment_method' => $normalizedMethod,
                        'installments' => $installments,
                        'gateway' => 'VIRTUALPOS'
                    ]);
                    
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
                } else {
                    Log::info('💳 TRANSBANK SELECTED - Using Transbank for payment (PRUEBAS)', [
                        'order_detail_id' => $orderDetail->id,
                        'payment_method' => $normalizedMethod,
                        'installments' => $installments,
                        'gateway' => 'TRANSBANK'
                    ]);
                    
                    $this->logInfo('Using Transbank for payment (PRUEBAS)', [
                        'order_detail_id' => $orderDetail->id,
                        'payment_method' => $normalizedMethod,
                        'installments' => $installments
                    ]);
                    
                    return $this->transbankService->createTransaction(
                        $orderId,
                        $amount,
                        $transbankCallbackUrl,
                        null, // Transbank no usa notification URL
                        $paymentType,
                        $installments
                    );
                }

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
