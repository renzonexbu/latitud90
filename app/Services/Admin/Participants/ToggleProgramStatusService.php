<?php

namespace App\Services\Admin\Participants;

use App\Models\ParticipantProgram;
use App\Models\ProgramCourse;
use App\Traits\AdminLogging;
use Illuminate\Support\Facades\DB;
use Exception;

class ToggleProgramStatusService
{
    use AdminLogging;
    /**
     * Toggle el status del programa entre pending_payment y cancelled
     *
     * @param int $participantId
     * @param int $programCourseId El ID del ProgramCourse
     * @return array
     * @throws Exception
     */
    public function execute(int $participantId, int $programCourseId): array
    {
        return DB::transaction(function () use ($participantId, $programCourseId) {
            // Obtener el ProgramCourse
            $programCourse = ProgramCourse::findOrFail($programCourseId);

            // Obtener el Participant para generar el enrollment_code
            $participant = \App\Models\Participant::findOrFail($participantId);

            // Generar el enrollment_code específico
            // Extraer solo la parte numérica del código (antes del guión)
            $programCodePart = explode('-', $programCourse->code)[0];
            $enrollmentCode = $participant->document_number . '-' . $programCodePart;

            // Buscar la relación participante-programa usando el enrollment_code específico
            $participantProgram = ParticipantProgram::where('participant_id', $participantId)
                ->where('enrollment_code', $enrollmentCode)
                ->first();

            if (!$participantProgram) {
                throw new Exception('La relación entre el participante y el programa no existe.');
            }

            // VALIDACIÓN: Verificar si hay suscripción activa antes de permitir cancelación
            if ($participantProgram->status !== 'cancelled') {
                $activeSubscription = \App\Models\ProgramSubscription::where('participant_id', $participantId)
                    ->where('program_id', $programCourseId)
                    ->where('status', 'ACTIVA')
                    ->first();

                if ($activeSubscription) {
                    throw new Exception('No se puede cancelar este programa porque el participante tiene una suscripción activa. Primero debe cancelar la suscripción.');
                }
            }

            // Toggle entre pending_payment y cancelled
            $newStatus = $participantProgram->status === 'cancelled' ? 'pending_payment' : 'cancelled';
            $previousStatus = $participantProgram->status;

            $participantProgram->update([
                'status' => $newStatus
            ]);

            $message = $newStatus === 'cancelled'
                ? 'Participante desvinculado del programa exitosamente.'
                : 'Participante re-vinculado al programa exitosamente.';

            // Admin logging
            $this->logStatusChange(
                'participants',
                'ParticipantProgram',
                $participantProgram->id,
                $previousStatus,
                $newStatus,
                "Estado del programa cambiado para participante {$participant->name}: {$previousStatus} → {$newStatus}",
                [
                    'participant_id' => $participantId,
                    'participant_name' => $participant->name,
                    'program_course_id' => $programCourseId,
                    'program_code' => $programCourse->code,
                    'enrollment_code' => $enrollmentCode
                ]
            );

            return [
                'success' => true,
                'message' => $message,
                'participant_program_id' => $participantProgram->id,
                'previous_status' => $previousStatus,
                'new_status' => $newStatus
            ];
        });
    }
}
