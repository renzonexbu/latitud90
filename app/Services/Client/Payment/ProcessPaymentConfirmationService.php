<?php

namespace App\Services\Client\Payment;

use App\Models\Payment;
use App\Services\Client\PaymentGateway\PaymentConfirmationService;
use App\Traits\SystemLogging;
use Illuminate\Http\Request;

class ProcessPaymentConfirmationService
{
    use SystemLogging;
    public function __construct(
        private PaymentConfirmationService $paymentConfirmationService
    ) {}

    /**
     * Procesar la confirmación de pago
     *
     * @param Request $request
     * @return array
     */
    public function execute(Request $request): array
    {
        $request->validate([
            'orderDetailId' => 'required|integer',
            'gatewayType' => 'required|string|in:transbank,virtualpos,khipu',
        ]);

        $orderDetailId = (int) $request->input('orderDetailId');
        $gatewayType = $request->input('gatewayType');
        
        // Construir gatewayData desde los campos individuales enviados por el frontend
        $gatewayData = $this->buildGatewayData($request, $gatewayType, $orderDetailId);

        // Obtener session_id del request
        $sessionId = $request->input('session_id');

        $this->logInfo('ProcessPaymentConfirmationService: confirmPayment', [
            'order_detail_id' => $orderDetailId,
            'gateway_type' => $gatewayType,
            'gateway_data' => $gatewayData,
            'all_request_data' => $request->all(),
        ]);

        return $this->paymentConfirmationService->confirmPayment($orderDetailId, $gatewayType, $gatewayData, $sessionId);
    }

    /**
     * Construir datos de la pasarela desde la request
     *
     * @param Request $request
     * @param string $gatewayType
     * @param int $orderDetailId
     * @return array
     */
    private function buildGatewayData(Request $request, string $gatewayType, int $orderDetailId): array
    {
        $gatewayData = [];
        
        if ($gatewayType === 'transbank' || $gatewayType === 'virtualpos') {
            $paymentId = $request->input('payment_id');
            if ($paymentId) {
                $gatewayData['payment_id'] = $paymentId;
            }
        } elseif ($gatewayType === 'khipu') {
            $paymentId = $request->input('payment_id');
            if ($paymentId) {
                $gatewayData['payment_id'] = $paymentId;
            }
        }

        // Para Khipu y VirtualPOS, si no hay payment_id en los datos, intentar obtenerlo de la base de datos
        if (($gatewayType === 'khipu' || $gatewayType === 'virtualpos') && empty($gatewayData['payment_id'])) {
            $lastPayment = Payment::where('order_detail_id', $orderDetailId)
                ->whereNotNull('external_payment_id')
                ->latest()
                ->first();
            
            if ($lastPayment && $lastPayment->external_payment_id) {
                $gatewayData['payment_id'] = $lastPayment->external_payment_id;
                $this->logInfo('ProcessPaymentConfirmationService: Retrieved payment_id from database', [
                    'order_detail_id' => $orderDetailId,
                    'payment_id' => $gatewayData['payment_id'],
                ]);
            }
        }

        return $gatewayData;
    }
}
