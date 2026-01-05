<?php

namespace App\Http\Controllers\Admin\Participants;

use App\Http\Controllers\Controller;
use App\Models\Participant;
use App\Services\Admin\Participants\ToggleActiveStatusService;
use App\Services\Admin\Participants\ToggleStatusService;
use App\Services\Admin\Participants\GetInactiveParticipantsService;
use App\Services\Admin\Participants\BulkActionService;
use App\Traits\AdminLogging;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ParticipantStatusController extends Controller
{
    use AdminLogging;

    protected $toggleActiveStatusService;
    protected $toggleStatusService;
    protected $getInactiveParticipantsService;
    protected $bulkActionService;

    public function __construct(
        ToggleActiveStatusService $toggleActiveStatusService,
        ToggleStatusService $toggleStatusService,
        GetInactiveParticipantsService $getInactiveParticipantsService,
        BulkActionService $bulkActionService
    ) {
        $this->toggleActiveStatusService = $toggleActiveStatusService;
        $this->toggleStatusService = $toggleStatusService;
        $this->getInactiveParticipantsService = $getInactiveParticipantsService;
        $this->bulkActionService = $bulkActionService;
    }

    /**
     * Toggle participant active status (soft delete).
     */
    public function destroy(Request $request, Participant $participant)
    {
        // Obtener el comentario del request (opcional)
        $comment = $request->input('comment');

        $result = $this->toggleActiveStatusService->execute($participant, $comment);

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
}
