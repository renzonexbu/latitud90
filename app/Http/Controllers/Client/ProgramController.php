<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Services\Client\Programs\ProgramService;
use App\Services\Client\Programs\ProgramDetailService;
use App\Services\EcommerceAnalyticsService;
use App\Helpers\TokenHelper;
use App\Models\SiteContent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class ProgramController extends Controller
{
    protected $programService;
    protected $programDetailService;
    protected $analyticsService;

    public function __construct(
        ProgramService $programService,
        ProgramDetailService $programDetailService,
        EcommerceAnalyticsService $analyticsService
    ) {
        $this->programService = $programService;
        $this->programDetailService = $programDetailService;
        $this->analyticsService = $analyticsService;
    }

    public function index(Request $request)
    {
        // Intentar obtener datos del token primero
        $token = $request->query('token');

        if ($token) {
            $data = TokenHelper::decodeParticipantToken($token);
            if ($data) {
                $document = $data['document'];
                $documentType = $data['document_type'];
            } else {
                Log::error('Token inválido recibido', ['token' => $token]);
                return redirect()->route('ecommerce.index')->with('error', 'Token inválido');
            }
        } else {
            // Backward compatibility: intentar obtener de query params
            $document = $request->query('document');
            $documentType = $request->query('document_type');
        }

        if (!$document || !$documentType) {
            return redirect()->route('ecommerce.index');
        }

        // Persistir documento en sesión para pasos posteriores
        session(['current_document' => $document, 'current_document_type' => $documentType]);

        // Generar token para pasar al frontend
        $token = TokenHelper::encodeParticipantToken($document, $documentType);

        $participant = $this->programService->getParticipantByDocument($document, $documentType);

        if (!$participant) {
            return redirect()->route('ecommerce.index')->with('error', 'Participante no encontrado');
        }

        // NO registrar analytics aquí - se hará desde el frontend con session_id

        $programs = $this->programService->getAvailablePrograms($participant);

        // Formatear los datos para la paginación como en el admin
        $formattedPrograms = [
            'data' => $programs,
            'current_page' => 1,
            'total' => count($programs),
            'per_page' => 6,
            'last_page' => ceil(count($programs) / 6)
        ];

        return Inertia::render('Ecommerce/Programs', [
            'participant' => $participant,
            'programs' => $formattedPrograms,
            'token' => $token
        ]);
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
                $document = null;
                $documentType = null;
            }
        } else {
            $document = $request->query('document');
            $documentType = $request->query('document_type');
        }

        if (!$document || !$documentType) {
            // Intentar recuperar de sesión
            $document = session('current_document');
            $documentType = session('current_document_type');

            if (!$document || !$documentType) {
                return redirect()->route('ecommerce.index');
            }
        }

        // Persistir documento en sesión para continuidad
        session(['current_document' => $document, 'current_document_type' => $documentType]);

        // Generar token para pasar al frontend
        $token = TokenHelper::encodeParticipantToken($document, $documentType);

        $participant = $this->programService->getParticipantByDocument($document, $documentType);

        if (!$participant) {
            return redirect()->route('ecommerce.index')->with('error', 'Participante no encontrado');
        }

        // NO registrar analytics aquí - se hará desde el frontend con session_id

        $programDetails = $this->programDetailService->getProgramDetails($programId, $participant->id);

        if (!$programDetails) {
            $redirectToken = TokenHelper::encodeParticipantToken($document, $documentType);
            return redirect()->route('ecommerce.programs', ['token' => $redirectToken])
                ->with('error', 'Programa no encontrado');
        }

        // Obtener contenido editable de program_detail
        $programDetailContent = SiteContent::where('section', 'program_detail')
            ->where('is_active', true)
            ->orderBy('order')
            ->get()
            ->keyBy('key')
            ->map(function ($item) {
                return [
                    'value' => $item->effective_value,
                    'key' => $item->key,
                ];
            })
            ->toArray();

        // Obtener contenido de contacto (WhatsApp)
        $contactContent = SiteContent::where('section', 'contact')
            ->where('is_active', true)
            ->orderBy('order')
            ->get()
            ->keyBy('key')
            ->map(function ($item) {
                return [
                    'value' => $item->effective_value,
                    'key' => $item->key,
                ];
            })
            ->toArray();

        // Obtener contenido del formulario de pago
        $paymentFormContent = SiteContent::where('section', 'payment_form')
            ->where('is_active', true)
            ->orderBy('order')
            ->get()
            ->keyBy('key')
            ->map(function ($item) {
                return [
                    'value' => $item->effective_value,
                    'key' => $item->key,
                ];
            })
            ->toArray();

        return Inertia::render('Ecommerce/ProgramDetail', [
            'participant' => $participant,
            'program' => $programDetails,
            'token' => $token,
            'siteContent' => [
                'program_detail' => $programDetailContent,
                'contact' => $contactContent,
                'payment_form' => $paymentFormContent,
            ],
        ]);
    }
}
