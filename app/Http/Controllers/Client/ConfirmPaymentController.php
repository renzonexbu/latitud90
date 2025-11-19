<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Services\Client\Payment\ConfirmPaymentService;
use App\Services\EcommerceAnalyticsService;
use App\Helpers\TokenHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class ConfirmPaymentController extends Controller
{
    protected $confirmPaymentService;
    protected $analyticsService;

    public function __construct(
        ConfirmPaymentService $confirmPaymentService,
        EcommerceAnalyticsService $analyticsService
    ) {
        $this->confirmPaymentService = $confirmPaymentService;
        $this->analyticsService = $analyticsService;
    }

    public function show(Request $request, $programCourseId)
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
                $documentType = $request->query('document_type', '');
            }
        } else {
            // Backward compatibility
            $document = $request->query('document', '');
            $documentType = $request->query('document_type', '');
        }

        // Obtener de sesión si no hay datos
        if (empty($document) || empty($documentType)) {
            $document = $document ?: session('current_document', '');
            $documentType = $documentType ?: session('current_document_type', '');
        }

        if (empty($document) || empty($documentType)) {
            return redirect()->route('ecommerce.index');
        }

        // Persistir en sesión
        session(['current_document' => $document, 'current_document_type' => $documentType]);

        // Generar token para pasar al frontend
        $token = TokenHelper::encodeParticipantToken($document, $documentType);

        $confirmationData = $this->confirmPaymentService->getConfirmationDetails($programCourseId, $request->user()->id ?? null, $document);

        // Verificar si hay un error (por ejemplo, guardian sin permiso)
        if (isset($confirmationData['error']) && $confirmationData['error'] === true) {
            return redirect()->route('ecommerce.programs')
                ->with('error', $confirmationData['message'] ?? 'No tienes permiso para acceder a esta página.');
        }

        // Registrar vista de confirmación en analytics
        $this->analyticsService->recordConfirmationView($request, $programCourseId, $document);

        return Inertia::render('Ecommerce/Confirmation', [
            'confirmationData' => $confirmationData,
            'programCourseId' => $programCourseId,
            'token' => $token
        ]);
    }
}
