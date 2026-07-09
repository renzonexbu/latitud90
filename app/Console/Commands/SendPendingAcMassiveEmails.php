<?php

namespace App\Console\Commands;

use App\Models\Payment;
use App\Services\Mail\SuccessPaymentEmailService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Comando one-off: reenvía emails de comprobante (AC + CR si corresponde) a los pagos
 * AC que fueron creados por importación masiva ANTES de que el servicio enviara
 * emails automáticamente.
 *
 * Filtra solo pagos que tienen:
 *   - document_type = 'AC'
 *   - status approved (lo que pone el masivo) o completed
 *   - email_sent = false
 *   - origen importación masiva (gateway_response.import_row presente)
 *
 * Uso:
 *   php artisan ac:send-pending-massive-emails           # dry-run, solo muestra
 *   php artisan ac:send-pending-massive-emails --send    # enviar de verdad
 *   php artisan ac:send-pending-massive-emails --send --limit=50
 *   php artisan ac:send-pending-massive-emails --send --since=2026-05-01
 */
class SendPendingAcMassiveEmails extends Command
{
    protected $signature = 'ac:send-pending-massive-emails
                            {--send : Enviar emails (sin esto solo muestra dry-run)}
                            {--limit=200 : Máximo de pagos a procesar}
                            {--since= : Solo pagos creados desde esta fecha (YYYY-MM-DD)}';

    protected $description = 'Reenvía emails de comprobante para pagos AC del masivo que quedaron sin email';

    public function handle(SuccessPaymentEmailService $emailService): int
    {
        $send = $this->option('send');
        $limit = (int) $this->option('limit');
        $since = $this->option('since');

        $query = Payment::where('document_type', 'AC')
            ->whereIn('status', ['approved', 'completed'])
            ->where('amount', '>', 0)
            ->where(function ($q) {
                $q->where('email_sent', false)
                  ->orWhereNull('email_sent');
            })
            // Solo pagos que vinieron de la importación masiva
            ->whereRaw("JSON_EXTRACT(gateway_response, '$.import_row') IS NOT NULL")
            ->whereHas('orderDetail', function ($q) {
                $q->whereNotNull('email')->where('email', '!=', '');
            })
            ->with(['orderDetail.order.participant'])
            ->orderBy('created_at', 'asc');

        if ($since) {
            $query->where('created_at', '>=', $since);
        }

        $total = $query->count();
        $this->info("Pagos AC del masivo sin email: {$total}");

        if ($total === 0) {
            $this->info('Nada que enviar.');
            return 0;
        }

        if (!$send) {
            $this->warn('Modo DRY-RUN: solo muestra los primeros pagos. Usa --send para enviar realmente.');
            $sample = $query->limit(min(10, $limit))->get();
            foreach ($sample as $p) {
                $name = $p->orderDetail?->order?->participant?->full_name ?? 'N/A';
                $email = $p->orderDetail?->email ?? 'N/A';
                $this->line("  #{$p->id} | {$name} | {$email} | \${$p->amount} | {$p->created_at}");
            }
            return 0;
        }

        $payments = $query->limit($limit)->get();
        $sent = 0;
        $failed = 0;

        $bar = $this->output->createProgressBar($payments->count());
        $bar->start();

        foreach ($payments as $payment) {
            try {
                $orderDetail = $payment->orderDetail;
                if (!$orderDetail || empty($orderDetail->email)) {
                    $failed++;
                    $bar->advance();
                    continue;
                }

                $success = $emailService->sendSuccessPaymentEmail($orderDetail, $payment);

                if ($success) {
                    $payment->update([
                        'email_sent' => true,
                        'email_sent_at' => now(),
                    ]);
                    $sent++;
                } else {
                    $failed++;
                }
            } catch (\Exception $e) {
                Log::warning('SendPendingAcMassiveEmails: error en payment ' . $payment->id, [
                    'error' => $e->getMessage(),
                ]);
                $failed++;
            }
            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);
        $this->info("Enviados: {$sent}");
        if ($failed > 0) {
            $this->warn("Fallidos: {$failed}");
        }

        return 0;
    }
}
