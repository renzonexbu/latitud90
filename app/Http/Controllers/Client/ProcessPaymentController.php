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
            Log::info('VirtualPOS notification received', $request->all());

            $notificationData = $request->all();
            $paymentId = $notificationData['payment_id'] ?? $notificationData['id'] ?? null;

            if (!$paymentId) {
                Log::error('VirtualPOS notification: No payment_id provided', $notificationData);
                return response()->json(['error' => 'No payment_id provided'], 400);
            }

            // Buscar el pago en la base de datos
            $payment = Payment::where('external_payment_id', $paymentId)->first();
            
            if (!$payment) {
                Log::error('VirtualPOS notification: Payment not found', ['payment_id' => $paymentId]);
                return response()->json(['error' => 'Payment not found'], 404);
            }

            // Procesar la notificación usando el servicio VirtualPOS
            $virtualPosService = new VirtualPosService();
            $result = $virtualPosService->processNotification($notificationData);

            if ($result['success']) {
                // Actualizar el estado del pago
                $payment->update([
                    'status' => 'completed',
                    'gateway_response' => $result,
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
                    'result' => $result
                ]);

                return response()->json(['success' => true]);
            } else {
                Log::error('VirtualPOS notification processing failed', [
                    'payment_id' => $paymentId,
                    'result' => $result
                ]);

                return response()->json(['error' => $result['error']], 400);
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
