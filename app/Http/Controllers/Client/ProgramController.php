<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Services\Client\ProgramService;
use App\Services\Client\ProgramDetailService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ProgramController extends Controller
{
    protected $programService;
    protected $programDetailService;

    public function __construct(ProgramService $programService, ProgramDetailService $programDetailService)
    {
        $this->programService = $programService;
        $this->programDetailService = $programDetailService;
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
        $document = $request->query('document');
        $documentType = $request->query('document_type');
        
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

        $participant = $this->programService->getParticipantByDocument($document, $documentType);
        
        if (!$participant) {
            return redirect()->route('ecommerce.index')->with('error', 'Participante no encontrado');
        }

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
