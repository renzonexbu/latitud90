<?php

namespace App\Console\Commands;

use App\Models\Payment;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class UpdatePaymentDocumentTypes extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'payments:update-document-types 
                           {--batch-size=100 : Número de pagos a procesar por lote} 
                           {--dry-run : Ejecutar sin hacer cambios reales}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Actualiza el campo document_type de los pagos existentes que no lo tienen asignado';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $batchSize = (int) $this->option('batch-size');
        $dryRun = $this->option('dry-run');

        $this->info('Iniciando actualización de tipos de documento para pagos...');
        
        if ($dryRun) {
            $this->warn('MODO DRY-RUN: No se realizarán cambios reales');
        }

        // Contar pagos sin document_type
        $totalPayments = Payment::whereNull('document_type')->count();
        
        if ($totalPayments === 0) {
            $this->info('✅ No hay pagos sin tipo de documento asignado.');
            return Command::SUCCESS;
        }

        $this->info("📊 Encontrados {$totalPayments} pagos sin tipo de documento asignado");

        $processed = 0;
        $updated = 0;
        $errors = 0;

        $progressBar = $this->output->createProgressBar($totalPayments);
        $progressBar->start();

        // Procesar en lotes para evitar problemas de memoria
        Payment::whereNull('document_type')
            ->with(['paymentOption', 'order.program'])
            ->chunk($batchSize, function ($payments) use (&$processed, &$updated, &$errors, $dryRun, $progressBar) {
                
                foreach ($payments as $payment) {
                    try {
                        $documentType = $payment->determineDocumentType();
                        
                        if (!$dryRun) {
                            // Usar DB::table para evitar triggers del modelo
                            DB::table('payments')
                                ->where('id', $payment->id)
                                ->update(['document_type' => $documentType]);
                        }
                        
                        $updated++;
                        
                        if ($this->output->isVerbose()) {
                            $this->line("\n📄 Pago ID {$payment->id}: {$documentType}");
                        }
                        
                    } catch (\Exception $e) {
                        $errors++;
                        $this->error("\n❌ Error procesando pago ID {$payment->id}: " . $e->getMessage());
                    }
                    
                    $processed++;
                    $progressBar->advance();
                }
            });

        $progressBar->finish();
        $this->newLine(2);

        // Mostrar resumen
        $this->info("📋 Resumen de la actualización:");
        $this->table(['Métrica', 'Cantidad'], [
            ['Pagos procesados', $processed],
            ['Pagos actualizados', $updated],
            ['Errores', $errors],
        ]);

        if ($dryRun) {
            $this->warn('⚠️  Este fue un DRY-RUN. Para aplicar los cambios, ejecuta el comando sin --dry-run');
        } else {
            $this->info('✅ Actualización completada exitosamente');
        }

        // Mostrar distribución de tipos de documento
        if (!$dryRun && $updated > 0) {
            $this->showDocumentTypeDistribution();
        }

        return $errors > 0 ? Command::FAILURE : Command::SUCCESS;
    }

    /**
     * Mostrar distribución de tipos de documento
     */
    private function showDocumentTypeDistribution()
    {
        $this->newLine();
        $this->info("📈 Distribución actual de tipos de documento:");
        
        $distribution = Payment::selectRaw('document_type, COUNT(*) as count')
            ->whereNotNull('document_type')
            ->groupBy('document_type')
            ->orderBy('count', 'desc')
            ->get();

        $tableData = [];
        foreach ($distribution as $item) {
            $type = $item->document_type;
            $label = match($type) {
                'B2' => 'Boleta',
                'BC' => 'Nota de Crédito',
                'FF' => 'Factura',
                'AC' => 'Reserva',
                default => $type
            };
            
            $tableData[] = [
                $type,
                $label,
                $item->count,
                number_format(($item->count / $distribution->sum('count')) * 100, 1) . '%'
            ];
        }

        $this->table(['Código', 'Tipo', 'Cantidad', 'Porcentaje'], $tableData);
    }
}
