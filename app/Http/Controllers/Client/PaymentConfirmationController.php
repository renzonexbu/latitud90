<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Services\Client\PaymentGateway\PaymentConfirmationService;
use App\Services\Client\Data\GetSpinnerDataService;
use App\Services\Client\Payment\ProcessPaymentConfirmationService;
use App\Services\Client\Data\GetSuccessDataService;
use App\Services\Client\Data\GetFailureDataService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class PaymentConfirmationController extends Controller
{
    public function __construct(
        private PaymentConfirmationService $paymentConfirmationService,
        private GetSpinnerDataService $getSpinnerDataService,
        private ProcessPaymentConfirmationService $processPaymentConfirmationService,
        private GetSuccessDataService $getSuccessDataService,
        private GetFailureDataService $getFailureDataService
    ) {}

    /**
     * Vista de spinner unificada para todas las pasarelas
     */
    public function showSpinner(Request $request, $orderDetailId)
    {
        // Verificar si VirtualPOS está enviando datos de cancelación/rechazo
        if ($request->has('uuid') && $request->input('gateway') === 'virtualpos') {
            Log::info('VirtualPOS callback received in showSpinner', [
                'order_detail_id' => $orderDetailId,
                'data' => $request->all()
            ]);
            
            $paymentId = $request->input('uuid');
            
            // Buscar el pago usando el método centralizado
            $payment = \App\Models\Payment::findByVirtualPosId($paymentId);
            
            if ($payment) {
                // Consultar el estado del pago en VirtualPOS
                $virtualPosService = app(\App\Services\Client\PaymentGateway\VirtualPosService::class);
                $confirmResult = $virtualPosService->confirmTransaction($paymentId);
                
                if (isset($confirmResult['full_response']['payment']['order']['status'])) {
                    $status = $confirmResult['full_response']['payment']['order']['status'];
                    
                    // Si el pago fue rechazado/cancelado, redirigir a fallo
                    if (in_array($status, \App\Services\Client\PaymentGateway\VirtualPosService::REJECTED_STATUSES)) {
                        $payment->update([
                            'status' => 'failed',
                            'gateway_response' => $confirmResult,
                        ]);

                        $orderDetail = $payment->orderDetail;
                        if ($orderDetail) {
                            $orderDetail->update([
                                'status' => 'failed',
                                'is_paid' => false,
                            ]);
                        }
                        
                        return redirect()->route('payment.failure', ['orderDetailId' => $orderDetailId])
                            ->with('status', 'canceled');
                    }
                    // Si está aprobado, usar el servicio centralizado para evitar duplicación
                    elseif (in_array($status, \App\Services\Client\PaymentGateway\VirtualPosService::APPROVED_STATUSES)) {
                        // Usar el servicio centralizado para procesar el pago exitoso
                        // Esto evitará duplicación de emails y asegurará consistencia
                        $this->paymentConfirmationService->confirmPayment(
                            $orderDetailId,
                            'virtualpos',
                            $confirmResult
                        );
                        
                        return redirect()->route('payment.success', ['orderDetailId' => $orderDetailId]);
                    }
                }
            }
        }
        
        $data = $this->getSpinnerDataService->execute($request, (int) $orderDetailId);
        
        return Inertia::render('Payment/PaymentSpinner', $data);
    }

    /**
     * Confirmar pago unificado
     */
    public function confirmPayment(Request $request)
    {
        $result = $this->processPaymentConfirmationService->execute($request);

        return response()->json($result);
    }

    /**
     * Vista de éxito unificada
     */
    public function showSuccess(Request $request, $orderDetailId)
    {
        try {
            $data = $this->getSuccessDataService->execute((int) $orderDetailId);

            return Inertia::render('Payment/PaymentSuccess', $data);
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
        $data = $this->getFailureDataService->execute($request, (int) $orderDetailId);

        return Inertia::render('Payment/PaymentFailure', $data);
    }

    /**
     * Manejar notificaciones webhook de VirtualPOS
     */
    public function handleVirtualPosWebhook(Request $request, $orderDetailId = null)
    {
        try {
            // Log detallado de la notificación recibida
            Log::info('VirtualPOS webhook received - Full details', [
                'order_detail_id' => $orderDetailId,
                'all_request_data' => $request->all(),
                'headers' => $request->headers->all(),
                'method' => $request->method(),
                'url' => $request->fullUrl(),
                'query_params' => $request->query(),
                'body_params' => $request->post(),
                'json_data' => $request->json() ? $request->json()->all() : null,
                'notification_data' => $request->all()
            ]);

            $notificationData = $request->all();
            
            // Usar el método unificado del VirtualPosService
            $virtualPosService = app(\App\Services\Client\PaymentGateway\VirtualPosService::class);
            $result = $virtualPosService->handleWebhookNotification($notificationData, $orderDetailId);

            if (!$result['success']) {
                if (isset($result['error']) && $result['error'] === 'Payment not found') {
                    return response()->json(['error' => 'Payment not found'], 404);
                }
                return response()->json(['error' => $result['error']], 400);
            }

            $payment = $result['payment'];
            $status = $result['status'];
            $paymentId = $result['payment_id'];

            // Determinar si el pago fue aprobado o rechazado usando las constantes
            $isApproved = in_array($status, \App\Services\Client\PaymentGateway\VirtualPosService::APPROVED_STATUSES);
            $isRejected = in_array($status, \App\Services\Client\PaymentGateway\VirtualPosService::REJECTED_STATUSES);

            if ($isApproved) {
                // Usar el servicio centralizado para procesar el pago exitoso
                $this->paymentConfirmationService->confirmPayment(
                    $payment->order_detail_id,
                    'virtualpos',
                    $result
                );

                Log::info('VirtualPOS webhook processed successfully', [
                    'order_detail_id' => $payment->order_detail_id,
                    'payment_id' => $paymentId,
                    'status' => $status,
                    'result' => $result
                ]);

                return response()->json(['success' => true]);
            } elseif ($isRejected) {
                // Actualizar el estado del pago a fallido
                $payment->update([
                    'status' => 'failed',
                    'gateway_response' => $result,
                ]);

                $orderDetail = $payment->orderDetail;
                if ($orderDetail) {
                    $orderDetail->update([
                        'status' => 'failed',
                        'is_paid' => false,
                    ]);
                }

                Log::info('VirtualPOS payment rejected/cancelled', [
                    'order_detail_id' => $payment->order_detail_id,
                    'payment_id' => $paymentId,
                    'status' => $status,
                    'result' => $result
                ]);

                // Si tenemos order_detail_id, redirigir a la página de fallo
                if ($payment->order_detail_id) {
                    return redirect()->route('payment.failure', ['orderDetailId' => $payment->order_detail_id])
                        ->with('status', 'canceled');
                }

                return response()->json(['success' => false, 'status' => 'rejected']);
            } else {
                Log::error('VirtualPOS webhook processing failed - Unknown status', [
                    'order_detail_id' => $payment->order_detail_id ?? $orderDetailId,
                    'payment_id' => $paymentId,
                    'status' => $status,
                    'result' => $result
                ]);

                return response()->json(['error' => 'Unknown payment status'], 400);
            }
        } catch (\Exception $e) {
            Log::error('VirtualPOS webhook error', [
                'order_detail_id' => $orderDetailId,
                'error' => $e->getMessage(),
                'data' => $request->all()
            ], $e);

            return response()->json(['error' => 'Internal server error'], 500);
        }
    }

}
