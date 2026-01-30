<?php

namespace App\Services\Admin\Reports\DailyPayments;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DailyPaymentsDataProvider
{
    public function buildBaseQuery(): Builder
    {
        return DB::table('payments as pay')
            ->leftJoin('orders_detail as od', 'pay.order_detail_id', '=', 'od.id')
            ->leftJoin('orders as o', 'pay.order_id', '=', 'o.id')
            ->leftJoin('participants as p', 'o.participant_id', '=', 'p.id')
            ->leftJoin('program_courses as pgc', 'pgc.id', '=', 'o.program_id')
            ->leftJoin('programs as pr', 'pr.id', '=', 'pgc.program_id')
            ->leftJoin('sales_executives as se', 'pgc.sales_executive_id', '=', 'se.id')
            ->leftJoin('payment_gateways as pg', 'od.payment_gateway_id', '=', 'pg.id')
            ->leftJoin('payment_options as po', 'pay.payment_option_id', '=', 'po.id')
            ->leftJoin('document as doc', 'p.document_type', '=', 'doc.id')
            ->select([
                'pay.id as payment_id',
                'pay.amount as payment_amount',
                'pay.transaction_date as payment_date',
                'pay.status as payment_status',
                'pay.authorization_code',
                'pay.response_code',
                'pay.card_number',
                'pay.card_type',
                'pay.installments_number',
                'pay.order_id',
                'pay.order_detail_id',
                'pay.document_type',
                'pay.payment_code', // ← NUEVO CAMPO
                'pay.bsale_number', // ← NUEVO CAMPO
                'pay.buy_order', // ← NUEVO CAMPO
                // Datos de la orden
                'o.order_number',
                'o.total_amount',
                'o.final_amount',
                'o.total_installments',
                'o.payment_type',
                'o.created_at as order_date',
                'o.participant_id',
                'o.program_id',
                // Datos del participante
                'p.id', 'p.first_last_name', 'p.second_last_name', 'p.first_name', 'p.second_name', 'p.email', 'p.document_number', 'p.phone',
                'doc.name as participant_document_type',
                // Datos del programa (template)
                'pr.id as template_program_id',
                'pr.destination',
                // Datos del programa específico (program_course)
                'pgc.id as program_course_id',
                'pgc.code as program_code',
                'pgc.name as program_name',
                'pgc.departure_date as departure_date',
                'pgc.sales_executive_id',
                // Datos del ejecutivo de ventas
                'se.name as sales_executive_name',
                'se.email as sales_executive_email',
                'se.phone as sales_executive_phone',
                // Datos del gateway y método de pago (desde orders_detail)
                'od.payment_gateway_id',
                'pg.name as payment_gateway_name',
                'pg.code as payment_gateway_code',
                'po.label as payment_option_name',
                'po.code as payment_option_code',
                'po.report_code as payment_form_code',
                // Datos del pagador (desde orders_detail)
                'od.name as payer_name',
                'od.email as payer_email',
                'od.phone as payer_phone',
                'od.document_number as payer_document',
                // Datos de la cuota
                'od.installment_number',
                'od.amount as installment_amount',
                'od.due_date as installment_due_date',
                'od.status as installment_status',
                'od.paid_at as installment_paid_at',
            ])
            ->orderByRaw('COALESCE(od.paid_at, pay.transaction_date) DESC');
    }

    public function getDailyPayments(Builder $query, int $page = 1): LengthAwarePaginator
    {
        return $query->paginate(10, ['*'], 'page', $page);
    }

    public function getAllDailyPayments(Builder $query): Collection
    {
        return $query->get();
    }

    public function getSummary(Builder $query): array
    {
        // Clonar el query y reemplazar el SELECT con agregaciones
        // Esto evita conflictos con el SELECT original que tiene muchas columnas individuales
        $summaryQuery = clone $query;

        // Remover el SELECT anterior y ORDER BY para las agregaciones
        $summaryQuery->orders = null;
        $summaryQuery->columns = null;

        // Hacer el SELECT con agregaciones
        $summary = $summaryQuery->selectRaw('
            COUNT(DISTINCT pay.order_id) as total_orders,
            COUNT(pay.id) as total_payments,
            SUM(pay.amount) as total_amount_paid,
            AVG(pay.amount) as average_payment,
            MIN(pay.transaction_date) as first_payment_date,
            MAX(pay.transaction_date) as last_payment_date
        ')->first();

        // Calcular pagos de hoy (solo pagos completados)
        $paymentsToday = DB::table('payments as pay')
            ->leftJoin('orders_detail as od', 'pay.order_detail_id', '=', 'od.id')
            ->leftJoin('orders as o', 'pay.order_id', '=', 'o.id')
            ->leftJoin('participants as p', 'o.participant_id', '=', 'p.id')
            ->leftJoin('program_courses as pgc', 'pgc.id', '=', 'o.program_id')
            ->leftJoin('programs as pr', 'pr.id', '=', 'pgc.program_id')
            ->leftJoin('sales_executives as se', 'pgc.sales_executive_id', '=', 'se.id')
            ->leftJoin('payment_gateways as pg', 'od.payment_gateway_id', '=', 'pg.id')
            ->leftJoin('payment_options as po', 'pay.payment_option_id', '=', 'po.id')
            ->leftJoin('document as doc', 'p.document_type', '=', 'doc.id')
            ->whereIn('pay.status', ['approved', 'completed', 'paid', 'success', 'rejected'])
            ->where(function($q) {
                $q->whereDate('pay.transaction_date', now()->toDateString())
                  ->orWhereDate('pay.created_at', now()->toDateString())
                  ->orWhereDate('od.paid_at', now()->toDateString());
            })
            ->count();

        // Calcular total de registros (pagos completados y rechazados, sin filtros de fecha)
        $totalRecords = DB::table('payments as pay')
            ->leftJoin('orders_detail as od', 'pay.order_detail_id', '=', 'od.id')
            ->leftJoin('orders as o', 'pay.order_id', '=', 'o.id')
            ->leftJoin('participants as p', 'o.participant_id', '=', 'p.id')
            ->leftJoin('program_courses as pgc', 'pgc.id', '=', 'o.program_id')
            ->leftJoin('programs as pr', 'pr.id', '=', 'pgc.program_id')
            ->leftJoin('sales_executives as se', 'pgc.sales_executive_id', '=', 'se.id')
            ->leftJoin('payment_gateways as pg', 'od.payment_gateway_id', '=', 'pg.id')
            ->leftJoin('payment_options as po', 'pay.payment_option_id', '=', 'po.id')
            ->leftJoin('document as doc', 'p.document_type', '=', 'doc.id')
            ->whereIn('pay.status', ['approved', 'completed', 'paid', 'success', 'rejected'])
            ->count();

        return [
            'total_orders' => $summary->total_orders ?? 0,
            'total_payments' => $summary->total_payments ?? 0,
            'total_amount_paid' => $summary->total_amount_paid ?? 0,
            'average_payment' => $summary->average_payment ?? 0,
            'first_payment_date' => $summary->first_payment_date,
            'last_payment_date' => $summary->last_payment_date,
            'payments_today' => $paymentsToday,
            'total_records' => $totalRecords,
        ];
    }

    public function getPrograms(): Collection
    {
        return DB::table('program_courses')
            ->select(['id', 'code', 'name'])
            ->where('active', true)
            ->orderBy('name')
            ->get();
    }

    public function getSalesExecutives(): Collection
    {
        return DB::table('sales_executives')
            ->select(['id', 'name', 'email'])
            ->orderBy('name')
            ->get();
    }

    public function getFinancingTypes(): array
    {
        return [
            'total' => 'Pago Único',
            'monthly' => 'Pago en Cuotas',
        ];
    }

    public function getPaymentMethods(): Collection
    {
        return DB::table('payment_options')
            ->select(['id', 'label', 'code'])
            ->where('active', true)
            ->orderBy('label')
            ->get();
    }

    public function getDefaultDateRange(): array
    {
        $endDate = Carbon::now();
        $startDate = Carbon::now()->subDays(4); // Últimos 5 días incluyendo el actual

        return [
            'dateFrom' => $startDate->format('Y-m-d'),
            'dateTo' => $endDate->format('Y-m-d'),
        ];
    }
}
