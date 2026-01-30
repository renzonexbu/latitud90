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
                            {--all : Sincronizar todas las suscripciones activas}
                            {--queue : Ejecutar en cola en lugar de síncronamente}
                            {--no-emails : No enviar correos de pago exitoso}';

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
        $useQueue = $this->option('queue');
        $sendEmails = !$this->option('no-emails'); // Si --no-emails está activo, no enviar emails

        if (!$subscriptionId && !$all) {
            $this->error('Debes especificar --subscription=ID o --all');
            return Command::FAILURE;
        }

        $this->info('Iniciando sincronización de pagos de suscripciones...');

        if (!$sendEmails) {
            $this->warn('Modo sin envío de emails activado (--no-emails)');
        }

        try {
            if ($useQueue) {
                // Ejecutar en cola (asíncrono)
                if ($all) {
                    $this->info('Despachando job para todas las suscripciones activas...');
                    SyncSubscriptionPaymentsJob::dispatch(null, $sendEmails);
                } else {
                    $this->info("Despachando job para suscripción ID: {$subscriptionId}");
                    SyncSubscriptionPaymentsJob::dispatch((int) $subscriptionId, $sendEmails);
                }
                $this->info('Job despachado. Revisa los logs para ver el progreso.');
            } else {
                // Ejecutar de forma síncrona (inmediato)
                if ($all) {
                    $this->info('Sincronizando todas las suscripciones activas...');
                    SyncSubscriptionPaymentsJob::dispatchSync(null, $sendEmails);
                } else {
                    $this->info("Sincronizando suscripción ID: {$subscriptionId}");
                    SyncSubscriptionPaymentsJob::dispatchSync((int) $subscriptionId, $sendEmails);
                }
                $this->info('Sincronización completada.');
            }

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error('Error: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
