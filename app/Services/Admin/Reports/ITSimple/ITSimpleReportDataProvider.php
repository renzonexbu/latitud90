<?php

namespace App\Services\Admin\Reports\ITSimple;

use App\Models\Order;
use App\Models\Payment;
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
        $query = ProgramCourse::where('active', true)->with(['course.participants']);

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
            // Participantes no cancelados (baja se maneja en calculateTotalToCollect)
            $participants = $programCourse->course?->participants ?? collect();
            $nonCancelledParticipants = $participants->filter(
                fn($p) => ($p->pivot->status ?? 'active') !== 'cancelled'
            );

            // 1. Total a Recaudar = Sum(Precio) - Sum(Liberado) con ajuste baja
            //    Misma lógica que Estado de Cuenta Parcial
            $totalToCollect = $this->calculateTotalToCollect($programCourse, $nonCancelledParticipants);

            // 2. Abono Pagadores = pagos normales + cuotas suscripción (sin aportes)
            $payerPayments = $this->calculatePayerPayments($programCourse);

            // 3. Aporte/Beca = pagos presential_aporte + descuentos tipo scholarship (columna informativa)
            $aporteBeca = $this->calculateAporteBeca($programCourse, $nonCancelledParticipants);

            // 4. Monto Liberado = descuentos tipo 'released' (columna informativa)
            $released = $this->calculateReleased($programCourse, $nonCancelledParticipants);

            // 5. Saldo = Total a Recaudar - (Abono + Aportes)
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
     * Calcula el Total a Recaudar usando la misma lógica del Estado de Cuenta Parcial:
     * Total = Sum(Precio) - Sum(Liberado), con ajuste para participantes de baja
     * Donde Precio = basePrice - descuentos simples (sin beca ni liberado)
     */
    private function calculateTotalToCollect(ProgramCourse $programCourse, $participants): float
    {
        $bajaParticipantIds = DB::table('participant_program')
            ->where('program_id', $programCourse->id)
            ->where('is_active', false)
            ->pluck('participant_id')
            ->toArray();

        $totalAmount = 0.0;

        foreach ($participants as $participant) {
            $priceData = ParticipantPriceHelper::calculateParticipantPrice($participant, $programCourse);
            $basePrice = $priceData['base_price'];

            $pp = DB::table('participant_program')
                ->where('participant_id', $participant->id)
                ->where('program_id', $programCourse->id)
                ->first();

            $simpleDiscounts = 0.0;
            $released = 0.0;

            if ($pp) {
                $discounts = DB::table('participant_program_discounts')
                    ->where('participant_program_id', $pp->id)
                    ->get();

                foreach ($discounts as $disc) {
                    $discAmount = 0.0;
                    if ($disc->percent && $disc->percent > 0) {
                        $discAmount += ($basePrice * $disc->percent) / 100;
                    }
                    if ($disc->amount && $disc->amount > 0) {
                        $discAmount += (float) $disc->amount;
                    }

                    if ($disc->discount_type === 'released') {
                        $released += $discAmount;
                    } elseif ($disc->discount_type !== 'scholarship') {
                        $simpleDiscounts += $discAmount;
                    }
                }
            }

            $precio = $basePrice - $simpleDiscounts;

            // Ajuste para participantes de baja
            if (in_array($participant->id, $bajaParticipantIds)) {
                $abono = $this->calculateParticipantAbono($participant->id, $programCourse->id);
                $precio = min($precio, $abono);
            }

            $totalAmount += $precio - $released;
        }

        return $totalAmount;
    }

    /**
     * Calcula el abono (pagos sin aportes) de un participante para un programa
     */
    private function calculateParticipantAbono(int $participantId, int $programCourseId): float
    {
        $orderIds = Order::where('participant_id', $participantId)
            ->where('program_id', $programCourseId)
            ->pluck('id')->all();

        if (empty($orderIds)) {
            return 0.0;
        }

        $normalPayments = (float) Payment::whereIn('order_id', $orderIds)
            ->whereIn('status', ['approved', 'completed'])
            ->where(function($q) {
                $q->whereNull('payment_source')
                  ->orWhere('payment_source', '!=', 'subscription');
            })
            ->where(function($q) {
                $q->whereNull('payment_option_id')
                  ->orWhereHas('paymentOption', function($sq) {
                      $sq->where('report_code', '!=', 'AP');
                  });
            })
            ->sum('amount');

        $subscriptionPayments = (float) DB::table('installments')
            ->join('installment_plans', 'installments.installment_plan_id', '=', 'installment_plans.id')
            ->where('installment_plans.participant_id', $participantId)
            ->where('installment_plans.program_id', $programCourseId)
            ->where('installment_plans.status', '!=', 'cancelled')
            ->where('installments.status', 'paid')
            ->sum('installments.amount');

        return $normalPayments + $subscriptionPayments;
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

        // Cuotas de suscripción pagadas - solo de planes activos
        $subscriptionPayments = (float) DB::table('installments')
            ->join('installment_plans', 'installments.installment_plan_id', '=', 'installment_plans.id')
            ->where('installment_plans.program_id', $programCourse->id)
            ->where('installment_plans.status', '!=', 'cancelled')
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
