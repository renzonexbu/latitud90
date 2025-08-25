<?php

namespace App\Services\Admin\Payments;

use App\Models\Payment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DeletePaymentService
{
    /**
     * Eliminar un pago
     *
     * @param Payment $payment
     * @return bool
     * @throws \Exception
     */
    public function execute(Payment $payment): bool
    {
        try {
            DB::beginTransaction();

            // Actualizar el detalle de la orden
            if ($payment->orderDetail) {
                $payment->orderDetail->update([
                    'is_paid' => false,
                    'status' => 'pending',
                    'paid_at' => null,
                ]);
            }

            // Actualizar estado de la orden
            if ($payment->order) {
                $payment->order->refreshStatus();
            }

            $payment->delete();

            DB::commit();

            Log::info('Pago eliminado exitosamente', [
                'payment_id' => $payment->id,
                'order_id' => $payment->order_id ?? null
            ]);

            return true;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al eliminar pago', [
                'payment_id' => $payment->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
}
