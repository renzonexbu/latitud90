<?php

namespace App\Services\Admin\Payments;

use App\Models\Payment;
use App\Traits\AdminLogging;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DeletePaymentService
{
    use AdminLogging;
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
            // Guardar datos del pago antes de eliminarlo para el log
            $paymentData = $payment->toArray();

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

            // Log the payment deletion
            $this->logDelete(
                'payments',
                'Payment',
                $paymentData['id'],
                "Pago eliminado: \${$paymentData['amount']} - ID: {$paymentData['id']}",
                $paymentData,
                [
                    'order_id' => $paymentData['order_id'] ?? null,
                    'order_detail_id' => $paymentData['order_detail_id'] ?? null,
                    'payment_gateway_id' => $paymentData['payment_gateway_id'] ?? null,
                ]
            );

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
