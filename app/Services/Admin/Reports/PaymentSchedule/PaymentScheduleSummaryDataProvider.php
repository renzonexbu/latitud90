<?php

namespace App\Services\Admin\Reports\PaymentSchedule;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class PaymentScheduleSummaryDataProvider
{
    public function buildExecutiveSummaryQuery(): Builder
    {
        // Usar la misma estructura base que RecoveryScheduleService pero adaptada para resumen
        return DB::table('installments as i')
            ->leftJoin('installment_plans as ip', 'i.installment_plan_id', '=', 'ip.id')
            ->leftJoin('orders as o', 'ip.order_id', '=', 'o.id')
            ->leftJoin('participants as p', 'o.participant_id', '=', 'p.id')
            ->leftJoin('program_courses as pgc', 'pgc.id', '=', 'o.program_id')
            ->leftJoin('programs as prog', 'prog.id', '=', 'pgc.program_id')
            ->leftJoin('sales_executives as se', 'se.id', '=', 'pgc.sales_executive_id')
            ->leftJoin('payments as pay', 'i.payment_id', '=', 'pay.id')
            ->leftJoin('payment_gateways as pg', 'pay.payment_gateway_id', '=', 'pg.id')
            ->select([
                // Datos del ejecutivo
                'se.id as sales_executive_id',
                'se.name as sales_executive_name',

                // Datos del programa (de program_courses + programs)
                'pgc.id as program_course_id',
                'pgc.code as program_code',
                'pgc.name as program_name',
                'prog.id as program_id',
                'prog.destination as program_destination',
                
                // Fecha para agrupación mensual
                DB::raw('YEAR(i.due_date) as `year`'),
                DB::raw('MONTH(i.due_date) as `month`'),
                DB::raw('DATE_FORMAT(i.due_date, "%Y-%m") as `year_month`'),
                
                // Datos de las cuotas
                'i.id as installment_id',
                'i.installment_number',
                'i.amount as installment_amount', 
                'i.due_date',
                'i.status as installment_status',
                'i.paid_at',
                
                // Datos del pago
                'pay.id as payment_id',
                'pay.amount as payment_amount',
                'pay.transaction_date',
                'pg.code as gateway_code',
                
                // Estado calculado de la cuota
                DB::raw('CASE 
                    WHEN i.status = "paid" THEN "paid"
                    WHEN i.due_date < CURDATE() AND i.status != "paid" THEN "overdue"
                    ELSE "pending"
                END as `payment_status`'),
                
                // Tipo de método de pago para TC vs PAT
                DB::raw('CASE 
                    WHEN i.status = "paid" AND pg.code = "transbank" THEN "TC"
                    WHEN i.status = "paid" AND pg.code IN ("khipu", "presencial", "refund") THEN "PAT"
                    WHEN i.status = "paid" AND pg.code IS NULL THEN "TC"
                    ELSE "UNPAID"
                END as `payment_method_type`')
            ])
            ->where('o.status', '!=', 'cancelled')
            ->orderBy('se.name')
            ->orderBy('pgc.name')
            ->orderBy('i.due_date', 'asc');
    }

    public function getExecutiveSummaryData(Builder $query): Collection
    {
        return $query->get();
    }

    public function getPrograms(): Collection
    {
        // Devuelve program_courses porque eso es lo que se usa en filtros
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

    public function getPaymentSchedules(array $filters): Collection
    {
        $query = $this->buildExecutiveSummaryQuery();

        // Aplicar filtros básicos
        if (!empty($filters['programId'])) {
            $query->where('pgc.id', $filters['programId']); // Filtrar por program_course
        }

        if (!empty($filters['salesExecutiveId'])) {
            $query->where('se.id', $filters['salesExecutiveId']);
        }

        if (!empty($filters['dateFrom'])) {
            $query->where('i.due_date', '>=', $filters['dateFrom']);
        }

        if (!empty($filters['dateTo'])) {
            $query->where('i.due_date', '<=', $filters['dateTo']);
        }

        return $query->get();
    }

    public function getGeneralSummary(array $filters): array
    {
        $query = $this->buildExecutiveSummaryQuery();

        // Aplicar filtros básicos
        if (!empty($filters['programId'])) {
            $query->where('pgc.id', $filters['programId']); // Filtrar por program_course
        }

        if (!empty($filters['salesExecutiveId'])) {
            $query->where('se.id', $filters['salesExecutiveId']);
        }

        if (!empty($filters['dateFrom'])) {
            $query->where('i.due_date', '>=', $filters['dateFrom']);
        }

        if (!empty($filters['dateTo'])) {
            $query->where('i.due_date', '<=', $filters['dateTo']);
        }

        $data = $query->get();

        return [
            'total_installments' => $data->count(),
            'total_amount' => $data->sum('installment_amount'),
            'paid_installments' => $data->where('installment_status', 'paid')->count(),
            'overdue_installments' => $data->where('payment_status', 'overdue')->count(),
            'pending_installments' => $data->where('payment_status', 'pending')->count(),
        ];
    }

    /**
     * Obtiene participantes que no han iniciado pagos por ecommerce
     */
    public function getParticipantsWithoutPayments(array $filters): Collection
    {
        $query = DB::table('participants as p')
            ->join('participant_program as pp', 'p.id', '=', 'pp.participant_id')
            // IMPORTANTE: pp.program_id apunta a program_courses, NO a programs
            ->join('program_courses as pgc', 'pp.program_id', '=', 'pgc.id')
            ->join('programs as prog', 'pgc.program_id', '=', 'prog.id')
            ->leftJoin('sales_executives as se', 'pgc.sales_executive_id', '=', 'se.id')
            ->leftJoin('emergency_contact as ec', 'p.id', '=', 'ec.participant_id')
            ->leftJoin('orders as o', function($join) {
                $join->on('p.id', '=', 'o.participant_id')
                     ->whereIn('o.status', ['pending', 'processing', 'paid']);
            })
            ->leftJoin('installment_plans as ip', 'o.id', '=', 'ip.order_id')
            ->leftJoin('participant_program_discounts as ppd', 'pp.id', '=', 'ppd.participant_program_id')
            ->select(
                'p.id as participant_id',
                'p.first_name',
                'p.first_last_name',
                'p.second_last_name',
                'p.document_number',
                'p.email as participant_email',
                'pp.created_at as incorporation_date',
                'pp.individual_price',
                'pgc.id as program_course_id',
                'pgc.name as program_name',
                'pgc.code as program_code',
                'prog.id as program_id',
                'se.name as sales_executive_name',
                'ec.name as emergency_contact_name',
                'ec.email as emergency_contact_email',
                'ec.phone as emergency_contact_phone',
                'ec.relationship as emergency_contact_relationship',
                DB::raw('COALESCE(ppd.amount, 0) as discount_amount'),
                DB::raw('COALESCE(ppd.percent, 0) as discount_percent'),
                DB::raw('CASE
                    WHEN ppd.percent IS NOT NULL THEN pp.individual_price - (pp.individual_price * ppd.percent / 100)
                    WHEN ppd.amount IS NOT NULL THEN pp.individual_price - ppd.amount
                    ELSE pp.individual_price
                END as final_amount'),
                DB::raw('COUNT(ip.id) as installment_plans_count')
            )
            ->where('p.is_active', true)
            ->whereIn('p.status', ['pending_payment', 'confirmed'])
            ->whereNotIn('ppd.discount_type', ['released']) // Excluir liberados
            ->orWhereNull('ppd.discount_type')
            ->groupBy(
                'p.id', 'p.first_name', 'p.first_last_name', 'p.second_last_name',
                'p.document_number', 'p.email', 'pp.created_at', 'pp.individual_price',
                'pgc.id', 'pgc.name', 'pgc.code', 'prog.id', 'se.name',
                'ec.name', 'ec.email', 'ec.phone', 'ec.relationship',
                'ppd.amount', 'ppd.percent'
            )
            ->having('installment_plans_count', '=', 0);

        // Aplicar filtros
        if (!empty($filters['program_id'])) {
            $query->where('pgc.id', $filters['program_id']); // Filtrar por program_course
        }

        if (!empty($filters['sales_executive_id'])) {
            $query->where('se.id', $filters['sales_executive_id']);
        }
        
        if (!empty($filters['search'])) {
            $search = '%' . $filters['search'] . '%';
            $query->where(function($q) use ($search) {
                $q->where('p.first_name', 'like', $search)
                  ->orWhere('p.first_last_name', 'like', $search)
                  ->orWhere('p.second_last_name', 'like', $search)
                  ->orWhere('p.document_number', 'like', $search)
                  ->orWhere(DB::raw("CONCAT(p.first_name, ' ', p.first_last_name, ' ', p.second_last_name)"), 'like', $search);
            });
        }

        return $query->get();
    }
}
