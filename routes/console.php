<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use App\Services\Commands\InstallmentOverdueService;
use App\Services\Commands\LogCleanupService;
use App\Services\Commands\SystemHealthService;
use App\Services\Commands\PaymentProcessingService;
use App\Services\Commands\UpdatePaymentOptionsService;
use App\Services\Commands\MarketingMailsService;

/*
|--------------------------------------------------------------------------
| Console Routes
|--------------------------------------------------------------------------
|
| This file is where you may define all of your Closure based console
| commands. Each Closure is bound to a command instance allowing a
| simple approach to interacting with each command's IO methods.
|
*/

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Comando para confirmar pagos pendientes cada minuto
Artisan::command('payments:process-pending', function () {
    $service = app(PaymentProcessingService::class);
    $results = $service->processPendingPayments();

    $this->info('🔄 Procesando pagos pendientes...');
    $this->info("📊 Total pagos pendientes: {$results['total_pending']}");
    $this->info("✅ Pagos procesados: {$results['processed']}");
    $this->info("❌ Errores: {$results['errors']}");

    if (!empty($results['details'])) {
        foreach ($results['details'] as $detail) {
            if ($detail['status'] === 'error') {
                $this->error("❌ Pago ID {$detail['payment_id']}: {$detail['message']}");
            } else {
                $this->info("✅ Pago ID {$detail['payment_id']}: {$detail['message']}");
            }
        }
    }

    $this->info('✅ Procesamiento de pagos pendientes completado');
})->purpose('Procesar pagos pendientes de confirmación');

// Comando para actualizar cuotas vencidas cada minuto
Artisan::command('installments:check-overdue', function () {
    $service = new InstallmentOverdueService();
    $results = $service->checkAndUpdateOverdue();

    $this->info('🔄 Verificando cuotas vencidas...');
    $this->info("📅 Fecha de referencia: {$results['date']}");
    $this->info("✅ Actualizadas {$results['installments_updated']} cuotas en installments");
    $this->info("✅ Actualizadas {$results['order_details_updated']} cuotas en orders_detail");
    $this->info("📊 Total actualizadas: {$results['total_updated']}");
    $this->info('✅ Verificación completada');
})->purpose('Verificar y actualizar cuotas vencidas');

// Comando para limpiar logs antiguos (ejecutar cada 12 horas)
Artisan::command('logs:cleanup', function () {
    $service = new LogCleanupService();
    $results = $service->cleanupOldLogs();

    $this->info('🧹 Limpiando logs antiguos...');
    $this->info("✅ Eliminados {$results['files_deleted']} archivos de log");
    $this->info("💾 Espacio liberado: " . number_format($results['total_space_freed'] / 1024 / 1024, 2) . " MB");

    if (!empty($results['errors'])) {
        foreach ($results['errors'] as $error) {
            $this->warn("⚠️ {$error}");
        }
    }
})->purpose('Limpiar logs antiguos del sistema');

// Comando para verificar estado del sistema (ejecutar cada 6 horas)
Artisan::command('system:health-check', function () {
    $service = new SystemHealthService();
    $results = $service->checkSystemHealth();

    $this->info('🏥 Verificando salud del sistema...');
    $this->info('✅ Base de datos: ' . ($results['database_status'] === 'connected' ? 'Conectada' : 'Error de conexión'));
    $this->info("💾 Espacio en disco: {$results['disk_usage_percent']}% usado");
    $this->info("📊 Cuotas vencidas totales: {$results['overdue_installments_count']}");
    $this->info("📈 Total cuotas en sistema: {$results['total_installments_count']}");

    if (!empty($results['errors'])) {
        foreach ($results['errors'] as $error) {
            $this->error("❌ {$error}");
        }
    }

    $this->info('✅ Verificación de salud completada');
})->purpose('Verificar estado general del sistema');

// Comando para actualizar opciones de pago y cuotas automáticamente
Artisan::command('payment-options:update', function () {
    $service = new UpdatePaymentOptionsService();
    $results = $service->updatePaymentOptions();

    $this->info('🔄 Actualizando opciones de pago automáticamente...');
    $this->info("📊 Programas procesados: {$results['programs_processed']}");
    $this->info("✅ Opciones actualizadas: {$results['options_updated']}");
    $this->info("📈 Cuotas actualizadas: {$results['installments_updated']}");

    if (!empty($results['errors'])) {
        $this->warn("⚠️ Errores encontrados:");
        foreach ($results['errors'] as $error) {
            $this->warn("   • {$error}");
        }
    }

    $this->info('✅ Actualización de opciones de pago completada');
})->purpose('Actualizar automáticamente las opciones de pago y cuotas según el tiempo transcurrido');

// Comando para procesar emails de marketing (ejecutar 2 veces al día)
Artisan::command('marketing:process-emails', function () {
    $service = new MarketingMailsService();
    $results = $service->processMarketingEmails();

    $this->info('📧 Procesando emails de marketing...');
    $this->info("📊 Total emails procesados: {$results['total_processed']}");
    $this->info("✅ Nuevos emails agregados: {$results['new_emails_added']}");
    $this->info("🔄 Emails existentes actualizados: {$results['existing_emails_updated']}");

    if (!empty($results['errors'])) {
        $this->warn("⚠️ Errores encontrados:");
        foreach ($results['errors'] as $error) {
            $this->warn("   • {$error}");
        }
    }

    // Mostrar estadísticas
    $stats = $service->getMarketingEmailsStats();
    $this->info("📈 Estadísticas actuales:");
    $this->info("   • Total emails en tabla: {$stats['total_emails']}");
    $this->info("   • Emails activos: {$stats['active_emails']}");
    $this->info("   • Emails inactivos: {$stats['inactive_emails']}");
    $this->info("   • Orders con marketing: {$stats['total_orders_with_marketing']}");
    $this->info("   • Clientes frecuentes con marketing: {$stats['total_frequent_clients_with_marketing']}");

    $this->info('✅ Procesamiento de emails de marketing completado');
})->purpose('Procesar y almacenar emails de marketing desde orders_detail y frequent_client');
