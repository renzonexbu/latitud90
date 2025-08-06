<?php

namespace App\Services\Admin\Participants;

use App\Models\Participant;
use App\Models\EmergencyContact;
use App\Models\Course;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CreateParticipantService
{
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

            // Asociar cursos al participante
            if (!empty($coursesData)) {
                $this->associateCourses($participant, $coursesData);
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
                'shift' => $courseData['shift'] ?? null,
                'status' => $courseData['status'] ?? 'pending_payment',
                'individual_price' => $courseData['individual_price'] ?? null,
                'price_adjustments' => $courseData['price_adjustments'] ?? 0,
                'adjustment_reason' => $courseData['adjustment_reason'] ?? null,
            ];

            $participant->courses()->attach($courseId, $pivotData);
        }
    }

    /**
     * Procesar participantes desde Excel con la nueva lógica de tabla pivote
     *
     * @param array $participantsData
     * @param Course $course
     * @return array
     */
    public function processParticipantsFromExcel(array $participantsData, Course $course): array
    {
        $createdCount = 0;
        $updatedCount = 0;
        $errors = [];

        foreach ($participantsData as $index => $participantData) {
            try {
                // Limpiar RUT
                $cleanRut = $this->cleanRut($participantData['RUT'] ?? '');
                
                if (empty($cleanRut)) {
                    $errors[] = "Fila " . ($index + 2) . ": RUT vacío o inválido";
                    continue;
                }

                // Buscar participante existente por RUT
                $existingParticipant = Participant::where('document_number', $cleanRut)
                    ->where('document_type', 'RUT')
                    ->where('country', 'CL')
                    ->first();

                if ($existingParticipant) {
                    // Verificar si ya está asociado a este curso
                    $isAlreadyInCourse = $existingParticipant->courses()
                        ->where('course_id', $course->id)
                        ->exists();

                    if ($isAlreadyInCourse) {
                        // UPDATE: Actualizar datos del participante y la relación con el curso
                        $this->updateParticipantAndCourseRelation($existingParticipant, $participantData, $course);
                        $updatedCount++;
                    } else {
                        // CREATE: Agregar nueva relación con el curso
                        $this->addParticipantToCourse($existingParticipant, $participantData, $course);
                        $createdCount++;
                    }
                } else {
                    // CREATE: Crear nuevo participante y asociarlo al curso
                    $newParticipant = $this->createNewParticipantFromExcel($participantData, $course);
                    $createdCount++;
                }

            } catch (\Exception $e) {
                $errors[] = "Fila " . ($index + 2) . ": " . $e->getMessage();
            }
        }

        return [
            'created' => $createdCount,
            'updated' => $updatedCount,
            'errors' => $errors
        ];
    }

    /**
     * Limpiar RUT de caracteres especiales
     */
    private function cleanRut(string $rut): string
    {
        return preg_replace('/[^0-9kK]/', '', $rut);
    }

    /**
     * Actualizar participante existente y su relación con el curso
     */
    private function updateParticipantAndCourseRelation(Participant $participant, array $participantData, Course $course): void
    {
        // Actualizar datos del participante
        $participant->update([
            'first_name' => $participantData['Nombre'] ?? $participant->first_name,
            'last_name' => $participantData['Apellido'] ?? $participant->last_name,
            'email' => $participantData['Email'] ?? $participant->email,
            'phone' => $participantData['Teléfono'] ?? $participant->phone,
            'birth_date' => $participantData['Fecha de nacimiento'] ?? $participant->birth_date,
            'address' => $participantData['Dirección'] ?? $participant->address,
            'dietary_restrictions' => $participantData['Restricción dietaria'] ?? $participant->dietary_restrictions,
            'medical_conditions' => $participantData['Condición médica'] ?? $participant->medical_conditions,
        ]);

        // Actualizar relación con el curso
        $pivotData = [
            'education_level' => $participantData['Nivel de educación'] ?? null,
            'year' => $participantData['Año'] ?? null,
            'grade' => $participantData['Grado'] ?? null,
            'shift' => $participantData['Turno'] ?? null,
            'individual_price' => $participantData['Precio individual'] ?? null,
            'price_adjustments' => $participantData['Ajustes de precio'] ?? 0,
            'adjustment_reason' => $participantData['Razón del ajuste'] ?? null,
        ];

        $participant->courses()->updateExistingPivot($course->id, $pivotData);
    }

    /**
     * Agregar participante existente a un nuevo curso
     */
    private function addParticipantToCourse(Participant $participant, array $participantData, Course $course): void
    {
        $pivotData = [
            'education_level' => $participantData['Nivel de educación'] ?? null,
            'year' => $participantData['Año'] ?? null,
            'grade' => $participantData['Grado'] ?? null,
            'shift' => $participantData['Turno'] ?? null,
            'status' => 'pending_payment',
            'individual_price' => $participantData['Precio individual'] ?? null,
            'price_adjustments' => $participantData['Ajustes de precio'] ?? 0,
            'adjustment_reason' => $participantData['Razón del ajuste'] ?? null,
        ];

        $participant->courses()->attach($course->id, $pivotData);
    }

    /**
     * Crear nuevo participante desde Excel y asociarlo al curso
     */
    private function createNewParticipantFromExcel(array $participantData, Course $course): Participant
    {
        $cleanRut = $this->cleanRut($participantData['RUT'] ?? '');

        $participant = Participant::create([
            'first_name' => $participantData['Nombre'] ?? '',
            'last_name' => $participantData['Apellido'] ?? '',
            'email' => $participantData['Email'] ?? '',
            'code_phone' => '+56',
            'phone' => $participantData['Teléfono'] ?? '',
            'document_type' => 'RUT',
            'document_number' => $cleanRut,
            'country' => 'CL',
            'birth_date' => $participantData['Fecha de nacimiento'] ?? null,
            'address' => $participantData['Dirección'] ?? null,
            'dietary_restrictions' => $participantData['Restricción dietaria'] ?? null,
            'medical_conditions' => $participantData['Condición médica'] ?? null,
            'status' => 'pending_payment',
            'registration_date' => now(),
            'individual_price' => 0,
            'price_adjustments' => 0,
        ]);

        // Asociar al curso
        $pivotData = [
            'education_level' => $participantData['Nivel de educación'] ?? null,
            'year' => $participantData['Año'] ?? null,
            'grade' => $participantData['Grado'] ?? null,
            'shift' => $participantData['Turno'] ?? null,
            'status' => 'pending_payment',
            'individual_price' => $participantData['Precio individual'] ?? null,
            'price_adjustments' => $participantData['Ajustes de precio'] ?? 0,
            'adjustment_reason' => $participantData['Razón del ajuste'] ?? null,
        ];

        $participant->courses()->attach($course->id, $pivotData);

        return $participant;
    }
}
