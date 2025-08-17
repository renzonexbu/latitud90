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
use App\Http\Requests\Admin\Participants\UpdateParticipantRequest;
use App\Http\Requests\Admin\Participants\UpdateMedicalConditionsRequest;
use App\Http\Requests\Admin\Participants\UpdateEmergencyContactsRequest;
use App\Http\Requests\Admin\Participants\UpdateEmergencyContactRequest;
use App\Http\Requests\Admin\Participants\DeleteEmergencyContactRequest;
use App\Services\Admin\Participants\UpdateEmergencyContactService;
use Illuminate\Support\Facades\DB;

class ParticipantsController extends Controller
{
    protected $createParticipantService;
    protected $updateParticipantService;
    protected $updateMedicalConditionsService;
    protected $updateEmergencyContactService;

    public function __construct(
        CreateParticipantService $createParticipantService,
        UpdateParticipatService $updateParticipantService,
        UpdateMedicalConditionsService $updateMedicalConditionsService,
        UpdateEmergencyContactService $updateEmergencyContactService
    ) {
        $this->createParticipantService = $createParticipantService;
        $this->updateParticipantService = $updateParticipantService;
        $this->updateMedicalConditionsService = $updateMedicalConditionsService;
        $this->updateEmergencyContactService = $updateEmergencyContactService;
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

        // Dataset de inscripciones (una fila por participante-programa) + montos pagados
        // Usar la misma lógica que ProgramService del cliente
        $enrollments = DB::table('participants as p')
            ->leftJoin('participant_program as pp', 'pp.participant_id', '=', 'p.id')
            ->leftJoin('programs as pr', 'pr.id', '=', 'pp.program_id')
            ->leftJoin('courses as c', 'c.program_id', '=', 'pr.id')
            ->leftJoin('institutions as i', 'i.id', '=', 'c.institution_id')
            ->leftJoin('orders as o', function($join) {
                $join->on('o.participant_id', '=', 'p.id')
                     ->on('o.program_id', '=', 'pr.id');
            })
            ->leftJoin('orders_detail as od', function ($join) {
                $join->on('od.order_id', '=', 'o.id')
                    ->where('od.is_paid', true);
            })
            ->groupBy([
                'p.id', 'p.first_name', 'p.last_name', 'p.document_number',
                'pp.id', 'pp.enrollment_code', 'pp.individual_price', 'pp.status',
                'pr.id', 'pr.code', 'pr.name', 'pr.destination', 'pr.year',
                'c.education_level', 'c.course_number',
                'i.name',
            ])
            ->select([
                'p.id as participant_id',
                'p.first_name',
                'p.last_name',
                'p.document_number',
                'pp.id as participant_program_id',
                'pp.enrollment_code',
                'pp.individual_price as total_due',
                'pp.status as enrollment_status',
                'pr.id as program_id',
                'pr.code as program_code',
                'pr.name as program_name',
                'pr.destination as program_destination',
                'pr.year as program_year',
                'c.education_level',
                'c.course_number',
                'i.name as institution_name',
                DB::raw('COALESCE(SUM(od.amount), 0) as paid_amount'),
            ])
            ->orderByDesc('pp.created_at')
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
            'enrollments' => $enrollments,
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
                'dietary_restrictions', 'medical_conditions'
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
            $query->where('participants.id', $participant->id);
        })
        ->with(['course' => function($q) use ($participant) {
            $q->with(['institution', 'participants' => function($qp) use ($participant) {
                $qp->where('participants.id', $participant->id);
            }]);
        }])
        ->get()
        ->map(function($program) use ($participant) {
            $pivotParticipant = optional($program->course)->participants->first();
            $individual = optional($pivotParticipant)->pivot->individual_price ?? $participant->individual_price ?? null;
            $adjust = optional($pivotParticipant)->pivot->price_adjustments ?? 0;
            $totalDue = is_null($individual) ? null : (float) $individual + (float) $adjust;
            // Pagos aprobados/completados del participante para este programa
            $paidAmount = (float) \App\Models\Payment::whereHas('order', function ($q) use ($participant, $program) {
                    $q->where('participant_id', $participant->id)
                      ->where('program_id', $program->id);
                })
                ->whereIn('status', ['approved', 'completed'])
                ->sum('amount');
            $paidAmount = round($paidAmount, 2);
            $balance = is_null($totalDue) ? null : max(round($totalDue - $paidAmount, 2), 0);
            $paymentPercentage = (!is_null($totalDue) && $totalDue > 0)
                ? round(($paidAmount / $totalDue) * 100, 0)
                : 0;

            $array = $program->toArray();
            $array['participant_amount'] = $individual; // precio base por participante
            $array['participant_adjustments'] = $adjust; // ajuste del pivote
            $array['participant_total_due'] = $totalDue; // total a pagar (base + ajuste)
            $array['paidAmount'] = $paidAmount;
            $array['participant_balance'] = $balance;
            $array['paymentPercentage'] = $paymentPercentage;
            return $array;
        });

        return Inertia::render('Admin/Participants/Edit', [
            'participant' => $participant,
            'participantPrograms' => $participantPrograms,
        ]);
    }

    /**
     * Update the specified participant in storage.
     */
    public function update(UpdateParticipantRequest $request, Participant $participant)
    {
        try {
            $this->updateParticipantService->execute($request->validated(), $participant);

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
            $this->updateMedicalConditionsService->execute($request->validated(), $participant);

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
    public function updateEmergencyContact(UpdateEmergencyContactRequest $request, Participant $participant)
    {
        try {
            $this->updateEmergencyContactService->update($request->validated(), $participant);

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
    public function deleteEmergencyContact(DeleteEmergencyContactRequest $request, Participant $participant)
    {
        try {
            $this->updateEmergencyContactService->delete($request->validated(), $participant);

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