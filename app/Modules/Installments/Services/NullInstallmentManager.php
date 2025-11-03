<?php

namespace App\Modules\Installments\Services;

use App\Models\Installment;
use App\Models\InstallmentPlan;
use App\Modules\Installments\Contracts\InstallmentServiceInterface;

/**
 * Implementación nula del servicio de cuotas
 * Se usa cuando el sistema de cuotas está desactivado
 */
class NullInstallmentManager implements InstallmentServiceInterface
{
    /**
     * El sistema siempre está deshabilitado
     */
    public function isEnabled(): bool
    {
        return false;
    }

    /**
     * No crea planes
     */
    public function createPlan(array $data): ?InstallmentPlan
    {
        return null;
    }

    /**
     * No crea ni obtiene planes
     */
    public function createOrGetPlan(int $programId, string $rut, array $paymentData, array $formData): ?InstallmentPlan
    {
        return null;
    }

    /**
     * No marca como pagado
     */
    public function markAsPaid(int $installmentId, array $paymentData): bool
    {
        return false;
    }

    /**
     * No reestructura planes
     */
    public function restructurePlan(int $planId, int $newInstallments, string $reason): bool
    {
        return false;
    }

    /**
     * No recalcula
     */
    public function recalculateAfterDiscount(int $planId): bool
    {
        return false;
    }

    /**
     * No devuelve cuotas
     */
    public function getNextPendingInstallment(int $planId): ?Installment
    {
        return null;
    }

    /**
     * No devuelve planes
     */
    public function getPlan(int $planId): ?InstallmentPlan
    {
        return null;
    }

    /**
     * No calcula
     */
    public function calculateInstallments(float $totalAmount, int $installmentCount): array
    {
        return [];
    }

    /**
     * No genera fechas
     */
    public function generateDueDates($program, int $installmentCount): array
    {
        return [];
    }

    /**
     * Siempre retorna false
     */
    public function isPlanCompleted(int $planId): bool
    {
        return false;
    }
}
