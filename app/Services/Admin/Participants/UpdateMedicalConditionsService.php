<?php

namespace App\Services\Admin\Participants;

use App\Models\Participant;
use Illuminate\Support\Facades\Log;

class UpdateMedicalConditionsService
{
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

            // Preparar solo los datos médicos para actualización
            $updateData = [
                'allergies' => $data['allergies'] ?? $participant->allergies,
                'intolerances' => $data['intolerances'] ?? $participant->intolerances,
                'dietary_restrictions' => $data['dietary_restrictions'] ?? $participant->dietary_restrictions,
            ];

            // Actualizar el participante
            $participant->update($updateData);

            return $participant;

        } catch (\Exception $e) {
            throw $e;
        }
    }
} 