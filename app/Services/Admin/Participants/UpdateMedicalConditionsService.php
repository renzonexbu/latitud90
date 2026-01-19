<?php

namespace App\Services\Admin\Participants;

use App\Models\Participant;
use App\Traits\AdminLogging;
use Illuminate\Support\Facades\Log;

class UpdateMedicalConditionsService
{
    use AdminLogging;
    /**
     * Actualiza solo las condiciones médicas de un participante
     *
     * @param array $data
     * @param Participant $participant
     * @return Participant
     */
    public function execute(array $data, Participant $participant): Participant
    {
        try {
            // Capturar valores anteriores para logging
            $oldValues = [
                'allergies' => $participant->allergies,
                'intolerances' => $participant->intolerances,
                'dietary_restrictions' => $participant->dietary_restrictions,
            ];

            // Preparar solo los datos médicos para actualización
            $updateData = [
                'allergies' => $data['allergies'] ?? $participant->allergies,
                'intolerances' => $data['intolerances'] ?? $participant->intolerances,
                'dietary_restrictions' => $data['dietary_restrictions'] ?? $participant->dietary_restrictions,
            ];

            // Actualizar el participante
            $participant->update($updateData);

            // Admin logging
            $newValues = [
                'allergies' => $participant->allergies,
                'intolerances' => $participant->intolerances,
                'dietary_restrictions' => $participant->dietary_restrictions,
            ];

            $this->logUpdate(
                'participants',
                'Participant',
                $participant->id,
                "Condiciones médicas actualizadas del participante {$participant->name}",
                $oldValues,
                $newValues,
                [
                    'participant_name' => $participant->name,
                    'participant_document' => $participant->document_number
                ]
            );

            return $participant;

        } catch (\Exception $e) {
            throw $e;
        }
    }
} 