<?php

namespace App\Http\Controllers\Client\Guardian;

use App\Http\Controllers\Controller;
use App\Models\Participant;
use App\Models\ProgramCourse;
use App\Services\Client\Guardian\GuardianParticipantService;
use Inertia\Inertia;
use Inertia\Response;

class GuardianDashboardController extends Controller
{
    public function __construct(
        private GuardianParticipantService $participantService
    ) {}

    /**
     * Mostrar el dashboard del guardian
     */
    public function index(): Response
    {
        $user = auth('guardian')->user();

        // Obtener datos del usuario con participantes vinculados
        $userData = $this->participantService->prepareUserDataForDashboard($user);

        return Inertia::render('Guardian/Dashboard', [
            'user' => $userData,
            'auth' => [
                'user' => $userData
            ]
        ]);
    }

    /**
     * Mostrar la lista completa de participantes
     */
    public function participants(): Response
    {
        $user = auth('guardian')->user();
        $participants = $this->participantService->getParticipantsList($user);

        return Inertia::render('Guardian/Participants', [
            'participants' => $participants,
            'auth' => [
                'user' => $user->toArray()
            ]
        ]);
    }

    /**
     * Mostrar los programas de un participante
     */
    public function participantPrograms(Participant $participant): Response
    {
        $user = auth('guardian')->user();

        // Verificar que el participante pertenece al guardian autenticado
        $hasAccess = $this->participantService->guardianHasAccessToParticipant($user, $participant->id);

        if (!$hasAccess) {
            abort(403, 'No tienes acceso a este participante.');
        }

        // Obtener programas del participante
        $programs = $this->participantService->getParticipantPrograms($participant);

        return Inertia::render('Guardian/ParticipantPrograms', [
            'participant' => [
                'id' => $participant->id,
                'name' => $participant->full_name,
                'document' => $participant->document_number,
                'document_type' => $participant->documentType ? $participant->documentType->name : 'N/A',
            ],
            'programs' => $programs,
            'auth' => [
                'user' => $user->toArray()
            ]
        ]);
    }

    /**
     * Mostrar el detalle de un programa específico con sus mensualidades
     */
    public function programDetail(Participant $participant, ProgramCourse $programCourse): Response
    {
        $user = auth('guardian')->user();

        // Verificar que el participante pertenece al guardian autenticado
        $hasAccess = $this->participantService->guardianHasAccessToParticipant($user, $participant->id);

        if (!$hasAccess) {
            abort(403, 'No tienes acceso a este participante.');
        }

        // Obtener detalles del programa con mensualidades
        $programDetails = $this->participantService->getProgramDetailWithInstallments($participant, $programCourse);

        if (!$programDetails) {
            return redirect()->route('guardian.participant.programs', $participant->id)
                ->with('error', 'Programa no encontrado');
        }

        return Inertia::render('Guardian/ProgramDetail', [
            'participant' => [
                'id' => $participant->id,
                'name' => $participant->full_name,
                'document' => $participant->document_number,
                'document_type' => $participant->documentType ? $participant->documentType->name : 'N/A',
            ],
            'program' => $programDetails,
            'auth' => [
                'user' => $user->toArray()
            ]
        ]);
    }
}
