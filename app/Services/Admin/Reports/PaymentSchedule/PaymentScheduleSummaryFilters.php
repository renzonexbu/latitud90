<?php

namespace App\Services\Admin\Reports\PaymentSchedule;

use Illuminate\Database\Query\Builder;
use Carbon\Carbon;

class PaymentScheduleSummaryFilters
{
    public function applyFilters(Builder $query, array $filters): Builder
    {
        // Filtro por programa
        if (!empty($filters['programId'])) {
            $query->where('prog.id', $filters['programId']);
        }

        // Filtro por ejecutivo de ventas
        if (!empty($filters['salesExecutiveId'])) {
            $query->where('se.id', $filters['salesExecutiveId']);
        }

        // Filtro por rango de fechas (fecha de vencimiento de cuotas)
        if (!empty($filters['dateFrom'])) {
            $query->where('i.due_date', '>=', $filters['dateFrom']);
        }

        if (!empty($filters['dateTo'])) {
            $query->where('i.due_date', '<=', $filters['dateTo']);
        }

        // Si no hay filtros de fecha, mostrar un rango amplio para permitir reportes históricos y futuros
        if (empty($filters['dateFrom']) && empty($filters['dateTo'])) {
            $query->where('i.due_date', '>=', Carbon::now()->subYears(2)->format('Y-m-d'))
                  ->where('i.due_date', '<=', Carbon::now()->addYears(3)->format('Y-m-d'));
        }

        return $query;
    }
}
