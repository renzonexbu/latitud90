<?php

namespace App\Console\Commands;

use App\Jobs\SendNoPaymentReminders;
use Illuminate\Console\Command;

class RunNoPaymentRemindersJob extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'reminders:send-no-payment';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Ejecutar manualmente el Job de recordatorios para participantes sin pagos iniciados';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🚀 Iniciando envío de recordatorios a participantes sin pagos...');

        try {
            // Ejecutar el Job sincrónicamente para ver los resultados inmediatamente
            $job = new SendNoPaymentReminders();
            $job->handle();

            $this->info('✅ Job ejecutado exitosamente');
            $this->line('Revisa los logs en storage/logs/laravel.log para más detalles');

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error('❌ Error ejecutando el Job: ' . $e->getMessage());
            $this->error($e->getTraceAsString());

            return Command::FAILURE;
        }
    }
}
