<?php

namespace App\Services\Admin\Installments;

use App\Models\Installment;
use Illuminate\Support\Facades\Log;
use Exception;

class MarkInstallmentAsPaidManually
{
    /**
     * Marcar una cuota como pagada manualmente
     *
     * @param int $installmentId ID de la cuota
     * @param int $paymentId ID del pago registrado
     * @param int $orderId ID de la orden
     * @param int $orderDetailId ID del detalle de orden
     * @param string $paymentSource Tipo de pago manual (manual_cash, manual_transfer, etc.)
     * @return array
     */
    public function markAsPaid(
        int $installmentId,
        int $paymentId,
        int $orderId,
        int $orderDetailId,
        string $paymentSource = 'manual_cash'
    ): array {
        try {
            $installment = Installment::find($installmentId);

            if (!$installment) {
                throw new Exception('Cuota no encontrada');
            }

            if ($installment->is_paid) {
                Log::warning('Intento de marcar cuota ya pagada', [
                    'installment_id' => $installmentId,
                    'current_payment_source' => $installment->payment_source
                ]);

                return [
                    'success' => false,
                    'message' => 'Esta cuota ya está marcada como pagada',
                    'data' => [
                        'installment_id' => $installment->id,
                        'payment_source' => $installment->payment_source
                    ]
                ];
            }

            // Validar payment_source
            $allowedSources = ['manual_cash', 'manual_transfer', 'manual_online', 'manual_check', 'manual_other'];
            if (!in_array($paymentSource, $allowedSources)) {
                $paymentSource = 'manual_cash';
            }

            // Marcar como pagada
            $installment->update([
                'status' => 'paid',
                'is_paid' => true,
                'paid_at' => now(),
                'payment_order_id' => $orderId,
                'payment_order_detail_id' => $orderDetailId,
                'payment_id' => $paymentId,
                'payment_source' => $paymentSource,
            ]);

            Log::info('✅ Cuota marcada como pagada manualmente', [
                'installment_id' => $installment->id,
                'installment_number' => $installment->installment_number,
                'payment_id' => $paymentId,
                'payment_source' => $paymentSource,
                'amount' => $installment->amount,
            ]);

            return [
                'success' => true,
                'message' => 'Cuota marcada como pagada exitosamente',
                'data' => [
                    'installment_id' => $installment->id,
                    'installment_number' => $installment->installment_number,
                    'payment_source' => $paymentSource,
                    'amount' => $installment->amount,
                ]
            ];

        } catch (Exception $e) {
            Log::error('❌ Error marcando cuota como pagada', [
                'installment_id' => $installmentId,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Error al marcar la cuota como pagada: ' . $e->getMessage(),
                'data' => null
            ];
        }
    }

    /**
     * Obtener cuotas pendientes de un participante y programa
     *
     * @param int $participantId
     * @param int $programId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getPendingInstallments(int $participantId, int $programId)
    {
        return Installment::whereHas('installmentPlan', function ($query) use ($participantId, $programId) {
            $query->where('participant_id', $participantId)
                  ->where('program_id', $programId);
        })
        ->whereIn('status', ['pending', 'overdue'])
        ->orderBy('installment_number')
        ->get();
    }
}
