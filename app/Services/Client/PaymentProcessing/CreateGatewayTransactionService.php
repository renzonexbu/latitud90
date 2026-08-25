<?php

namespace App\Services\Client\PaymentProcessing;

use App\Models\OrderDetail;
use App\Services\Client\PaymentGateway\VirtualPosService;
use App\Services\Client\PaymentGateway\TransbankService;
use App\Services\Client\PaymentGateway\KhipuService;
use App\Traits\SystemLogging;

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
     * @param array $formData Datos del formulario con nombres/apellidos separados
     * @return array
     */
    public function execute(OrderDetail $orderDetail, array $paymentData, array $formData = []): array
    {
        $amount = $orderDetail->amount;
        // Usar el número de orden completo de la relación Order
        $orderId = $orderDetail->order->order_number ?? ($orderDetail->order_id . '-' . $orderDetail->installment_number);

        // Extraer nombres y apellidos separados del formData (provienen del frontend)
        $buyerNombres = $formData['nombres'] ?? null;
        $buyerApellidos = $formData['apellidos'] ?? null;

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

        $this->logInfo('=== CreateGatewayTransactionService: NORMALIZED METHOD ===', [
            'original_method' => $method,
            'normalized_method' => $normalizedMethod,
            'installmentsOverride' => $installmentsOverride,
            'will_use_case' => $normalizedMethod === 'khipu' ? 'KHIPU' : ($normalizedMethod === 'credit' || $normalizedMethod === 'debit' || $normalizedMethod === 'international' ? 'VIRTUALPOS/TRANSBANK' : 'DEFAULT/UNSUPPORTED'),
        ]);

        \Log::info('=== CREATE GATEWAY: Normalized method ===', [
            'original' => $method,
            'normalized' => $normalizedMethod,
        ]);

        switch ($normalizedMethod) {
            case 'debit':
            case 'credit':
            case 'international':
                // paymentType del formulario es "total/monthly"; VirtualPos necesita
                // el medio (debit/credit/international) para elegir API KEY / comercio.
                $paymentType = $normalizedMethod;
                $installments = $installmentsOverride ?? ($paymentData['installments'] ?? null);

                $this->logInfo('=== CreateGatewayTransactionService: VirtualPos config input ===', [
                    'normalized_method' => $normalizedMethod,
                    'payment_type_sent' => $paymentType,
                    'installments' => $installments,
                ]);

                // Verificar flag para usar VirtualPOS (producción) o Transbank (pruebas)
                $useVirtualPos = config('lat90.payment.use_virtualpos', true);
                
                if ($useVirtualPos) {
                    // Usar VirtualPOS (producción)
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
                        $customerPhone,
                        $buyerNombres,
                        $buyerApellidos
                    );
                } else {
                    // Usar Transbank (pruebas)
                    return $this->transbankService->createTransaction(
                        $orderId,
                        $amount,
                        $virtualPosCallbackUrl,
                        $virtualPosNotificationUrl,
                        $paymentType,
                        $installments
                    );
                }

            case 'khipu':
                \Log::info('=== CREATE GATEWAY: KHIPU case entered ===', [
                    'order_detail_id' => $orderDetail->id,
                    'amount' => $amount,
                    'is_production' => $this->khipuService->isProductionMode(),
                ]);

                $this->logInfo('=== KHIPU CASE ENTERED ===', [
                    'order_detail_id' => $orderDetail->id,
                    'amount' => $amount,
                    'order_id' => $orderId,
                    'khipu_service_production_mode' => $this->khipuService->isProductionMode(),
                ]);

                // Datos del cliente para Khipu
                // Usar nombres/apellidos separados si están disponibles
                $khipuFirstName = $buyerNombres
                    ? ucwords(strtolower(trim($buyerNombres)))
                    : $this->extractFirstName($orderDetail->name ?? 'Cliente');
                $khipuLastName = $buyerApellidos
                    ? ucwords(strtolower(trim($buyerApellidos)))
                    : $this->extractLastName($orderDetail->name ?? 'Latitud90');

                $customerData = [
                    'email' => $orderDetail->email ?? 'cliente@latitud90.cl',
                    'rut' => $orderDetail->document_number ?? '',
                    'first_name' => $khipuFirstName,
                    'last_name' => $khipuLastName,
                    'description' => 'Pago programa Latitud 90 - Orden #' . $orderId,
                ];

                $this->logInfo('Khipu customer data prepared', [
                    'customer_data' => $customerData,
                    'callback_url' => $khipuCallbackUrl,
                ]);

                // Para Khipu, necesitamos crear la transacción primero para obtener el payment_id
                $khipuResult = $this->khipuService->createTransaction($orderId, $amount, $khipuCallbackUrl, null, $customerData);

                $this->logInfo('Khipu createTransaction result', [
                    'result' => $khipuResult,
                ]);

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

    /**
     * Extraer nombres (primer y segundo nombre) del nombre completo
     * Heurística chilena: 4+ partes = 2 nombres + 2 apellidos, 3 partes = 1 nombre + 2 apellidos
     */
    private function extractFirstName(string $fullName): string
    {
        $parts = array_values(array_filter(explode(' ', trim($fullName))));
        $count = count($parts);

        if ($count === 0) return 'Cliente';
        if ($count <= 3) return ucwords(strtolower($parts[0]));

        // 4+ partes: primeras 2 son nombres
        return ucwords(strtolower($parts[0] . ' ' . $parts[1]));
    }

    /**
     * Extraer apellidos del nombre completo
     * Heurística chilena: 4+ partes = 2 nombres + 2 apellidos, 3 partes = 1 nombre + 2 apellidos
     */
    private function extractLastName(string $fullName): string
    {
        $parts = array_values(array_filter(explode(' ', trim($fullName))));
        $count = count($parts);

        if ($count <= 1) return 'Latitud90';
        if ($count === 2) return ucwords(strtolower($parts[1]));
        if ($count === 3) return ucwords(strtolower($parts[1] . ' ' . $parts[2]));

        // 4+ partes: últimas partes desde la tercera son apellidos
        return ucwords(strtolower(implode(' ', array_slice($parts, 2))));
    }
}
