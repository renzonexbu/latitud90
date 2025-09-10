<?php

namespace App\Http\Controllers\Admin\Participants;    

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CreateParticipantRequest;
use App\Models\Participant;
use App\Services\Admin\Participants\CreateParticipantService;
use App\Services\Admin\Participants\UpdateParticipantService;
use App\Services\Admin\Participants\GetParticipantsService;
use App\Services\Admin\Participants\GetCreateDataService;
use App\Services\Admin\Participants\GetEditDataService;
use App\Traits\AdminLogging;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use App\Http\Requests\Admin\Participants\UpdateParticipantRequest;

class ParticipantsController extends Controller
{
    use AdminLogging;

    protected $createParticipantService;
    protected $updateParticipantService;
    protected $getParticipantsService;
    protected $getCreateDataService;
    protected $getEditDataService;

    public function __construct(
        CreateParticipantService $createParticipantService,
        UpdateParticipantService $updateParticipantService,
        GetParticipantsService $getParticipantsService,
        GetCreateDataService $getCreateDataService,
        GetEditDataService $getEditDataService
    ) {
        $this->createParticipantService = $createParticipantService;
        $this->updateParticipantService = $updateParticipantService;
        $this->getParticipantsService = $getParticipantsService;
        $this->getCreateDataService = $getCreateDataService;
        $this->getEditDataService = $getEditDataService;
    }

    /**
     * Display a listing of participants.
     */
    public function index(Request $request)
    {
        $data = $this->getParticipantsService->execute($request);

        return Inertia::render('Admin/Participants/Index', $data);
    }

    /**
     * Show the form for creating a new participant.
     */
    public function create()
    {
        $data = $this->getCreateDataService->execute();
        return Inertia::render('Admin/Participants/Create', $data);
    }

    /**
     * Store a newly created participant in storage.
     */
    public function store(CreateParticipantRequest $request)
    {
        try {
            // Separar los datos del participante
            $participantData = array_intersect_key($request->validated(), array_flip([
                'program_id',
                'first_last_name',
                'second_last_name',
                'first_name',
                'second_name',
                'document_type',
                'document_number',
                'birth_date',
                'nationality',
                'gender',
                'dietary_restrictions',
                'intolerances',
                'allergies',
                'country',
                'address',
                'individual_price',
                'price_adjustments',
                'adjustment_reason'
            ]));



            $emergencyContactsData = $request->validated()['emergency_contacts'] ?? [];

            // Crear participante usando el servicio
            $participant = $this->createParticipantService->execute(
                $participantData,
                $emergencyContactsData
            );

            return redirect()->route('admin.participants.index')
                ->with('success', 'Participante creado exitosamente.');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Error al crear el participante: ' . $e->getMessage()])
                ->withInput();
        }
    }

    /**
     * Display the specified participant.
     */
    public function show(Participant $participant)
    {
        $participant->load(['courses', 'courses.program', 'emergencyContacts', 'medicalConditions']);

        return Inertia::render('Admin/Participants/Show', [
            'participant' => $participant
        ]);
    }

    /**
     * Show the form for editing the specified participant.
     */
    public function edit($id)
    {
        try {
            $data = $this->getEditDataService->execute($id);

            return Inertia::render('Admin/Participants/Edit', $data);
        } catch (\Exception $e) {
            Log::error('Error al editar participante', [
                'participant_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return redirect()->route('admin.participants.index')
                ->with('error', 'Error al cargar el participante: ' . $e->getMessage());
        }
    }

    /**
     * Update the specified participant in storage.
     */
    public function update(UpdateParticipantRequest $request, Participant $participant)
    {
        try {
            $oldValues = $participant->getOriginal();

            $this->updateParticipantService->execute($request->validated(), $participant);

            $this->logUpdate(
                'Participantes',
                'Participant',
                $participant->id,
                'Participante actualizado',
                $oldValues,
                $request->validated()
            );

            return back()->with('success', 'Participante actualizado exitosamente.');
        } catch (\Exception $e) {
            Log::error('Error en controlador al actualizar participante', [
                'participant_id' => $participant->id,
                'error' => $e->getMessage()
            ]);

            return back()->withErrors(['error' => 'Error al actualizar el participante: ' . $e->getMessage()]);
        }
    }

}
