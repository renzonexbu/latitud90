<?php

namespace App\Modules\Installments\Repositories;

use App\Models\InstallmentPlan;
use App\Modules\Installments\Contracts\InstallmentPlanRepositoryInterface;
use Illuminate\Support\Collection;

class InstallmentPlanRepository implements InstallmentPlanRepositoryInterface
{
    /**
     * Encontrar un plan por ID
     */
    public function find(int $id): ?InstallmentPlan
    {
        return InstallmentPlan::find($id);
    }

    /**
     * Crear un nuevo plan
     */
    public function create(array $data): InstallmentPlan
    {
        return InstallmentPlan::create($data);
    }

    /**
     * Actualizar un plan
     */
    public function update(int $id, array $data): bool
    {
        $plan = $this->find($id);

        if (!$plan) {
            return false;
        }

        return $plan->update($data);
    }

    /**
     * Eliminar un plan
     */
    public function delete(int $id): bool
    {
        $plan = $this->find($id);

        if (!$plan) {
            return false;
        }

        return $plan->delete();
    }

    /**
     * Buscar plan activo por programa y participante
     */
    public function findActiveByProgramAndParticipant(int $programId, int $participantId): ?InstallmentPlan
    {
        return InstallmentPlan::where('program_id', $programId)
            ->where('participant_id', $participantId)
            ->where('status', 'active')
            ->first();
    }

    /**
     * Buscar plan por orden
     */
    public function findByOrder(int $orderId): ?InstallmentPlan
    {
        return InstallmentPlan::where('order_id', $orderId)->first();
    }

    /**
     * Obtener planes activos
     */
    public function getActive(): Collection
    {
        return InstallmentPlan::where('status', 'active')
            ->with(['installments', 'participant', 'program'])
            ->get();
    }

    /**
     * Obtener planes completados
     */
    public function getCompleted(): Collection
    {
        return InstallmentPlan::where('status', 'completed')
            ->with(['installments', 'participant', 'program'])
            ->get();
    }

    /**
     * Obtener planes por participante
     */
    public function getByParticipant(int $participantId): Collection
    {
        return InstallmentPlan::where('participant_id', $participantId)
            ->with(['installments', 'program'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Obtener planes por programa
     */
    public function getByProgram(int $programId): Collection
    {
        return InstallmentPlan::where('program_id', $programId)
            ->with(['installments', 'participant'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Marcar plan como completado
     */
    public function markAsCompleted(int $id): bool
    {
        return $this->update($id, ['status' => 'completed']);
    }

    /**
     * Marcar plan como cancelado
     */
    public function markAsCancelled(int $id): bool
    {
        return $this->update($id, ['status' => 'cancelled']);
    }

    /**
     * Verificar si existe plan activo
     */
    public function hasActivePlan(int $programId, int $participantId): bool
    {
        return InstallmentPlan::where('program_id', $programId)
            ->where('participant_id', $participantId)
            ->where('status', 'active')
            ->exists();
    }

    /**
     * Obtener plan con relaciones cargadas
     */
    public function findWithRelations(int $id): ?InstallmentPlan
    {
        return InstallmentPlan::with([
            'installments',
            'participant',
            'program',
            'order'
        ])->find($id);
    }
}
