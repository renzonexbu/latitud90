<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CreateParticipantRequest;
use App\Models\Participant;
use App\Models\EmergencyContact;
use App\Services\Admin\Participants\CreateParticipantService;
use App\Services\Admin\Participants\UpdateParticipatService;
use App\Services\Admin\Participants\UpdateMedicalConditionsService;
use App\Services\Admin\Participants\UpdateEmergencyContactService;
use App\Services\Admin\Participants\GetParticipantsService;
use App\Services\Admin\Participants\GetCreateDataService;
use App\Services\Admin\Participants\GetEditDataService;
use App\Services\Admin\Participants\CreateEmergencyContactService;
use App\Services\Admin\Participants\ToggleActiveStatusService;
use App\Services\Admin\Participants\ToggleStatusService;
use App\Services\Admin\Participants\GetInactiveParticipantsService;
use App\Services\Admin\Participants\BulkActionService;
use App\Services\Admin\Participants\GetParticipantPaymentsService;
use App\Traits\AdminLogging;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use App\Http\Requests\Admin\Participants\UpdateParticipantRequest;
use App\Http\Requests\Admin\Participants\UpdateMedicalConditionsRequest;
use App\Http\Requests\Admin\Participants\UpdateEmergencyContactsRequest;
use App\Http\Requests\Admin\Participants\UpdateEmergencyContactRequest;
use App\Http\Requests\Admin\Participants\DeleteEmergencyContactRequest;

class ParticipantsController extends Controller
{
    use AdminLogging;

    protected $createParticipantService;
    protected $updateParticipantService;
    protected $updateMedicalConditionsService;
    protected $updateEmergencyContactService;
    protected $getParticipantsService;
    protected $getCreateDataService;
    protected $getEditDataService;
    protected $createEmergencyContactService;
    protected $toggleActiveStatusService;
    protected $toggleStatusService;
    protected $getInactiveParticipantsService;
    protected $bulkActionService;
    protected $getParticipantPaymentsService;

    public function __construct(
        CreateParticipantService $createParticipantService,
        UpdateParticipatService $updateParticipantService,
        UpdateMedicalConditionsService $updateMedicalConditionsService,
        UpdateEmergencyContactService $updateEmergencyContactService,
        GetParticipantsService $getParticipantsService,
        GetCreateDataService $getCreateDataService,
        GetEditDataService $getEditDataService,
        CreateEmergencyContactService $createEmergencyContactService,
        ToggleActiveStatusService $toggleActiveStatusService,
        ToggleStatusService $toggleStatusService,
        GetInactiveParticipantsService $getInactiveParticipantsService,
        BulkActionService $bulkActionService,
        GetParticipantPaymentsService $getParticipantPaymentsService
    ) {
        $this->createParticipantService = $createParticipantService;
        $this->updateParticipantService = $updateParticipantService;
        $this->updateMedicalConditionsService = $updateMedicalConditionsService;
        $this->updateEmergencyContactService = $updateEmergencyContactService;
        $this->getParticipantsService = $getParticipantsService;
        $this->getCreateDataService = $getCreateDataService;
        $this->getEditDataService = $getEditDataService;
        $this->createEmergencyContactService = $createEmergencyContactService;
        $this->toggleActiveStatusService = $toggleActiveStatusService;
        $this->toggleStatusService = $toggleStatusService;
        $this->getInactiveParticipantsService = $getInactiveParticipantsService;
        $this->bulkActionService = $bulkActionService;
        $this->getParticipantPaymentsService = $getParticipantPaymentsService;
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

    /**
     * Update medical conditions of a participant.
     */
    public function updateMedicalConditions(UpdateMedicalConditionsRequest $request, Participant $participant)
    {
        try {
            $oldValues = [
                'allergies' => $participant->allergies,
                'intolerances' => $participant->intolerances,
                'dietary_restrictions' => $participant->dietary_restrictions,
            ];

            $this->updateMedicalConditionsService->execute($request->validated(), $participant);

            $this->logUpdate(
                'Condiciones Médicas',
                'Participant',
                $participant->id,
                'Condiciones médicas actualizadas para participante: ' . $participant->first_name . ' ' . $participant->first_last_name,
                $oldValues,
                $request->validated()
            );

            return back()->with('success', 'Condiciones médicas actualizadas exitosamente.');
        } catch (\Exception $e) {
            Log::error('Error en controlador al actualizar condiciones médicas', [
                'participant_id' => $participant->id,
                'error' => $e->getMessage()
            ]);

            return back()->withErrors(['error' => 'Error al actualizar las condiciones médicas: ' . $e->getMessage()]);
        }
    }

    /**
     * Update emergency contacts of a participant.
     */
    public function updateEmergencyContacts(UpdateEmergencyContactsRequest $request, Participant $participant)
    {
        try {
            $emergencyContactsData = json_decode($request->validated()['emergency_contacts'], true);

            if (!is_array($emergencyContactsData)) {
                throw new \Exception('Formato de datos inválido');
            }

            $createdContacts = $this->createEmergencyContactService->execute($emergencyContactsData, $participant);

            // Log cada contacto creado
            foreach ($createdContacts as $contact) {
                $this->logCreate(
                    'Apoderados',
                    'EmergencyContact',
                    $contact->id,
                    'Nuevo apoderado creado para participante: ' . $participant->first_name . ' ' . $participant->first_last_name,
                    $contact->toArray()
                );
            }

            return back()->with('success', 'Apoderado agregado exitosamente.');
        } catch (\Exception $e) {
            Log::error('Error en controlador al actualizar contactos de emergencia', [
                'participant_id' => $participant->id,
                'error' => $e->getMessage()
            ]);

            return back()->withErrors(['error' => 'Error al agregar el apoderado: ' . $e->getMessage()]);
        }
    }

    /**
     * Update a specific emergency contact.
     */
    public function updateEmergencyContact(UpdateEmergencyContactRequest $request, Participant $participant)
    {
        try {
            $validatedData = $request->validated();
            $contactId = $validatedData['contact_id'];
            $contact = EmergencyContact::findOrFail($contactId);
            $oldValues = $contact->getOriginal();

            $this->updateEmergencyContactService->update($validatedData, $participant);

            $this->logUpdate(
                'Apoderados',
                'EmergencyContact',
                $contact->id,
                'Apoderado actualizado para participante: ' . $participant->first_name . ' ' . $participant->first_last_name,
                $oldValues,
                $validatedData
            );

            return back()->with('success', 'Apoderado actualizado exitosamente.');
        } catch (\Exception $e) {
            Log::error('Error en controlador al actualizar contacto de emergencia', [
                'participant_id' => $participant->id,
                'contact_id' => $request->input('contact_id') ?? null,
                'error' => $e->getMessage()
            ]);

            return back()->withErrors(['error' => 'Error al actualizar el apoderado: ' . $e->getMessage()]);
        }
    }

    /**
     * Delete a specific emergency contact.
     */
    public function deleteEmergencyContact(DeleteEmergencyContactRequest $request, Participant $participant)
    {
        try {
            $validatedData = $request->validated();
            $contactId = $validatedData['contact_id'];
            $contact = EmergencyContact::findOrFail($contactId);

            $this->updateEmergencyContactService->delete($validatedData, $participant);

            $this->logDelete(
                'Apoderados',
                'EmergencyContact',
                $contact->id,
                'Apoderado eliminado para participante: ' . $participant->first_name . ' ' . $participant->first_last_name
            );

            return back()->with('success', 'Apoderado eliminado exitosamente.');
        } catch (\Exception $e) {
            Log::error('Error en controlador al eliminar contacto de emergencia', [
                'participant_id' => $participant->id,
                'contact_id' => $request->input('contact_id') ?? null,
                'error' => $e->getMessage()
            ]);

            return back()->withErrors(['error' => 'Error al eliminar el apoderado: ' . $e->getMessage()]);
        }
    }

    /**
     * Toggle participant active status.
     */
    public function destroy(Participant $participant)
    {
        $result = $this->toggleActiveStatusService->execute($participant);

        $this->logDelete(
            'Participantes',
            'Participant',
            $participant->id,
            'Participante ' . $result['action'] . ': ' . $participant->id . ' - ' . $result['participant_name']
        );

        return redirect()->back()
            ->with('success', 'Participante ' . $result['action'] . ' exitosamente.');
    }

    /**
     * Toggle participant status.
     */
    public function toggleStatus(Participant $participant)
    {
        $result = $this->toggleStatusService->execute($participant);

        return back()->with('success', 'Estado del participante actualizado.');
    }

    /**
     * Show inactive participants.
     */
    public function inactive()
    {
        $data = $this->getInactiveParticipantsService->execute(request());

        return Inertia::render('Admin/Participants/Inactive', $data);
    }

    /**
     * Perform bulk actions on participants.
     */
    public function bulkAction(Request $request)
    {
        $validated = $request->validate([
            'action' => 'required|in:delete,activate,confirm,cancel',
            'participant_ids' => 'required|array',
            'participant_ids.*' => 'exists:participants,id'
        ]);

        $result = $this->bulkActionService->execute($validated['action'], $validated['participant_ids']);

        return back()->with('success', $result['message']);
    }

    /**
     * Get participant payments.
     */
    public function payments(Participant $participant)
    {
        $payments = $this->getParticipantPaymentsService->execute($participant);

        return response()->json($payments);
    }

    /**
     * Export participants to Excel.
     */
    public function export(Request $request)
    {
        // Implementation would depend on your Excel export package
        // For example, using Laravel Excel
        return response()->json(['message' => 'Export functionality to be implemented']);
    }
}
