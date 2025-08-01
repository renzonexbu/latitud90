<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Participant;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ParticipantsController extends Controller
{
    /**
     * Display a listing of participants.
     */
    public function index()
    {
        $participants = Participant::with(['course', 'course.program'])
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return Inertia::render('Admin/Participants/Index', [
            'participants' => $participants,
            'filters' => request()->only(['search', 'institution', 'level', 'program', 'status'])
        ]);
    }

    /**
     * Show the form for creating a new participant.
     */
    public function create()
    {
        return Inertia::render('Admin/Participants/Create');
    }

    /**
     * Store a newly created participant in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'course_id' => 'required|exists:courses,id',
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'code_phone' => 'required|string|max:10',
            'phone' => 'required|string|max:20',
            'document_type' => 'required|string|max:50',
            'document_number' => 'required|string|max:20',
            'country' => 'required|string|max:100',
            'birth_date' => 'required|date',
            'address' => 'nullable|string',
            'dietary_restrictions' => 'nullable|string',
            'medical_conditions' => 'nullable|string',
            'individual_price' => 'required|numeric|min:0',
            'price_adjustments' => 'nullable|numeric',
            'adjustment_reason' => 'nullable|string',
        ]);

        $validated['status'] = 'pending_payment';
        $validated['registration_date'] = now();

        Participant::create($validated);

        return redirect()->route('admin.participants.index')
            ->with('message', 'Participante creado exitosamente.');
    }

    /**
     * Display the specified participant.
     */
    public function show(Participant $participant)
    {
        $participant->load(['course', 'course.program', 'emergencyContacts', 'medicalConditions']);
        
        return Inertia::render('Admin/Participants/Show', [
            'participant' => $participant
        ]);
    }

    /**
     * Show the form for editing the specified participant.
     */
    public function edit(Participant $participant)
    {
        $participant->load(['course']);
        
        return Inertia::render('Admin/Participants/Edit', [
            'participant' => $participant
        ]);
    }

    /**
     * Update the specified participant in storage.
     */
    public function update(Request $request, Participant $participant)
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'code_phone' => 'required|string|max:10',
            'phone' => 'required|string|max:20',
            'document_type' => 'required|string|max:50',
            'document_number' => 'required|string|max:20',
            'country' => 'required|string|max:100',
            'birth_date' => 'required|date',
            'address' => 'nullable|string',
            'dietary_restrictions' => 'nullable|string',
            'medical_conditions' => 'nullable|string',
            'individual_price' => 'required|numeric|min:0',
            'price_adjustments' => 'nullable|numeric',
            'adjustment_reason' => 'nullable|string',
            'status' => 'required|in:pending_payment,confirmed,cancelled',
        ]);

        $participant->update($validated);

        return redirect()->route('admin.participants.index')
            ->with('message', 'Participante actualizado exitosamente.');
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