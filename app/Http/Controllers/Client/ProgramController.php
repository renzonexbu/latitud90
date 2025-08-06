<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Services\Client\ProgramService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ProgramController extends Controller
{
    protected $programService;

    public function __construct(ProgramService $programService)
    {
        $this->programService = $programService;
    }

    public function index(Request $request)
    {
        $rut = $request->query('rut');
        
        if (!$rut) {
            return redirect()->route('ecommerce.index');
        }

        $participant = $this->programService->getParticipantByRut($rut);
        
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
            'rut' => $rut
        ]);
    }

    public function show(Request $request, $programId)
    {
        $rut = $request->query('rut');
        
        if (!$rut) {
            return redirect()->route('ecommerce.index');
        }

        $participant = $this->programService->getParticipantByRut($rut);
        
        if (!$participant) {
            return redirect()->route('ecommerce.index')->with('error', 'Participante no encontrado');
        }

        $program = $this->programService->getProgramById($programId);
        
        if (!$program) {
            return redirect()->route('ecommerce.programs', ['rut' => $rut])->with('error', 'Programa no encontrado');
        }

        return Inertia::render('Ecommerce/ProgramDetail', [
            'participant' => $participant,
            'program' => $program,
            'rut' => $rut
        ]);
    }
}
