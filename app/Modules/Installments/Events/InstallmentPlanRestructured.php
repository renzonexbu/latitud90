<?php

namespace App\Modules\Installments\Events;

use App\Models\InstallmentPlan;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class InstallmentPlanRestructured
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public InstallmentPlan $plan;
    public int $oldInstallments;
    public int $newInstallments;
    public string $reason;

    /**
     * Create a new event instance.
     */
    public function __construct(InstallmentPlan $plan, int $oldInstallments, int $newInstallments, string $reason)
    {
        $this->plan = $plan;
        $this->oldInstallments = $oldInstallments;
        $this->newInstallments = $newInstallments;
        $this->reason = $reason;
    }
}
