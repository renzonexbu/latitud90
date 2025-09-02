<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\InstallmentPlan;
use App\Models\Installment;
use App\Models\Order;
use App\Models\Payment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CleanPresentialPaymentInstallments extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'installments:clean-presential {--participant_id= : ID específico del participante} {--program_id= : ID específico del programa} {--dry-run : Solo mostrar qué se haría sin ejecutar cambios}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Limpia las cuotas falsas creadas por pagos presenciales que no deberían tener cuotas';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔍 Iniciando limpieza de cuotas falsas de pagos presenciales...');
        
        $participantId = $this->option('participant_id');
        $programId = $this->option('program_id');
        $isDryRun = $this->option('dry-run');
        
        if ($isDryRun) {
            $this->warn('⚠️  MODO DRY-RUN: No se realizarán cambios reales');
        }
        
        try {
            // Buscar planes de cuotas que fueron creados por pagos presenciales
            $query = InstallmentPlan::with(['installments', 'order', 'participant', 'program'])
                ->whereHas('order', function ($q) {
                    $q->where('payment_type', 'total')
                      ->whereHas('orderDetails', function ($od) {
                          $od->whereHas('paymentGateway', function ($pg) {
                              $pg->where('code', 'presencial');
                          });
                      });
                });
            
            if ($participantId) {
                $query->where('participant_id', $participantId);
            }
            
            if ($programId) {
                $query->where('program_id', $programId);
            }
            
            $installmentPlans = $query->get();
            
            if ($installmentPlans->isEmpty()) {
                $this->info('✅ No se encontraron planes de cuotas creados por pagos presenciales');
                return 0;
            }
            
            $this->info("📋 Se encontraron {$installmentPlans->count()} planes de cuotas para limpiar:");
            
            $totalInstallmentsToDelete = 0;
            $totalPlansToDelete = 0;
            
            foreach ($installmentPlans as $plan) {
                $this->line("  • Plan ID: {$plan->id} - Participante: {$plan->participant->first_name} {$plan->participant->first_last_name}");
                $this->line("    Programa: {$plan->program->name} - Monto: $" . number_format($plan->total_amount, 0, ',', '.'));
                $this->line("    Cuotas: {$plan->installments->count()} (todas pendientes)");
                
                $totalInstallmentsToDelete += $plan->installments->count();
                $totalPlansToDelete++;
                
                // Verificar que todas las cuotas estén pendientes (no pagadas)
                $pendingInstallments = $plan->installments->where('status', 'pending');
                $paidInstallments = $plan->installments->where('status', 'paid');
                
                if ($paidInstallments->count() > 0) {
                    $this->warn("    ⚠️  ADVERTENCIA: Este plan tiene {$paidInstallments->count()} cuotas pagadas. No se eliminará automáticamente.");
                    continue;
                }
                
                if ($pendingInstallments->count() > 0) {
                    $this->line("    ✅ Se eliminarán {$pendingInstallments->count()} cuotas pendientes");
                }
            }
            
            if ($totalPlansToDelete === 0) {
                $this->info('✅ No hay planes de cuotas que se puedan eliminar de forma segura');
                return 0;
            }
            
            $this->newLine();
            $this->info("📊 Resumen de limpieza:");
            $this->line("  • Planes de cuotas a eliminar: {$totalPlansToDelete}");
            $this->line("  • Cuotas pendientes a eliminar: {$totalInstallmentsToDelete}");
            
            if (!$isDryRun) {
                if (!$this->confirm('¿Estás seguro de que quieres proceder con la limpieza?')) {
                    $this->info('❌ Operación cancelada');
                    return 0;
                }
                
                $this->info('🧹 Ejecutando limpieza...');
                
                DB::beginTransaction();
                
                try {
                    foreach ($installmentPlans as $plan) {
                        // Solo eliminar si todas las cuotas están pendientes
                        $paidInstallments = $plan->installments->where('status', 'paid');
                        
                        if ($paidInstallments->count() > 0) {
                            $this->warn("    ⚠️  Saltando plan ID {$plan->id} - tiene cuotas pagadas");
                            continue;
                        }
                        
                        // Eliminar cuotas pendientes
                        $deletedInstallments = $plan->installments()->where('status', 'pending')->delete();
                        
                        // Eliminar el plan de cuotas
                        $plan->delete();
                        
                        $this->line("    ✅ Plan ID {$plan->id} eliminado con {$deletedInstallments} cuotas");
                    }
                    
                    DB::commit();
                    
                    $this->info('✅ Limpieza completada exitosamente');
                    
                    // Log de la operación
                    Log::info('Limpieza de cuotas falsas de pagos presenciales completada', [
                        'plans_deleted' => $totalPlansToDelete,
                        'installments_deleted' => $totalInstallmentsToDelete,
                        'executed_by' => 'console_command',
                        'participant_id_filter' => $participantId,
                        'program_id_filter' => $programId
                    ]);
                    
                } catch (\Exception $e) {
                    DB::rollBack();
                    $this->error("❌ Error durante la limpieza: {$e->getMessage()}");
                    Log::error('Error durante limpieza de cuotas falsas', [
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString()
                    ]);
                    return 1;
                }
            } else {
                $this->info('🔍 MODO DRY-RUN: No se realizaron cambios reales');
            }
            
            return 0;
            
        } catch (\Exception $e) {
            $this->error("❌ Error general: {$e->getMessage()}");
            Log::error('Error en comando CleanPresentialPaymentInstallments', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return 1;
        }
    }
}
