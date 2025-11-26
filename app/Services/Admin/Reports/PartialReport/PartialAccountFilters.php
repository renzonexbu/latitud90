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

        // Filtro por programa (ahora filtra por program_courses.id)
        if (!empty($filters['programId'])) {
            $query->where('pgc.id', $filters['programId']);
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

        // Apply payment status filter if provided
        if (!empty($filters['paymentStatus'])) {
            // We need to filter by payment status at the query level
            // This requires calculating the payment status in the query
            $paymentStatus = $filters['paymentStatus'];
            
            if ($paymentStatus === 'paid') {
                // Paid: total paid >= individual price (use individual_price from participant_program)
                $query->havingRaw('COALESCE(SUM(pay.amount), 0) >= COALESCE(pp.individual_price, 0)');
            } elseif ($paymentStatus === 'pending') {
                // Pending: no payments made
                $query->havingRaw('COALESCE(SUM(pay.amount), 0) = 0');
            } elseif ($paymentStatus === 'partial') {
                // Partial: some payment made but not fully paid
                $query->havingRaw('COALESCE(SUM(pay.amount), 0) > 0 AND COALESCE(SUM(pay.amount), 0) < COALESCE(pp.individual_price, 0)');
            }
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
