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
            
            // Buscar el pago
            $payment = \App\Models\Payment::where('token', $paymentId)
                ->orWhere('external_payment_id', $paymentId)
                ->first();
            
            if ($payment) {
                // Consultar el estado del pago en VirtualPOS
                $virtualPosService = app(\App\Services\Client\PaymentGateway\VirtualPosService::class);
                $confirmResult = $virtualPosService->confirmTransaction($paymentId);
                
                if (isset($confirmResult['full_response']['payment']['order']['status'])) {
                    $status = $confirmResult['full_response']['payment']['order']['status'];
                    
                    // Si el pago fue rechazado/cancelado, redirigir a fallo
                    if (in_array($status, ['rechazado', 'cancelado', 'cancelled', 'rejected', 'failed'])) {
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
                    // Si está aprobado, redirigir a éxito
                    elseif (in_array($status, ['aprobado', 'approved', 'paid', 'success'])) {
                        $payment->update([
                            'status' => 'completed',
                            'authorization_code' => $confirmResult['authorization_code'] ?? $payment->authorization_code,
                            'installments_number' => isset($confirmResult['installments']) ? (int) $confirmResult['installments'] : $payment->installments_number,
                            'installment_amount' => $confirmResult['installment_amount'] ?? $payment->installment_amount,
                            'gateway_response' => $confirmResult,
                            'transaction_date' => $payment->transaction_date ?? now()->setTimezone('America/Santiago'),
                        ]);

                        $orderDetail = $payment->orderDetail;
                        if ($orderDetail) {
                            $orderDetail->update([
                                'status' => 'paid',
                                'is_paid' => true,
                                'paid_at' => now()->setTimezone('America/Santiago'),
                            ]);
                        }
                        
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
    public function handleVirtualPosWebhook(Request $request, $orderDetailId)
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
            $paymentId = $notificationData['payment_id'] ?? $notificationData['id'] ?? $notificationData['uuid'] ?? null;

            if (!$paymentId) {
                Log::error('VirtualPOS webhook: No payment_id provided', [
                    'order_detail_id' => $orderDetailId,
                    'data' => $notificationData
                ]);
                return response()->json(['error' => 'No payment_id provided'], 400);
            }

            // Buscar el pago en la base de datos usando el payment_id (uuid)
            // Para VirtualPOS, el UUID se guarda en la columna 'token'
            $payment = \App\Models\Payment::where('token', $paymentId)
                ->orWhere('external_payment_id', $paymentId)
                ->first();
            
            if (!$payment) {
                // Log adicional para debug: buscar todos los pagos relacionados con este order_detail
                $allPayments = \App\Models\Payment::where('order_detail_id', $orderDetailId)->get();
                Log::error('VirtualPOS webhook: Payment not found - Debug info', [
                    'order_detail_id' => $orderDetailId,
                    'payment_id' => $paymentId,
                    'all_payments_for_order_detail' => $allPayments->map(function($p) {
                        return [
                            'id' => $p->id,
                            'external_payment_id' => $p->external_payment_id,
                            'token' => $p->token,
                            'status' => $p->status,
                            'amount' => $p->amount
                        ];
                    })
                ]);
                return response()->json(['error' => 'Payment not found'], 404);
            }

            // Procesar la notificación usando el servicio VirtualPOS
            $virtualPosService = app(\App\Services\Client\PaymentGateway\VirtualPosService::class);
            
            // Si el webhook no incluye status, consultar el estado del pago
            $status = $notificationData['status'] ?? null;
            if (!$status) {
                Log::info('VirtualPOS webhook: No status provided, consulting payment status', [
                    'payment_id' => $paymentId
                ]);
                
                $confirmResult = $virtualPosService->confirmTransaction($paymentId);
                if ($confirmResult['success']) {
                    $status = $confirmResult['status'];
                    $notificationData['status'] = $status;
                    $notificationData['amount'] = $confirmResult['amount'];
                    $notificationData['authorization_code'] = $confirmResult['authorization_code'];
                    $notificationData['installments'] = $confirmResult['installments'] ?? null;
                    $notificationData['installment_amount'] = $confirmResult['installment_amount'] ?? null;
                } else {
                    // Verificar si el resultado contiene información de estado rechazado
                    $responseStatus = $confirmResult['full_response']['payment']['order']['status'] ?? null;
                    
                    if (in_array($responseStatus, ['rechazado', 'cancelado', 'cancelled', 'rejected', 'failed'])) {
                        // Actualizar el estado del pago a fallido
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
                        
                        Log::info('VirtualPOS payment marked as failed', [
                            'order_detail_id' => $orderDetailId,
                            'payment_id' => $paymentId,
                            'status' => $responseStatus,
                            'result' => $confirmResult
                        ]);
                        
                        // Redirigir directamente a la página de fallo
                        return redirect()->route('payment.failure', ['orderDetailId' => $orderDetailId])
                            ->with('status', 'canceled');
                    } else {
                        Log::error('VirtualPOS webhook: Failed to confirm transaction', [
                            'payment_id' => $paymentId,
                            'result' => $confirmResult
                        ]);
                        return response()->json(['error' => 'Failed to confirm transaction'], 400);
                    }
                }
            }
            
            $result = $virtualPosService->processNotification($notificationData);

            if ($result['success']) {
                // Actualizar el estado del pago
                $payment->update([
                    'status' => 'completed',
                    'authorization_code' => $result['authorization_code'] ?? $payment->authorization_code,
                    'installments_number' => isset($result['installments']) ? (int) $result['installments'] : $payment->installments_number,
                    'installment_amount' => $result['installment_amount'] ?? $payment->installment_amount,
                    'gateway_response' => $result,
                    'transaction_date' => $payment->transaction_date ?? now()->setTimezone('America/Santiago'),
                ]);

                // Actualizar el order detail
                $orderDetail = $payment->orderDetail;
                if ($orderDetail) {
                    $orderDetail->update([
                        'status' => 'paid',
                        'is_paid' => true,
                        'paid_at' => now()->setTimezone('America/Santiago'),
                    ]);
                }

                Log::info('VirtualPOS webhook processed successfully', [
                    'order_detail_id' => $orderDetailId,
                    'payment_id' => $paymentId,
                    'status' => $status,
                    'result' => $result
                ]);

                return response()->json(['success' => true]);
            } else {
                // Si el pago fue rechazado/cancelado, actualizar el estado y redirigir
                if (in_array($status, ['rechazado', 'cancelado', 'cancelled', 'rejected', 'failed'])) {
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
                        'order_detail_id' => $orderDetailId,
                        'payment_id' => $paymentId,
                        'status' => $status,
                        'result' => $result
                    ]);

                    // Redirigir directamente a la página de fallo
                    return redirect()->route('payment.failure', ['orderDetailId' => $orderDetailId])
                        ->with('status', 'canceled');
                } else {
                    Log::error('VirtualPOS webhook processing failed', [
                        'order_detail_id' => $orderDetailId,
                        'payment_id' => $paymentId,
                        'status' => $status,
                        'result' => $result
                    ]);

                    return response()->json(['error' => $result['error']], 400);
                }
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
