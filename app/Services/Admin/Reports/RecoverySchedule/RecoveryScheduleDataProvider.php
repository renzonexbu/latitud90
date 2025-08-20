<?php

namespace App\Services\Admin\Reports\RecoverySchedule;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class RecoveryScheduleDataProvider
{
    public function buildBaseQuery(): Builder
    {
        // Usar installments como base para las cuotas individuales
        // Según las migraciones: installments -> installment_plans -> orders -> participants -> programs
        // MOSTRAR TODOS los installments sin filtrar por estado
        return DB::table('installments as i')
            ->join('installment_plans as ip', 'i.installment_plan_id', '=', 'ip.id')
            ->join('orders as o', 'ip.order_id', '=', 'o.id')
            ->join('participants as p', 'o.participant_id', '=', 'p.id')
            ->join('programs as prog', 'o.program_id', '=', 'prog.id')
            ->select([
                'i.id',
                'p.first_name',
                'p.last_name',
                'p.email',
                'p.document_number',
                'p.phone',
                'prog.name as program_name',
                'prog.departure_date as program_departure_date',
                'i.installment_number',
                'i.due_date', // Esta es la fecha máxima de pago
                DB::raw('ROUND(i.amount) as amount'),
                DB::raw('ROUND(i.amount) as base_amount'), // En installments no hay base_amount separado
                DB::raw('0 as discount_amount'), // En installments no hay discount_amount separado
                'i.status',
                DB::raw('CASE WHEN i.status = "paid" THEN 1 ELSE 0 END as is_paid'),
                'i.paid_at',
                'o.order_number',
                'o.total_amount as order_total_amount',
                'o.final_amount as order_final_amount',
                DB::raw('CASE 
                    WHEN i.due_date < CURDATE() THEN DATEDIFF(CURDATE(), i.due_date)
                    ELSE 0 
                END as days_overdue'),
                DB::raw('CASE 
                    WHEN i.due_date >= CURDATE() THEN DATEDIFF(i.due_date, CURDATE())
                    ELSE 0 
                END as days_until_due'),
                DB::raw('CASE WHEN i.status = "paid" THEN ROUND(i.amount) ELSE 0 END as paid_amount'),
                DB::raw('CASE WHEN i.status != "paid" THEN ROUND(i.amount) ELSE 0 END as pending_amount'),
                DB::raw('CASE 
                    WHEN i.due_date < CURDATE() AND i.status != "paid" THEN "overdue"
                    WHEN i.due_date >= CURDATE() AND i.status != "paid" THEN "pending"
                    WHEN i.status = "paid" THEN "paid"
                    ELSE "pending"
                END as calculated_status')
            ])
            ->where('o.status', '!=', 'cancelled') // Solo excluir órdenes canceladas
            ->orderBy('i.due_date', 'asc'); // Ordenar por fecha de vencimiento (fecha máxima de pago)
    }

    public function getPaymentSchedules(Builder $query, int $page = 1): LengthAwarePaginator
    {
        return $query->paginate(25, ['*'], 'page', $page); // Aumentado de 10 a 25 registros por página
    }

    public function getAllPaymentSchedules(Builder $query): Collection
    {
        return $query->get();
    }

    public function getSummary(Builder $query): array
    {
        $baseQuery = clone $query;
        
        $summary = $baseQuery->select([
            DB::raw('COUNT(*) as total'),
            DB::raw('SUM(CASE WHEN i.status = "pending" AND i.due_date >= CURDATE() THEN 1 ELSE 0 END) as pending'),
            DB::raw('SUM(CASE WHEN i.status = "overdue" AND i.due_date < CURDATE() THEN 1 ELSE 0 END) as overdue'),
            DB::raw('SUM(CASE WHEN i.status = "paid" THEN 1 ELSE 0 END) as paid'),
            DB::raw('SUM(CASE WHEN i.due_date >= CURDATE() AND i.status = "pending" THEN 1 ELSE 0 END) as upcoming'),
            DB::raw('ROUND(SUM(i.amount)) as total_amount'),
            DB::raw('ROUND(SUM(CASE WHEN i.status = "paid" THEN i.amount ELSE 0 END)) as total_paid'),
            DB::raw('ROUND(SUM(CASE WHEN i.status != "paid" THEN i.amount ELSE 0 END)) as total_pending'),
            DB::raw('ROUND(SUM(CASE WHEN i.status = "overdue" AND i.due_date < CURDATE() THEN i.amount ELSE 0 END)) as total_overdue')
        ])
        ->first();

        return [
            'total' => (int) ($summary->total ?? 0),
            'pending' => (int) ($summary->pending ?? 0),
            'overdue' => (int) ($summary->overdue ?? 0),
            'paid' => (int) ($summary->paid ?? 0),
            'upcoming' => (int) ($summary->upcoming ?? 0),
            'totalAmount' => (int) ($summary->total_amount ?? 0),
            'totalPaid' => (int) ($summary->total_paid ?? 0),
            'totalPending' => (int) ($summary->total_pending ?? 0),
            'totalOverdue' => (int) ($summary->total_overdue ?? 0),
        ];
    }

    public function getPrograms(): Collection
    {
        return DB::table('programs')
            ->select(['id', 'name', 'destination', 'departure_date'])
            ->where('active', true)
            ->orderBy('name')
            ->get();
    }
}
