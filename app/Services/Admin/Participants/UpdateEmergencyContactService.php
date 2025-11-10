<?php

namespace App\Services\Admin\Participants;

use App\Models\EmergencyContact;
use App\Models\Participant;

class UpdateEmergencyContactService
{
    public function update(array $data, Participant $participant): void
    {
        $contact = EmergencyContact::findOrFail($data['contact_id']);
        if ($contact->participant_id !== $participant->id) {
            throw new \Exception('El contacto no pertenece a este participante');
        }

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
            'code_phone' => $data['code_phone'],
            'phone' => $data['phone'],
            'country' => $data['country'],
            'birth_date' => $data['birth_date'] ?? null,
            'address' => $data['address'] ?? null,
        ]);
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

        $contact->delete();
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


