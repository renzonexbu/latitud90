<?php

namespace App\Services\Admin\Participants;

use App\Models\Participant;
use App\Models\EmergencyContact;
use App\Models\Course;
use App\Models\Program;
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

            // Normalizar país: usar código 'CL' al guardar (compat con Excel)
            if (!empty($participantData['country']) && strtolower($participantData['country']) === 'chile') {
                $participantData['country'] = 'CL';
            }

            // Crear el participante
            $participant = $this->createParticipant($participantData);

            // Crear contactos de emergencia
            if (!empty($emergencyContactsData)) {
                $this->createEmergencyContacts($participant, $emergencyContactsData);
            }

            // Asociar programa y curso automáticamente
            // program_id ahora apunta a program_courses (instancias específicas)
            if (!empty($participantData['program_id'])) {
                $programCourse = \App\Models\ProgramCourse::with(['course', 'program'])->find($participantData['program_id']);

                if ($programCourse && $programCourse->course) {
                    // Calcular el precio individual por participante
                    $individualPrice = $this->calculateIndividualPriceFromProgramCourse($programCourse);

                    // Generar enrollment_code consistente con el flujo Excel
                    $enrollmentCode = \App\Helpers\EnrollmentCodeHelper::generateEnrollmentCode($programCourse, $participant);

                    // Asociar al curso del programa automáticamente
                    $coursePivotData = [
                        'status' => 'pending_payment',
                        'individual_price' => $individualPrice,
                        'price_adjustments' => $participantData['price_adjustments'] ?? 0,
                        'adjustment_reason' => $participantData['adjustment_reason'] ?? null,
                    ];
                    $participant->courses()->attach($programCourse->course->id, $coursePivotData);

                    // Asociar al program_course (participant_program.program_id -> program_courses.id)
                    $participant->programCourses()->attach($participantData['program_id'], [
                        'enrollment_code' => $enrollmentCode,
                        'individual_price' => $individualPrice,
                        'status' => 'pending_payment',
                        'created_at' => now(),
                        'updated_at' => now()
                    ]);

                    // Si el participante estaba inactivo (ej: trasladado de otro programa),
                    // reactivarlo al inscribirlo en un programa nuevo
                    if (!$participant->is_active) {
                        $participant->update(['is_active' => true]);
                        Log::info('Participante reactivado automáticamente al inscribir en programa nuevo', [
                            'participant_id' => $participant->id,
                            'program_course_id' => $programCourse->id,
                        ]);
                    }

                    Log::info('Participante asociado automáticamente al curso del programa', [
                        'participant_id' => $participant->id,
                        'program_course_id' => $programCourse->id,
                        'course_id' => $programCourse->course->id,
                        'individual_price' => $individualPrice
                    ]);
                }
            } elseif (!empty($participantData['course_id'])) {
                // Caso fallback: si solo viene course_id sin program_id
                $pivotData = [
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
                    'has_course_association' => !empty($coursesData),
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

        // Normalizar nombres a Capital Case
        foreach (['first_name', 'second_name', 'first_last_name', 'second_last_name'] as $nameField) {
            if (!empty($data[$nameField])) {
                $data[$nameField] = $this->toCapitalCase($data[$nameField]);
            }
        }

        // Limpiar RUT si es tipo RUT
        if (!empty($data['document_number']) && !empty($data['document_type'])) {
            $documentType = \App\Models\Document::find($data['document_type']);
            if ($documentType && strtolower($documentType->name) === 'rut') {
                $data['document_number'] = $this->cleanRut($data['document_number']);
            }
        }

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
            $contactData['relationship'] = 'Familiar'; // Valor por defecto
            $contactData['country'] = 'CL'; // Normalizar país como Chile

            // Normalizar nombre a Capital Case
            if (!empty($contactData['name'])) {
                $contactData['name'] = $this->toCapitalCase($contactData['name']);
            }

            // Limpiar RUT del contacto de emergencia si existe
            if (!empty($contactData['document_number'])) {
                $contactData['document_number'] = $this->cleanRut($contactData['document_number']);
            }

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
                'status' => $courseData['status'] ?? 'pending_payment',
                'individual_price' => $courseData['individual_price'] ?? null,
                'price_adjustments' => $courseData['price_adjustments'] ?? 0,
                'adjustment_reason' => $courseData['adjustment_reason'] ?? null,
            ];

            $participant->courses()->attach($courseId, $pivotData);
        }
    }



    /**
     * Clean RUT by removing dots and dashes.
     */
    private function cleanRut(string $rut): string
    {
        // Quitar puntos y guiones, mantener solo números y dígito verificador
        return str_replace(['.', '-'], '', $rut);
    }

    /**
     * Obtiene el precio individual por participante desde un ProgramCourse
     * El trip_price ya representa el precio por alumno
     */
    private function calculateIndividualPriceFromProgramCourse(\App\Models\ProgramCourse $programCourse): float
    {
        return (float) ($programCourse->trip_price ?? 0);
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

    /**
     * Convertir texto a Capital Case (primera letra de cada palabra en mayúscula)
     */
    private function toCapitalCase(?string $text): ?string
    {
        if (empty($text)) {
            return $text;
        }
        return mb_convert_case(trim($text), MB_CASE_TITLE, 'UTF-8');
    }
}
