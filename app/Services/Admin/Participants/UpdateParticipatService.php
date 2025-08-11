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

            // El precio y ajustes se gestionan en el pivote, no en la tabla participants

            // Actualizar el participante
            $participant->update($updateData);

            // Si se especificó un curso, aplicar ajuste/individual_price al pivote de ese curso
            if (!empty($data['pivot_course_id'])) {
                $pivotUpdate = [];
                if (array_key_exists('individual_price', $data)) {
                    $pivotUpdate['individual_price'] = $data['individual_price'];
                }
                if (array_key_exists('price_adjustments', $data)) {
                    $pivotUpdate['price_adjustments'] = $data['price_adjustments'];
                }
                if (array_key_exists('adjustment_reason', $data)) {
                    $pivotUpdate['adjustment_reason'] = $data['adjustment_reason'];
                }
                if (!empty($pivotUpdate)) {
                    $participant->courses()->updateExistingPivot((int) $data['pivot_course_id'], $pivotUpdate);
                }
            }

            

            return $participant;

        } catch (\Exception $e) {
            throw $e;
        }
    }
}
