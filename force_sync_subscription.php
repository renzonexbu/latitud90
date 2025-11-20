<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\ProgramSubscription;
use App\Services\Subscription\VirtualPosSubscriptionService;
use Illuminate\Support\Facades\Log;

echo "=== Forzando sincronización de suscripción ===\n\n";

$subscription = ProgramSubscription::find(5);

if (!$subscription) {
    echo "Suscripción no encontrada\n";
    exit(1);
}

echo "Suscripción ID: {$subscription->id}\n";
echo "VirtualPos ID: {$subscription->virtualpos_subscription_id}\n\n";

$virtualPosService = new VirtualPosSubscriptionService();

// Obtener datos actuales desde VirtualPos
echo "Consultando VirtualPos...\n";
$virtualPosData = $virtualPosService->getSubscription($subscription->virtualpos_subscription_id);

if (!$virtualPosData) {
    echo "Error obteniendo datos de VirtualPos\n";
    exit(1);
}

$currentCharges = $virtualPosData['suscription']['charge_program'] ?? [];
$savedCharges = $subscription->charge_program ?? [];

echo "Charges actuales en VirtualPos: " . count($currentCharges) . "\n";
echo "Charges guardados en BD: " . count($savedCharges) . "\n\n";

// Mostrar comparación
echo "Estado de charges:\n";
echo str_repeat("=", 80) . "\n";

foreach ($currentCharges as $index => $charge) {
    $chargeNum = $index + 1;
    $chargeId = $charge['id'] ?? 'N/A';
    $currentStatus = $charge['status'] ?? 'unknown';

    // Buscar en charges guardados
    $savedStatus = 'not-saved';
    foreach ($savedCharges as $saved) {
        if (($saved['id'] ?? null) === $chargeId) {
            $savedStatus = $saved['status'] ?? 'unknown';
            break;
        }
    }

    $isNew = ($savedStatus === 'not-saved' || ($currentStatus === 'pagado' && $savedStatus !== 'pagado'));

    echo "Charge #{$chargeNum} ({$chargeId}):\n";
    echo "  VirtualPos status: {$currentStatus}\n";
    echo "  Saved status: {$savedStatus}\n";
    echo "  Is new payment: " . ($isNew ? 'YES ✅' : 'NO') . "\n\n";
}

// Actualizar charge_program en la suscripción
echo "Actualizando charge_program en la BD...\n";
$subscription->update(['charge_program' => $currentCharges]);

echo "✅ Actualizado\n\n";

echo "Ahora ejecuta: php artisan subscriptions:sync-payments --subscription=5\n";
echo "Y luego: php artisan queue:work --once\n";
