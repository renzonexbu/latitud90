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
        // Subconsultas para agregados
        $scholarshipSub = DB::table('participant_program_discounts')
            ->selectRaw('participant_program_id, SUM(amount) as scholarship_amount')
            ->where('discount_type', 'scholarship')
            ->groupBy('participant_program_id');

        $releasedSub = DB::table('participant_program_discounts')
            ->selectRaw('participant_program_id, COUNT(*) as released_count')
            ->where('discount_type', 'released')
            ->groupBy('participant_program_id');

        // Subconsulta: total de cuotas e info de plan por orden
        $installmentPlanSub = DB::table('installment_plans as ip')
            ->selectRaw('ip.order_id, MAX(ip.total_installments) as plan_total_installments')
            ->groupBy('ip.order_id');

        // Subconsulta: estadísticas de cuotas por participante y programa (desde installments)
        // Agrupar por order_id para obtener estadísticas específicas de cada orden
        $installmentsStatsSub = DB::table('installments as i')
            ->join('installment_plans as ip2', 'i.installment_plan_id', '=', 'ip2.id')
            ->selectRaw('
                ip2.participant_id,
                ip2.program_id,
                ip2.order_id,
                COUNT(*) as total_installments_ins,
                COUNT(CASE WHEN i.status = "paid" THEN 1 END) as paid_installments_ins
            ')
            ->groupBy('ip2.participant_id', 'ip2.program_id', 'ip2.order_id');

        // Subconsulta: estadísticas de cuotas por participante y programa (fallback orders_detail)
        // IMPORTANTE: Solo contar cuotas de órdenes no canceladas para evitar contar cuotas de órdenes fallidas
        $orderDetailsStatsSub = DB::table('orders_detail as od2')
            ->join('orders as o2', 'od2.order_id', '=', 'o2.id')
            ->selectRaw('
                o2.participant_id,
                o2.program_id,
                o2.id as order_id,
                COUNT(*) as total_installments_od,
                COUNT(CASE WHEN od2.is_paid = 1 THEN 1 END) as paid_installments_od
            ')
            ->whereNotIn('o2.status', ['cancelled', 'failed', 'pending'])
            ->groupBy('o2.participant_id', 'o2.program_id', 'o2.id');

        return DB::table('payments as pay')
            ->leftJoin('orders as o', 'pay.order_id', '=', 'o.id')
            ->leftJoin('participants as p', 'o.participant_id', '=', 'p.id')
            ->leftJoin('program_courses as pgc', 'pgc.id', '=', 'o.program_id')
            ->leftJoin('programs as pr', 'pr.id', '=', 'pgc.program_id')
            ->leftJoin('payment_gateways as pg', function($join) {
                $join->on('pg.id', '=', 'pay.payment_gateway_id')
                     ->orOn('pg.id', '=', DB::raw('(SELECT payment_gateway_id FROM orders_detail WHERE id = pay.order_detail_id)'));
            })
            ->leftJoin('orders_detail as od', 'pay.order_detail_id', '=', 'od.id')
            ->leftJoin('participant_program as pp', 'o.participant_program_id', '=', 'pp.id')
            ->leftJoinSub($scholarshipSub, 'sch', function ($join) {
                $join->on('sch.participant_program_id', '=', 'o.participant_program_id');
            })
            ->leftJoinSub($releasedSub, 'rel', function ($join) {
                $join->on('rel.participant_program_id', '=', 'o.participant_program_id');
            })
            ->leftJoinSub($installmentPlanSub, 'iplan', function ($join) {
                $join->on('iplan.order_id', '=', 'o.id');
            })
            ->leftJoinSub($installmentsStatsSub, 'ins_stats', function ($join) {
                // Usar order_id para obtener estadísticas específicas de esta orden
                $join->on('ins_stats.order_id', '=', 'o.id');
            })
            ->leftJoinSub($orderDetailsStatsSub, 'od_stats', function ($join) {
                // Usar order_id para obtener estadísticas específicas de esta orden
                $join->on('od_stats.order_id', '=', 'o.id');
            })
            ->select([
                // Identificación
                'pr.id as program_id',
                'pgc.id as program_course_id',
                'pr.name as program_name',
                'pgc.code as program_code',
                'p.document_number as participant_rut',
                'p.first_name',
                'p.second_name',
                'p.first_last_name',
                'p.second_last_name',
                'pay.authorization_code',
                
                // Información del pago
                'pay.amount as payment_amount',
                'pay.status as payment_status',
                'pay.buy_order',
                'pay.bsale_number',
                'pay.document_type',
                'pg.code as payment_method_code',
                'pg.name as payment_method_name',
                'pay.installments_number',
                DB::raw('COALESCE(od.paid_at, pay.transaction_date, pay.created_at) as payment_date'),
                
                // Datos del pagador (con fallback para pagos sin cuotas)
                DB::raw('COALESCE(od.name, CONCAT_WS(" ", p.first_name, p.second_name, p.first_last_name, p.second_last_name)) as payer_name'),
                DB::raw('COALESCE(od.email, p.email) as payer_email'),
                
                // Orden / Reserva
                'o.order_number',
                'o.total_installments',
                'o.payment_type',

                // Agregados
                DB::raw('COALESCE(ins_stats.paid_installments_ins, od_stats.paid_installments_od, 0) as paid_installments'),
                DB::raw('COALESCE(ins_stats.total_installments_ins, od_stats.total_installments_od, iplan.plan_total_installments, o.total_installments, 0) as total_installments'),
                DB::raw('COALESCE(sch.scholarship_amount, 0) as scholarship_amount'),
                DB::raw('CASE WHEN COALESCE(rel.released_count, 0) > 0 THEN 1 ELSE 0 END as released'),
                DB::raw('COALESCE(pp.individual_price, 0) as program_total_value'),
                DB::raw('CASE WHEN o.payment_type = "total" THEN "Pago Total" ELSE "Pago en Cuotas" END as payment_type_label'),

                // Campos adicionales para filtros
                'pay.created_at',
                'pay.transaction_date',
                'pay.order_id',
                'pay.order_detail_id',
            ])
            ->orderBy(DB::raw('COALESCE(od.paid_at, pay.transaction_date, pay.created_at)'), 'desc');
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
            DB::raw('MIN(COALESCE(od.paid_at, pay.transaction_date, pay.created_at)) as first_payment_date'),
            DB::raw('MAX(COALESCE(od.paid_at, pay.transaction_date, pay.created_at)) as last_payment_date'),
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
