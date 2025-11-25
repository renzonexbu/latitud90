<?php

namespace App\Helpers;

use App\Models\ProgramCourse;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class PaymentDocumentTypeHelper
{
    /**
     * Determina el tipo de documento según el año del programa
     *
     * Regla:
     * - AC (Anticipo): Si el programa es para un año POSTERIOR al actual
     * - B2 (Boleta): Si el programa es para el MISMO año actual
     *
     * @param int $programCourseId ID del program_course
     * @return string 'AC' o 'B2'
     */
    public static function determineDocumentType(int $programCourseId): string
    {
        try {
            $programCourse = ProgramCourse::find($programCourseId);

            if (!$programCourse) {
                Log::warning('ProgramCourse not found for document_type determination', [
                    'program_course_id' => $programCourseId
                ]);
                return 'B2'; // Default a B2 si no se encuentra el programa
            }

            // Obtener la fecha de salida o inicio del programa
            $programDate = $programCourse->departure_date ?? $programCourse->start_date;

            if (!$programDate) {
                Log::warning('Program has no departure_date or start_date', [
                    'program_course_id' => $programCourseId
                ]);
                return 'B2'; // Default a B2 si no tiene fecha
            }

            $currentYear = Carbon::now()->year;
            $programYear = Carbon::parse($programDate)->year;

            // Si el programa es para el año siguiente o posterior, es AC (Anticipo)
            if ($programYear > $currentYear) {
                Log::info('Document type determined as AC (anticipo)', [
                    'program_course_id' => $programCourseId,
                    'current_year' => $currentYear,
                    'program_year' => $programYear,
                    'program_date' => $programDate
                ]);
                return 'AC';
            }

            // Si el programa es para el mismo año, es B2 (Boleta)
            Log::info('Document type determined as B2 (boleta)', [
                'program_course_id' => $programCourseId,
                'current_year' => $currentYear,
                'program_year' => $programYear,
                'program_date' => $programDate
            ]);
            return 'B2';

        } catch (\Exception $e) {
            Log::error('Error determining document_type', [
                'program_course_id' => $programCourseId,
                'error' => $e->getMessage()
            ]);
            return 'B2'; // Default a B2 en caso de error
        }
    }
}
