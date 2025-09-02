<?php

namespace App\Services\Client\Payment;

use App\Models\OrderDetail;
use App\Traits\SystemLogging;

class CheckPaymentStatusService
{
    use SystemLogging;
    /**
     * Verificar estado del pago
     *
     * @param int $orderDetailId
     * @return array
     */
    public function execute(int $orderDetailId): array
    {
        try {
            $orderDetail = OrderDetail::findOrFail($orderDetailId);

            return [
                'success' => true,
                'is_paid' => $orderDetail->is_paid,
                'order_detail_id' => $orderDetail->id,
                'amount' => $orderDetail->amount,
                'order_id' => $orderDetail->order_id
            ];
        } catch (\Exception $e) {
            $this->logError('CheckPaymentStatusService: Error checking payment status', [
                'error' => $e->getMessage(),
                'order_detail_id' => $orderDetailId
            ], $e);

            return [
                'success' => false,
                'message' => 'Error al verificar el estado del pago'
            ];
        }
    }
}
