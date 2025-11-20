<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Payment;

echo "=== Verificando estado del email enviado ===\n\n";

$payment = Payment::with('orderDetail')->find(1);

if (!$payment) {
    echo "❌ Payment no encontrado\n";
    exit(1);
}

echo "Payment #1:\n";
echo "  email_sent: " . ($payment->email_sent ? 'true' : 'false') . "\n";
echo "  email_sent_at: " . ($payment->email_sent_at ? $payment->email_sent_at->format('Y-m-d H:i:s') : 'null') . "\n";
echo "  installment_number: " . $payment->orderDetail->installment_number . "\n";
echo "  amount: $" . number_format($payment->amount, 0, ',', '.') . " CLP\n";

echo "\n✅ Verificación completada\n";
