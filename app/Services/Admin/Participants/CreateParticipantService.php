<?php

namespace App\Services\Admin\Participants;

use App\Models\Participant;
use App\Models\EmergencyContact;
use App\Models\Course;
use App\Traits\AdminLogging;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CreateParticipantService
{
    use AdminLogging;
    /**
     * Crear un nuevo participante con sus contactos de emergencia y cursos
     *
     * @param array $participantData
     * @param array $emergencyContactsData
     * @param array $coursesData
     * @return Participant
     * @throws \Exception
     */
    public function execute(array $participantData, array $emergencyContactsData = [], array $coursesData = [])
    {
        try {
            DB::beginTransaction();

            // Crear el participante
            $participant = $this->createParticipant($participantData);

            // Crear contactos de emergencia
            if (!empty($emergencyContactsData)) {
                $this->createEmergencyContacts($participant, $emergencyContactsData);
            }

            // Asociar curso desde payload plano si viene course_id (caso Create.vue)
            if (!empty($participantData['course_id'])) {
                $pivotData = [
                    'education_level' => $participantData['education_level'] ?? null,
                    'year' => $participantData['year'] ?? null,
                    'grade' => $participantData['grade'] ?? null,
                    'status' => 'pending_payment',
                    'individual_price' => $participantData['individual_price'] ?? null,
                    'price_adjustments' => $participantData['price_adjustments'] ?? 0,
                    'adjustment_reason' => $participantData['adjustment_reason'] ?? null,
                ];
                $participant->courses()->attach($participantData['course_id'], $pivotData);
            } elseif (!empty($coursesData)) {
                $this->associateCourses($participant, $coursesData);
            }

            DB::commit();

            // Log the participant creation
            $this->logCreate(
                'participants',
                'Participant',
                $participant->id,
                "Participante creado: {$participant->first_name} {$participant->first_last_name}",
                $participant->toArray(),
                [
                    'courses_count' => $participant->courses->count(),
                    'emergency_contacts_count' => $participant->emergencyContacts->count(),
                    'has_course_association' => !empty($participantData['course_id']) || !empty($coursesData),
                ]
            );

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

        // Normalizar email si existe
        if (!empty($data['email'])) {
            $data['email'] = $this->normalizeEmail($data['email']);
        }
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
            if (!empty($contactData['email'])) {
                $contactData['email'] = $this->normalizeEmail($contactData['email']);
            }

            EmergencyContact::create($contactData);
        }
    }

    /**
     * Asociar cursos al participante usando la tabla pivote
     *
     * @param Participant $participant
     * @param array $coursesData
     * @return void
     */
    private function associateCourses(Participant $participant, array $coursesData): void
    {
        foreach ($coursesData as $courseData) {
            $courseId = $courseData['course_id'];
            $pivotData = [
                'education_level' => $courseData['education_level'] ?? null,
                'year' => $courseData['year'] ?? null,
                'grade' => $courseData['grade'] ?? null,
                'status' => $courseData['status'] ?? 'pending_payment',
                'individual_price' => $courseData['individual_price'] ?? null,
                'price_adjustments' => $courseData['price_adjustments'] ?? 0,
                'adjustment_reason' => $courseData['adjustment_reason'] ?? null,
            ];

            $participant->courses()->attach($courseId, $pivotData);
        }
    }

    /**
     * Normaliza emails: minúsculas, sin acentos/diacríticos y sin espacios.
     */
    private function normalizeEmail(string $email): string
    {
        $email = trim(strtolower($email));
        if ($email === '') {
            return '';
        }
        if (class_exists('\\Normalizer')) {
            $normalized = \Normalizer::normalize($email, \Normalizer::FORM_D);
            $normalized = preg_replace('/\p{Mn}+/u', '', $normalized);
        } else {
            $normalized = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $email);
            if ($normalized === false) {
                $normalized = $email;
            }
        }
        $normalized = preg_replace('/\s+/', '', $normalized);
        return $normalized ?? $email;
    }
}
