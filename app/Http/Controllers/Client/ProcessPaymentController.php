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

}
