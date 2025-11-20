<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\OrderDetail;
use App\Models\ProgramSubscription;

echo "=== Corrigiendo installments_number ===\n\n";

$orderDetail = OrderDetail::with('order')->find(7);

if (!$orderDetail) {
    echo "❌ OrderDetail no encontrado\n";
    exit(1);
}

echo "OrderDetail #7:\n";
echo "  Antes - installments_number: " . ($orderDetail->installments_number ?? 'NULL') . "\n";

// Buscar la suscripción
$order = $orderDetail->order;
$subscription = ProgramSubscription::where('participant_id', $order->participant_id)
    ->where('program_id', $order->program_id)
    ->first();

if (!$subscription) {
    echo "❌ No se encontró suscripción\n";
    exit(1);
}

$totalInstallments = count($subscription->charge_program ?? []);
echo "  Total de cuotas en suscripción: {$totalInstallments}\n";

// Actualizar
$orderDetail->update([
    'installments_number' => $totalInstallments
]);

echo "  Después - installments_number: {$orderDetail->installments_number}\n";
echo "\n✅ Corregido exitosamente\n";
