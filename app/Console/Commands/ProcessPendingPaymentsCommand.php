<?php

namespace App\Console\Commands;

use App\Models\Payment;
use App\Services\Client\Payment\ProcessPaymentConfirmationService;
use Illuminate\Console\Command;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ProcessPendingPaymentsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'payments:process-pending';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Procesar pagos pendientes ejecutando confirmaciones';

    /**
     * Execute the console command.
     */
    public function handle(ProcessPaymentConfirmationService $processPaymentConfirmationService)
    {
        $this->info('Iniciando procesamiento de pagos pendientes...');

        // Obtener todos los pagos pendientes
        $pendingPayments = Payment::where('status', 'pending')
            ->with(['orderDetail', 'paymentGateway'])
            ->get();

        if ($pendingPayments->isEmpty()) {
            $this->info('No hay pagos pendientes para procesar.');
            return 0;
        }

        $this->info("Encontrados {$pendingPayments->count()} pagos pendientes.");

        $processed = 0;
        $errors = 0;

        foreach ($pendingPayments as $payment) {
            try {
                $this->info("Procesando pago ID: {$payment->id} para OrderDetail: {$payment->order_detail_id}");

                // Determinar el tipo de pasarela
                $gatewayType = $this->getGatewayType($payment);
                
                if (!$gatewayType) {
                    $this->warn("No se pudo determinar el tipo de pasarela para el pago ID: {$payment->id}");
                    continue;
                }

                // Crear una request simulada con los datos necesarios
                $request = $this->createMockRequest($payment, $gatewayType);

                // Ejecutar la confirmación del pago
                $result = $processPaymentConfirmationService->execute($request);

                if (isset($result['success']) && $result['success']) {
                    $this->info("✅ Pago ID: {$payment->id} procesado exitosamente");
                    $processed++;
                } else {
                    $this->warn("⚠️ Pago ID: {$payment->id} no pudo ser confirmado: " . ($result['error'] ?? 'Error desconocido'));
                    $errors++;
                }

            } catch (\Exception $e) {
                $this->error("❌ Error procesando pago ID: {$payment->id} - " . $e->getMessage());
                Log::error('ProcessPendingPaymentsCommand: Error processing payment', [
                    'payment_id' => $payment->id,
                    'order_detail_id' => $payment->order_detail_id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                $errors++;
            }
        }

        $this->info("Procesamiento completado. Procesados: {$processed}, Errores: {$errors}");

        return 0;
    }

    /**
     * Determinar el tipo de pasarela basado en el payment gateway
     */
    private function getGatewayType(Payment $payment): ?string
    {
        if (!$payment->paymentGateway) {
            return null;
        }

        $gatewayName = strtolower($payment->paymentGateway->name);

        if (str_contains($gatewayName, 'transbank')) {
            return 'transbank';
        } elseif (str_contains($gatewayName, 'virtualpos')) {
            return 'virtualpos';
        } elseif (str_contains($gatewayName, 'khipu')) {
            return 'khipu';
        }

        return null;
    }

    /**
     * Crear una request simulada con los datos necesarios
     */
    private function createMockRequest(Payment $payment, string $gatewayType): Request
    {
        $request = new Request();
        
        $request->merge([
            'orderDetailId' => $payment->order_detail_id,
            'gatewayType' => $gatewayType,
            'payment_id' => $payment->external_payment_id ?: $payment->token,
            'session_id' => $payment->session_id ?? null,
        ]);

        return $request;
    }
}
