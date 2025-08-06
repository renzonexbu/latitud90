<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CreateParticipantRequest;
use App\Models\Course;
use App\Models\Participant;
use App\Models\Institution;
use App\Models\EmergencyContact;
use App\Services\Admin\Participants\CreateParticipantService;
use App\Services\Admin\Participants\UpdateParticipatService;
use App\Services\Admin\Participants\UpdateMedicalConditionsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class ParticipantsController extends Controller
{
    protected $createParticipantService;
    protected $updateParticipantService;
    protected $updateMedicalConditionsService;

    public function __construct(
        CreateParticipantService $createParticipantService,
        UpdateParticipatService $updateParticipantService,
        UpdateMedicalConditionsService $updateMedicalConditionsService
    ) {
        $this->createParticipantService = $createParticipantService;
        $this->updateParticipantService = $updateParticipantService;
        $this->updateMedicalConditionsService = $updateMedicalConditionsService;
    }

    /**
     * Display a listing of participants.
     */
    public function index()
    {
        $participants = Participant::with(['courses', 'courses.institution', 'courses.program'])
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        // Obtener todos los participantes para los filtros (sin paginación)
        $allParticipants = Participant::with(['courses', 'courses.institution', 'courses.program'])
            ->orderBy('created_at', 'desc')
            ->get();

        $courses = Course::with(['program', 'institution'])
            ->where('status', 'active')
            ->orderBy('institution_id')
            ->get();

        $institutions = Institution::active()
            ->orderBy('name')
            ->get();

        return Inertia::render('Admin/Participants/Index', [
            'participants' => $participants,
            'allParticipants' => $allParticipants,
            'courses' => $courses,
            'institutions' => $institutions,
            'filters' => request()->only(['search', 'institution', 'level', 'program', 'status'])
        ]);
    }

    /**
     * Show the form for creating a new participant.
     */
    public function create()
    {
        $courses = \App\Models\Course::with(['program', 'institution'])
            ->where('status', 'active')
            ->orderBy('institution_id')
            ->get();

        $institutions = Institution::active()
            ->orderBy('name')
            ->get();

        return Inertia::render('Admin/Participants/Create', [
            'courses' => $courses,
            'institutions' => $institutions
        ]);
    }

    /**
     * Store a newly created participant in storage.
     */
    public function store(CreateParticipantRequest $request)
    {
        try {
            // Separar los datos del participante
            $participantData = array_intersect_key($request->validated(), array_flip([
                'course_id', 'first_name', 'last_name', 'email', 'code_phone', 'phone',
                'document_type', 'document_number', 'country', 'birth_date', 'address',
                'dietary_restrictions', 'medical_conditions', 'individual_price',
                'price_adjustments', 'adjustment_reason'
            ]));

            // Asegurar que medical_conditions sea un string, no un array
            if (isset($participantData['medical_conditions']) && is_array($participantData['medical_conditions'])) {
                $participantData['medical_conditions'] = implode(', ', array_column($participantData['medical_conditions'], 'description'));
            }

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
    public function edit(Participant $participant)
    {
        $participant->load(['courses', 'courses.institution', 'courses.program', 'emergencyContacts']);
        
        // Buscar todos los programas relacionados al RUT del participante
        $participantPrograms = \App\Models\Program::whereHas('course.participants', function($query) use ($participant) {
            $query->where('document_number', $participant->document_number)
                  ->where('document_type', $participant->document_type)
                  ->where('country', $participant->country);
        })->with(['course', 'course.institution'])->get();
        
        return Inertia::render('Admin/Participants/Edit', [
            'participant' => $participant,
            'participantPrograms' => $participantPrograms
        ]);
    }

    /**
     * Update the specified participant in storage.
     */
    public function update(Request $request, Participant $participant)
    {
        try {
            $validated = $request->validate([
                'first_name' => 'required|string|max:255',
                'last_name' => 'required|string|max:255',
                'email' => 'nullable|email|max:255',
                'code_phone' => 'nullable|string|max:10',
                'phone' => 'nullable|string|max:20',
                'document_number' => 'required|string|max:20',
                'birth_date' => 'nullable|date',
            ]);

            // Usar el servicio para actualizar
            $this->updateParticipantService->execute($validated, $participant);

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
    public function updateMedicalConditions(Request $request, Participant $participant)
    {
        try {
            $validated = $request->validate([
                'medical_conditions' => 'nullable|string',
                'dietary_restrictions' => 'nullable|string',
            ]);

            // Usar el servicio específico para condiciones médicas
            $this->updateMedicalConditionsService->execute($validated, $participant);

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
    public function updateEmergencyContacts(Request $request, Participant $participant)
    {
        try {
            $validated = $request->validate([
                'emergency_contacts' => 'required|string', // JSON string
            ]);

            $emergencyContactsData = json_decode($validated['emergency_contacts'], true);

            if (!is_array($emergencyContactsData)) {
                throw new \Exception('Formato de datos inválido');
            }

            // Crear nuevos contactos de emergencia
            foreach ($emergencyContactsData as $contactData) {
                EmergencyContact::create([
                    'participant_id' => $participant->id,
                    'first_name' => $contactData['first_name'],
                    'last_name' => $contactData['last_name'],
                    'email' => $contactData['email'],
                    'code_phone' => $contactData['code_phone'],
                    'phone' => $contactData['phone'],
                    'country' => $contactData['country'],
                    'birth_date' => $contactData['birth_date'] ?? null,
                    'address' => $contactData['address'] ?? null,
                    'relationship' => $contactData['relationship'],
                ]);
            }

            return back()->with('success', 'Contacto de emergencia agregado exitosamente.');

        } catch (\Exception $e) {
            Log::error('Error en controlador al actualizar contactos de emergencia', [
                'participant_id' => $participant->id,
                'error' => $e->getMessage()
            ]);

            return back()->withErrors(['error' => 'Error al agregar el contacto de emergencia: ' . $e->getMessage()]);
        }
    }

    /**
     * Update a specific emergency contact.
     */
    public function updateEmergencyContact(Request $request, Participant $participant)
    {
        try {
            $validated = $request->validate([
                'contact_id' => 'required|exists:emergency_contact,id',
                'first_name' => 'required|string|max:255',
                'last_name' => 'required|string|max:255',
                'email' => 'required|email|max:255',
                'code_phone' => 'required|string|max:10',
                'phone' => 'required|string|max:20',
                'country' => 'required|string|max:100',
                'birth_date' => 'nullable|date|before:today',
                'address' => 'nullable|string',
                'relationship' => 'required|string|max:100',
            ]);

            $contact = EmergencyContact::findOrFail($validated['contact_id']);
            
            // Verificar que el contacto pertenece al participante
            if ($contact->participant_id !== $participant->id) {
                throw new \Exception('El contacto no pertenece a este participante');
            }

            $contact->update([
                'first_name' => $validated['first_name'],
                'last_name' => $validated['last_name'],
                'email' => $validated['email'],
                'code_phone' => $validated['code_phone'],
                'phone' => $validated['phone'],
                'country' => $validated['country'],
                'birth_date' => $validated['birth_date'] ?? null,
                'address' => $validated['address'] ?? null,
                'relationship' => $validated['relationship'],
            ]);

            return back()->with('success', 'Contacto de emergencia actualizado exitosamente.');

        } catch (\Exception $e) {
            Log::error('Error en controlador al actualizar contacto de emergencia', [
                'participant_id' => $participant->id,
                'contact_id' => $request->contact_id ?? null,
                'error' => $e->getMessage()
            ]);

            return back()->withErrors(['error' => 'Error al actualizar el contacto de emergencia: ' . $e->getMessage()]);
        }
    }

    /**
     * Delete a specific emergency contact.
     */
    public function deleteEmergencyContact(Request $request, Participant $participant)
    {
        try {
            $validated = $request->validate([
                'contact_id' => 'required|exists:emergency_contact,id',
            ]);

            $contact = EmergencyContact::findOrFail($validated['contact_id']);
            
            // Verificar que el contacto pertenece al participante
            if ($contact->participant_id !== $participant->id) {
                throw new \Exception('El contacto no pertenece a este participante');
            }

            // Verificar que no sea el último contacto de emergencia
            $totalContacts = EmergencyContact::where('participant_id', $participant->id)->count();
            if ($totalContacts <= 1) {
                throw new \Exception('No se puede eliminar el último contacto de emergencia. Debe mantener al menos un contacto.');
            }

            $contact->delete();

            return back()->with('success', 'Contacto de emergencia eliminado exitosamente.');

        } catch (\Exception $e) {
            Log::error('Error en controlador al eliminar contacto de emergencia', [
                'participant_id' => $participant->id,
                'contact_id' => $request->contact_id ?? null,
                'error' => $e->getMessage()
            ]);

            return back()->withErrors(['error' => 'Error al eliminar el contacto de emergencia: ' . $e->getMessage()]);
        }
    }

    /**
     * Remove the specified participant from storage.
     */
    public function destroy(Participant $participant)
    {
        $participant->delete();

        return redirect()->route('admin.participants.index')
            ->with('message', 'Participante eliminado exitosamente.');
    }

    /**
     * Toggle participant status.
     */
    public function toggleStatus(Participant $participant)
    {
        $newStatus = $participant->status === 'confirmed' ? 'pending_payment' : 'confirmed';
        $participant->update(['status' => $newStatus]);

        return back()->with('message', 'Estado del participante actualizado.');
    }

    /**
     * Perform bulk actions on participants.
     */
    public function bulkAction(Request $request)
    {
        $validated = $request->validate([
            'action' => 'required|in:delete,confirm,cancel',
            'participant_ids' => 'required|array',
            'participant_ids.*' => 'exists:participants,id'
        ]);

        $participants = Participant::whereIn('id', $validated['participant_ids']);

        switch ($validated['action']) {
            case 'delete':
                $participants->delete();
                $message = 'Participantes eliminados exitosamente.';
                break;
            case 'confirm':
                $participants->update(['status' => 'confirmed']);
                $message = 'Participantes confirmados exitosamente.';
                break;
            case 'cancel':
                $participants->update(['status' => 'cancelled']);
                $message = 'Participantes cancelados exitosamente.';
                break;
        }

        return back()->with('message', $message);
    }

    /**
     * Get participant payments.
     */
    public function payments(Participant $participant)
    {
        $payments = $participant->payments()->orderBy('created_at', 'desc')->get();
        
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