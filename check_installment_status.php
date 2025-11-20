<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\ProgramSubscription;
use App\Models\InstallmentPlan;
use App\Models\Installment;

$subscription = ProgramSubscription::find(5);

if (!$subscription) {
    echo "Suscripción no encontrada\n";
    exit(1);
}

echo "Suscripción ID: {$subscription->id}\n";
echo "Participant ID: {$subscription->participant_id}\n";
echo "Program ID: {$subscription->program_id}\n\n";

$plan = InstallmentPlan::where('participant_id', $subscription->participant_id)
    ->where('program_id', $subscription->program_id)
    ->first();

if (!$plan) {
    echo "No se encontró InstallmentPlan\n";
    exit(1);
}

echo "InstallmentPlan ID: {$plan->id}\n";
echo "Total installments: {$plan->total_installments}\n\n";

$installments = Installment::where('installment_plan_id', $plan->id)
    ->orderBy('installment_number')
    ->get();

echo "Cuotas encontradas: {$installments->count()}\n\n";

foreach ($installments as $inst) {
    $statusEmoji = match($inst->status) {
        'paid' => '✅',
        'pending' => '⏳',
        'overdue' => '⚠️',
        default => '❓'
    };

    echo "Cuota #{$inst->installment_number}:\n";
    echo "  Status: {$statusEmoji} {$inst->status}\n";
    echo "  Is paid: " . ($inst->is_paid ? 'SÍ' : 'NO') . "\n";
    echo "  Amount: $" . number_format($inst->amount, 0, ',', '.') . "\n";
    echo "  Due date: {$inst->due_date}\n";
    echo "  VirtualPos charge ID: " . ($inst->virtualpos_charge_id ?? 'N/A') . "\n";
    echo "\n";
}
