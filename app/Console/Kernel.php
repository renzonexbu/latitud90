<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // $schedule->command('inspire')->hourly();
        
        // Procesar pagos pendientes cada minuto
        $schedule->command('payments:process-pending')
            ->everyMinute()
            ->withoutOverlapping()
            ->runInBackground()
            ->appendOutputTo(storage_path('logs/pending-payments.log'));
            
        // Verificar cuotas vencidas cada minuto (para pruebas)
        // TODO: Cambiar a cada 12 horas en producción
        $schedule->command('installments:check-overdue')
            ->everyMinute()
            ->withoutOverlapping()
            ->runInBackground()
            ->appendOutputTo(storage_path('logs/overdue-installments.log'));
            
        // Limpiar logs antiguos cada 12 horas
        $schedule->command('logs:cleanup')
            ->twiceDaily(6, 18) // 6:00 AM y 6:00 PM
            ->withoutOverlapping()
            ->runInBackground()
            ->appendOutputTo(storage_path('logs/log-cleanup.log'));
            
        // Verificar salud del sistema cada 6 horas
        $schedule->command('system:health-check')
            ->everyFourHours()
            ->withoutOverlapping()
            ->runInBackground()
            ->appendOutputTo(storage_path('logs/system-health.log'));
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
