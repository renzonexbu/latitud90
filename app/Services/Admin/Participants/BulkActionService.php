<?php

namespace App\Services\Admin\Participants;

use App\Models\Participant;
use App\Traits\AdminLogging;
use Illuminate\Support\Facades\Log;

class BulkActionService
{
    use AdminLogging;
    /**
     * Ejecutar acciones masivas en participantes
     *
     * @param string $action
     * @param array $participantIds
     * @return array
     */
    public function execute(string $action, array $participantIds): array
    {
        try {
            $participants = Participant::whereIn('id', $participantIds);
            $affectedCount = $participants->count();

            switch ($action) {
                case 'delete':
                    $participants->update(['is_active' => false]);
                    $message = 'Participantes desactivados exitosamente.';
                    break;
                case 'activate':
                    $participants->update(['is_active' => true]);
                    $message = 'Participantes activados exitosamente.';
                    break;
                case 'confirm':
                    $participants->update(['status' => 'confirmed']);
                    $message = 'Participantes confirmados exitosamente.';
                    break;
                case 'cancel':
                    $participants->update(['status' => 'cancelled']);
                    $message = 'Participantes cancelados exitosamente.';
                    break;
                default:
                    throw new \Exception('Acción no válida');
            }

            Log::info('Acción masiva ejecutada en participantes', [
                'action' => $action,
                'affected_count' => $affectedCount,
                'participant_ids' => $participantIds
            ]);

            // Admin logging
            $this->logAction(
                'bulk_' . $action,
                'participants',
                "Acción masiva '{$action}' ejecutada en {$affectedCount} participantes",
                'Participant',
                null,
                null,
                ['action' => $action],
                [
                    'affected_count' => $affectedCount,
                    'participant_ids' => $participantIds
                ]
            );

            return [
                'success' => true,
                'message' => $message,
                'affected_count' => $affectedCount
            ];
        } catch (\Exception $e) {
            Log::error('Error al ejecutar acción masiva en participantes', [
                'action' => $action,
                'participant_ids' => $participantIds,
                'error' => $e->getMessage()
            ]);

            throw $e;
        }
    }
}
