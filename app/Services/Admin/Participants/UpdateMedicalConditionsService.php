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
            Log::info('Iniciando actualización de condiciones médicas', [
                'participant_id' => $participant->id,
                'document_number' => $participant->document_number,
                'data_to_update' => $data
            ]);

            // Preparar solo los datos médicos para actualización
            $updateData = [
                'medical_conditions' => $data['medical_conditions'] ?? $participant->medical_conditions,
                'dietary_restrictions' => $data['dietary_restrictions'] ?? $participant->dietary_restrictions,
            ];

            // Actualizar el participante
            $participant->update($updateData);

            Log::info('Condiciones médicas actualizadas exitosamente', [
                'participant_id' => $participant->id,
                'document_number' => $participant->document_number,
                'updated_fields' => array_keys($updateData)
            ]);

            return $participant;

        } catch (\Exception $e) {
            Log::error('Error al actualizar condiciones médicas', [
                'participant_id' => $participant->id,
                'document_number' => $participant->document_number,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            throw $e;
        }
    }
} 