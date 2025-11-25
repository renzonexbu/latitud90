<?php

namespace App\Console\Commands;

use App\Models\ProgramSubscription;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\Payment;
use App\Models\Installment;
use App\Services\Subscription\VirtualPosSubscriptionService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ReviewSubscriptions extends Command
{
    protected $signature = 'subscriptions:review';
    protected $description = 'Revisar todas las suscripciones y mostrar sus mensualidades en logs';

    public function handle()
    {
        $this->info('🔍 Revisando todas las suscripciones...');
        Log::info('========================================');
        Log::info('REVISIÓN DE SUSCRIPCIONES - ' . now());
        Log::info('========================================');

        $subscriptions = ProgramSubscription::with(['participant', 'programCourse.program'])
            ->whereNotNull('virtualpos_subscription_id')
            ->get();

        $this->info("📊 Total suscripciones encontradas: {$subscriptions->count()}");
        Log::info("Total suscripciones encontradas: {$subscriptions->count()}");

        if ($subscriptions->isEmpty()) {
            $this->warn('⚠️ No hay suscripciones en la base de datos');
            Log::warning('No hay suscripciones en la base de datos');
            return;
        }

        $virtualPosService = new VirtualPosSubscriptionService();

        foreach ($subscriptions as $subscription) {
            $this->newLine();
            $this->info("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
            $this->info("📝 SUSCRIPCIÓN #{$subscription->id}");
            $this->info("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");

            Log::info('');
            Log::info('========================================');
            Log::info("SUSCRIPCIÓN #{$subscription->id}");
            Log::info('========================================');

            // Información básica
            $participantName = $subscription->participant ? $subscription->participant->full_name : 'N/A';
            $programName = $subscription->programCourse && $subscription->programCourse->program
                ? $subscription->programCourse->program->name
                : 'N/A';

            $this->info("👤 Participante: {$participantName}");
            $this->info("📚 Programa: {$programName}");
            $this->info("💰 Monto Total: $" . number_format($subscription->total_amount, 0, ',', '.'));
            $this->info("📅 Cuotas: {$subscription->installments}");
            $this->info("📊 Estado: {$subscription->status}");
            $this->info("🔑 VirtualPos ID: {$subscription->virtualpos_subscription_id}");

            Log::info("Participante: {$participantName}");
            Log::info("Programa: {$programName}");
            Log::info("Monto Total: {$subscription->total_amount}");
            Log::info("Cuotas: {$subscription->installments}");
            Log::info("Estado: {$subscription->status}");
            Log::info("VirtualPos ID: {$subscription->virtualpos_subscription_id}");

            // Mostrar objeto completo de la suscripción
            Log::info("OBJETO COMPLETO SUSCRIPCIÓN:");
            Log::info(json_encode($subscription->toArray(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

            // Obtener datos de VirtualPos
            $this->newLine();
            $this->info("🌐 Consultando VirtualPos...");

            try {
                $virtualPosData = $virtualPosService->getSubscription($subscription->virtualpos_subscription_id);
                $chargeProgram = $virtualPosData['charge_program'] ?? [];

                $this->info("✅ VirtualPos respondió: " . count($chargeProgram) . " cargos programados");
                Log::info("VirtualPos respondió con " . count($chargeProgram) . " cargos");

                // Mostrar respuesta completa de VirtualPos
                Log::info("RESPUESTA COMPLETA DE VIRTUALPOS:");
                Log::info(json_encode($virtualPosData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

                // Mostrar cada cargo/mensualidad
                $this->newLine();
                $this->info("💳 MENSUALIDADES EN VIRTUALPOS:");
                Log::info("MENSUALIDADES EN VIRTUALPOS:");

                foreach ($chargeProgram as $index => $charge) {
                    $chargeNumber = $index + 1;
                    $amount = $charge['amount'] ?? 0;
                    $status = $charge['status'] ?? 'unknown';
                    $chargeDate = $charge['charge_date'] ?? 'N/A';
                    $chargeId = $charge['id'] ?? 'N/A';

                    $statusIcon = $status === 'pagado' ? '✅' : ($status === 'pendiente' ? '⏳' : '❌');

                    $this->info("  {$statusIcon} Cuota {$chargeNumber}/{$subscription->installments}: $" . number_format($amount, 0, ',', '.') . " - {$status} - Fecha: {$chargeDate}");

                    Log::info("  Cuota {$chargeNumber}: [ID: {$chargeId}] [Monto: {$amount}] [Estado: {$status}] [Fecha: {$chargeDate}]");
                }

            } catch (\Exception $e) {
                $this->error("❌ Error consultando VirtualPos: {$e->getMessage()}");
                Log::error("Error consultando VirtualPos para suscripción {$subscription->id}: {$e->getMessage()}");
            }

            // Revisar datos en BD local
            $this->newLine();
            $this->info("💾 DATOS EN BASE DE DATOS LOCAL:");
            Log::info("DATOS EN BASE DE DATOS LOCAL:");

            // Orders
            $orders = Order::where('participant_id', $subscription->participant_id)
                ->where('program_id', $subscription->program_id)
                ->get();

            $this->info("  📦 Orders: {$orders->count()}");
            Log::info("  Orders encontradas: {$orders->count()}");

            foreach ($orders as $order) {
                $this->info("     - Order #{$order->id}: {$order->order_number} - {$order->status} - Total: $" . number_format($order->total_amount, 0, ',', '.'));
                Log::info("     Order #{$order->id}: {$order->order_number} | Status: {$order->status} | Total: {$order->total_amount} | Final: {$order->final_amount}");

                // OrderDetails de esta orden
                $orderDetails = OrderDetail::where('order_id', $order->id)->get();
                $this->info("        📄 OrderDetails: {$orderDetails->count()}");
                Log::info("        OrderDetails: {$orderDetails->count()}");

                foreach ($orderDetails as $detail) {
                    $paidIcon = $detail->is_paid ? '✅' : '⏳';
                    $this->info("           {$paidIcon} Detail #{$detail->id}: Cuota {$detail->installment_number} - $" . number_format($detail->amount, 0, ',', '.') . " - {$detail->status}");
                    Log::info("           Detail #{$detail->id}: Cuota {$detail->installment_number} | Monto: {$detail->amount} | Status: {$detail->status} | Paid: " . ($detail->is_paid ? 'Si' : 'No') . " | Buyer: {$detail->name}");
                }

                // Payments de esta orden
                $payments = Payment::where('order_id', $order->id)->get();
                $this->info("        💰 Payments: {$payments->count()}");
                Log::info("        Payments: {$payments->count()}");

                foreach ($payments as $payment) {
                    $this->info("           💳 Payment #{$payment->id}: $" . number_format($payment->amount, 0, ',', '.') . " - {$payment->status} - External ID: {$payment->external_payment_id}");
                    Log::info("           Payment #{$payment->id}: Monto: {$payment->amount} | Status: {$payment->status} | External: {$payment->external_payment_id} | Gateway: {$payment->payment_gateway_id}");
                }
            }

            // Installments
            $installments = Installment::whereHas('installmentPlan', function ($query) use ($subscription) {
                $query->where('participant_id', $subscription->participant_id)
                    ->where('program_id', $subscription->program_id);
            })->get();

            $this->info("  📅 Installments: {$installments->count()}");
            Log::info("  Installments: {$installments->count()}");

            foreach ($installments as $installment) {
                $paidIcon = $installment->is_paid ? '✅' : '⏳';
                $this->info("     {$paidIcon} Installment #{$installment->id}: Cuota {$installment->installment_number} - $" . number_format($installment->amount, 0, ',', '.') . " - {$installment->status}");
                Log::info("     Installment #{$installment->id}: Cuota {$installment->installment_number} | Monto: {$installment->amount} | Status: {$installment->status} | VirtualPos ID: {$installment->virtualpos_charge_id}");
            }

            $this->newLine();
        }

        $this->newLine();
        $this->info('✅ Revisión completada. Revisa el archivo de logs para más detalles.');
        Log::info('========================================');
        Log::info('REVISIÓN COMPLETADA');
        Log::info('========================================');
    }
}
