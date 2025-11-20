<?php

namespace App\Console\Commands;

use App\Jobs\SyncSubscriptionPaymentsJob;
use Illuminate\Console\Command;

class SyncSubscriptionPayments extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'subscriptions:sync-payments
                            {--subscription= : ID de suscripción específica a sincronizar}
                            {--all : Sincronizar todas las suscripciones activas}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sincronizar pagos de suscripciones con VirtualPos y registrar nuevos cobros';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $subscriptionId = $this->option('subscription');
        $all = $this->option('all');

        if (!$subscriptionId && !$all) {
            $this->error('Debes especificar --subscription=ID o --all');
            return Command::FAILURE;
        }

        $this->info('Iniciando sincronización de pagos de suscripciones...');

        try {
            if ($all) {
                $this->info('Sincronizando todas las suscripciones activas');
                SyncSubscriptionPaymentsJob::dispatch();
            } else {
                $this->info("Sincronizando suscripción ID: {$subscriptionId}");
                SyncSubscriptionPaymentsJob::dispatch((int) $subscriptionId);
            }

            $this->info('Job despachado exitosamente. Revisa los logs para ver el progreso.');
            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error('Error despachando job: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
