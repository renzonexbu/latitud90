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
            ->leftJoin('document as doc', 'p.document_type', '=', 'doc.id')
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
                'doc.name as participant_document_type',

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
                'o.status as order_status',

                // Cuota específica (puede ser null para participantes liberados)
                'i.installment_number',
                DB::raw('ROUND(i.amount) as installment_amount'),
                'i.due_date',
                'i.status as installment_status',
                'i.paid_at as installment_paid_at',
                
                // Estado calculado del participante
                DB::raw('CASE 
                    WHEN o.status = "paid" THEN "liberado"
                    WHEN i.status = "paid" THEN "pagado"
                    WHEN i.due_date < CURDATE() AND i.status != "paid" THEN "vencido"
                    WHEN i.due_date >= CURDATE() AND i.status != "paid" THEN "pendiente"
                    ELSE "sin_cuotas"
                END as participant_status')
            ])
            ->where('o.status', '!=', 'cancelled')
            // Incluir todas las cuotas (pagadas y no pagadas) para mostrar el estado real
            ->whereNotNull('i.id')
            // Ordenar por estado (liberados primero), luego por fecha de vencimiento y alumno
            ->orderByRaw('CASE WHEN o.status = "paid" THEN 0 ELSE 1 END')
            ->orderBy('i.due_date', 'asc')
            ->orderBy('p.first_last_name', 'asc');
    }

    public function getScheduleDetails(Builder $query): Collection
    {
        return $query->get();
    }

    /**
     * Obtener participantes liberados adicionales para el mes específico
     * Estos participantes aparecen en todos los meses del programa
     */
    public function getLiberatedParticipantsForMonth(array $filters): Collection
    {
        // Participantes liberados segun descuentos released 100%
        $query = DB::table('participant_program_discounts as ppd')
            ->join('participant_program as pp', 'ppd.participant_program_id', '=', 'pp.id')
            ->join('participants as p', 'pp.participant_id', '=', 'p.id')
            ->join('programs as prog', 'pp.program_id', '=', 'prog.id')
            ->leftJoin('sales_executives as se', 'se.id', '=', 'prog.sales_executive_id')
            ->leftJoin('document as doc', 'p.document_type', '=', 'doc.id')
            ->select([
                DB::raw('NULL as order_id'),
                'p.id as participant_id',
                'prog.id as program_id',
                'p.first_last_name',
                'p.second_last_name',
                'p.first_name',
                'p.second_name',
                'p.email',
                'p.document_number',
                'p.phone',
                DB::raw('TRIM(CONCAT(p.first_name, " ", COALESCE(p.second_name, ""), " ", p.first_last_name, " ", COALESCE(p.second_last_name, ""))) as participant_name'),
                'doc.name as participant_document_type',
                'prog.code as program_code',
                'prog.name as program_name',
                'prog.departure_date as program_departure_date',
                'prog.destination as program_destination',
                'prog.sales_executive_id',
                'se.name as sales_executive_name',
                'se.email as sales_executive_email',
                'se.phone as sales_executive_phone',
                DB::raw('NULL as order_number'),
                DB::raw('NULL as order_status'),
                DB::raw('NULL as installment_number'),
                DB::raw('NULL as installment_amount'),
                DB::raw('NULL as due_date'),
                DB::raw('NULL as installment_status'),
                DB::raw('NULL as installment_paid_at'),
                DB::raw('"liberado" as participant_status')
            ])
            ->where('ppd.discount_type', 'released')
            ->where(function ($q) {
                $q->whereNotNull('ppd.percent')->where('ppd.percent', '=', 100);
            })
            ->distinct();

        // Filtros
        if (!empty($filters['programId'])) {
            $query->where('prog.id', $filters['programId']);
        }
        if (!empty($filters['salesExecutiveId'])) {
            $query->where('se.id', $filters['salesExecutiveId']);
        }

        return $query->get();
    }
}
