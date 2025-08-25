<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Services\Client\GeneratePaymentService;
use App\Models\Region;
use App\Models\Comune;
use App\Models\Document;
use App\Models\Country;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
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
        // Debug: Log todos los query parameters
        Log::info('GeneratePaymentController - Query parameters:', [
            'all_query' => $request->all(),
            'rut' => $request->query('rut'),
            'document' => $request->query('document'),
            'document_type' => $request->query('document_type'),
            'programId' => $programId
        ]);
        
        $rut = $request->query('rut', '');
        $document = $request->query('document', '');
        $documentType = $request->query('document_type', 'RUT');
        
        // Debug: Log la URL completa
        Log::info('GeneratePaymentController - Full URL:', [
            'url' => $request->fullUrl(),
            'path' => $request->path(),
            'query_string' => $request->getQueryString()
        ]);
        
        // Si no hay document pero hay rut, usar rut como document
        if (empty($document) && !empty($rut)) {
            $document = $rut;
        }
        
        // Fallback: si aún no hay document, usar un valor por defecto para testing
        if (empty($document)) {
            $document = '23515086'; // Valor por defecto para testing
            Log::info('Using fallback document value:', ['document' => $document]);
        }
        
        $paymentData = $this->generatePaymentService->getPaymentDetails($programId, $request->user()->id ?? null, $rut);
        
        // Obtener países
        $countries = Country::where('name', 'Chile')->get();
        
        // Obtener regiones y comunas
        $regions = Region::with('comunes')->get();
        
        // Obtener tipos de documento
        $documentTypes = Document::all();
        
        // NO registrar analytics aquí - se hará desde el frontend con session_id
        
        return Inertia::render('Ecommerce/PaymentDetails', [
            'paymentData' => $paymentData,
            'programId' => $programId,
            'rut' => $rut,
            'document' => $document,
            'document_type' => $documentType,
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
