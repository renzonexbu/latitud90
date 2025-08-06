<?php

namespace App\Services\Admin\Participants;

use App\Models\Participant;
use Illuminate\Support\Facades\Log;

class UpdateParticipatService
{
    /**
     * Actualiza los datos personales de un participante
     *
     * @param array $data
     * @param Participant $participant
     * @return Participant
     */
    public function execute(array $data, Participant $participant): Participant
    {
        try {
            Log::info('Iniciando actualización de participante', [
                'participant_id' => $participant->id,
                'document_number' => $participant->document_number,
                'data_to_update' => $data
            ]);

            // Validar que el RUT no se esté intentando modificar
            if (isset($data['document_number']) && $data['document_number'] !== $participant->document_number) {
                throw new \Exception('El RUT no se puede modificar');
            }

            // Preparar los datos para actualización
            $updateData = [
                'first_name' => $data['first_name'] ?? $participant->first_name,
                'last_name' => $data['last_name'] ?? $participant->last_name,
                'email' => $data['email'] ?? $participant->email,
                'code_phone' => $data['code_phone'] ?? $participant->code_phone,
                'phone' => $data['phone'] ?? $participant->phone,
                'birth_date' => $data['birth_date'] ?? $participant->birth_date,
                'medical_conditions' => $data['medical_conditions'] ?? $participant->medical_conditions,
                'dietary_restrictions' => $data['dietary_restrictions'] ?? $participant->dietary_restrictions,
            ];

            // Actualizar el participante
            $participant->update($updateData);

            Log::info('Participante actualizado exitosamente', [
                'participant_id' => $participant->id,
                'document_number' => $participant->document_number,
                'updated_fields' => array_keys($updateData)
            ]);

            return $participant;

        } catch (\Exception $e) {
            Log::error('Error al actualizar participante', [
                'participant_id' => $participant->id,
                'document_number' => $participant->document_number,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            throw $e;
        }
    }
}
