<?php

namespace App\Services\Admin\Participants;

use App\Models\Participant;
use App\Models\EmergencyContact;
use App\Models\MedicalCondition;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CreateParticipantService
{
    /**
     * Crear un nuevo participante con sus contactos de emergencia y condiciones médicas
     *
     * @param array $participantData
     * @param array $emergencyContactsData
     * @param array $medicalConditionsData
     * @return Participant
     * @throws \Exception
     */
    public function execute(array $participantData, array $emergencyContactsData = [], array $medicalConditionsData = [])
    {
        try {
            DB::beginTransaction();

            // Crear el participante
            $participant = $this->createParticipant($participantData);

            // Crear contactos de emergencia
            if (!empty($emergencyContactsData)) {
                $this->createEmergencyContacts($participant, $emergencyContactsData);
            }

            // Crear condiciones médicas
            if (!empty($medicalConditionsData)) {
                $this->createMedicalConditions($participant, $medicalConditionsData);
            }

            DB::commit();

            Log::info('Participante creado exitosamente', [
                'participant_id' => $participant->id,
                'email' => $participant->email
            ]);

            return $participant;

        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Error al crear participante', [
                'error' => $e->getMessage(),
                'data' => $participantData
            ]);

            throw $e;
        }
    }

    /**
     * Crear el participante principal
     *
     * @param array $data
     * @return Participant
     */
    private function createParticipant(array $data): Participant
    {
        // Establecer valores por defecto
        $data['status'] = $data['status'] ?? 'pending_payment';
        $data['registration_date'] = $data['registration_date'] ?? now();
        $data['price_adjustments'] = $data['price_adjustments'] ?? 0;

        return Participant::create($data);
    }

    /**
     * Crear contactos de emergencia para el participante
     *
     * @param Participant $participant
     * @param array $emergencyContactsData
     * @return void
     */
    private function createEmergencyContacts(Participant $participant, array $emergencyContactsData): void
    {
        foreach ($emergencyContactsData as $contactData) {
            $contactData['participant_id'] = $participant->id;
            $contactData['relationship'] = $contactData['relationship'] ?? 'Familiar';
            
            EmergencyContact::create($contactData);
        }
    }

    /**
     * Crear condiciones médicas para el participante
     *
     * @param Participant $participant
     * @param array $medicalConditionsData
     * @return void
     */
    private function createMedicalConditions(Participant $participant, array $medicalConditionsData): void
    {
        foreach ($medicalConditionsData as $conditionData) {
            $conditionData['participant_id'] = $participant->id;
            
            MedicalCondition::create($conditionData);
        }
    }
}
