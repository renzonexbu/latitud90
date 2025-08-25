<?php

namespace App\Services\Admin\Participants;

use App\Models\Participant;
use Illuminate\Support\Facades\Log;

class ToggleStatusService
{
    /**
     * Cambiar el estado de un participante entre confirmed y pending_payment
     *
     * @param Participant $participant
     * @return array
     */
    public function execute(Participant $participant): array
    {
        try {
            $oldStatus = $participant->status;
            $newStatus = $participant->status === 'confirmed' ? 'pending_payment' : 'confirmed';
            
            $participant->update(['status' => $newStatus]);

            Log::info('Estado de participante cambiado', [
                'participant_id' => $participant->id,
                'participant_name' => $participant->first_name . ' ' . $participant->first_last_name,
                'old_status' => $oldStatus,
                'new_status' => $newStatus
            ]);

            return [
                'success' => true,
                'old_status' => $oldStatus,
                'new_status' => $newStatus
            ];
        } catch (\Exception $e) {
            Log::error('Error al cambiar estado de participante', [
                'participant_id' => $participant->id,
                'error' => $e->getMessage()
            ]);

            throw $e;
        }
    }
}
