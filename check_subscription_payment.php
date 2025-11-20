<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Payment;
use App\Models\Order;
use App\Models\ProgramSubscription;

echo "=== Verificar si Payment es de Suscripción ===\n\n";

$payment = Payment::with([
    'orderDetail.order',
    'paymentGateway'
])->find(1);

if (!$payment) {
    echo "❌ Payment no encontrado\n";
    exit(1);
}

echo "Payment #1:\n";
echo "  Gateway: " . ($payment->paymentGateway->name ?? 'N/A') . "\n";
echo "  Gateway Code: " . ($payment->paymentGateway->code ?? 'N/A') . "\n";
echo "  External Payment ID: " . ($payment->external_payment_id ?? 'N/A') . "\n\n";

// Verificar si hay suscripción para este participante/programa
$order = $payment->orderDetail->order;
$subscription = ProgramSubscription::where('participant_id', $order->participant_id)
    ->where('program_id', $order->program_id)
    ->first();

echo "Verificar Suscripción:\n";
if ($subscription) {
    echo "  ✅ SÍ es un pago de suscripción\n";
    echo "  Subscription ID: {$subscription->id}\n";
    echo "  VirtualPos ID: {$subscription->virtualpos_subscription_id}\n";
    echo "  Status: {$subscription->status}\n";

    // Obtener información del charge_program
    $chargeProgram = $subscription->charge_program ?? [];
    echo "  Total cuotas: " . count($chargeProgram) . "\n\n";

    if (!empty($chargeProgram)) {
        echo "Próxima cuota:\n";
        $nextCharge = null;
        foreach ($chargeProgram as $charge) {
            if (($charge['status'] ?? '') === 'pendiente') {
                $nextCharge = $charge;
                break;
            }
        }

        if ($nextCharge) {
            echo "  Fecha: " . ($nextCharge['charge_date'] ?? 'N/A') . "\n";
            echo "  Monto: $" . number_format($nextCharge['amount'] ?? 0, 0, ',', '.') . " CLP\n";
        } else {
            echo "  No hay próxima cuota pendiente\n";
        }
    }
} else {
    echo "  ❌ NO es un pago de suscripción (pago tradicional)\n";
}

echo "\nMétodo de detección recomendado:\n";
echo "  Gateway code === 'virtualpos' && external_payment_id existe\n";
echo "  → Es suscripción: " . (($payment->paymentGateway->code ?? '') === 'virtualpos' && !empty($payment->external_payment_id) ? 'SÍ' : 'NO') . "\n";
