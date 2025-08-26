<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Services\Client\Programs\ProgramService;
use App\Services\Client\Programs\ProgramDetailService;
use App\Services\EcommerceAnalyticsService;
use Illuminate\Http\Request;
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
        $document = $request->query('document');
        $documentType = $request->query('document_type');
        
        if (!$document || !$documentType) {
            return redirect()->route('ecommerce.index');
        }

        // Persistir documento en sesión para pasos posteriores
        session(['current_document' => $document, 'current_document_type' => $documentType]);

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
            'document' => $document,
            'document_type' => $documentType
        ]);
    }

    public function show(Request $request, $programId)
    {
        // Debug: Log todos los parámetros recibidos
        \Log::info('ProgramController@show - Debug info:', [
            'programId' => $programId,
            'all_query_params' => $request->all(),
            'document_from_query' => $request->query('document'),
            'document_type_from_query' => $request->query('document_type'),
            'full_url' => $request->fullUrl(),
            'session_document' => session('current_document'),
            'session_document_type' => session('current_document_type')
        ]);
        
        $document = $request->query('document');
        $documentType = $request->query('document_type');
        
        \Log::info('ProgramController@show - Initial values:', [
            'document' => $document,
            'documentType' => $documentType,
            'document_empty' => empty($document),
            'documentType_empty' => empty($documentType)
        ]);
        
        if (!$document || !$documentType) {
            \Log::info('ProgramController@show - Missing params, checking session');
            // Intentar recuperar de sesión
            $document = session('current_document');
            $documentType = session('current_document_type');
            
            \Log::info('ProgramController@show - Session values:', [
                'session_document' => $document,
                'session_documentType' => $documentType
            ]);
            
            if (!$document || !$documentType) {
                \Log::warning('ProgramController@show - Redirecting to home, missing required params');
                return redirect()->route('ecommerce.index');
            }
        }

        // Persistir documento en sesión para continuidad
        session(['current_document' => $document, 'current_document_type' => $documentType]);

        $participant = $this->programService->getParticipantByDocument($document, $documentType);
        
        if (!$participant) {
            return redirect()->route('ecommerce.index')->with('error', 'Participante no encontrado');
        }

        // NO registrar analytics aquí - se hará desde el frontend con session_id

        $programDetails = $this->programDetailService->getProgramDetails($programId, $participant->id);
        
        if (!$programDetails) {
            return redirect()->route('ecommerce.programs', [
                'document' => $document,
                'document_type' => $documentType
            ])->with('error', 'Programa no encontrado');
        }

        return Inertia::render('Ecommerce/ProgramDetail', [
            'participant' => $participant,
            'program' => $programDetails,
            'document' => $document,
            'document_type' => $documentType
        ]);
    }
}
