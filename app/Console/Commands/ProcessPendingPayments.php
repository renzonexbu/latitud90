<?php

namespace App\Console\Commands;

use App\Jobs\ProcessPendingPaymentsJob;
use Illuminate\Console\Command;

class ProcessPendingPayments extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'payments:process-pending';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Procesar pagos pendientes de validación';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Iniciando procesamiento de pagos pendientes...');
        
        try {
            ProcessPendingPaymentsJob::dispatch();
            
            $this->info('Job de procesamiento de pagos pendientes enviado a la cola.');
        } catch (\Exception $e) {
            $this->error('Error al procesar pagos pendientes: ' . $e->getMessage());
            return 1;
        }
        
        return 0;
    }
}
