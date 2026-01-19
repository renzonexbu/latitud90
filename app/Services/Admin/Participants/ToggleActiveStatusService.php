<?php

namespace App\Services\Admin\Participants;

use App\Models\Participant;
use App\Models\ParticipantStatusHistory;
use App\Traits\AdminLogging;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class ToggleActiveStatusService
{
    use AdminLogging;
    /**
     * Cambiar el estado activo/inactivo de un participante
     *
     * @param Participant $participant
     * @param string|null $comment Comentario opcional explicando el cambio de estado
     * @return array
     */
    public function execute(Participant $participant, ?string $comment = null): array
    {
        try {
            // Capturar el estado anterior antes de cambiarlo
            $previousStatus = $participant->is_active;

            // Cambiar el estado activo/inactivo
            $newStatus = !$participant->is_active;
            $participant->update(['is_active' => $newStatus]);

            // Guardar el historial de cambio de estado
            ParticipantStatusHistory::create([
                'participant_id' => $participant->id,
                'previous_status' => $previousStatus,
                'new_status' => $newStatus,
                'comment' => $comment,
                'changed_by' => Auth::id(),
            ]);

            $action = $newStatus ? 'activado' : 'desactivado';
            $participantName = $participant->first_name . ' ' . $participant->first_last_name;

            Log::info('Estado de participante cambiado', [
                'participant_id' => $participant->id,
                'participant_name' => $participantName,
                'previous_status' => $previousStatus,
                'new_status' => $newStatus,
                'action' => $action,
                'comment' => $comment,
                'changed_by' => Auth::id()
            ]);

            // Admin logging
            $this->logStatusChange(
                'participants',
                'Participant',
                $participant->id,
                $previousStatus ? 'activo' : 'inactivo',
                $newStatus ? 'activo' : 'inactivo',
                "Estado del participante {$participantName} cambiado a {$action}",
                [
                    'participant_name' => $participantName,
                    'participant_document' => $participant->document_number,
                    'comment' => $comment
                ]
            );

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
