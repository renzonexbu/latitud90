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

        // =====================================================================
        // TEMPORALMENTE COMENTADOS PARA PRUEBAS - DESCOMENTAR EN PRODUCCIÓN
        // =====================================================================

        // // Procesar pagos pendientes cada minuto
        // $schedule->command('payments:process-pending')
        //     ->everyMinute()
        //     ->withoutOverlapping()
        //     ->runInBackground()
        //     ->appendOutputTo(storage_path('logs/pending-payments.log'));

        // // Sincronizar pagos de suscripciones con VirtualPos cada 5 minutos
        // // Detecta pagos nuevos (estado 'pagado' o 'procesando') y envía emails
        // $schedule->command('subscriptions:sync-payments --all')
        //     ->everyFiveMinutes()
        //     ->withoutOverlapping()
        //     ->runInBackground()
        //     ->appendOutputTo(storage_path('logs/subscription-payments-sync.log'));

        // // Sincronizar installments con charge_program de VirtualPos cada 8 horas
        // $schedule->command('subscription:sync-installments')
        //     ->cron('0 */8 * * *')
        //     ->withoutOverlapping()
        //     ->runInBackground()
        //     ->appendOutputTo(storage_path('logs/subscription-installments-sync.log'));

        // // Verificar cuotas vencidas cada minuto (para pruebas)
        // // TODO: Cambiar a cada 12 horas en producción
        // $schedule->command('installments:check-overdue')
        //     ->everyMinute()
        //     ->withoutOverlapping()
        //     ->runInBackground()
        //     ->appendOutputTo(storage_path('logs/overdue-installments.log'));

        // // Limpiar logs antiguos cada 12 horas
        // $schedule->command('logs:cleanup')
        //     ->twiceDaily(6, 18) // 6:00 AM y 6:00 PM
        //     ->withoutOverlapping()
        //     ->runInBackground()
        //     ->appendOutputTo(storage_path('logs/log-cleanup.log'));

        // // Verificar salud del sistema cada 6 horas
        // $schedule->command('system:health-check')
        //     ->everyFourHours()
        //     ->withoutOverlapping()
        //     ->runInBackground()
        //     ->appendOutputTo(storage_path('logs/system-health.log'));

        // // Actualizar opciones de pago y cuotas automáticamente cada día a las 6:00 AM
        // $schedule->command('payment-options:update')
        //     ->everyMinute()
        //     ->withoutOverlapping()
        //     ->runInBackground()
        //     ->appendOutputTo(storage_path('logs/payment-options-update.log'));

        // // Procesar emails de marketing 2 veces al día (6:00 AM y 6:00 PM)
        // $schedule->command('marketing:process-emails')
        //     ->twiceDaily(6, 18)
        //     ->withoutOverlapping()
        //     ->runInBackground()
        //     ->appendOutputTo(storage_path('logs/marketing-emails.log'));

        // // Reporte mensual de participantes sin pagos - primer día de cada mes a las 9:00 AM
        // $schedule->command('participants:monthly-without-payments')
        //     ->monthlyOn(1, '09:00')
        //     ->withoutOverlapping()
        //     ->runInBackground()
        //     ->appendOutputTo(storage_path('logs/monthly-participants-without-payments.log'));

        // // Enviar recordatorios a participantes sin pagos iniciados - cada día a las 9:00 AM
        // $schedule->command('reminders:send-no-payment')
        //     ->dailyAt('09:00')
        //     ->withoutOverlapping()
        //     ->runInBackground()
        //     ->appendOutputTo(storage_path('logs/no-payment-reminders.log'));

        // =====================================================================
        // ACTIVO PARA PRUEBAS - Envío de emails de confirmación de pago
        // =====================================================================

        // Enviar emails pendientes de pagos exitosos - cada minuto
        // El comando aplica un delay configurable (default 10 min) antes de enviar
        // para permitir que BSale genere la boleta
        $schedule->command('payments:send-pending-emails')
            ->everyMinute()
            ->withoutOverlapping()
            ->runInBackground()
            ->appendOutputTo(storage_path('logs/pending-payment-emails.log'));
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__ . '/Commands');

        require base_path('routes/console.php');
    }
}
