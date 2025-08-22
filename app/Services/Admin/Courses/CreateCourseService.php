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
                'grade' => $courseData['grade'] ?? null,
                'year' => $courseData['year'],
                'course_number' => $courseData['courseNumber'] ?? null,
                'course_name' => $courseData['courseName'] ?? null,
                'contact_email' => $courseData['contactEmail'] ?? null,
                'contact_phone' => $courseData['contactPhone'] ?? null,
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
                
                // Función helper para obtener valor de múltiples nombres de columna
                $getFieldValue = function($possibleNames) use ($participantData) {
                    foreach ($possibleNames as $name) {
                        if (isset($participantData[$name]) && !empty($participantData[$name])) {
                            return $participantData[$name];
                        }
                    }
                    return null;
                };

                $cleanRut = $this->cleanRut($getFieldValue([
                    'Rut del participante', 'RUT', 'Rut', 'rut', 'Documento', 'Documento del participante', 'documento del participante'
                ]));
                
                if (empty($cleanRut)) {
                    continue; // Saltar filas sin RUT
                }
                
                // Obtener tipo de documento del participante
                $documentType = $getFieldValue([
                    'rut/pasaporte', 'tipo documento', 'tipo de documento', 'documento tipo'
                ]);
                $documentTypeId = $this->getDocumentTypeId($documentType);
                
                // Buscar participante existente por RUT
                $existingParticipant = Participant::where('document_number', $cleanRut)
                    ->where('document_type', $documentTypeId)
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
                            'first_last_name' => $this->toLowercase($getFieldValue([
                                'Primer apellido', 'primer apellido', 'apellido paterno'
                            ])) ?? $existingParticipant->first_last_name,
                            'second_last_name' => $this->toLowercase($getFieldValue([
                                'Segundo apellido', 'segundo apellido', 'apellido materno'
                            ])) ?? $existingParticipant->second_last_name,
                            'first_name' => $this->toLowercase($getFieldValue([
                                'Primer Nombre', 'primer nombre', 'nombre', 'Nombre'
                            ])) ?? $existingParticipant->first_name,
                            'second_name' => $this->toLowercase($getFieldValue([
                                'Segundo Nombre', 'segundo nombre', 'nombre segundo'
                            ])) ?? $existingParticipant->second_name,
                            'email' => $this->toLowercase($getFieldValue([
                                'Email', 'email', 'correo', 'correo electronico'
                            ])) ?? $existingParticipant->email,
                            'phone' => $getFieldValue([
                                'Teléfono', 'telefono', 'fono', 'celular'
                            ]) ?? $existingParticipant->phone,
                            'birth_date' => $getFieldValue([
                                'fecha de nacimiento', 'fecha nacimiento', 'nacimiento', 'Fecha de nacimiento'
                            ]) ?? $existingParticipant->birth_date,
                            'nationality' => $this->toLowercase($getFieldValue([
                                'nacionalidad', 'pais', 'origen'
                            ])) ?? $existingParticipant->nationality,
                            'gender' => $this->normalizeGender($getFieldValue([
                                'sexo', 'genero', 'género'
                            ])) ?? $existingParticipant->gender,
                            'address' => $getFieldValue([
                                'Dirección', 'direccion', 'domicilio', 'domicilio'
                            ]) ?? $existingParticipant->address,
                            'dietary_restrictions' => $getFieldValue([
                                'restricción alimenticia', 'restriccion alimenticia', 'restricción dietaria', 'restriccion dietaria', 'Restricción dietaria'
                            ]) ?? $existingParticipant->dietary_restrictions, // NO convertir a lowercase
                            'intolerances' => $getFieldValue([
                                'intolerancia', 'intolerancias'
                            ]) ?? $existingParticipant->intolerances, // NO convertir a lowercase
                            'allergies' => $getFieldValue([
                                'alergias', 'alergia'
                            ]) ?? $existingParticipant->allergies, // NO convertir a lowercase
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
                        'first_last_name' => $this->toLowercase($getFieldValue([
                            'Primer apellido', 'primer apellido', 'apellido paterno'
                        ])) ?? '',
                        'second_last_name' => $this->toLowercase($getFieldValue([
                            'Segundo apellido', 'segundo apellido', 'apellido materno'
                        ])) ?? '',
                        'first_name' => $this->toLowercase($getFieldValue([
                            'Primer Nombre', 'primer nombre', 'nombre', 'Nombre'
                        ])) ?? '',
                        'second_name' => $this->toLowercase($getFieldValue([
                            'Segundo Nombre', 'segundo nombre', 'nombre segundo'
                        ])) ?? '',
                        'email' => $this->toLowercase($getFieldValue([
                            'Email', 'email', 'correo', 'correo electronico'
                        ])) ?? '',
                        'code_phone' => '+56', // Código por defecto para Chile
                        'phone' => $getFieldValue([
                            'Teléfono', 'telefono', 'fono', 'celular'
                        ]) ?? '',
                        'document_type' => $documentTypeId,
                        'document_number' => $cleanRut,
                        'country' => 'CL', // Chile por defecto
                        'birth_date' => $getFieldValue([
                            'fecha de nacimiento', 'fecha nacimiento', 'nacimiento', 'Fecha de nacimiento'
                        ]) ?? null,
                        'nationality' => $this->toLowercase($getFieldValue([
                            'nacionalidad', 'pais', 'origen'
                        ])) ?? 'chilena',
                        'gender' => $this->normalizeGender($getFieldValue([
                            'sexo', 'genero', 'género'
                        ])) ?? 'Masculino',
                        'address' => $getFieldValue([
                            'Dirección', 'direccion', 'domicilio', 'domicilio'
                        ]) ?? null,
                        'dietary_restrictions' => $getFieldValue([
                            'restricción alimenticia', 'restriccion alimenticia', 'restricción dietaria', 'restriccion dietaria', 'Restricción dietaria'
                        ]) ?? null, // NO convertir a lowercase
                        'intolerances' => $getFieldValue([
                            'intolerancia', 'intolerancias'
                        ]) ?? null, // NO convertir a lowercase
                        'allergies' => $getFieldValue([
                            'alergias', 'alergia'
                        ]) ?? null, // NO convertir a lowercase
                        'status' => 'pending_payment',
                        'registration_date' => now(),
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
                if ($getFieldValue([
                    'Nombre del apoderado', 'nombre apoderado', 'apoderado', 'guardian'
                ])) {
                    
                    // Obtener datos del apoderado
                    $guardianName = $getFieldValue([
                        'Nombre del apoderado', 'nombre apoderado', 'apoderado', 'guardian'
                    ]);
                    $guardianEmail = $getFieldValue([
                        'correo electronico del apoderado', 'correo apoderado', 'email apoderado', 'email del apoderado'
                    ]);
                    
                    // Verificar si ya existe un contacto de emergencia para este participante
                    $existingEmergencyContact = EmergencyContact::where('participant_id', $participant->id)->first();
                    
                    if ($existingEmergencyContact) {
                        // UPDATE: Actualizar contacto de emergencia existente
                        
                        // Obtener tipo de documento del apoderado (por defecto RUT)
                        $guardianDocumentTypeId = $this->getDocumentTypeId('RUT');
                        
                        // Limpiar RUT del apoderado
                        $cleanGuardianRut = $this->cleanRut($guardianRut ?? '');
                        
                        $existingEmergencyContact->update([
                            'name' => $this->toLowercase($guardianName) ?? $existingEmergencyContact->name,
                            'email' => $this->toLowercase($guardianEmail) ?? $existingEmergencyContact->email,
                            'document_type' => $guardianDocumentTypeId,
                            'document_number' => $cleanGuardianRut,
                            'phone' => $getFieldValue([
                                'Teléfono contacto emergencia', 'telefono contacto emergencia', 'fono contacto emergencia'
                            ]) ?? $existingEmergencyContact->phone,
                            'birth_date' => $getFieldValue([
                                'Fecha nacimiento contacto emergencia', 'fecha nacimiento contacto emergencia'
                            ]) ?? $existingEmergencyContact->birth_date,
                            'relationship' => $getFieldValue([
                                'Relación contacto emergencia', 'relacion contacto emergencia'
                            ]) ?? $existingEmergencyContact->relationship,
                        ]);
                        
                        
                    } else {
                        // CREATE: Crear nuevo contacto de emergencia
                        
                        // Obtener tipo de documento del apoderado (por defecto RUT)
                        $guardianDocumentTypeId = $this->getDocumentTypeId('RUT');
                        
                        // Limpiar RUT del apoderado
                        $cleanGuardianRut = $this->cleanRut($guardianRut ?? '');
                        
                        $emergencyContact = EmergencyContact::create([
                            'name' => $this->toLowercase($guardianName),
                            'email' => $this->toLowercase($guardianEmail),
                            'document_type' => $guardianDocumentTypeId,
                            'document_number' => $cleanGuardianRut,
                            'code_phone' => '+56', // Código por defecto para Chile
                            'phone' => $getFieldValue([
                                'Teléfono contacto emergencia', 'telefono contacto emergencia', 'fono contacto emergencia'
                            ]) ?? '',
                            'country' => 'CL', // Chile por defecto
                            'birth_date' => $getFieldValue([
                                'Fecha nacimiento contacto emergencia', 'fecha nacimiento contacto emergencia'
                            ]) ?? null,
                            'address' => null,
                            'relationship' => $getFieldValue([
                                'Relación contacto emergencia', 'relacion contacto emergencia'
                            ]) ?? 'Familiar',
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

    /**
     * Convertir texto a lowercase (excepto datos médicos)
     */
    private function toLowercase(?string $text): ?string
    {
        if (empty($text)) return $text;
        return strtolower(trim($text));
    }

    /**
     * Normalizar género
     */
    private function normalizeGender(string $gender): string
    {
        $gender = strtolower(trim($gender));
        
        if (in_array($gender, ['m', 'masculino', 'male', 'hombre'])) {
            return 'Masculino';
        }
        
        if (in_array($gender, ['f', 'femenino', 'female', 'mujer'])) {
            return 'Femenino';
        }
        
        return 'Masculino'; // Por defecto
    }

    /**
     * Obtener el ID del tipo de documento
     */
    private function getDocumentTypeId(string $documentType): int
    {
        $document = \App\Models\Document::where('name', 'LIKE', "%{$documentType}%")->first();
        return $document ? $document->id : 1; // Por defecto ID 1 (RUT)
    }
}
