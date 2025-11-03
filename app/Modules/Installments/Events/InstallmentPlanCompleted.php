<?php

namespace App\Modules\Installments\Events;

use App\Models\InstallmentPlan;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class InstallmentPlanCompleted
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public InstallmentPlan $plan;

    /**
     * Create a new event instance.
     */
    public function __construct(InstallmentPlan $plan)
    {
        $this->plan = $plan;
    }
}
