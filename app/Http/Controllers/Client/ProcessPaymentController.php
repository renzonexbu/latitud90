<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Services\Client\PaymentGateway\VirtualPosService;
use App\Services\Client\PaymentProcessing\ProcessPaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class ProcessPaymentController extends Controller
{
    public function __construct(
        private ProcessPaymentService $processPaymentService
    ) {}

    public function processPayment(Request $request)
    {
        $result = $this->processPaymentService->execute($request);

        return response()->json($result);
    }

    // Métodos deprecados - redirigir al nuevo controlador
    public function paymentSuccess($orderDetailId)
    {
        return redirect()->route('payment.success', $orderDetailId);
    }

    public function paymentFailure($orderDetailId)
    {
        return redirect()->route('payment.failure', $orderDetailId);
    }

    public function confirmKhipu(Request $request)
    {
        return redirect()->route('payment.confirm');
    }

    /**
     * Procesar notificación webhook de VirtualPOS
     */
    public function virtualposNotification(Request $request)
    {
        try {
            // Log detallado de la notificación recibida
            Log::info('VirtualPOS notification received - Full details', [
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
                Log::error('VirtualPOS notification: No payment_id provided', $notificationData);
                return response()->json(['error' => 'No payment_id provided'], 400);
            }

            // Buscar el pago en la base de datos
            // Para VirtualPOS, el UUID se guarda en la columna 'token'
            $payment = Payment::where('token', $paymentId)
                ->orWhere('external_payment_id', $paymentId)
                ->first();
            
            if (!$payment) {
                // Log adicional para debug: buscar todos los pagos con external_payment_id similar
                $allPayments = Payment::where('external_payment_id', 'like', '%' . $paymentId . '%')->get();
                Log::error('VirtualPOS notification: Payment not found - Debug info', [
                    'payment_id' => $paymentId,
                    'all_payments_with_similar_external_id' => $allPayments->map(function($p) {
                        return [
                            'id' => $p->id,
                            'external_payment_id' => $p->external_payment_id,
                            'token' => $p->token,
                            'status' => $p->status,
                            'amount' => $p->amount,
                            'order_detail_id' => $p->order_detail_id
                        ];
                    })
                ]);
                return response()->json(['error' => 'Payment not found'], 404);
            }

            // Procesar la notificación usando el servicio VirtualPOS
            $virtualPosService = new VirtualPosService();
            
            // Si el webhook no incluye status, consultar el estado del pago
            $status = $notificationData['status'] ?? null;
            if (!$status) {
                Log::info('VirtualPOS notification: No status provided, consulting payment status', [
                    'payment_id' => $paymentId
                ]);
                
                $confirmResult = $virtualPosService->confirmTransaction($paymentId);
                if ($confirmResult['success']) {
                    $status = $confirmResult['status'];
                    $notificationData['status'] = $status;
                    $notificationData['amount'] = $confirmResult['amount'];
                    $notificationData['authorization_code'] = $confirmResult['authorization_code'];
                } else {
                    Log::error('VirtualPOS notification: Failed to confirm transaction', [
                        'payment_id' => $paymentId,
                        'result' => $confirmResult
                    ]);
                    return response()->json(['error' => 'Failed to confirm transaction'], 400);
                }
            }
            
            $result = $virtualPosService->processNotification($notificationData);

            if ($result['success']) {
                // Actualizar el estado del pago y campos de autorización/cuotas
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

                Log::info('VirtualPOS notification processed successfully', [
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
                        'payment_id' => $paymentId,
                        'status' => $status,
                        'result' => $result
                    ]);

                    // Obtener order_detail_id para la redirección
                    $orderDetailId = $payment->order_detail_id;
                    $failureUrl = route('payment.failure', ['orderDetailId' => $orderDetailId]) . '?status=canceled';
                    return response()->json(['redirect' => $failureUrl]);
                } else {
                    Log::error('VirtualPOS notification processing failed', [
                        'payment_id' => $paymentId,
                        'status' => $status,
                        'result' => $result
                    ]);

                    return response()->json(['error' => $result['error']], 400);
                }
            }
        } catch (\Exception $e) {
            Log::error('VirtualPOS notification error', [
                'error' => $e->getMessage(),
                'data' => $request->all()
            ], $e);

            return response()->json(['error' => 'Internal server error'], 500);
        }
    }
}
