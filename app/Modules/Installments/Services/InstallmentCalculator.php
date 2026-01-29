<?php

namespace App\Modules\Installments\Services;

use App\Modules\Installments\Contracts\InstallmentCalculatorInterface;
use Carbon\Carbon;

class InstallmentCalculator implements InstallmentCalculatorInterface
{
    /**
     * Dividir un monto en cuotas iguales
     * El residuo se distribuye en las primeras cuotas
     *
     * @param float $totalAmount Monto total
     * @param int $installmentCount Número de cuotas
     * @return array Array de montos por cuota
     */
    public function splitAmount(float $totalAmount, int $installmentCount): array
    {
        if ($installmentCount <= 0) {
            return [];
        }

        // Asegurar que trabajamos con enteros
        $totalAmount = (int) round($totalAmount);

        // Monto base por cuota: solo .6+ hacia arriba (1-5 abajo, 6-9 arriba)
        $baseAmount = (int) floor(($totalAmount / $installmentCount) + 0.4);

        // Calcular monto de última cuota (absorbe el residuo)
        $allocated = $baseAmount * ($installmentCount - 1);
        $lastAmount = $totalAmount - $allocated;

        // Array de montos: primeras n-1 cuotas iguales, última absorbe residuo
        $amounts = [];
        for ($i = 0; $i < $installmentCount - 1; $i++) {
            $amounts[] = $baseAmount;
        }
        $amounts[] = $lastAmount;

        return $amounts;
    }

    /**
     * Generar fechas de vencimiento mensuales
     *
     * @param \Carbon\Carbon|string $startDate Fecha de inicio
     * @param int $installmentCount Número de cuotas
     * @param int|null $dayOfMonth Día del mes para vencimiento (null = mismo día)
     * @return array Array de fechas de vencimiento
     */
    public function generateMonthlyDueDates($startDate, int $installmentCount, ?int $dayOfMonth = null): array
    {
        $start = $startDate instanceof Carbon ? $startDate : Carbon::parse($startDate);
        $dates = [];

        for ($i = 0; $i < $installmentCount; $i++) {
            $dueDate = $start->copy()->addMonths($i);

            // Si se especifica un día del mes, ajustar
            if ($dayOfMonth !== null) {
                $dueDate->day($dayOfMonth);
            }

            $dates[] = $dueDate->format('Y-m-d');
        }

        return $dates;
    }

    /**
     * Generar fechas de vencimiento hacia atrás desde una fecha final
     *
     * @param \Carbon\Carbon|string $endDate Fecha final de pago
     * @param int $installmentCount Número de cuotas
     * @return array Array de fechas de vencimiento
     */
    public function generateDueDatesBackward($endDate, int $installmentCount): array
    {
        $end = $endDate instanceof Carbon ? $endDate : Carbon::parse($endDate);
        $dates = [];

        // Generar hacia atrás
        for ($i = $installmentCount - 1; $i >= 0; $i--) {
            $dueDate = $end->copy()->subMonths($i);
            $dates[] = $dueDate->format('Y-m-d');
        }

        return $dates;
    }

    /**
     * Calcular monto total de cuotas
     *
     * @param array $installmentAmounts Array de montos
     * @return float Monto total
     */
    public function calculateTotal(array $installmentAmounts): float
    {
        return array_sum($installmentAmounts);
    }

    /**
     * Recalcular cuotas pendientes después de un cambio de monto
     *
     * @param float $newTotal Nuevo monto total
     * @param int $paidCount Cuotas ya pagadas
     * @param int $totalCount Total de cuotas
     * @return array Array de montos para cuotas pendientes
     */
    public function recalculatePending(float $newTotal, int $paidCount, int $totalCount): array
    {
        $pendingCount = $totalCount - $paidCount;

        if ($pendingCount <= 0) {
            return [];
        }

        return $this->splitAmount($newTotal, $pendingCount);
    }

    /**
     * Generar fechas de vencimiento para programa LAT90
     * Usa la lógica específica del sistema LAT90
     *
     * @param \App\Models\Program $program
     * @param int $installmentCount
     * @return array
     */
    public function generateLat90DueDates($program, int $installmentCount): array
    {
        // Si el programa tiene fecha final de pago, generar hacia atrás
        if ($program->final_payment_date) {
            return $this->generateDueDatesBackward($program->final_payment_date, $installmentCount);
        }

        // Si no, generar hacia adelante desde hoy
        $startDate = Carbon::now()->addDays(config('installments.first_installment_days_offset', 30));
        $dayOfMonth = config('installments.due_day_of_month');

        return $this->generateMonthlyDueDates($startDate, $installmentCount, $dayOfMonth);
    }

    /**
     * Aplicar redondeo según configuración
     *
     * @param float $amount
     * @return float
     */
    protected function applyRounding(float $amount): float
    {
        $mode = config('installments.rounding_mode');

        switch ($mode) {
            case 'up':
                return ceil($amount);
            case 'down':
                return floor($amount);
            case 'nearest':
                return round($amount);
            default:
                return $amount;
        }
    }
}
