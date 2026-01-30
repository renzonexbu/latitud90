<?php

namespace App\Console\Commands;

use App\Models\BsaleRequest;
use App\Services\Bsale\BsaleQueueService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ProcessBsaleQueue extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'bsale:process
                            {--limit=50 : Límite de solicitudes a procesar}
                            {--request= : ID de solicitud específica a procesar}
                            {--retry-failed : Reintentar todas las solicitudes fallidas}
                            {--dry-run : Mostrar qué se procesaría sin ejecutar}
                            {--stats : Mostrar solo estadísticas}';

    /**
     * The console command description.
     */
    protected $description = 'Procesar cola de solicitudes BSale (generar boletas)';

    protected BsaleQueueService $queueService;

    public function __construct(BsaleQueueService $queueService)
    {
        parent::__construct();
        $this->queueService = $queueService;
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        // Mostrar estadísticas primero
        $this->showStats();

        if ($this->option('stats')) {
            return Command::SUCCESS;
        }

        // Modo reintentar fallidos
        if ($this->option('retry-failed')) {
            return $this->retryFailed();
        }

        // Procesar solicitud específica
        if ($requestId = $this->option('request')) {
            return $this->processSpecificRequest((int) $requestId);
        }

        // Procesar cola normal
        return $this->processQueue();
    }

    /**
     * Mostrar estadísticas de la cola
     */
    protected function showStats(): void
    {
        $stats = $this->queueService->getQueueStats();

        $this->newLine();
        $this->info('═══════════════════════════════════════════════════════════');
        $this->info('📊 ESTADÍSTICAS COLA BSALE - ' . now()->format('d/m/Y H:i:s'));
        $this->info('═══════════════════════════════════════════════════════════');
        $this->line("   📋 Pendientes: {$stats['pending']}");
        $this->line("   ⏳ En proceso: {$stats['processing']}");
        $this->line("   ✅ Completados hoy: {$stats['completed_today']}");

        if ($stats['failed'] > 0) {
            $this->error("   ❌ Fallidos: {$stats['failed']}");
        } else {
            $this->line("   ❌ Fallidos: {$stats['failed']}");
        }

        $this->line("   📥 Total hoy: {$stats['total_today']}");
        $this->info('═══════════════════════════════════════════════════════════');
        $this->newLine();
    }

    /**
     * Procesar cola de solicitudes
     */
    protected function processQueue(): int
    {
        $limit = (int) $this->option('limit');
        $isDryRun = $this->option('dry-run');

        $pendingRequests = BsaleRequest::readyToProcess()
            ->with(['payment', 'orderDetail.order.participant'])
            ->orderBy('scheduled_at', 'asc')
            ->limit($limit)
            ->get();

        if ($pendingRequests->isEmpty()) {
            $this->info('✅ No hay solicitudes pendientes de procesar');
            return Command::SUCCESS;
        }

        $this->info("📋 Encontradas {$pendingRequests->count()} solicitudes pendientes");
        $this->newLine();

        if ($isDryRun) {
            $this->showDryRunDetails($pendingRequests);
            return Command::SUCCESS;
        }

        $this->info('🔄 Procesando solicitudes...');
        $this->newLine();

        $results = $this->queueService->processPendingRequests($limit);

        $this->newLine();
        $this->info('📊 Resumen:');
        $this->line("   ✅ Exitosos: {$results['success']}");
        $this->line("   ⏭️  Omitidos: {$results['skipped']}");
        $this->line("   ❌ Fallidos: {$results['failed']}");

        return Command::SUCCESS;
    }

    /**
     * Procesar solicitud específica
     */
    protected function processSpecificRequest(int $requestId): int
    {
        $request = BsaleRequest::with(['payment', 'orderDetail'])->find($requestId);

        if (!$request) {
            $this->error("❌ Solicitud #{$requestId} no encontrada");
            return Command::FAILURE;
        }

        $this->info("📋 Procesando solicitud #{$requestId}");
        $this->line("   Payment ID: {$request->payment_id}");
        $this->line("   Estado actual: {$request->status_label}");
        $this->line("   Intentos: {$request->attempts}/{$request->max_attempts}");

        if ($this->option('dry-run')) {
            $this->warn('⚠️  [DRY-RUN] No se procesará la solicitud');
            return Command::SUCCESS;
        }

        $success = $this->queueService->processRequest($request);
        $request->refresh();

        $this->newLine();
        if ($success) {
            $this->info("✅ Solicitud procesada: {$request->status_label}");
            if ($request->bsale_number) {
                $this->line("   Boleta: {$request->bsale_number}");
            }
        } else {
            $this->error("❌ Error: {$request->error_message}");
        }

        return $success ? Command::SUCCESS : Command::FAILURE;
    }

    /**
     * Reintentar solicitudes fallidas
     */
    protected function retryFailed(): int
    {
        $failedRequests = BsaleRequest::failed()
            ->where('attempts', '<', \DB::raw('max_attempts'))
            ->get();

        if ($failedRequests->isEmpty()) {
            $this->info('✅ No hay solicitudes fallidas que puedan reintentarse');
            return Command::SUCCESS;
        }

        $this->info("🔄 Reintentando {$failedRequests->count()} solicitudes fallidas...");

        if ($this->option('dry-run')) {
            $this->warn('⚠️  [DRY-RUN] No se reintentarán las solicitudes');
            return Command::SUCCESS;
        }

        $retried = 0;
        foreach ($failedRequests as $request) {
            if ($this->queueService->retryRequest($request)) {
                $retried++;
                $this->line("   ✅ #{$request->id} programada para reintento");
            }
        }

        $this->newLine();
        $this->info("📊 {$retried} solicitudes programadas para reintento");
        $this->info('💡 Ejecuta: php artisan bsale:process para procesarlas');

        return Command::SUCCESS;
    }

    /**
     * Mostrar detalles en modo dry-run
     */
    protected function showDryRunDetails($requests): void
    {
        $this->warn('⚠️  [DRY-RUN] Solicitudes que serían procesadas:');
        $this->newLine();

        $tableData = [];
        foreach ($requests as $request) {
            $payment = $request->payment;
            $participant = $request->orderDetail?->order?->participant;

            $tableData[] = [
                'ID' => $request->id,
                'Payment' => $payment?->id ?? 'N/A',
                'Monto' => '$' . number_format($payment?->amount ?? 0, 0, ',', '.'),
                'Participante' => mb_substr($participant?->full_name ?? 'N/A', 0, 25),
                'Estado' => $request->status_label,
                'Intentos' => "{$request->attempts}/{$request->max_attempts}",
                'Creado' => $request->created_at->format('d/m H:i'),
            ];
        }

        $this->table(
            ['ID', 'Payment', 'Monto', 'Participante', 'Estado', 'Intentos', 'Creado'],
            $tableData
        );

        $this->newLine();
        $this->warn('⚠️  [DRY-RUN] Ejecuta sin --dry-run para procesar');
    }
}
