<?php

namespace App\Services\Client;

use App\Models\OrderDetail;
use App\Traits\SystemLogging;
use Illuminate\Http\Request;

class GetSpinnerDataService
{
    use SystemLogging;
    /**
     * Obtener datos para la vista de spinner de pago
     *
     * @param Request $request
     * @param int $orderDetailId
     * @return array
     */
    public function execute(Request $request, int $orderDetailId): array
    {
        $rut = session('current_rut');
        
        // Determinar tipo de pasarela basado en parámetros
        $gatewayType = $this->determineGatewayType($request);
        $gatewayData = $this->extractGatewayData($request, $gatewayType);
        
        $this->logInfo('GetSpinnerDataService: showSpinner', [
            'order_detail_id' => $orderDetailId,
            'query_params' => $request->query(),
            'gateway_detected' => $gatewayType,
            'gateway_data' => $gatewayData,
            'rut_in_session' => $rut,
        ]);

        // Obtener el programId y session_id desde el orderDetail
        $orderDetail = OrderDetail::find($orderDetailId);
        $programId = $orderDetail ? $orderDetail->order->program_id : 1;
        $sessionId = $orderDetail ? $orderDetail->order->session_id : null;
        
        return [
            'orderDetailId' => $orderDetailId,
            'gatewayType' => $gatewayType,
            'gatewayData' => $gatewayData,
            'rut' => $rut,
            'programId' => $programId,
            'sessionId' => $sessionId,
        ];
    }

    /**
     * Determinar tipo de pasarela basado en parámetros de la request
     *
     * @param Request $request
     * @return string
     */
    private function determineGatewayType(Request $request): string
    {
        // Prioridad 1: Parámetro explícito gateway
        $gateway = $request->query('gateway');
        if ($gateway && in_array($gateway, ['transbank', 'khipu'])) {
            return $gateway;
        }

        // Prioridad 2: Detección por parámetros específicos (fallback)
        if ($request->query('token_ws')) {
            return 'transbank';
        }

        if ($request->query('payment_id')) {
            return 'khipu';
        }

        // Prioridad 3: Detección por URL path (legacy)
        $path = $request->path();
        if (str_contains($path, 'khipu')) {
            return 'khipu';
        }
        if (str_contains($path, 'transbank') || str_contains($path, 'webpay')) {
            return 'transbank';
        }

        return 'unknown';
    }

    /**
     * Extraer datos específicos de la pasarela
     *
     * @param Request $request
     * @param string $gatewayType
     * @return array
     */
    private function extractGatewayData(Request $request, string $gatewayType): array
    {
        switch ($gatewayType) {
            case 'transbank':
                return [
                    'token_ws' => $request->query('token_ws'),
                ];
            
            case 'khipu':
                return [
                    'payment_id' => $request->query('payment_id'),
                ];
            
            default:
                return [];
        }
    }
}
