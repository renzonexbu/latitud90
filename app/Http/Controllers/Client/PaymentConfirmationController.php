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
            Log::info('VirtualPOS webhook received', [
                'order_detail_id' => $orderDetailId,
                'data' => $request->all()
            ]);

            $notificationData = $request->all();
            $paymentId = $notificationData['payment_id'] ?? $notificationData['id'] ?? null;

            if (!$paymentId) {
                Log::error('VirtualPOS webhook: No payment_id provided', [
                    'order_detail_id' => $orderDetailId,
                    'data' => $notificationData
                ]);
                return response()->json(['error' => 'No payment_id provided'], 400);
            }

            // Procesar la notificación usando el servicio VirtualPOS
            $virtualPosService = app(\App\Services\Client\PaymentGateway\VirtualPosService::class);
            $result = $virtualPosService->processNotification($notificationData);

            if ($result['success']) {
                Log::info('VirtualPOS webhook processed successfully', [
                    'order_detail_id' => $orderDetailId,
                    'payment_id' => $paymentId,
                    'result' => $result
                ]);

                return response()->json(['success' => true]);
            } else {
                Log::error('VirtualPOS webhook processing failed', [
                    'order_detail_id' => $orderDetailId,
                    'payment_id' => $paymentId,
                    'result' => $result
                ]);

                return response()->json(['error' => $result['error']], 400);
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
