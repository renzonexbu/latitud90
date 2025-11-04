<?php

namespace App\Services\Admin\Participants;

use App\Models\Participant;
use App\Models\EmergencyContact;
use Illuminate\Support\Facades\Log;

class CreateEmergencyContactService
{
    /**
     * Crear nuevos contactos de emergencia para un participante
     *
     * @param array $emergencyContactsData
     * @param Participant $participant
     * @return array
     * @throws \Exception
     */
    public function execute(array $emergencyContactsData, Participant $participant): array
    {
        try {
            $createdContacts = [];

            // Crear nuevos contactos de emergencia
            foreach ($emergencyContactsData as $contactData) {
                $contact = EmergencyContact::create([
                    'participant_id' => $participant->id,
                    'name' => $contactData['name'],
                    'email' => $contactData['email'],
                    'document_type' => $contactData['document_type'] ?? 1, // Default a RUT si no viene
                    'document_number' => $contactData['document_number'] ?? '',
                    'code_phone' => $contactData['code_phone'],
                    'phone' => $contactData['phone'],
                    'country' => $contactData['country'],
                    'birth_date' => $contactData['birth_date'] ?? null,
                    'address' => $contactData['address'] ?? null,
                ]);

                $createdContacts[] = $contact;
            }

            Log::info('Contactos de emergencia creados exitosamente', [
                'participant_id' => $participant->id,
                'contacts_count' => count($createdContacts)
            ]);

            return $createdContacts;
        } catch (\Exception $e) {
            Log::error('Error al crear contactos de emergencia', [
                'participant_id' => $participant->id,
                'error' => $e->getMessage()
            ]);

            throw $e;
        }
    }
}
