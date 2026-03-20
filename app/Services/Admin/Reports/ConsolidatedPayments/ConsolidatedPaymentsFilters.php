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
        // Para suscripciones, usar created_at en vez de transaction_date (que tiene la fecha de vencimiento, no de pago)
        if (!empty($filters['dateFrom'])) {
            $query->whereRaw('COALESCE(od.paid_at, CASE WHEN pay.payment_source = "subscription" THEN pay.created_at ELSE pay.transaction_date END, pay.created_at) >= ?', [$filters['dateFrom']]);
        }

        // Filtro por fecha hasta
        if (!empty($filters['dateTo'])) {
            $query->whereRaw('COALESCE(od.paid_at, CASE WHEN pay.payment_source = "subscription" THEN pay.created_at ELSE pay.transaction_date END, pay.created_at) <= ?', [$filters['dateTo'] . ' 23:59:59']);
        }

        // Si no hay filtros de fecha, aplicar rango por defecto (mes actual)
        if (empty($filters['dateFrom']) && empty($filters['dateTo'])) {
            $endDate = Carbon::now();
            $startDate = Carbon::now()->startOfMonth();

            $query->whereRaw('COALESCE(od.paid_at, CASE WHEN pay.payment_source = "subscription" THEN pay.created_at ELSE pay.transaction_date END, pay.created_at) >= ?', [$startDate->format('Y-m-d')])
                  ->whereRaw('COALESCE(od.paid_at, CASE WHEN pay.payment_source = "subscription" THEN pay.created_at ELSE pay.transaction_date END, pay.created_at) <= ?', [$endDate->format('Y-m-d') . ' 23:59:59']);
        }

        return $query;
    }
}
