<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Services\Client\Payment\GeneratePaymentService;
use App\Helpers\TokenHelper;
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
        // Intentar obtener datos del token primero
        $token = $request->query('token');

        if ($token) {
            $data = TokenHelper::decodeParticipantToken($token);
            if ($data) {
                $document = $data['document'];
                $documentType = $data['document_type'];
            } else {
                $document = $request->query('document', '');
                $documentType = $request->query('document_type', 'RUT');
            }
        } else {
            // Backward compatibility
            $rut = $request->query('rut', '');
            $document = $request->query('document', '');
            $documentType = $request->query('document_type', 'RUT');

            if (empty($document) && !empty($rut)) {
                $document = $rut;
            }

            // Intentar recuperar de sesión si no hay document
            if (empty($document)) {
                $document = session('current_document');
                $documentType = session('current_document_type', 'RUT');
            }
        }

        if (empty($document)) {
            return redirect()->route('ecommerce.index');
        }

        // Generar token para pasar al frontend
        $token = TokenHelper::encodeParticipantToken($document, $documentType);

        $paymentData = $this->generatePaymentService->getPaymentDetails($programId, $request->user()->id ?? null, $document);

        // Obtener países
        $countries = Country::where('name', 'Chile')->get();

        // Obtener regiones y comunas
        $regions = Region::with('comunes')->get();

        // Obtener tipos de documento
        $documentTypes = Document::all();

        // NO registrar analytics aquí - se hará desde el frontend con session_id

        // Obtener guardian autenticado si existe
        $guardian = auth('guardian')->user();
        if ($guardian) {
            $guardian->load(['documentType', 'country', 'region', 'comune']);
        }

        return Inertia::render('Ecommerce/PaymentDetails', [
            'paymentData' => $paymentData,
            'programId' => $programId,
            'token' => $token,
            'countries' => $countries,
            'regions' => $regions,
            'documentTypes' => $documentTypes,
            'guardian' => $guardian
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
