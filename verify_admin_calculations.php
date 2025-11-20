<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\ProgramSubscription;
use App\Models\InstallmentPlan;
use App\Models\Payment;
use App\Models\Order;
use App\Models\ProgramCourse;

echo "=== Verificando cálculos de suscripción ===\n\n";

$subscription = ProgramSubscription::find(5);

if (!$subscription) {
    echo "❌ Suscripción no encontrada\n";
    exit(1);
}

$programCourse = $subscription->programCourse;
$installmentPlan = InstallmentPlan::where('participant_id', $subscription->participant_id)
    ->where('program_id', $subscription->program_id)
    ->first();

echo "📋 SUSCRIPCIÓN\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "ID: {$subscription->id}\n";
echo "Participante: " . $subscription->participant->full_name . "\n";
echo "Programa: " . ($programCourse->name ?? 'N/A') . "\n";
echo "Estado: {$subscription->status}\n";
echo "Monto total: \$" . number_format($installmentPlan->total_amount ?? 0, 0, ',', '.') . " CLP\n";
echo "Total cuotas: " . ($installmentPlan->total_installments ?? 0) . "\n\n";

// Calcular cuotas pagadas y pendientes
$paidInstallments = $installmentPlan->installments->where('is_paid', true)->count();
$pendingInstallments = $installmentPlan->installments->where('is_paid', false)->count();
$paidAmount = $installmentPlan->installments->where('is_paid', true)->sum('amount');
$pendingAmount = $installmentPlan->installments->where('is_paid', false)->sum('amount');

echo "💰 ESTADO DE PAGOS\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "Cuotas pagadas: {$paidInstallments} de " . ($installmentPlan->total_installments ?? 0) . "\n";
echo "Cuotas pendientes: {$pendingInstallments}\n";
echo "Monto pagado: \$" . number_format($paidAmount, 0, ',', '.') . " CLP\n";
echo "Monto pendiente: \$" . number_format($pendingAmount, 0, ',', '.') . " CLP\n\n";

// Verificar pagos registrados
$payments = Payment::whereHas('orderDetail', function($q) use ($subscription) {
    $q->whereHas('order', function($q2) use ($subscription) {
        $q2->where('participant_id', $subscription->participant_id)
           ->where('program_id', $subscription->program_id);
    });
})->get();

echo "📝 PAGOS REGISTRADOS\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "Total de pagos: " . $payments->count() . "\n\n";

foreach ($payments as $payment) {
    echo "Payment ID: {$payment->id}\n";
    echo "  Monto: \$" . number_format($payment->amount, 0, ',', '.') . " CLP\n";
    echo "  Estado: {$payment->status}\n";
    echo "  Fecha: " . ($payment->transaction_date ? $payment->transaction_date->format('d-m-Y H:i') : 'N/A') . "\n";
    echo "  External ID: " . ($payment->external_payment_id ?? 'N/A') . "\n";
    echo "\n";
}

// Verificar Order
$order = Order::where('participant_id', $subscription->participant_id)
    ->where('program_id', $subscription->program_id)
    ->first();

if ($order) {
    echo "📦 ORDEN\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "Order ID: {$order->id}\n";
    echo "Número: {$order->order_number}\n";
    echo "Total: \$" . number_format($order->total_amount, 0, ',', '.') . " CLP\n";
    echo "Estado: {$order->status}\n";
    echo "OrderDetails: " . $order->orderDetails->count() . "\n\n";
}

// Resumen para el panel administrativo
echo "✅ RESUMEN PARA PANEL ADMIN\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "Progreso: {$paidInstallments}/{$installmentPlan->total_installments} cuotas pagadas\n";
echo "Porcentaje: " . round(($paidInstallments / $installmentPlan->total_installments) * 100, 1) . "%\n";
echo "Pagado: \$" . number_format($paidAmount, 0, ',', '.') . " CLP\n";
echo "Pendiente: \$" . number_format($pendingAmount, 0, ',', '.') . " CLP\n";
echo "Total: \$" . number_format($installmentPlan->total_amount, 0, ',', '.') . " CLP\n\n";

// Verificar que coincida con los payments
$totalPaymentsAmount = $payments->sum('amount');
if ($totalPaymentsAmount != $paidAmount) {
    echo "⚠️  ADVERTENCIA: Discrepancia entre installments pagados y payments\n";
    echo "   Installments pagados: \$" . number_format($paidAmount, 0, ',', '.') . "\n";
    echo "   Payments registrados: \$" . number_format($totalPaymentsAmount, 0, ',', '.') . "\n\n";
} else {
    echo "✅ Los montos coinciden correctamente\n\n";
}
