<?php

namespace App\Modules\Installments\Contracts;

use App\Models\Installment;
use App\Models\InstallmentPlan;

interface InstallmentServiceInterface
{
    /**
     * Verificar si el sistema de cuotas está habilitado
     */
    public function isEnabled(): bool;

    /**
     * Crear un plan de cuotas
     *
     * @param array $data Datos del plan (program_id, participant_id, total_amount, installments, etc.)
     * @return InstallmentPlan|null
     */
    public function createPlan(array $data): ?InstallmentPlan;

    /**
     * Obtener o crear un plan de cuotas
     *
     * @param int $programId
     * @param string $rut
     * @param array $paymentData
     * @param array $formData
     * @return InstallmentPlan|null
     */
    public function createOrGetPlan(int $programId, string $rut, array $paymentData, array $formData): ?InstallmentPlan;

    /**
     * Marcar una cuota como pagada
     *
     * @param int $installmentId
     * @param array $paymentData Datos del pago (order_id, order_detail_id, payment_id)
     * @return bool
     */
    public function markAsPaid(int $installmentId, array $paymentData): bool;

    /**
     * Reestructurar un plan de cuotas
     *
     * @param int $planId
     * @param int $newInstallments Nuevo número de cuotas
     * @param string $reason Razón de la reestructuración
     * @return bool
     */
    public function restructurePlan(int $planId, int $newInstallments, string $reason): bool;

    /**
     * Recalcular cuotas después de aplicar descuento
     *
     * @param int $planId
     * @return bool
     */
    public function recalculateAfterDiscount(int $planId): bool;

    /**
     * Obtener la siguiente cuota pendiente de un plan
     *
     * @param int $planId
     * @return Installment|null
     */
    public function getNextPendingInstallment(int $planId): ?Installment;

    /**
     * Obtener un plan de cuotas por ID
     *
     * @param int $planId
     * @return InstallmentPlan|null
     */
    public function getPlan(int $planId): ?InstallmentPlan;

    /**
     * Calcular distribución de monto en cuotas
     *
     * @param float $totalAmount Monto total
     * @param int $installmentCount Número de cuotas
     * @return array Array de montos por cuota
     */
    public function calculateInstallments(float $totalAmount, int $installmentCount): array;

    /**
     * Generar fechas de vencimiento para cuotas mensuales
     *
     * @param \App\Models\Program $program
     * @param int $installmentCount
     * @return array Array de fechas de vencimiento
     */
    public function generateDueDates($program, int $installmentCount): array;

    /**
     * Verificar si un plan está completo (todas las cuotas pagadas)
     *
     * @param int $planId
     * @return bool
     */
    public function isPlanCompleted(int $planId): bool;
}
