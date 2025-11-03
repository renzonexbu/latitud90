<?php

namespace App\Modules\Installments\Events;

use App\Models\Installment;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class InstallmentCreated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Installment $installment;

    /**
     * Create a new event instance.
     */
    public function __construct(Installment $installment)
    {
        $this->installment = $installment;
    }
}
