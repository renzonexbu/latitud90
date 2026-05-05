<?php

namespace App\Services\Admin\Participants;

use App\Models\Participant;
use App\Models\ParticipantProgramDiscount;
use App\Models\ProgramCourse;
use App\Models\Course;
use App\Models\Program;
use App\Traits\AdminLogging;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Exception;
use App\Services\Admin\Installments\InstallmentRecalculationService;
use App\Services\Subscription\ParticipantPlanService;
use App\Helpers\ParticipantPriceHelper;

class UpdateParticipantService
{
    use AdminLogging;
    /**
     * Actualiza los datos personales de un participante
     *
     * @param array $data
     * @param Participant $participant
     * @param bool $canEditDocument Si el usuario puede editar el documento (super admin)
     * @return Participant
     */
    public function execute(array $data, Participant $participant, bool $canEditDocument = false): Participant
    {
        try {
            DB::beginTransaction();

            // Manejar cambio de RUT/documento
            $oldDocumentNumber = $participant->document_number;
            $newDocumentNumber = null;

            if (isset($data['document_number'])) {
                // Limpiar el documento (quitar puntos, guiones, espacios)
                $cleanedDocument = \App\Helpers\RutHelper::clean($data['document_number']);

                if ($cleanedDocument !== $oldDocumentNumber) {
                    // Solo super admins pueden cambiar el documento
                    if (!$canEditDocument) {
                        throw new Exception('No tienes permisos para modificar el RUT/documento');
                    }
                    $newDocumentNumber = $cleanedDocument;

                    Log::info('UpdateParticipantService: Cambio de documento autorizado', [
                        'participant_id' => $participant->id,
                        'old_document' => $oldDocumentNumber,
                        'new_document' => $newDocumentNumber,
                    ]);
                }
            }

            // Normalizar nombres a Capital Case
            foreach (['first_name', 'second_name', 'first_last_name', 'second_last_name'] as $nameField) {
                if (!empty($data[$nameField])) {
                    $data[$nameField] = $this->toCapitalCase($data[$nameField]);
                }
            }

            // Preparar los datos para actualización
            $updateData = [
                'first_last_name' => $data['first_last_name'] ?? $participant->first_last_name,
                'second_last_name' => $data['second_last_name'] ?? $participant->second_last_name,
                'first_name' => $data['first_name'] ?? $participant->first_name,
                'second_name' => $data['second_name'] ?? $participant->second_name,
                'email' => $data['email'] ?? $participant->email,
                'code_phone' => $data['code_phone'] ?? $participant->code_phone,
                'phone' => $data['phone'] ?? $participant->phone,
                'country' => $data['country'] ?? $participant->country,
                'birth_date' => $data['birth_date'] ?? $participant->birth_date,
                'allergies' => $data['allergies'] ?? $participant->allergies,
                'intolerances' => $data['intolerances'] ?? $participant->intolerances,
                'dietary_restrictions' => $data['dietary_restrictions'] ?? $participant->dietary_restrictions,
            ];

            // Si el documento cambió, agregarlo a los datos de actualización
            if ($newDocumentNumber) {
                $updateData['document_number'] = $newDocumentNumber;
            }

            // Guardar valores anteriores para el log
            $oldValues = $participant->toArray();

            // Actualizar el participante
            $participant->update($updateData);

            // Si el documento cambió, actualizar todos los enrollment_codes relacionados
            if ($newDocumentNumber) {
                $this->updateEnrollmentCodes($participant, $oldDocumentNumber, $newDocumentNumber);
            }

            // Si se especificó un curso/programa, actualizar el pivot del curso con el precio individual
            if (!empty($data['pivot_course_id'])) {
                // El pivot_course_id puede ser un ProgramCourse.id (desde el nuevo frontend) o un Course.id (legacy)
                // Primero intentamos buscar como ProgramCourse, luego como Course
                $programCourse = ProgramCourse::find((int) $data['pivot_course_id']);
                $course = null;

                if ($programCourse) {
                    // Encontrado como ProgramCourse, obtener el Course asociado
                    $course = $programCourse->course;
                } else {
                    // Fallback: buscar como Course.id (compatibilidad legacy)
                    $course = Course::find((int) $data['pivot_course_id']);
                    if ($course) {
                        $programCourse = $course->programCourses()->first();
                    }
                }

                if ($course) {

                    // VALIDACIÓN: Verificar si hay suscripción activa antes de permitir cambios de precio/descuentos
                    if ($programCourse && (array_key_exists('individual_price', $data) || array_key_exists('price_adjustments', $data) || !empty($data['discounts']))) {
                        $activeSubscription = \App\Models\ProgramSubscription::where('participant_id', $participant->id)
                            ->where('program_id', $programCourse->id)
                            ->where('status', 'ACTIVA')
                            ->first();

                        if ($activeSubscription) {
                            throw new Exception('No se pueden aplicar descuentos ni ajustes de precio porque este participante tiene una suscripción activa para este programa.');
                        }
                    }

                    // Actualizar el pivot del curso con el precio individual (usar $course->id, no pivot_course_id que puede ser ProgramCourse.id)
                    if (array_key_exists('individual_price', $data)) {
                        $participant->courses()->updateExistingPivot($course->id, [
                            'individual_price' => $data['individual_price']
                        ]);
                    }

                    // Actualizar ajustes de precio si se proporcionan
                    if (array_key_exists('price_adjustments', $data)) {
                        $participant->courses()->updateExistingPivot($course->id, [
                            'price_adjustments' => $data['price_adjustments'],
                            'adjustment_reason' => $data['adjustment_reason'] ?? null
                        ]);
                    }

                    // Procesar descuentos si se proporcionan
                    if (!empty($data['discounts']) && $programCourse) {
                        // Buscar el participant_program usando program_course_id (almacenado en program_id)
                        $participantProgram = DB::table('participant_program')
                            ->where('participant_id', $participant->id)
                            ->where('program_id', $programCourse->id)
                            ->first();

                        // Si no existe, crearlo
                        if (!$participantProgram) {
                            $participantProgramId = DB::table('participant_program')->insertGetId([
                                'participant_id' => $participant->id,
                                'program_id' => $programCourse->id,
                                'status' => 'pending_payment',
                                'created_at' => now(),
                                'updated_at' => now(),
                            ]);

                            Log::info('UpdateParticipantService: Creado participant_program', [
                                'participant_id' => $participant->id,
                                'program_course_id' => $programCourse->id,
                                'participant_program_id' => $participantProgramId
                            ]);

                            $participantProgram = (object) ['id' => $participantProgramId];
                        }

                        Log::info('UpdateParticipantService: Procesando descuentos', [
                            'participant_id' => $participant->id,
                            'program_course_id' => $programCourse->id,
                            'participant_program_id' => $participantProgram->id
                        ]);
                        $this->processDiscounts($participantProgram->id, $data['discounts']);

                        // Crear plan personalizado en VirtualPos si tiene descuentos y suscripción habilitada
                        if ($programCourse->enable_subscription_payment) {
                            $this->createPersonalizedPlanIfNeeded($participant, $programCourse, $data['discounts']);
                        }
                    }
                }
            }

            DB::commit();

            // Log the participant update
            $this->logUpdate(
                'participants',
                'Participant',
                $participant->id,
                "Participante actualizado: {$participant->first_name} {$participant->first_last_name}",
                $oldValues,
                $participant->toArray(),
                [
                    'has_course_update' => !empty($data['pivot_course_id']),
                    'has_price_update' => array_key_exists('individual_price', $data),
                    'has_discounts' => isset($data['discounts']),
                ]
            );

            return $participant;

        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Procesa los descuentos para un participant_program
     * Los descuentos se guardan en la tabla participant_program_discounts
     *
     * @param int $participantProgramId
     * @param string $discountsJson
     * @return void
     */
    private function processDiscounts(int $participantProgramId, string $discountsJson): void
    {
        $discounts = json_decode($discountsJson, true);
        
        if (!is_array($discounts)) {
            return;
        }

        // Obtener descuentos existentes
        $existingDiscounts = ParticipantProgramDiscount::where('participant_program_id', $participantProgramId)
            ->get()
            ->keyBy('id');

        $processedDiscountIds = [];

        foreach ($discounts as $discountData) {
            if (isset($discountData['id']) && $discountData['id']) {
                // Actualizar descuento existente
                $discount = $existingDiscounts->get($discountData['id']);
                if ($discount) {
                    // Mapear el tipo y valor a percent/amount según corresponda
                    $percent = null;
                    $amount = null;
                    $discountType = 'discount'; // Por defecto: descuento simple (no aparece en Aporte/Beca)

                    if ($discountData['type'] === 'percent') {
                        $percent = $discountData['value'] ?? null;
                        $discountType = 'discount'; // Descuento simple, se resta del precio total
                    } elseif ($discountData['type'] === 'amount') {
                        $amount = $discountData['value'] ?? null;
                        $discountType = 'discount'; // Descuento simple, se resta del precio total
                    } elseif ($discountData['type'] === 'liberado') {
                        // Liberado por porcentaje
                        $percent = $discountData['value'] ?? 100;
                        $discountType = 'released';
                    } elseif ($discountData['type'] === 'liberado_amount') {
                        // Liberado por monto fijo (CLP)
                        $amount = $discountData['value'] ?? null;
                        $discountType = 'released';
                    }

                    $discount->update([
                        'percent' => $percent,
                        'amount' => $amount,
                        'discount_type' => $discountType,
                        'comment' => $discountData['comment'] ?? null,
                    ]);
                    $processedDiscountIds[] = $discount->id;
                }
            } else {
                // Crear nuevo descuento
                // Mapear el tipo y valor a percent/amount según corresponda
                $percent = null;
                $amount = null;
                $discountType = 'discount'; // Por defecto: descuento simple (no aparece en Aporte/Beca)

                if ($discountData['type'] === 'percent') {
                    $percent = $discountData['value'] ?? null;
                    $discountType = 'discount'; // Descuento simple, se resta del precio total
                } elseif ($discountData['type'] === 'amount') {
                    $amount = $discountData['value'] ?? null;
                    $discountType = 'discount'; // Descuento simple, se resta del precio total
                } elseif ($discountData['type'] === 'liberado') {
                    // Liberado por porcentaje
                    $percent = $discountData['value'] ?? 100;
                    $discountType = 'released';
                } elseif ($discountData['type'] === 'liberado_amount') {
                    // Liberado por monto fijo (CLP)
                    $amount = $discountData['value'] ?? null;
                    $discountType = 'released';
                }

                $newDiscount = ParticipantProgramDiscount::create([
                    'participant_program_id' => $participantProgramId,
                    'percent' => $percent,
                    'amount' => $amount,
                    'discount_type' => $discountType,
                    'comment' => $discountData['comment'] ?? null,
                    'approved_by' => auth()->id(),
                ]);
                $processedDiscountIds[] = $newDiscount->id;
            }
        }

        // Eliminar descuentos que ya no están en la lista
        $discountsToDelete = $existingDiscounts->whereNotIn('id', $processedDiscountIds);
        foreach ($discountsToDelete as $discount) {
            $discount->delete();
        }

        // AUTO-RECÁLCULO: Recalcular cuotas automáticamente después de cambios en descuentos
        Log::info('UpdateParticipatService: Iniciando recálculo automático después de cambios en descuentos', [
            'participant_program_id' => $participantProgramId,
            'discounts_processed' => count($processedDiscountIds),
            'discounts_deleted' => $discountsToDelete->count()
        ]);
        
        $this->autoRecalculateInstallments($participantProgramId);
    }

    /**
     * Recalcula automáticamente las cuotas después de cambios en descuentos
     *
     * @param int $participantProgramId
     * @return void
     */
    private function autoRecalculateInstallments(int $participantProgramId): void
    {
        try {
            Log::info('AutoRecalculationService: Iniciando búsqueda de plan de cuotas', [
                'participant_program_id' => $participantProgramId
            ]);
            
            // Obtener el participant_program para acceder a participant y program
            $participantProgram = DB::table('participant_program')
                ->where('id', $participantProgramId)
                ->first();

            if (!$participantProgram) {
                Log::info('AutoRecalculationService: No se encontró participant_program', [
                    'participant_program_id' => $participantProgramId
                ]);
                return;
            }
            
            // Buscar el plan de cuotas activo usando participant_id y program_id
            $installmentPlan = DB::table('installment_plans')
                ->where('participant_id', $participantProgram->participant_id)
                ->where('program_id', $participantProgram->program_id)
                ->where('status', 'active')
                ->first();

            if (!$installmentPlan) {
                Log::info('AutoRecalculationService: No se encontró plan de cuotas activo', [
                    'participant_program_id' => $participantProgramId,
                    'participant_id' => $participantProgram->participant_id,
                    'program_id' => $participantProgram->program_id
                ]);
                return; // No hay plan de cuotas activo
            }
            
            Log::info('AutoRecalculationService: Plan de cuotas encontrado', [
                'installment_plan_id' => $installmentPlan->id,
                'current_total_amount' => $installmentPlan->total_amount,
                'participant_id' => $participantProgram->participant_id,
                'program_id' => $participantProgram->program_id
            ]);

            // Obtener el participante y program_course (program_id ahora apunta a program_courses)
            $participant = Participant::find($participantProgram->participant_id);
            $programCourse = \App\Models\ProgramCourse::find($participantProgram->program_id);

            if (!$participant || !$programCourse) {
                Log::info('AutoRecalculationService: No se encontró participante o program_course', [
                    'participant_id' => $participantProgram->participant_id,
                    'program_id' => $participantProgram->program_id,
                    'participant_found' => $participant ? 'yes' : 'no',
                    'program_course_found' => $programCourse ? 'yes' : 'no'
                ]);
                return;
            }

            // Calcular el nuevo precio final con descuentos aplicados
            $priceData = ParticipantPriceHelper::calculateParticipantPrice($participant, $programCourse);
            $newTotalAmount = $priceData['final_price'];

            Log::info('AutoRecalculationService: Precio calculado', [
                'old_total_amount' => $installmentPlan->total_amount,
                'new_total_amount' => $newTotalAmount,
                'difference' => $installmentPlan->total_amount - $newTotalAmount,
                'price_data' => $priceData
            ]);

            // Si el precio no ha cambiado, no hacer nada
            if (abs($installmentPlan->total_amount - $newTotalAmount) < 0.01) {
                Log::info('AutoRecalculationService: No se requiere recálculo, precio sin cambios');
                return;
            }

            Log::info('AutoRecalculationService: Detectado cambio de precio, recalculando cuotas automáticamente', [
                'installment_plan_id' => $installmentPlan->id,
                'old_total_amount' => $installmentPlan->total_amount,
                'new_total_amount' => $newTotalAmount,
                'difference' => $installmentPlan->total_amount - $newTotalAmount
            ]);

            // Usar el servicio de recálculo existente
            $recalculationService = new InstallmentRecalculationService();
            $result = $recalculationService->recalculateInstallmentsAfterDiscount($installmentPlan->id);

            Log::info('AutoRecalculationService: Cuotas recalculadas exitosamente', [
                'installment_plan_id' => $installmentPlan->id,
                'result' => $result
            ]);

        } catch (\Exception $e) {
            Log::error('AutoRecalculationService: Error al recalcular cuotas automáticamente: ' . $e->getMessage(), [
                'participant_program_id' => $participantProgramId,
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Convertir texto a Capital Case (primera letra de cada palabra en mayúscula)
     */
    private function toCapitalCase(?string $text): ?string
    {
        if (empty($text)) {
            return $text;
        }
        return mb_convert_case(trim($text), MB_CASE_TITLE, 'UTF-8');
    }

    /**
     * Crear plan personalizado en VirtualPos si el participante tiene descuentos
     */
    private function createPersonalizedPlanIfNeeded(
        Participant $participant,
        ProgramCourse $programCourse,
        string $discountsJson
    ): void {
        try {
            Log::channel('daily')->info('=== UPDATE PARTICIPANT: Verificando creación de plan personalizado ===', [
                'participant_id' => $participant->id,
                'participant_name' => $participant->full_name,
                'program_course_id' => $programCourse->id,
                'program_course_name' => $programCourse->name,
                'discounts_json' => $discountsJson,
            ]);

            $discounts = json_decode($discountsJson, true);

            // Si no hay descuentos o están vacíos, no crear plan personalizado
            if (empty($discounts)) {
                Log::channel('daily')->info('UPDATE PARTICIPANT: No hay descuentos en JSON', [
                    'participant_id' => $participant->id,
                    'program_course_id' => $programCourse->id,
                    'discounts_decoded' => $discounts,
                ]);
                return;
            }

            // Verificar si hay al menos un descuento con valor
            $hasValidDiscount = false;
            $discountType = 'scholarship';
            $discountComment = null;

            foreach ($discounts as $discount) {
                Log::channel('daily')->info('UPDATE PARTICIPANT: Analizando descuento', [
                    'discount' => $discount,
                    'has_value' => !empty($discount['value']),
                    'type' => $discount['type'] ?? 'N/A',
                ]);

                if (!empty($discount['value']) || $discount['type'] === 'liberado') {
                    $hasValidDiscount = true;
                    if ($discount['type'] === 'liberado' || $discount['type'] === 'liberado_amount') {
                        $discountType = 'released';
                    }
                    $discountComment = $discount['comment'] ?? $discountComment;
                }
            }

            if (!$hasValidDiscount) {
                Log::channel('daily')->info('UPDATE PARTICIPANT: Descuentos sin valor válido', [
                    'participant_id' => $participant->id,
                    'program_course_id' => $programCourse->id,
                    'discounts' => $discounts,
                ]);
                return;
            }

            Log::channel('daily')->info('UPDATE PARTICIPANT: Descuento válido encontrado, procediendo a crear plan', [
                'participant_id' => $participant->id,
                'discount_type' => $discountType,
                'discount_comment' => $discountComment,
            ]);

            // Usar el servicio de planes personalizados
            $planService = app(ParticipantPlanService::class);

            // Verificar si ya existe un plan personalizado o suscripción activa
            $checkResult = $planService->needsPersonalizedPlan($participant, $programCourse);

            // Si tiene suscripción activa, no crear plan personalizado
            if ($checkResult['has_active_subscription']) {
                Log::channel('daily')->warning('UPDATE PARTICIPANT: No se crea plan - participante tiene suscripción activa', [
                    'participant_id' => $participant->id,
                    'program_course_id' => $programCourse->id,
                    'subscription_id' => $checkResult['active_subscription']?->id,
                    'subscription_status' => $checkResult['active_subscription']?->status,
                ]);
                return; // No crear plan si ya tiene suscripción activa
            }

            if ($checkResult['has_existing_plan']) {
                // Si ya existe un plan, desactivarlo para crear uno nuevo con los montos actualizados
                Log::channel('daily')->info('UPDATE PARTICIPANT: Desactivando plan existente', [
                    'participant_id' => $participant->id,
                    'existing_plan_id' => $checkResult['existing_plan']?->id,
                ]);
                $planService->deactivatePlan($participant->id, $programCourse->id);
            }

            // Crear el plan personalizado
            $result = $planService->createPersonalizedPlan(
                $participant,
                $programCourse,
                $discountType,
                $discountComment
            );

            if ($result['success']) {
                Log::channel('daily')->info('UPDATE PARTICIPANT: Plan personalizado creado exitosamente', [
                    'participant_id' => $participant->id,
                    'program_course_id' => $programCourse->id,
                    'virtualpos_plan_id' => $result['virtualpos_plan_id'],
                    'price_details' => $result['price_details']
                ]);
            } else {
                Log::channel('daily')->warning('UPDATE PARTICIPANT: No se pudo crear plan personalizado', [
                    'participant_id' => $participant->id,
                    'program_course_id' => $programCourse->id,
                    'error' => $result['message']
                ]);
            }

        } catch (\Exception $e) {
            Log::channel('daily')->error('UPDATE PARTICIPANT: Error al crear plan personalizado', [
                'participant_id' => $participant->id,
                'program_course_id' => $programCourse->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            // No lanzar excepción para no interrumpir la actualización del participante
        }
    }

    /**
     * Actualiza todos los enrollment_codes cuando cambia el documento del participante
     * Formato: {document_number}-{program_code}
     *
     * @param Participant $participant
     * @param string $oldDocumentNumber
     * @param string $newDocumentNumber
     * @return void
     */
    private function updateEnrollmentCodes(Participant $participant, string $oldDocumentNumber, string $newDocumentNumber): void
    {
        // Obtener todos los participant_program del participante
        $participantPrograms = DB::table('participant_program')
            ->where('participant_id', $participant->id)
            ->get();

        $updatedCount = 0;

        foreach ($participantPrograms as $pp) {
            $oldEnrollmentCode = $pp->enrollment_code;

            if (empty($oldEnrollmentCode)) {
                continue;
            }

            // Verificar que el enrollment_code empieza con el documento antiguo
            if (str_starts_with($oldEnrollmentCode, $oldDocumentNumber . '-')) {
                // Extraer el código del programa (parte después del guión)
                $programCode = substr($oldEnrollmentCode, strlen($oldDocumentNumber) + 1);

                // Generar el nuevo enrollment_code con el documento nuevo
                $newEnrollmentCode = $newDocumentNumber . '-' . $programCode;

                // Actualizar el enrollment_code
                DB::table('participant_program')
                    ->where('id', $pp->id)
                    ->update(['enrollment_code' => $newEnrollmentCode]);

                Log::info('UpdateParticipantService: enrollment_code actualizado', [
                    'participant_program_id' => $pp->id,
                    'old_enrollment_code' => $oldEnrollmentCode,
                    'new_enrollment_code' => $newEnrollmentCode,
                ]);

                $updatedCount++;
            }
        }

        Log::info('UpdateParticipantService: Cascade de enrollment_codes completado', [
            'participant_id' => $participant->id,
            'old_document' => $oldDocumentNumber,
            'new_document' => $newDocumentNumber,
            'enrollment_codes_updated' => $updatedCount,
        ]);
    }
}
