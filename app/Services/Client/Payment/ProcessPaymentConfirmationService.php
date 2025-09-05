<?php

namespace App\Services\Client\Payment;

use App\Models\Payment;
use App\Services\Client\PaymentGateway\PaymentConfirmationService;
use App\Traits\SystemLogging;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

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
        try {
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
                'use_virtualpos_flag' => config('lat90.payment.use_virtualpos', true),
            ]);

            return $this->paymentConfirmationService->confirmPayment($orderDetailId, $gatewayType, $gatewayData, $sessionId);
        } catch (\Exception $e) {
            Log::error('ProcessPaymentConfirmationService: Error processing payment confirmation', [
                'error' => $e->getMessage(),
                'order_detail_id' => $request->input('orderDetailId'),
                'gateway_type' => $request->input('gatewayType'),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'message' => 'Error interno del servidor: ' . $e->getMessage(),
                'status' => 'error',
            ];
        }
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
        
        $this->logInfo('ProcessPaymentConfirmationService: buildGatewayData', [
            'gateway_type' => $gatewayType,
            'order_detail_id' => $orderDetailId,
            'request_inputs' => $request->all(),
        ]);
        
        if ($gatewayType === 'transbank') {
            // Para Transbank, necesitamos el token (puede venir como 'token' o 'token_ws')
            $token = $request->input('token') ?? $request->input('token_ws');
            
            $this->logInfo('ProcessPaymentConfirmationService: Transbank token search', [
                'gateway_type' => $gatewayType,
                'order_detail_id' => $orderDetailId,
                'token_from_request' => $token,
            ]);
            
            if ($token) {
                $gatewayData['token'] = $token;
                $this->logInfo('ProcessPaymentConfirmationService: Found token in request', [
                    'token' => $token,
                    'gateway_type' => $gatewayType,
                ]);
            } else {
                Log::error('ProcessPaymentConfirmationService: No token found in request for Transbank', [
                    'order_detail_id' => $orderDetailId,
                    'gateway_type' => $gatewayType,
                ]);
            }
        } elseif ($gatewayType === 'virtualpos') {
            // Para VirtualPOS, necesitamos el payment_id
            $paymentId = $request->input('payment_id');
            if ($paymentId) {
                $gatewayData['payment_id'] = $paymentId;
                $this->logInfo('ProcessPaymentConfirmationService: Found payment_id in request', [
                    'payment_id' => $paymentId,
                    'gateway_type' => $gatewayType,
                ]);
            }
        } elseif ($gatewayType === 'khipu') {
            // Para Khipu, necesitamos el payment_id
            $paymentId = $request->input('payment_id');
            if ($paymentId) {
                $gatewayData['payment_id'] = $paymentId;
                $this->logInfo('ProcessPaymentConfirmationService: Found payment_id in request', [
                    'payment_id' => $paymentId,
                    'gateway_type' => $gatewayType,
                ]);
            }
        }

        // Si no hay datos en el request, intentar obtenerlos de la base de datos
        if (empty($gatewayData)) {
            $this->logInfo('ProcessPaymentConfirmationService: No gateway data in request, checking database', [
                'gateway_type' => $gatewayType,
                'order_detail_id' => $orderDetailId,
            ]);
            
            $lastPayment = Payment::where('order_detail_id', $orderDetailId)
                ->latest()
                ->first();
            
            if ($lastPayment) {
                $this->logInfo('ProcessPaymentConfirmationService: Found payment in database', [
                    'payment_id' => $lastPayment->id,
                    'token' => $lastPayment->token,
                    'external_payment_id' => $lastPayment->external_payment_id,
                    'gateway_type' => $gatewayType,
                ]);
                
                if ($gatewayType === 'transbank' && $lastPayment->token) {
                    $gatewayData['token'] = $lastPayment->token;
                    $this->logInfo('ProcessPaymentConfirmationService: Retrieved token from database', [
                        'order_detail_id' => $orderDetailId,
                        'token' => $gatewayData['token'],
                    ]);
                } elseif (($gatewayType === 'khipu' || $gatewayType === 'virtualpos') && $lastPayment->external_payment_id) {
                    $gatewayData['payment_id'] = $lastPayment->external_payment_id;
                    $this->logInfo('ProcessPaymentConfirmationService: Retrieved payment_id from database', [
                        'order_detail_id' => $orderDetailId,
                        'payment_id' => $gatewayData['payment_id'],
                    ]);
                }
            } else {
                Log::error('ProcessPaymentConfirmationService: No payment found in database', [
                    'order_detail_id' => $orderDetailId,
                    'gateway_type' => $gatewayType,
                ]);
            }
        }

        $this->logInfo('ProcessPaymentConfirmationService: Final gateway data', [
            'gateway_type' => $gatewayType,
            'gateway_data' => $gatewayData,
        ]);

        return $gatewayData;
    }
}
