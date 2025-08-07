<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Services\Client\CreateOrderService;
use App\Services\Client\PaymentGateway\TransbankService;
use App\Services\Client\PaymentGateway\KhipuService;
use App\Models\OrderDetail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class ProcessPaymentController extends Controller
{
    protected $createOrderService;
    protected $transbankService;
    protected $khipuService;

    public function __construct(
        CreateOrderService $createOrderService,
        TransbankService $transbankService,
        KhipuService $khipuService
    ) {
        $this->createOrderService = $createOrderService;
        $this->transbankService = $transbankService;
        $this->khipuService = $khipuService;
    }

    public function processPayment(Request $request)
    {
        try {
            $request->validate([
                'programId' => 'required|integer',
                'rut' => 'required|string',
            ]);

            $programId = $request->input('programId');
            $rut = $request->input('rut');

            // Obtener datos del localStorage (enviados desde el frontend)
            $paymentData = $request->input('paymentData');
            $formData = $request->input('formData');

            if (!$paymentData || !$formData) {
                return response()->json([
                    'success' => false,
                    'error' => 'Datos de pago o formulario no encontrados'
                ], 400);
            }

            Log::info('Processing payment request', [
                'program_id' => $programId,
                'rut' => $rut,
                'payment_data' => $paymentData,
                'form_data' => $formData
            ]);

            // 1. Crear la orden y detalles
            $orderResult = $this->createOrderService->createOrder(
                $programId,
                $rut,
                $paymentData,
                $formData
            );

            if (!$orderResult['success']) {
                return response()->json([
                    'success' => false,
                    'error' => $orderResult['error']
                ], 400);
            }

            $order = $orderResult['order'];
            $orderDetail = $orderResult['order_detail'];

            // 2. Determinar el gateway de pago y crear la transacción
            $gatewayResult = $this->createGatewayTransaction($orderDetail, $paymentData);

            if (!$gatewayResult['success']) {
                return response()->json([
                    'success' => false,
                    'error' => $gatewayResult['error']
                ], 400);
            }

            Log::info('Payment gateway transaction created', [
                'order_id' => $order->id,
                'order_detail_id' => $orderDetail->id,
                'gateway' => $paymentData['paymentMethod'],
                'gateway_response' => $gatewayResult
            ]);

            return response()->json([
                'success' => true,
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'gateway_url' => $gatewayResult['url'],
                'gateway_token' => $gatewayResult['token'] ?? null,
                'gateway_type' => $paymentData['paymentMethod']
            ]);

        } catch (\Exception $e) {
            Log::error('Error processing payment', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Error interno del servidor: ' . $e->getMessage()
            ], 500);
        }
    }

    private function createGatewayTransaction($orderDetail, $paymentData)
    {
        $amount = $orderDetail->amount;
        $orderId = $orderDetail->order_id . '-' . $orderDetail->installment_number;
        
        // URLs de retorno
        $successUrl = route('payment.success', ['orderDetailId' => $orderDetail->id]);
        $failureUrl = route('payment.failure', ['orderDetailId' => $orderDetail->id]);
        
        // URLs de notificación (Webhook de prueba - TEMPORAL PARA DESARROLLO)
        // TODO: Cambiar a URLs de producción cuando esté listo
        $transbankNotificationUrl = 'https://webhook.site/a9c403a5-d11c-4617-ab9e-9354e5d4972d';
        $khipuNotificationUrl = 'https://webhook.site/a9c403a5-d11c-4617-ab9e-9354e5d4972d';

        switch ($paymentData['paymentMethod']) {
            case 'debit':
            case 'credit':
                // Determinar tipo de pago y cuotas para Transbank
                $paymentType = $paymentData['paymentType'] ?? null; // 'total' o 'monthly'
                $installments = $paymentData['installments'] ?? null;
                
                // Usar Transbank con URL de notificación y control de cuotas
                return $this->transbankService->createTransaction(
                    $orderId, 
                    $amount, 
                    $successUrl, 
                    $transbankNotificationUrl,
                    $paymentType,
                    $installments
                );

            case 'khipu':
                // Usar Khipu con URL de notificación
                return $this->khipuService->createTransaction($orderId, $amount, $successUrl, $khipuNotificationUrl);

            default:
                return [
                    'success' => false,
                    'error' => 'Método de pago no soportado'
                ];
        }
    }

    public function paymentSuccess($orderDetailId)
    {
        try {
            $orderDetail = OrderDetail::with(['order.program', 'order.participant'])->findOrFail($orderDetailId);
            
            Log::info('Payment success page accessed', [
                'order_detail_id' => $orderDetailId,
                'order_id' => $orderDetail->order_id,
                'amount' => $orderDetail->amount,
                'is_paid' => $orderDetail->is_paid,
                'status' => $orderDetail->status
            ]);

            // Verificar si el pago fue realmente procesado
            if (!$orderDetail->is_paid || $orderDetail->status !== 'paid') {
                Log::warning('Payment success page accessed but payment not confirmed', [
                    'order_detail_id' => $orderDetailId,
                    'is_paid' => $orderDetail->is_paid,
                    'status' => $orderDetail->status
                ]);

                // Redirigir al paso 4 con mensaje de error
                $programId = $orderDetail->order->program_id;
                $rut = $orderDetail->document_number;
                
                return redirect()->route('payment.confirmation', [
                    'programId' => $programId,
                    'rut' => $rut
                ])->with('error', 'El pago aún no ha sido confirmado. Por favor, espera unos minutos o contacta soporte.');
            }

            // Preparar datos para la vista de éxito
            $paymentData = [
                'order_number' => $orderDetail->order->order_number,
                'amount' => $orderDetail->amount,
                'payment_method' => $this->getPaymentMethodFromId($orderDetail->payment_method_id),
                'transaction_id' => $orderDetail->transaction_id,
                'paid_at' => $orderDetail->paid_at,
                'program' => [
                    'name' => $orderDetail->order->program->name,
                    'destination' => $orderDetail->order->program->destination,
                    'departure_date' => $orderDetail->order->program->departure_date,
                ],
                'participant_name' => $orderDetail->name,
                'participant_rut' => $orderDetail->document_number,
                'participant_email' => $orderDetail->email,
                'participant_phone' => $orderDetail->code_phone . ' ' . $orderDetail->phone,
            ];

            return Inertia::render('Ecommerce/SuccessfulPayment', [
                'paymentData' => $paymentData
            ]);

        } catch (\Exception $e) {
            Log::error('Error in payment success page', [
                'error' => $e->getMessage(),
                'order_detail_id' => $orderDetailId
            ]);

            return redirect()->route('ecommerce.index')->with('error', 'Error al procesar el pago');
        }
    }

    public function paymentFailure($orderDetailId)
    {
        try {
            $orderDetail = OrderDetail::with(['order.program', 'order.participant'])->findOrFail($orderDetailId);
            
            Log::info('Payment failure page accessed', [
                'order_detail_id' => $orderDetailId,
                'order_id' => $orderDetail->order_id,
                'amount' => $orderDetail->amount,
                'is_paid' => $orderDetail->is_paid,
                'status' => $orderDetail->status
            ]);

            // Verificar si el pago fue realmente procesado (por si acaso llegó aquí por error)
            if ($orderDetail->is_paid && $orderDetail->status === 'paid') {
                Log::warning('Payment failure page accessed but payment was actually successful', [
                    'order_detail_id' => $orderDetailId,
                    'is_paid' => $orderDetail->is_paid,
                    'status' => $orderDetail->status
                ]);

                // Redirigir a la página de éxito
                return redirect()->route('payment.success', ['orderDetailId' => $orderDetailId]);
            }

            // Redirigir al paso 4 (confirmación) con mensaje de error
            $programId = $orderDetail->order->program_id;
            $rut = $orderDetail->document_number;
            
            return redirect()->route('payment.confirmation', [
                'programId' => $programId,
                'rut' => $rut
            ])->with('error', 'El pago no pudo ser procesado. Por favor, intenta nuevamente.');

        } catch (\Exception $e) {
            Log::error('Error in payment failure page', [
                'error' => $e->getMessage(),
                'order_detail_id' => $orderDetailId
            ]);

            return redirect()->route('ecommerce.index')->with('error', 'Error al procesar el pago');
        }
    }

    private function getPaymentMethodFromId($methodId)
    {
        $methods = [
            1 => 'debit',
            2 => 'credit', 
            3 => 'khipu'
        ];
        
        return $methods[$methodId] ?? 'unknown';
    }



    /**
     * API endpoint para recibir notificaciones de Transbank
     * Esta ruta no tiene CORS y es llamada directamente por Transbank
     */
    public function transbankNotification(Request $request)
    {
        try {
            Log::info('Transbank notification received', [
                'data' => $request->all(),
                'headers' => $request->headers->all()
            ]);

            // Obtener el token de la transacción
            $token = $request->input('token_ws');
            
            if (!$token) {
                Log::error('Transbank notification: No token received');
                return response()->json(['error' => 'No token received'], 400);
            }

            // Buscar el OrderDetail por el token
            $orderDetail = OrderDetail::where('transaction_id', $token)->first();
            
            if (!$orderDetail) {
                Log::error('Transbank notification: OrderDetail not found', [
                    'token' => $token
                ]);
                return response()->json(['error' => 'OrderDetail not found'], 404);
            }

            // Confirmar la transacción con Transbank
            $confirmation = $this->transbankService->confirmTransaction($token);

            if ($confirmation['success']) {
                // Actualizar el estado del pago
                $orderDetail->update([
                    'is_paid' => true,
                    'paid_at' => now(),
                    'status' => 'paid',
                    'gateway_response' => $confirmation
                ]);

                Log::info('Transbank payment confirmed via notification', [
                    'order_detail_id' => $orderDetail->id,
                    'transaction_id' => $token,
                    'amount' => $confirmation['amount'] ?? 'unknown'
                ]);

                return response()->json(['success' => true, 'message' => 'Payment confirmed']);
            } else {
                // Marcar como fallido
                $orderDetail->update([
                    'status' => 'cancelled',
                    'gateway_response' => $confirmation
                ]);

                Log::error('Transbank payment failed via notification', [
                    'token' => $token,
                    'error' => $confirmation['error'] ?? 'unknown error',
                    'order_detail_id' => $orderDetail->id
                ]);

                return response()->json(['error' => 'Payment failed'], 400);
            }

        } catch (\Exception $e) {
            Log::error('Error processing Transbank notification', [
                'error' => $e->getMessage(),
                'data' => $request->all()
            ]);

            return response()->json(['error' => 'Internal server error'], 500);
        }
    }

    /**
     * API endpoint para recibir notificaciones de Khipu
     * Esta ruta no tiene CORS y es llamada directamente por Khipu
     */
    public function khipuNotification(Request $request)
    {
        try {
            Log::info('Khipu notification received', [
                'data' => $request->all(),
                'headers' => $request->headers->all()
            ]);

            // Obtener datos de la notificación
            $paymentId = $request->input('payment_id');
            
            if (!$paymentId) {
                Log::error('Khipu notification: No payment_id received');
                return response()->json(['error' => 'No payment_id received'], 400);
            }

            // Buscar el OrderDetail por el payment_id
            $orderDetail = OrderDetail::where('transaction_id', $paymentId)->first();
            
            if (!$orderDetail) {
                Log::error('Khipu notification: OrderDetail not found', [
                    'payment_id' => $paymentId
                ]);
                return response()->json(['error' => 'OrderDetail not found'], 404);
            }

            // Obtener datos del webhook de Khipu
            $data = $request->all();
            $status = $data['status'] ?? null;

            // Procesar según el estado del webhook
            if ($status === 'done' || $status === 'success') {
                // Pago exitoso
                $orderDetail->update([
                    'is_paid' => true,
                    'paid_at' => now(),
                    'status' => 'paid',
                    'gateway_response' => $data
                ]);

                Log::info('Khipu payment confirmed via notification', [
                    'order_detail_id' => $orderDetail->id,
                    'payment_id' => $paymentId,
                    'status' => $status
                ]);

                return response()->json(['success' => true, 'message' => 'Payment confirmed']);
            } else {
                // Pago fallido o cancelado
                $orderDetail->update([
                    'status' => 'cancelled',
                    'gateway_response' => $data
                ]);

                Log::error('Khipu payment failed via notification', [
                    'payment_id' => $paymentId,
                    'status' => $status,
                    'order_detail_id' => $orderDetail->id
                ]);

                return response()->json(['error' => 'Payment failed'], 400);
            }

        } catch (\Exception $e) {
            Log::error('Error processing Khipu notification', [
                'error' => $e->getMessage(),
                'data' => $request->all()
            ]);

            return response()->json(['error' => 'Internal server error'], 500);
        }
    }
}
