<?php

namespace App\Services\Admin\Participants;

use App\Models\EmergencyContact;
use App\Models\Participant;
use App\Traits\AdminLogging;

class UpdateEmergencyContactService
{
    use AdminLogging;
    public function update(array $data, Participant $participant): void
    {
        $contact = EmergencyContact::findOrFail($data['contact_id']);
        if ($contact->participant_id !== $participant->id) {
            throw new \Exception('El contacto no pertenece a este participante');
        }

        // Capturar valores anteriores para logging
        $oldValues = [
            'name' => $contact->name,
            'email' => $contact->email,
            'phone' => $contact->code_phone . ' ' . $contact->phone,
        ];

        $documentType = $data['document_type'] ?? 1;
        $documentNumber = $data['document_number'] ?? '';

        // Sanitizar RUT: remover puntos y guiones si el tipo de documento es RUT (ID = 1)
        if ($documentType == 1) {
            $documentNumber = $this->sanitizeRut($documentNumber);
        }

        $contact->update([
            'name' => $data['name'],
            'email' => $data['email'],
            'document_type' => $documentType,
            'document_number' => $documentNumber,
            'code_phone' => $data['code_phone'] ?? null,
            'phone' => $data['phone'] ?? null,
            'country' => $data['country'] ?? null,
            'birth_date' => !empty($data['birth_date']) ? $data['birth_date'] : null,
            'address' => $data['address'] ?? null,
        ]);

        // Admin logging
        $newValues = [
            'name' => $contact->name,
            'email' => $contact->email,
            'phone' => $contact->code_phone . ' ' . $contact->phone,
        ];

        $this->logUpdate(
            'participants',
            'EmergencyContact',
            $contact->id,
            "Contacto de emergencia actualizado: {$contact->name} del participante {$participant->name}",
            $oldValues,
            $newValues,
            [
                'participant_id' => $participant->id,
                'participant_name' => $participant->name
            ]
        );
    }

    public function delete(array $data, Participant $participant): void
    {
        $contact = EmergencyContact::findOrFail($data['contact_id']);
        if ($contact->participant_id !== $participant->id) {
            throw new \Exception('El contacto no pertenece a este participante');
        }

        $totalContacts = EmergencyContact::where('participant_id', $participant->id)->count();
        if ($totalContacts <= 1) {
            throw new \Exception('No se puede eliminar el último contacto de emergencia. Debe mantener al menos un contacto.');
        }

        // Capturar valores para logging antes de eliminar
        $oldValues = [
            'name' => $contact->name,
            'email' => $contact->email,
            'phone' => $contact->code_phone . ' ' . $contact->phone,
        ];

        $contactId = $contact->id;
        $contactName = $contact->name;

        $contact->delete();

        // Admin logging
        $this->logDelete(
            'participants',
            'EmergencyContact',
            $contactId,
            "Contacto de emergencia eliminado: {$contactName} del participante {$participant->name}",
            $oldValues,
            [
                'participant_id' => $participant->id,
                'participant_name' => $participant->name
            ]
        );
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


