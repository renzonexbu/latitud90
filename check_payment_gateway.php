<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Payment;

echo "=== Verificar PaymentGateway ===\n\n";

$payment = Payment::with([
    'orderDetail',
    'paymentGateway'
])->find(1);

if (!$payment) {
    echo "❌ Payment no encontrado\n";
    exit(1);
}

echo "Payment #1:\n";
echo "  payment_gateway_id: " . ($payment->payment_gateway_id ?? 'NULL') . "\n";
echo "  paymentGateway->name: " . ($payment->paymentGateway->name ?? 'NULL') . "\n\n";

echo "OrderDetail #" . $payment->orderDetail->id . ":\n";
echo "  payment_gateway_id: " . ($payment->orderDetail->payment_gateway_id ?? 'NULL') . "\n";
echo "  paymentGateway: " . ($payment->orderDetail->paymentGateway ? $payment->orderDetail->paymentGateway->name : 'NULL') . "\n\n";

echo "Problema identificado:\n";
if (!$payment->orderDetail->payment_gateway_id) {
    echo "  ❌ OrderDetail NO tiene payment_gateway_id asignado\n";
    echo "  ✅ Payment SÍ tiene payment_gateway_id: " . ($payment->payment_gateway_id ?? 'NULL') . "\n\n";
    echo "Solución: Obtener paymentGateway desde Payment en lugar de OrderDetail\n";
} else {
    echo "  ✅ OrderDetail tiene payment_gateway_id asignado\n";
}
