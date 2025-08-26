<?php

namespace App\Services\Admin\Programs;

use App\Models\Program;
use App\Models\Course;
use App\Models\Participant;
use App\Models\EmergencyContact;
use App\Traits\AdminLogging;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\IOFactory;

class UpdateProgramService
{
    use AdminLogging;
    /**
     * Execute the program update.
     */
    public function execute(array $programData, Program $program): Program
    {
        // Capturar datos originales antes de la actualización para el logging
        $originalData = $program->toArray();
        
        // LOG: Verificar qué datos llegan al método
        Log::info('UpdateProgramService: Datos recibidos al inicio', [
            'program_id' => $program->id,
            'programData_keys' => array_keys($programData),
            'arrays_received' => array_filter($programData, 'is_array'),
            'full_payment_options' => $programData['full_payment_options'] ?? 'NO PRESENTE',
            'lat90_payment_options' => $programData['lat90_payment_options'] ?? 'NO PRESENTE'
        ]);
        
        // Guardar los arrays de opciones de pago antes de filtrar
        $fullPaymentOptions = $programData['full_payment_options'] ?? [];
        $lat90PaymentOptions = $programData['lat90_payment_options'] ?? [];
        
        // Filtrar campos que no deben guardarse directamente en el modelo Program
        $programData = array_filter($programData, function($key) {
            return !in_array($key, ['full_payment_options', 'lat90_payment_options']);
        }, ARRAY_FILTER_USE_KEY);
        
        Log::info('UpdateProgramService: Datos después del filtrado inicial', [
            'program_id' => $program->id,
            'programData_keys' => array_keys($programData),
            'arrays_after_filter' => array_filter($programData, 'is_array')
        ]);
        
        try {
            DB::beginTransaction();

            Log::info('UpdateProgramService: Payload recibido', [
                'program_id' => $program->id,
                'keys' => array_keys($programData),
                'filtered_arrays' => [
                    'full_payment_options' => $fullPaymentOptions,
                    'lat90_payment_options' => $lat90PaymentOptions
                ],
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

            // Borrar archivos/imágenes marcados para eliminar
            $this->deleteMarkedFilesAndImages($programData, $program);

            // Procesar archivos antes de actualizar el programa
            $processedData = $this->processFiles($programData, $program);

            // Preparar datos para actualización (solo campos que se enviaron)
            $updateData = [];
            if (isset($programData['code'])) {
                $updateData['code'] = $programData['code'];
            }
            
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
            if (isset($programData['sales_executive_id'])) {
                $updateData['sales_executive_id'] = $programData['sales_executive_id'];
            }
            // Actualizar configuración de pagos (nuevo esquema)
            if (
                isset($programData['payment_options']) || isset($programData['payment_option']) ||
                isset($programData['full_payment_method']) || isset($programData['installments_payment_method']) ||
                isset($programData['max_installments'])
            ) {
                $updateData['enable_total_payment'] = $this->isTotalPaymentEnabled($programData);
                 // Eliminado: total_payment_method_id (usamos payment_options + pivote)
                $updateData['enable_lat90_payment'] = $this->isLat90PaymentEnabled($programData);
                 // Eliminado: lat90_payment_method_id (usamos payment_options + pivote)
                if (isset($programData['max_installments'])) {
                    $updateData['lat90_max_installments'] = $this->getLat90MaxInstallments($programData);
                }
                Log::info('UpdateProgramService: Configuración de pagos resuelta', [
                    'program_id' => $program->id,
                    'enable_total_payment' => $updateData['enable_total_payment'],
                    'enable_lat90_payment' => $updateData['enable_lat90_payment'],
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
            // Si cambian datos base, recalcular nombre automático
            $shouldRebuildName = (
                isset($programData['institution_id']) ||
                isset($programData['institution_name']) ||
                isset($programData['education_level']) ||
                isset($programData['grade']) ||
                isset($programData['course_number']) ||
                isset($programData['destination']) ||
                isset($programData['departure_date'])
            );
            if ($shouldRebuildName) {
                // Filtrar también los arrays del programa para evitar conflictos
                $programArray = $program->toArray();
                Log::info('UpdateProgramService: programArray antes de filtrar', [
                    'program_id' => $program->id,
                    'programArray_keys' => array_keys($programArray),
                    'arrays_in_programArray' => array_filter($programArray, 'is_array')
                ]);
                
                $filteredProgramArray = array_filter($programArray, function($value, $key) {
                    // Filtrar todos los arrays para evitar problemas con "Array to string conversion"
                    return !is_array($value);
                }, ARRAY_FILTER_USE_BOTH);
                
                Log::info('UpdateProgramService: programData antes del merge', [
                    'program_id' => $program->id,
                    'programData_keys' => array_keys($programData),
                    'arrays_in_programData' => array_filter($programData, 'is_array')
                ]);
                
                $merged = array_merge($filteredProgramArray, $programData);
                
                Log::info('UpdateProgramService: Datos para buildProgramNameForUpdate', [
                    'program_id' => $program->id,
                    'merged_keys' => array_keys($merged),
                    'arrays_in_merged' => array_filter($merged, 'is_array')
                ]);
                
                try {
                    $autoName = $this->buildProgramNameForUpdate($merged);
                    Log::info('UpdateProgramService: buildProgramNameForUpdate exitoso', [
                        'program_id' => $program->id,
                        'autoName' => $autoName
                    ]);
                } catch (\Exception $e) {
                    Log::error('UpdateProgramService: Error en buildProgramNameForUpdate', [
                        'program_id' => $program->id,
                        'error' => $e->getMessage(),
                        'merged_data' => $merged,
                        'trace' => $e->getTraceAsString()
                    ]);
                    throw $e;
                }
                if ($autoName) {
                    $updateData['name'] = $autoName;
                }
                // Actualizar year según departure_date si viene
                if (isset($programData['departure_date'])) {
                    $updateData['year'] = (int) date('Y', strtotime($programData['departure_date']));
                }
            }

            if (!empty($updateData)) {
                Log::info('UpdateProgramService: Campos a actualizar (primera fase)', [
                    'program_id' => $program->id,
                    'update_keys' => array_keys($updateData),
                    'update_preview' => $updateData,
                ]);
                
                // Verificar si hay arrays en updateData antes de update
                $arrayFields = [];
                foreach ($updateData as $key => $value) {
                    if (is_array($value)) {
                        $arrayFields[$key] = $value;
                    }
                }
                
                if (!empty($arrayFields)) {
                    Log::error('UpdateProgramService: ARRAYS DETECTADOS en updateData - ESTO CAUSARÁ ERROR', [
                        'program_id' => $program->id,
                        'array_fields' => $arrayFields
                    ]);
                }
                
                try {
                    $program->update($updateData);
                    Log::info('UpdateProgramService: Primera actualización exitosa', [
                        'program_id' => $program->id
                    ]);
                } catch (\Exception $e) {
                    Log::error('UpdateProgramService: Error en primera actualización', [
                        'program_id' => $program->id,
                        'error' => $e->getMessage(),
                        'updateData' => $updateData,
                        'trace' => $e->getTraceAsString()
                    ]);
                    throw $e;
                }
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
                    
                    // Verificar si hay arrays en updateData de archivos
                    $arrayFields = [];
                    foreach ($updateData as $key => $value) {
                        if (is_array($value)) {
                            $arrayFields[$key] = $value;
                        }
                    }
                    
                    if (!empty($arrayFields)) {
                        Log::error('UpdateProgramService: ARRAYS DETECTADOS en updateData de archivos - ESTO CAUSARÁ ERROR', [
                            'program_id' => $program->id,
                            'array_fields' => $arrayFields
                        ]);
                    }
                    
                    try {
                        $program->update($updateData);
                        Log::info('UpdateProgramService: Actualización de archivos exitosa', [
                            'program_id' => $program->id
                        ]);
                    } catch (\Exception $e) {
                        Log::error('UpdateProgramService: Error en actualización de archivos', [
                            'program_id' => $program->id,
                            'error' => $e->getMessage(),
                            'updateData' => $updateData,
                            'trace' => $e->getTraceAsString()
                        ]);
                        throw $e;
                    }
                }
            }

            // Lógica para crear/actualizar curso y participantes si se proporcionan los datos
            $existingCourse = Course::where('program_id', $program->id)->first();
            
            if (!$existingCourse && 
                !empty($programData['institution_id']) && 
                !empty($programData['education_level'])) {
                
                // Crear curso nuevo si no existe uno
                $course = $this->createOrUpdateCourse($programData, $program);
                
                // Asignar el curso al programa
                $program->update(['course_id' => $course->id]);
                
                // Procesar participantes si se proporciona el archivo
                if (!empty($programData['students_file'])) {
                    $this->processParticipants($programData['students_file'], $course, $program);
                }
            } elseif ($existingCourse) {
                // Si ya existe un curso, actualizarlo con los nuevos datos que afectan al nombre
                $this->updateExistingCourse($existingCourse, $programData);
                
                // Procesar participantes si se sube un nuevo archivo
                if (!empty($programData['students_file'])) {
                    $this->processParticipants($programData['students_file'], $existingCourse, $program);
                }
            }

            // Recalcular el total del programa (trip_price = precio final por participante x #participantes)
            $this->recalculateProgramTotal($program, $programData);

            // Asegurar participant_program para todos los participantes del curso (si existe curso)
            $program->load('course.participants');
            if ($program->course && $program->course->participants) {
                foreach ($program->course->participants as $participant) {
                    $this->ensureParticipantProgram($participant, $program, $participant->pivot->individual_price ?? null);
                }
            }

            // Sincronizar opciones de pago (pivote program_payment_option) si vienen nuevas
            // Usar los arrays guardados anteriormente
            $paymentOptionsData = [
                'full_payment_options' => $fullPaymentOptions,
                'lat90_payment_options' => $lat90PaymentOptions
            ];
            $this->syncProgramPaymentOptions($program, $paymentOptionsData);

            DB::commit();
            
            // Log the program update
            $freshData = $program->fresh()->toArray();
            
            // Filtrar arrays para evitar problemas con array_diff_assoc
            $filteredOriginalData = array_filter($originalData, function($value) {
                return !is_array($value);
            });
            $filteredFreshData = array_filter($freshData, function($value) {
                return !is_array($value);
            });
            
            $this->logUpdate(
                'programs',
                'Program',
                $program->id,
                "Programa actualizado: {$program->name} ({$program->code})",
                $originalData,
                $freshData,
                [
                    'program_name' => $program->name,
                    'program_code' => $program->code,
                    'destination' => $program->destination,
                    'departure_date' => $program->departure_date,
                    'trip_price' => $program->trip_price,
                    'updated_fields' => array_keys(array_diff_assoc($filteredFreshData, $filteredOriginalData)),
                ]
            );
            
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
     * Crea o asegura la fila en participant_program con enrollment_code = program.code + primeros 6 dígitos del RUT
     */
    private function ensureParticipantProgram(\App\Models\Participant $participant, Program $program, ?float $individualPrice = null): void
    {
        $enrollmentCode = \App\Helpers\EnrollmentCodeHelper::generateEnrollmentCode($program, $participant);
        
        if (!$enrollmentCode) {
            return; // No podemos generar enrollment_code
        }

        DB::table('participant_program')->updateOrInsert(
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

    private function syncProgramPaymentOptions(Program $program, array $programData): void
    {
        $codes = [];
        $full = $programData['full_payment_options'] ?? [];
        $lat90 = $programData['lat90_payment_options'] ?? [];
        if (is_array($full)) { $codes = array_merge($codes, $full); }
        if (is_array($lat90)) { $codes = array_merge($codes, $lat90); }
        $codes = array_values(array_unique($codes));

        if (!array_key_exists('full_payment_options', $programData) && !array_key_exists('lat90_payment_options', $programData)) {
            // Nada que sincronizar si no vinieron campos
            return;
        }

        // Vaciar y volver a insertar lo enviado
        DB::table('program_payment_option')->where('program_id', $program->id)->delete();
        if (empty($codes)) return;

        $optionIds = DB::table('payment_options')->whereIn('code', $codes)->pluck('id')->toArray();
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
     * Calcula el precio final por participante aplicando descuento.
     */
    private function resolvePerParticipantFinal(array $programData, Program $program): float
    {
        $base = (float) ($programData['total_price'] ?? $programData['trip_price'] ?? $program->trip_price ?? 0);
        // Cuando el base venga del request, es precio por participante; si viene del modelo puede ser total.
        if (!isset($programData['total_price']) && !isset($programData['trip_price'])) {
            // Si no vino en request, no podemos inferir precio por participante desde total con seguridad.
            // Usamos el valor actual de participantes para estimar si existe curso.
            $program->load('course.participants');
            $count = $program->course?->participants?->count() ?? 0;
            if ($count > 0) {
                $base = round(((float) $program->trip_price) / $count, 2);
            }
        }

        $discountType = $programData['discount_type'] ?? $programData['group_benefit'] ?? ($program->discount_type ?? null);
        $discountValue = isset($programData['discount_type']) || isset($programData['group_benefit'])
            ? $this->calculateDiscountValue($programData)
            : $program->discount_value;

        if (!$discountType || !$discountValue) {
            return round($base, 2);
        }

        if ($discountType === 'monto_fijo') {
            return max(0.0, round($base - (float) $discountValue, 2));
        }

        return max(0.0, round($base - ($base * (float) $discountValue), 2));
    }

    /**
     * Recalcula y actualiza el total del programa y sincroniza individual_price/pivote.
     */
    private function recalculateProgramTotal(Program $program, array $programData): void
    {
        $program->load('course.participants');
        $course = $program->course;
        if (!$course) return;

        $participants = $course->participants ?? collect();
        $count = $participants->count();
        if ($count <= 0) return;

        $perParticipantFinal = $this->resolvePerParticipantFinal($programData, $program);

        foreach ($participants as $participant) {
            $participant->courses()->updateExistingPivot($course->id, [
                'individual_price' => $perParticipantFinal,
            ]);
        }

        $total = round($perParticipantFinal * $count, 2);
        $program->update(['trip_price' => $total]);
    }

    /**
     * Process uploaded files and store them, removing old files if new ones are uploaded.
     */
    private function processFiles(array $programData, Program $program): array
    {
        $programId = $program->id;
        $timestamp = now()->format('Y_m_d_H_i_s');
        $processedData = [];
        
        // Directorios base (relativos al disco 'public')
        $baseDir = "programs/{$programId}";
        $pdfDir = $baseDir . '/pdfs';
        $imagesDir = $baseDir . '/images';
        
        // Procesar archivo de itinerario
        if (isset($programData['itinerary_file']) && $programData['itinerary_file']) {
            if ($program->itinerary_file) {
                Storage::disk('public')->delete($program->itinerary_file);
            }
            $filename = "itinerario_{$programId}_{$timestamp}.pdf";
            $file = $programData['itinerary_file'];
            if (method_exists($file, 'storeAs')) {
                $file->storeAs($pdfDir, $filename, 'public');
            } else {
                Storage::disk('public')->put($pdfDir . '/' . $filename, file_get_contents($file));
            }
            $processedData['itinerary_file_path'] = $pdfDir . '/' . $filename;
        }

        // Procesar archivo de cobertura
        if (isset($programData['coverage_file']) && $programData['coverage_file']) {
            if ($program->travel_assistance_coverage) {
                Storage::disk('public')->delete($program->travel_assistance_coverage);
            }
            $filename = "cobertura_{$programId}_{$timestamp}.pdf";
            $file = $programData['coverage_file'];
            if (method_exists($file, 'storeAs')) {
                $file->storeAs($pdfDir, $filename, 'public');
            } else {
                Storage::disk('public')->put($pdfDir . '/' . $filename, file_get_contents($file));
            }
            $processedData['coverage_file_path'] = $pdfDir . '/' . $filename;
        }

        // Procesar archivo de lista de equipo
        if (isset($programData['equipment_file']) && $programData['equipment_file']) {
            if ($program->equipment_list) {
                Storage::disk('public')->delete($program->equipment_list);
            }
            $filename = "equipo_{$programId}_{$timestamp}.pdf";
            $file = $programData['equipment_file'];
            if (method_exists($file, 'storeAs')) {
                $file->storeAs($pdfDir, $filename, 'public');
            } else {
                Storage::disk('public')->put($pdfDir . '/' . $filename, file_get_contents($file));
            }
            $processedData['equipment_file_path'] = $pdfDir . '/' . $filename;
        }

        // Procesar imágenes si se proporcionaron
        if (isset($programData['images']) && is_array($programData['images'])) {
            $storedAny = false;
            foreach ($programData['images'] as $index => $image) {
                if ($image && $image->isValid()) {
                    $ext = $image->getClientOriginalExtension();
                    $filename = "imagen_{$programId}_{$timestamp}_{$index}.{$ext}";
                    if (method_exists($image, 'storeAs')) {
                        $image->storeAs($imagesDir, $filename, 'public');
                    } else {
                        Storage::disk('public')->put($imagesDir . '/' . $filename, file_get_contents($image));
                    }
                    $storedAny = true;
                }
            }
            if ($storedAny) {
                $processedData['images_folder'] = $imagesDir; // sin prefijo 'public/'
            }
        }

        return $processedData;
    }

    /**
     * Elimina archivos pdf y/o imágenes existentes marcadas para eliminar desde el frontend.
     * Espera en $programData:
     *  - filesToDelete: [ 'itinerary'|'coverage'|'equipment' ]
     *  - imagesToDelete: array de índices de imágenes existentes (según orden del disco)
     */
    private function deleteMarkedFilesAndImages(array $programData, Program $program): void
    {
        // Eliminar PDFs
        if (!empty($programData['filesToDelete']) && is_array($programData['filesToDelete'])) {
            $filesToDelete = $programData['filesToDelete'];
            if (in_array('itinerary', $filesToDelete) && $program->itinerary_file) {
                Storage::disk('public')->delete($program->itinerary_file);
                $program->update(['itinerary_file' => null]);
            }
            if (in_array('coverage', $filesToDelete) && $program->travel_assistance_coverage) {
                Storage::disk('public')->delete($program->travel_assistance_coverage);
                $program->update(['travel_assistance_coverage' => null]);
            }
            if (in_array('equipment', $filesToDelete) && $program->equipment_list) {
                Storage::disk('public')->delete($program->equipment_list);
                $program->update(['equipment_list' => null]);
            }
        }

        // Eliminar imágenes por índice (según orden del folder)
        if (!empty($programData['imagesToDelete']) && is_array($programData['imagesToDelete'])) {
            if ($program->images_folder) {
                $relativePath = str_replace('public/', '', $program->images_folder);
                $folderPath = storage_path('app/public/' . $relativePath);
                if (is_dir($folderPath)) {
                    // Listar archivos del directorio y ordenarlos alfabéticamente (estable)
                    $files = array_values(array_filter(scandir($folderPath), function ($f) use ($folderPath) {
                        return is_file($folderPath . DIRECTORY_SEPARATOR . $f);
                    }));
                    sort($files);
                    $indices = array_map('intval', $programData['imagesToDelete']);
                    foreach ($indices as $idx) {
                        if (isset($files[$idx])) {
                            $filePath = $relativePath . '/' . $files[$idx];
                            Storage::disk('public')->delete($filePath);
                        }
                    }
                }
            }
        }
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
     * Construye el nombre del programa para actualización usando los datos disponibles (program + payload).
     */
    private function buildProgramNameForUpdate(array $data): ?string
    {
        // Institution
        $institutionName = null;
        if (!empty($data['institution_id'])) {
            $inst = \App\Models\Institution::find($data['institution_id']);
            $institutionName = $inst?->name;
        } elseif (!empty($data['institution_name'])) {
            $institutionName = $data['institution_name'];
        }

        // Course
        $course = null;
        if (!empty($data['education_level']) || !empty($data['course_number']) || !empty($data['grade'])) {
            $level = $this->mapEducationLevel($data['education_level'] ?? '');
            $num = $data['course_number'] ?? '';
            $grade = $data['grade'] ?? '';
            $course = trim(($num ? ($num . '° ') : '') . ($level ?: ''));
            // Agregar grado si existe
            if ($grade) {
                $course .= ' ' . strtoupper($grade);
            }
        }

        $destination = $data['destination'] ?? null;
        $depDate = $data['departure_date'] ?? null;
        $year = $depDate ? (int) date('Y', strtotime($depDate)) : ($data['year'] ?? null);

        if ($institutionName && $course && $destination && $year) {
            return sprintf('%s - %s - %s - %d', $institutionName, $course, $destination, $year);
        }
        return null;
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
            'grade' => $programData['grade'] ?? null,
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
                            'first_last_name' => $participantData['Primer apellido'] ?? $existingParticipant->first_last_name,
                            'second_last_name' => $participantData['Segundo apellido'] ?? $existingParticipant->second_last_name,
                            'first_name' => $participantData['Primer Nombre'] ?? $existingParticipant->first_name,
                            'second_name' => $participantData['Segundo Nombre'] ?? $existingParticipant->second_name,
                            'email' => isset($participantData['Email']) && $participantData['Email'] !== ''
                                ? $this->normalizeEmail($participantData['Email'])
                                : $existingParticipant->email,
                            'phone' => $participantData['Teléfono'] ?? $existingParticipant->phone,
                            'birth_date' => $participantData['Fecha de nacimiento'] ?? $existingParticipant->birth_date,
                            'address' => $participantData['Dirección'] ?? $existingParticipant->address,
                            'dietary_restrictions' => $participantData['Restricción dietaria'] ?? $existingParticipant->dietary_restrictions,
                            'intolerances' => $participantData['Intolerancia'] ?? $existingParticipant->intolerances,
                            'allergies' => $participantData['Alergias'] ?? $existingParticipant->allergies,
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
                        'first_last_name' => $participantData['Primer apellido'] ?? '',
                        'second_last_name' => $participantData['Segundo apellido'] ?? '',
                        'first_name' => $participantData['Primer Nombre'] ?? '',
                        'second_name' => $participantData['Segundo Nombre'] ?? '',
                        'email' => $this->normalizeEmail($participantData['Email'] ?? ''),
                        'code_phone' => '+56', // Código por defecto para Chile
                        'phone' => $participantData['Teléfono'] ?? '',
                        'document_type' => $documentType,
                        'document_number' => $cleanRut,
                        'country' => 'CL', // Chile por defecto
                        'birth_date' => $participantData['Fecha de nacimiento'] ?? null,
                        'address' => $participantData['Dirección'] ?? null,
                        'dietary_restrictions' => $participantData['Restricción dietaria'] ?? null,
                        'intolerances' => $participantData['Intolerancia'] ?? null,
                        'allergies' => $participantData['Alergias'] ?? null,
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
                if (!empty($participantData['Nombre contacto emergencia'])) {
                    
                    // Verificar si ya existe un contacto de emergencia para este participante
                    $existingEmergencyContact = EmergencyContact::where('participant_id', $participant->id)->first();
                    
                    if ($existingEmergencyContact) {
                        // UPDATE: Actualizar contacto de emergencia existente
                        $existingEmergencyContact->update([
                            'name' => $participantData['Nombre contacto emergencia'],
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
                            'name' => $participantData['Nombre contacto emergencia'],
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
                $discountType = $program->discount_type;
                $discountValue = $program->discount_value;

                $discountAmount = 0.0;
                if ($discountType && $discountValue) {
                    if ($discountType === 'monto_fijo') {
                        $discountAmount = min($tripPrice, (float) $discountValue);
                    } else {
                        $discountAmount = round($tripPrice * (float) $discountValue, 2);
                    }
                }
                $finalTotal = max(0.0, round($tripPrice - $discountAmount, 2));
                $individualPrice = round($finalTotal / $participantCount, 2);

                // Actualizar el precio individual del participante y del pivote
                foreach ($participants as $participant) {
                    $participant->update(['individual_price' => $individualPrice]);
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
        
        // Si contiene letras, es un pasaporte
        if (preg_match('/[A-Za-z]/', $documentNumber)) {
            return 'PASSPORT';
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
        // Aceptar mismas claves que en pago total
        $methodMapping = [
            'todos_medios' => 1,
            'solo_tarjeta' => 2,
            'solo_transferencia' => 3,
            'solo_contado' => 4,
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

    /**
     * Update existing course with new data that affects the program name.
     */
    private function updateExistingCourse(Course $course, array $programData): void
    {
        $updateData = [];
        
        // Solo actualizar campos que afectan al nombre del programa
        if (isset($programData['institution_id'])) {
            $updateData['institution_id'] = $programData['institution_id'];
        }
        if (isset($programData['education_level'])) {
            $updateData['education_level'] = $this->mapEducationLevel($programData['education_level']);
        }
        if (isset($programData['grade'])) {
            $updateData['grade'] = $programData['grade'];
        }
        if (isset($programData['course_number'])) {
            $updateData['course_number'] = $programData['course_number'];
        }
        if (isset($programData['course_name'])) {
            $updateData['course_name'] = $programData['course_name'];
        }
        if (isset($programData['contact_email'])) {
            $updateData['contact_email'] = $programData['contact_email'];
        }
        if (isset($programData['contact_phone'])) {
            $updateData['contact_phone'] = $programData['contact_phone'];
        }
        if (isset($programData['final_payment_date'])) {
            $updateData['end_date'] = $programData['final_payment_date'];
        }
        
        // Solo actualizar si hay datos para cambiar
        if (!empty($updateData)) {
            Log::info('UpdateProgramService: Actualizando curso existente', [
                'course_id' => $course->id,
                'update_data' => $updateData,
            ]);
            $course->update($updateData);
        }
    }
}
