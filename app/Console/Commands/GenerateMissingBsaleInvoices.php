<?php

namespace App\Console\Commands;

use App\Models\Payment;
use App\Models\OrderDetail;
use App\Services\Client\Integration\BsaleService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class GenerateMissingBsaleInvoices extends Command
{
    protected $signature = 'bsale:generate-missing
                            {--ids= : IDs de pagos separados por coma (ej: 4165,4156)}
                            {--dry-run : Solo mostrar qué se haría sin ejecutar}';

    protected $description = 'Genera boletas en Bsale para pagos que no las tienen';

    public function handle(BsaleService $bsaleService)
    {
        $ids = $this->option('ids');
        $dryRun = $this->option('dry-run');

        if (!$ids) {
            $this->error('Debes especificar los IDs de pago con --ids=4165,4156');
            return 1;
        }

        $paymentIds = array_map('trim', explode(',', $ids));

        $this->info("Procesando " . count($paymentIds) . " pago(s)...");
        if ($dryRun) {
            $this->warn("MODO DRY-RUN: No se ejecutarán cambios reales");
        }

        $success = 0;
        $failed = 0;

        foreach ($paymentIds as $paymentId) {
            $payment = Payment::with(['orderDetail', 'order.participant', 'order.programCourse'])
                ->find($paymentId);

            if (!$payment) {
                $this->error("Pago ID {$paymentId}: No encontrado");
                $failed++;
                continue;
            }

            $orderDetail = $payment->orderDetail;
            if (!$orderDetail) {
                $this->error("Pago ID {$paymentId}: No tiene OrderDetail asociado");
                $failed++;
                continue;
            }

            // Mostrar info del pago
            $participant = $payment->order->participant ?? null;
            $program = $payment->order->programCourse ?? null;

            $this->line("");
            $this->info("=== Pago ID: {$paymentId} ===");
            $this->line("  Participante: " . ($participant ? $participant->full_name : 'N/A'));
            $this->line("  Programa: " . ($program ? $program->name : 'N/A'));
            $this->line("  Monto: $" . number_format($payment->amount, 0, ',', '.'));
            $this->line("  Document Type: {$payment->document_type}");
            $this->line("  Status: {$payment->status}");
            $this->line("  RUT comprador: {$orderDetail->document_number}");
            $this->line("  bsale_document_id actual: " . ($payment->bsale_document_id ?? 'NULL'));
            $this->line("  bsale_number actual: " . ($payment->bsale_number ?? 'NULL'));
            $this->line("  payment_code actual: " . ($payment->payment_code ?? 'NULL'));

            // Verificar condiciones
            if ($payment->document_type !== 'B2') {
                $this->warn("  ⚠️  Saltando: document_type no es B2 (es {$payment->document_type})");
                continue;
            }

            if (!in_array($payment->status, ['completed', 'approved'])) {
                $this->warn("  ⚠️  Saltando: status no es completed/approved (es {$payment->status})");
                continue;
            }

            if ($payment->bsale_document_id) {
                $this->warn("  ⚠️  Saltando: Ya tiene bsale_document_id ({$payment->bsale_document_id})");
                continue;
            }

            if ($dryRun) {
                $this->info("  🔄 [DRY-RUN] Se generaría boleta en Bsale");
                continue;
            }

            // Limpiar bsale_number si tiene valor (era el código manual que se guardó mal)
            if ($payment->bsale_number) {
                $oldBsaleNumber = $payment->bsale_number;

                // Mover el valor a payment_code si está vacío
                if (empty($payment->payment_code)) {
                    $payment->update([
                        'payment_code' => $oldBsaleNumber,
                        'bsale_number' => null,
                    ]);
                    $this->line("  📝 Movido '{$oldBsaleNumber}' de bsale_number a payment_code");
                } else {
                    $payment->update(['bsale_number' => null]);
                    $this->line("  📝 Limpiado bsale_number (payment_code ya tiene valor)");
                }

                // Recargar el payment
                $payment->refresh();
            }

            // Generar boleta
            $this->line("  🔄 Generando boleta en Bsale...");

            try {
                $bsaleResult = $bsaleService->generateInvoice($orderDetail, $payment);

                if ($bsaleResult) {
                    $payment->update([
                        'bsale_document_id' => $bsaleResult['id'] ?? null,
                        'bsale_number' => $bsaleResult['number'] ?? null,
                        'bsale_token' => $bsaleResult['token'] ?? null,
                    ]);

                    $this->info("  ✅ Boleta generada exitosamente!");
                    $this->line("     bsale_document_id: " . ($bsaleResult['id'] ?? 'N/A'));
                    $this->line("     bsale_number: " . ($bsaleResult['number'] ?? 'N/A'));

                    Log::info('GenerateMissingBsaleInvoices: Boleta generada', [
                        'payment_id' => $paymentId,
                        'bsale_document_id' => $bsaleResult['id'] ?? null,
                        'bsale_number' => $bsaleResult['number'] ?? null,
                    ]);

                    $success++;
                } else {
                    $this->error("  ❌ No se pudo generar la boleta (BsaleService retornó null)");
                    $failed++;
                }
            } catch (\Exception $e) {
                $this->error("  ❌ Error: " . $e->getMessage());
                Log::error('GenerateMissingBsaleInvoices: Error generando boleta', [
                    'payment_id' => $paymentId,
                    'error' => $e->getMessage(),
                ]);
                $failed++;
            }
        }

        $this->line("");
        $this->info("=== Resumen ===");
        $this->line("  Exitosos: {$success}");
        $this->line("  Fallidos: {$failed}");

        return $failed > 0 ? 1 : 0;
    }
}
