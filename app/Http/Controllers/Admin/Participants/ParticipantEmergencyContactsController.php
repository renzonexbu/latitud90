<?php

namespace App\Http\Controllers\Admin\Participants;

use App\Http\Controllers\Controller;
use App\Models\Participant;
use App\Models\EmergencyContact;
use App\Services\Admin\Participants\UpdateEmergencyContactService;
use App\Services\Admin\Participants\CreateEmergencyContactService;
use App\Traits\AdminLogging;
use Illuminate\Support\Facades\Log;
use App\Http\Requests\Admin\Participants\UpdateEmergencyContactsRequest;
use App\Http\Requests\Admin\Participants\UpdateEmergencyContactRequest;
use App\Http\Requests\Admin\Participants\DeleteEmergencyContactRequest;

class ParticipantEmergencyContactsController extends Controller
{
    use AdminLogging;

    protected $updateEmergencyContactService;
    protected $createEmergencyContactService;

    public function __construct(
        UpdateEmergencyContactService $updateEmergencyContactService,
        CreateEmergencyContactService $createEmergencyContactService
    ) {
        $this->updateEmergencyContactService = $updateEmergencyContactService;
        $this->createEmergencyContactService = $createEmergencyContactService;
    }

    /**
     * Update emergency contacts of a participant.
     */
    public function store(UpdateEmergencyContactsRequest $request, Participant $participant)
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
    public function update(UpdateEmergencyContactRequest $request, Participant $participant)
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
    public function destroy(DeleteEmergencyContactRequest $request, Participant $participant)
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
}
