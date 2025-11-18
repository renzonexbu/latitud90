<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ProgramSubscription;
use App\Models\Order;
use App\Models\InstallmentPlan;
use App\Services\Subscription\VirtualPosSubscriptionService;
use Illuminate\Support\Facades\Log;

class SyncSubscriptionsWithVirtualPos extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'subscription:sync-virtualpos {--all : Sincronizar todas las suscripciones} {--subscription_id= : ID de suscripción específica}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sincroniza las cuotas (installments) de las suscripciones con los datos de VirtualPos';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔄 Iniciando sincronización de suscripciones con VirtualPos...');

        // Determinar qué suscripciones sincronizar
        if ($this->option('subscription_id')) {
            $subscriptions = ProgramSubscription::where('id', $this->option('subscription_id'))->get();
        } elseif ($this->option('all')) {
            $subscriptions = ProgramSubscription::whereNotNull('virtualpos_subscription_id')->get();
        } else {
            // Por defecto, solo suscripciones activas
            $subscriptions = ProgramSubscription::whereIn('status', ['ACTIVA', 'SUSCRIBIENDO'])
                ->whereNotNull('virtualpos_subscription_id')
                ->get();
        }

        if ($subscriptions->isEmpty()) {
            $this->warn('⚠️  No se encontraron suscripciones para sincronizar.');
            return 0;
        }

        $this->info("📊 Encontradas {$subscriptions->count()} suscripciones para sincronizar.");

        $bar = $this->output->createProgressBar($subscriptions->count());
        $bar->start();

        $virtualPosService = new VirtualPosSubscriptionService();
        $successCount = 0;
        $errorCount = 0;

        foreach ($subscriptions as $subscription) {
            try {
                // Consultar el estado actual en VirtualPos
                $virtualPosResponse = $virtualPosService->getSubscription($subscription->virtualpos_subscription_id);

                if (!isset($virtualPosResponse['suscription'])) {
                    $this->newLine();
                    $this->error("❌ Error en suscripción ID {$subscription->id}: Respuesta inválida de VirtualPos");
                    $errorCount++;
                    $bar->advance();
                    continue;
                }

                $virtualPosStatus = $virtualPosResponse['suscription']['status'] ?? null;
                $chargeProgram = $virtualPosResponse['suscription']['charge_program'] ?? [];

                // Actualizar estado de la suscripción
                $subscription->update([
                    'status' => $virtualPosStatus,
                    'payment_method' => $virtualPosResponse['suscription']['payment_method'] ?? null,
                    'charge_program' => $chargeProgram,
                    'api_response' => $virtualPosResponse,
                ]);

                // Buscar la orden y el plan de cuotas
                $order = Order::where('participant_id', $subscription->participant_id)
                    ->where('program_id', $subscription->program_id)
                    ->where('order_number', 'LIKE', 'SUB-%')
                    ->orderBy('created_at', 'desc')
                    ->first();

                if (!$order) {
                    $this->newLine();
                    $this->warn("⚠️  No se encontró orden para suscripción ID {$subscription->id}");
                    $errorCount++;
                    $bar->advance();
                    continue;
                }

                $installmentPlan = InstallmentPlan::where('order_id', $order->id)
                    ->where('participant_id', $subscription->participant_id)
                    ->first();

                if (!$installmentPlan) {
                    $this->newLine();
                    $this->warn("⚠️  No se encontró plan de cuotas para suscripción ID {$subscription->id}");
                    $errorCount++;
                    $bar->advance();
                    continue;
                }

                // Sincronizar cuotas con VirtualPos
                if (empty($chargeProgram)) {
                    $this->newLine();
                    $this->warn("⚠️  No se recibió charge_program para suscripción ID {$subscription->id}");
                    $errorCount++;
                    $bar->advance();
                    continue;
                }

                $updatedCount = 0;
                $createdCount = 0;

                foreach ($chargeProgram as $index => $charge) {
                    // Buscar si ya existe una cuota con este virtualpos_charge_id
                    $installment = $installmentPlan->installments()
                        ->where('virtualpos_charge_id', $charge['id'])
                        ->first();

                    // Si no se encuentra por charge_id, buscar por installment_number (para cuotas antiguas)
                    if (!$installment) {
                        $installment = $installmentPlan->installments()
                            ->where('installment_number', $index + 1)
                            ->first();
                    }

                    // Convertir el status de VirtualPos a nuestro formato
                    $status = 'pending';
                    $isPaid = false;
                    if (isset($charge['status'])) {
                        if ($charge['status'] === 'pagado') {
                            $status = 'paid';
                            $isPaid = true;
                        }
                    }

                    if ($installment) {
                        // Actualizar cuota existente (incluyendo el virtualpos_charge_id si no lo tenía)
                        $installment->update([
                            'virtualpos_charge_id' => $charge['id'],
                            'amount' => $charge['amount'],
                            'due_date' => $charge['charge_date'],
                            'status' => $status,
                            'is_paid' => $isPaid,
                            'paid_at' => $isPaid ? ($installment->paid_at ?? now()) : null,
                        ]);
                        $updatedCount++;
                    } else {
                        // Crear nueva cuota si no existe
                        $installmentPlan->installments()->create([
                            'installment_number' => $index + 1,
                            'virtualpos_charge_id' => $charge['id'],
                            'amount' => $charge['amount'],
                            'due_date' => $charge['charge_date'],
                            'status' => $status,
                            'is_paid' => $isPaid,
                            'paid_at' => $isPaid ? now() : null,
                        ]);
                        $createdCount++;
                    }
                }

                Log::info("Suscripción sincronizada", [
                    'subscription_id' => $subscription->id,
                    'virtualpos_subscription_id' => $subscription->virtualpos_subscription_id,
                    'status' => $virtualPosStatus,
                    'cuotas_actualizadas' => $updatedCount,
                    'cuotas_creadas' => $createdCount,
                ]);

                $successCount++;

            } catch (\Exception $e) {
                $this->newLine();
                $this->error("❌ Error sincronizando suscripción ID {$subscription->id}: {$e->getMessage()}");
                Log::error("Error sincronizando suscripción", [
                    'subscription_id' => $subscription->id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
                $errorCount++;
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        // Resumen
        $this->info("✅ Sincronización completada:");
        $this->table(
            ['Resultado', 'Cantidad'],
            [
                ['✅ Exitosas', $successCount],
                ['❌ Errores', $errorCount],
                ['📊 Total', $subscriptions->count()],
            ]
        );

        return 0;
    }
}
