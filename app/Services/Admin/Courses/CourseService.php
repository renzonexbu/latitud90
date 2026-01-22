<?php

namespace App\Services\Admin\Courses;

use App\Models\Course;
use App\Models\Institution;
use App\Models\Program;
use App\Models\ProgramCourse;
use App\Models\Participant;
use App\Models\EmergencyContact;
use App\Models\VirtualPosPlan;
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

            // 3. Calcular días mínimos antes de la salida basándose en las fechas
            $this->calculateMinDaysBeforeDeparture($data);

            // 4. Validar que las cuotas no excedan el límite permitido
            $this->validateSubscriptionMonths($data);

            // 4. Crear el plan de programa (ProgramCourse)
            // Nota: El status se asigna automáticamente por el trigger según la departure_date
            $programCourse = new ProgramCourse([
                'program_id' => $data['program_id'],
                'course_id' => $course->id,
                'code' => $data['code'],
                'destination' => $data['destination'] ?? null,
                'departure_date' => $data['departure_date'],
                'trip_price' => $data['trip_price'],
                'final_payment_date' => $data['final_payment_date'],
                'year' => $data['year'],
                'sales_executive_id' => $data['sales_executive_id'] ?? null,
                'enable_total_payment' => $data['enable_total_payment'] ?? true,
                'enable_subscription_payment' => $data['enable_subscription_payment'] ?? false,
                'subscription_max_months' => $data['subscription_max_months'] ?? null,
                'min_days_before_departure' => $data['min_days_before_departure'] ?? 30,
                'immediate_first_charge' => $data['immediate_first_charge'] ?? true,
                'virtualpos_plan_id' => $data['virtualpos_plan_id'] ?? null,
                'discount_type' => $data['discount_type'] ?? null,
                'discount_value' => $data['discount_value'] ?? null,
                // No se establece 'status' aquí - lo asigna automáticamente el trigger de la BD
                'active' => $data['active'] ?? true,
                'created_by' => auth()->id(),
            ]);

            // Generar nombre del plan basado en la plantilla e institución
            $program = Program::find($data['program_id']);
            $institution = Institution::find($data['institutionId']);
            if ($program && $institution) {
                $programCourse->name = $this->generateProgramCourseName($institution, $course, $program, $data);
            }

            $programCourse->save();

            // 4. Procesar archivos PDF del programa
            $this->processProgramFiles($programCourse, $data);

            // 5. Sincronizar opciones de pago
            $this->syncPaymentOptions($programCourse, $data);

            // 5. Crear registros en participant_program para cada participante del curso
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

            // Usar destination de $data (ProgramCourse) con fallback a program.destination
            $destination = $data['destination'] ?? $program->destination;

            // Usar el año de la fecha de salida (departure_date) en lugar del año del curso
            $departureDate = $data['departure_date'] ?? null;
            $year = $departureDate ? date('Y', strtotime($departureDate)) : ($data['year'] ?? date('Y'));

            if ($institutionName && $coursePart && $destination && $year) {
                return sprintf(
                    '%s - %s - %s - %d',
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
                'immediate_first_charge' => $programCourse->immediate_first_charge,
                'trip_description' => $program->trip_description ??
                    "Programa {$program->name} - {$institution->name} - {$course->education_level} {$course->year}",
            ];

            // Crear plan en VirtualPos
            $result = $this->virtualPosPlanService->createPlan($planData);

            if ($result && $result['success'] && isset($result['plan_id'])) {
                // Guardar el plan_id en el ProgramCourse
                $programCourse->virtualpos_plan_id = $result['plan_id'];
                $programCourse->save();

                // Guardar el plan en la tabla virtualpos_plans
                $monthlyAmount = $planData['trip_price'] / $planData['max_installments'];

                VirtualPosPlan::create([
                    'virtualpos_plan_id' => $result['plan_id'],
                    'program_course_id' => $programCourse->id,
                    'code' => $planData['code'],
                    'name' => $planData['name'],
                    'description' => $planData['trip_description'],
                    'trip_price' => $planData['trip_price'],
                    'monthly_amount' => $monthlyAmount,
                    'max_installments' => $planData['max_installments'],
                    'immediate_first_charge' => $planData['immediate_first_charge'],
                    'currency' => 'CLP',
                    'frequency_type' => 'Mensual',
                    'plan_type' => 'PROGRAMA_DE_PAGOS',
                    'is_active' => true,
                    'api_response' => $result['data'] ?? null,
                ]);

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

            // Verificar si cambió el año de la fecha de salida (afecta boleta vs contrato)
            $departureDateChanged = $this->hasDepartureDateYearChanged($programCourse, $data);

            // Si cambió el año de departure_date, verificar si hay pagos o suscripciones
            if ($departureDateChanged) {
                $hasPaymentsOrSubscriptions = $this->hasPaymentsOrSubscriptions($programCourse);

                if ($hasPaymentsOrSubscriptions) {
                    throw new \Exception(
                        'No se puede cambiar el año de la fecha de salida porque ya existen pagos o suscripciones para este programa. ' .
                        'Los documentos fiscales (boleta/contrato) ya fueron generados con la fecha anterior.'
                    );
                }
            }

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

            // Calcular días mínimos antes de la salida basándose en las fechas
            $this->calculateMinDaysBeforeDeparture($data);

            // Validar que las cuotas no excedan el límite permitido
            $this->validateSubscriptionMonths($data, $programCourse);

            // Actualizar datos básicos del ProgramCourse
            $programCourse->update([
                'program_id' => $data['program_id'],
                'code' => $data['code'],
                'destination' => $data['destination'] ?? $programCourse->destination,
                'departure_date' => $data['departure_date'],
                'trip_price' => $data['trip_price'],
                'final_payment_date' => $data['final_payment_date'],
                'year' => $data['year'],
                'sales_executive_id' => $data['sales_executive_id'] ?? null,
                'enable_total_payment' => $data['enable_total_payment'] ?? true,
                'enable_subscription_payment' => $data['enable_subscription_payment'] ?? false,
                'subscription_max_months' => $data['subscription_max_months'] ?? null,
                'min_days_before_departure' => $data['min_days_before_departure'] ?? $programCourse->min_days_before_departure ?? 30,
                'immediate_first_charge' => $data['immediate_first_charge'] ?? $programCourse->immediate_first_charge ?? true,
                'discount_type' => $data['discount_type'] ?? null,
                'discount_value' => $data['discount_value'] ?? null,
                'active' => $data['active'] ?? $programCourse->active ?? true,
            ]);

            // Actualizar nombre del plan
            $programCourse->name = $this->generateProgramCourseName($institution, $course, $program, $data);
            $programCourse->save();

            // Si el precio cambió, actualizar los precios de los participantes
            if ($priceChanged) {
                $this->updateParticipantPrices($programCourse, $data['trip_price']);
            }

            // Procesar archivos PDF del programa
            $this->processProgramFiles($programCourse, $data);

            // Sincronizar opciones de pago
            $this->syncPaymentOptions($programCourse, $data);

            // Manejar plan de VirtualPos
            if ($data['enable_subscription_payment'] && $data['subscription_max_months'] > 0) {
                if ($programCourse->virtualpos_plan_id) {
                    // Ya existe un plan - verificar si cambió immediate_first_charge y actualizar si es necesario
                    $immediateFirstChargeChanged = isset($data['immediate_first_charge']) &&
                        $data['immediate_first_charge'] != $programCourse->getOriginal('immediate_first_charge');

                    if ($priceChanged || $monthsChanged || $immediateFirstChargeChanged) {
                        // Preparar datos actualizados para el plan
                        $planData = [
                            'code' => $data['code'],
                            'name' => $this->generateProgramCourseName($institution, $course, $program, $data),
                            'trip_price' => $data['trip_price'],
                            'max_installments' => $data['subscription_max_months'],
                            'immediate_first_charge' => $data['immediate_first_charge'] ?? true,
                            'trip_description' => $program->trip_description ??
                                "Programa {$program->name} - {$institution->name} - {$course->education_level} {$course->year}",
                        ];

                        // Actualizar plan en VirtualPos
                        $result = $this->virtualPosPlanService->updatePlan($programCourse->virtualpos_plan_id, $planData);

                        if ($result && !$result['success']) {
                            Log::warning('No se pudo actualizar el plan en VirtualPos, pero se actualizaron los datos locales', [
                                'program_course_id' => $programCourse->id,
                                'virtualpos_plan_id' => $programCourse->virtualpos_plan_id,
                                'error' => $result['error'] ?? 'Unknown error'
                            ]);
                        } else {
                            Log::info('Plan de VirtualPos actualizado exitosamente', [
                                'program_course_id' => $programCourse->id,
                                'virtualpos_plan_id' => $programCourse->virtualpos_plan_id,
                                'immediate_first_charge' => $data['immediate_first_charge'] ?? true
                            ]);
                        }
                    } else {
                        Log::info('Plan de VirtualPos ya existe, sin cambios que actualizar', [
                            'program_course_id' => $programCourse->id,
                            'virtualpos_plan_id' => $programCourse->virtualpos_plan_id,
                        ]);
                    }
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
        // Calcular días mínimos antes de la salida basándose en las fechas
        $this->calculateMinDaysBeforeDeparture($data);

        // Validar que las cuotas no excedan el límite permitido
        $this->validateSubscriptionMonths($data);

        $programCourse = new ProgramCourse([
            'program_id' => $data['program_id'],
            'course_id' => $course->id,
            'code' => $data['code'],
            'destination' => $data['destination'] ?? null,
            'departure_date' => $data['departure_date'],
            'trip_price' => $data['trip_price'],
            'final_payment_date' => $data['final_payment_date'],
            'year' => $data['year'],
            'sales_executive_id' => $data['sales_executive_id'] ?? null,
            'enable_total_payment' => $data['enable_total_payment'] ?? true,
            'enable_subscription_payment' => $data['enable_subscription_payment'] ?? false,
            'subscription_max_months' => $data['subscription_max_months'] ?? null,
            'min_days_before_departure' => $data['min_days_before_departure'] ?? 30,
            'immediate_first_charge' => $data['immediate_first_charge'] ?? true,
            'discount_type' => $data['discount_type'] ?? null,
            'discount_value' => $data['discount_value'] ?? null,
            'active' => true,
            'created_by' => auth()->id(),
        ]);

        // Generar nombre del plan
        $programCourse->name = $this->generateProgramCourseName($institution, $course, $program, $data);
        $programCourse->save();

        // Sincronizar opciones de pago
        $this->syncPaymentOptions($programCourse, $data);

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
     * Calcula automáticamente los días mínimos antes de la salida
     * basándose en la diferencia entre departure_date y final_payment_date
     */
    private function calculateMinDaysBeforeDeparture(array &$data): void
    {
        if (isset($data['departure_date']) && isset($data['final_payment_date'])) {
            $departureDate = new \DateTime($data['departure_date']);
            $finalPaymentDate = new \DateTime($data['final_payment_date']);

            $interval = $finalPaymentDate->diff($departureDate);
            $data['min_days_before_departure'] = $interval->days;
        } else {
            // Si no hay ambas fechas, usar el default de 30 días
            $data['min_days_before_departure'] = $data['min_days_before_departure'] ?? 30;
        }
    }

    /**
     * Valida que el número de cuotas no exceda el máximo permitido
     * basado en la fecha final de pago (que debe ser 30 o 60 días antes de la salida)
     */
    private function validateSubscriptionMonths(array $data, ?ProgramCourse $existingProgramCourse = null): void
    {
        // Solo validar si el pago por suscripción está habilitado y hay cuotas configuradas
        $enableSubscription = $data['enable_subscription_payment'] ?? ($existingProgramCourse?->enable_subscription_payment ?? false);
        if (!$enableSubscription) {
            return;
        }

        $subscriptionMaxMonths = $data['subscription_max_months'] ?? ($existingProgramCourse?->subscription_max_months ?? null);
        if (!$subscriptionMaxMonths || $subscriptionMaxMonths <= 0) {
            return;
        }

        // Obtener valores de $data o del ProgramCourse existente
        $departureDate = $data['departure_date'] ?? ($existingProgramCourse?->departure_date?->format('Y-m-d') ?? null);
        $finalPaymentDate = $data['final_payment_date'] ?? ($existingProgramCourse?->final_payment_date?->format('Y-m-d') ?? null);
        $minDaysBeforeDeparture = $data['min_days_before_departure'] ?? ($existingProgramCourse?->min_days_before_departure ?? 30);

        // Validar que existan los datos necesarios
        if (!$departureDate) {
            throw new \Exception('Se requiere la fecha de salida para configurar pagos por suscripción.');
        }

        if (!$finalPaymentDate) {
            throw new \Exception('Se requiere la fecha final de pago para configurar pagos por suscripción.');
        }

        $subscriptionMaxMonths = (int) $subscriptionMaxMonths;
        $minDaysBeforeDeparture = (int) $minDaysBeforeDeparture;
        $departureDate = new \DateTime($departureDate);
        $finalPaymentDateTime = new \DateTime($finalPaymentDate);
        $now = new \DateTime();

        // Calcular los meses disponibles desde ahora hasta la fecha final de pago
        // Usa días exactos: cada cuota = 30 días aproximadamente
        $diffDays = $now->diff($finalPaymentDateTime)->days;

        // Si la fecha final es anterior a hoy, diffDays será positivo pero debemos considerar el signo
        if ($finalPaymentDateTime < $now) {
            $diffDays = -$diffDays;
        }

        // Cada cuota = 30 días aproximadamente
        $availableMonths = max(0, (int) floor($diffDays / 30));

        // Validar que las cuotas solicitadas no excedan el máximo disponible
        if ($subscriptionMaxMonths > $availableMonths) {
            throw new \Exception(
                "El número máximo de cuotas ({$subscriptionMaxMonths}) excede el límite permitido ({$availableMonths} meses). " .
                    "Esto se debe a que el último pago debe realizarse antes de la fecha final de pago " .
                    "({$finalPaymentDateTime->format('d/m/Y')}). Por favor, reduce el número de cuotas o ajusta la fecha final de pago."
            );
        }
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

            // Buscar la fila de encabezados automáticamente
            $headerRowIndex = $this->findHeaderRow($rows);

            if ($headerRowIndex === null) {
                throw new \Exception('No se pudo encontrar la fila de encabezados en el archivo. Asegúrate de que el archivo contenga columnas como "RUT", "Apellido Paterno", "Nombre", etc.');
            }

            // Extraer los encabezados
            $headers = $rows[$headerRowIndex];

            // Extraer información de la cabecera (opcional, para logs)
            $headerInfo = $this->extractHeaderInfo($worksheet->toArray(), $headerRowIndex);

            // Registrar información de cabecera si se encuentra
            if (!empty(array_filter($headerInfo))) {
                Log::info('Información de cabecera del Excel detectada', [
                    'course_id' => $course->id,
                    'header_info' => $headerInfo,
                ]);
            }

            // Eliminar todas las filas anteriores a los encabezados (incluyendo los encabezados)
            $rows = array_slice($rows, $headerRowIndex + 1);



            // Mapear headers a campos de participantes
            $participantCount = 0;
            $updatedCount = 0;
            $createdCount = 0;
            $errors = []; // Array para acumular errores
            $totalRows = count($rows);

            foreach ($rows as $rowIndex => $row) {
                // El número de fila para mostrar al usuario (considerando la cabecera y el header)
                // +2 porque: +1 por el índice base 0, +1 por la fila de encabezados que ya quitamos
                // Luego sumamos el índice donde encontramos los encabezados
                $displayRowNumber = $rowIndex + $headerRowIndex + 2;
                // Saltar filas vacías
                if (empty(array_filter($row))) {
                    continue;
                }

                // Asegurar que la fila tenga el mismo número de columnas que los headers
                while (count($row) < count($headers)) {
                    $row[] = '';
                }

                $participantData = array_combine($headers, $row);

                try {
                    // Función helper para obtener valor de múltiples nombres de columna
                    $getFieldValue = function ($possibleNames) use ($participantData) {
                        // Create a case-insensitive lookup map
                        $lowercaseMap = [];
                        foreach ($participantData as $key => $value) {
                            $lowercaseMap[strtolower(trim($key))] = $value;
                        }

                        foreach ($possibleNames as $name) {
                            $nameLower = strtolower(trim($name));
                            if (isset($lowercaseMap[$nameLower]) && !empty($lowercaseMap[$nameLower])) {
                                return $lowercaseMap[$nameLower];
                            }
                        }
                        return null;
                    };

                    $cleanRut = $this->cleanRut($getFieldValue([
                        'N° de documento',
                        'Rut del participante',
                        'RUT',
                        'Rut',
                        'rut',
                        'Documento',
                        'Documento del participante',
                        'documento del participante'
                    ]));

                    if (empty($cleanRut)) {
                        // Registrar error y continuar con la siguiente fila
                        $errors[] = [
                            'row' => $displayRowNumber,
                            'participant_name' => 'Desconocido (sin RUT)',
                            'error' => 'El RUT es obligatorio pero no se encontró en el archivo',
                        ];
                        continue;
                    }

                // Obtener tipo de documento del participante
                $documentType = $getFieldValue([
                    'rut/pasaporte',
                    'tipo documento',
                    'tipo de documento',
                    'documento tipo'
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
                            'first_last_name' => $this->toCapitalCase($getFieldValue([
                                'Primer apellido',
                                'primer apellido',
                                'apellido paterno',
                                'apellido'
                            ])) ?? $existingParticipant->first_last_name,
                            'second_last_name' => $this->toCapitalCase($getFieldValue([
                                'Segundo apellido',
                                'segundo apellido',
                                'apellido materno'
                            ])) ?? $existingParticipant->second_last_name,
                            'first_name' => $this->toCapitalCase($getFieldValue([
                                'Primer Nombre',
                                'primer nombre',
                                'nombre',
                                'Nombre'
                            ])) ?? $existingParticipant->first_name,
                            'second_name' => $this->toCapitalCase($getFieldValue([
                                'Segundo Nombre',
                                'segundo nombre',
                                'nombre segundo'
                            ])) ?? $existingParticipant->second_name,
                            'email' => $this->toLowercase($getFieldValue([
                                'Email',
                                'email',
                                'correo',
                                'correo electronico'
                            ])) ?? $existingParticipant->email,
                            'phone' => $getFieldValue([
                                'Teléfono',
                                'telefono',
                                'fono',
                                'celular'
                            ]) ?? $existingParticipant->phone,
                            'birth_date' => $this->parseBirthDate($getFieldValue([
                                'fecha de nacimiento',
                                'fecha nacimiento',
                                'nacimiento',
                                'Fecha de nacimiento'
                            ])) ?? $existingParticipant->birth_date,
                            'nationality' => $this->toLowercase($getFieldValue([
                                'nacionalidad',
                                'pais',
                                'origen'
                            ])) ?? $existingParticipant->nationality,
                            'gender' => $this->normalizeGender($getFieldValue([
                                'sexo',
                                'genero',
                                'género'
                            ])) ?? $existingParticipant->gender,
                            'address' => $getFieldValue([
                                'Dirección',
                                'direccion',
                                'domicilio',
                                'domicilio'
                            ]) ?? $existingParticipant->address,
                            'dietary_restrictions' => $getFieldValue([
                                'restricción alimenticia',
                                'restriccion alimenticia',
                                'restricción dietaria',
                                'restriccion dietaria',
                                'Restricción dietaria'
                            ]) ?? $existingParticipant->dietary_restrictions, // NO convertir a lowercase
                            'intolerances' => $getFieldValue([
                                'intolerancia',
                                'intolerancias'
                            ]) ?? $existingParticipant->intolerances, // NO convertir a lowercase
                            'allergies' => $getFieldValue([
                                'alergias',
                                'alergia'
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
                        'first_last_name' => $this->toCapitalCase($getFieldValue([
                            'Primer apellido',
                            'primer apellido',
                            'apellido paterno',
                            'apellido'
                        ])) ?? '',
                        'second_last_name' => $this->toCapitalCase($getFieldValue([
                            'Segundo apellido',
                            'segundo apellido',
                            'apellido materno'
                        ])) ?? '',
                        'first_name' => $this->toCapitalCase($getFieldValue([
                            'Primer Nombre',
                            'primer nombre',
                            'nombre',
                            'Nombre'
                        ])) ?? '',
                        'second_name' => $this->toCapitalCase($getFieldValue([
                            'Segundo Nombre',
                            'segundo nombre',
                            'nombre segundo'
                        ])) ?? '',
                        'email' => $this->toLowercase($getFieldValue([
                            'Email',
                            'email',
                            'correo',
                            'correo electronico'
                        ])) ?? '',
                        'code_phone' => '+56', // Código por defecto para Chile
                        'phone' => $getFieldValue([
                            'Teléfono',
                            'telefono',
                            'fono',
                            'celular'
                        ]) ?? '',
                        'document_type' => $documentTypeId,
                        'document_number' => $cleanRut,
                        'country' => 'CL', // Chile por defecto
                        'birth_date' => $this->parseBirthDate($getFieldValue([
                            'fecha de nacimiento',
                            'fecha nacimiento',
                            'nacimiento',
                            'Fecha de nacimiento'
                        ])) ?? null,
                        'nationality' => $this->toLowercase($getFieldValue([
                            'nacionalidad',
                            'pais',
                            'origen'
                        ])) ?? null,
                        'gender' => $this->normalizeGender($getFieldValue([
                            'sexo',
                            'genero',
                            'género'
                        ])),
                        'address' => $getFieldValue([
                            'Dirección',
                            'direccion',
                            'domicilio',
                            'domicilio'
                        ]) ?? null,
                        'dietary_restrictions' => $getFieldValue([
                            'restricción alimenticia',
                            'restriccion alimenticia',
                            'restricción dietaria',
                            'restriccion dietaria',
                            'Restricción dietaria'
                        ]) ?? null, // NO convertir a lowercase
                        'intolerances' => $getFieldValue([
                            'intolerancia',
                            'intolerancias'
                        ]) ?? null, // NO convertir a lowercase
                        'allergies' => $getFieldValue([
                            'alergias',
                            'alergia'
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
                    'Nombre del apoderado',
                    'nombre apoderado',
                    'apoderado',
                    'guardian'
                ])) {

                    // Obtener datos del apoderado
                    $guardianName = $getFieldValue([
                        'Nombre del apoderado',
                        'nombre apoderado',
                        'apoderado',
                        'guardian'
                    ]);
                    $guardianEmail = $getFieldValue([
                        'correo electronico del apoderado',
                        'correo apoderado',
                        'email apoderado',
                        'email del apoderado'
                    ]);

                    // Verificar si ya existe un contacto de emergencia para este participante
                    $existingEmergencyContact = EmergencyContact::where('participant_id', $participant->id)->first();

                    if ($existingEmergencyContact) {
                        // UPDATE: Actualizar contacto de emergencia existente

                        // Obtener tipo de documento del apoderado (por defecto RUT)
                        $guardianDocumentTypeId = $this->getDocumentTypeId('RUT');

                        // Obtener y limpiar RUT del apoderado
                        $guardianRut = $getFieldValue([
                            'RUT del apoderado',
                            'rut apoderado',
                            'documento apoderado'
                        ]);
                        $cleanGuardianRut = $this->cleanRut($guardianRut ?? '');

                        $existingEmergencyContact->update([
                            'name' => $this->toCapitalCase($guardianName) ?? $existingEmergencyContact->name,
                            'email' => $this->toLowercase($guardianEmail) ?? $existingEmergencyContact->email,
                            'document_type' => $guardianDocumentTypeId,
                            'document_number' => $cleanGuardianRut ?: $existingEmergencyContact->document_number,
                            'phone' => $getFieldValue([
                                'Teléfono contacto emergencia',
                                'telefono contacto emergencia',
                                'fono contacto emergencia'
                            ]) ?? $existingEmergencyContact->phone,
                            'birth_date' => $this->parseBirthDate($getFieldValue([
                                'Fecha nacimiento contacto emergencia',
                                'fecha nacimiento contacto emergencia'
                            ])) ?? $existingEmergencyContact->birth_date,
                            'relationship' => $getFieldValue([
                                'Relación contacto emergencia',
                                'relacion contacto emergencia'
                            ]) ?? $existingEmergencyContact->relationship,
                        ]);
                    } else {
                        // CREATE: Crear nuevo contacto de emergencia

                        // Obtener tipo de documento del apoderado (por defecto RUT)
                        $guardianDocumentTypeId = $this->getDocumentTypeId('RUT');

                        // Obtener y limpiar RUT del apoderado
                        $guardianRut = $getFieldValue([
                            'RUT del apoderado',
                            'rut apoderado',
                            'documento apoderado'
                        ]);
                        $cleanGuardianRut = $this->cleanRut($guardianRut ?? '');

                        $emergencyContact = EmergencyContact::create([
                            'name' => $this->toCapitalCase($guardianName) ?? null,
                            'email' => $this->toLowercase($guardianEmail) ?? null,
                            'document_type' => $guardianDocumentTypeId,
                            'document_number' => $cleanGuardianRut ?: null,
                            'code_phone' => '+56', // Código por defecto para Chile
                            'phone' => $getFieldValue([
                                'Teléfono contacto emergencia',
                                'telefono contacto emergencia',
                                'fono contacto emergencia'
                            ]) ?? null,
                            'country' => 'CL', // Chile por defecto
                            'birth_date' => $this->parseBirthDate($getFieldValue([
                                'Fecha nacimiento contacto emergencia',
                                'fecha nacimiento contacto emergencia'
                            ])) ?? null,
                            'address' => null,
                            'relationship' => $getFieldValue([
                                'Relación contacto emergencia',
                                'relacion contacto emergencia'
                            ]) ?? 'Familiar',
                            'participant_id' => $participant->id,
                        ]);
                    }
                }

                    $participantCount++;
                } catch (\Exception $e) {
                    // Capturar error de esta fila específica
                    $participantName = 'Desconocido';

                    // Intentar obtener el nombre del participante para el reporte de error
                    try {
                        $firstName = $this->toCapitalCase($getFieldValue([
                            'Primer Nombre',
                            'primer nombre',
                            'nombre',
                            'Nombre'
                        ])) ?? '';
                        $lastName = $this->toCapitalCase($getFieldValue([
                            'Primer apellido',
                            'primer apellido',
                            'apellido paterno',
                            'apellido'
                        ])) ?? '';

                        if ($firstName || $lastName) {
                            $participantName = trim("{$firstName} {$lastName}");
                        }

                        if (isset($cleanRut) && !empty($cleanRut)) {
                            $participantName .= " (RUT: {$cleanRut})";
                        }
                    } catch (\Exception $nameError) {
                        // Si falla obtener el nombre, usar "Desconocido"
                    }

                    $errors[] = [
                        'row' => $displayRowNumber,
                        'participant_name' => $participantName,
                        'error' => $e->getMessage(),
                    ];

                    Log::warning('Error procesando fila de participante', [
                        'row' => $displayRowNumber,
                        'participant_name' => $participantName,
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                    ]);
                }
            }



            // Verificar si hubo errores durante el procesamiento
            if (!empty($errors)) {
                // Lanzar excepción con todos los errores acumulados
                throw new \App\Exceptions\ParticipantImportException(
                    $errors,
                    $totalRows,
                    $participantCount
                );
            }

            // Actualizar el curso con el número total de estudiantes
            $course->update(['total_students' => $participantCount]);

            Log::info('Participantes procesados exitosamente', [
                'course_id' => $course->id,
                'total_students' => $participantCount,
                'participants_created' => $createdCount,
                'participants_updated' => $updatedCount,
                'total_rows' => $totalRows,
            ]);
        } catch (\App\Exceptions\ParticipantImportException $e) {
            // Re-lanzar la excepción de importación para que sea manejada por el controlador
            throw $e;
        } catch (\PhpOffice\PhpSpreadsheet\Reader\Exception $e) {
            Log::error('Error de formato de archivo Excel', [
                'course_id' => $course->id,
                'error' => $e->getMessage(),
            ]);
            throw new \Exception('El archivo no tiene un formato válido de Excel. Por favor, asegúrate de subir un archivo .xlsx, .xls o .csv válido.');
        } catch (\Illuminate\Database\QueryException $e) {
            Log::error('Error de base de datos al procesar estudiantes', [
                'course_id' => $course->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw new \Exception('Error al guardar los datos de los participantes. Verifica que el formato del archivo sea correcto y que los datos no estén duplicados.');
        } catch (\Exception $e) {
            Log::error('Error al procesar el archivo de estudiantes', [
                'course_id' => $course->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Detectar errores comunes y dar mensajes más claros
            $message = $e->getMessage();
            if (str_contains($message, 'Undefined array key') || str_contains($message, 'array_combine')) {
                throw new \Exception('El formato del archivo no es compatible. Asegúrate de que las columnas tengan los nombres correctos (RUT, Nombre, Apellido, etc.).');
            }
            if (str_contains($message, 'Cannot read file') || str_contains($message, 'Could not find')) {
                throw new \Exception('No se pudo leer el archivo. El archivo puede estar corrupto o no ser un Excel válido.');
            }

            throw new \Exception('Ocurrió un error inesperado al procesar el archivo de estudiantes. Verifica el formato del archivo e intenta nuevamente.');
        }
    }

    /**
     * Extraer información de la cabecera del Excel (opcional)
     * Lee información como institución, programa, fecha, valor, etc.
     *
     * @param array $rows Todas las filas del Excel
     * @param int $headerRowIndex Índice de la fila de encabezados
     * @return array Información extraída de la cabecera
     */
    private function extractHeaderInfo(array $rows, int $headerRowIndex): array
    {
        $info = [
            'institution' => null,
            'program' => null,
            'program_code' => null,
            'date' => null,
            'price' => null,
        ];

        // Procesar solo las filas antes de los encabezados
        for ($i = 0; $i < $headerRowIndex; $i++) {
            $row = $rows[$i];

            foreach ($row as $cell) {
                if (empty($cell)) continue;

                $cellStr = trim($cell);
                $cellLower = strtolower($cellStr);

                // Detectar institución (línea que contiene nombre largo)
                if (strlen($cellStr) > 20 && !str_contains($cellLower, 'fecha') && !str_contains($cellLower, 'valor')) {
                    $info['institution'] = $cellStr;
                }

                // Detectar código de programa (Programa N°: XXXXX)
                if (preg_match('/programa\s*n°?\s*:?\s*([A-Z0-9]+)/i', $cellStr, $matches)) {
                    $info['program_code'] = $matches[1];
                }

                // Detectar fecha (Fecha: DD de MMMM, YYYY)
                if (preg_match('/fecha\s*:?\s*(.+)/i', $cellStr, $matches)) {
                    $info['date'] = trim($matches[1]);
                }

                // Detectar valor/precio (Valor: $XXX,XXX)
                if (preg_match('/valor\s*:?\s*\$?\s*([\d,\.]+)/i', $cellStr, $matches)) {
                    $priceStr = str_replace([',', '.'], '', $matches[1]);
                    $info['price'] = (int) $priceStr;
                }
            }
        }

        return $info;
    }

    /**
     * Buscar la fila de encabezados en el archivo Excel
     * Detecta automáticamente dónde comienza la tabla de datos
     *
     * @param array $rows Todas las filas del Excel
     * @return int|null Índice de la fila de encabezados, o null si no se encuentra
     */
    private function findHeaderRow(array $rows): ?int
    {
        // Palabras clave que indican encabezados de participantes
        $headerKeywords = [
            'rut',
            'documento',
            'apellido',
            'nombre',
            'email',
            'correo',
            'telefono',
            'fono',
            'celular',
            'n°',
            'numero',
        ];

        foreach ($rows as $index => $row) {
            // Contar cuántas columnas contienen palabras clave
            $matchCount = 0;

            foreach ($row as $cell) {
                if (empty($cell)) continue;

                $cellLower = strtolower(trim($cell));

                foreach ($headerKeywords as $keyword) {
                    if (str_contains($cellLower, $keyword)) {
                        $matchCount++;
                        break; // Ya encontramos una coincidencia en esta celda
                    }
                }
            }

            // Si encontramos al menos 3 columnas con palabras clave, probablemente sea la fila de encabezados
            if ($matchCount >= 3) {
                return $index;
            }
        }

        return null; // No se encontró fila de encabezados
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
     * Convertir texto a Capital Case (primera letra de cada palabra en mayúscula)
     */
    private function toCapitalCase(?string $text): ?string
    {
        if (empty($text)) return $text;
        return mb_convert_case(trim($text), MB_CASE_TITLE, 'UTF-8');
    }

    /**
     * Normalizar género
     */
    private function normalizeGender(?string $gender): ?string
    {
        if (empty($gender)) return null; // Retornar null si no hay valor

        $gender = strtolower(trim($gender));

        if (in_array($gender, ['m', 'masculino', 'male', 'hombre'])) {
            return 'Masculino';
        }

        if (in_array($gender, ['f', 'femenino', 'female', 'mujer'])) {
            return 'Femenino';
        }

        return null; // Retornar null si no es reconocido
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

    /**
     * Actualizar precios individuales de participantes cuando cambia el precio del programa
     */
    private function updateParticipantPrices(ProgramCourse $programCourse, float $newPrice): void
    {
        try {
            // Actualizar todos los participantes en participant_program que tienen este program_course
            $updatedCount = DB::table('participant_program')
                ->where('program_id', $programCourse->id)
                ->update([
                    'individual_price' => $newPrice,
                    'updated_at' => now(),
                ]);

            Log::info('Precios de participantes actualizados', [
                'program_course_id' => $programCourse->id,
                'new_price' => $newPrice,
                'updated_count' => $updatedCount,
            ]);
        } catch (\Exception $e) {
            Log::error('Error al actualizar precios de participantes', [
                'program_course_id' => $programCourse->id,
                'new_price' => $newPrice,
                'error' => $e->getMessage(),
            ]);
            // No lanzar excepción para no interrumpir la actualización del curso
        }
    }

    /**
     * Verificar si el año de la fecha de salida cambió
     */
    private function hasDepartureDateYearChanged(ProgramCourse $programCourse, array $data): bool
    {
        if (!isset($data['departure_date'])) {
            return false;
        }

        $currentDepartureDate = $programCourse->departure_date;
        if (!$currentDepartureDate) {
            return false;
        }

        $newDepartureDate = new \DateTime($data['departure_date']);
        $currentYear = $currentDepartureDate->year;
        $newYear = (int) $newDepartureDate->format('Y');

        return $currentYear !== $newYear;
    }

    /**
     * Verificar si hay pagos aprobados o suscripciones activas para el programa
     */
    private function hasPaymentsOrSubscriptions(ProgramCourse $programCourse): bool
    {
        // Verificar pagos aprobados
        $hasApprovedPayments = \App\Models\Payment::whereHas('order', function ($query) use ($programCourse) {
            $query->where('program_id', $programCourse->id);
        })->whereIn('status', ['approved', 'completed'])->exists();

        if ($hasApprovedPayments) {
            return true;
        }

        // Verificar suscripciones activas en VirtualPos
        if ($programCourse->virtualpos_plan_id) {
            try {
                $hasActiveSubscriptions = $this->virtualPosPlanService->hasActiveSubscriptions(
                    $programCourse->virtualpos_plan_id
                );
                if ($hasActiveSubscriptions) {
                    return true;
                }
            } catch (\Exception $e) {
                Log::warning('Error verificando suscripciones activas', [
                    'program_course_id' => $programCourse->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return false;
    }

    /**
     * Procesar y guardar archivos PDF del programa
     */
    private function processProgramFiles(ProgramCourse $programCourse, array $data): void
    {
        try {
            $courseCode = $programCourse->code ?? $programCourse->id;

            Log::info('Procesando archivos PDF del programa', [
                'program_course_id' => $programCourse->id,
                'course_code' => $courseCode,
                'has_itinerary_file' => isset($data['itinerary_file']),
                'itinerary_is_uploadedfile' => isset($data['itinerary_file']) && $data['itinerary_file'] instanceof UploadedFile,
                'has_coverage_file' => isset($data['coverage_file']),
                'coverage_is_uploadedfile' => isset($data['coverage_file']) && $data['coverage_file'] instanceof UploadedFile,
                'has_equipment_file' => isset($data['equipment_file']),
                'equipment_is_uploadedfile' => isset($data['equipment_file']) && $data['equipment_file'] instanceof UploadedFile,
            ]);

            // Procesar itinerario
            if (isset($data['itinerary_file']) && $data['itinerary_file'] instanceof UploadedFile) {
                // Eliminar archivo anterior si existe
                if ($programCourse->itinerary_file) {
                    Storage::disk('public')->delete($programCourse->itinerary_file);
                }
                $path = $data['itinerary_file']->store("program_courses/{$courseCode}/files", 'public');
                $programCourse->itinerary_file = $path;
                Log::info('Itinerary file saved', ['path' => $path]);
            } elseif (!empty($data['remove_itinerary_file']) && $programCourse->itinerary_file) {
                Storage::disk('public')->delete($programCourse->itinerary_file);
                $programCourse->itinerary_file = null;
            }

            // Procesar cobertura de asistencia
            if (isset($data['coverage_file']) && $data['coverage_file'] instanceof UploadedFile) {
                if ($programCourse->travel_assistance_coverage) {
                    Storage::disk('public')->delete($programCourse->travel_assistance_coverage);
                }
                $path = $data['coverage_file']->store("program_courses/{$courseCode}/files", 'public');
                $programCourse->travel_assistance_coverage = $path;
            } elseif (!empty($data['remove_coverage_file']) && $programCourse->travel_assistance_coverage) {
                Storage::disk('public')->delete($programCourse->travel_assistance_coverage);
                $programCourse->travel_assistance_coverage = null;
            }

            // Procesar lista de equipo
            if (isset($data['equipment_file']) && $data['equipment_file'] instanceof UploadedFile) {
                if ($programCourse->equipment_list) {
                    Storage::disk('public')->delete($programCourse->equipment_list);
                }
                $path = $data['equipment_file']->store("program_courses/{$courseCode}/files", 'public');
                $programCourse->equipment_list = $path;
            } elseif (!empty($data['remove_equipment_file']) && $programCourse->equipment_list) {
                Storage::disk('public')->delete($programCourse->equipment_list);
                $programCourse->equipment_list = null;
            }

            $programCourse->save();

            Log::info('Archivos de programa procesados', [
                'program_course_id' => $programCourse->id,
                'itinerary_file' => $programCourse->itinerary_file,
                'travel_assistance_coverage' => $programCourse->travel_assistance_coverage,
                'equipment_list' => $programCourse->equipment_list,
            ]);
        } catch (\Exception $e) {
            Log::error('Error procesando archivos de programa', [
                'program_course_id' => $programCourse->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Sincronizar opciones de pago para el curso
     */
    private function syncPaymentOptions(ProgramCourse $programCourse, array $data): void
    {
        try {
            // Obtener todas las opciones de pago seleccionadas
            $selectedPaymentOptions = [];

            // Opciones de pago total
            if (!empty($data['full_payment_options']) && is_array($data['full_payment_options'])) {
                $selectedPaymentOptions = array_merge($selectedPaymentOptions, $data['full_payment_options']);
            }

            // Opciones de suscripción
            if (!empty($data['subscription_payment_options']) && is_array($data['subscription_payment_options'])) {
                $selectedPaymentOptions = array_merge($selectedPaymentOptions, $data['subscription_payment_options']);
            }

            Log::info('Sincronizando opciones de pago para ProgramCourse', [
                'program_course_id' => $programCourse->id,
                'selected_options' => $selectedPaymentOptions,
            ]);

            if (empty($selectedPaymentOptions)) {
                // Si no hay opciones seleccionadas, remover todas las existentes
                $programCourse->paymentOptions()->detach();
                Log::info('No hay opciones de pago seleccionadas, se eliminaron todas las existentes', [
                    'program_course_id' => $programCourse->id,
                ]);
                return;
            }

            // Obtener IDs de opciones de pago desde la base de datos
            $paymentOptionIds = \App\Models\PaymentOption::whereIn('code', $selectedPaymentOptions)->pluck('id')->toArray();

            if (empty($paymentOptionIds)) {
                Log::warning('No se encontraron opciones de pago válidas en la base de datos', [
                    'program_course_id' => $programCourse->id,
                    'selected_codes' => $selectedPaymentOptions,
                ]);
                return;
            }

            // Sincronizar con la tabla pivot (esto añade nuevas y elimina las que no están)
            $programCourse->paymentOptions()->sync($paymentOptionIds);

            Log::info('Opciones de pago sincronizadas exitosamente', [
                'program_course_id' => $programCourse->id,
                'synced_ids' => $paymentOptionIds,
                'synced_count' => count($paymentOptionIds),
            ]);
        } catch (\Exception $e) {
            Log::error('Error al sincronizar opciones de pago', [
                'program_course_id' => $programCourse->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            // No lanzar excepción para no interrumpir la creación/actualización del curso
        }
    }

    /**
     * Get participants for a specific program-course with payment status
     */
    public function getParticipantsWithPaymentStatus(int $programCourseId): array
    {
        Log::info('getParticipantsWithPaymentStatus called', [
            'programCourseId' => $programCourseId
        ]);

        $participantPrograms = \App\Models\ParticipantProgram::where('program_id', $programCourseId)
            ->with(['participant'])
            ->get();

        Log::info('ParticipantPrograms found', [
            'count' => $participantPrograms->count(),
            'programCourseId' => $programCourseId
        ]);

        $participantsData = [];

        foreach ($participantPrograms as $pp) {
            $participant = $pp->participant;

            // Get total paid amount (only completed/approved payments with amount > 0)
            $totalPaid = \App\Models\Payment::whereHas('order', function ($q) use ($participant, $programCourseId) {
                $q->where('participant_id', $participant->id)
                  ->where('program_id', $programCourseId);
            })
            ->whereIn('status', ['completed', 'approved'])
            ->where('amount', '>', 0)
            ->sum('amount');

            // has_payments is true only if there are actual confirmed payments with amount > 0
            $hasPayments = $totalPaid > 0;

            $participantsData[] = [
                'participant_program_id' => $pp->id,
                'participant_id' => $participant->id,
                'full_name' => $participant->full_name,
                'rut' => $participant->document_number,
                'enrollment_code' => $pp->enrollment_code,
                'has_payments' => $hasPayments,
                'total_paid' => $totalPaid,
                'can_delete' => !$hasPayments,
            ];
        }

        return $participantsData;
    }

    /**
     * Remove a participant from a program-course
     * Only if they have no payments
     */
    public function removeParticipant(int $participantProgramId): array
    {
        try {
            $participantProgram = \App\Models\ParticipantProgram::findOrFail($participantProgramId);
            $participant = $participantProgram->participant;

            // Check if participant has payments
            $hasPayments = \App\Models\Payment::whereHas('order', function ($q) use ($participant, $participantProgram) {
                $q->where('participant_id', $participant->id)
                  ->where('program_id', $participantProgram->program_id);
            })->exists();

            if ($hasPayments) {
                return [
                    'success' => false,
                    'message' => 'No se puede eliminar el participante porque tiene transacciones registradas.'
                ];
            }

            // Get the ProgramCourse to find the Course
            $programCourse = ProgramCourse::find($participantProgram->program_id);
            $courseId = $programCourse?->course_id;

            // Delete associated orders (if any without payments)
            \App\Models\Order::where('participant_id', $participant->id)
                ->where('program_id', $participantProgram->program_id)
                ->delete();

            // Delete installment plans (if any without payments)
            \App\Models\InstallmentPlan::where('participant_id', $participant->id)
                ->where('program_id', $participantProgram->program_id)
                ->delete();

            // Delete the participant_program relationship
            $participantProgram->delete();

            // Also delete from participant_course pivot table so the count updates
            if ($courseId) {
                DB::table('participant_course')
                    ->where('participant_id', $participant->id)
                    ->where('course_id', $courseId)
                    ->delete();

                Log::info('Participante eliminado de participant_course', [
                    'participant_id' => $participant->id,
                    'course_id' => $courseId,
                ]);
            }

            // Verificar si el participante quedó huérfano (sin ningún programa asociado)
            $hasOtherPrograms = \App\Models\ParticipantProgram::where('participant_id', $participant->id)->exists();

            $participantDeleted = false;
            if (!$hasOtherPrograms) {
                // El participante no tiene más programas, eliminarlo completamente
                $participantName = $participant->full_name;
                $participantId = $participant->id;
                $participant->delete();
                $participantDeleted = true;

                Log::info('Participante huérfano eliminado completamente', [
                    'participant_id' => $participantId,
                    'participant_name' => $participantName,
                ]);
            }

            Log::info('Participante eliminado del programa-curso', [
                'participant_program_id' => $participantProgramId,
                'participant' => $participant->full_name ?? 'N/A',
                'program_id' => $participantProgram->program_id,
                'course_id' => $courseId,
                'participant_fully_deleted' => $participantDeleted,
            ]);

            return [
                'success' => true,
                'message' => $participantDeleted
                    ? 'Participante eliminado completamente del sistema.'
                    : 'Participante eliminado del programa (aún tiene otros programas asociados).'
            ];

        } catch (\Exception $e) {
            Log::error('Error al eliminar participante', [
                'participant_program_id' => $participantProgramId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'message' => 'Error al eliminar participante: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Verificar si un programa puede ser eliminado
     * Valida que NO existan: pagos, descuentos aplicados, o suscripciones activas
     *
     * @param int $programCourseId
     * @return array ['can_delete' => bool, 'reasons' => array]
     */
    public function canDeleteProgram(int $programCourseId): array
    {
        $reasons = [];

        $programCourse = ProgramCourse::find($programCourseId);

        if (!$programCourse) {
            return [
                'can_delete' => false,
                'reasons' => ['El programa no existe.']
            ];
        }

        // 1. Verificar pagos completados o aprobados
        $approvedPaymentsCount = \App\Models\Payment::whereHas('order', function ($q) use ($programCourseId) {
            $q->where('program_id', $programCourseId);
        })
        ->whereIn('status', ['completed', 'approved'])
        ->count();

        if ($approvedPaymentsCount > 0) {
            $reasons[] = "Existen {$approvedPaymentsCount} pago(s) confirmado(s) asociado(s) al programa.";
        }

        // 2. Verificar pagos pendientes o en proceso (cualquier pago registrado)
        $pendingPaymentsCount = \App\Models\Payment::whereHas('order', function ($q) use ($programCourseId) {
            $q->where('program_id', $programCourseId);
        })
        ->whereNotIn('status', ['completed', 'approved', 'failed', 'rejected', 'cancelled'])
        ->count();

        if ($pendingPaymentsCount > 0) {
            $reasons[] = "Existen {$pendingPaymentsCount} pago(s) pendiente(s) o en proceso.";
        }

        // 3. Verificar suscripciones activas en VirtualPos
        if ($programCourse->virtualpos_plan_id) {
            try {
                $hasActiveSubscriptions = $this->virtualPosPlanService->hasActiveSubscriptions(
                    $programCourse->virtualpos_plan_id
                );

                if ($hasActiveSubscriptions) {
                    $reasons[] = "Existen suscripciones activas en VirtualPos para este programa.";
                }
            } catch (\Exception $e) {
                Log::warning('Error verificando suscripciones en VirtualPos', [
                    'program_course_id' => $programCourseId,
                    'error' => $e->getMessage(),
                ]);
                // Si hay error consultando VirtualPos, ser conservador y no permitir eliminar
                $reasons[] = "No se pudo verificar el estado de suscripciones en VirtualPos.";
            }
        }

        // 4. Verificar órdenes con cuotas pagadas (orders_detail.is_paid = true)
        $paidInstallmentsCount = \App\Models\OrderDetail::whereHas('order', function ($q) use ($programCourseId) {
            $q->where('program_id', $programCourseId);
        })
        ->where('is_paid', true)
        ->count();

        if ($paidInstallmentsCount > 0) {
            $reasons[] = "Existen {$paidInstallmentsCount} cuota(s) marcada(s) como pagada(s).";
        }

        // 5. Verificar planes de cuotas con pagos realizados (installments.is_paid = true)
        $paidInstallmentPlansCount = DB::table('installments')
            ->join('installment_plans', 'installments.installment_plan_id', '=', 'installment_plans.id')
            ->where('installment_plans.program_id', $programCourseId)
            ->where('installments.is_paid', true)
            ->count();

        if ($paidInstallmentPlansCount > 0) {
            $reasons[] = "Existen {$paidInstallmentPlansCount} cuota(s) de suscripción pagada(s).";
        }

        return [
            'can_delete' => empty($reasons),
            'reasons' => $reasons,
            'program_name' => $programCourse->name,
            'program_code' => $programCourse->code,
        ];
    }

    /**
     * Eliminar un programa completamente con todas sus relaciones
     * SOLO si pasa todas las validaciones de canDeleteProgram
     *
     * @param int $programCourseId
     * @return array ['success' => bool, 'message' => string]
     */
    public function deleteProgram(int $programCourseId): array
    {
        // Primero validar si se puede eliminar
        $canDelete = $this->canDeleteProgram($programCourseId);

        if (!$canDelete['can_delete']) {
            return [
                'success' => false,
                'message' => 'No se puede eliminar el programa.',
                'reasons' => $canDelete['reasons']
            ];
        }

        return DB::transaction(function () use ($programCourseId) {
            $programCourse = ProgramCourse::findOrFail($programCourseId);
            $courseId = $programCourse->course_id;
            $programName = $programCourse->name;
            $programCode = $programCourse->code;

            $deletedCounts = [
                'participant_program_discounts' => 0,
                'installments' => 0,
                'installment_plans' => 0,
                'order_details' => 0,
                'payments' => 0,
                'orders' => 0,
                'participant_programs' => 0,
                'virtualpos_plans' => 0,
                'payment_options' => 0,
                'participant_course' => 0,
                'course' => 0,
            ];

            // Obtener IDs de participant_programs para este programa
            $participantProgramIds = \App\Models\ParticipantProgram::where('program_id', $programCourseId)
                ->pluck('id')
                ->toArray();

            // Obtener IDs de participantes para limpiar participant_course
            $participantIds = \App\Models\ParticipantProgram::where('program_id', $programCourseId)
                ->pluck('participant_id')
                ->toArray();

            // Obtener IDs de órdenes para este programa
            $orderIds = \App\Models\Order::where('program_id', $programCourseId)
                ->pluck('id')
                ->toArray();

            // Obtener IDs de installment_plans
            $installmentPlanIds = \App\Models\InstallmentPlan::where('program_id', $programCourseId)
                ->pluck('id')
                ->toArray();

            // 1. Eliminar descuentos de participantes
            if (!empty($participantProgramIds)) {
                $deletedCounts['participant_program_discounts'] = \App\Models\ParticipantProgramDiscount::whereIn('participant_program_id', $participantProgramIds)
                    ->delete();
            }

            // 2. Eliminar installments (cuotas de planes de suscripción)
            if (!empty($installmentPlanIds)) {
                $deletedCounts['installments'] = DB::table('installments')
                    ->whereIn('installment_plan_id', $installmentPlanIds)
                    ->delete();
            }

            // 3. Eliminar installment_plans
            $deletedCounts['installment_plans'] = \App\Models\InstallmentPlan::where('program_id', $programCourseId)
                ->delete();

            // 4. Eliminar order_details
            if (!empty($orderIds)) {
                $deletedCounts['order_details'] = \App\Models\OrderDetail::whereIn('order_id', $orderIds)
                    ->delete();
            }

            // 5. Eliminar payments (solo fallidos/cancelados ya que validamos antes)
            if (!empty($orderIds)) {
                $deletedCounts['payments'] = \App\Models\Payment::whereIn('order_id', $orderIds)
                    ->delete();
            }

            // 6. Eliminar orders
            $deletedCounts['orders'] = \App\Models\Order::where('program_id', $programCourseId)
                ->delete();

            // 7. Eliminar participant_programs
            $deletedCounts['participant_programs'] = \App\Models\ParticipantProgram::where('program_id', $programCourseId)
                ->delete();

            // 8. Eliminar virtualpos_plans locales (si existen)
            $deletedCounts['virtualpos_plans'] = VirtualPosPlan::where('program_course_id', $programCourseId)
                ->delete();

            // 9. Eliminar relación pivot program_course_payment_option
            $deletedCounts['payment_options'] = DB::table('program_course_payment_option')
                ->where('program_course_id', $programCourseId)
                ->delete();

            // 10. Eliminar de participant_course (pivot) para sincronizar conteo
            if (!empty($participantIds) && $courseId) {
                $deletedCounts['participant_course'] = DB::table('participant_course')
                    ->whereIn('participant_id', $participantIds)
                    ->where('course_id', $courseId)
                    ->delete();
            }

            // 11. Eliminar archivos PDF asociados al programa
            $this->deleteProgramFiles($programCourse);

            // 12. Eliminar el ProgramCourse
            $programCourse->delete();

            // 13. Eliminar el Course asociado y su archivo de estudiantes
            $course = Course::find($courseId);
            if ($course) {
                // Eliminar archivo de estudiantes si existe
                if ($course->students_file_path) {
                    try {
                        Storage::disk('public')->delete($course->students_file_path);
                        Log::info("Archivo de estudiantes eliminado: {$course->students_file_path}");
                    } catch (\Exception $e) {
                        Log::warning("Error eliminando archivo de estudiantes", [
                            'path' => $course->students_file_path,
                            'error' => $e->getMessage(),
                        ]);
                    }
                }

                $deletedCounts['course'] = 1;
                $course->delete();
            }

            Log::info('Programa y curso eliminados completamente', [
                'program_course_id' => $programCourseId,
                'program_name' => $programName,
                'program_code' => $programCode,
                'course_id' => $courseId,
                'deleted_counts' => $deletedCounts,
                'deleted_by' => auth()->id(),
            ]);

            return [
                'success' => true,
                'message' => "Programa '{$programName}' eliminado exitosamente.",
                'deleted_counts' => $deletedCounts
            ];
        });
    }

    /**
     * Eliminar archivos PDF asociados al programa
     */
    private function deleteProgramFiles(ProgramCourse $programCourse): void
    {
        $fileFields = [
            'terms_file_path',
            'itinerary_file_path',
            'contract_file_path',
        ];

        foreach ($fileFields as $field) {
            if (!empty($programCourse->$field)) {
                try {
                    Storage::disk('public')->delete($programCourse->$field);
                    Log::info("Archivo eliminado: {$programCourse->$field}");
                } catch (\Exception $e) {
                    Log::warning("Error eliminando archivo {$field}", [
                        'path' => $programCourse->$field,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }
    }
}
