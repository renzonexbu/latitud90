<?php

namespace App\Console\Commands;

use App\Services\Commands\UpdatePaymentOptionsService;
use Illuminate\Console\Command;

class UpdatePaymentOptionsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'payment-options:update 
                           {--dry-run : Ejecutar sin hacer cambios reales}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Actualizar automáticamente las opciones de pago y cuotas según el tiempo transcurrido en los programas';

    /**
     * Execute the console command.
     */
    public function handle(UpdatePaymentOptionsService $service): int
    {
        $dryRun = $this->option('dry-run');
        
        if ($dryRun) {
            $this->warn('🔍 MODO DRY-RUN: No se realizarán cambios reales');
        }

        $this->info('🔄 Iniciando actualización automática de opciones de pago...');
        
        try {
            // Obtener estadísticas antes de la actualización
            $stats = $service->getUpdateStats();
            
            $this->info("📊 Estadísticas del sistema:");
            $this->info("   • Total de programas activos: {$stats['total_programs']}");
            $this->info("   • Programas con opciones de pago: {$stats['programs_with_options']}");
            $this->info("   • Fecha de referencia: {$stats['date']}");
            
            if ($dryRun) {
                $this->info('🔍 DRY-RUN: Simulando actualización...');
                $this->info('✅ Simulación completada (sin cambios reales)');
                return Command::SUCCESS;
            }
            
            // Ejecutar la actualización
            $results = $service->updatePaymentOptions();
            
            $this->info('✅ Actualización completada exitosamente');
            $this->info("📊 Resultados:");
            $this->info("   • Programas procesados: {$results['programs_processed']}");
            $this->info("   • Opciones actualizadas: {$results['options_updated']}");
            $this->info("   • Cuotas actualizadas: {$results['installments_updated']}");
            
            if (!empty($results['errors'])) {
                $this->warn("⚠️ Errores encontrados:");
                foreach ($results['errors'] as $error) {
                    $this->warn("   • {$error}");
                }
            }
            
        } catch (\Exception $e) {
            $this->error('❌ Error durante la actualización: ' . $e->getMessage());
            return Command::FAILURE;
        }
        
        return Command::SUCCESS;
    }
}
