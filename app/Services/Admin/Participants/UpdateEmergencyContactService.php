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

        $contact->update([
            'name' => $data['name'],
            'email' => $data['email'],
            'document_type' => $data['document_type'] ?? 1,
            'document_number' => $data['document_number'] ?? '',
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
}


