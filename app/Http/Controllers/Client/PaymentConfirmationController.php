<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Services\Client\PaymentGateway\PaymentConfirmationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class PaymentConfirmationController extends Controller
{
    private $paymentConfirmationService;

    public function __construct(PaymentConfirmationService $paymentConfirmationService)
    {
        $this->paymentConfirmationService = $paymentConfirmationService;
    }

    /**
     * Vista de spinner unificada para todas las pasarelas
     */
    public function showSpinner(Request $request, $orderDetailId)
    {
        $rut = session('current_rut');
        
        // Determinar tipo de pasarela basado en parámetros
        $gatewayType = $this->determineGatewayType($request);
        $gatewayData = $this->extractGatewayData($request, $gatewayType);
        
        Log::info('PaymentConfirmationController: showSpinner', [
            'order_detail_id' => (int) $orderDetailId,
            'query_params' => $request->query(),
            'gateway_detected' => $gatewayType,
            'gateway_data' => $gatewayData,
            'rut_in_session' => $rut,
        ]);

        // Obtener el programId y session_id desde el orderDetail
        $orderDetail = \App\Models\OrderDetail::find($orderDetailId);
        $programId = $orderDetail ? $orderDetail->order->program_id : 1;
        $sessionId = $orderDetail ? $orderDetail->order->session_id : null;
        
        return Inertia::render('Payment/PaymentSpinner', [
            'orderDetailId' => (int) $orderDetailId,
            'gatewayType' => $gatewayType,
            'gatewayData' => $gatewayData,
            'rut' => $rut,
            'programId' => $programId,
            'sessionId' => $sessionId,
        ]);
    }

    /**
     * Confirmar pago unificado
     */
    public function confirmPayment(Request $request)
    {
        $request->validate([
            'orderDetailId' => 'required|integer',
            'gatewayType' => 'required|string|in:transbank,khipu',
        ]);

        $orderDetailId = (int) $request->input('orderDetailId');
        $gatewayType = $request->input('gatewayType');
        
        // Construir gatewayData desde los campos individuales enviados por el frontend
        $gatewayData = [];
        
        if ($gatewayType === 'transbank') {
            $tokenWs = $request->input('token_ws');
            if ($tokenWs) {
                $gatewayData['token_ws'] = $tokenWs;
            }
        } elseif ($gatewayType === 'khipu') {
            $paymentId = $request->input('payment_id');
            if ($paymentId) {
                $gatewayData['payment_id'] = $paymentId;
            }
        }

        // Para Khipu, si no hay payment_id en los datos, intentar obtenerlo de la base de datos
        if ($gatewayType === 'khipu' && empty($gatewayData['payment_id'])) {
            $lastPayment = \App\Models\Payment::where('order_detail_id', $orderDetailId)
                ->whereNotNull('external_payment_id')
                ->latest()
                ->first();
            
            if ($lastPayment && $lastPayment->external_payment_id) {
                $gatewayData['payment_id'] = $lastPayment->external_payment_id;
                Log::info('PaymentConfirmationController: Retrieved payment_id from database', [
                    'order_detail_id' => $orderDetailId,
                    'payment_id' => $gatewayData['payment_id'],
                ]);
            }
        }

        Log::info('PaymentConfirmationController: confirmPayment', [
            'order_detail_id' => $orderDetailId,
            'gateway_type' => $gatewayType,
            'gateway_data' => $gatewayData,
            'all_request_data' => $request->all(),
        ]);

        // Obtener session_id del request
        $sessionId = $request->input('session_id');

        $result = $this->paymentConfirmationService->confirmPayment($orderDetailId, $gatewayType, $gatewayData, $sessionId);

        return response()->json($result);
    }

    /**
     * Vista de éxito unificada
     */
    public function showSuccess(Request $request, $orderDetailId)
    {
        try {
            $paymentData = $this->paymentConfirmationService->getPaymentData($orderDetailId);
            
            // Preservar RUT en sesión
            if ($paymentData['order_detail']->document_number) {
                session(['current_rut' => preg_replace('/[.-]/', '', $paymentData['order_detail']->document_number)]);
            }

            return Inertia::render('Payment/PaymentSuccess', [
                'paymentData' => $paymentData,
                'rut' => session('current_rut'),
            ]);
        } catch (\Exception $e) {
            Log::error('PaymentConfirmationController: Error showing success', [
                'error' => $e->getMessage(),
                'order_detail_id' => $orderDetailId,
            ]);
            
            return redirect()->route('payment.failure', $orderDetailId)
                ->with('error', 'Error al mostrar la confirmación del pago.');
        }
    }

    /**
     * Vista de error unificada
     */
    public function showFailure(Request $request, $orderDetailId)
    {
        try {
            $paymentData = $this->paymentConfirmationService->getPaymentData($orderDetailId);
            $errorMessage = $request->session()->get('error', 'El pago no pudo ser procesado correctamente.');
            
            // Preservar RUT en sesión
            if ($paymentData['order_detail']->document_number) {
                session(['current_rut' => preg_replace('/[.-]/', '', $paymentData['order_detail']->document_number)]);
            }

            return Inertia::render('Payment/PaymentFailure', [
                'paymentData' => $paymentData,
                'errorMessage' => $errorMessage,
                'rut' => session('current_rut'),
            ]);
        } catch (\Exception $e) {
            Log::error('PaymentConfirmationController: Error showing failure', [
                'error' => $e->getMessage(),
                'order_detail_id' => $orderDetailId,
            ]);
            
            return Inertia::render('Payment/PaymentFailure', [
                'paymentData' => null,
                'errorMessage' => 'Error al procesar el resultado del pago.',
                'rut' => session('current_rut'),
            ]);
        }
    }

    /**
     * Determinar tipo de pasarela basado en parámetros de la request
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
