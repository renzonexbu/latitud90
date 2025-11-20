<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\ProgramSubscription;
use App\Services\Subscription\VirtualPosSubscriptionService;
use Illuminate\Support\Facades\Log;

echo "=== Consultando última suscripción ===\n\n";

// Obtener la última suscripción creada
$lastSubscription = ProgramSubscription::orderBy('id', 'desc')->first();

if (!$lastSubscription) {
    echo "❌ No se encontraron suscripciones en la base de datos.\n";
    exit(1);
}

echo "📋 Última suscripción en base de datos:\n";
echo "   - ID: {$lastSubscription->id}\n";
echo "   - VirtualPos ID: {$lastSubscription->virtualpos_subscription_id}\n";
echo "   - Estado: {$lastSubscription->status}\n";
echo "   - Creada: {$lastSubscription->created_at}\n\n";

// Verificar que tenga virtualpos_subscription_id
if (!$lastSubscription->virtualpos_subscription_id) {
    echo "❌ Esta suscripción no tiene virtualpos_subscription_id.\n";
    exit(1);
}

// Consultar suscripción en VirtualPos
echo "🔍 Consultando suscripción en VirtualPos...\n\n";

$virtualPosService = new VirtualPosSubscriptionService();

try {
    $result = $virtualPosService->getSubscription($lastSubscription->virtualpos_subscription_id);
} catch (Exception $e) {
    echo "❌ Error al consultar suscripción en VirtualPos:\n";
    echo "   " . $e->getMessage() . "\n";

    Log::error('Error al consultar suscripción en VirtualPos', [
        'subscription_id' => $lastSubscription->id,
        'virtualpos_subscription_id' => $lastSubscription->virtualpos_subscription_id,
        'error' => $e->getMessage()
    ]);

    exit(1);
}

echo "✅ Suscripción obtenida exitosamente de VirtualPos\n\n";

// Extraer datos de la suscripción (VirtualPos usa "suscription" en español)
$subscriptionData = $result['suscription'] ?? $result['subscription'] ?? $result;

// Registrar respuesta completa en el log
Log::info('=== CONSULTA DE ÚLTIMA SUSCRIPCIÓN ===', [
    'local_subscription_id' => $lastSubscription->id,
    'virtualpos_subscription_id' => $lastSubscription->virtualpos_subscription_id,
    'response_completa' => $result
]);

// Extraer y mostrar el programa de cobros
$chargeProgram = $subscriptionData['charge_program'] ?? [];

echo "💰 PROGRAMA DE COBROS (MENSUALIDADES):\n";
echo str_repeat("=", 80) . "\n\n";

if (empty($chargeProgram)) {
    echo "⚠️  No se encontró programa de cobros en la respuesta\n";

    Log::warning('Suscripción sin programa de cobros', [
        'subscription_id' => $lastSubscription->id,
        'virtualpos_subscription_id' => $lastSubscription->virtualpos_subscription_id
    ]);
} else {
    $totalCharges = count($chargeProgram);
    $totalAmount = 0;

    foreach ($chargeProgram as $index => $charge) {
        $chargeNumber = $index + 1;
        $chargeId = $charge['id'] ?? 'N/A';
        $chargeDate = $charge['charge_date'] ?? 'N/A';
        $amount = $charge['amount'] ?? 0;
        $status = $charge['status'] ?? 'N/A';
        $description = $charge['description'] ?? 'N/A';

        $totalAmount += $amount;

        // Determinar emoji según estado
        $statusEmoji = match($status) {
            'procesando' => '🔄',
            'pendiente' => '⏳',
            'pagado' => '✅',
            'fallido' => '❌',
            default => '❓'
        };

        echo "Cuota #{$chargeNumber} de {$totalCharges}:\n";
        echo "   ID:          {$chargeId}\n";
        echo "   Fecha:       {$chargeDate}\n";
        echo "   Monto:       $" . number_format($amount, 0, ',', '.') . " CLP\n";
        echo "   Estado:      {$statusEmoji} {$status}\n";
        echo "   Descripción: {$description}\n";
        echo "\n";
    }

    echo str_repeat("=", 80) . "\n";
    echo "📊 RESUMEN:\n";
    echo "   Total de cuotas:  {$totalCharges}\n";
    echo "   Monto total:      $" . number_format($totalAmount, 0, ',', '.') . " CLP\n";
    echo "   Monto por cuota:  $" . number_format($totalAmount / $totalCharges, 0, ',', '.') . " CLP\n\n";

    // Registrar detalles de cada cuota en el log
    Log::info('DETALLES DE TODAS LAS MENSUALIDADES', [
        'subscription_id' => $lastSubscription->id,
        'virtualpos_subscription_id' => $lastSubscription->virtualpos_subscription_id,
        'total_cuotas' => $totalCharges,
        'monto_total' => $totalAmount,
        'monto_por_cuota' => $totalAmount / $totalCharges,
        'cuotas' => array_map(function($charge, $index) {
            return [
                'numero' => $index + 1,
                'id' => $charge['id'] ?? null,
                'fecha' => $charge['charge_date'] ?? null,
                'monto' => $charge['amount'] ?? null,
                'estado' => $charge['status'] ?? null,
                'descripcion' => $charge['description'] ?? null,
            ];
        }, $chargeProgram, array_keys($chargeProgram))
    ]);
}

// Mostrar otros datos relevantes de la suscripción
echo "📄 INFORMACIÓN ADICIONAL DE LA SUSCRIPCIÓN:\n";
echo str_repeat("=", 80) . "\n";
echo "   Plan ID:         " . ($subscriptionData['plan_id'] ?? 'N/A') . "\n";
echo "   Estado:          " . ($subscriptionData['status'] ?? 'N/A') . "\n";
echo "   Fecha creación:  " . ($subscriptionData['created_at'] ?? 'N/A') . "\n";
echo "   Cliente ID:      " . ($subscriptionData['customer_id'] ?? 'N/A') . "\n";

if (isset($subscriptionData['card'])) {
    echo "\n💳 TARJETA:\n";
    echo "   Últimos 4:       " . ($subscriptionData['card']['last4'] ?? 'N/A') . "\n";
    echo "   Marca:           " . ($subscriptionData['card']['brand'] ?? 'N/A') . "\n";
}

echo "\n✅ Script completado. Revisa el log de Laravel para ver los detalles completos.\n";
echo "   Ubicación del log: storage/logs/laravel.log\n\n";
