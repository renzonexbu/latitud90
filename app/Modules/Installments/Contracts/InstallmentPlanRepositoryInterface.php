<?php

namespace App\Modules\Installments\Contracts;

use App\Models\InstallmentPlan;
use Illuminate\Support\Collection;

interface InstallmentPlanRepositoryInterface
{
    /**
     * Encontrar un plan por ID
     */
    public function find(int $id): ?InstallmentPlan;

    /**
     * Crear un nuevo plan
     */
    public function create(array $data): InstallmentPlan;

    /**
     * Actualizar un plan
     */
    public function update(int $id, array $data): bool;

    /**
     * Eliminar un plan
     */
    public function delete(int $id): bool;

    /**
     * Buscar plan activo por programa y participante
     */
    public function findActiveByProgramAndParticipant(int $programId, int $participantId): ?InstallmentPlan;

    /**
     * Buscar plan por orden
     */
    public function findByOrder(int $orderId): ?InstallmentPlan;

    /**
     * Obtener planes activos
     */
    public function getActive(): Collection;

    /**
     * Obtener planes completados
     */
    public function getCompleted(): Collection;

    /**
     * Obtener planes por participante
     */
    public function getByParticipant(int $participantId): Collection;

    /**
     * Obtener planes por programa
     */
    public function getByProgram(int $programId): Collection;

    /**
     * Marcar plan como completado
     */
    public function markAsCompleted(int $id): bool;

    /**
     * Marcar plan como cancelado
     */
    public function markAsCancelled(int $id): bool;
}
