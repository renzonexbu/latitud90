<?php

namespace App\Services\Admin\Participants;

use App\Models\Participant;
use Illuminate\Support\Facades\Log;

class ToggleActiveStatusService
{
    /**
     * Cambiar el estado activo/inactivo de un participante
     *
     * @param Participant $participant
     * @return array
     */
    public function execute(Participant $participant): array
    {
        try {
            // Cambiar el estado activo/inactivo
            $newStatus = !$participant->is_active;
            $participant->update(['is_active' => $newStatus]);

            $action = $newStatus ? 'activado' : 'desactivado';
            $participantName = $participant->first_name . ' ' . $participant->first_last_name;

            Log::info('Estado de participante cambiado', [
                'participant_id' => $participant->id,
                'participant_name' => $participantName,
                'new_status' => $newStatus,
                'action' => $action
            ]);

            return [
                'success' => true,
                'action' => $action,
                'participant_name' => $participantName,
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
