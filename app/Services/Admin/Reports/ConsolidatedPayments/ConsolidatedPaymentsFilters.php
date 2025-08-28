<?php

namespace App\Services\Admin\Reports\ConsolidatedPayments;

use Illuminate\Database\Query\Builder;
use Carbon\Carbon;

class ConsolidatedPaymentsFilters
{
    public function applyFilters(Builder $query, array $filters): Builder
    {
        // Mostrar solo pagos/devoluciones efectivos (completados)
        $query->where('pay.status', 'completed');

        // Filtro por modalidad de pago (payment_method)
        if (!empty($filters['paymentMethodId'])) {
            $paymentMethodId = $filters['paymentMethodId'];
            $query->where(function($q) use ($paymentMethodId) {
                $q->where('pg.id', $paymentMethodId)
                  ->orWhere('pay.payment_gateway_id', $paymentMethodId);
            });
        }

        // Filtro por programa
        if (!empty($filters['programId'])) {
            $query->where('o.program_id', $filters['programId']);
        }

        // Filtro por alumno (nombre completo o documento)
        if (!empty($filters['participantQuery'])) {
            $search = trim($filters['participantQuery']);
            $query->where(function($q) use ($search) {
                $q->where('p.document_number', 'like', "%{$search}%")
                  ->orWhereRaw("CONCAT_WS(' ', p.first_name, p.second_name, p.first_last_name, p.second_last_name) LIKE ?", ["%{$search}%"]);
            });
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
