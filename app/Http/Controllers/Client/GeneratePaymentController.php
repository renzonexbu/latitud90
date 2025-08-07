<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Services\Client\GeneratePaymentService;
use App\Models\Region;
use App\Models\Comune;
use App\Models\Document;
use App\Models\Country;
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
        
        // Obtener países
        $countries = Country::orderBy('name')->get();
        
        // Obtener regiones y comunas
        $regions = Region::with('comunes')->get();
        
        // Obtener tipos de documento
        $documentTypes = Document::all();
        
        return Inertia::render('Ecommerce/PaymentDetails', [
            'paymentData' => $paymentData,
            'programId' => $programId,
            'rut' => $request->query('rut', ''),
            'countries' => $countries,
            'regions' => $regions,
            'documentTypes' => $documentTypes
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
