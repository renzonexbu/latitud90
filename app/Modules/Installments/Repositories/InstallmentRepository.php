<?php

namespace App\Modules\Installments\Repositories;

use App\Models\Installment;
use App\Modules\Installments\Contracts\InstallmentRepositoryInterface;
use Illuminate\Support\Collection;
use Carbon\Carbon;

class InstallmentRepository implements InstallmentRepositoryInterface
{
    /**
     * Encontrar una cuota por ID
     */
    public function find(int $id): ?Installment
    {
        return Installment::find($id);
    }

    /**
     * Crear una nueva cuota
     */
    public function create(array $data): Installment
    {
        return Installment::create($data);
    }

    /**
     * Actualizar una cuota
     */
    public function update(int $id, array $data): bool
    {
        $installment = $this->find($id);

        if (!$installment) {
            return false;
        }

        return $installment->update($data);
    }

    /**
     * Eliminar una cuota
     */
    public function delete(int $id): bool
    {
        $installment = $this->find($id);

        if (!$installment) {
            return false;
        }

        return $installment->delete();
    }

    /**
     * Obtener cuotas por plan
     */
    public function getByPlan(int $planId): Collection
    {
        return Installment::where('installment_plan_id', $planId)
            ->orderBy('installment_number')
            ->get();
    }

    /**
     * Obtener cuotas pendientes por plan
     */
    public function getPendingByPlan(int $planId): Collection
    {
        return Installment::where('installment_plan_id', $planId)
            ->where('status', 'pending')
            ->orderBy('installment_number')
            ->get();
    }

    /**
     * Obtener cuotas pagadas por plan
     */
    public function getPaidByPlan(int $planId): Collection
    {
        return Installment::where('installment_plan_id', $planId)
            ->where('status', 'paid')
            ->orderBy('installment_number')
            ->get();
    }

    /**
     * Obtener cuotas vencidas por plan
     */
    public function getOverdueByPlan(int $planId): Collection
    {
        return Installment::where('installment_plan_id', $planId)
            ->where('status', 'overdue')
            ->orderBy('installment_number')
            ->get();
    }

    /**
     * Obtener la siguiente cuota pendiente
     */
    public function getNextPending(int $planId): ?Installment
    {
        return Installment::where('installment_plan_id', $planId)
            ->whereIn('status', ['pending', 'overdue'])
            ->orderBy('installment_number')
            ->first();
    }

    /**
     * Marcar cuota como pagada
     */
    public function markAsPaid(int $id, array $paymentData): bool
    {
        $installment = $this->find($id);

        if (!$installment) {
            return false;
        }

        return $installment->update([
            'status' => 'paid',
            'paid_at' => Carbon::now(),
            'payment_order_id' => $paymentData['order_id'] ?? null,
            'payment_order_detail_id' => $paymentData['order_detail_id'] ?? null,
            'payment_id' => $paymentData['payment_id'] ?? null,
        ]);
    }

    /**
     * Marcar cuotas como vencidas
     */
    public function markAsOverdue(array $installmentIds): int
    {
        return Installment::whereIn('id', $installmentIds)
            ->where('status', 'pending')
            ->update(['status' => 'overdue']);
    }

    /**
     * Eliminar cuotas pendientes de un plan
     */
    public function deletePendingByPlan(int $planId): int
    {
        return Installment::where('installment_plan_id', $planId)
            ->whereIn('status', ['pending', 'overdue'])
            ->delete();
    }

    /**
     * Obtener cuotas vencidas del sistema
     */
    public function getAllOverdue(): Collection
    {
        return Installment::where('status', 'pending')
            ->where('due_date', '<', Carbon::now())
            ->get();
    }

    /**
     * Contar cuotas por estado
     */
    public function countByStatus(int $planId, string $status): int
    {
        return Installment::where('installment_plan_id', $planId)
            ->where('status', $status)
            ->count();
    }
}
