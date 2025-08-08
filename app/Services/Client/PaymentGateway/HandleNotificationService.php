<?php

namespace App\Services\Client\PaymentGateway;

use App\Models\OrderDetail;
use Illuminate\Support\Facades\Log;

class HandleNotificationService
{
    public function handleNotification($paymentGateway, $notification)
    {
        if ($paymentGateway === 'transbank') {
            return $this->handleTransbankNotification($notification);
        }
        if ($paymentGateway === 'khipu') {
            return $this->handleKhipuNotification($notification);
        }
        return ['success' => false, 'error' => 'Gateway no soportado'];
    }

    private function handleTransbankNotification($notification)
    {
        try {
            $orderId = $notification['buy_order'] ?? null;
            $status = $notification['status'] ?? null;
            
            if (!$orderId) {
                Log::error('Transbank notification: No order_id provided', $notification);
                return ['success' => false, 'error' => 'No order_id provided'];
            }

            $orderDetail = OrderDetail::where('order_id', $orderId)->first();
            
            if (!$orderDetail) {
                Log::error('Transbank notification: OrderDetail not found', ['order_id' => $orderId]);
                return ['success' => false, 'error' => 'OrderDetail not found'];
            }

            if ($status === 'paid' || $status === 'approved') {
                $orderDetail->update(['is_paid' => true]);
                
                Log::info('OrderDetail marked as paid via Transbank notification', [
                    'order_detail_id' => $orderDetail->id,
                    'order_id' => $orderId
                ]);
                
                return ['success' => true, 'order_detail_id' => $orderDetail->id];
            } else {
                Log::info('Transbank notification: Payment not approved', [
                    'order_id' => $orderId,
                    'status' => $status
                ]);
                
                return ['success' => false, 'error' => 'Payment not approved'];
            }
        } catch (\Exception $e) {
            Log::error('Error processing Transbank notification', [
                'error' => $e->getMessage(),
                'notification' => $notification
            ]);
            
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    private function handleKhipuNotification($notification)
    {
        try {
            $orderId = $notification['order_id'] ?? null;
            $status = $notification['status'] ?? null;
            
            if (!$orderId) {
                Log::error('Khipu notification: No order_id provided', $notification);
                return ['success' => false, 'error' => 'No order_id provided'];
            }

            $orderDetail = OrderDetail::where('order_id', $orderId)->first();
            
            if (!$orderDetail) {
                Log::error('Khipu notification: OrderDetail not found', ['order_id' => $orderId]);
                return ['success' => false, 'error' => 'OrderDetail not found'];
            }

            if ($status === 'done' || $status === 'paid') {
                $orderDetail->update(['is_paid' => true]);
                
                Log::info('OrderDetail marked as paid via Khipu notification', [
                    'order_detail_id' => $orderDetail->id,
                    'order_id' => $orderId
                ]);
                
                return ['success' => true, 'order_detail_id' => $orderDetail->id];
            } else {
                Log::info('Khipu notification: Payment not approved', [
                    'order_id' => $orderId,
                    'status' => $status
                ]);
                
                return ['success' => false, 'error' => 'Payment not approved'];
            }
        } catch (\Exception $e) {
            Log::error('Error processing Khipu notification', [
                'error' => $e->getMessage(),
                'notification' => $notification
            ]);
            
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}
