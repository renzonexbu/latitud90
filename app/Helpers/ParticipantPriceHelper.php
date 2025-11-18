<?php

namespace App\Helpers;

use App\Models\Participant;
use App\Models\Program;
use App\Models\ProgramCourse;
use Illuminate\Support\Facades\DB;

class ParticipantPriceHelper
{
    /**
     * Calcula el precio final que debe pagar un participante para un programa específico
     * Considerando el precio base, ajustes y descuentos aplicados
     *
     * @param Participant $participant
     * @param ProgramCourse|Program $programCourse Acepta ProgramCourse (nueva arquitectura) o Program (compatibilidad)
     * @return array ['base_price', 'adjustments', 'discounts', 'final_price']
     */
    public static function calculateParticipantPrice(Participant $participant, ProgramCourse|Program $programCourse): array
    {
        // 1. Obtener el precio base del participante para este programa
        $basePrice = self::getBasePrice($participant, $programCourse);

        // 2. Obtener ajustes del pivote (si existen)
        $adjustments = self::getAdjustments($participant, $programCourse);

        // 3. Calcular descuentos aplicados
        $discounts = self::calculateDiscounts($participant, $programCourse, $basePrice);

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
    private static function getBasePrice(Participant $participant, ProgramCourse|Program $programCourse): float
    {
        // Determinar si es ProgramCourse o Program
        $isProgramCourse = $programCourse instanceof ProgramCourse;
        $programCourseId = $isProgramCourse ? $programCourse->id : null;
        $course = $isProgramCourse ? $programCourse->course : ($programCourse->course ?? null);

        // 1. Intentar obtener desde participant_program (program_id apunta a program_courses)
        if ($programCourseId) {
            $pp = DB::table('participant_program')
                ->where('participant_id', $participant->id)
                ->where('program_id', $programCourseId) // program_id ahora apunta a program_courses
                ->first();

            if ($pp && $pp->individual_price) {
                return (float) $pp->individual_price;
            }
        }

        // 2. Fallback al pivote participant_course
        if ($course) {
            $pivotParticipant = $course->participants
                ->firstWhere('id', $participant->id);

            if ($pivotParticipant && $pivotParticipant->pivot) {
                return (float) ($pivotParticipant->pivot->individual_price ?? 0);
            }
        }

        // 3. Fallback al precio individual del participante
        if ($participant->individual_price) {
            return (float) $participant->individual_price;
        }

        // 4. Fallback al precio del programa/program_course
        return (float) $programCourse->trip_price;
    }
    
    /**
     * Obtiene los ajustes del pivote (si existen)
     */
    private static function getAdjustments(Participant $participant, ProgramCourse|Program $programCourse): float
    {
        // Determinar si es ProgramCourse o Program
        $isProgramCourse = $programCourse instanceof ProgramCourse;
        $course = $isProgramCourse ? $programCourse->course : ($programCourse->course ?? null);

        // Solo considerar ajustes del pivote participant_course (sistema antiguo)
        if ($course) {
            $pivotParticipant = $course->participants
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
    private static function calculateDiscounts(Participant $participant, ProgramCourse|Program $programCourse, float $basePrice): float
    {
        // Determinar si es ProgramCourse o Program
        $isProgramCourse = $programCourse instanceof ProgramCourse;
        $programCourseId = $isProgramCourse ? $programCourse->id : null;

        if (!$programCourseId) {
            return 0.0; // No hay descuentos para programas sin ID
        }

        // Buscar el participant_program_id (program_id apunta a program_courses)
        $pp = DB::table('participant_program')
            ->where('participant_id', $participant->id)
            ->where('program_id', $programCourseId) // program_id ahora apunta a program_courses
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
    public static function getFinalPrice(Participant $participant, ProgramCourse|Program $programCourse): float
    {
        $priceData = self::calculateParticipantPrice($participant, $programCourse);
        return $priceData['final_price'];
    }
}
