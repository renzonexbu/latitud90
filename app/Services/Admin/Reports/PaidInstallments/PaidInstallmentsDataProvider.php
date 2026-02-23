<?php

namespace App\Services\Admin\Reports\PaidInstallments;

use App\Models\Installment;
use App\Models\ProgramCourse;
use App\Models\ParticipantProgram;
use Illuminate\Support\Collection;

class PaidInstallmentsDataProvider
{
    /**
     * Obtener todos los registros de cuotas pagadas
     */
    public function getData(array $filters = []): Collection
    {
        $query = Installment::query()
            ->where('status', 'paid')
            ->whereNotNull('paid_at')
            ->with([
                'installmentPlan.participant',
                'installmentPlan.installments',
                'payment',
                'paymentOrderDetail',
            ]);

        // Filtro por programa
        if (!empty($filters['program_id'])) {
            $query->whereHas('installmentPlan', function ($q) use ($filters) {
                $q->where('program_id', $filters['program_id']);
            });
        }

        // Filtro por rango de fechas de pago
        if (!empty($filters['date_from'])) {
            $query->whereDate('paid_at', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->whereDate('paid_at', '<=', $filters['date_to']);
        }

        // Búsqueda por nombre del participante o código de inscripción
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->whereHas('installmentPlan.participant', function ($q2) use ($search) {
                    $q2->where('first_name', 'like', "%{$search}%")
                        ->orWhere('first_last_name', 'like', "%{$search}%")
                        ->orWhere('second_last_name', 'like', "%{$search}%");
                })->orWhereHas('installmentPlan', function ($q2) use ($search) {
                    $q2->whereExists(function ($sub) use ($search) {
                        $sub->from('participant_program')
                            ->whereColumn('participant_program.participant_id', 'installment_plans.participant_id')
                            ->whereColumn('participant_program.program_id', 'installment_plans.program_id')
                            ->where('participant_program.enrollment_code', 'like', "%{$search}%");
                    });
                });
            });
        }

        // Filtrar por estado del participante (usa is_active de participant_program)
        if (!empty($filters['participant_status'])) {
            $query->whereHas('installmentPlan', function ($q) use ($filters) {
                $q->whereExists(function ($subquery) use ($filters) {
                    $subquery->from('participant_program as pp_filter')
                        ->whereColumn('pp_filter.participant_id', 'installment_plans.participant_id')
                        ->whereColumn('pp_filter.program_id', 'installment_plans.program_id');

                    if ($filters['participant_status'] === 'active') {
                        $subquery->where(function($q2) {
                            $q2->where('pp_filter.is_active', true)
                               ->orWhereNull('pp_filter.is_active');
                        });
                    } elseif ($filters['participant_status'] === 'inactive') {
                        $subquery->where('pp_filter.is_active', false);
                    }
                });
            });
        }

        // Ordenar por fecha de pago descendente
        $query->orderBy('paid_at', 'desc');

        // Pre-cargar enrollment codes
        $enrollmentCodes = ParticipantProgram::pluck('enrollment_code', \DB::raw("CONCAT(participant_id, '-', program_id)"))
            ->toArray();

        return $query->get()->map(function ($installment) use ($enrollmentCodes) {
            $plan = $installment->installmentPlan;
            $participant = $plan?->participant;
            $payment = $installment->payment;
            $orderDetail = $installment->paymentOrderDetail;

            // Código de inscripción desde participant_program
            $ppKey = ($plan?->participant_id ?? '') . '-' . ($plan?->program_id ?? '');
            $enrollmentCode = $enrollmentCodes[$ppKey] ?? 'N/A';

            // Suscriptor (pagador) desde OrderDetail, fallback a participante
            $subscriberName = $orderDetail?->name ?? $participant?->full_name ?? 'N/A';

            // Boleta BSale
            $bsaleNumber = $payment?->bsale_number ?? null;

            // Total pagado = suma de todas las cuotas pagadas del plan
            $totalPaid = $plan?->installments
                ->where('status', 'paid')
                ->sum('amount') ?? 0;

            // Saldo = monto total del plan - total pagado
            $saldo = ($plan?->total_amount ?? 0) - $totalPaid;

            return [
                'id' => $installment->id,
                'paid_at' => $installment->paid_at?->format('d/m/Y H:i:s'),
                'subscriber_name' => $subscriberName,
                'enrollment_code' => $enrollmentCode,
                'amount' => $installment->amount,
                'bsale_number' => $bsaleNumber,
                'installment_label' => "Cuota {$installment->installment_number} de " . ($plan?->total_installments ?? '?'),
                'total_paid' => $totalPaid,
                'saldo' => $saldo,
                // Mantener para compatibilidad
                'participant_name' => $participant?->full_name ?? 'N/A',
            ];
        });
    }
}
