<?php

namespace App\Services\Client\Guardian;

use App\Models\EmergencyContact;
use App\Models\GuardianUser;
use App\Models\Participant;

class GuardianParticipantService
{
    /**
     * Obtener participantes asociados al email del guardian
     */
    public function getParticipantsByEmail(string $email): array
    {
        // Buscar contactos de emergencia que tengan el email del guardian
        $emergencyContacts = EmergencyContact::where('email', $email)
            ->with('participant.documentType')
            ->get();

        // Transformar a estructura compatible con las vistas
        return $emergencyContacts->map(function ($contact) {
            return [
                'id' => $contact->id,
                'is_primary' => true,
                'can_pay' => true,
                'can_view_documents' => true,
                'can_view_itinerary' => true,
                'emergency_contact' => [
                    'id' => $contact->id,
                    'name' => $contact->name,
                    'email' => $contact->email,
                    'phone' => $contact->phone,
                    'relationship' => $contact->relationship,
                    'participant' => $contact->participant ? [
                        'id' => $contact->participant->id,
                        'name' => $contact->participant->full_name,
                        'document' => $contact->participant->document_number,
                        'document_type' => $contact->participant->documentType ? $contact->participant->documentType->name : 'N/A',
                        'email' => $contact->participant->email,
                        'phone' => $contact->participant->code_phone && $contact->participant->phone
                            ? '+' . $contact->participant->code_phone . ' ' . $contact->participant->phone
                            : $contact->participant->phone,
                        'birth_date' => $contact->participant->birth_date,
                    ] : null
                ]
            ];
        })->toArray();
    }

    /**
     * Preparar datos del usuario con participantes para el dashboard
     */
    public function prepareUserDataForDashboard(GuardianUser $user): array
    {
        $participants = $this->getParticipantsByEmail($user->email);

        $userData = $user->toArray();
        $userData['guardian_links'] = $participants;

        return $userData;
    }

    /**
     * Obtener solo la lista de participantes
     */
    public function getParticipantsList(GuardianUser $user): array
    {
        return $this->getParticipantsByEmail($user->email);
    }

    /**
     * Verificar si el guardian tiene acceso a un participante
     */
    public function guardianHasAccessToParticipant(GuardianUser $user, int $participantId): bool
    {
        return EmergencyContact::where('email', $user->email)
            ->where('participant_id', $participantId)
            ->exists();
    }

    /**
     * Obtener los programas de un participante
     */
    public function getParticipantPrograms(Participant $participant): array
    {
        // Obtener programas del participante con sus datos
        $programs = $participant->programs()->get();

        return $programs->map(function ($program) {
            // Obtener el precio desde el pivote
            $pivotData = $program->pivot;

            // Obtener primera imagen del programa si existe
            $images = $program->images;
            $firstImage = !empty($images) ? $images[0]['url'] : null;

            return [
                'id' => $program->id,
                'name' => $program->name,
                'description' => $program->trip_description,
                'start_date' => $program->departure_date,
                'end_date' => null, // Los programas solo tienen fecha de salida
                'location' => $program->destination,
                'price' => $pivotData->individual_price ?? $program->trip_price,
                'status' => $pivotData->status ?? 'active',
                'enrollment_code' => $pivotData->enrollment_code,
                'image' => $firstImage,
            ];
        })->toArray();
    }
}
