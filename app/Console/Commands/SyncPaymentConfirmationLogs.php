<?php

namespace App\Console\Commands;

use App\Models\Payment;
use App\Models\PaymentConfirmationLog;
use App\Models\GeneratedDocument;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SyncPaymentConfirmationLogs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'payments:sync-logs
                            {--payment-id= : ID específico de un pago a sincronizar}
                            {--status=completed : Estado de pagos a sincronizar (completed, approved, all)}
                            {--dry-run : Solo mostrar lo que se haría, sin crear registros}
                            {--force : Crear registros incluso si ya existen}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sincronizar pagos existentes con la tabla payment_confirmation_logs';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $paymentId = $this->option('payment-id');
        $status = $this->option('status');
        $dryRun = $this->option('dry-run');
        $force = $this->option('force');

        if ($dryRun) {
            $this->warn('Modo DRY-RUN: No se crearán registros en la base de datos');
        }

        $this->info('Iniciando sincronización de logs de confirmación...');

        // Construir query de pagos
        $query = Payment::query()
            ->with(['orderDetail', 'order']);

        if ($paymentId) {
            $query->where('id', $paymentId);
            $this->info("Sincronizando pago específico ID: {$paymentId}");
        } else {
            switch ($status) {
                case 'completed':
                    $query->where('status', 'completed');
                    $this->info('Sincronizando pagos con estado: completed');
                    break;
                case 'approved':
                    $query->whereIn('status', ['completed', 'approved']);
                    $this->info('Sincronizando pagos con estado: completed/approved');
                    break;
                case 'all':
                    $this->info('Sincronizando TODOS los pagos');
                    break;
                default:
                    $query->where('status', $status);
                    $this->info("Sincronizando pagos con estado: {$status}");
            }
        }

        $payments = $query->get();
        $total = $payments->count();

        if ($total === 0) {
            $this->warn('No se encontraron pagos para sincronizar');
            return Command::SUCCESS;
        }

        $this->info("Total de pagos a procesar: {$total}");
        $this->newLine();

        $stats = [
            'bsale_created' => 0,
            'email_created' => 0,
            'receipt_created' => 0,
            'contract_created' => 0,
            'skipped' => 0,
            'errors' => 0,
        ];

        foreach ($payments as $payment) {
            $this->line("Procesando Payment #{$payment->id}...");

            try {
                $result = $this->syncPaymentLogs($payment, $dryRun, $force);

                $stats['bsale_created'] += $result['bsale'];
                $stats['email_created'] += $result['email'];
                $stats['receipt_created'] += $result['receipt'];
                $stats['contract_created'] += $result['contract'];
                $stats['skipped'] += $result['skipped'];

            } catch (\Exception $e) {
                $stats['errors']++;
                $this->error("  Error: {$e->getMessage()}");
                Log::error('SyncPaymentConfirmationLogs: Error', [
                    'payment_id' => $payment->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $this->newLine();
        $this->info('Resumen de sincronización:');
        $this->table(
            ['Tipo de Log', 'Creados'],
            [
                ['Boletas Bsale', $stats['bsale_created']],
                ['Emails enviados', $stats['email_created']],
                ['Comprobantes de pago', $stats['receipt_created']],
                ['Contratos', $stats['contract_created']],
                ['Omitidos (ya existían)', $stats['skipped']],
                ['Errores', $stats['errors']],
            ]
        );

        if ($dryRun) {
            $this->warn('Este fue un DRY-RUN. Ejecuta sin --dry-run para crear los registros.');
        }

        return Command::SUCCESS;
    }

    /**
     * Sincronizar logs para un pago específico
     */
    private function syncPaymentLogs(Payment $payment, bool $dryRun, bool $force): array
    {
        $result = [
            'bsale' => 0,
            'email' => 0,
            'receipt' => 0,
            'contract' => 0,
            'skipped' => 0,
        ];

        $orderDetail = $payment->orderDetail;

        // 1. Sincronizar log de boleta Bsale
        if (!empty($payment->bsale_number)) {
            $existingBsaleLog = PaymentConfirmationLog::where('payment_id', $payment->id)
                ->where('event_type', 'bsale_invoice_generated')
                ->exists();

            if (!$existingBsaleLog || $force) {
                if (!$dryRun) {
                    // Buscar el archivo de boleta si existe
                    $bsaleFilePath = $this->findBsaleFile($payment);

                    PaymentConfirmationLog::create([
                        'payment_id' => $payment->id,
                        'order_detail_id' => $orderDetail?->id,
                        'order_id' => $orderDetail?->order_id,
                        'event_type' => 'bsale_invoice_generated',
                        'status' => 'success',
                        'bsale_document_id' => $payment->bsale_document_id,
                        'bsale_number' => $payment->bsale_number,
                        'file_path' => $bsaleFilePath,
                        'details' => [
                            'synced_from_existing' => true,
                            'synced_at' => now()->toIso8601String(),
                        ],
                    ]);
                }
                $this->info("  + Boleta Bsale #{$payment->bsale_number}");
                $result['bsale']++;
            } else {
                $this->line("  - Boleta Bsale ya registrada");
                $result['skipped']++;
            }
        }

        // 2. Sincronizar log de email enviado
        if ($payment->email_sent) {
            $existingEmailLog = PaymentConfirmationLog::where('payment_id', $payment->id)
                ->whereIn('event_type', ['email_sent', 'email_resent'])
                ->exists();

            if (!$existingEmailLog || $force) {
                if (!$dryRun) {
                    PaymentConfirmationLog::create([
                        'payment_id' => $payment->id,
                        'order_detail_id' => $orderDetail?->id,
                        'order_id' => $orderDetail?->order_id,
                        'event_type' => 'email_sent',
                        'status' => 'success',
                        'email_recipient' => $orderDetail?->email,
                        'details' => [
                            'synced_from_existing' => true,
                            'synced_at' => now()->toIso8601String(),
                        ],
                    ]);
                }
                $this->info("  + Email enviado a {$orderDetail?->email}");
                $result['email']++;
            } else {
                $this->line("  - Email ya registrado");
                $result['skipped']++;
            }
        }

        // 3. Sincronizar log de comprobante de pago (desde generated_documents)
        $receiptDoc = GeneratedDocument::where('payment_id', $payment->id)
            ->where('document_type', 'payment_receipt')
            ->first();

        if ($receiptDoc) {
            $existingReceiptLog = PaymentConfirmationLog::where('payment_id', $payment->id)
                ->where('event_type', 'payment_receipt_generated')
                ->exists();

            if (!$existingReceiptLog || $force) {
                if (!$dryRun) {
                    PaymentConfirmationLog::create([
                        'payment_id' => $payment->id,
                        'order_detail_id' => $orderDetail?->id,
                        'order_id' => $orderDetail?->order_id,
                        'event_type' => 'payment_receipt_generated',
                        'status' => 'success',
                        'file_path' => $receiptDoc->file_path,
                        'file_name' => $receiptDoc->file_name,
                        'details' => [
                            'synced_from_existing' => true,
                            'synced_at' => now()->toIso8601String(),
                            'generated_document_id' => $receiptDoc->id,
                        ],
                    ]);
                }
                $this->info("  + Comprobante de pago");
                $result['receipt']++;
            } else {
                $this->line("  - Comprobante ya registrado");
                $result['skipped']++;
            }
        }

        // 4. Sincronizar log de contrato (desde generated_documents)
        $contractDoc = GeneratedDocument::where('order_detail_id', $orderDetail?->id)
            ->where('document_type', 'contract')
            ->first();

        if ($contractDoc) {
            $existingContractLog = PaymentConfirmationLog::where('payment_id', $payment->id)
                ->where('event_type', 'contract_generated')
                ->exists();

            if (!$existingContractLog || $force) {
                if (!$dryRun) {
                    PaymentConfirmationLog::create([
                        'payment_id' => $payment->id,
                        'order_detail_id' => $orderDetail?->id,
                        'order_id' => $orderDetail?->order_id,
                        'event_type' => 'contract_generated',
                        'status' => 'success',
                        'file_path' => $contractDoc->file_path,
                        'file_name' => $contractDoc->file_name,
                        'details' => [
                            'synced_from_existing' => true,
                            'synced_at' => now()->toIso8601String(),
                            'generated_document_id' => $contractDoc->id,
                        ],
                    ]);
                }
                $this->info("  + Contrato");
                $result['contract']++;
            } else {
                $this->line("  - Contrato ya registrado");
                $result['skipped']++;
            }
        }

        return $result;
    }

    /**
     * Buscar archivo de boleta Bsale
     */
    private function findBsaleFile(Payment $payment): ?string
    {
        $bsaleDir = storage_path('app/bsale_documents');
        $year = $payment->created_at->format('Y');
        $yearDir = $bsaleDir . '/' . $year;

        $bsaleNumber = $payment->bsale_number ?? $payment->id;
        $filename = 'bsale_' . $bsaleNumber . '_payment_' . $payment->id . '.pdf';
        $filePath = $yearDir . '/' . $filename;

        if (file_exists($filePath)) {
            return $filePath;
        }

        return null;
    }
}
