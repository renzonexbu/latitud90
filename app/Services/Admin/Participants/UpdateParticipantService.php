<?php

namespace App\Services\Admin\Participants;

use App\Models\Participant;
use App\Models\ParticipantProgramDiscount;
use App\Models\Course;
use App\Models\Program;
use App\Traits\AdminLogging;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Exception;
use App\Services\Admin\Installments\InstallmentRecalculationService;
use App\Helpers\ParticipantPriceHelper;

class UpdateParticipantService
{
    use AdminLogging;
    /**
     * Actualiza los datos personales de un participante
     *
     * @param array $data
     * @param Participant $participant
     * @return Participant
     */
    public function execute(array $data, Participant $participant): Participant
    {
        try {
            DB::beginTransaction();

            // Validar que el RUT no se esté intentando modificar
            if (isset($data['document_number']) && $data['document_number'] !== $participant->document_number) {
                throw new Exception('El RUT no se puede modificar');
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

            // Guardar valores anteriores para el log
            $oldValues = $participant->toArray();

            // Actualizar el participante
            $participant->update($updateData);

            // Si se especificó un curso, actualizar el pivot del curso con el precio individual
            if (!empty($data['pivot_course_id'])) {
                $course = Course::find((int) $data['pivot_course_id']);

                if ($course) {
                    // Actualizar el pivot del curso con el precio individual
                    if (array_key_exists('individual_price', $data)) {
                        $participant->courses()->updateExistingPivot((int) $data['pivot_course_id'], [
                            'individual_price' => $data['individual_price']
                        ]);
                    }

                    // Actualizar ajustes de precio si se proporcionan
                    if (array_key_exists('price_adjustments', $data)) {
                        $participant->courses()->updateExistingPivot((int) $data['pivot_course_id'], [
                            'price_adjustments' => $data['price_adjustments'],
                            'adjustment_reason' => $data['adjustment_reason'] ?? null
                        ]);
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
     * DEPRECATED: Este método ya no se usa con la nueva estructura de base de datos
     * Los descuentos ahora se manejan directamente en el pivot participant_course
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
                    $discountType = 'scholarship'; // Por defecto
                    
                    if ($discountData['type'] === 'percent') {
                        $percent = $discountData['value'] ?? null;
                        $discountType = 'scholarship';
                    } elseif ($discountData['type'] === 'amount') {
                        $amount = $discountData['value'] ?? null;
                        $discountType = 'scholarship';
                    } elseif ($discountData['type'] === 'liberado') {
                        $percent = 100; // Liberado = 100%
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
                $discountType = 'scholarship'; // Por defecto
                
                if ($discountData['type'] === 'percent') {
                    $percent = $discountData['value'] ?? null;
                    $discountType = 'scholarship';
                } elseif ($discountData['type'] === 'amount') {
                    $amount = $discountData['value'] ?? null;
                    $discountType = 'scholarship';
                } elseif ($discountData['type'] === 'liberado') {
                    $percent = 100; // Liberado = 100%
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
}
