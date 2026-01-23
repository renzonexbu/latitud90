<?php

namespace App\Console\Commands;

use App\Models\Payment;
use App\Models\OrderDetail;
use App\Services\Mail\SuccessPaymentEmailService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
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
                            {--no-delay : Ignorar el tiempo de espera configurado}
                            {--limit=50 : Límite de emails a enviar por ejecución}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Enviar emails pendientes de pagos exitosos con delay configurable y reintentos automáticos';

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
        $delayMinutes = config('lat90.payment.email_delay_minutes', 10);
        $this->info("🔍 Buscando pagos con emails pendientes (delay: {$delayMinutes} min)...");

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
            $skippedCount = 0;

            foreach ($payments as $payment) {
                try {
                    $result = $this->sendPaymentEmail($payment);

                    if ($result === true) {
                        $successCount++;
                        $this->line("  ✅ Payment #{$payment->id} - Email enviado");
                    } elseif ($result === 'skipped') {
                        $skippedCount++;
                        $this->line("  ⏭️  Payment #{$payment->id} - Omitido (sin datos requeridos)");
                    } else {
                        $errorCount++;
                        $this->handleEmailFailure($payment);
                        $this->line("  ⚠️  Payment #{$payment->id} - No se pudo enviar");
                    }
                } catch (Exception $e) {
                    $errorCount++;
                    $this->error("  ❌ Payment #{$payment->id} - Error: {$e->getMessage()}");
                    $this->handleEmailFailure($payment, $e->getMessage());

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
            $this->info("   ⏭️  Omitidos: {$skippedCount}");
            $this->info("   📝 Total procesados: " . ($successCount + $errorCount + $skippedCount));

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
        $delayMinutes = config('lat90.payment.email_delay_minutes', 10);
        $maxAttempts = config('lat90.payment.email_max_attempts', 5);

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

        // Aplicar delay: solo procesar pagos con más de X minutos de antigüedad
        // Esto permite que BSale genere la boleta antes de enviar el email
        if (!$this->option('no-delay')) {
            $cutoffTime = Carbon::now()->subMinutes($delayMinutes);
            $query->where('created_at', '<=', $cutoffTime);
        }

        // Excluir pagos que ya alcanzaron el máximo de intentos
        $query->where(function ($q) use ($maxAttempts) {
            $q->whereNull('email_attempts')
              ->orWhere('email_attempts', '<', $maxAttempts);
        });

        // Limitar cantidad
        $limit = (int) $this->option('limit');
        $query->limit($limit);

        // Ordenar por más antiguos primero
        $query->orderBy('created_at', 'asc');

        return $query->get();
    }

    /**
     * Enviar email de pago exitoso
     * @return bool|string true si se envió, false si falló, 'skipped' si se omitió
     */
    protected function sendPaymentEmail(Payment $payment)
    {
        $orderDetail = $payment->orderDetail;

        if (!$orderDetail) {
            Log::warning('Payment sin OrderDetail asociado', [
                'payment_id' => $payment->id
            ]);
            return 'skipped';
        }

        Log::info('Enviando email de pago exitoso', [
            'payment_id' => $payment->id,
            'order_detail_id' => $orderDetail->id,
            'installment_number' => $orderDetail->installment_number,
            'attempt' => ($payment->email_attempts ?? 0) + 1,
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
            'email_sent_at' => now(),
            'email_attempts' => ($payment->email_attempts ?? 0) + 1,
        ]);

        return true;
    }

    /**
     * Manejar fallo de envío de email
     */
    protected function handleEmailFailure(Payment $payment, ?string $errorMessage = null): void
    {
        $maxAttempts = config('lat90.payment.email_max_attempts', 5);
        $currentAttempts = ($payment->email_attempts ?? 0) + 1;

        // Incrementar contador de intentos
        $payment->update([
            'email_attempts' => $currentAttempts,
            'email_last_error' => $errorMessage,
        ]);

        Log::warning('Fallo en envío de email de pago', [
            'payment_id' => $payment->id,
            'attempt' => $currentAttempts,
            'max_attempts' => $maxAttempts,
            'error' => $errorMessage,
        ]);

        // Si alcanzó el máximo de intentos, notificar al admin
        if ($currentAttempts >= $maxAttempts) {
            $this->notifyAdminOfFailure($payment, $errorMessage);
        }
    }

    /**
     * Notificar al administrador sobre fallo de email
     */
    protected function notifyAdminOfFailure(Payment $payment, ?string $errorMessage = null): void
    {
        $maxAttempts = config('lat90.payment.email_max_attempts', 5);

        Log::error('Email de pago falló después de todos los intentos', [
            'payment_id' => $payment->id,
            'max_attempts' => $maxAttempts,
            'error' => $errorMessage,
        ]);

        try {
            $orderDetail = $payment->orderDetail;
            $participant = $orderDetail?->order?->participant;

            // Enviar notificación al admin
            $adminEmail = config('lat90.company.email', 'info@latitud90.cl');

            Mail::raw(
                "⚠️ ALERTA: Email de confirmación de pago no pudo ser enviado\n\n" .
                "Payment ID: {$payment->id}\n" .
                "Participante: " . ($participant?->full_name ?? 'N/A') . "\n" .
                "Email destino: " . ($participant?->email ?? 'N/A') . "\n" .
                "Monto: $" . number_format($payment->amount ?? 0, 0, ',', '.') . "\n" .
                "Intentos realizados: {$maxAttempts}\n" .
                "Último error: " . ($errorMessage ?? 'Sin mensaje de error') . "\n\n" .
                "Por favor revise manualmente este pago en el panel de administración.",
                function ($message) use ($adminEmail, $payment) {
                    $message->to($adminEmail)
                        ->subject("⚠️ Email de pago #{$payment->id} no pudo ser enviado");
                }
            );

            Log::info('Notificación de fallo enviada al admin', [
                'payment_id' => $payment->id,
                'admin_email' => $adminEmail,
            ]);

        } catch (Exception $e) {
            Log::error('Error al notificar admin sobre fallo de email', [
                'payment_id' => $payment->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
