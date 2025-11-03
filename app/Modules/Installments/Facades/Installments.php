<?php

namespace App\Modules\Installments\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * Facade para acceso fácil al servicio de cuotas
 *
 * @method static bool isEnabled()
 * @method static \App\Models\InstallmentPlan|null createPlan(array $data)
 * @method static \App\Models\InstallmentPlan|null createOrGetPlan(int $programId, string $rut, array $paymentData, array $formData)
 * @method static bool markAsPaid(int $installmentId, array $paymentData)
 * @method static bool restructurePlan(int $planId, int $newInstallments, string $reason)
 * @method static bool recalculateAfterDiscount(int $planId)
 * @method static \App\Models\Installment|null getNextPendingInstallment(int $planId)
 * @method static \App\Models\InstallmentPlan|null getPlan(int $planId)
 * @method static array calculateInstallments(float $totalAmount, int $installmentCount)
 * @method static array generateDueDates($program, int $installmentCount)
 * @method static bool isPlanCompleted(int $planId)
 *
 * @see \App\Modules\Installments\Contracts\InstallmentServiceInterface
 */
class Installments extends Facade
{
    /**
     * Get the registered name of the component.
     */
    protected static function getFacadeAccessor(): string
    {
        return 'installments';
    }
}
