<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Services\Client\GeneratePaymentService;
use App\Models\Region;
use App\Models\Comune;
use Illuminate\Http\Request;
use Inertia\Inertia;

class GeneratePaymentController extends Controller
{
    protected $generatePaymentService;

    public function __construct(GeneratePaymentService $generatePaymentService)
    {
        $this->generatePaymentService = $generatePaymentService;
    }

    public function show(Request $request, $programId)
    {
        $paymentData = $this->generatePaymentService->getPaymentDetails($programId, $request->user()->id ?? null);
        
        // Obtener regiones y comunas
        $regions = Region::with('comunes')->get();
        
        return Inertia::render('Ecommerce/PaymentDetails', [
            'paymentData' => $paymentData,
            'programId' => $programId,
            'rut' => $request->query('rut', ''),
            'regions' => $regions
        ]);
    }

    public function store(Request $request, $programId)
    {
        $validatedData = $request->validate([
            'payment_type' => 'required|in:total,monthly',
            'payment_method' => 'required|in:debit,credit,khipu',
            'installments' => 'required_if:payment_type,monthly|integer|min:1|max:12',
        ]);

        $result = $this->generatePaymentService->generatePayment($programId, $validatedData);

        if ($result['success']) {
            return redirect()->route('payment.gateway', [
                'programId' => $programId,
                'paymentId' => $result['payment_id']
            ]);
        }

        return back()->withErrors(['error' => $result['message']]);
    }
}
