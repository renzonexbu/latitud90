<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class RunScheduleForeverCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'schedule:run-forever 
                           {--sleep=60 : Segundos entre ejecuciones}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Ejecuta el schedule de Laravel de manera permanente en un loop infinito';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $sleepSeconds = (int) $this->option('sleep');
        
        $this->info('🔄 Iniciando ejecución permanente del schedule...');
        $this->info("⏰ Intervalo entre ejecuciones: {$sleepSeconds} segundos");
        $this->info('🛑 Presiona Ctrl+C para detener');
        $this->info('');
        
        $executionCount = 0;
        
        while (true) {
            try {
                $executionCount++;
                $now = Carbon::now()->setTimezone('America/Santiago');
                
                $this->info("🔄 Ejecución #{$executionCount} - {$now->format('Y-m-d H:i:s')}");
                
                // Ejecutar el schedule
                $this->call('schedule:run');
                
                $this->info("✅ Ejecución #{$executionCount} completada");
                $this->info("⏳ Esperando {$sleepSeconds} segundos...");
                $this->info('');
                
                // Esperar antes de la siguiente ejecución
                sleep($sleepSeconds);
                
            } catch (\Exception $e) {
                $this->error("❌ Error en ejecución #{$executionCount}: " . $e->getMessage());
                Log::error('Error en RunScheduleForeverCommand', [
                    'execution_count' => $executionCount,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                
                // Esperar un poco más en caso de error
                sleep($sleepSeconds * 2);
            }
        }
    }
}
