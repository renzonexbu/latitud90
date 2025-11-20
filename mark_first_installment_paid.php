<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\ProgramSubscription;
use App\Models\InstallmentPlan;
use App\Models\Installment;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\Payment;
use App\Models\PaymentGateway;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

echo "=== Marcando primera cuota como pagada ===\n\n";

DB::beginTransaction();

try {
    // 1. Obtener suscripción
    $subscription = ProgramSubscription::find(5);
    if (!$subscription) {
        throw new Exception("Suscripción no encontrada");
    }

    echo "✓ Suscripción encontrada: {$subscription->id}\n";

    // 2. Obtener charge_program directamente desde VirtualPos
    echo "  Consultando VirtualPos...\n";
    $virtualPosService = new \App\Services\Subscription\VirtualPosSubscriptionService();
    $virtualPosData = $virtualPosService->getSubscription($subscription->virtualpos_subscription_id);

    $chargeProgram = $virtualPosData['suscription']['charge_program'] ?? [];
    if (empty($chargeProgram) || !isset($chargeProgram[0])) {
        throw new Exception("No hay charge_program en VirtualPos");
    }

    // Guardar charge_program en la BD para futuras sincronizaciones
    $subscription->update(['charge_program' => $chargeProgram]);

    $firstCharge = $chargeProgram[0];
    if (($firstCharge['status'] ?? '') !== 'pagado') {
        throw new Exception("El primer charge no está pagado en VirtualPos: " . ($firstCharge['status'] ?? 'N/A'));
    }

    echo "✓ Primer charge está pagado en VirtualPos\n";
    echo "  Charge ID: {$firstCharge['id']}\n";
    echo "  Amount: \${$firstCharge['amount']}\n\n";

    // 3. Verificar si ya existe Payment
    $existingPayment = Payment::where('external_payment_id', $firstCharge['id'])->first();
    if ($existingPayment) {
        echo "⚠ Ya existe un Payment para este charge (ID: {$existingPayment->id})\n";
        DB::rollBack();
        exit(0);
    }

    // 4. Obtener Order
    $order = Order::where('participant_id', $subscription->participant_id)
        ->where('program_id', $subscription->program_id)
        ->first();

    if (!$order) {
        throw new Exception("No se encontró Order para esta suscripción");
    }

    echo "✓ Order encontrada: {$order->id}\n";

    // 5. Obtener gateway de VirtualPos
    $gateway = PaymentGateway::where('code', 'virtualpos')->first();

    // 6. Crear OrderDetail
    $programCourse = $subscription->programCourse;
    $productName = $programCourse ? $programCourse->name : 'Cuota de suscripción';

    $orderDetail = OrderDetail::create([
        'order_id' => $order->id,
        'name' => $productName,
        'email' => $subscription->client_data['email'] ?? '',
        'quantity' => 1,
        'unit_price' => $firstCharge['amount'],
        'total_price' => $firstCharge['amount'],
        'discount' => 0,
        'discount_type' => null,
        'base_amount' => $firstCharge['amount'],
        'discount_amount' => 0,
        'amount' => $firstCharge['amount'],
        'installment_number' => 1,
        'installments_number' => count($chargeProgram),
        'status' => 'paid',
        'is_paid' => true,
        'paid_at' => now(),
        'payment_gateway_id' => $gateway ? $gateway->id : null,
    ]);

    echo "✓ OrderDetail creado: {$orderDetail->id}\n";

    // 7. Actualizar total de la orden
    $order->increment('total_amount', $firstCharge['amount']);

    // 8. Crear Payment

    $payment = Payment::create([
        'order_id' => $order->id,
        'order_detail_id' => $orderDetail->id,
        'payment_gateway_id' => $gateway ? $gateway->id : null,
        'external_payment_id' => $firstCharge['id'],
        'status' => 'completed',
        'amount' => $firstCharge['amount'],
        'installments_number' => 1,
        'installment_amount' => $firstCharge['amount'],
        'transaction_date' => $firstCharge['charge_date'] ?? now(),
        'card_type' => $subscription->payment_method['brand'] ?? null,
        'card_number' => $subscription->payment_method['last4CardDigit'] ?? null,
        'gateway_response' => json_encode($firstCharge),
        'email_sent' => false,
    ]);

    echo "✓ Payment creado: {$payment->id}\n";

    // 9. Marcar Installment como pagada
    $installmentPlan = InstallmentPlan::where('participant_id', $subscription->participant_id)
        ->where('program_id', $subscription->program_id)
        ->first();

    if (!$installmentPlan) {
        throw new Exception("No se encontró InstallmentPlan");
    }

    $firstInstallment = Installment::where('installment_plan_id', $installmentPlan->id)
        ->where('installment_number', 1)
        ->first();

    if (!$firstInstallment) {
        throw new Exception("No se encontró la primera cuota");
    }

    $firstInstallment->update([
        'status' => 'paid',
        'is_paid' => true,
        'paid_at' => now(),
        'payment_order_id' => $order->id,
        'payment_order_detail_id' => $orderDetail->id,
        'payment_id' => $payment->id,
    ]);

    echo "✓ Installment marcada como pagada: {$firstInstallment->id}\n\n";

    DB::commit();

    echo "✅ COMPLETADO\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "Payment ID: {$payment->id}\n";
    echo "Amount: \$" . number_format($firstCharge['amount'], 0, ',', '.') . " CLP\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

} catch (Exception $e) {
    DB::rollBack();
    echo "\n❌ ERROR: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}
