<?php

namespace App\Services\Admin\Reports\DailyPayments;

use Illuminate\Database\Query\Builder;
use Carbon\Carbon;

class DailyPaymentsFilters
{
    public function applyFilters(Builder $query, array $filters): Builder
    {
        // FILTRO PRINCIPAL: Pagos completados/aprobados o rechazados
        // Si se especifica filtro de status, usar ese filtro
        if (!empty($filters['paymentStatus'])) {
            $status = $filters['paymentStatus'];
            if (is_array($status)) {
                $query->whereIn('pay.status', $status);
            } else {
                $query->where('pay.status', $status);
            }
            // Si incluye rejected, no filtrar por is_paid/status de order_detail
            if ((is_array($status) && in_array('rejected', $status)) || $status === 'rejected') {
                // Mostrar todos los registros sin filtro de is_paid
            } else {
                // Solo pagos exitosos
                $query->where('od.is_paid', true)
                      ->where('od.status', 'paid');
            }
        } else {
            // Por defecto: mostrar completados, aprobados Y rechazados
            $query->whereIn('pay.status', ['approved', 'completed', 'paid', 'success', 'rejected']);
            // No filtrar por is_paid para incluir rechazados
        }

        // Filtro por número de programa (program_course específico)
        if (!empty($filters['programId'])) {
            $query->where('pgc.id', $filters['programId']);
        }

        // Filtro por ejecutivo comercial
        if (!empty($filters['salesExecutiveId'])) {
            $query->where('pgc.sales_executive_id', $filters['salesExecutiveId']);
        }

        // Filtro por método de pago (gateway)
        if (!empty($filters['paymentMethod'])) {
            $method = $filters['paymentMethod'];
            switch ($method) {
                case 'transbank':
                case 'khipu':
                    $query->where('pg.code', $method);
                    break;
                case 'presencial':
                    $query->where(function ($q) {
                        $q->where('pg.code', 'presencial')
                          ->orWhere('po.mode', 'presential');
                    });
                    break;
                case 'virtualpos':
                    $query->where(function ($q) {
                        $q->where('po.mode', 'subscription')
                          ->orWhere('po.code', 'subscription_virtualpos');
                    });
                    break;
                case 'refund':
                    $query->where(function ($q) {
                        $q->where('pg.code', 'refund')
                          ->orWhere('po.gateway_code', 'refund')
                          ->orWhere('pay.amount', '<', 0)
                          ->orWhere('pay.document_type', 'VC');
                    });
                    break;
            }
        }

        // Filtro por participante (nombre o documento)
        if (!empty($filters['participantQuery'])) {
            $search = $filters['participantQuery'];
            $searchClean = preg_replace('/[.\-\s]/', '', $search);
            $searchLower = strtolower($search);

            $query->where(function ($q) use ($search, $searchClean, $searchLower) {
                $q->where('p.document_number', 'LIKE', "%{$search}%")
                  ->orWhere(\Illuminate\Support\Facades\DB::raw("REPLACE(REPLACE(p.document_number, '.', ''), '-', '')"), 'LIKE', "%{$searchClean}%")
                  ->orWhere(\Illuminate\Support\Facades\DB::raw('LOWER(p.first_name)'), 'LIKE', "%{$searchLower}%")
                  ->orWhere(\Illuminate\Support\Facades\DB::raw('LOWER(p.first_last_name)'), 'LIKE', "%{$searchLower}%")
                  ->orWhere(\Illuminate\Support\Facades\DB::raw('LOWER(p.second_last_name)'), 'LIKE', "%{$searchLower}%");
            });
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
