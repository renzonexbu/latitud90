<?php

namespace App\Helpers;

class RutHelper
{
    /**
     * Clean RUT by removing dots, hyphens and spaces, keeping only numbers and K
     * Always returns uppercase K for verification digit
     *
     * @param string|null $rut
     * @return string|null
     */
    public static function clean(?string $rut): ?string
    {
        if (empty($rut)) {
            return null;
        }

        return strtoupper(trim(preg_replace('/[^0-9kK]/', '', $rut)));
    }

    /**
     * Validate Chilean RUT
     *
     * @param string|null $rut
     * @return bool
     */
    public static function validate(?string $rut): bool
    {
        if (empty($rut)) {
            return false;
        }

        $cleanRut = self::clean($rut);
        
        if (strlen($cleanRut) < 8) {
            return false;
        }

        $body = substr($cleanRut, 0, -1);
        $dv = substr($cleanRut, -1);
        
        $sum = 0;
        $factor = 2;
        
        for ($i = strlen($body) - 1; $i >= 0; $i--) {
            $sum += $body[$i] * $factor;
            $factor = $factor === 7 ? 2 : $factor + 1;
        }
        
        $calculatedDv = 11 - ($sum % 11);
        $calculatedDv = $calculatedDv === 10 ? 'K' : ($calculatedDv === 11 ? '0' : $calculatedDv);
        
        return $dv === $calculatedDv;
    }

    /**
     * Format RUT for display (adds dots and hyphen)
     *
     * @param string|null $rut
     * @return string|null
     */
    public static function format(?string $rut): ?string
    {
        if (empty($rut)) {
            return null;
        }

        $cleanRut = self::clean($rut);
        
        if (strlen($cleanRut) < 8) {
            return $cleanRut;
        }

        $body = substr($cleanRut, 0, -1);
        $dv = substr($cleanRut, -1);
        
        // Add dots every 3 digits from right to left
        $formattedBody = number_format($body, 0, '', '.');
        
        return $formattedBody . '-' . $dv;
    }
}
