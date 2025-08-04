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
                'education_level' => $courseData['educationLevel'],
                'year' => $courseData['year'],
                'grade' => $courseData['grade'],
                'shift' => $courseData['shift'],
                'contact_email' => $courseData['contactEmail'],
                'contact_phone' => $courseData['contactPhone'],
                'program_id' => null, // Se asignará después si es necesario
                'end_date' => $courseData['endDate'],
                'status' => 'active',
                'created_by' => auth()->id(),
            ]);

            Log::info('Curso creado exitosamente', [
                'course_id' => $course->id,
                'institution_id' => $courseData['institutionId']
            ]);

            // Procesar participantes si se proporciona el archivo
            if (!empty($courseData['studentsFile'])) {
                Log::info('Procesando archivo de estudiantes para curso', [
                    'file_name' => $courseData['studentsFile']->getClientOriginalName(),
                    'file_size' => $courseData['studentsFile']->getSize(),
                    'course_id' => $course->id
                ]);
                
                $this->processParticipants($courseData['studentsFile'], $course);
            } else {
                Log::info('No se proporcionó archivo de estudiantes para el curso', [
                    'course_id' => $course->id
                ]);
            }

            DB::commit();
            return $course;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al crear curso', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            throw $e;
        }
    }

    /**
     * Process participants from uploaded file.
     */
    private function processParticipants($file, Course $course): void
    {
        try {
            Log::info('Iniciando procesamiento de participantes para curso', [
                'course_id' => $course->id
            ]);
            
            // Guardar el archivo
            $filePath = $file->store('courses/students', 'public');
            
            Log::info('Archivo guardado para curso', [
                'file_path' => $filePath,
                'original_name' => $file->getClientOriginalName()
            ]);
            
            // Actualizar el curso con la información del archivo
            $course->update([
                'students_file_path' => $filePath,
                'students_file_name' => $file->getClientOriginalName(),
            ]);

            // Leer el archivo usando PhpSpreadsheet
            $spreadsheet = IOFactory::load(storage_path('app/public/' . $filePath));
            $worksheet = $spreadsheet->getActiveSheet();
            $rows = $worksheet->toArray();
            
            Log::info('Archivo leído con PhpSpreadsheet para curso', [
                'total_rows' => count($rows),
                'file_name' => $file->getClientOriginalName()
            ]);
            
            // La primera fila contiene los headers
            $headers = array_shift($rows);
            
            Log::info('Headers encontrados para curso', [
                'headers' => $headers,
                'headers_count' => count($headers)
            ]);
            
            // Mapear headers a campos de participantes
            $participantCount = 0;
            foreach ($rows as $rowIndex => $row) {
                // Saltar filas vacías
                if (empty(array_filter($row))) {
                    Log::info('Fila vacía encontrada en curso', ['row_index' => $rowIndex]);
                    continue;
                }
                
                // Asegurar que la fila tenga el mismo número de columnas que los headers
                while (count($row) < count($headers)) {
                    $row[] = '';
                }
                
                $participantData = array_combine($headers, $row);
                
                Log::info('Procesando participante para curso', [
                    'row_index' => $rowIndex,
                    'nombre' => $participantData['Nombre'] ?? 'N/A',
                    'apellido' => $participantData['Apellido'] ?? 'N/A'
                ]);
                
                // Crear participante (sin precio individual por ahora)
                $participant = Participant::create([
                    'course_id' => $course->id,
                    'first_name' => $participantData['Nombre'] ?? '',
                    'last_name' => $participantData['Apellido'] ?? '',
                    'email' => $participantData['Email'] ?? '',
                    'code_phone' => '+56', // Código por defecto para Chile
                    'phone' => $participantData['Teléfono'] ?? '',
                    'document_type' => $this->getDocumentType($participantData),
                    'document_number' => $this->cleanRut($participantData['RUT'] ?? ''),
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
                
                Log::info('Participante creado para curso', [
                    'participant_id' => $participant->id,
                    'nombre_completo' => $participant->first_name . ' ' . $participant->last_name
                ]);
                
                // Crear contacto de emergencia
                if (!empty($participantData['Nombre contacto emergencia']) && 
                    !empty($participantData['Apellido contacto emergencia'])) {
                    
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
                    
                    Log::info('Contacto de emergencia creado para curso', [
                        'emergency_contact_id' => $emergencyContact->id,
                        'participant_id' => $participant->id,
                        'nombre_completo' => $emergencyContact->first_name . ' ' . $emergencyContact->last_name
                    ]);
                } else {
                    Log::warning('No se creó contacto de emergencia para curso - datos faltantes', [
                        'participant_id' => $participant->id,
                        'has_nombre' => !empty($participantData['Nombre contacto emergencia']),
                        'has_apellido' => !empty($participantData['Apellido contacto emergencia'])
                    ]);
                }
                
                $participantCount++;
            }
            
            Log::info('Procesamiento completado para curso', [
                'total_participants_created' => $participantCount,
                'course_id' => $course->id
            ]);
            
            // Actualizar el curso con el número total de estudiantes
            $course->update(['total_students' => $participantCount]);
            
        } catch (\Exception $e) {
            Log::error('Error al procesar participantes para curso', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'course_id' => $course->id
            ]);
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
