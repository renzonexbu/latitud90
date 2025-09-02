<?php

namespace App\Helpers;

use App\Models\Participant;
use App\Models\Program;
use Illuminate\Support\Facades\DB;

class ParticipantPriceHelper
{
    /**
     * Calcula el precio final que debe pagar un participante para un programa específico
     * Considerando el precio base, ajustes y descuentos aplicados
     *
     * @param Participant $participant
     * @param Program $program
     * @return array ['base_price', 'adjustments', 'discounts', 'final_price']
     */
    public static function calculateParticipantPrice(Participant $participant, Program $program): array
    {
        // 1. Obtener el precio base del participante para este programa
        $basePrice = self::getBasePrice($participant, $program);
        
        // 2. Obtener ajustes del pivote (si existen)
        $adjustments = self::getAdjustments($participant, $program);
        
        // 3. Calcular descuentos aplicados
        $discounts = self::calculateDiscounts($participant, $program, $basePrice);
        
        // 4. Calcular precio final
        $finalPrice = max(0, $basePrice + $adjustments - $discounts);
        
        return [
            'base_price' => $basePrice,
            'adjustments' => $adjustments,
            'discounts' => $discounts,
            'final_price' => $finalPrice
        ];
    }
    
    /**
     * Obtiene el precio base del participante para el programa
     */
    private static function getBasePrice(Participant $participant, Program $program): float
    {
        // 1. Intentar obtener desde participant_program
        $pp = DB::table('participant_program')
            ->where('participant_id', $participant->id)
            ->where('program_id', $program->id)
            ->first();
            
        if ($pp && $pp->individual_price) {
            return (float) $pp->individual_price;
        }
        
        // 2. Fallback al pivote participant_course
        if ($program->course) {
            $pivotParticipant = $program->course->participants
                ->firstWhere('id', $participant->id);
                
            if ($pivotParticipant && $pivotParticipant->pivot) {
                return (float) ($pivotParticipant->pivot->individual_price ?? 0);
            }
        }
        
        // 3. Fallback al precio individual del participante
        if ($participant->individual_price) {
            return (float) $participant->individual_price;
        }
        
        // 4. Fallback al precio del programa
        return (float) $program->trip_price;
    }
    
    /**
     * Obtiene los ajustes del pivote (si existen)
     */
    private static function getAdjustments(Participant $participant, Program $program): float
    {
        // Solo considerar ajustes del pivote participant_course (sistema antiguo)
        if ($program->course) {
            $pivotParticipant = $program->course->participants
                ->firstWhere('id', $participant->id);
                
            if ($pivotParticipant && $pivotParticipant->pivot) {
                return (float) ($pivotParticipant->pivot->price_adjustments ?? 0);
            }
        }
        
        return 0.0;
    }
    
    /**
     * Calcula el total de descuentos aplicados al participante para este programa
     */
    private static function calculateDiscounts(Participant $participant, Program $program, float $basePrice): float
    {
        // Buscar el participant_program_id
        $pp = DB::table('participant_program')
            ->where('participant_id', $participant->id)
            ->where('program_id', $program->id)
            ->first();
            
        if (!$pp) {
            return 0.0;
        }
        
        // Obtener todos los descuentos activos
        $discounts = DB::table('participant_program_discounts')
            ->where('participant_program_id', $pp->id)
            ->get();
            
        $totalDiscount = 0.0;
        
        foreach ($discounts as $discount) {
            // Descuento por porcentaje
            if ($discount->percent && $discount->percent > 0) {
                $totalDiscount += ($basePrice * $discount->percent) / 100;
            }
            
            // Descuento por monto fijo
            if ($discount->amount && $discount->amount > 0) {
                $totalDiscount += $discount->amount;
            }
        }
        
        return $totalDiscount;
    }
    
    /**
     * Obtiene el precio final simplificado (para compatibilidad)
     */
    public static function getFinalPrice(Participant $participant, Program $program): float
    {
        $priceData = self::calculateParticipantPrice($participant, $program);
        return $priceData['final_price'];
    }
}
