<?php

namespace App\Modules\Installments\Contracts;

use App\Models\Installment;
use Illuminate\Support\Collection;

interface InstallmentRepositoryInterface
{
    /**
     * Encontrar una cuota por ID
     */
    public function find(int $id): ?Installment;

    /**
     * Crear una nueva cuota
     */
    public function create(array $data): Installment;

    /**
     * Actualizar una cuota
     */
    public function update(int $id, array $data): bool;

    /**
     * Eliminar una cuota
     */
    public function delete(int $id): bool;

    /**
     * Obtener cuotas por plan
     */
    public function getByPlan(int $planId): Collection;

    /**
     * Obtener cuotas pendientes por plan
     */
    public function getPendingByPlan(int $planId): Collection;

    /**
     * Obtener cuotas pagadas por plan
     */
    public function getPaidByPlan(int $planId): Collection;

    /**
     * Obtener cuotas vencidas por plan
     */
    public function getOverdueByPlan(int $planId): Collection;

    /**
     * Obtener la siguiente cuota pendiente
     */
    public function getNextPending(int $planId): ?Installment;

    /**
     * Marcar cuota como pagada
     */
    public function markAsPaid(int $id, array $paymentData): bool;

    /**
     * Marcar cuotas como vencidas
     */
    public function markAsOverdue(array $installmentIds): int;

    /**
     * Eliminar cuotas pendientes de un plan
     */
    public function deletePendingByPlan(int $planId): int;
}
