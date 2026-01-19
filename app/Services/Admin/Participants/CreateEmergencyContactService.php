<?php

namespace App\Services\Admin\Participants;

use App\Models\Participant;
use App\Models\EmergencyContact;
use App\Traits\AdminLogging;
use Illuminate\Support\Facades\Log;

class CreateEmergencyContactService
{
    use AdminLogging;
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
                $documentType = $contactData['document_type'] ?? 1; // Default a RUT si no viene
                $documentNumber = $contactData['document_number'] ?? '';

                // Sanitizar RUT: remover puntos y guiones si el tipo de documento es RUT (ID = 1)
                if ($documentType == 1) {
                    $documentNumber = $this->sanitizeRut($documentNumber);
                }

                $contact = EmergencyContact::create([
                    'participant_id' => $participant->id,
                    'name' => $contactData['name'],
                    'email' => $contactData['email'],
                    'document_type' => $documentType,
                    'document_number' => $documentNumber,
                    'code_phone' => $contactData['code_phone'],
                    'phone' => $contactData['phone'],
                    'country' => $contactData['country'],
                    'birth_date' => $contactData['birth_date'] ?? null,
                    'address' => $contactData['address'] ?? null,
                ]);

                // Admin logging
                $this->logCreate(
                    'participants',
                    'EmergencyContact',
                    $contact->id,
                    "Contacto de emergencia creado: {$contact->name} para participante {$participant->name}",
                    [
                        'name' => $contact->name,
                        'email' => $contact->email,
                        'phone' => $contact->code_phone . ' ' . $contact->phone,
                    ],
                    [
                        'participant_id' => $participant->id,
                        'participant_name' => $participant->name
                    ]
                );

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

    /**
     * Sanitizar RUT removiendo puntos y guiones
     * Ejemplo: "12.345.678-9" -> "123456789"
     *
     * @param string $rut
     * @return string
     */
    private function sanitizeRut(string $rut): string
    {
        return str_replace(['.', '-'], '', $rut);
    }
}
