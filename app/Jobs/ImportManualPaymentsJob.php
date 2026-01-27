<?php

namespace App\Jobs;

use App\Services\Admin\Payments\ImportManualPaymentsService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class ImportManualPaymentsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 1200; // 20 minutos
    public $tries = 1; // Solo 1 intento

    protected $rowsData;
    protected $columnIndices;
    protected $userId;
    protected $jobId;

    /**
     * Create a new job instance.
     *
     * @param array $rowsData Array of rows with ['row_number' => int, 'data' => array]
     * @param array $columnIndices Column mapping from headers
     * @param int $userId
     * @param string $jobId
     */
    public function __construct(array $rowsData, array $columnIndices, int $userId, string $jobId)
    {
        $this->rowsData = $rowsData;
        $this->columnIndices = $columnIndices;
        $this->userId = $userId;
        $this->jobId = $jobId;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info('🚀 Job de importación iniciado', [
            'job_id' => $this->jobId,
            'user_id' => $this->userId,
            'total_rows' => count($this->rowsData)
        ]);

        try {
            // Procesar los datos directamente (sin leer Excel)
            $this->processRowsData();

            Log::info('✅ Job de importación completado exitosamente', [
                'job_id' => $this->jobId
            ]);

        } catch (\Exception $e) {
            Log::error('❌ Error en job de importación', [
                'job_id' => $this->jobId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            $this->updateProgress(
                0,
                0,
                'failed',
                'Error: ' . $e->getMessage()
            );

            throw $e;
        }
    }

    /**
     * Process rows data directly (NO Excel reading)
     */
    protected function processRowsData(): void
    {
        $importService = app(\App\Services\Admin\Payments\ImportManualPaymentsService::class);

        $this->updateProgress(5, 0, 'processing', 'Iniciando procesamiento de pagos...');

        $totalRows = count($this->rowsData);

        if (empty($this->rowsData)) {
            throw new \Exception('No hay datos para procesar');
        }

        Log::info('Procesando pagos con columnIndices', [
            'total_rows' => $totalRows,
            'column_indices' => $this->columnIndices
        ]);

        $this->updateProgress(10, 0, 'processing', 'Procesando pagos...');

        // Process in chunks
        $currentChunk = [];
        $stats = ['successful' => 0, 'duplicates' => 0, 'skipped' => 0, 'failed' => 0];
        $processedCount = 0;

        foreach ($this->rowsData as $rowItem) {
            $rowNumber = $rowItem['row_number'] ?? 0;
            $rowData = $rowItem['data'] ?? [];

            if (empty($rowData)) {
                continue;
            }

            // Formato esperado por processChunk: ['row_index' => int, 'data' => array]
            $currentChunk[] = ['row_index' => $rowNumber, 'data' => $rowData];

            // Process chunk when full
            if (count($currentChunk) >= 50) {
                $chunkResult = $this->processChunk($currentChunk, $this->columnIndices, $stats);
                $stats = $chunkResult['stats'];
                $processedCount += count($currentChunk);

                $progress = 10 + (($processedCount / $totalRows) * 90);
                $this->updateProgress(
                    $progress,
                    $stats['successful'],
                    'processing',
                    "Procesados: {$stats['successful']} exitosos de {$totalRows}",
                    $stats
                );

                $currentChunk = [];
            }
        }

        // Process remaining
        if (!empty($currentChunk)) {
            $chunkResult = $this->processChunk($currentChunk, $this->columnIndices, $stats);
            $stats = $chunkResult['stats'];
        }

        $this->updateProgress(
            100,
            $stats['successful'],
            'completed',
            "Importación completada: {$stats['successful']} pagos insertados",
            $stats
        );
    }

    /**
     * Update progress in cache
     */
    protected function updateProgress(
        float $percentage,
        int $successful,
        string $status,
        string $message,
        ?array $stats = null
    ): void {
        $progressData = [
            'job_id' => $this->jobId,
            'percentage' => $percentage,
            'successful' => $successful,
            'status' => $status, // processing, completed, failed
            'message' => $message,
            'stats' => $stats,
            'updated_at' => now()->toISOString()
        ];

        Cache::put("import_progress_{$this->jobId}", $progressData, now()->addHours(24));
    }

    /**
     * Process a chunk with transaction
     */
    protected function processChunk(array $chunk, array $columnIndices, array $stats): array
    {
        $importService = app(ImportManualPaymentsService::class);

        // Llamar directamente al método público
        return $importService->processChunk($chunk, $columnIndices, [], $stats, null);
    }
}
