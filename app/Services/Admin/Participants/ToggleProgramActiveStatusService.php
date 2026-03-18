<?php

namespace App\Services\Admin\Participants;

use App\Models\ParticipantProgram;
use App\Models\ProgramCourse;
use App\Models\Participant;
use App\Traits\AdminLogging;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Exception;

class ToggleProgramActiveStatusService
{
    use AdminLogging;

    /**
     * Toggle el estado is_active del participante en un programa específico
     *
     * @param int $participantId
     * @param int $programCourseId El ID del ProgramCourse
     * @param string|null $comment Comentario opcional explicando el cambio de estado
     * @return array
     * @throws Exception
     */
    public function execute(int $participantId, int $programCourseId, ?string $comment = null): array
    {
        return DB::transaction(function () use ($participantId, $programCourseId, $comment) {
            // Obtener el ProgramCourse
            $programCourse = ProgramCourse::findOrFail($programCourseId);

            // Obtener el Participant
            $participant = Participant::findOrFail($participantId);

            // Generar el enrollment_code específico
            $programCodePart = explode('-', $programCourse->code)[0];
            $enrollmentCode = $participant->document_number . '-' . $programCodePart;

            // Buscar la relación participante-programa usando el enrollment_code específico
            $participantProgram = ParticipantProgram::where('participant_id', $participantId)
                ->where('enrollment_code', $enrollmentCode)
                ->first();

            if (!$participantProgram) {
                throw new Exception('La relación entre el participante y el programa no existe.');
            }

            // Toggle el estado is_active
            $previousStatus = $participantProgram->is_active ?? true;
            $newStatus = !$previousStatus;

            $participantProgram->update([
                'is_active' => $newStatus
            ]);

            $action = $newStatus ? 'reactivado' : 'dado de baja';
            $message = $newStatus
                ? 'Participante reactivado en el programa exitosamente.'
                : 'Participante dado de baja del programa exitosamente.';

            $participantName = $participant->first_name . ' ' . $participant->first_last_name;

            Log::info('Estado is_active del programa cambiado', [
                'participant_id' => $participantId,
                'participant_name' => $participantName,
                'program_course_id' => $programCourseId,
                'program_code' => $programCourse->code,
                'previous_status' => $previousStatus,
                'new_status' => $newStatus,
                'action' => $action,
                'comment' => $comment,
                'changed_by' => Auth::id()
            ]);

            // Admin logging
            $this->logStatusChange(
                'participants',
                'ParticipantProgram',
                $participantProgram->id,
                $previousStatus ? 'activo' : 'baja',
                $newStatus ? 'activo' : 'baja',
                "Participante {$participantName} {$action} en programa {$programCourse->code}",
                [
                    'participant_id' => $participantId,
                    'participant_name' => $participantName,
                    'program_course_id' => $programCourseId,
                    'program_code' => $programCourse->code,
                    'enrollment_code' => $enrollmentCode,
                    'comment' => $comment
                ]
            );

            // Si se dio de baja y no tiene ningún otro programa activo, desactivar al participante
            $participantDeactivated = false;
            if (!$newStatus) {
                $activeProgramsCount = ParticipantProgram::where('participant_id', $participantId)
                    ->where('is_active', true)
                    ->count();

                if ($activeProgramsCount === 0 && $participant->is_active) {
                    $participant->update(['is_active' => false]);
                    $participantDeactivated = true;

                    Log::info('Participante desactivado automáticamente (sin programas activos)', [
                        'participant_id' => $participantId,
                        'participant_name' => $participantName,
                        'changed_by' => Auth::id()
                    ]);

                    $this->logStatusChange(
                        'participants',
                        'Participant',
                        $participant->id,
                        'activo',
                        'baja',
                        "Participante {$participantName} desactivado automáticamente (sin programas activos)",
                        [
                            'participant_id' => $participantId,
                            'participant_name' => $participantName,
                            'reason' => 'no_active_programs',
                            'comment' => $comment
                        ]
                    );
                }
            }

            // Si se reactivó en un programa, reactivar al participante si estaba inactivo
            if ($newStatus && !$participant->is_active) {
                $participant->update(['is_active' => true]);

                Log::info('Participante reactivado automáticamente al reactivar en programa', [
                    'participant_id' => $participantId,
                    'participant_name' => $participantName,
                    'changed_by' => Auth::id()
                ]);
            }

            $finalMessage = $message;
            if ($participantDeactivated) {
                $finalMessage .= ' El participante fue desactivado automáticamente al no tener programas activos.';
            }

            return [
                'success' => true,
                'message' => $finalMessage,
                'action' => $action,
                'participant_program_id' => $participantProgram->id,
                'previous_status' => $previousStatus,
                'new_status' => $newStatus,
                'participant_deactivated' => $participantDeactivated,
            ];
        });
    }
}
