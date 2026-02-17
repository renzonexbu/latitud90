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
     *
     * Filtro de fechas: selecciona programas por departure_date (no filtra pagos).
     * Sin filtros: solo programas del año en curso.
     * Los montos siempre reflejan el total acumulado histórico.
     */
    public function getData(array $filters = []): Collection
    {
        $query = ProgramCourse::with(['course.participants']);

        // Filtro por programa específico
        if (!empty($filters['program_id'])) {
            $query->where('id', $filters['program_id']);
        }

        // Filtro por búsqueda de texto (predictivo)
        if (!empty($filters['program_search'])) {
            $search = $filters['program_search'];
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                  ->orWhere('name', 'like', "%{$search}%");
            });
        }

        // Filtro de fechas: selecciona programas por departure_date
        if (!empty($filters['date_from']) || !empty($filters['date_to'])) {
            if (!empty($filters['date_from'])) {
                $query->whereDate('departure_date', '>=', $filters['date_from']);
            }
            if (!empty($filters['date_to'])) {
                $query->whereDate('departure_date', '<=', $filters['date_to']);
            }
        } elseif (empty($filters['program_id']) && empty($filters['program_search'])) {
            // Sin filtros → solo programas del año en curso (por departure_date)
            $currentYear = now()->year;
            $query->whereYear('departure_date', $currentYear);
        }

        $programCourses = $query->orderBy('code', 'asc')->get();

        $result = $programCourses->map(function ($programCourse) {
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
            $payerPayments = $this->calculatePayerPayments($programCourse);

            // 3. Aporte/Beca = pagos presential_aporte + descuentos tipo scholarship (columna informativa)
            $aporteBeca = $this->calculateAporteBeca($programCourse, $activeParticipants);

            // 4. Monto Liberado = descuentos tipo 'released' (columna informativa)
            $released = $this->calculateReleased($programCourse, $activeParticipants);

            // 5. Saldo = Total a Recaudar - Abono Pagadores - Pagos aporte (solo pagos reales)
            // Nota: Total a Recaudar (final_price) ya tiene descontados scholarship y released,
            // por lo que NO se restan de nuevo. Solo se restan pagos efectivos.
            $aportePaymentsOnly = $this->calculateAportePaymentsOnly($programCourse);
            $balance = round($totalToCollect - $payerPayments - $aportePaymentsOnly, 2);

            return [
                'program_id' => $programCourse->id,
                'program_code' => $programCourse->code ?? 'N/A',
                'total_to_collect' => round($totalToCollect, 2),
                'payer_payments' => round($payerPayments, 2),
                'aporte_beca' => round($aporteBeca, 2),
                'released' => round($released, 2),
                'balance' => $balance,
            ];
        })->filter(fn($item) => $item['total_to_collect'] > 0);

        // Ordenamiento
        $sortBy = $filters['sort_by'] ?? 'program_code';
        $sortDir = ($filters['sort_dir'] ?? 'asc') === 'desc' ? 'desc' : 'asc';

        $sorted = $sortDir === 'desc'
            ? $result->sortByDesc($sortBy)
            : $result->sortBy($sortBy);

        return $sorted->values();
    }

    /**
     * Pagos normales (no aporte) + cuotas de suscripción pagadas.
     * Siempre muestra el total acumulado histórico (sin filtro de fecha).
     */
    private function calculatePayerPayments(ProgramCourse $programCourse): float
    {
        // Pagos normales (excluyendo aportes y suscripciones)
        $normalPayments = (float) DB::table('payments')
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
            })
            ->sum('payments.amount');

        // Cuotas de suscripción pagadas
        $subscriptionPayments = (float) DB::table('installments')
            ->join('installment_plans', 'installments.installment_plan_id', '=', 'installment_plans.id')
            ->where('installment_plans.program_id', $programCourse->id)
            ->where('installments.status', 'paid')
            ->sum('installments.amount');

        return $normalPayments + $subscriptionPayments;
    }

    /**
     * Solo pagos reales tipo aporte (presential_aporte). Sin descuentos.
     * Se usa para el cálculo del saldo (evita doble descuento con final_price).
     */
    private function calculateAportePaymentsOnly(ProgramCourse $programCourse): float
    {
        return (float) DB::table('payments')
            ->join('orders', 'payments.order_id', '=', 'orders.id')
            ->join('payment_options', 'payments.payment_option_id', '=', 'payment_options.id')
            ->where('orders.program_id', $programCourse->id)
            ->whereIn('payments.status', ['approved', 'completed'])
            ->where('payment_options.code', 'presential_aporte')
            ->sum('payments.amount');
    }

    /**
     * Aportes/Becas = pagos con código presential_aporte + descuentos tipo scholarship.
     * Siempre muestra el total acumulado histórico (sin filtro de fecha).
     */
    private function calculateAporteBeca(ProgramCourse $programCourse, $activeParticipants): float
    {
        // Pagos tipo aporte
        $aportePayments = (float) DB::table('payments')
            ->join('orders', 'payments.order_id', '=', 'orders.id')
            ->join('payment_options', 'payments.payment_option_id', '=', 'payment_options.id')
            ->where('orders.program_id', $programCourse->id)
            ->whereIn('payments.status', ['approved', 'completed'])
            ->where('payment_options.code', 'presential_aporte')
            ->sum('payments.amount');

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
