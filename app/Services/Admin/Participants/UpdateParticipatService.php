<?php

namespace App\Services\Admin\Participants;

use App\Models\Participant;
use App\Models\ParticipantProgramDiscount;
use App\Models\Course;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class UpdateParticipatService
{
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
                throw new \Exception('El RUT no se puede modificar');
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

            // Actualizar el participante
            $participant->update($updateData);

            // Si se especificó un curso, gestionar descuentos y precio individual
            if (!empty($data['pivot_course_id'])) {
                $course = Course::find((int) $data['pivot_course_id']);
                if ($course && $course->program_id) {
                    // Buscar o crear el participant_program
                    $participantProgram = DB::table('participant_program')
                        ->where('participant_id', $participant->id)
                        ->where('program_id', $course->program_id)
                        ->first();

                    if (!$participantProgram) {
                        // Crear el participant_program si no existe
                        $participantProgramId = DB::table('participant_program')->insertGetId([
                            'participant_id' => $participant->id,
                            'program_id' => $course->program_id,
                            'enrollment_code' => 'ENR-' . time(),
                            'individual_price' => $data['individual_price'] ?? 0,
                            'status' => 'active',
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    } else {
                        $participantProgramId = $participantProgram->id;
                        
                        // Actualizar el precio individual si se proporciona
                        if (array_key_exists('individual_price', $data)) {
                            DB::table('participant_program')
                                ->where('id', $participantProgramId)
                                ->update([
                                    'individual_price' => $data['individual_price'],
                                    'updated_at' => now(),
                                ]);
                        }
                    }

                    // Procesar descuentos si se proporcionan
                    if (isset($data['discounts'])) {
                        $this->processDiscounts($participantProgramId, $data['discounts']);
                    }
                }

                // Actualizar el pivot del curso con el precio individual
                if (array_key_exists('individual_price', $data)) {
                    $participant->courses()->updateExistingPivot((int) $data['pivot_course_id'], [
                        'individual_price' => $data['individual_price']
                    ]);
                }
            }

            DB::commit();
            return $participant;

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Procesa los descuentos para un participant_program
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
                    
                    if ($discountData['type'] === 'percent') {
                        $percent = $discountData['value'] ?? null;
                    } elseif ($discountData['type'] === 'amount') {
                        $amount = $discountData['value'] ?? null;
                    } elseif ($discountData['type'] === 'liberado') {
                        $percent = 100; // Liberado = 100%
                    }
                    
                    $discount->update([
                        'percent' => $percent,
                        'amount' => $amount,
                        'comment' => $discountData['comment'] ?? null,
                    ]);
                    $processedDiscountIds[] = $discount->id;
                }
            } else {
                // Crear nuevo descuento
                // Mapear el tipo y valor a percent/amount según corresponda
                $percent = null;
                $amount = null;
                
                if ($discountData['type'] === 'percent') {
                    $percent = $discountData['value'] ?? null;
                } elseif ($discountData['type'] === 'amount') {
                    $amount = $discountData['value'] ?? null;
                } elseif ($discountData['type'] === 'liberado') {
                    $percent = 100; // Liberado = 100%
                }
                
                $newDiscount = ParticipantProgramDiscount::create([
                    'participant_program_id' => $participantProgramId,
                    'percent' => $percent,
                    'amount' => $amount,
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
    }
}
