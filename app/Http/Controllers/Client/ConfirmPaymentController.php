<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Services\Client\ConfirmPaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class ConfirmPaymentController extends Controller
{
    protected $confirmPaymentService;

    public function __construct(ConfirmPaymentService $confirmPaymentService)
    {
        $this->confirmPaymentService = $confirmPaymentService;
    }

    public function show(Request $request, $programId)
    {
        $documentFromQuery = $request->query('document');
        $documentTypeFromQuery = $request->query('document_type');
        
        // Obtener de sesión si no vienen en la URL
        $document = $documentFromQuery ?? session('current_document', '');
        $documentType = $documentTypeFromQuery ?? session('current_document_type', '');
        
        if ($document && $documentType) {
            // Persistir en sesión
            session(['current_document' => $document, 'current_document_type' => $documentType]);
        }

        // Si no vienen los parámetros en la URL pero existen en sesión, redirigir agregándolos para persistir en URL
        if ((!$documentFromQuery || !$documentTypeFromQuery) && (!empty($document) && !empty($documentType))) {
            Log::info('ConfirmPaymentController.show adding document params to URL', [
                'program_id' => $programId,
                'document' => $document,
                'document_type' => $documentType,
            ]);
            $redirect = redirect()->route('payment.confirmation', [
                'programId' => $programId, 
                'document' => $document, 
                'document_type' => $documentType
            ]);
            if (session()->has('error')) {
                $redirect->with('error', session('error'));
            }
            return $redirect;
        }
        
        $confirmationData = $this->confirmPaymentService->getConfirmationDetails($programId, $request->user()->id ?? null, $document);
        
        return Inertia::render('Ecommerce/Confirmation', [
            'confirmationData' => $confirmationData,
            'programId' => $programId,
            'document' => $document,
            'document_type' => $documentType
        ]);
    }
}
