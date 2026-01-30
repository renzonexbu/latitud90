<?php

namespace App\Services\Admin\Reports\ConsolidatedPayments;

use Illuminate\Database\Query\Builder;
use Carbon\Carbon;

class ConsolidatedPaymentsFilters
{
    public function applyFilters(Builder $query, array $filters): Builder
    {
        // Filtro por estado de pago
        // Por defecto muestra completados y rechazados (todos los pagos procesados)
        // El usuario puede filtrar solo por completados o solo por rechazados desde la UI
        if (!empty($filters['paymentStatus'])) {
            $status = $filters['paymentStatus'];
            if (is_array($status)) {
                $query->whereIn('pay.status', $status);
            } else {
                $query->where('pay.status', $status);
            }
        } else {
            // Por defecto: mostrar completados y rechazados
            $query->whereIn('pay.status', ['completed', 'approved', 'rejected']);
        }

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
                  ->orWhereDate('pay.transaction_date', '>=', $filters['dateFrom'])
                  ->orWhereDate('pay.created_at', '>=', $filters['dateFrom']);
            });
        }

        // Filtro por fecha hasta
        if (!empty($filters['dateTo'])) {
            $query->where(function($q) use ($filters) {
                $q->whereDate('od.paid_at', '<=', $filters['dateTo'])
                  ->orWhereDate('pay.transaction_date', '<=', $filters['dateTo'])
                  ->orWhereDate('pay.created_at', '<=', $filters['dateTo']);
            });
        }

        // Si no hay filtros de fecha, aplicar rango por defecto (mes actual)
        if (empty($filters['dateFrom']) && empty($filters['dateTo'])) {
            $endDate = Carbon::now();
            $startDate = Carbon::now()->startOfMonth();
            
            $query->where(function($q) use ($startDate, $endDate) {
                $q->whereDate('od.paid_at', '>=', $startDate->format('Y-m-d'))
                  ->orWhereDate('pay.transaction_date', '>=', $startDate->format('Y-m-d'))
                  ->orWhereDate('pay.created_at', '>=', $startDate->format('Y-m-d'));
            })->where(function($q) use ($startDate, $endDate) {
                $q->whereDate('od.paid_at', '<=', $endDate->format('Y-m-d'))
                  ->orWhereDate('pay.transaction_date', '<=', $endDate->format('Y-m-d'))
                  ->orWhereDate('pay.created_at', '<=', $endDate->format('Y-m-d'));
            });
        }

        return $query;
    }
}
