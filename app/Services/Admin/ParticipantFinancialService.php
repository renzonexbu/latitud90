<?php

namespace App\Services\Admin;

use App\Helpers\ParticipantPriceHelper;
use App\Models\Order;
use App\Models\Participant;
use App\Models\ParticipantProgram;
use App\Models\ProgramCourse;
use Illuminate\Support\Facades\DB;

/**
 * Servicio centralizado para cálculos financieros de participantes y programas.
 *
 * FUENTE ÚNICA DE VERDAD para precios, pagos, saldos y agregaciones por programa.
 * Todos los reportes deben usar este servicio en vez de calcular por su cuenta.
 *
 * Categorización de pagos (basado en payment_options.report_code):
 *   - Abono (pagador): VP, KP, TC, TE, DP, WP, VPI, PAT (excluye AP, CT, NC, RA)
 *   - Aporte:          AP (incluye refund_aporte_credit_note como negativo)
 *   - Crédito Temp:    CT
 *   - Nota Crédito:    NC (siempre negativo)
 *   - Reverso Admin:   RA (siempre negativo)
 *
 * Precio:
 *   net_amount = base_price + adjustments - regular_discounts - released_discounts
 *
 * Saldo:
 *   pending_amount = max(0, net_amount - total_paid)
 *   donde total_paid es la suma neta de TODOS los pagos approved/completed
 */
class ParticipantFinancialService
{
    /** Códigos de pago que cuentan como abono del pagador */
    private const ABONO_CODES = ['VP', 'VPI', 'KP', 'WP', 'TC', 'TE', 'DP', 'PAT'];

    /**
     * Calcula todos los datos financieros de un participante en un programa.
     */
    public static function calculate(int $participantId, int $programCourseId): array
    {
        $participant = Participant::find($participantId);
        $programCourse = ProgramCourse::find($programCourseId);

        if (!$participant || !$programCourse) {
            return self::emptyResult();
        }

        // 1. Precio y descuentos
        $priceData = ParticipantPriceHelper::calculateParticipantPrice($participant, $programCourse);
        $basePrice = (float) ($priceData['base_price'] ?? 0);
        $adjustments = (float) ($priceData['adjustments'] ?? 0);
        $regularDiscounts = (float) ($priceData['regular_discounts'] ?? 0);
        $releasedDiscounts = (float) ($priceData['released_discounts'] ?? 0);
        $netAmount = (float) ($priceData['final_price'] ?? 0);

        // 2. Estado de baja
        $pp = ParticipantProgram::where('participant_id', $participantId)
            ->where('program_id', $programCourseId)
            ->first();
        $isDeBaja = $pp && !$pp->is_active;

        // 3. Pagos categorizados
        $payments = self::aggregatePaymentsForParticipant($participantId, $programCourseId);

        // 4. Total pagado neto (suma de todas las categorías con signos)
        $totalPaid = $payments['abono']
            + $payments['aporte']
            + $payments['credito_temporal']
            + $payments['nota_credito']
            + $payments['reverso_admin'];

        // 5. Si está de baja, ajustar precio al monto pagado (saldo = 0)
        $displayNetAmount = $netAmount;
        if ($isDeBaja) {
            $displayNetAmount = min($netAmount, max($totalPaid, 0));
        }

        // 6. Saldo pendiente
        $pendingAmount = max($displayNetAmount - $totalPaid, 0);

        // 7. Estado de pago
        $status = 'pending';
        if ($displayNetAmount > 0 && $pendingAmount <= 0) {
            $status = 'paid';
        } elseif ($totalPaid > 0) {
            $status = 'partial';
        }

        // 8. Porcentaje de avance
        $progressPercentage = $displayNetAmount > 0
            ? round(($totalPaid / $displayNetAmount) * 100, 2)
            : 0;

        return [
            // Precio
            'base_price' => $basePrice,
            'adjustments' => $adjustments,
            'regular_discounts' => $regularDiscounts,
            'released_discounts' => $releasedDiscounts,
            'discounts' => $regularDiscounts + $releasedDiscounts,
            'net_amount' => $displayNetAmount,
            'original_net_amount' => $netAmount,

            // Pagos categorizados
            'abono' => $payments['abono'],
            'aporte' => $payments['aporte'],
            'credito_temporal' => $payments['credito_temporal'],
            'nota_credito' => $payments['nota_credito'],
            'reverso_admin' => $payments['reverso_admin'],
            'total_paid' => $totalPaid,

            // Saldo
            'pending_amount' => $pendingAmount,
            'progress_percentage' => $progressPercentage,
            'status' => $status,
            'is_de_baja' => $isDeBaja,
        ];
    }

    /**
     * Agrega los pagos de un participante en un programa por categoría.
     * Retorna montos netos (incluyendo signos negativos de NC/RA).
     */
    private static function aggregatePaymentsForParticipant(int $participantId, int $programCourseId): array
    {
        $orderIds = Order::where('participant_id', $participantId)
            ->where('program_id', $programCourseId)
            ->pluck('id')
            ->all();

        if (empty($orderIds)) {
            return self::emptyPaymentBreakdown();
        }

        return self::aggregatePaymentsByOrderIds($orderIds);
    }

    /**
     * Agrega pagos por categoría dado un conjunto de order IDs.
     * Una sola query agrupada para eficiencia.
     */
    private static function aggregatePaymentsByOrderIds(array $orderIds): array
    {
        $rows = DB::table('payments')
            ->leftJoin('payment_options', 'payments.payment_option_id', '=', 'payment_options.id')
            ->whereIn('payments.order_id', $orderIds)
            ->whereIn('payments.status', ['approved', 'completed'])
            ->select(
                DB::raw("COALESCE(payment_options.report_code, '') as report_code"),
                DB::raw('SUM(payments.amount) as total')
            )
            ->groupBy('report_code')
            ->get();

        $breakdown = self::emptyPaymentBreakdown();

        foreach ($rows as $row) {
            $code = $row->report_code;
            $amount = (float) $row->total;

            if (in_array($code, self::ABONO_CODES, true)) {
                $breakdown['abono'] += $amount;
            } elseif ($code === 'AP') {
                $breakdown['aporte'] += $amount;
            } elseif ($code === 'CT') {
                $breakdown['credito_temporal'] += $amount;
            } elseif ($code === 'NC') {
                $breakdown['nota_credito'] += $amount;
            } elseif ($code === 'RA') {
                $breakdown['reverso_admin'] += $amount;
            } else {
                // Pagos sin payment_option_id o con código desconocido → cuentan como abono
                $breakdown['abono'] += $amount;
            }
        }

        return $breakdown;
    }

    /**
     * Calcula totales agregados de un programa completo.
     * Optimizado con queries agrupadas (evita N+1).
     */
    public static function calculateProgramTotals(int $programCourseId): array
    {
        $programCourse = ProgramCourse::find($programCourseId);
        if (!$programCourse) {
            return self::emptyProgramTotals();
        }

        // Participantes activos del programa
        $participantIds = ParticipantProgram::where('program_id', $programCourseId)
            ->pluck('participant_id')
            ->all();

        if (empty($participantIds)) {
            return self::emptyProgramTotals();
        }

        // Agregación de pagos para todo el programa (una sola query)
        $orderIds = Order::where('program_id', $programCourseId)
            ->pluck('id')
            ->all();

        $payments = empty($orderIds)
            ? self::emptyPaymentBreakdown()
            : self::aggregatePaymentsByOrderIds($orderIds);

        $totalPaid = $payments['abono']
            + $payments['aporte']
            + $payments['credito_temporal']
            + $payments['nota_credito']
            + $payments['reverso_admin'];

        // Total esperado: suma de net_amount de cada participante activo
        // Iteramos porque ParticipantPriceHelper requiere lógica per-participante
        $totalAmount = 0.0;
        $participantsCount = 0;

        foreach ($participantIds as $pid) {
            $financial = self::calculate($pid, $programCourseId);
            $totalAmount += $financial['net_amount'];
            $participantsCount++;
        }

        $paymentPercentage = $totalAmount > 0
            ? round(($totalPaid / $totalAmount) * 100, 2)
            : 0;

        return [
            'participants_count' => $participantsCount,
            'total_amount' => $totalAmount,
            'total_paid' => $totalPaid,
            'abono' => $payments['abono'],
            'aporte' => $payments['aporte'],
            'credito_temporal' => $payments['credito_temporal'],
            'nota_credito' => $payments['nota_credito'],
            'reverso_admin' => $payments['reverso_admin'],
            'pending_amount' => max($totalAmount - $totalPaid, 0),
            'payment_percentage' => $paymentPercentage,
        ];
    }

    /**
     * Suma simple de pagos approved/completed de un participante (para retrocompatibilidad).
     * Devuelve el total neto incluyendo NC/RA negativos.
     */
    public static function getTotalPaid(int $participantId, int $programCourseId): float
    {
        $payments = self::aggregatePaymentsForParticipant($participantId, $programCourseId);
        return $payments['abono']
            + $payments['aporte']
            + $payments['credito_temporal']
            + $payments['nota_credito']
            + $payments['reverso_admin'];
    }

    private static function emptyPaymentBreakdown(): array
    {
        return [
            'abono' => 0.0,
            'aporte' => 0.0,
            'credito_temporal' => 0.0,
            'nota_credito' => 0.0,
            'reverso_admin' => 0.0,
        ];
    }

    private static function emptyResult(): array
    {
        return [
            'base_price' => 0,
            'adjustments' => 0,
            'regular_discounts' => 0,
            'released_discounts' => 0,
            'discounts' => 0,
            'net_amount' => 0,
            'original_net_amount' => 0,
            'abono' => 0,
            'aporte' => 0,
            'credito_temporal' => 0,
            'nota_credito' => 0,
            'reverso_admin' => 0,
            'total_paid' => 0,
            'pending_amount' => 0,
            'progress_percentage' => 0,
            'status' => 'pending',
            'is_de_baja' => false,
        ];
    }

    private static function emptyProgramTotals(): array
    {
        return [
            'participants_count' => 0,
            'total_amount' => 0,
            'total_paid' => 0,
            'abono' => 0,
            'aporte' => 0,
            'credito_temporal' => 0,
            'nota_credito' => 0,
            'reverso_admin' => 0,
            'pending_amount' => 0,
            'payment_percentage' => 0,
        ];
    }
}
