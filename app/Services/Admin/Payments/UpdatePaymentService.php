<?php

namespace App\Services\Admin\Payments;

use App\Models\Payment;
use App\Traits\AdminLogging;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class UpdatePaymentService
{
    use AdminLogging;
    /**
     * Actualizar un pago existente
     *
     * @param Request $request
     * @param Payment $payment
     * @return Payment
     * @throws \Exception
     */
    public function execute(Request $request, Payment $payment): Payment
    {
        try {
            // Guardar valores anteriores para el log
            $oldValues = $payment->toArray();

            DB::beginTransaction();

            $payment->update([
                'amount' => $request->amount,
                'status' => $request->status,
                'transaction_date' => $request->transaction_date,
                'authorization_code' => $request->authorization_code,
                'card_number' => $request->card_number,
                'card_type' => $request->card_type,
                'gateway_response' => array_merge($payment->gateway_response ?? [], [
                    'notes' => $request->notes,
                    'updated_manually' => true
                ]),
            ]);

            // Actualizar el detalle de la orden
            if ($payment->orderDetail) {
                $payment->orderDetail->update([
                    'amount' => $request->amount,
                    'is_paid' => $request->status === 'completed',
                    'status' => $request->status,
                    'paid_at' => $request->status === 'completed' ? now() : null,
                ]);
            }

            // Actualizar estado de la orden
            if ($payment->order) {
                $payment->order->refreshStatus();
            }

            DB::commit();

            // Log the payment update
            $this->logUpdate(
                'payments',
                'Payment',
                $payment->id,
                "Pago actualizado: \${$request->amount} - Estado: {$request->status}",
                $oldValues,
                $payment->toArray(),
                [
                    'order_id' => $payment->order_id ?? null,
                    'order_detail_id' => $payment->order_detail_id ?? null,
                    'payment_gateway_id' => $payment->payment_gateway_id ?? null,
                    'updated_manually' => true,
                ]
            );

            Log::info('Pago actualizado exitosamente', [
                'payment_id' => $payment->id,
                'amount' => $request->amount,
                'status' => $request->status
            ]);

            return $payment;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al actualizar pago', [
                'payment_id' => $payment->id,
                'error' => $e->getMessage(),
                'request_data' => $request->all()
            ]);
            throw $e;
        }
    }
}
