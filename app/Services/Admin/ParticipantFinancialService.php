<?php

namespace App\Services\Admin;

use App\Helpers\ParticipantPriceHelper;
use App\Models\InstallmentPlan;
use App\Models\Order;
use App\Models\Participant;
use App\Models\ParticipantProgram;
use App\Models\ProgramCourse;
use Illuminate\Support\Facades\DB;

/**
 * Servicio centralizado para cálculos financieros de participantes.
 *
 * FUENTE ÚNICA DE VERDAD para: precio, abono, saldo, estado de pago.
 * Todos los reportes y vistas deben usar este servicio en vez de calcular por su cuenta.
 *
 * Referencia: lógica del Estado de Cuenta Parcial (PartialAccountTransformer::calculateFinancialData)
 */
class ParticipantFinancialService
{
    /**
     * Calcula todos los datos financieros de un participante en un programa.
     *
     * @param int $participantId
     * @param int $programCourseId  ID de program_courses (NO de programs)
     * @return array
     */
    public static function calculate(int $participantId, int $programCourseId): array
    {
        $participant = Participant::find($participantId);
        $programCourse = ProgramCourse::find($programCourseId);

        if (!$participant || !$programCourse) {
            return self::emptyResult();
        }

        // 1. Precio y descuentos via ParticipantPriceHelper
        $priceData = ParticipantPriceHelper::calculateParticipantPrice($participant, $programCourse);
        $basePrice = (float) ($priceData['base_price'] ?? 0);
        $discounts = (float) ($priceData['discounts'] ?? 0);
        $regularDiscounts = (float) ($priceData['regular_discounts'] ?? $discounts);
        $releasedDiscounts = (float) ($priceData['released_discounts'] ?? 0);
        $netAmount = (float) ($priceData['final_price'] ?? ($basePrice - $discounts));

        // 2. Estado de baja
        $pp = ParticipantProgram::where('participant_id', $participantId)
            ->where('program_id', $programCourseId)
            ->first();
        $isDeBaja = $pp && !$pp->is_active;

        // 3. Total pagado (misma lógica que Parcial)
        $totalPaid = self::calculateTotalPaid($participant, $programCourse);

        // 4. Si está de baja, ajustar precio al abono (saldo = 0)
        if ($isDeBaja) {
            $netAmount = min($netAmount, $totalPaid);
        }

        // 5. Saldo pendiente (nunca negativo)
        $pendingAmount = max($netAmount - $totalPaid, 0);

        // 6. Estado de pago
        $status = 'pending';
        if ($pendingAmount <= 0) {
            $status = 'paid';
        } elseif ($totalPaid > 0) {
            $status = 'partial';
        }

        // 7. Porcentaje de avance
        $progressPercentage = $netAmount > 0 ? round(($totalPaid / $netAmount) * 100, 2) : 0;

        return [
            'base_price' => $basePrice,
            'discounts' => $discounts,
            'regular_discounts' => $regularDiscounts,
            'released_discounts' => $releasedDiscounts,
            'net_amount' => $netAmount,
            'total_paid' => $totalPaid,
            'pending_amount' => $pendingAmount,
            'progress_percentage' => $progressPercentage,
            'status' => $status,
            'is_de_baja' => $isDeBaja,
        ];
    }

    /**
     * Calcula el total pagado por un participante en un programa.
     * Suma todos los pagos reales (approved/completed) de todas las órdenes.
     *
     * No se usa el installment plan porque puede tener cuotas marcadas
     * como "paid" sin cobro real (ej: suscripción fallida) o con montos
     * distintos al pago vinculado, e ignora pagos en otras órdenes.
     */
    private static function calculateTotalPaid(Participant $participant, ProgramCourse $programCourse): float
    {
        $orderIds = Order::where('participant_id', $participant->id)
            ->where('program_id', $programCourse->id)
            ->pluck('id')
            ->all();

        if (empty($orderIds)) {
            return 0;
        }

        return (float) DB::table('payments')
            ->whereIn('order_id', $orderIds)
            ->whereIn('status', ['approved', 'completed'])
            ->sum('amount');
    }

    /**
     * Resultado vacío para casos sin datos.
     */
    private static function emptyResult(): array
    {
        return [
            'base_price' => 0,
            'discounts' => 0,
            'regular_discounts' => 0,
            'released_discounts' => 0,
            'net_amount' => 0,
            'total_paid' => 0,
            'pending_amount' => 0,
            'progress_percentage' => 0,
            'status' => 'pending',
            'is_de_baja' => false,
        ];
    }
}
