<?php

namespace App\Services\Admin\Courses;

use App\Models\Course;
use App\Models\Institution;
use App\Models\Program;
use App\Models\ProgramCourse;
use App\Models\Participant;
use App\Models\EmergencyContact;
use App\Services\Subscription\VirtualPosPlanService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;
use PhpOffice\PhpSpreadsheet\IOFactory;

class CourseService
{
    protected $virtualPosPlanService;

    public function __construct(VirtualPosPlanService $virtualPosPlanService)
    {
        $this->virtualPosPlanService = $virtualPosPlanService;
    }
    public function createCourse(array $data): Course
    {
        return DB::transaction(function () use ($data) {
            // 1. Crear el curso
            $course = new Course([
                'institution_id' => $data['institutionId'],
                'education_level' => $data['educationLevel'],
                'grade' => $data['grade'] ?? null,
                'year' => $data['year'],
                'course_number' => $data['courseNumber'] ?? null,
                'course_name' => $data['courseName'] ?? null,
                'contact_email' => $data['contactEmail'] ?? null,
                'contact_phone' => $data['contactPhone'] ?? null,
                'end_date' => $data['endDate'] ?? null,
                'status' => 'active',
                'created_by' => auth()->id(),
            ]);

            $course->save();

            // 2. Procesar participantes si se proporciona el archivo
            if (isset($data['studentsFile']) && $data['studentsFile'] instanceof UploadedFile) {
                $this->processParticipants($data['studentsFile'], $course);
            }

            // 3. Crear el plan de programa (ProgramCourse)
            // Nota: El status se asigna automáticamente por el trigger según la departure_date
            $programCourse = new ProgramCourse([
                'program_id' => $data['program_id'],
                'course_id' => $course->id,
                'code' => $data['code'],
                'departure_date' => $data['departure_date'],
                'trip_price' => $data['trip_price'],
                'final_payment_date' => $data['final_payment_date'],
                'year' => $data['year'],
                'sales_executive_id' => $data['sales_executive_id'] ?? null,
                'enable_total_payment' => $data['enable_total_payment'] ?? true,
                'enable_subscription_payment' => $data['enable_subscription_payment'] ?? false,
                'subscription_max_months' => $data['subscription_max_months'] ?? null,
                'virtualpos_plan_id' => $data['virtualpos_plan_id'] ?? null,
                'discount_type' => $data['discount_type'] ?? null,
                'discount_value' => $data['discount_value'] ?? null,
                // No se establece 'status' aquí - lo asigna automáticamente el trigger de la BD
                'active' => true,
                'created_by' => auth()->id(),
            ]);

            // Generar nombre del plan basado en la plantilla e institución
            $program = Program::find($data['program_id']);
            $institution = Institution::find($data['institutionId']);
            if ($program && $institution) {
                $programCourse->name = $this->generateProgramCourseName($institution, $course, $program, $data);
            }

            $programCourse->save();

            // 4. Crear registros en participant_program para cada participante del curso
            $this->createParticipantProgramRecords($course, $programCourse);

            // 5. Crear plan en VirtualPos si el pago por suscripción está habilitado
            if ($programCourse->enable_subscription_payment && $programCourse->subscription_max_months > 0) {
                $this->createVirtualPosPlan($programCourse, $program, $institution, $course);
            }

            // Recargar el curso con sus relaciones
            return $course->fresh(['institution', 'programCourses']);
        });
    }

    public function updateCourse(Course $course, array $data): Course
    {
        return DB::transaction(function () use ($course, $data) {
            // 1. Actualizar datos básicos del curso
            $course->update([
                'institution_id' => $data['institutionId'],
                'education_level' => $data['educationLevel'],
                'grade' => $data['grade'] ?? null,
                'year' => $data['year'],
                'course_number' => $data['courseNumber'] ?? null,
                'course_name' => $data['courseName'] ?? null,
                'contact_email' => $data['contactEmail'] ?? $course->contact_email,
                'contact_phone' => $data['contactPhone'] ?? $course->contact_phone,
                'end_date' => $data['endDate'] ?? $course->end_date,
            ]);

            // 2. Actualizar archivo de estudiantes si se proporcionó uno nuevo
            if (isset($data['studentsFile']) && $data['studentsFile'] instanceof UploadedFile) {
                // Delete old file if exists
                if ($course->students_file_path) {
                    Storage::disk('public')->delete($course->students_file_path);
                }

                // Procesar participantes
                $this->processParticipants($data['studentsFile'], $course);
            }

            // 3. Actualizar o crear ProgramCourse
            $program = Program::find($data['program_id']);
            $institution = Institution::find($data['institutionId']);

            if (!$program || !$institution) {
                throw new \Exception('Programa o institución no encontrados');
            }

            // Buscar el ProgramCourse existente o crear uno nuevo
            $programCourse = $course->programCourses()->first();

            if ($programCourse) {
                // Actualizar ProgramCourse existente
                $this->updateProgramCourse($programCourse, $program, $institution, $course, $data);
            } else {
                // Crear nuevo ProgramCourse
                $programCourse = $this->createProgramCourseForUpdate($course, $program, $institution, $data);
            }

            // Crear/actualizar registros en participant_program
            $this->createParticipantProgramRecords($course, $programCourse);

            // Recargar el curso con sus relaciones
            return $course->fresh(['institution', 'programCourses']);
        });
    }

    public function deleteCourse(Course $course): bool
    {
        try {
            if ($course->students_file_path) {
                Storage::disk('public')->delete($course->students_file_path);
            }
            
            return $course->delete();
        } catch (\Exception $e) {
            Log::error('Error deleting course', [
                'course_id' => $course->id,
                'error' => $e->getMessage()
            ]);
            
            return false;
        }
    }

    public function toggleStatus(Course $course): bool
    {
        try {
            $course->update([
                'status' => $course->status === 'active' ? 'inactive' : 'active'
            ]);
            
            return true;
        } catch (\Exception $e) {
            Log::error('Error toggling course status', [
                'course_id' => $course->id,
                'error' => $e->getMessage()
            ]);
            
            return false;
        }
    }

    public function regenerateProgramName(Course $course): void
    {
        // Este método ya no es necesario con la nueva arquitectura
        // Los programas (plantillas) mantienen su nombre original
        // Los nombres específicos están en program_courses
        return;
    }

    /**
     * Generar nombre específico para el plan de programa
     */
    private function generateProgramCourseName(Institution $institution, Course $course, Program $program, array $data): string
    {
        try {
            $institutionName = $institution->name;
            $level = $this->mapEducationLevel($course->education_level);
            $num = $course->course_number;
            $grade = $course->grade;

            $coursePart = $num ? $num . '° ' . $level : $level;
            if ($grade) {
                $coursePart .= ' ' . strtoupper($grade);
            }

            $destination = $program->destination;

            // Usar el año de la fecha de salida (departure_date) en lugar del año del curso
            $departureDate = $data['departure_date'] ?? null;
            $year = $departureDate ? date('Y', strtotime($departureDate)) : ($data['year'] ?? date('Y'));

            if ($institutionName && $coursePart && $destination && $year) {
                return sprintf('%s - %s - %s - %d',
                    $institutionName,
                    $coursePart,
                    $destination,
                    $year
                );
            }

            // Fallback: usar nombre de plantilla con año de departure_date
            return $program->name . ' - ' . $year;
        } catch (\Exception $e) {
            Log::error('Error generating program course name', [
                'course_id' => $course->id,
                'program_id' => $program->id,
                'error' => $e->getMessage()
            ]);

            // En caso de error, usar año de departure_date o año actual
            $year = isset($data['departure_date']) ? date('Y', strtotime($data['departure_date'])) : date('Y');
            return $program->name . ' - ' . $year;
        }
    }

    /**
     * Crear plan en VirtualPos para suscripción
     */
    private function createVirtualPosPlan(
        ProgramCourse $programCourse,
        Program $program,
        Institution $institution,
        Course $course
    ): void {
        try {
            Log::info('Creando plan en VirtualPos para ProgramCourse', [
                'program_course_id' => $programCourse->id,
                'code' => $programCourse->code,
            ]);

            // Preparar datos para el plan de VirtualPos
            $planData = [
                'code' => $programCourse->code,
                'name' => $programCourse->name,
                'trip_price' => $programCourse->trip_price,
                'max_installments' => $programCourse->subscription_max_months,
                'trip_description' => $program->trip_description ??
                    "Programa {$program->name} - {$institution->name} - {$course->education_level} {$course->year}",
            ];

            // Crear plan en VirtualPos
            $result = $this->virtualPosPlanService->createPlan($planData);

            if ($result && $result['success'] && isset($result['plan_id'])) {
                // Guardar el plan_id en el ProgramCourse
                $programCourse->virtualpos_plan_id = $result['plan_id'];
                $programCourse->save();

                Log::info('Plan de VirtualPos creado y guardado exitosamente', [
                    'program_course_id' => $programCourse->id,
                    'virtualpos_plan_id' => $result['plan_id'],
                ]);
            } else {
                Log::warning('No se pudo crear el plan en VirtualPos', [
                    'program_course_id' => $programCourse->id,
                    'error_message' => $result['error'] ?? 'Unknown error',
                    'error_code' => $result['error_code'] ?? null,
                    'full_response' => $result ?? null,
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Error al crear plan en VirtualPos', [
                'program_course_id' => $programCourse->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            // No lanzar excepción para no interrumpir la creación del curso
        }
    }

    /**
     * Actualizar ProgramCourse existente
     */
    private function updateProgramCourse(
        ProgramCourse $programCourse,
        Program $program,
        Institution $institution,
        Course $course,
        array $data
    ): void {
        try {
            // Verificar si hay cambios en el monto o cuotas que afecten el plan de VirtualPos
            $priceChanged = isset($data['trip_price']) && $data['trip_price'] != $programCourse->trip_price;
            $monthsChanged = isset($data['subscription_max_months']) &&
                $data['subscription_max_months'] != $programCourse->subscription_max_months;

            // Si el plan existe y hay cambios en el monto/cuotas, verificar suscripciones activas
            if ($programCourse->virtualpos_plan_id && ($priceChanged || $monthsChanged)) {
                $hasActiveSubscriptions = $this->virtualPosPlanService->hasActiveSubscriptions(
                    $programCourse->virtualpos_plan_id
                );

                if ($hasActiveSubscriptions) {
                    throw new \Exception(
                        'No se puede cambiar el precio o el número de cuotas porque ya existen suscripciones activas para este plan. ' .
                        'Por favor, contacte a soporte para modificar el plan.'
                    );
                }
            }

            // Actualizar datos básicos del ProgramCourse
            $programCourse->update([
                'program_id' => $data['program_id'],
                'code' => $data['code'],
                'departure_date' => $data['departure_date'],
                'trip_price' => $data['trip_price'],
                'final_payment_date' => $data['final_payment_date'],
                'year' => $data['year'],
                'sales_executive_id' => $data['sales_executive_id'] ?? null,
                'enable_total_payment' => $data['enable_total_payment'] ?? true,
                'enable_subscription_payment' => $data['enable_subscription_payment'] ?? false,
                'subscription_max_months' => $data['subscription_max_months'] ?? null,
                'discount_type' => $data['discount_type'] ?? null,
                'discount_value' => $data['discount_value'] ?? null,
            ]);

            // Actualizar nombre del plan
            $programCourse->name = $this->generateProgramCourseName($institution, $course, $program, $data);
            $programCourse->save();

            // Manejar plan de VirtualPos
            if ($data['enable_subscription_payment'] && $data['subscription_max_months'] > 0) {
                if ($programCourse->virtualpos_plan_id) {
                    // Ya existe un plan - VirtualPos NO permite actualizar planes existentes
                    // Solo se actualizan los datos locales en la base de datos
                    Log::info('Plan de VirtualPos ya existe, solo se actualizaron datos locales', [
                        'program_course_id' => $programCourse->id,
                        'virtualpos_plan_id' => $programCourse->virtualpos_plan_id,
                    ]);
                } else {
                    // No existe plan - crearlo
                    $this->createVirtualPosPlan($programCourse, $program, $institution, $course);
                }
            } elseif ($programCourse->virtualpos_plan_id && !$data['enable_subscription_payment']) {
                // Se deshabilitó el pago por suscripción - verificar si hay suscripciones activas
                $hasActiveSubscriptions = $this->virtualPosPlanService->hasActiveSubscriptions(
                    $programCourse->virtualpos_plan_id
                );

                if ($hasActiveSubscriptions) {
                    throw new \Exception(
                        'No se puede deshabilitar el pago por suscripción porque ya existen suscripciones activas para este plan.'
                    );
                }

                Log::info('Pago por suscripción deshabilitado (sin suscripciones activas)', [
                    'program_course_id' => $programCourse->id,
                    'virtualpos_plan_id' => $programCourse->virtualpos_plan_id,
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Error al actualizar ProgramCourse', [
                'program_course_id' => $programCourse->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Crear ProgramCourse para un curso que estaba sin ProgramCourse
     */
    private function createProgramCourseForUpdate(
        Course $course,
        Program $program,
        Institution $institution,
        array $data
    ): ProgramCourse {
        $programCourse = new ProgramCourse([
            'program_id' => $data['program_id'],
            'course_id' => $course->id,
            'code' => $data['code'],
            'departure_date' => $data['departure_date'],
            'trip_price' => $data['trip_price'],
            'final_payment_date' => $data['final_payment_date'],
            'year' => $data['year'],
            'sales_executive_id' => $data['sales_executive_id'] ?? null,
            'enable_total_payment' => $data['enable_total_payment'] ?? true,
            'enable_subscription_payment' => $data['enable_subscription_payment'] ?? false,
            'subscription_max_months' => $data['subscription_max_months'] ?? null,
            'discount_type' => $data['discount_type'] ?? null,
            'discount_value' => $data['discount_value'] ?? null,
            'active' => true,
            'created_by' => auth()->id(),
        ]);

        // Generar nombre del plan
        $programCourse->name = $this->generateProgramCourseName($institution, $course, $program, $data);
        $programCourse->save();

        // Crear plan en VirtualPos si está habilitado
        if ($programCourse->enable_subscription_payment && $programCourse->subscription_max_months > 0) {
            $this->createVirtualPosPlan($programCourse, $program, $institution, $course);
        }

        return $programCourse;
    }

    private function mapEducationLevel(string $level): string
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
                    'N° de documento', 'Rut del participante', 'RUT', 'Rut', 'rut', 'Documento', 'Documento del participante', 'documento del participante'
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
                            'birth_date' => $this->parseBirthDate($getFieldValue([
                                'fecha de nacimiento', 'fecha nacimiento', 'nacimiento', 'Fecha de nacimiento'
                            ])) ?? $existingParticipant->birth_date,
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
                        'birth_date' => $this->parseBirthDate($getFieldValue([
                            'fecha de nacimiento', 'fecha nacimiento', 'nacimiento', 'Fecha de nacimiento'
                        ])) ?? null,
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

                        // Obtener y limpiar RUT del apoderado
                        $guardianRut = $getFieldValue([
                            'RUT del apoderado', 'rut apoderado', 'documento apoderado'
                        ]);
                        $cleanGuardianRut = $this->cleanRut($guardianRut ?? '');

                        $existingEmergencyContact->update([
                            'name' => $this->toLowercase($guardianName) ?? $existingEmergencyContact->name,
                            'email' => $this->toLowercase($guardianEmail) ?? $existingEmergencyContact->email,
                            'document_type' => $guardianDocumentTypeId,
                            'document_number' => $cleanGuardianRut,
                            'phone' => $getFieldValue([
                                'Teléfono contacto emergencia', 'telefono contacto emergencia', 'fono contacto emergencia'
                            ]) ?? $existingEmergencyContact->phone,
                            'birth_date' => $this->parseBirthDate($getFieldValue([
                                'Fecha nacimiento contacto emergencia', 'fecha nacimiento contacto emergencia'
                            ])) ?? $existingEmergencyContact->birth_date,
                            'relationship' => $getFieldValue([
                                'Relación contacto emergencia', 'relacion contacto emergencia'
                            ]) ?? $existingEmergencyContact->relationship,
                        ]);


                    } else {
                        // CREATE: Crear nuevo contacto de emergencia

                        // Obtener tipo de documento del apoderado (por defecto RUT)
                        $guardianDocumentTypeId = $this->getDocumentTypeId('RUT');

                        // Obtener y limpiar RUT del apoderado
                        $guardianRut = $getFieldValue([
                            'RUT del apoderado', 'rut apoderado', 'documento apoderado'
                        ]);
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
                            'birth_date' => $this->parseBirthDate($getFieldValue([
                                'Fecha nacimiento contacto emergencia', 'fecha nacimiento contacto emergencia'
                            ])) ?? null,
                            'address' => null,
                            'relationship' => $getFieldValue([
                                'Relación contacto emergencia', 'relacion contacto emergencia'
                            ]) ?? 'Familiar',
                            'participant_id' => $participant->id,
                        ]);


                    }
                }

                $participantCount++;
            }



            // Actualizar el curso con el número total de estudiantes
            $course->update(['total_students' => $participantCount]);

            Log::info('Participantes procesados exitosamente', [
                'course_id' => $course->id,
                'total_students' => $participantCount,
                'participants_created' => $createdCount,
                'participants_updated' => $updatedCount,
            ]);

        } catch (\Exception $e) {
            Log::error('Error al procesar el archivo de estudiantes', [
                'course_id' => $course->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw new \Exception('Error al procesar el archivo de estudiantes: ' . $e->getMessage());
        }
    }

    /**
     * Clean RUT by removing dots and dashes.
     */
    private function cleanRut(?string $rut): string
    {
        if (empty($rut)) return '';
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
    private function normalizeGender(?string $gender): string
    {
        if (empty($gender)) return 'Masculino';

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
    private function getDocumentTypeId(?string $documentType): int
    {
        if (empty($documentType)) return 1; // Por defecto ID 1 (RUT)

        $document = \App\Models\Document::where('name', 'LIKE', "%{$documentType}%")->first();
        return $document ? $document->id : 1; // Por defecto ID 1 (RUT)
    }

    /**
     * Parsear fecha de nacimiento en diferentes formatos
     * Soporta: YYYY/MM/DD, DD/MM/YYYY, YYYY-MM-DD, DD-MM-YYYY
     */
    private function parseBirthDate(?string $dateString): ?string
    {
        if (empty($dateString)) {
            return null;
        }

        $dateString = trim($dateString);

        // Si ya es un formato válido de fecha, retornarlo
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateString)) {
            return $dateString;
        }

        // Formato YYYY/MM/DD
        if (preg_match('/^(\d{4})\/(\d{1,2})\/(\d{1,2})$/', $dateString, $matches)) {
            $year = $matches[1];
            $month = str_pad($matches[2], 2, '0', STR_PAD_LEFT);
            $day = str_pad($matches[3], 2, '0', STR_PAD_LEFT);
            return "{$year}-{$month}-{$day}";
        }

        // Formato DD/MM/YYYY
        if (preg_match('/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/', $dateString, $matches)) {
            $day = str_pad($matches[1], 2, '0', STR_PAD_LEFT);
            $month = str_pad($matches[2], 2, '0', STR_PAD_LEFT);
            $year = $matches[3];
            return "{$year}-{$month}-{$day}";
        }

        // Formato YYYY-MM-DD (con espacios)
        if (preg_match('/^(\d{4})-(\d{1,2})-(\d{1,2})$/', $dateString, $matches)) {
            $year = $matches[1];
            $month = str_pad($matches[2], 2, '0', STR_PAD_LEFT);
            $day = str_pad($matches[3], 2, '0', STR_PAD_LEFT);
            return "{$year}-{$month}-{$day}";
        }

        // Formato DD-MM-YYYY
        if (preg_match('/^(\d{1,2})-(\d{1,2})-(\d{4})$/', $dateString, $matches)) {
            $day = str_pad($matches[1], 2, '0', STR_PAD_LEFT);
            $month = str_pad($matches[2], 2, '0', STR_PAD_LEFT);
            $year = $matches[3];
            return "{$year}-{$month}-{$day}";
        }

        // Si no coincide con ningún formato, retornar null
        return null;
    }

    /**
     * Crear registros en participant_program para cada participante del curso
     */
    private function createParticipantProgramRecords(Course $course, ProgramCourse $programCourse): void
    {
        try {
            // Obtener todos los participantes del curso
            $participants = $course->participants;

            if ($participants->isEmpty()) {
                Log::info('No hay participantes para asociar al program_course', [
                    'course_id' => $course->id,
                    'program_course_id' => $programCourse->id,
                ]);
                return;
            }

            $createdCount = 0;

            foreach ($participants as $participant) {
                // Verificar si ya existe el registro (evitar duplicados)
                $exists = DB::table('participant_program')
                    ->where('participant_id', $participant->id)
                    ->where('program_id', $programCourse->id) // program_id ahora apunta a program_courses
                    ->exists();

                if ($exists) {
                    continue; // Ya existe, saltar
                }

                // Generar enrollment_code: documento_participante + program_course.code
                $enrollmentCode = $participant->document_number . '-' . $programCourse->code;

                // Obtener el precio individual del participante (del pivot o del program_course)
                $individualPrice = $participant->pivot->individual_price ?? $programCourse->trip_price;

                // Crear el registro en participant_program
                DB::table('participant_program')->insert([
                    'participant_id' => $participant->id,
                    'program_id' => $programCourse->id, // program_id ahora apunta a program_courses
                    'enrollment_code' => $enrollmentCode,
                    'individual_price' => $individualPrice,
                    'status' => 'pending_payment',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $createdCount++;
            }

            Log::info('Registros en participant_program creados exitosamente', [
                'course_id' => $course->id,
                'program_course_id' => $programCourse->id,
                'participants_count' => $participants->count(),
                'created_count' => $createdCount,
            ]);

        } catch (\Exception $e) {
            Log::error('Error al crear registros en participant_program', [
                'course_id' => $course->id,
                'program_course_id' => $programCourse->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            // No lanzar excepción para no interrumpir la creación del curso
        }
    }
}
