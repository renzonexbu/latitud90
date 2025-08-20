<?php

namespace App\Services\Admin\Reports\DailyPayments;

use Illuminate\Database\Query\Builder;
use Carbon\Carbon;

class DailyPaymentsFilters
{
    public function applyFilters(Builder $query, array $filters): Builder
    {
        // Filtro por número de programa
        if (!empty($filters['programId'])) {
            $query->where('pr.id', $filters['programId']);
        }

        // Filtro por ejecutivo comercial
        if (!empty($filters['salesExecutiveId'])) {
            $query->where('pr.sales_executive_id', $filters['salesExecutiveId']);
        }

        // Filtro por forma de financiamiento (payment_type)
        if (!empty($filters['financingType'])) {
            $query->where('o.payment_type', $filters['financingType']);
        }

        // Filtro por método de pago (payment_option)
        if (!empty($filters['paymentMethodId'])) {
            $query->where('po.id', $filters['paymentMethodId']);
        }

        // Filtro por fecha desde
        if (!empty($filters['dateFrom'])) {
            $query->where(function($q) use ($filters) {
                $q->whereDate('pay.transaction_date', '>=', $filters['dateFrom'])
                  ->orWhereDate('pay.created_at', '>=', $filters['dateFrom']);
            });
        }

        // Filtro por fecha hasta
        if (!empty($filters['dateTo'])) {
            $query->where(function($q) use ($filters) {
                $q->whereDate('pay.transaction_date', '<=', $filters['dateTo'])
                  ->orWhereDate('pay.created_at', '<=', $filters['dateTo']);
            });
        }

        // Si no hay filtros de fecha, aplicar rango por defecto (últimos 5 días)
        if (empty($filters['dateFrom']) && empty($filters['dateTo'])) {
            $endDate = Carbon::now();
            $startDate = Carbon::now()->subDays(4);
            
            $query->where(function($q) use ($startDate, $endDate) {
                $q->whereDate('pay.transaction_date', '>=', $startDate->format('Y-m-d'))
                  ->orWhereDate('pay.created_at', '>=', $startDate->format('Y-m-d'));
            })->where(function($q) use ($startDate, $endDate) {
                $q->whereDate('pay.transaction_date', '<=', $endDate->format('Y-m-d'))
                  ->orWhereDate('pay.created_at', '<=', $endDate->format('Y-m-d'));
            });
        }

        return $query;
    }
}
