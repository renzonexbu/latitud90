<?php

namespace App\Services\Admin\Reports\PartialReport;

use Illuminate\Database\Query\Builder;

class PartialAccountFilters
{
    public function __construct(
        private PartialAccountDataProvider $dataProvider
    ) {}

    /**
     * Aplica los filtros a la consulta base
     */
    public function applyFilters(array $filters): Builder
    {
        $query = $this->dataProvider->buildBaseQuery();

        // Filtro por programa
        if (!empty($filters['programId'])) {
            $query->where('pr.id', $filters['programId']);
        }

        // Filtro por participante
        if (!empty($filters['participantId'])) {
            $query->where('p.id', $filters['participantId']);
        }

        // Filtro por fecha desde
        if (!empty($filters['dateFrom'])) {
            $query->where('pp.created_at', '>=', $filters['dateFrom']);
        }

        // Filtro por fecha hasta
        if (!empty($filters['dateTo'])) {
            $query->where('pp.created_at', '<=', $filters['dateTo'] . ' 23:59:59');
        }

        return $query;
    }

    /**
     * Valida los filtros recibidos
     */
    public function validateFilters(array $filters): array
    {
        $validated = [];

        // Validar programId
        if (isset($filters['programId']) && !empty($filters['programId'])) {
            $validated['programId'] = (int) $filters['programId'];
        }

        // Validar participantId
        if (isset($filters['participantId']) && !empty($filters['participantId'])) {
            $validated['participantId'] = (int) $filters['participantId'];
        }

        // Validar dateFrom
        if (isset($filters['dateFrom']) && !empty($filters['dateFrom'])) {
            $validated['dateFrom'] = $filters['dateFrom'];
        }

        // Validar dateTo
        if (isset($filters['dateTo']) && !empty($filters['dateTo'])) {
            $validated['dateTo'] = $filters['dateTo'];
        }

        // Validar página
        if (isset($filters['page']) && !empty($filters['page'])) {
            $validated['page'] = max(1, (int) $filters['page']);
        }

        return $validated;
    }
}
