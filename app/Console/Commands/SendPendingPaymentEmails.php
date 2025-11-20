<?php

namespace App\Console\Commands;

use App\Models\Payment;
use App\Models\OrderDetail;
use App\Services\Mail\SuccessPaymentEmailService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

class SendPendingPaymentEmails extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'payments:send-pending-emails
                            {--payment= : ID de un pago específico}
                            {--force : Forzar envío aunque email_sent=true}
                            {--limit=50 : Límite de emails a enviar por ejecución}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Enviar emails pendientes de pagos exitosos con reintentos automáticos';

    protected SuccessPaymentEmailService $emailService;

    public function __construct(SuccessPaymentEmailService $emailService)
    {
        parent::__construct();
        $this->emailService = $emailService;
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('🔍 Buscando pagos con emails pendientes de envío...');

        try {
            // Obtener pagos pendientes de envío
            $payments = $this->getPendingPayments();

            if ($payments->isEmpty()) {
                $this->info('✅ No hay emails pendientes de envío');
                return Command::SUCCESS;
            }

            $this->info("📧 Encontrados {$payments->count()} pagos con emails pendientes\n");

            $successCount = 0;
            $errorCount = 0;

            foreach ($payments as $payment) {
                try {
                    $result = $this->sendPaymentEmail($payment);

                    if ($result) {
                        $successCount++;
                        $this->line("  ✅ Payment #{$payment->id} - Email enviado");
                    } else {
                        $errorCount++;
                        $this->line("  ⚠️  Payment #{$payment->id} - No se pudo enviar");
                    }
                } catch (Exception $e) {
                    $errorCount++;
                    $this->error("  ❌ Payment #{$payment->id} - Error: {$e->getMessage()}");

                    Log::error('Error enviando email de pago', [
                        'payment_id' => $payment->id,
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString()
                    ]);
                }
            }

            $this->newLine();
            $this->info("📊 Resumen:");
            $this->info("   ✅ Enviados: {$successCount}");
            $this->info("   ❌ Errores: {$errorCount}");
            $this->info("   📝 Total procesados: " . ($successCount + $errorCount));

            return Command::SUCCESS;

        } catch (Exception $e) {
            $this->error("❌ Error fatal: {$e->getMessage()}");
            Log::error('Error fatal en SendPendingPaymentEmails', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return Command::FAILURE;
        }
    }

    /**
     * Obtener pagos con emails pendientes de envío
     */
    protected function getPendingPayments()
    {
        $query = Payment::with([
            'orderDetail',
            'orderDetail.order',
            'orderDetail.order.participant',
            'orderDetail.order.participant.documentType',
            'orderDetail.order.participant.emergencyContacts',
            'orderDetail.order.programCourse',
            'orderDetail.order.programCourse.program',
            'orderDetail.order.programCourse.course',
            'orderDetail.order.programCourse.course.institution',
            'paymentGateway'
        ])
        ->where('status', 'completed');

        // Si se especifica un payment ID
        if ($paymentId = $this->option('payment')) {
            return $query->where('id', $paymentId)->get();
        }

        // Si se fuerza el envío
        if ($this->option('force')) {
            $query->where('email_sent', true);
        } else {
            // Solo pagos sin email enviado
            $query->where(function ($q) {
                $q->where('email_sent', false)
                  ->orWhereNull('email_sent');
            });
        }

        // Limitar cantidad
        $limit = (int) $this->option('limit');
        $query->limit($limit);

        // Ordenar por más antiguos primero
        $query->orderBy('created_at', 'asc');

        return $query->get();
    }

    /**
     * Enviar email de pago exitoso
     */
    protected function sendPaymentEmail(Payment $payment): bool
    {
        $orderDetail = $payment->orderDetail;

        if (!$orderDetail) {
            Log::warning('Payment sin OrderDetail asociado', [
                'payment_id' => $payment->id
            ]);
            return false;
        }

        Log::info('Enviando email de pago exitoso', [
            'payment_id' => $payment->id,
            'order_detail_id' => $orderDetail->id,
            'installment_number' => $orderDetail->installment_number,
        ]);

        // Enviar email usando el servicio existente
        // El servicio maneja internamente la lógica de qué adjuntos enviar
        $this->emailService->sendSuccessPaymentEmail(
            $orderDetail,
            $payment
        );

        // Marcar como enviado
        $payment->update([
            'email_sent' => true,
            'email_sent_at' => now()
        ]);

        return true;
    }
}
