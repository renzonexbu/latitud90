<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Payment;
use Illuminate\Support\Facades\Log;

echo "=== Test: Verificar flujo completo de email ===\n\n";

$payment = Payment::with([
    'orderDetail',
    'orderDetail.order',
    'orderDetail.order.participant',
    'orderDetail.order.programCourse',
])->find(1);

if (!$payment) {
    echo "❌ Payment no encontrado\n";
    exit(1);
}

$orderDetail = $payment->orderDetail;
$order = $orderDetail->order;
$programCourse = $order->programCourse;

echo "Payment #1:\n";
echo "  Amount: $" . number_format($payment->amount, 0, ',', '.') . " CLP\n";
echo "  Installment: {$orderDetail->installment_number}/{$orderDetail->installments_number}\n";
echo "  Status: {$payment->status}\n\n";

echo "Program Course:\n";
echo "  ID: {$programCourse->id}\n";
echo "  Name: {$programCourse->name}\n";
echo "  Departure Date: " . ($programCourse->departure_date ? $programCourse->departure_date->format('Y-m-d') : 'N/A') . "\n";
echo "  Departure Year: " . ($programCourse->departure_date ? $programCourse->departure_date->year : 'N/A') . "\n";
echo "  Current Year: " . now()->year . "\n";
echo "  Is Same Year: " . (($programCourse->departure_date && $programCourse->departure_date->year === now()->year) ? 'YES' : 'NO') . "\n\n";

echo "Lógica de adjuntos:\n";
echo "  📄 Comprobante de pago: SIEMPRE\n";

// Determinar si envía contrato
$isSameYear = $programCourse->departure_date && $programCourse->departure_date->year === now()->year;
$isFirstInstallment = $orderDetail->installment_number == 1;

if ($isFirstInstallment && !$isSameYear) {
    echo "  📋 Contrato: SÍ (Primera cuota + Año próximo)\n";
} else {
    echo "  📋 Contrato: NO\n";
    if (!$isFirstInstallment) {
        echo "      Razón: No es primera cuota\n";
    }
    if ($isSameYear) {
        echo "      Razón: Es del mismo año\n";
    }
}

// Determinar si envía boleta BSale
if ($payment->bsale_document_id && $isSameYear) {
    echo "  🧾 Boleta BSale: SÍ (Mismo año + Tiene BSale)\n";
} else {
    echo "  🧾 Boleta BSale: NO\n";
    if (!$payment->bsale_document_id) {
        echo "      Razón: No tiene bsale_document_id\n";
    }
    if (!$isSameYear) {
        echo "      Razón: No es del mismo año\n";
    }
}

echo "\n✅ Análisis completado\n";
