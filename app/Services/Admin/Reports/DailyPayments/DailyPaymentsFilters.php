<?php

namespace App\Services\Admin\Reports\DailyPayments;

use Illuminate\Database\Query\Builder;
use Carbon\Carbon;

class DailyPaymentsFilters
{
    public function applyFilters(Builder $query, array $filters): Builder
    {
        // FILTRO PRINCIPAL: Solo pagos completados/aprobados
        $query->where(function($q) {
            $q->whereIn('pay.status', ['approved', 'completed', 'paid', 'success'])
              ->where('od.is_paid', true)
              ->where('od.status', 'paid');
        });

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

        // Filtro por fecha desde (priorizando fecha de pago exitoso)
        if (!empty($filters['dateFrom'])) {
            $query->where(function($q) use ($filters) {
                $q->whereDate('od.paid_at', '>=', $filters['dateFrom'])
                  ->orWhere(function($subQ) use ($filters) {
                      $subQ->whereNull('od.paid_at')
                           ->whereDate('pay.transaction_date', '>=', $filters['dateFrom']);
                  });
            });
        }

        // Filtro por fecha hasta (priorizando fecha de pago exitoso)
        if (!empty($filters['dateTo'])) {
            $query->where(function($q) use ($filters) {
                $q->whereDate('od.paid_at', '<=', $filters['dateTo'])
                  ->orWhere(function($subQ) use ($filters) {
                      $subQ->whereNull('od.paid_at')
                           ->whereDate('pay.transaction_date', '<=', $filters['dateTo']);
                  });
            });
        }

        // Si no hay filtros de fecha, aplicar rango por defecto (últimos 5 días)
        if (empty($filters['dateFrom']) && empty($filters['dateTo'])) {
            $endDate = Carbon::now();
            $startDate = Carbon::now()->subDays(4);
            
            $query->where(function($q) use ($startDate, $endDate) {
                $q->where(function($subQ) use ($startDate) {
                    $subQ->whereDate('od.paid_at', '>=', $startDate->format('Y-m-d'))
                         ->orWhere(function($innerQ) use ($startDate) {
                             $innerQ->whereNull('od.paid_at')
                                    ->whereDate('pay.transaction_date', '>=', $startDate->format('Y-m-d'));
                         });
                })->where(function($subQ) use ($endDate) {
                    $subQ->whereDate('od.paid_at', '<=', $endDate->format('Y-m-d'))
                         ->orWhere(function($innerQ) use ($endDate) {
                             $innerQ->whereNull('od.paid_at')
                                    ->whereDate('pay.transaction_date', '<=', $endDate->format('Y-m-d'));
                         });
                });
            });
        }

        return $query;
    }
}
