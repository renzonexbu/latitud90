<?php

namespace App\Services\Admin\Reports\ConsolidatedPayments;

use Illuminate\Database\Query\Builder;
use Carbon\Carbon;

class ConsolidatedPaymentsFilters
{
    public function applyFilters(Builder $query, array $filters): Builder
    {
        // Filtro por modalidad de pago (payment_method)
        if (!empty($filters['paymentMethodId'])) {
            $query->where('pg.id', $filters['paymentMethodId']);
        }

        // Filtro por fecha desde
        if (!empty($filters['dateFrom'])) {
            $query->where(function($q) use ($filters) {
                $q->whereDate('od.paid_at', '>=', $filters['dateFrom'])
                  ->orWhereDate('pay.created_at', '>=', $filters['dateFrom']);
            });
        }

        // Filtro por fecha hasta
        if (!empty($filters['dateTo'])) {
            $query->where(function($q) use ($filters) {
                $q->whereDate('od.paid_at', '<=', $filters['dateTo'])
                  ->orWhereDate('pay.created_at', '<=', $filters['dateTo']);
            });
        }

        // Si no hay filtros de fecha, aplicar rango por defecto (mes actual)
        if (empty($filters['dateFrom']) && empty($filters['dateTo'])) {
            $endDate = Carbon::now();
            $startDate = Carbon::now()->startOfMonth();
            
            $query->where(function($q) use ($startDate, $endDate) {
                $q->whereDate('od.paid_at', '>=', $startDate->format('Y-m-d'))
                  ->orWhereDate('pay.created_at', '>=', $startDate->format('Y-m-d'));
            })->where(function($q) use ($startDate, $endDate) {
                $q->whereDate('od.paid_at', '<=', $endDate->format('Y-m-d'))
                  ->orWhereDate('pay.created_at', '<=', $endDate->format('Y-m-d'));
            });
        }

        return $query;
    }
}
