<?php

namespace App\Jobs;

use App\Services\Client\PaymentGateway\PaymentConfirmationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessPendingPaymentsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(PaymentConfirmationService $paymentConfirmationService): void
    {
        Log::info('ProcessPendingPaymentsJob: Iniciando procesamiento de pagos pendientes');
        
        try {
            $paymentConfirmationService->processPendingPayments();
            
            Log::info('ProcessPendingPaymentsJob: Procesamiento completado');
        } catch (\Exception $e) {
            Log::error('ProcessPendingPaymentsJob: Error procesando pagos pendientes', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            throw $e;
        }
    }
}
