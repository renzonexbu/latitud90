<?php

namespace App\Helpers;

use App\Models\Participant;
use App\Models\Program;
use App\Models\ProgramCourse;

class EnrollmentCodeHelper
{
    /**
     * Genera un código de enrolamiento basado en el programa/programCourse y participante
     * Formato: DOCUMENTO-CODIGO (ej: 233251437-V0017)
     *
     * @param Program|ProgramCourse $program
     * @param Participant $participant
     * @return string|null
     */
    public static function generateEnrollmentCode(Program|ProgramCourse $program, Participant $participant): ?string
    {
        $code = (string) ($program->code ?? '');

        if (!$code || !$participant->document_number) {
            return null;
        }

        // Formato consistente: documento_number-codigo_programa
        // Esto coincide con el formato usado en CourseService para carga masiva
        return $participant->document_number . '-' . $code;
    }
    
    /**
     * Valida que un código de enrolamiento sea válido
     *
     * @param string $enrollmentCode
     * @param Program|ProgramCourse $program
     * @param Participant $participant
     * @return bool
     */
    public static function validateEnrollmentCode(string $enrollmentCode, Program|ProgramCourse $program, Participant $participant): bool
    {
        $expectedCode = self::generateEnrollmentCode($program, $participant);
        return $expectedCode === $enrollmentCode;
    }
    
    /**
     * Extrae el RUT sin dígito verificador de un número de documento
     * 
     * @param string $documentNumber
     * @return string|null
     */
    public static function extractRutDigits(string $documentNumber): ?string
    {
        // Extraer solo los dígitos del RUT
        $digits = preg_replace('/\D/', '', $documentNumber);
        
        // Remover el último dígito (dígito verificador)
        return substr($digits, 0, -1) ?: null;
    }
}
