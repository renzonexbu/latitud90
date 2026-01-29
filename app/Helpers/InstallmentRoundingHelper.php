<?php

namespace App\Helpers;

/**
 * Helper para redondeo de montos de cuotas.
 *
 * Regla de redondeo para pesos chilenos (sin decimales):
 * - Decimales de .0 a .59... → hacia abajo (floor)
 * - Decimales de .6 a .99... → hacia arriba (ceil)
 * - La última cuota absorbe el residuo
 *
 * Ejemplos:
 * - 149555.55 → 149555 (porque .55 < .6)
 * - 149555.65 → 149556 (porque .65 >= .6)
 * - 149555.50 → 149555 (porque .50 < .6)
 * - 33333.33 → 33333 (porque .33 < .6)
 */
class InstallmentRoundingHelper
{
    /**
     * Redondear monto usando regla: solo .6+ hacia arriba
     *
     * @param float $amount Monto a redondear
     * @return int Monto redondeado (entero)
     */
    public static function round(float $amount): int
    {
        // Fórmula: si decimal < 0.6, floor; si decimal >= 0.6, ceil
        // Equivalente a: floor($amount + 0.4)
        return (int) floor($amount + 0.4);
    }

    /**
     * Dividir un monto total en cuotas.
     * Primeras n-1 cuotas iguales (redondeadas), última cuota absorbe el residuo.
     *
     * @param float $total Monto total a dividir
     * @param int $installments Número de cuotas
     * @return array Array de montos (enteros) por cuota
     */
    public static function splitAmount(float $total, int $installments): array
    {
        if ($installments <= 0) {
            return [];
        }

        // Asegurar que trabajamos con enteros
        $total = (int) round($total);

        // Monto base por cuota (redondeo: .0-.59 abajo, .6-.99 arriba)
        $base = self::round($total / $installments);

        // Última cuota absorbe el residuo
        $allocated = $base * ($installments - 1);
        $lastAmount = $total - $allocated;

        // Primeras n-1 cuotas iguales, última absorbe residuo
        $amounts = [];
        for ($i = 0; $i < $installments - 1; $i++) {
            $amounts[] = $base;
        }
        $amounts[] = $lastAmount;

        return $amounts;
    }
}
