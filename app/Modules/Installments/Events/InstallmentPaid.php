<?php

namespace App\Modules\Installments\Events;

use App\Models\Installment;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class InstallmentPaid
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Installment $installment;
    public array $paymentData;

    /**
     * Create a new event instance.
     */
    public function __construct(Installment $installment, array $paymentData)
    {
        $this->installment = $installment;
        $this->paymentData = $paymentData;
    }
}
