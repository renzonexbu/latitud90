<?php

namespace App\Services\Admin\Reports\PaymentSchedule;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class PaymentScheduleDetailDataProvider
{
    public function buildDetailQuery(): Builder
    {
        // Construir desde órdenes para incluir suscritos aunque no tengan cuotas/pagos
        return DB::table('orders as o')
            ->leftJoin('participants as p', 'o.participant_id', '=', 'p.id')
            ->leftJoin('programs as prog', 'o.program_id', '=', 'prog.id')
            ->leftJoin('sales_executives as se', 'se.id', '=', 'prog.sales_executive_id')
            ->leftJoin('installment_plans as ip', 'ip.order_id', '=', 'o.id')
            ->leftJoin('installments as i', 'i.installment_plan_id', '=', 'ip.id')
            ->select([
                // Identificadores base
                'o.id as order_id',
                'p.id as participant_id',
                'prog.id as program_id',

                // Participante
                'p.first_last_name',
                'p.second_last_name',
                'p.first_name',
                'p.second_name',
                'p.email',
                'p.document_number',
                'p.phone',
                DB::raw('TRIM(CONCAT(p.first_name, " ", COALESCE(p.second_name, ""), " ", p.first_last_name, " ", COALESCE(p.second_last_name, ""))) as participant_name'),

                // Programa
                'prog.code as program_code',
                'prog.name as program_name',
                'prog.departure_date as program_departure_date',
                'prog.destination as program_destination',

                // Ejecutivo
                'prog.sales_executive_id',
                'se.name as sales_executive_name',
                'se.email as sales_executive_email',
                'se.phone as sales_executive_phone',

                // Orden
                'o.order_number',

                // Cuota específica por vencer
                'i.installment_number',
                DB::raw('ROUND(i.amount) as installment_amount'),
                'i.due_date'
            ])
            ->where('o.status', '!=', 'cancelled')
            // Solo cuotas por vencer (no pagadas, fecha futura)
            ->where('i.status', '!=', 'paid')
            ->where('i.due_date', '>=', DB::raw('CURDATE()'))
            // Ordenar por fecha de vencimiento de la cuota y luego por alumno
            ->orderBy('i.due_date', 'asc')
            ->orderBy('p.first_last_name', 'asc');
    }

    public function getScheduleDetails(Builder $query): Collection
    {
        return $query->get();
    }
}
