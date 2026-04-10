<?php

namespace App\Console\Commands;

use App\Models\ProgramSubscription;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ExpireStaleSubscriptions extends Command
{
    protected $signature = 'subscriptions:expire-stale {--minutes=60 : Minutos para considerar una suscripción como expirada}';
    protected $description = 'Cancela suscripciones atascadas en SUSCRIBIENDO que superaron el tiempo máximo de espera';

    public function handle(): int
    {
        $minutes = (int) $this->option('minutes');
        $cutoff = Carbon::now()->subMinutes($minutes);

        $stale = ProgramSubscription::where('status', 'SUSCRIBIENDO')
            ->where('created_at', '<', $cutoff)
            ->get();

        if ($stale->isEmpty()) {
            $this->info('No hay suscripciones atascadas.');
            return 0;
        }

        $this->info("Encontradas {$stale->count()} suscripción(es) atascadas (>{$minutes} min).");

        foreach ($stale as $subscription) {
            try {
                DB::beginTransaction();

                $subscription->update([
                    'status' => 'CANCELADA',
                    'cancelled_at' => now(),
                ]);

                // Cancelar orden pendiente asociada
                DB::table('orders')
                    ->where('subscription_id', $subscription->id)
                    ->where('status', 'pending')
                    ->update(['status' => 'cancelled', 'updated_at' => now()]);

                // Cancelar installment plan activo
                $planIds = DB::table('installment_plans')
                    ->where('participant_id', $subscription->participant_id)
                    ->where('program_id', $subscription->program_id)
                    ->where('status', 'active')
                    ->pluck('id');

                if ($planIds->isNotEmpty()) {
                    DB::table('installment_plans')
                        ->whereIn('id', $planIds)
                        ->update(['status' => 'cancelled', 'updated_at' => now()]);

                    DB::table('installments')
                        ->whereIn('installment_plan_id', $planIds)
                        ->where('status', 'pending')
                        ->update(['status' => 'cancelled', 'updated_at' => now()]);
                }

                DB::commit();

                $this->line("  #{$subscription->id} (participante {$subscription->participant_id}) → CANCELADA");

                Log::info('Suscripción expirada automáticamente', [
                    'subscription_id' => $subscription->id,
                    'participant_id' => $subscription->participant_id,
                    'program_id' => $subscription->program_id,
                    'created_at' => $subscription->created_at,
                    'minutes_stale' => $subscription->created_at->diffInMinutes(now()),
                ]);
            } catch (\Exception $e) {
                DB::rollBack();
                $this->error("  Error en #{$subscription->id}: {$e->getMessage()}");
                Log::error('Error expirando suscripción', [
                    'subscription_id' => $subscription->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $this->info('Proceso completado.');
        return 0;
    }
}
