<?php

namespace App\Helpers;

use App\Models\Participant;
use App\Models\Program;

class EnrollmentCodeHelper
{
    /**
     * Genera un código de enrolamiento basado en el programa y participante
     * 
     * @param Program $program
     * @param Participant $participant
     * @return string|null
     */
    public static function generateEnrollmentCode(Program $program, Participant $participant): ?string
    {
        $code = (string) ($program->code ?? '');
        
        if (!$code || !$participant->document_number) {
            return null;
        }
        
        if (strtolower($participant->document_type) === 'rut') {
            // Para RUT: usar código del programa + RUT completo sin dígito verificador
            $digits = preg_replace('/\D/', '', (string) $participant->document_number);
            // Remover el último dígito (dígito verificador)
            $rutDigits = substr($digits, 0, -1) ?: null;
            
            if ($rutDigits) {
                return $code . $rutDigits;
            }
        } else {
            // Para pasaporte: usar código del programa + número completo del pasaporte
            return $code . $participant->document_number;
        }
        
        return null;
    }
    
    /**
     * Valida que un código de enrolamiento sea válido
     * 
     * @param string $enrollmentCode
     * @param Program $program
     * @param Participant $participant
     * @return bool
     */
    public static function validateEnrollmentCode(string $enrollmentCode, Program $program, Participant $participant): bool
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
