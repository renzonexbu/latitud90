<?php

namespace App\Jobs;

use App\Services\Bsale\BsaleQueueService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessBsaleRequestsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected int $limit;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 1;

    /**
     * The number of seconds the job can run before timing out.
     */
    public int $timeout = 300; // 5 minutos

    /**
     * Create a new job instance.
     */
    public function __construct(int $limit = 50)
    {
        $this->limit = $limit;
        $this->onQueue('bsale'); // Cola específica para BSale
    }

    /**
     * Execute the job.
     */
    public function handle(BsaleQueueService $queueService): void
    {
        Log::info('ProcessBsaleRequestsJob: Iniciando procesamiento de cola BSale', [
            'limit' => $this->limit,
        ]);

        try {
            $results = $queueService->processPendingRequests($this->limit);

            Log::info('ProcessBsaleRequestsJob: Procesamiento completado', $results);

        } catch (\Exception $e) {
            Log::error('ProcessBsaleRequestsJob: Error en procesamiento', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }
}
