<?php

namespace App\Console\Commands;

use App\Models\ProgramSubscription;
use App\Models\Installment;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SyncSubscriptionInstallments extends Command
{
    protected $signature = 'subscription:sync-installments';
    protected $description = 'Sincronizar estado de installments con charge_program de VirtualPos';

    public function handle()
    {
        $this->info('🔄 Sincronizando installments con charge_program...');
        $this->newLine();

        $subscriptions = ProgramSubscription::whereIn('status', ['ACTIVA', 'SUSCRIBIENDO'])
            ->with(['installmentPlan.installments'])
            ->get();

        if ($subscriptions->isEmpty()) {
            $this->warn('⚠️  No se encontraron suscripciones activas');
            return 0;
        }

        $this->info("📊 Total de suscripciones a procesar: {$subscriptions->count()}");
        $this->newLine();

        $totalUpdated = 0;

        foreach ($subscriptions as $subscription) {
            $this->line("───────────────────────────────────────────────");
            $this->info("📋 Suscripción ID: {$subscription->id}");
            $this->line("   Participante: {$subscription->participant->full_name}");

            // Obtener charge_program
            $chargeProgram = $subscription->charge_program;

            if (empty($chargeProgram)) {
                $this->warn("   ⚠️  No hay charge_program");
                continue;
            }

            // Obtener plan de cuotas
            $installmentPlan = $subscription->getInstallmentPlanAttribute();

            if (!$installmentPlan) {
                $this->warn("   ⚠️  No se encontró plan de cuotas");
                continue;
            }

            $installments = $installmentPlan->installments;

            if ($installments->isEmpty()) {
                $this->warn("   ⚠️  No hay cuotas en el plan");
                continue;
            }

            $this->line("   📦 Procesando {$installments->count()} cuotas...");

            $updatedCount = 0;

            foreach ($chargeProgram as $charge) {
                // Buscar la cuota correspondiente por virtualpos_charge_id
                $installment = $installments->firstWhere('virtualpos_charge_id', $charge['id']);

                if (!$installment) {
                    $this->warn("   ⚠️  No se encontró installment para charge_id: {$charge['id']}");
                    continue;
                }

                // Verificar si el status cambió
                $currentStatus = $installment->status;
                $currentIsPaid = $installment->is_paid;

                $newStatus = $charge['status'] === 'pagado' ? 'paid' : 'pending';
                $newIsPaid = $charge['status'] === 'pagado';

                // No degradar cuotas con pago local (payment_id / paid) si VP ya no dice "pagado"
                // (p. ej. suscripción cancelada y charge_program marcado cancelado/pendiente).
                $hasLocalPayment = $installment->is_paid
                    || $installment->payment_id
                    || $installment->status === 'paid';

                if ($hasLocalPayment && $newStatus !== 'paid') {
                    $this->line("   ⏭ Cuota #{$installment->installment_number}: se mantiene paid (tiene pago local)");
                    continue;
                }

                if ($currentStatus !== $newStatus || $currentIsPaid !== $newIsPaid) {
                    // Actualizar la cuota
                    $installment->update([
                        'status' => $newStatus,
                        'is_paid' => $newIsPaid,
                        'paid_at' => $newIsPaid ? ($installment->paid_at ?? now()) : null,
                    ]);

                    $this->line("   ✅ Cuota #{$installment->installment_number}: {$currentStatus} → {$newStatus}");
                    $updatedCount++;
                    $totalUpdated++;
                }
            }

            if ($updatedCount === 0) {
                $this->line("   ✓ Todas las cuotas ya estaban sincronizadas");
            } else {
                $this->info("   ✓ {$updatedCount} cuotas actualizadas");
            }
        }

        $this->newLine();
        $this->line("═══════════════════════════════════════════════");

        if ($totalUpdated > 0) {
            $this->info("✅ Sincronización completada: {$totalUpdated} cuotas actualizadas");
        } else {
            $this->info("✓ Todas las cuotas ya estaban sincronizadas");
        }

        return 0;
    }
}
