<?php

namespace App\Services\Admin\Reports\ConsolidatedPayments;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ConsolidatedPaymentsDataProvider
{
    public function buildBaseQuery(): Builder
    {
        return DB::table('payments as pay')
            ->leftJoin('orders_detail as od', 'pay.order_detail_id', '=', 'od.id')
            ->leftJoin('orders as o', 'pay.order_id', '=', 'o.id')
            ->leftJoin('participants as p', 'o.participant_id', '=', 'p.id')
            ->leftJoin('programs as pr', 'o.program_id', '=', 'pr.id')
            ->leftJoin('payment_gateways as pg', 'od.payment_gateway_id', '=', 'pg.id')
            ->select([
                // Identificación
                'pr.id as program_id',
                'p.document_number as participant_rut',
                'pay.authorization_code',
                
                // Información del pago
                'pay.amount as payment_amount',
                'pay.status as payment_status',
                'pay.buy_order as invoice_number',
                'pg.code as payment_method_code',
                'pg.name as payment_method_name',
                'pay.installments_number',
                'od.paid_at as payment_date',
                
                // Datos del pagador
                'od.name as payer_name',
                'od.email as payer_email',
                
                // Campos adicionales para filtros
                'pay.created_at',
                'pay.transaction_date',
                'pay.order_id',
                'pay.order_detail_id',
            ])
            ->orderBy('od.paid_at', 'desc');
    }

    public function getConsolidatedPayments(Builder $query, int $page = 1): LengthAwarePaginator
    {
        return $query->paginate(10, ['*'], 'page', $page);
    }

    public function getAllConsolidatedPayments(Builder $query): Collection
    {
        return $query->get();
    }

    public function getSummary(Builder $query): array
    {
        $summary = $query->select([
            DB::raw('COUNT(DISTINCT pay.order_id) as total_orders'),
            DB::raw('COUNT(pay.id) as total_payments'),
            DB::raw('SUM(CASE WHEN pay.amount > 0 THEN pay.amount ELSE 0 END) as total_payments_amount'),
            DB::raw('SUM(CASE WHEN pay.amount < 0 THEN ABS(pay.amount) ELSE 0 END) as total_refunds_amount'),
            DB::raw('SUM(pay.amount) as net_amount'),
            DB::raw('MIN(od.paid_at) as first_payment_date'),
            DB::raw('MAX(od.paid_at) as last_payment_date'),
        ])->first();

        return [
            'total_orders' => $summary->total_orders ?? 0,
            'total_payments' => $summary->total_payments ?? 0,
            'total_payments_amount' => $summary->total_payments_amount ?? 0,
            'total_refunds_amount' => $summary->total_refunds_amount ?? 0,
            'net_amount' => $summary->net_amount ?? 0,
            'first_payment_date' => $summary->first_payment_date,
            'last_payment_date' => $summary->last_payment_date,
        ];
    }

    public function getPaymentMethods(): Collection
    {
        return DB::table('payment_gateways')
            ->select(['id', 'name', 'code'])
            ->where('active', true)
            ->orderBy('name')
            ->get();
    }

    public function getDefaultDateRange(): array
    {
        $endDate = Carbon::now();
        $startDate = Carbon::now()->startOfMonth(); // Por defecto, mes actual

        return [
            'dateFrom' => $startDate->format('Y-m-d'),
            'dateTo' => $endDate->format('Y-m-d'),
        ];
    }
}
