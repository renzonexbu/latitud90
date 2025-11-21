<?php

namespace App\Http\Controllers\Admin\Participants;

use App\Http\Controllers\Controller;
use App\Models\Participant;
use App\Services\Admin\Participants\ToggleProgramStatusService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;

class ParticipantProgramController extends Controller
{
    public function __construct(
        private ToggleProgramStatusService $toggleService
    ) {}

    /**
     * Toggle el status del programa (pending_payment <-> cancelled)
     *
     * @param Participant $participant
     * @param int $programId
     * @return RedirectResponse
     */
    public function toggleProgramStatus(Participant $participant, int $programId): RedirectResponse
    {
        try {
            $result = $this->toggleService->execute($participant->id, $programId);

            Log::info('Status del programa toggleado', [
                'participant_id' => $participant->id,
                'program_id' => $programId,
                'previous_status' => $result['previous_status'],
                'new_status' => $result['new_status'],
                'admin_user_id' => auth()->id()
            ]);

            return redirect()
                ->back()
                ->with('success', $result['message']);

        } catch (\Exception $e) {
            Log::error('Error al toggle status del programa', [
                'participant_id' => $participant->id,
                'program_id' => $programId,
                'error' => $e->getMessage(),
                'admin_user_id' => auth()->id()
            ]);

            return redirect()
                ->back()
                ->with('error', $e->getMessage());
        }
    }
}
