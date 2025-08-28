<?php

namespace App\Services\Admin\Reports\PaymentSchedule;

use Illuminate\Database\Query\Builder;
use Carbon\Carbon;

class PaymentScheduleDetailFilters
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

        // Filtro por año-mes específico - solo cuotas que vencen en ese mes
        if (!empty($filters['yearMonth'])) {
            // Parsear año-mes (formato: 2026-11)
            $yearMonth = explode('-', $filters['yearMonth']);
            if (count($yearMonth) === 2) {
                $year = (int) $yearMonth[0];
                $month = (int) $yearMonth[1];
                
                $query->whereYear('i.due_date', $year)
                      ->whereMonth('i.due_date', $month);
            }
        }

        // Filtro por rango de fechas (si no hay yearMonth específico)
        if (empty($filters['yearMonth'])) {
            if (!empty($filters['dateFrom'])) {
                $query->where('i.due_date', '>=', $filters['dateFrom']);
            }

            if (!empty($filters['dateTo'])) {
                $query->where('i.due_date', '<=', $filters['dateTo']);
            }
        }

        return $query;
    }
}
