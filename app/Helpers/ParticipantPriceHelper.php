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

        // 3. Calcular descuentos aplicados (separados por tipo)
        $discountBreakdown = self::calculateDiscounts($participant, $programCourse, $basePrice);
        $discounts = $discountBreakdown['total'];

        // 4. Calcular precio final (redondeado a entero, CLP no tiene centavos)
        $finalPrice = (int) round(max(0, $basePrice + $adjustments - $discounts));

        // LOG para depuración
        \Illuminate\Support\Facades\Log::info('ParticipantPriceHelper::calculateParticipantPrice', [
            'participant_id' => $participant->id,
            'participant_name' => $participant->full_name,
            'program_course_id' => $programCourse->id ?? 'N/A',
            'program_course_name' => $programCourse->name ?? 'N/A',
            'trip_price' => $programCourse->trip_price ?? 0,
            'PRECIO_BASE' => $basePrice,
            'AJUSTES' => $adjustments,
            'DESCUENTOS' => $discounts,
            'DESCUENTOS_REGULARES' => $discountBreakdown['regular'],
            'MONTO_LIBERADO' => $discountBreakdown['released'],
            'PRECIO_FINAL' => $finalPrice,
        ]);

        return [
            'base_price' => $basePrice,
            'adjustments' => $adjustments,
            'discounts' => $discounts,
            'regular_discounts' => $discountBreakdown['regular'],
            'released_discounts' => $discountBreakdown['released'],
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
        // IMPORTANTE: Program NO tiene relación course, solo ProgramCourse
        $course = $isProgramCourse ? $programCourse->course : null;

        $source = 'NINGUNO';
        $price = 0.0;

        // 1. PRIMERO: Buscar en el pivote participant_course (consulta directa para evitar problemas de carga de relaciones)
        if ($course) {
            $pivot = DB::table('participant_course')
                ->where('participant_id', $participant->id)
                ->where('course_id', $course->id)
                ->first();

            \Illuminate\Support\Facades\Log::info('getBasePrice - Buscando en participant_course', [
                'participant_id' => $participant->id,
                'course_id' => $course->id,
                'pivot_encontrado' => $pivot ? 'SI' : 'NO',
                'pivot_individual_price' => $pivot->individual_price ?? 'NULL',
            ]);

            if ($pivot && $pivot->individual_price && $pivot->individual_price > 0) {
                $source = 'participant_course.individual_price';
                $price = (float) $pivot->individual_price;
            }
        }

        // 2. Fallback: Buscar en participant_program.individual_price
        if ($price == 0 && $programCourseId) {
            $pp = DB::table('participant_program')
                ->where('participant_id', $participant->id)
                ->where('program_id', $programCourseId)
                ->first();

            \Illuminate\Support\Facades\Log::info('getBasePrice - Buscando en participant_program', [
                'participant_id' => $participant->id,
                'program_course_id' => $programCourseId,
                'pp_encontrado' => $pp ? 'SI' : 'NO',
                'pp_individual_price' => $pp->individual_price ?? 'NULL',
            ]);

            if ($pp && $pp->individual_price && $pp->individual_price > 0) {
                $source = 'participant_program.individual_price';
                $price = (float) $pp->individual_price;
            }
        }

        // 3. Fallback al precio individual del participante
        if ($price == 0 && $participant->individual_price && $participant->individual_price > 0) {
            $source = 'participant.individual_price';
            $price = (float) $participant->individual_price;
        }

        // 4. Fallback al precio del programa/program_course
        if ($price == 0) {
            $source = 'programCourse.trip_price';
            $price = (float) ($programCourse->trip_price ?? 0);
        }

        \Illuminate\Support\Facades\Log::info('getBasePrice - RESULTADO FINAL', [
            'participant_id' => $participant->id,
            'FUENTE_DEL_PRECIO' => $source,
            'PRECIO_BASE_FINAL' => $price,
            'trip_price_programa' => $programCourse->trip_price ?? 0,
        ]);

        return $price;
    }
    
    /**
     * Obtiene los ajustes del pivote (si existen)
     */
    private static function getAdjustments(Participant $participant, ProgramCourse|Program $programCourse): float
    {
        // Determinar si es ProgramCourse o Program
        $isProgramCourse = $programCourse instanceof ProgramCourse;
        // IMPORTANTE: Program NO tiene relación course, solo ProgramCourse
        $course = $isProgramCourse ? $programCourse->course : null;

        // Consulta directa al pivote para evitar problemas de carga de relaciones
        if ($course) {
            $pivot = DB::table('participant_course')
                ->where('participant_id', $participant->id)
                ->where('course_id', $course->id)
                ->first();

            if ($pivot && $pivot->price_adjustments) {
                return (float) $pivot->price_adjustments;
            }
        }

        return 0.0;
    }
    
    /**
     * Calcula los descuentos aplicados al participante, separados por tipo.
     *
     * @return array ['total' => float, 'regular' => float, 'released' => float]
     */
    private static function calculateDiscounts(Participant $participant, ProgramCourse|Program $programCourse, float $basePrice): array
    {
        $empty = ['total' => 0.0, 'regular' => 0.0, 'released' => 0.0];

        // Determinar si es ProgramCourse o Program
        $isProgramCourse = $programCourse instanceof ProgramCourse;
        $programCourseId = $isProgramCourse ? $programCourse->id : null;

        if (!$programCourseId) {
            return $empty;
        }

        // Buscar el participant_program_id (program_id apunta a program_courses)
        $pp = DB::table('participant_program')
            ->where('participant_id', $participant->id)
            ->where('program_id', $programCourseId)
            ->first();

        if (!$pp) {
            return $empty;
        }

        // Obtener todos los descuentos activos
        $discounts = DB::table('participant_program_discounts')
            ->where('participant_program_id', $pp->id)
            ->get();

        $regularDiscount = 0.0;
        $releasedDiscount = 0.0;

        foreach ($discounts as $discount) {
            $amount = 0.0;

            if ($discount->percent && $discount->percent > 0) {
                $amount += ($basePrice * $discount->percent) / 100;
            }

            if ($discount->amount && $discount->amount > 0) {
                $amount += $discount->amount;
            }

            if ($discount->discount_type === 'released') {
                $releasedDiscount += $amount;
            } else {
                $regularDiscount += $amount;
            }
        }

        return [
            'total' => $regularDiscount + $releasedDiscount,
            'regular' => $regularDiscount,
            'released' => $releasedDiscount,
        ];
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
