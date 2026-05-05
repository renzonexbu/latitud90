<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use Carbon\Carbon;

class Kernel extends ConsoleKernel
{
    /**
     * Obtener ruta de log con fecha
     * Formato: logs/schedules/nombre_comando_27_01_26.log
     */
    protected function getScheduleLogPath(string $commandName): string
    {
        $date = Carbon::now('America/Santiago')->format('d_m_y');
        $logDir = storage_path('logs/schedules');

        // Crear directorio si no existe
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }

        return "{$logDir}/{$commandName}_{$date}.log";
    }

    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // =====================================================================
        // COMANDOS ACTIVOS EN PRODUCCIÓN
        // =====================================================================

        // 1. Enviar emails pendientes de pagos exitosos - cada minuto
        // El comando aplica un delay configurable (default 10 min) antes de enviar
        // para permitir que BSale genere la boleta
        // withoutOverlapping(5) = lock expira en 5 min para evitar bloqueos permanentes
        $schedule->command('payments:send-pending-emails')
            ->everyMinute()
            ->withoutOverlapping(5)
            ->runInBackground()
            ->appendOutputTo($this->getScheduleLogPath('pending_payment_emails'));

        // 2. Sincronizar pagos de suscripciones con VirtualPos cada 5 minutos
        // Detecta pagos nuevos (estado 'pagado' o 'procesando') y envía emails
        // withoutOverlapping(15) = lock expira en 15 min (puede tardar con muchas suscripciones)
        $schedule->command('subscriptions:sync-payments --all')
            ->everyFiveMinutes()
            ->withoutOverlapping(15)
            ->runInBackground()
            ->appendOutputTo($this->getScheduleLogPath('subscription_payments_sync'));

        // 3. Procesar cola de solicitudes BSale cada 5 minutos
        // Genera boletas electrónicas para los pagos encolados
        // Separado del flujo de emails para evitar problemas si BSale falla
        // withoutOverlapping(10) = lock expira en 10 min (BSale puede ser lento)
        $schedule->command('bsale:process --limit=30')
            ->everyFiveMinutes()
            ->withoutOverlapping(10)
            ->runInBackground()
            ->appendOutputTo($this->getScheduleLogPath('bsale_process'));

        // 4. Expirar suscripciones atascadas en SUSCRIBIENDO (>60 min)
        // Libera al cliente para reintentar el pago sin intervención manual
        $schedule->command('subscriptions:expire-stale --minutes=60')
            ->everyFiveMinutes()
            ->withoutOverlapping(5)
            ->runInBackground()
            ->appendOutputTo($this->getScheduleLogPath('expire_stale_subscriptions'));

        // 5. Limpieza de logs cada noche a las 3:00 AM
        // Conserva últimos 7 días; libera espacio en disco
        $schedule->command('logs:cleanup --days=7')
            ->dailyAt('03:00')
            ->timezone('America/Santiago')
            ->withoutOverlapping()
            ->runInBackground()
            ->appendOutputTo($this->getScheduleLogPath('logs_cleanup'));

        // 6. Procesar emails de marketing 2 veces al día (6:00 AM y 6:00 PM)
        // Sincroniza emails de clientes para campañas de marketing
        $schedule->command('marketing:process-emails')
            ->twiceDaily(6, 18)
            ->withoutOverlapping(30)
            ->runInBackground()
            ->appendOutputTo($this->getScheduleLogPath('marketing_emails'));

        // 5. Enviar recordatorios a participantes sin pagos - cada día a las 9:00 AM
        // Notifica a contactos de emergencia sobre participantes sin pagos ecommerce
        // COMENTADO TEMPORALMENTE - 2026-01-29
        // $schedule->command('reminders:send-no-payment')
        //     ->dailyAt('09:00')
        //     ->withoutOverlapping()
        //     ->runInBackground()
        //     ->appendOutputTo($this->getScheduleLogPath('no_payment_reminders'));

        // =====================================================================
        // COMANDOS TEMPORALMENTE COMENTADOS
        // =====================================================================

        // // Procesar pagos pendientes cada minuto
        // $schedule->command('payments:process-pending')
        //     ->everyMinute()
        //     ->withoutOverlapping()
        //     ->runInBackground()
        //     ->appendOutputTo($this->getScheduleLogPath('pending_payments'));

        // // Sincronizar installments con charge_program de VirtualPos cada 8 horas
        // // NOTA: Redundante si subscriptions:sync-payments está activo
        // $schedule->command('subscription:sync-installments')
        //     ->cron('0 */8 * * *')
        //     ->withoutOverlapping()
        //     ->runInBackground()
        //     ->appendOutputTo($this->getScheduleLogPath('subscription_installments_sync'));

        // // Verificar cuotas vencidas cada 12 horas
        // $schedule->command('installments:check-overdue')
        //     ->twiceDaily(6, 18)
        //     ->withoutOverlapping()
        //     ->runInBackground()
        //     ->appendOutputTo($this->getScheduleLogPath('overdue_installments'));

        // // Limpiar logs antiguos cada 12 horas
        // $schedule->command('logs:cleanup')
        //     ->twiceDaily(6, 18)
        //     ->withoutOverlapping()
        //     ->runInBackground()
        //     ->appendOutputTo($this->getScheduleLogPath('log_cleanup'));

        // // Verificar salud del sistema cada 6 horas
        // $schedule->command('system:health-check')
        //     ->everyFourHours()
        //     ->withoutOverlapping()
        //     ->runInBackground()
        //     ->appendOutputTo($this->getScheduleLogPath('system_health'));

        // Actualizar opciones de pago diariamente a las 6:00 AM
        $schedule->command('payment-options:update')
            ->dailyAt('06:00')
            ->withoutOverlapping()
            ->runInBackground()
            ->appendOutputTo($this->getScheduleLogPath('payment_options_update'));

        // // Reporte mensual de participantes sin pagos
        // $schedule->command('participants:monthly-without-payments')
        //     ->monthlyOn(1, '09:00')
        //     ->withoutOverlapping()
        //     ->runInBackground()
        //     ->appendOutputTo($this->getScheduleLogPath('monthly_participants_without_payments'));
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
