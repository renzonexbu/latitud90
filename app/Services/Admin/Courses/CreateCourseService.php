<?php

namespace App\Services\Admin\Courses;

use App\Models\Course;
use App\Models\Participant;
use App\Models\EmergencyContact;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\IOFactory;

class CreateCourseService
{
    /**
     * Execute the course creation.
     */
    public function execute(array $courseData): Course
    {
        try {
            DB::beginTransaction();

            // Crear el curso
            $course = Course::create([
                'institution_id' => $courseData['institutionId'],
                'education_level' => $this->normalizeLevel($courseData['educationLevel']),
                'year' => $courseData['year'],
                'course_number' => $courseData['courseNumber'] ?? null,
                'course_name' => $courseData['courseName'] ?? null,
                'contact_email' => $courseData['contactEmail'],
                'contact_phone' => $courseData['contactPhone'],
                'program_id' => null, // Se asignará después si es necesario
                'end_date' => $courseData['endDate'],
                'status' => 'active',
                'created_by' => auth()->id(),
            ]);

            

            // Procesar participantes si se proporciona el archivo
            if (!empty($courseData['studentsFile'])) {
                
                
                $this->processParticipants($courseData['studentsFile'], $course);
            } else {
                
            }

            DB::commit();
            return $course;

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    private function normalizeLevel(string $level): string
    {
        return match ($level) {
            'primaria', 'primario', 'basica' => 'basica',
            'secundaria', 'secundario', 'media' => 'media',
            'preescolar' => 'preescolar',
            'universitaria', 'universitario' => 'universitaria',
            default => $level,
        };
    }

    /**
     * Process participants from uploaded file.
     */
    private function processParticipants($file, Course $course): void
    {
        try {
            
            
            // Guardar el archivo
            $filePath = $file->store('courses/students', 'public');
            
            
            
            // Actualizar el curso con la información del archivo
            $course->update([
                'students_file_path' => $filePath,
                'students_file_name' => $file->getClientOriginalName(),
            ]);

            // Leer el archivo usando PhpSpreadsheet
            $spreadsheet = IOFactory::load(storage_path('app/public/' . $filePath));
            $worksheet = $spreadsheet->getActiveSheet();
            $rows = $worksheet->toArray();
            
            
            
            // La primera fila contiene los headers
            $headers = array_shift($rows);
            
            
            
            // Mapear headers a campos de participantes
            $participantCount = 0;
            $updatedCount = 0;
            $createdCount = 0;
            
            foreach ($rows as $rowIndex => $row) {
                // Saltar filas vacías
                if (empty(array_filter($row))) {
                    
                    continue;
                }
                
                // Asegurar que la fila tenga el mismo número de columnas que los headers
                while (count($row) < count($headers)) {
                    $row[] = '';
                }
                
                $participantData = array_combine($headers, $row);
                $cleanRut = $this->cleanRut($participantData['RUT'] ?? '');
                
                
                
                // Buscar participante existente por RUT
                $documentType = $this->getDocumentType($participantData);
                $existingParticipant = Participant::where('document_number', $cleanRut)
                    ->where('document_type', $documentType)
                    ->where('country', 'CL')
                    ->first();
                
                if ($existingParticipant) {
                    // Verificar si ya está asociado a este curso
                    $isAlreadyInCourse = $existingParticipant->courses()
                        ->where('course_id', $course->id)
                        ->exists();
                    
                    if ($isAlreadyInCourse) {
                        // UPDATE: Actualizar datos del participante y la relación con el curso
                        
                        
                        // Actualizar datos del participante
                        $existingParticipant->update([
                            'first_name' => $participantData['Nombre'] ?? $existingParticipant->first_name,
                            'last_name' => $participantData['Apellido'] ?? $existingParticipant->last_name,
                            'email' => $participantData['Email'] ?? $existingParticipant->email,
                            'phone' => $participantData['Teléfono'] ?? $existingParticipant->phone,
                            'birth_date' => $participantData['Fecha de nacimiento'] ?? $existingParticipant->birth_date,
                            'address' => $participantData['Dirección'] ?? $existingParticipant->address,
                            'dietary_restrictions' => $participantData['Restricción dietaria'] ?? $existingParticipant->dietary_restrictions,
                            'medical_conditions' => $participantData['Condición médica'] ?? $existingParticipant->medical_conditions,
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
                        
                        $existingParticipant->courses()->updateExistingPivot($course->id, $pivotData);
                        $updatedCount++;
                         // Asegurar referencia consistente para secciones posteriores (contacto de emergencia)
                         $participant = $existingParticipant;
                        
                    } else {
                        // CREATE: Agregar nueva relación con el curso
                        
                        
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
                        
                        $existingParticipant->courses()->attach($course->id, $pivotData);
                        $createdCount++;
                         // Asegurar referencia consistente para secciones posteriores (contacto de emergencia)
                         $participant = $existingParticipant;
                    }
                    
                } else {
                    // CREATE: Crear nuevo participante y asociarlo al curso
                    
                    
                    $participant = Participant::create([
                        'first_name' => $participantData['Nombre'] ?? '',
                        'last_name' => $participantData['Apellido'] ?? '',
                        'email' => $participantData['Email'] ?? '',
                        'code_phone' => '+56', // Código por defecto para Chile
                        'phone' => $participantData['Teléfono'] ?? '',
                        'document_type' => $documentType,
                        'document_number' => $cleanRut,
                        'country' => 'CL', // Chile por defecto
                        'birth_date' => $participantData['Fecha de nacimiento'] ?? null,
                        'address' => $participantData['Dirección'] ?? null,
                        'dietary_restrictions' => $participantData['Restricción dietaria'] ?? null,
                        'medical_conditions' => $participantData['Condición médica'] ?? null,
                        'status' => 'pending_payment',
                        'registration_date' => now(),
                        'individual_price' => 0, // Se calculará después si se asigna a un programa
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
                    $createdCount++;
                    
                    
                }
                
                // Manejar contacto de emergencia
                if (!empty($participantData['Nombre contacto emergencia']) && 
                    !empty($participantData['Apellido contacto emergencia'])) {
                    
                    // Verificar si ya existe un contacto de emergencia para este participante
                    $existingEmergencyContact = EmergencyContact::where('participant_id', $participant->id)->first();
                    
                    if ($existingEmergencyContact) {
                        // UPDATE: Actualizar contacto de emergencia existente
                        $existingEmergencyContact->update([
                            'first_name' => $participantData['Nombre contacto emergencia'],
                            'last_name' => $participantData['Apellido contacto emergencia'],
                            'email' => $participantData['Email contacto emergencia'] ?? $existingEmergencyContact->email,
                            'phone' => $participantData['Teléfono contacto emergencia'] ?? $existingEmergencyContact->phone,
                            'birth_date' => $participantData['Fecha nacimiento contacto emergencia'] ?? $existingEmergencyContact->birth_date,
                            'relationship' => $participantData['Relación contacto emergencia'] ?? $existingEmergencyContact->relationship,
                        ]);
                        
                        
                    } else {
                        // CREATE: Crear nuevo contacto de emergencia
                        $emergencyContact = EmergencyContact::create([
                            'first_name' => $participantData['Nombre contacto emergencia'],
                            'last_name' => $participantData['Apellido contacto emergencia'],
                            'email' => $participantData['Email contacto emergencia'] ?? '',
                            'code_phone' => '+56', // Código por defecto para Chile
                            'phone' => $participantData['Teléfono contacto emergencia'] ?? '',
                            'country' => 'CL', // Chile por defecto
                            'birth_date' => $participantData['Fecha nacimiento contacto emergencia'] ?? null,
                            'address' => null,
                            'relationship' => $participantData['Relación contacto emergencia'] ?? 'Familiar',
                            'participant_id' => $participant->id,
                        ]);
                        
                        
                    }
                } else {
                    
                }
                
                $participantCount++;
            }
            
            
            
            // Actualizar el curso con el número total de estudiantes
            $course->update(['total_students' => $participantCount]);
            
        } catch (\Exception $e) {
            throw new \Exception('Error al procesar el archivo de estudiantes: ' . $e->getMessage());
        }
    }

    /**
     * Get document type based on document number format.
     */
    private function getDocumentType(array $participantData): string
    {
        $documentNumber = $participantData['RUT'] ?? '';
        
        // Si contiene puntos y guión, es un RUT chileno
        if (strpos($documentNumber, '.') !== false && strpos($documentNumber, '-') !== false) {
            return 'RUT';
        }
        
        // Si es solo números, podría ser un RUT sin formato
        if (is_numeric(str_replace(['.', '-'], '', $documentNumber))) {
            return 'RUT';
        }
        
        // Por defecto, asumir que es un RUT
        return 'RUT';
    }

    /**
     * Clean RUT by removing dots and dashes.
     */
    private function cleanRut(string $rut): string
    {
        // Quitar puntos y guiones, mantener solo números y dígito verificador
        return str_replace(['.', '-'], '', $rut);
    }
}
