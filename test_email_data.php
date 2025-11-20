<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Payment;
use App\Services\Mail\SuccessPaymentEmailService;

echo "=== Test: Datos del Email de Suscripción ===\n\n";

$payment = Payment::with([
    'orderDetail.order',
    'paymentGateway'
])->find(1);

if (!$payment) {
    echo "❌ Payment no encontrado\n";
    exit(1);
}

// Usar reflection para acceder al método privado prepareEmailData
$service = new SuccessPaymentEmailService();
$reflection = new ReflectionClass($service);
$method = $reflection->getMethod('prepareEmailData');
$method->setAccessible(true);

$emailData = $method->invoke($service, $payment->orderDetail, $payment);

echo "Datos preparados para el email:\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "📧 Customer: {$emailData['customer_name']}\n";
echo "📋 Program: {$emailData['program_name']}\n";
echo "💰 Amount: \${$emailData['payment_amount']} {$emailData['payment_currency']}\n";
echo "🔧 Method: {$emailData['payment_method']}\n";
echo "📅 Date: {$emailData['payment_date']}\n";
echo "📝 Installment: {$emailData['installment_number']} de {$emailData['total_installments']}\n";
echo "🔄 Is Subscription: " . ($emailData['is_subscription'] ? 'SÍ ✅' : 'NO ❌') . "\n";

if ($emailData['is_subscription']) {
    echo "\n🔔 Información de Próxima Cuota:\n";
    echo "   📅 Fecha: " . ($emailData['next_payment_date'] ?? 'N/A') . "\n";
    echo "   💵 Monto: $" . ($emailData['next_payment_amount'] ?? 'N/A') . " CLP\n";
}

echo "\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "✅ Datos preparados correctamente\n";
