<?php

namespace App\Services\Admin\Subscriptions\ChargeAttempts;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;

class ChargeAttemptsDataProvider
{
    /**
     * Obtener todas las cuotas con información de intentos de cobro (si existen)
     */
    public function getData(array $filters = []): Collection
    {
        $query = DB::table('installment_plans as ip')
            ->join('program_subscriptions as ps', function ($join) {
                $join->on('ip.participant_id', '=', 'ps.participant_id')
                     ->on('ip.program_id', '=', 'ps.program_id');
            })
            ->join('installments as i', 'i.installment_plan_id', '=', 'ip.id')
            ->join('participants as p', 'ps.participant_id', '=', 'p.id')
            ->join('program_courses as pc', 'ps.program_id', '=', 'pc.id')
            ->join('programs as prog', 'pc.program_id', '=', 'prog.id')
            ->leftJoin('virtualpos_plans as vp', 'ps.virtualpos_plan_id', '=', 'vp.virtualpos_plan_id')
            ->leftJoin('sales_executives as se', 'pc.sales_executive_id', '=', 'se.id')
            ->leftJoin('charge_attempts as ca', function($join) {
                $join->on('ca.installment_id', '=', 'i.id')
                     ->whereNotNull('ca.installment_id');
            })
            ->select([
                // IDs principales
                'i.id as installment_id',
                'ip.id as installment_plan_id',
                'ps.id as subscription_id',
                'ps.virtualpos_plan_id',
                'ca.id as charge_attempt_id',

                // Información del plan
                'vp.code as plan_code',
                'vp.name as plan_name',
                'vp.description as plan_description',

                // Información del participante
                'p.id as participant_id',
                DB::raw("CONCAT(p.first_name, ' ', COALESCE(p.second_name, ''), ' ', p.first_last_name, ' ', COALESCE(p.second_last_name, '')) as participant_name"),
                'p.document_number as participant_document',
                'p.document_type as document_type',

                // Información del programa
                'prog.id as program_id',
                'prog.name as program_name',
                'pc.id as program_course_id',
                'pc.departure_date as program_departure_date',

                // Información del ejecutivo
                'se.id as sales_executive_id',
                'se.name as sales_executive_name',

                // Información de la cuota
                'i.installment_number',
                'i.amount',
                'i.due_date as installment_due_date',
                'i.status as installment_status',
                'i.is_paid as installment_is_paid',
                'i.payment_source as installment_payment_source',
                'i.paid_at',
                'i.virtualpos_charge_id',

                // Información del intento de cobro (si existe)
                'ca.original_charge_id',
                'ca.attempt_number',
                'ca.description',
                'ca.type as attempt_type',
                'ca.status as charge_status',
                'ca.failure_reason',
                'ca.virtualpos_status',
                'ca.attempted_at',
                'ca.resolved_at',
                'ca.created_at as charge_created_at',

                // Información de la suscripción
                'ps.status as subscription_status',
                'ps.virtualpos_subscription_id',
            ])
            ->orderBy('i.installment_number', 'asc')
            ->orderBy('ca.attempted_at', 'desc');

        // Aplicar filtros
        if (!empty($filters['dateFrom'])) {
            $query->where('i.due_date', '>=', $filters['dateFrom']);
        }

        if (!empty($filters['dateTo'])) {
            $query->where('i.due_date', '<=', $filters['dateTo']);
        }

        if (!empty($filters['status'])) {
            $query->where('i.status', $filters['status']);
        }

        if (!empty($filters['programId'])) {
            $query->where('pc.id', $filters['programId']);
        }

        if (!empty($filters['salesExecutiveId'])) {
            $query->where('pc.sales_executive_id', $filters['salesExecutiveId']);
        }

        if (!empty($filters['subscriptionId'])) {
            $query->where('ps.id', $filters['subscriptionId']);
        }

        if (!empty($filters['subscriptionIds'])) {
            $query->whereIn('ps.id', $filters['subscriptionIds']);
        }

        if (!empty($filters['participantSearch'])) {
            $search = '%' . $filters['participantSearch'] . '%';
            $query->where(function ($q) use ($search) {
                $q->where('p.first_name', 'like', $search)
                  ->orWhere('p.first_last_name', 'like', $search)
                  ->orWhere('p.document_number', 'like', $search);
            });
        }

        if (!empty($filters['attemptType'])) {
            $query->where('ca.type', $filters['attemptType']);
        }

        return $query->get();
    }

    /**
     * Obtener estadísticas de cuotas
     */
    public function getStatistics(array $filters = []): array
    {
        $data = $this->getData($filters);

        $totalInstallments = $data->count();
        $paidInstallments = $data->where('installment_is_paid', true)->count();
        $pendingInstallments = $data->where('installment_status', 'pending')->count();
        $overdueInstallments = $data->where('installment_status', 'overdue')->count();
        $cancelledInstallments = $data->where('installment_status', 'cancelled')->count();

        $totalAmount = $data->sum('amount');
        $paidAmount = $data->where('installment_is_paid', true)->sum('amount');
        $pendingAmount = $data->where('installment_is_paid', false)->sum('amount');

        return [
            'total_installments' => $totalInstallments,
            'paid_installments' => $paidInstallments,
            'pending_installments' => $pendingInstallments,
            'overdue_installments' => $overdueInstallments,
            'cancelled_installments' => $cancelledInstallments,
            'payment_rate' => $totalInstallments > 0 ? round(($paidInstallments / $totalInstallments) * 100, 2) : 0,
            'total_amount' => $totalAmount,
            'paid_amount' => $paidAmount,
            'pending_amount' => $pendingAmount,
        ];
    }

    /**
     * Obtener cuotas agrupadas por participante
     */
    public function getByParticipant(int $participantId, int $programCourseId): Collection
    {
        return $this->getData([
            'programId' => $programCourseId,
        ])->where('participant_id', $participantId);
    }

    /**
     * Obtener cuotas de una suscripción específica
     */
    public function getBySubscription(int $subscriptionId): Collection
    {
        return $this->getData([
            'subscriptionId' => $subscriptionId,
        ]);
    }
}
