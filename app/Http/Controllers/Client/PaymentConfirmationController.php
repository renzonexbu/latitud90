<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Services\Client\PaymentGateway\PaymentConfirmationService;
use App\Services\Client\GetSpinnerDataService;
use App\Services\Client\ProcessPaymentConfirmationService;
use App\Services\Client\GetSuccessDataService;
use App\Services\Client\GetFailureDataService;
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


}
