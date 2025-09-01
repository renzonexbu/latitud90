<?php

namespace App\Console\Commands;

use App\Models\Installment;
use App\Models\OrderDetail;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class UpdateOverdueInstallmentsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'installments:update-overdue-status 
                           {--dry-run : Ejecutar sin hacer cambios reales}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Actualiza automáticamente el estado de las cuotas vencidas a overdue';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $dryRun = $this->option('dry-run');
        $today = Carbon::today()->setTimezone('America/Santiago');
        
        if ($dryRun) {
            $this->warn('🔍 MODO DRY-RUN: No se realizarán cambios reales');
        }

        $this->info('🔄 Iniciando actualización de cuotas vencidas...');
        $this->info("📅 Fecha de referencia: {$today->format('Y-m-d')}");
        
        try {
            // Procesar cuotas de la tabla installments
            $this->processInstallmentsTable($today, $dryRun);
            
            // Procesar cuotas de la tabla orders_detail
            $this->processOrderDetailsTable($today, $dryRun);
            
            $this->info('✅ Proceso completado exitosamente');
            
        } catch (\Exception $e) {
            $this->error('❌ Error durante la actualización: ' . $e->getMessage());
            Log::error('Error en UpdateOverdueInstallmentsCommand', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return 1;
        }
        
        return 0;
    }

    /**
     * Procesar cuotas de la tabla installments
     */
    private function processInstallmentsTable(Carbon $today, bool $dryRun): void
    {
        $this->info('📊 Procesando tabla installments...');
        
        $overdueInstallments = Installment::where('status', 'pending')
            ->where('due_date', '<', $today)
            ->get();
            
        $count = $overdueInstallments->count();
        
        if ($count === 0) {
            $this->info('   ✅ No hay cuotas vencidas en installments');
            return;
        }
        
        $this->info("   📋 Encontradas {$count} cuotas vencidas en installments");
        
        if (!$dryRun) {
            $updated = Installment::where('status', 'pending')
                ->where('due_date', '<', $today)
                ->update([
                    'status' => 'overdue',
                    'updated_at' => now()->setTimezone('America/Santiago')
                ]);
                
            $this->info("   ✅ Actualizadas {$updated} cuotas a estado overdue");
            
            Log::info('Cuotas vencidas actualizadas en tabla installments', [
                'count' => $updated,
                'date' => $today->format('Y-m-d')
            ]);
        } else {
            $this->info("   🔍 DRY-RUN: Se actualizarían {$count} cuotas a estado overdue");
        }
    }

    /**
     * Procesar cuotas de la tabla orders_detail
     */
    private function processOrderDetailsTable(Carbon $today, bool $dryRun): void
    {
        $this->info('📊 Procesando tabla orders_detail...');
        
        $overdueOrderDetails = OrderDetail::where('status', 'pending')
            ->where('due_date', '<', $today)
            ->where('is_paid', false)
            ->get();
            
        $count = $overdueOrderDetails->count();
        
        if ($count === 0) {
            $this->info('   ✅ No hay cuotas vencidas en orders_detail');
            return;
        }
        
        $this->info("   📋 Encontradas {$count} cuotas vencidas en orders_detail");
        
        if (!$dryRun) {
            $updated = OrderDetail::where('status', 'pending')
                ->where('due_date', '<', $today)
                ->where('is_paid', false)
                ->update([
                    'status' => 'overdue',
                    'updated_at' => now()->setTimezone('America/Santiago')
                ]);
                
            $this->info("   ✅ Actualizadas {$updated} cuotas a estado overdue");
            
            Log::info('Cuotas vencidas actualizadas en tabla orders_detail', [
                'count' => $updated,
                'date' => $today->format('Y-m-d')
            ]);
        } else {
            $this->info("   🔍 DRY-RUN: Se actualizarían {$count} cuotas a estado overdue");
        }
    }
}
