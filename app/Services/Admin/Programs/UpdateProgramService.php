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

class UpdateProgramService
{
    /**
     * Execute the program update.
     */
    public function execute(array $programData, Program $program): Program
    {
        try {
            DB::beginTransaction();

            Log::info('UpdateProgramService: Payload recibido', [
                'program_id' => $program->id,
                'keys' => array_keys($programData),
                'payment_option' => $programData['payment_option'] ?? null,
                'payment_options' => $programData['payment_options'] ?? null,
                'full_payment_method' => $programData['full_payment_method'] ?? null,
                'installments_payment_method' => $programData['installments_payment_method'] ?? null,
                'max_installments' => $programData['max_installments'] ?? null,
            ]);

            // Procesar los pilares solo si alguno fue enviado desde el frontend
            $hasAnyPillarInput = array_key_exists('pilar_1', $programData)
                || array_key_exists('pilar_2', $programData)
                || array_key_exists('pilar_3', $programData)
                || array_key_exists('pilar_4', $programData);

            if ($hasAnyPillarInput) {
                $pillars = [];
                if (isset($programData['pilar_1']) && $programData['pilar_1'] !== '') {
                    $pillars[] = $programData['pilar_1'];
                }
                if (isset($programData['pilar_2']) && $programData['pilar_2'] !== '') {
                    $pillars[] = $programData['pilar_2'];
                }
                if (isset($programData['pilar_3']) && $programData['pilar_3'] !== '') {
                    $pillars[] = $programData['pilar_3'];
                }
                if (isset($programData['pilar_4']) && $programData['pilar_4'] !== '') {
                    $pillars[] = $programData['pilar_4'];
                }
                $programData['pillars'] = implode(', ', $pillars);
                Log::info('UpdateProgramService: Pilares procesados', [
                    'program_id' => $program->id,
                    'pillars' => $programData['pillars']
                ]);
            }

            // Procesar archivos antes de actualizar el programa
            $processedData = $this->processFiles($programData, $program);

            // Preparar datos para actualización (solo campos que se enviaron)
            $updateData = [];
            
            if (isset($programData['name'])) {
                $updateData['name'] = $programData['name'];
            }
            if (isset($programData['destination'])) {
                $updateData['destination'] = $programData['destination'];
            }
            if (isset($programData['departure_date'])) {
                $updateData['departure_date'] = $programData['departure_date'];
            }
            if (isset($programData['description']) || isset($programData['trip_description'])) {
                $updateData['trip_description'] = $programData['description'] ?? $programData['trip_description'];
            }
            if (array_key_exists('pillars', $programData)) {
                $updateData['pillars'] = $programData['pillars'];
            }
            if (isset($programData['itinerary'])) {
                $updateData['itinerary_description'] = $programData['itinerary'];
            }
            if (isset($programData['total_price']) || isset($programData['trip_price'])) {
                $updateData['trip_price'] = $programData['total_price'] ?? $programData['trip_price'];
            }
            if (isset($programData['final_payment_date'])) {
                $updateData['final_payment_date'] = $programData['final_payment_date'];
            }
            if (isset($programData['sales_person']) || isset($programData['seller_name'])) {
                $updateData['seller_name'] = $programData['sales_person'] ?? $programData['seller_name'];
            }
            // Actualizar configuración de pagos (nuevo esquema)
            if (
                isset($programData['payment_options']) || isset($programData['payment_option']) ||
                isset($programData['full_payment_method']) || isset($programData['installments_payment_method']) ||
                isset($programData['max_installments'])
            ) {
                $updateData['enable_total_payment'] = $this->isTotalPaymentEnabled($programData);
                $updateData['total_payment_method_id'] = $this->getTotalPaymentMethodId($programData);
                $updateData['enable_lat90_payment'] = $this->isLat90PaymentEnabled($programData);
                $updateData['lat90_payment_method_id'] = $this->getLat90PaymentMethodId($programData);
                if (isset($programData['max_installments'])) {
                    $updateData['lat90_max_installments'] = $this->getLat90MaxInstallments($programData);
                }
                Log::info('UpdateProgramService: Configuración de pagos resuelta', [
                    'program_id' => $program->id,
                    'enable_total_payment' => $updateData['enable_total_payment'],
                    'total_payment_method_id' => $updateData['total_payment_method_id'],
                    'enable_lat90_payment' => $updateData['enable_lat90_payment'],
                    'lat90_payment_method_id' => $updateData['lat90_payment_method_id'],
                    'lat90_max_installments' => $updateData['lat90_max_installments'] ?? null,
                ]);
            }
            if (isset($programData['discount_type']) || isset($programData['group_benefit'])) {
                $updateData['discount_type'] = $programData['discount_type'] ?? $programData['group_benefit'];
            }
            if (isset($programData['discount_amount'])) {
                $updateData['discount_value'] = $this->calculateDiscountValue($programData);
            }
            if (isset($programData['active'])) {
                $updateData['active'] = $programData['active'];
            }

            // Actualizar el programa solo si hay datos para actualizar
            if (!empty($updateData)) {
                Log::info('UpdateProgramService: Campos a actualizar (primera fase)', [
                    'program_id' => $program->id,
                    'update_keys' => array_keys($updateData),
                    'update_preview' => $updateData,
                ]);
                $program->update($updateData);
            }

            // Actualizar rutas de archivos si se procesaron nuevos
            if (!empty($processedData)) {
                $updateData = [];
                
                if (isset($processedData['images_folder'])) {
                    $updateData['images_folder'] = $processedData['images_folder'];
                }
                if (isset($processedData['itinerary_file_path'])) {
                    $updateData['itinerary_file'] = $processedData['itinerary_file_path'];
                }
                if (isset($processedData['coverage_file_path'])) {
                    $updateData['travel_assistance_coverage'] = $processedData['coverage_file_path'];
                }
                if (isset($processedData['equipment_file_path'])) {
                    $updateData['equipment_list'] = $processedData['equipment_file_path'];
                }
                
                if (!empty($updateData)) {
                    Log::info('UpdateProgramService: Campos de archivos a actualizar', [
                        'program_id' => $program->id,
                        'update_keys' => array_keys($updateData),
                        'update_preview' => $updateData,
                    ]);
                    $program->update($updateData);
                }
            }

            // Lógica para crear/actualizar curso y participantes si se proporcionan los datos
            // Solo crear curso si no existe uno ya
            $existingCourse = Course::where('program_id', $program->id)->first();
            
            if (!$existingCourse && 
                !empty($programData['institution_id']) && 
                !empty($programData['education_level'])) {
                
                // Solo crear curso si no existe uno
                $course = $this->createOrUpdateCourse($programData, $program);
                
                // Asignar el curso al programa
                $program->update(['course_id' => $course->id]);
                
                // Procesar participantes si se proporciona el archivo
                if (!empty($programData['students_file'])) {
                    $this->processParticipants($programData['students_file'], $course, $program);
                }
            } elseif ($existingCourse && !empty($programData['students_file'])) {
                // Si ya existe un curso, solo procesar participantes si se sube un nuevo archivo
                $this->processParticipants($programData['students_file'], $existingCourse, $program);
            }

            DB::commit();
            Log::info('UpdateProgramService: Actualización finalizada', [
                'program_id' => $program->id
            ]);
            return $program;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('UpdateProgramService: Error durante la actualización', [
                'program_id' => $program->id,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    /**
     * Process uploaded files and store them, removing old files if new ones are uploaded.
     */
    private function processFiles(array $programData, Program $program): array
    {
        $programId = $program->id;
        $timestamp = now()->format('Y_m_d_H_i_s');
        $processedData = [];
        
        // Crear la carpeta base del programa
        $programFolder = "public/programs/{$programId}";
        
        // Procesar archivo de itinerario
        if (isset($programData['itinerary_file']) && $programData['itinerary_file']) {
            // Eliminar archivo anterior si existe
            if ($program->itinerary_file) {
                Storage::disk('public')->delete($program->itinerary_file);
            }
            
            $pdfPath = "{$programFolder}/pdfs/itinerario_{$programId}_{$timestamp}.pdf";
            $fullPath = $programData['itinerary_file']->storeAs($pdfPath, null, 'public');
            $processedData['itinerary_file_path'] = $fullPath;
        }

        // Procesar archivo de cobertura
        if (isset($programData['coverage_file']) && $programData['coverage_file']) {
            // Eliminar archivo anterior si existe
            if ($program->travel_assistance_coverage) {
                Storage::disk('public')->delete($program->travel_assistance_coverage);
            }
            
            $pdfPath = "{$programFolder}/pdfs/cobertura_{$programId}_{$timestamp}.pdf";
            $fullPath = $programData['coverage_file']->storeAs($pdfPath, null, 'public');
            $processedData['coverage_file_path'] = $fullPath;
        }

        // Procesar archivo de lista de equipo
        if (isset($programData['equipment_file']) && $programData['equipment_file']) {
            // Eliminar archivo anterior si existe
            if ($program->equipment_list) {
                Storage::disk('public')->delete($program->equipment_list);
            }
            
            $pdfPath = "{$programFolder}/pdfs/equipo_{$programId}_{$timestamp}.pdf";
            $fullPath = $programData['equipment_file']->storeAs($pdfPath, null, 'public');
            $processedData['equipment_file_path'] = $fullPath;
        }

        // Procesar imágenes si se proporcionaron
        if (isset($programData['images']) && is_array($programData['images'])) {
            $imagePaths = [];
            foreach ($programData['images'] as $index => $image) {
                if ($image && $image->isValid()) {
                    $imagePath = "{$programFolder}/images/imagen_{$programId}_{$timestamp}_{$index}.{$image->getClientOriginalExtension()}";
                    $fullPath = $image->storeAs($imagePath, null, 'public');
                    $imagePaths[] = $fullPath;
                }
            }
            if (!empty($imagePaths)) {
                $processedData['images_folder'] = $programFolder . '/images';
            }
        }

        return $processedData;
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
    private function createOrUpdateCourse(array $programData, Program $program): Course
    {
        // Solo crear cursos nuevos, no actualizar existentes
        $course = Course::create([
            'institution_id' => $programData['institution_id'],
            'education_level' => $this->mapEducationLevel($programData['education_level']),
            'year' => date('Y'),
            'course_number' => $programData['course_number'] ?? null,
            'course_name' => $programData['course_name'] ?? null,
            'contact_email' => $programData['contact_email'] ?? '',
            'contact_phone' => $programData['contact_phone'] ?? '',
            'program_id' => $program->id,
            'end_date' => $programData['final_payment_date'] ?? null,
            'status' => 'active',
            'created_by' => auth()->id(),
        ]);
        
        return $course;
    }

    /**
     * Process participants from uploaded file.
     */
    private function processParticipants($file, Course $course, Program $program): void
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
            $participants = []; // Array para almacenar los participantes procesados
            
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
                        'individual_price' => 0, // Se calculará después
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
                
                $participants[] = $participant; // Guardar referencia al participante
                
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
            
            // Calcular el precio individual después de procesar todos los participantes
            if ($participantCount > 0) {
                $individualPrice = $program->trip_price / $participantCount;
                
                
                
                // Actualizar el precio individual de todos los participantes
                foreach ($participants as $participant) {
                    $participant->update(['individual_price' => $individualPrice]);
                }
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

    /**
     * Map education level from frontend to database format.
     */
    private function mapEducationLevel(string $level): string
    {
        return match ($level) {
            'inicial' => 'preescolar',
            'primario', 'primaria', 'basica' => 'basica',
            'secundario', 'secundaria', 'media' => 'media',
            'universitario', 'universitaria' => 'universitaria',
            'preescolar' => 'preescolar',
            default => $level,
        };
    }

    /**
     * Get payment method ID based on selected payment option and method.
     */
    private function getPaymentMethodId(array $programData): ?int
    {
        $paymentOption = $programData['payment_option'] ?? '';
        $paymentMethod = null;

        if ($paymentOption === 'full_payment') {
            $paymentMethod = $programData['full_payment_method'] ?? '';
        } elseif ($paymentOption === 'installments') {
            $paymentMethod = $programData['installments_payment_method'] ?? '';
        }

        if (!$paymentMethod) {
            return null;
        }

        // Mapear los valores del frontend a los IDs de la base de datos
        $methodMapping = [
            'todos_medios' => 1, // Todos los medios (Débito/Crédito/Transferencia)
            'solo_tarjeta' => 2, // Solo pago con Tarjeta (Débito/Crédito)
            'solo_transferencia' => 3, // Solo pago transferencia
            'solo_contado' => 4, // Solo pago contado (Débito/Transferencia)
        ];

        return $methodMapping[$paymentMethod] ?? null;
    }

    // === Nuevo esquema de pagos (paridad con CreateProgramService) ===
    public function isTotalPaymentEnabled(array $programData): bool
    {
        if (isset($programData['payment_options']) && is_array($programData['payment_options'])) {
            return in_array('full_payment', $programData['payment_options']);
        }
        return ($programData['payment_option'] ?? '') === 'full_payment';
    }

    public function getTotalPaymentMethodId(array $programData): ?int
    {
        if (!$this->isTotalPaymentEnabled($programData)) {
            return null;
        }
        $paymentMethod = $programData['full_payment_method'] ?? '';
        if (!$paymentMethod) {
            return null;
        }
        $methodMapping = [
            'todos_medios' => 1,
            'solo_tarjeta' => 2,
            'solo_transferencia' => 3,
            'solo_contado' => 4,
        ];
        return $methodMapping[$paymentMethod] ?? null;
    }

    public function isLat90PaymentEnabled(array $programData): bool
    {
        if (isset($programData['payment_options']) && is_array($programData['payment_options'])) {
            return in_array('installments', $programData['payment_options']);
        }
        return ($programData['payment_option'] ?? '') === 'installments';
    }

    public function getLat90PaymentMethodId(array $programData): ?int
    {
        if (!$this->isLat90PaymentEnabled($programData)) {
            return null;
        }
        $paymentMethodKey = $programData['installments_payment_method'] ?? '';
        if (!$paymentMethodKey) {
            return null;
        }
        $nameByKey = [
            'khipu'    => 'Transferencia bancaria (Khipu)',
            'webpay_1' => 'Débito y crédito sin cuotas (Webpay)',
            'webpay_3' => 'Débito y crédito 3 cuotas sin interés (Webpay)',
            'webpay_6' => 'Débito y crédito 6 cuotas sin interés (Webpay)',
            'webpay_12'=> 'Débito y crédito 12 cuotas sin interés (Webpay)',
        ];
        $targetName = $nameByKey[$paymentMethodKey] ?? null;
        if (!$targetName) {
            return null;
        }
        $method = \App\Models\PaymentMethod::where('name', $targetName)->first();
        return $method?->id;
    }

    public function getLat90MaxInstallments(array $programData): ?int
    {
        if (!$this->isLat90PaymentEnabled($programData)) {
            return null;
        }
        return isset($programData['max_installments']) && $programData['max_installments'] !== ''
            ? (int) $programData['max_installments']
            : null;
    }

    /**
     * Calculate discount value based on discount type.
     */
    private function calculateDiscountValue(array $programData): ?float
    {
        $discountType = $programData['discount_type'] ?? $programData['group_benefit'] ?? '';
        
        if (!$discountType) {
            return null;
        }

        // Si es monto fijo, usar el valor del input
        if ($discountType === 'monto_fijo') {
            $discountAmount = $programData['discount_amount'] ?? '';
            return $discountAmount ? (float) $discountAmount : null;
        }

        // Mapear tipos de descuento a valores
        $discountMapping = [
            'porcentaje_10' => 0.10, // 10%
            'porcentaje_15' => 0.15, // 15%
            'porcentaje_20' => 0.20, // 20%
        ];

        return $discountMapping[$discountType] ?? null;
    }
}
