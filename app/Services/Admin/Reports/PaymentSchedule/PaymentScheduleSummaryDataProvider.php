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
            ->leftJoin('programs as prog', 'o.program_id', '=', 'prog.id')
            ->leftJoin('sales_executives as se', 'se.id', '=', 'prog.sales_executive_id')
            ->leftJoin('payments as pay', 'i.payment_id', '=', 'pay.id')
            ->leftJoin('payment_gateways as pg', 'pay.payment_gateway_id', '=', 'pg.id')
            ->select([
                // Datos del ejecutivo
                'se.id as sales_executive_id',
                'se.name as sales_executive_name',
                
                // Datos del programa  
                'prog.id as program_id',
                'prog.code as program_code',
                'prog.name as program_name',
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
            ->orderBy('prog.name') 
            ->orderBy('i.due_date', 'asc');
    }

    public function getExecutiveSummaryData(Builder $query): Collection
    {
        return $query->get();
    }

    public function getPrograms(): Collection
    {
        return DB::table('programs')
            ->select(['id', 'code', 'name', 'destination'])
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
            $query->where('prog.id', $filters['programId']);
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
            $query->where('prog.id', $filters['programId']);
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
}
