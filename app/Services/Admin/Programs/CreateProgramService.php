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
        if (!empty($programData['pilar_1'])) {
            $pillars[] = $programData['pilar_1'];
        }
        if (!empty($programData['pilar_2'])) {
            $pillars[] = $programData['pilar_2'];
        }
        if (!empty($programData['pilar_3'])) {
            $pillars[] = $programData['pilar_3'];
        }
        if (!empty($programData['pilar_4'])) {
            $pillars[] = $programData['pilar_4'];
        }
        $programData['pillars'] = implode(', ', $pillars);
        

            // Crear el programa primero (sin archivos por ahora)
            $autoName = $this->buildProgramName($programData);
            $program = Program::create([
                'code' => $programData['code'],
                'name' => $autoName ?? $programData['name'],
                'institution_id' => $programData['institution_id'],
                'destination' => $programData['destination'],
                'departure_date' => $programData['departure_date'],
                'trip_description' => $programData['description'] ?? $programData['trip_description'],
                'images_folder' => null, // Se actualizará después
                'pillars' => $programData['pillars'] ?? null,
                'itinerary_description' => $programData['itinerary'] ?? null,
                'itinerary_file' => null, // Se actualizará después
                'travel_assistance_coverage' => null, // Se actualizará después
                'equipment_list' => null, // Se actualizará después
                // trip_price en BD almacena el total del programa (precio por participante FINAL x #participantes)
                // Inicialmente 0; se recalculará tras procesar participantes
                'trip_price' => 0,
                'year' => (int) date('Y', strtotime($programData['departure_date'])),
                'final_payment_date' => $programData['final_payment_date'],
                'seller_name' => null,
                'sales_executive_id' => $programData['sales_executive_id'],
                
                // Configuración de pago total
                'enable_total_payment' => $this->isTotalPaymentEnabled($programData),
                // Mapear nombres semánticos del frontend a IDs sembrados por PaymentMethodSeeder
                // Eliminado: total_payment_method_id (usamos payment_options + pivote)
                
                // Configuración de pago mensual Lat90
                'enable_lat90_payment' => $this->isLat90PaymentEnabled($programData),
                // Usa el mismo mapeo de métodos que pago total
                // Eliminado: lat90_payment_method_id (usamos payment_options + pivote)
                'lat90_max_installments' => $this->getLat90MaxInstallments($programData),
                
                // Campos de descuento
                'discount_type' => $programData['discount_type'] ?? $programData['group_benefit'] ?? null,
                'discount_value' => $this->calculateDiscountValue($programData),
                'created_by' => auth()->id(),
                'active' => $programData['active'] ?? true,
            ]);



            // Procesar archivos después de crear el programa para poder usar su ID
            $processedData = $this->processFiles($programData, $program);
            
            // Actualizar el programa con las rutas de los archivos
            $program->update([
                'images_folder' => $processedData['images_folder'] ?? null,
                'itinerary_file' => $processedData['itinerary_file_path'] ?? null,
                'travel_assistance_coverage' => $processedData['coverage_file_path'] ?? null,
                'equipment_list' => $processedData['equipment_file_path'] ?? null,
            ]);

            // Lógica para crear curso y participantes si se proporcionan los datos (opcional)
            // Relajamos la condición: si hay institución y se sube students_file, creamos el curso aunque no venga education_level
            if (!empty($programData['institution_id']) && (
                !empty($programData['education_level']) || !empty($programData['students_file'])
            )) {
                
                // Crear el curso
                $course = $this->createCourse($programData, $program);
                
                // Asignar el curso al programa
                $program->update(['course_id' => $course->id]);
                
                // Procesar participantes si se proporciona el archivo
                if (!empty($programData['students_file'])) {
                    $this->processParticipants($programData['students_file'], $course, $program);
                }
            }

            // Guardar opciones de pago seleccionadas (program_payment_option)
            $this->syncProgramPaymentOptions($program, $programData);

            // Recalcular y actualizar el total del programa (trip_price) según #participantes y precio por participante final
            $this->recalculateProgramTotal($program, $programData);

            // Asegurar participant_program para todos los participantes del curso (SIEMPRE)
            $program->load('course.participants');
            if ($program->course && $program->course->participants) {
                $participants = $program->course->participants;
                foreach ($participants as $p) {
                    $this->ensureParticipantProgram($p, $program, $p->pivot->individual_price ?? ($p->individual_price ?? null));
                }
            }

            DB::commit();
            return $program;

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
    /**
     * Sincroniza las opciones de pago habilitadas para el programa
     */
    private function syncProgramPaymentOptions(Program $program, array $programData): void
    {
        $codes = [];
        $full = $programData['full_payment_options'] ?? [];
        $lat90 = $programData['lat90_payment_options'] ?? [];
        if (is_array($full)) { $codes = array_merge($codes, $full); }
        if (is_array($lat90)) { $codes = array_merge($codes, $lat90); }
        $codes = array_values(array_unique($codes));

        if (empty($codes)) {
            // No hay opciones marcadas: dejar vacío
            DB::table('program_payment_option')->where('program_id', $program->id)->delete();
            return;
        }

        $optionIds = DB::table('payment_options')
            ->whereIn('code', $codes)
            ->pluck('id')
            ->toArray();

        // Limpiar actuales
        DB::table('program_payment_option')->where('program_id', $program->id)->delete();

        // Insertar nuevas
        $now = now();
        $rows = array_map(fn($id) => [
            'program_id' => $program->id,
            'payment_option_id' => $id,
            'enabled' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ], $optionIds);
        if (!empty($rows)) {
            DB::table('program_payment_option')->insert($rows);
        }
    }

    /**
     * Construye el nombre del programa a partir de institución, curso, destino y año.
     * Si faltan datos clave, retorna null (para usar el nombre manual).
     */
    private function buildProgramName(array $programData): ?string
    {
        $institutionName = null;
        if (!empty($programData['institution_id'])) {
            $inst = \App\Models\Institution::find($programData['institution_id']);
            $institutionName = $inst?->name;
        } elseif (!empty($programData['institution_name'])) {
            $institutionName = $programData['institution_name'];
        }

        $course = null;
        if (!empty($programData['education_level']) || !empty($programData['course_number'])) {
            $level = $this->mapEducationLevel($programData['education_level'] ?? '');
            $num = $programData['course_number'] ?? '';
            $course = trim(($num ? ($num . '° ') : '') . ($level ?: ''));
        }

        $destination = $programData['destination'] ?? null;
        $year = (int) ($programData['year'] ?? date('Y'));

        if ($institutionName && $course && $destination && $year) {
            return sprintf('%s - %s - %s - %d', $institutionName, $course, $destination, $year);
        }
        return null;
    }

    /**
     * Process uploaded files and store them.
     */
    private function processFiles(array $programData, Program $program = null): array
    {
        // Si no tenemos el programa aún, creamos un identificador temporal
        $programId = $program ? $program->id : uniqid('temp_');
        $timestamp = now()->format('Y_m_d_H_i_s');
        
        // Crear la carpeta base del programa
        $programFolder = "public/programs/{$programId}";
        
        // Procesar archivo de itinerario
        if (isset($programData['itinerary_file']) && $programData['itinerary_file']) {
            $pdfPath = "{$programFolder}/pdfs/itinerario_{$programId}_{$timestamp}.pdf";
            $fullPath = $programData['itinerary_file']->storeAs($pdfPath, null, 'public');
            // Guardar solo la ruta relativa en la base de datos
            $programData['itinerary_file_path'] = $fullPath;
        }

        // Procesar archivo de cobertura
        if (isset($programData['coverage_file']) && $programData['coverage_file']) {
            $pdfPath = "{$programFolder}/pdfs/cobertura_{$programId}_{$timestamp}.pdf";
            $fullPath = $programData['coverage_file']->storeAs($pdfPath, null, 'public');
            // Guardar solo la ruta relativa en la base de datos
            $programData['coverage_file_path'] = $fullPath;
        }

        // Procesar archivo de lista de equipo
        if (isset($programData['equipment_file']) && $programData['equipment_file']) {
            $pdfPath = "{$programFolder}/pdfs/equipo_{$programId}_{$timestamp}.pdf";
            $fullPath = $programData['equipment_file']->storeAs($pdfPath, null, 'public');
            // Guardar solo la ruta relativa en la base de datos
            $programData['equipment_file_path'] = $fullPath;
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
                // Guardar solo la ruta de la carpeta de imágenes (sin el nombre del archivo)
                $programData['images_folder'] = $programFolder . '/images';
            }
        }

        return $programData;
    }

    /**
     * Calcula el precio final por participante aplicando descuento.
     */
    private function resolvePerParticipantFinal(array $programData): float
    {
        $base = (float) ($programData['total_price'] ?? $programData['trip_price'] ?? 0);
        $discountType = $programData['discount_type'] ?? $programData['group_benefit'] ?? null;
        $discountValue = $this->calculateDiscountValue($programData); // porcentaje (0.10) o monto fijo

        if (!$discountType || !$discountValue) {
            return round($base, 2);
        }

        if ($discountType === 'monto_fijo') {
            return max(0.0, round($base - (float) $discountValue, 2));
        }

        // porcentaje
        return max(0.0, round($base - ($base * (float) $discountValue), 2));
    }

    /**
     * Recalcula el total del programa (trip_price) como precio por participante FINAL x #participantes del curso.
     * Actualiza también el individual_price de cada participante y en el pivote participant_course.
     */
    private function recalculateProgramTotal(Program $program, array $programData): void
    {
        $program->load('course.participants');
        $course = $program->course;
        if (!$course) {
            return;
        }
        $participants = $course->participants ?? collect();
        $count = $participants->count();
        $perParticipantFinal = $this->resolvePerParticipantFinal($programData);

        // Actualizar solo el pivote participant_course
        foreach ($participants as $participant) {
            $participant->courses()->updateExistingPivot($course->id, [
                'individual_price' => $perParticipantFinal,
            ]);
        }

        $total = round($perParticipantFinal * $count, 2);
        $program->update(['trip_price' => $total]);
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
            // Usar nivel provisto o un valor por defecto ('media') cuando no venga, para no bloquear la creación
            'education_level' => $this->mapEducationLevel($programData['education_level'] ?? 'media'),
            'year' => date('Y'),
            'course_number' => $programData['course_number'] ?? null,
            'course_name' => $programData['course_name'] ?? null,
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
                // Soportar múltiples encabezados posibles para RUT/Documento
                $rutRaw = $participantData['RUT']
                    ?? $participantData['Rut']
                    ?? $participantData['rut']
                    ?? $participantData['Documento']
                    ?? $participantData['Documento de identidad']
                    ?? $participantData['Documento Identidad']
                    ?? '';
                $cleanRut = $this->cleanRut($rutRaw);
                
                
                
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
                        // Preparar RUT normalizado
                        $digitsOnly = preg_replace('/\D/', '', $cleanRut);
                        $first6 = substr($digitsOnly, 0, 6);
                        $existingParticipant->update([
                            'first_name' => $participantData['Nombre'] ?? $existingParticipant->first_name,
                            'last_name' => $participantData['Apellido'] ?? $existingParticipant->last_name,
                            'email' => isset($participantData['Email']) && $participantData['Email'] !== ''
                                ? $this->normalizeEmail($participantData['Email'])
                                : $existingParticipant->email,
                            'phone' => $participantData['Teléfono'] ?? $existingParticipant->phone,
                            'birth_date' => $participantData['Fecha de nacimiento'] ?? $existingParticipant->birth_date,
                            'address' => $participantData['Dirección'] ?? $existingParticipant->address,
                            'dietary_restrictions' => $participantData['Restricción dietaria'] ?? $existingParticipant->dietary_restrictions,
                            'medical_conditions' => $participantData['Condición médica'] ?? $existingParticipant->medical_conditions,
                            'rut_digits' => $digitsOnly ?: $existingParticipant->rut_digits,
                            'rut_first6' => $first6 ?: $existingParticipant->rut_first6,
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
                        // Asegurar registro participant_program (programa-participante), aunque ya exista
                        $this->ensureParticipantProgram($existingParticipant, $program, $pivotData['individual_price'] ?? ($existingParticipant->individual_price ?? null));
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
                    
                    
                    $digitsOnly = preg_replace('/\D/', '', $cleanRut);
                    $first6 = substr($digitsOnly, 0, 6);
                    $participant = Participant::create([
                        'first_name' => $participantData['Nombre'] ?? '',
                        'last_name' => $participantData['Apellido'] ?? '',
                        'email' => $this->normalizeEmail($participantData['Email'] ?? ''),
                        'code_phone' => '+56', // Código por defecto para Chile
                        'phone' => $participantData['Teléfono'] ?? '',
                        'document_type' => $documentType,
                        'document_number' => $cleanRut,
                        'rut_digits' => $digitsOnly,
                        'rut_first6' => $first6,
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
                    // Asegurar registro participant_program (programa-participante)
                    $this->ensureParticipantProgram($participant, $program, $pivotData['individual_price'] ?? ($participant->individual_price ?? null));
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
                            'email' => isset($participantData['Email contacto emergencia']) && $participantData['Email contacto emergencia'] !== ''
                                ? $this->normalizeEmail($participantData['Email contacto emergencia'])
                                : $existingEmergencyContact->email,
                            'phone' => $participantData['Teléfono contacto emergencia'] ?? $existingEmergencyContact->phone,
                            'birth_date' => $participantData['Fecha nacimiento contacto emergencia'] ?? $existingEmergencyContact->birth_date,
                            'relationship' => $participantData['Relación contacto emergencia'] ?? $existingEmergencyContact->relationship,
                        ]);
                        
                        
                    } else {
                        // CREATE: Crear nuevo contacto de emergencia
                        $emergencyContact = EmergencyContact::create([
                            'first_name' => $participantData['Nombre contacto emergencia'],
                            'last_name' => $participantData['Apellido contacto emergencia'],
                            'email' => $this->normalizeEmail($participantData['Email contacto emergencia'] ?? ''),
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
            
            // Calcular el precio individual (por participante) aplicando descuento del programa
            if ($participantCount > 0) {
                $tripPrice = (float) $program->trip_price;
                $discountType = $program->discount_type; // porcentaje_10 | porcentaje_15 | porcentaje_20 | monto_fijo | null
                $discountValue = $program->discount_value; // decimal (porcentaje) o monto fijo

                $discountAmount = 0.0;
                if ($discountType && $discountValue) {
                    if ($discountType === 'monto_fijo') {
                        $discountAmount = min($tripPrice, (float) $discountValue);
                    } else {
                        // Se asume discount_value en decimal (e.g., 0.10)
                        $discountAmount = round($tripPrice * (float) $discountValue, 2);
                    }
                }
                $finalTotal = max(0.0, round($tripPrice - $discountAmount, 2));
                $individualPrice = round($finalTotal / $participantCount, 2);

                // Actualizar el precio individual del participante y del pivote
                foreach ($participants as $participant) {
                    $participant->update(['individual_price' => $individualPrice]);
                    // Actualizar pivote participant_course
                    $participant->courses()->updateExistingPivot($course->id, [
                        'individual_price' => $individualPrice,
                    ]);
                }
            }
            
            
            
            // Actualizar el curso con el número total de estudiantes
            $course->update(['total_students' => $participantCount]);
            
        } catch (\Exception $e) {
            throw new \Exception('Error al procesar el archivo de estudiantes: ' . $e->getMessage());
        }
    }

    /**
     * Crea o asegura la fila en participant_program con enrollment_code = program.code + primeros 6 dígitos del RUT
     */
    private function ensureParticipantProgram(Participant $participant, Program $program, ?float $individualPrice = null): void
    {
        $code = (string) ($program->code ?? '');
        // Preferir rut_first6 si existe, si no, derivarlo de document_number
        $rutFirst6 = $participant->rut_first6;
        if (!$rutFirst6) {
            $digits = preg_replace('/\D/', '', (string) $participant->document_number);
            $rutFirst6 = substr($digits, 0, 6) ?: null;
        }
        if (!$code || !$rutFirst6) {
            return; // No podemos generar enrollment_code
        }
        $enrollmentCode = $code . $rutFirst6;

        // Evitar duplicados por la clave única (participant_id, program_id)
        \Illuminate\Support\Facades\DB::table('participant_program')->updateOrInsert(
            [
                'participant_id' => $participant->id,
                'program_id' => $program->id,
            ],
            [
                'enrollment_code' => $enrollmentCode,
                'individual_price' => $individualPrice ?? null,
                'status' => 'pending_payment',
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );
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
        // Eliminar diacríticos (acentos)
        if (class_exists('\\Normalizer')) {
            $normalized = \Normalizer::normalize($email, \Normalizer::FORM_D);
            $normalized = preg_replace('/\p{Mn}+/u', '', $normalized);
        } else {
            $normalized = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $email);
            if ($normalized === false) {
                $normalized = $email;
            }
        }
        // Quitar espacios internos accidentales
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
     * Map education level from frontend to database format.
     */
    private function mapEducationLevel(string $level): string
    {
        // Aceptar claves antiguas y nuevas, normalizar a: preescolar, basica, media, universitaria
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

    /**
     * Check if total payment is enabled.
     */
    public function isTotalPaymentEnabled(array $programData): bool
    {
        // Verificar si el pago total está habilitado basado en la selección del usuario
        return isset($programData['payment_options']) && 
               is_array($programData['payment_options']) && 
               in_array('full_payment', $programData['payment_options']);
    }

    /**
     * Get total payment method ID.
     */
    public function getTotalPaymentMethodId(array $programData): ?int
    {
        if (!$this->isTotalPaymentEnabled($programData)) {
            return null;
        }
        
        $paymentMethod = $programData['full_payment_method'] ?? '';
        
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

    /**
     * Check if Lat90 payment is enabled.
     */
    public function isLat90PaymentEnabled(array $programData): bool
    {
        // Verificar si el pago Lat90 está habilitado basado en la selección del usuario
        return isset($programData['payment_options']) && 
               is_array($programData['payment_options']) && 
               in_array('installments', $programData['payment_options']);
    }

    /**
     * Get Lat90 payment method ID.
     */
    public function getLat90PaymentMethodId(array $programData): ?int
    {
        if (!$this->isLat90PaymentEnabled($programData)) {
            return null;
        }
        $paymentMethodKey = $programData['installments_payment_method'] ?? '';
        if (!$paymentMethodKey) {
            return null;
        }

        // Aceptar mismas claves que en pago total
        $methodMapping = [
            'todos_medios' => 1, // Todos los medios (Débito/Crédito/Transferencia)
            'solo_tarjeta' => 2, // Solo pago con Tarjeta (Débito/Crédito)
            'solo_transferencia' => 3, // Solo pago transferencia
            'solo_contado' => 4, // Solo pago contado (Débito/Transferencia)
        ];
        if (isset($methodMapping[$paymentMethodKey])) {
            return $methodMapping[$paymentMethodKey];
        }

        // Fallback para claves antiguas
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

    /**
     * Get Lat90 max installments.
     */
    public function getLat90MaxInstallments(array $programData): ?int
    {
        if (!$this->isLat90PaymentEnabled($programData)) {
            return null;
        }
        return $programData['max_installments'] ?? null;
    }
} 