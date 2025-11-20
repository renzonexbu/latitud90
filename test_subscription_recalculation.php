<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\ProgramSubscription;
use App\Models\Participant;
use App\Models\EmergencyContact;
use App\Models\ProgramCourse;
use App\Services\Admin\Payments\CreateParticularPaymentService;
use App\Services\Admin\Payments\CreateRefundService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

echo "=== TEST DE RECALCULACIÓN DE SUSCRIPCIONES ===\n\n";

// 1. Listar suscripciones disponibles
echo "1. SUSCRIPCIONES DISPONIBLES:\n";
echo "-----------------------------\n";

$subscriptions = ProgramSubscription::with('participant', 'programCourse')->get();

if ($subscriptions->isEmpty()) {
    echo "No hay suscripciones en la base de datos.\n";
    exit;
}

foreach ($subscriptions as $subscription) {
    echo "ID: {$subscription->id}\n";
    echo "Participante: {$subscription->participant->first_name} {$subscription->participant->first_last_name}\n";
    echo "Programa: " . ($subscription->programCourse->name ?? 'N/A') . "\n";
    echo "Estado: {$subscription->status}\n";
    echo "VirtualPos ID: {$subscription->virtualpos_subscription_id}\n";
    echo "Cuotas en charge_program: " . count($subscription->charge_program ?? []) . "\n";
    echo "-----------------------------\n";
}

// 2. Seleccionar una suscripción ACTIVA para prueba
$subscription = $subscriptions->where('status', 'ACTIVA')->first();

if (!$subscription) {
    echo "No hay suscripciones activas disponibles para la prueba.\n";
    exit;
}

echo "\n2. SUSCRIPCIÓN SELECCIONADA PARA PRUEBA:\n";
echo "ID: {$subscription->id}\n";
echo "Participante: {$subscription->participant->first_name} {$subscription->participant->first_last_name}\n";
echo "Programa: " . ($subscription->programCourse->name ?? 'N/A') . "\n\n";

// 3. Simular que ya se pagaron 3 cuotas
echo "3. SIMULANDO 3 CUOTAS PAGADAS:\n";
echo "-------------------------------\n";

$chargeProgram = $subscription->charge_program ?? [];

if (empty($chargeProgram)) {
    echo "ERROR: La suscripción no tiene charge_program.\n";
    echo "Generando charge_program de prueba...\n";

    // Generar 12 cuotas de prueba
    $chargeProgram = [];
    $baseAmount = 100000;
    $startDate = Carbon::now()->addMonth();

    for ($i = 1; $i <= 12; $i++) {
        $chargeProgram[] = [
            'id' => 'cid_test_' . $i,
            'charge_date' => $startDate->copy()->addMonths($i - 1)->format('Y-m-d'),
            'amount' => $baseAmount,
            'status' => 'pendiente',
            'description' => "Cargo {$i} de 12"
        ];
    }
}

echo "Total de cuotas: " . count($chargeProgram) . "\n";

// Marcar las primeras 3 como pagadas
$paidCount = 0;
$pendingCount = 0;
$totalPaid = 0;
$totalPending = 0;

foreach ($chargeProgram as $index => &$charge) {
    if ($index < 3) {
        $charge['status'] = 'pagado';
        $paidCount++;
        $totalPaid += $charge['amount'];
    } else {
        $charge['status'] = 'pendiente';
        $pendingCount++;
        $totalPending += $charge['amount'];
    }
}

// Actualizar en base de datos
$subscription->update(['charge_program' => $chargeProgram]);

echo "✓ Cuotas pagadas: {$paidCount} (Total: $" . number_format($totalPaid, 0, ',', '.') . ")\n";
echo "✓ Cuotas pendientes: {$pendingCount} (Total: $" . number_format($totalPending, 0, ',', '.') . ")\n\n";

// 4. Simular PAGO PRESENCIAL
echo "4. SIMULANDO PAGO PRESENCIAL:\n";
echo "==============================\n";

$paymentAmount = 200000;
echo "Monto del pago: $" . number_format($paymentAmount, 0, ',', '.') . "\n";
echo "Monto pendiente actual: $" . number_format($totalPending, 0, ',', '.') . "\n";
echo "Monto pendiente después del pago: $" . number_format($totalPending - $paymentAmount, 0, ',', '.') . "\n";
echo "Cuotas pendientes: {$pendingCount}\n\n";

echo "Iniciando pago presencial...\n\n";

try {
    $paymentService = app(CreateParticularPaymentService::class);

    $paymentData = [
        'participant_id' => $subscription->participant_id,
        'program_id' => $subscription->program_id,
        'amount' => $paymentAmount,
        'payment_code' => 'TEST-PRES-' . time(),
        'transaction_date' => now()->format('Y-m-d H:i:s'),
        'authorization_code' => 'AUTH-' . time(),
        'presential_payment_type' => 'BX', // Tarjeta en oficina
        'notes' => 'Pago presencial de prueba para test de recalculación de suscripciones',
        'buyer_full_name' => $subscription->participant->first_name . ' ' . $subscription->participant->first_last_name,
        'buyer_email' => $subscription->participant->email ?? 'test@test.com',
        'buyer_document_type' => 1, // 1 = RUT (ID del tipo de documento)
        'buyer_document_number' => $subscription->participant->document_number,
    ];

    $result = $paymentService->execute($paymentData);

    if ($result['success']) {
        echo "✓ PAGO PRESENCIAL EXITOSO\n";
        echo "  Payment ID: {$result['payment']->id}\n";
        echo "  Order ID: {$result['order']->id}\n";
        echo "  Monto pagado: $" . number_format($result['payment']->amount, 0, ',', '.') . "\n\n";
    } else {
        echo "✗ ERROR EN PAGO PRESENCIAL\n";
        echo "  Error: " . ($result['error'] ?? 'Unknown') . "\n\n";
    }
} catch (\Exception $e) {
    echo "✗ EXCEPCIÓN EN PAGO PRESENCIAL\n";
    echo "  Error: {$e->getMessage()}\n";
    echo "  Archivo: {$e->getFile()}:{$e->getLine()}\n\n";
}

// Recargar suscripción para ver cambios
$subscription->refresh();

echo "\n5. ESTADO DESPUÉS DEL PAGO PRESENCIAL:\n";
echo "======================================\n";

$subscriptionsAfterPayment = ProgramSubscription::where('participant_id', $subscription->participant_id)
    ->where('program_id', $subscription->program_id)
    ->orderBy('created_at', 'desc')
    ->get();

echo "Total de suscripciones para este participante/programa: {$subscriptionsAfterPayment->count()}\n\n";

foreach ($subscriptionsAfterPayment as $sub) {
    echo "Suscripción ID: {$sub->id}\n";
    echo "  Estado: {$sub->status}\n";
    echo "  VirtualPos ID: {$sub->virtualpos_subscription_id}\n";
    echo "  Plan ID: {$sub->virtualpos_plan_id}\n";
    echo "  Monto por cuota: $" . number_format($sub->amount, 0, ',', '.') . "\n";
    echo "  Cuotas en charge_program: " . count($sub->charge_program ?? []) . "\n";
    if ($sub->status === 'CANCELADA') {
        echo "  Cancelada el: {$sub->cancelled_at}\n";
    }
    echo "\n";
}

// 6. Esperar un poco y simular REEMBOLSO
echo "\n6. SIMULANDO REEMBOLSO:\n";
echo "========================\n";

sleep(2); // Esperar para logs más claros

$refundAmount = 100000;
echo "Monto del reembolso: $" . number_format($refundAmount, 0, ',', '.') . "\n\n";

echo "Iniciando reembolso...\n\n";

try {
    $refundService = app(CreateRefundService::class);

    $refundData = [
        'participant_id' => $subscription->participant_id,
        'program_id' => $subscription->program_id,
        'amount' => $refundAmount,
        'payment_code' => 'TEST-REFUND-' . time(),
        'transaction_date' => now()->format('Y-m-d H:i:s'),
        'sii_code' => '61', // Nota de crédito
        'document_number' => time(),
        'total_amount' => $refundAmount,
        'notes' => 'Reembolso de prueba para test de recalculación de suscripciones',
    ];

    $result = $refundService->execute($refundData);

    if ($result['success']) {
        echo "✓ REEMBOLSO EXITOSO\n";
        echo "  Refund ID: {$result['refund']->id}\n";
        echo "  Order ID: {$result['order']->id}\n";
        echo "  Monto reembolsado: $" . number_format($result['refund']->amount, 0, ',', '.') . "\n\n";
    } else {
        echo "✗ ERROR EN REEMBOLSO\n";
        echo "  Error: " . ($result['error'] ?? 'Unknown') . "\n\n";
    }
} catch (\Exception $e) {
    echo "✗ EXCEPCIÓN EN REEMBOLSO\n";
    echo "  Error: {$e->getMessage()}\n";
    echo "  Archivo: {$e->getFile()}:{$e->getLine()}\n\n";
}

// 7. Estado final
echo "\n7. ESTADO FINAL:\n";
echo "=================\n";

$subscriptionsFinal = ProgramSubscription::where('participant_id', $subscription->participant_id)
    ->where('program_id', $subscription->program_id)
    ->orderBy('created_at', 'desc')
    ->get();

echo "Total de suscripciones: {$subscriptionsFinal->count()}\n\n";

foreach ($subscriptionsFinal as $sub) {
    echo "Suscripción ID: {$sub->id}\n";
    echo "  Estado: {$sub->status}\n";
    echo "  VirtualPos ID: {$sub->virtualpos_subscription_id}\n";
    echo "  Plan ID: {$sub->virtualpos_plan_id}\n";
    echo "  Monto por cuota: $" . number_format($sub->amount, 0, ',', '.') . "\n";
    echo "  Cuotas en charge_program: " . count($sub->charge_program ?? []) . "\n";
    echo "  Creada: {$sub->created_at}\n";
    if ($sub->status === 'CANCELADA') {
        echo "  Cancelada el: {$sub->cancelled_at}\n";
    }
    echo "\n";
}

echo "\n=== FIN DEL TEST ===\n";
echo "\nPara ver los logs detallados, ejecuta:\n";
echo "tail -100 storage/logs/laravel.log | grep -E '(SubscriptionRecalculation|Pago presencial|Reembolso)'\n";
