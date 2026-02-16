<?php

namespace App\Services\Admin\Reports\ITSimple;

use App\Models\ProgramCourse;
use App\Helpers\ParticipantPriceHelper;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ITSimpleReportDataProvider
{
    /**
     * Obtener datos de recaudación global agrupados por programa.
     * Columnas: N° Programa, Total a Recaudar, Abono Pagadores, Aporte/Beca, Monto Liberado, Saldo
     */
    public function getData(array $filters = []): Collection
    {
        // Obtener program_courses con su curso y participantes
        $query = ProgramCourse::with(['course.participants']);

        // Filtro por programa específico
        if (!empty($filters['program_id'])) {
            $query->where('id', $filters['program_id']);
        }

        // Sin filtros → solo programas del año en curso (por departure_date)
        if (empty($filters['program_id']) && empty($filters['date_from']) && empty($filters['date_to'])) {
            $currentYear = now()->year;
            $query->whereYear('departure_date', $currentYear);
        }

        $programCourses = $query->orderBy('code', 'asc')->get();

        return $programCourses->map(function ($programCourse) use ($filters) {
            // Participantes activos (no cancelados)
            $participants = $programCourse->course?->participants ?? collect();
            $activeParticipants = $participants->filter(
                fn($p) => ($p->pivot->status ?? 'active') !== 'cancelled'
            );

            // 1. Total a Recaudar = suma de precios finales individuales
            $totalToCollect = 0.0;
            foreach ($activeParticipants as $participant) {
                $priceData = ParticipantPriceHelper::calculateParticipantPrice($participant, $programCourse);
                $totalToCollect += $priceData['final_price'] ?? 0;
            }

            // 2. Abono Pagadores = pagos normales + cuotas suscripción (sin aportes)
            $payerPayments = $this->calculatePayerPayments($programCourse, $filters);

            // 3. Aporte/Beca = pagos presential_aporte + descuentos tipo scholarship
            $aporteBeca = $this->calculateAporteBeca($programCourse, $activeParticipants, $filters);

            // 4. Monto Liberado = descuentos tipo 'released'
            $released = $this->calculateReleased($programCourse, $activeParticipants);

            // 5. Saldo = Total a Recaudar - Abono - Aporte/Beca - Liberado
            $balance = round($totalToCollect - $payerPayments - $aporteBeca - $released, 2);

            return [
                'program_id' => $programCourse->id,
                'program_code' => $programCourse->code ?? 'N/A',
                'total_to_collect' => round($totalToCollect, 2),
                'payer_payments' => round($payerPayments, 2),
                'aporte_beca' => round($aporteBeca, 2),
                'released' => round($released, 2),
                'balance' => max($balance, 0),
            ];
        })->filter(fn($item) => $item['total_to_collect'] > 0)
          ->sortByDesc('total_to_collect')
          ->values();
    }

    /**
     * Pagos normales (no aporte) + cuotas de suscripción pagadas
     */
    private function calculatePayerPayments(ProgramCourse $programCourse, array $filters): float
    {
        // Pagos normales (excluyendo aportes y suscripciones)
        $normalQuery = DB::table('payments')
            ->join('orders', 'payments.order_id', '=', 'orders.id')
            ->leftJoin('payment_options', 'payments.payment_option_id', '=', 'payment_options.id')
            ->where('orders.program_id', $programCourse->id)
            ->whereIn('payments.status', ['approved', 'completed'])
            ->where(function ($query) {
                $query->whereNull('payments.payment_source')
                      ->orWhere('payments.payment_source', '!=', 'subscription');
            })
            ->where(function ($query) {
                $query->whereNull('payment_options.code')
                      ->orWhere('payment_options.code', '!=', 'presential_aporte');
            });

        // Filtros de fecha sobre pagos
        if (!empty($filters['date_from'])) {
            $normalQuery->whereDate('payments.transaction_date', '>=', $filters['date_from']);
        }
        if (!empty($filters['date_to'])) {
            $normalQuery->whereDate('payments.transaction_date', '<=', $filters['date_to']);
        }

        $normalPayments = (float) $normalQuery->sum('payments.amount');

        // Cuotas de suscripción pagadas
        $subsQuery = DB::table('installments')
            ->join('installment_plans', 'installments.installment_plan_id', '=', 'installment_plans.id')
            ->where('installment_plans.program_id', $programCourse->id)
            ->where('installments.status', 'paid');

        if (!empty($filters['date_from'])) {
            $subsQuery->whereDate('installments.paid_at', '>=', $filters['date_from']);
        }
        if (!empty($filters['date_to'])) {
            $subsQuery->whereDate('installments.paid_at', '<=', $filters['date_to']);
        }

        $subscriptionPayments = (float) $subsQuery->sum('installments.amount');

        return $normalPayments + $subscriptionPayments;
    }

    /**
     * Aportes/Becas = pagos con código presential_aporte + descuentos tipo scholarship
     */
    private function calculateAporteBeca(ProgramCourse $programCourse, $activeParticipants, array $filters): float
    {
        // Pagos tipo aporte
        $aporteQuery = DB::table('payments')
            ->join('orders', 'payments.order_id', '=', 'orders.id')
            ->join('payment_options', 'payments.payment_option_id', '=', 'payment_options.id')
            ->where('orders.program_id', $programCourse->id)
            ->whereIn('payments.status', ['approved', 'completed'])
            ->where('payment_options.code', 'presential_aporte');

        if (!empty($filters['date_from'])) {
            $aporteQuery->whereDate('payments.transaction_date', '>=', $filters['date_from']);
        }
        if (!empty($filters['date_to'])) {
            $aporteQuery->whereDate('payments.transaction_date', '<=', $filters['date_to']);
        }

        $aportePayments = (float) $aporteQuery->sum('payments.amount');

        // Descuentos tipo scholarship de participant_program_discounts
        $scholarshipTotal = $this->sumDiscountsByType($programCourse, $activeParticipants, 'scholarship');

        return $aportePayments + $scholarshipTotal;
    }

    /**
     * Monto Liberado = descuentos tipo 'released' en participant_program_discounts
     */
    private function calculateReleased(ProgramCourse $programCourse, $activeParticipants): float
    {
        return $this->sumDiscountsByType($programCourse, $activeParticipants, 'released');
    }

    /**
     * Suma descuentos de participant_program_discounts por tipo (scholarship, released, etc.)
     */
    private function sumDiscountsByType(ProgramCourse $programCourse, $activeParticipants, string $discountType): float
    {
        if ($activeParticipants->isEmpty()) {
            return 0.0;
        }

        // Obtener los participant_program IDs para este program_course
        $ppIds = DB::table('participant_program')
            ->where('program_id', $programCourse->id)
            ->whereIn('participant_id', $activeParticipants->pluck('id'))
            ->pluck('id');

        if ($ppIds->isEmpty()) {
            return 0.0;
        }

        // Obtener descuentos del tipo solicitado
        $discounts = DB::table('participant_program_discounts')
            ->whereIn('participant_program_id', $ppIds)
            ->where('discount_type', $discountType)
            ->get();

        $total = 0.0;
        foreach ($discounts as $disc) {
            if ($disc->percent && $disc->percent > 0) {
                // Necesitamos el precio base del participante para calcular el porcentaje
                $pp = DB::table('participant_program')
                    ->where('id', $disc->participant_program_id)
                    ->first();
                if ($pp) {
                    $basePrice = (float) ($pp->individual_price ?? $programCourse->trip_price ?? 0);
                    $total += ($basePrice * $disc->percent) / 100;
                }
            }
            if ($disc->amount && $disc->amount > 0) {
                $total += (float) $disc->amount;
            }
        }

        return $total;
    }
}
