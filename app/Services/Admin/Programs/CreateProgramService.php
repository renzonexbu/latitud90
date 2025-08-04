<?php

namespace App\Services\Admin\Programs;

use App\Models\Program;
use App\Models\Course;
use App\Models\Participant;
use App\Models\EmergencyContact;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\IOFactory;

class CreateProgramService
{
    /**
     * Execute the program creation.
     */
    public function execute(array $programData): Program
    {
        try {
            DB::beginTransaction();

            // Procesar los pilares como string separado por comas
            $pillars = [];
            if (!empty($programData['pilar_aventura'])) {
                $pillars[] = $programData['pilar_aventura'];
            }
            if (!empty($programData['pilar_entretenimiento'])) {
                $pillars[] = $programData['pilar_entretenimiento'];
            }
            if (!empty($programData['pilar_educacion'])) {
                $pillars[] = $programData['pilar_educacion'];
            }
            if (!empty($programData['pilar_seguridad'])) {
                $pillars[] = $programData['pilar_seguridad'];
            }
            $programData['pillars'] = implode(', ', $pillars);

            // Procesar archivos si se proporcionaron
            $programData = $this->processFiles($programData);

            // Crear el programa
            $program = Program::create([
                'name' => $programData['name'],
                'destination' => $programData['destination'],
                'departure_date' => $programData['departure_date'],
                'trip_description' => $programData['description'] ?? $programData['trip_description'],
                'images_folder' => $programData['images_folder'] ?? null,
                'pillars' => $programData['pillars'] ?? null,
                'itinerary_description' => $programData['itinerary'] ?? null,
                'itinerary_file' => $programData['itinerary_file_path'] ?? null,
                'travel_assistance_coverage' => $programData['coverage_file_path'] ?? null,
                'equipment_list' => $programData['equipment_file_path'] ?? null,
                'trip_price' => $programData['total_price'] ?? $programData['trip_price'],
                'final_payment_date' => $programData['final_payment_date'],
                'seller_name' => $programData['sales_person'] ?? $programData['seller_name'],
                'payment_mode_id' => $this->getPaymentModeId($programData),
                'active' => $programData['active'] ?? true,
            ]);

            // Lógica para crear curso y participantes si se proporcionan los datos
            if (!empty($programData['institution_id']) && 
                !empty($programData['education_level']) && 
                !empty($programData['shift']) && 
                !empty($programData['grade'])) {
                
                Log::info('Creando curso para programa', [
                    'program_id' => $program->id,
                    'institution_id' => $programData['institution_id'],
                    'education_level' => $programData['education_level'],
                    'shift' => $programData['shift'],
                    'grade' => $programData['grade']
                ]);
                
                // Crear el curso
                $course = $this->createCourse($programData, $program);
                
                Log::info('Curso creado exitosamente', [
                    'course_id' => $course->id,
                    'program_id' => $program->id
                ]);
                
                // Asignar el curso al programa
                $program->update(['course_id' => $course->id]);
                
                // Procesar participantes si se proporciona el archivo
                if (!empty($programData['students_file'])) {
                    Log::info('Procesando archivo de estudiantes', [
                        'file_name' => $programData['students_file']->getClientOriginalName(),
                        'file_size' => $programData['students_file']->getSize(),
                        'course_id' => $course->id
                    ]);
                    
                    $this->processParticipants($programData['students_file'], $course, $program);
                } else {
                    Log::warning('No se proporcionó archivo de estudiantes', [
                        'program_id' => $program->id,
                        'course_id' => $course->id
                    ]);
                }
            } else {
                Log::info('No se creará curso - datos incompletos', [
                    'program_id' => $program->id,
                    'has_institution' => !empty($programData['institution_id']),
                    'has_education_level' => !empty($programData['education_level']),
                    'has_shift' => !empty($programData['shift']),
                    'has_grade' => !empty($programData['grade'])
                ]);
            }

            DB::commit();
            return $program;

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Process uploaded files and store them.
     */
    private function processFiles(array $programData): array
    {
        // Procesar archivo de itinerario
        if (isset($programData['itinerary_file']) && $programData['itinerary_file']) {
            $path = $programData['itinerary_file']->store('programs/files', 'public');
            $programData['itinerary_file_path'] = $path;
        }

        // Procesar archivo de cobertura
        if (isset($programData['coverage_file']) && $programData['coverage_file']) {
            $path = $programData['coverage_file']->store('programs/files', 'public');
            $programData['coverage_file_path'] = $path;
        }

        // Procesar archivo de lista de equipo
        if (isset($programData['equipment_file']) && $programData['equipment_file']) {
            $path = $programData['equipment_file']->store('programs/files', 'public');
            $programData['equipment_file_path'] = $path;
        }

        // Procesar imágenes si se proporcionaron
        if (isset($programData['images']) && is_array($programData['images'])) {
            $imagePaths = [];
            foreach ($programData['images'] as $image) {
                if ($image && $image->isValid()) {
                    $path = $image->store('programs/images', 'public');
                    $imagePaths[] = $path;
                }
            }
            if (!empty($imagePaths)) {
                $programData['images_folder'] = 'programs/images/' . uniqid();
            }
        }

        return $programData;
    }

    /**
     * Get payment mode ID based on payment options.
     */
    private function getPaymentModeId(array $programData): int
    {
        // Si se proporciona directamente payment_mode_id, usarlo
        if (isset($programData['payment_mode_id'])) {
            return $programData['payment_mode_id'];
        }

        // Si no, crear o encontrar un payment mode basado en las opciones
        $paymentOption = $programData['payment_option'] ?? null;
        $paymentMethod = null;

        if ($paymentOption === 'full_payment') {
            $paymentMethod = $programData['full_payment_method'] ?? 'todos_medios';
        } elseif ($paymentOption === 'installments') {
            $paymentMethod = $programData['installments_payment_method'] ?? 'todos_medios';
        }

        // Buscar o crear el payment mode
        $paymentMode = \App\Models\PaymentMode::firstOrCreate([
            'name' => $this->getPaymentModeName($paymentOption, $paymentMethod),
        ], [
            'code' => $this->getPaymentModeCode($paymentOption, $paymentMethod),
            'description' => $this->getPaymentModeDescription($paymentOption, $paymentMethod),
            'active' => true,
        ]);

        return $paymentMode->id;
    }

    /**
     * Get payment mode code based on options.
     */
    private function getPaymentModeCode(?string $paymentOption, ?string $paymentMethod): string
    {
        $methodCode = $this->getMethodCode($paymentMethod);
        
        if ($paymentOption === 'full_payment') {
            return 'full_payment_' . $methodCode;
        } elseif ($paymentOption === 'installments') {
            return 'installments_' . $methodCode;
        }

        return 'standard_payment';
    }

    /**
     * Get payment mode name based on options.
     */
    private function getPaymentModeName(?string $paymentOption, ?string $paymentMethod): string
    {
        if ($paymentOption === 'full_payment') {
            return 'Pago Total - ' . $this->getMethodDisplayName($paymentMethod);
        } elseif ($paymentOption === 'installments') {
            return 'Pago en Cuotas - ' . $this->getMethodDisplayName($paymentMethod);
        }

        return 'Pago Estándar';
    }

    /**
     * Get payment mode description based on options.
     */
    private function getPaymentModeDescription(?string $paymentOption, ?string $paymentMethod): string
    {
        $methodDesc = $this->getMethodDescription($paymentMethod);
        
        if ($paymentOption === 'full_payment') {
            return "Pago total del viaje. $methodDesc";
        } elseif ($paymentOption === 'installments') {
            return "Pago en cuotas mensuales. $methodDesc";
        }

        return "Método de pago estándar. $methodDesc";
    }

    /**
     * Get method display name.
     */
    private function getMethodDisplayName(?string $method): string
    {
        return match ($method) {
            'todos_medios' => 'Todos los medios',
            'solo_tarjeta' => 'Solo tarjeta',
            'solo_transferencia' => 'Solo transferencia',
            'solo_contado' => 'Solo contado',
            default => 'Todos los medios',
        };
    }

    /**
     * Get method code.
     */
    private function getMethodCode(?string $method): string
    {
        return match ($method) {
            'todos_medios' => 'all_methods',
            'solo_tarjeta' => 'card_only',
            'solo_transferencia' => 'transfer_only',
            'solo_contado' => 'cash_only',
            default => 'all_methods',
        };
    }

    /**
     * Get method description.
     */
    private function getMethodDescription(?string $method): string
    {
        return match ($method) {
            'todos_medios' => 'Acepta débito, crédito y transferencia',
            'solo_tarjeta' => 'Solo acepta tarjetas de débito y crédito',
            'solo_transferencia' => 'Solo acepta transferencias bancarias',
            'solo_contado' => 'Solo acepta débito y transferencia',
            default => 'Acepta todos los medios de pago',
        };
    }

    /**
     * Create a new course based on program data.
     */
    private function createCourse(array $programData, Program $program): Course
    {
        return Course::create([
            'institution_id' => $programData['institution_id'],
            'education_level' => $this->mapEducationLevel($programData['education_level']),
            'year' => date('Y'),
            'grade' => $programData['grade'],
            'shift' => $programData['shift'],
            'contact_email' => $programData['contact_email'] ?? '',
            'contact_phone' => $programData['contact_phone'] ?? '',
            'program_id' => $program->id, // Asignar el program_id correctamente
            'end_date' => $programData['final_payment_date'] ?? null,
            'status' => 'active',
            'created_by' => auth()->id(),
        ]);
    }

    /**
     * Process participants from uploaded file.
     */
    private function processParticipants($file, Course $course, Program $program): void
    {
        try {
            Log::info('Iniciando procesamiento de participantes', [
                'course_id' => $course->id,
                'program_id' => $program->id
            ]);
            
            // Guardar el archivo
            $filePath = $file->store('courses/students', 'public');
            
            Log::info('Archivo guardado', [
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
            
            Log::info('Archivo leído con PhpSpreadsheet', [
                'total_rows' => count($rows),
                'file_name' => $file->getClientOriginalName()
            ]);
            
            // La primera fila contiene los headers
            $headers = array_shift($rows);
            
            Log::info('Headers encontrados', [
                'headers' => $headers,
                'headers_count' => count($headers)
            ]);
            
            // Mapear headers a campos de participantes
            $participantCount = 0;
            $participants = []; // Array para almacenar los participantes creados
            
            foreach ($rows as $rowIndex => $row) {
                // Saltar filas vacías
                if (empty(array_filter($row))) {
                    Log::info('Fila vacía encontrada', ['row_index' => $rowIndex]);
                    continue;
                }
                
                // Asegurar que la fila tenga el mismo número de columnas que los headers
                while (count($row) < count($headers)) {
                    $row[] = '';
                }
                
                $participantData = array_combine($headers, $row);
                
                Log::info('Procesando participante', [
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
                    'individual_price' => 0, // Se calculará después
                    'price_adjustments' => 0,
                ]);
                
                $participants[] = $participant; // Guardar referencia al participante
                
                Log::info('Participante creado', [
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
                    
                    Log::info('Contacto de emergencia creado', [
                        'emergency_contact_id' => $emergencyContact->id,
                        'participant_id' => $participant->id,
                        'nombre_completo' => $emergencyContact->first_name . ' ' . $emergencyContact->last_name
                    ]);
                } else {
                    Log::warning('No se creó contacto de emergencia - datos faltantes', [
                        'participant_id' => $participant->id,
                        'has_nombre' => !empty($participantData['Nombre contacto emergencia']),
                        'has_apellido' => !empty($participantData['Apellido contacto emergencia'])
                    ]);
                }
                
                $participantCount++;
            }
            
            // Calcular el precio individual después de procesar todos los participantes
            if ($participantCount > 0) {
                $individualPrice = $program->trip_price / $participantCount;
                
                Log::info('Calculando precio individual', [
                    'program_price' => $program->trip_price,
                    'participant_count' => $participantCount,
                    'individual_price' => $individualPrice
                ]);
                
                // Actualizar el precio individual de todos los participantes
                foreach ($participants as $participant) {
                    $participant->update(['individual_price' => $individualPrice]);
                }
            }
            
            Log::info('Procesamiento completado', [
                'total_participants_created' => $participantCount,
                'course_id' => $course->id
            ]);
            
            // Actualizar el curso con el número total de estudiantes
            $course->update(['total_students' => $participantCount]);
            
        } catch (\Exception $e) {
            Log::error('Error al procesar participantes', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'course_id' => $course->id,
                'program_id' => $program->id
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

    /**
     * Map education level from frontend to database format.
     */
    private function mapEducationLevel(string $level): string
    {
        return match ($level) {
            'inicial' => 'preescolar',
            'primario' => 'primaria',
            'secundario' => 'secundaria',
            'universitario' => 'universitaria',
            default => $level,
        };
    }
} 