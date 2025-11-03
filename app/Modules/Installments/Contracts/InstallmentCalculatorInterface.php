<?php

namespace App\Modules\Installments\Contracts;

interface InstallmentCalculatorInterface
{
    /**
     * Dividir un monto en cuotas iguales
     * El residuo se distribuye en las primeras cuotas
     *
     * @param float $totalAmount Monto total
     * @param int $installmentCount Número de cuotas
     * @return array Array de montos por cuota
     */
    public function splitAmount(float $totalAmount, int $installmentCount): array;

    /**
     * Generar fechas de vencimiento mensuales
     *
     * @param \Carbon\Carbon|string $startDate Fecha de inicio
     * @param int $installmentCount Número de cuotas
     * @param int|null $dayOfMonth Día del mes para vencimiento (null = mismo día)
     * @return array Array de fechas de vencimiento
     */
    public function generateMonthlyDueDates($startDate, int $installmentCount, ?int $dayOfMonth = null): array;

    /**
     * Generar fechas de vencimiento hacia atrás desde una fecha final
     *
     * @param \Carbon\Carbon|string $endDate Fecha final de pago
     * @param int $installmentCount Número de cuotas
     * @return array Array de fechas de vencimiento
     */
    public function generateDueDatesBackward($endDate, int $installmentCount): array;

    /**
     * Calcular monto total de cuotas
     *
     * @param array $installmentAmounts Array de montos
     * @return float Monto total
     */
    public function calculateTotal(array $installmentAmounts): float;

    /**
     * Recalcular cuotas pendientes después de un cambio de monto
     *
     * @param float $newTotal Nuevo monto total
     * @param int $paidCount Cuotas ya pagadas
     * @param int $totalCount Total de cuotas
     * @return array Array de montos para cuotas pendientes
     */
    public function recalculatePending(float $newTotal, int $paidCount, int $totalCount): array;
}
