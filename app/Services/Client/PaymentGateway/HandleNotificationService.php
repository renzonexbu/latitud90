<?php

namespace App\Services\Client\PaymentGateway;

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
        return $notification;
    }

    private function handleTransbankNotification($notification)
    {
        return $notification;
    }

    private function handleKhipuNotification($notification)
    {
        return $notification;
    }
}
