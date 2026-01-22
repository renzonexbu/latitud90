<?php

namespace App\Console\Commands;

use App\Models\Payment;
use App\Models\OrderDetail;
use App\Services\Client\PaymentGateway\VirtualPosService;
use App\Services\Client\Integration\BsaleService;
use App\Services\Mail\SuccessPaymentEmailService;
use App\Helpers\PaymentDocumentTypeHelper;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ReconfirmPayments extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'payments:reconfirm
                            {--status=pending : Estado de pagos a reconfirmar (pending, all, failed)}
                            {--payment-id= : ID específico de un pago a reconfirmar}
                            {--dry-run : Solo mostrar lo que se haría, sin actualizar la base de datos}
                            {--skip-email : Omitir el envío de correo de confirmación}
                            {--skip-bsale : Omitir la generación de boleta Bsale}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reconfirmar pagos con VirtualPOS, actualizar el estado en la base de datos, generar boleta y enviar correo';

    public function __construct(
        private VirtualPosService $virtualPosService,
        private BsaleService $bsaleService,
        private SuccessPaymentEmailService $emailService
    ) {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $status = $this->option('status');
        $paymentId = $this->option('payment-id');
        $dryRun = $this->option('dry-run');
        $skipEmail = $this->option('skip-email');
        $skipBsale = $this->option('skip-bsale');

        if ($dryRun) {
            $this->warn('🔍 Modo DRY-RUN: No se realizarán cambios en la base de datos');
        }
        if ($skipEmail) {
            $this->warn('📧 Se omitirá el envío de correos de confirmación');
        }
        if ($skipBsale) {
            $this->warn('📄 Se omitirá la generación de boletas Bsale');
        }

        $this->info('🔄 Iniciando reconfirmación de pagos con VirtualPOS...');

        // Construir query de pagos
        $query = Payment::query()
            ->whereNotNull('token')
            ->where('token', '!=', '');

        if ($paymentId) {
            $query->where('id', $paymentId);
            $this->info("📌 Reconfirmando pago específico ID: {$paymentId}");
        } else {
            switch ($status) {
                case 'pending':
                    $query->where('status', 'pending');
                    $this->info('📌 Reconfirmando pagos con estado: pending');
                    break;
                case 'failed':
                    $query->whereIn('status', ['failed', 'rejected']);
                    $this->info('📌 Reconfirmando pagos con estado: failed/rejected');
                    break;
                case 'all':
                    $this->info('📌 Reconfirmando TODOS los pagos con token');
                    break;
                default:
                    $query->where('status', $status);
                    $this->info("📌 Reconfirmando pagos con estado: {$status}");
            }
        }

        $payments = $query->with(['orderDetail.order'])->get();
        $total = $payments->count();

        if ($total === 0) {
            $this->warn('⚠️  No se encontraron pagos para reconfirmar');
            return Command::SUCCESS;
        }

        $this->info("📊 Total de pagos a procesar: {$total}");
        $this->newLine();

        $updated = 0;
        $noChanges = 0;
        $errors = 0;
        $notFound = 0;

        // No usar barra de progreso cuando hay output detallado
        // $progressBar = $this->output->createProgressBar($total);
        // $progressBar->start();

        foreach ($payments as $payment) {
            try {
                $result = $this->processPayment($payment, $dryRun, $skipEmail, $skipBsale);

                switch ($result) {
                    case 'updated':
                        $updated++;
                        break;
                    case 'no_change':
                        $noChanges++;
                        break;
                    case 'not_found':
                        $notFound++;
                        break;
                    case 'error':
                        $errors++;
                        break;
                }
            } catch (\Exception $e) {
                $errors++;
                Log::error('ReconfirmPayments: Error procesando pago', [
                    'payment_id' => $payment->id,
                    'error' => $e->getMessage(),
                ]);
            }

            // Se omite la barra de progreso para ver los logs detallados
        }

        $this->newLine(2);

        // Resumen
        $this->info('📈 Resumen de reconfirmación:');
        $this->table(
            ['Métrica', 'Cantidad'],
            [
                ['Total procesados', $total],
                ['Actualizados', $updated],
                ['Sin cambios', $noChanges],
                ['No encontrados en VirtualPOS', $notFound],
                ['Errores', $errors],
            ]
        );

        if ($dryRun) {
            $this->warn('🔍 Este fue un DRY-RUN. Ejecuta sin --dry-run para aplicar los cambios.');
        }

        return Command::SUCCESS;
    }

    /**
     * Procesar un pago individual
     */
    private function processPayment(Payment $payment, bool $dryRun, bool $skipEmail = false, bool $skipBsale = false): string
    {
        $token = $payment->token;

        if (empty($token)) {
            return 'error';
        }

        // Consultar estado en VirtualPOS
        $result = $this->virtualPosService->confirmTransaction($token);

        if (!isset($result['success'])) {
            return 'error';
        }

        // Si no se encontró la transacción
        if (isset($result['error']) && str_contains($result['error'], 'no encontrada')) {
            $this->newLine();
            $this->warn("  ⚠️  Payment #{$payment->id}: No encontrado en VirtualPOS (token: {$token})");
            return 'not_found';
        }

        $virtualPosStatus = $result['status'] ?? 'unknown';
        $isApproved = in_array($virtualPosStatus, VirtualPosService::APPROVED_STATUSES);
        $isRejected = in_array($virtualPosStatus, VirtualPosService::REJECTED_STATUSES);

        // Determinar nuevo estado
        $newStatus = 'pending';
        if ($isApproved) {
            $newStatus = 'approved';
        } elseif ($isRejected) {
            $newStatus = 'failed';
        }

        // Verificar si hay cambios
        $currentStatus = $payment->status;
        $statusChanged = $currentStatus !== $newStatus;

        // Si no hay cambios y el pago ya estaba aprobado, verificar si necesita post-procesamiento
        $needsPostProcessing = $isApproved && (!$payment->email_sent || empty($payment->bsale_number));

        if (!$statusChanged && !$needsPostProcessing) {
            return 'no_change';
        }

        // Mostrar información del cambio
        $this->newLine();
        $statusIcon = $isApproved ? '✅' : ($isRejected ? '❌' : '⏳');

        if ($statusChanged) {
            $this->line("  {$statusIcon} Payment #{$payment->id}: {$currentStatus} → {$newStatus} (VirtualPOS: {$virtualPosStatus})");
        } else {
            $this->line("  {$statusIcon} Payment #{$payment->id}: Sin cambio de estado, ejecutando post-procesamiento");
        }

        if ($dryRun) {
            if ($isApproved) {
                $this->line("    📄 Se generaría boleta Bsale (document_type: {$payment->document_type})");
                $this->line("    📧 Se enviaría correo de confirmación");
            }
            return 'updated';
        }

        // Actualizar pago si cambió el estado
        if ($statusChanged) {
            $updateData = [
                'status' => $newStatus,
                'gateway_response' => $result['full_response'] ?? $result,
                'authorization_code' => $result['authorization_code'] ?? $payment->authorization_code,
            ];

            // Si el pago fue aprobado, también actualizar el status a 'completed' para consistencia
            if ($isApproved) {
                $updateData['status'] = 'completed';

                // Asegurar que document_type esté establecido
                if (empty($payment->document_type)) {
                    $updateData['document_type'] = PaymentDocumentTypeHelper::determineDocumentType(
                        $payment->orderDetail->order->program_id ?? null
                    );
                }
            }

            $payment->update($updateData);
            $payment->refresh();
        }

        // Actualizar OrderDetail si existe
        $orderDetail = $payment->orderDetail;
        if ($orderDetail && $statusChanged) {
            $orderDetail->update([
                'status' => $isApproved ? 'paid' : ($isRejected ? 'failed' : 'pending'),
                'is_paid' => $isApproved,
                'paid_at' => $isApproved ? ($orderDetail->paid_at ?? now()) : null,
                'gateway_response' => $result['full_response'] ?? $result,
            ]);

            // Actualizar Order si existe
            if ($orderDetail->order) {
                $orderDetail->order->refreshStatus();
            }

            // Si el pago fue aprobado y es una cuota, marcarla como pagada
            if ($isApproved && $orderDetail->installment_number >= 1) {
                $this->markInstallmentAsPaid($orderDetail, $payment);
            }
        }

        // Si el pago fue aprobado, ejecutar post-procesamiento (boleta y email)
        if ($isApproved && $orderDetail) {
            $this->executePostPaymentProcessing($payment, $orderDetail, $skipBsale, $skipEmail);
        }

        Log::info('ReconfirmPayments: Pago actualizado', [
            'payment_id' => $payment->id,
            'old_status' => $currentStatus,
            'new_status' => $newStatus,
            'virtualpos_status' => $virtualPosStatus,
            'post_processing_executed' => $isApproved,
        ]);

        return 'updated';
    }

    /**
     * Ejecutar post-procesamiento del pago (boleta Bsale y envío de email)
     */
    private function executePostPaymentProcessing(Payment $payment, OrderDetail $orderDetail, bool $skipBsale, bool $skipEmail): void
    {
        // Generar boleta Bsale si corresponde
        if (!$skipBsale) {
            $this->generateBsaleInvoice($payment, $orderDetail);
        }

        // Enviar correo de confirmación si corresponde
        if (!$skipEmail) {
            $this->sendConfirmationEmail($payment, $orderDetail);
        }
    }

    /**
     * Generar boleta en Bsale
     */
    private function generateBsaleInvoice(Payment $payment, OrderDetail $orderDetail): void
    {
        try {
            // Verificar si ya tiene boleta generada
            if (!empty($payment->bsale_number)) {
                $this->line("    📄 Boleta ya existe: #{$payment->bsale_number}");
                return;
            }

            // Verificar si el document_type permite generar boleta (solo B2)
            if ($payment->document_type !== 'B2') {
                $this->line("    📄 No genera boleta - document_type: {$payment->document_type} (solo B2 genera boleta)");
                return;
            }

            $this->line("    📄 Generando boleta Bsale...");

            $bsaleResult = $this->bsaleService->generateInvoice($orderDetail, $payment);

            if ($bsaleResult) {
                // Recargar payment para obtener datos actualizados
                $payment->refresh();
                $this->info("    ✅ Boleta generada: #{$payment->bsale_number}");
            } else {
                $this->warn("    ⚠️  No se generó boleta (puede ser por document_type o error)");
            }
        } catch (\Exception $e) {
            $this->error("    ❌ Error generando boleta: {$e->getMessage()}");
            Log::error('ReconfirmPayments: Error generando boleta', [
                'payment_id' => $payment->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Enviar correo de confirmación
     */
    private function sendConfirmationEmail(Payment $payment, OrderDetail $orderDetail): void
    {
        try {
            // Verificar si ya se envió el email
            if ($payment->email_sent) {
                $this->line("    📧 Email ya enviado anteriormente");
                return;
            }

            // Verificar que el pago esté completado
            if (!in_array($payment->status, ['completed', 'approved'])) {
                $this->warn("    ⚠️  No se envía email - estado del pago: {$payment->status}");
                return;
            }

            $this->line("    📧 Enviando correo de confirmación a {$orderDetail->email}...");

            $emailSent = $this->emailService->sendSuccessPaymentEmail($orderDetail, $payment);

            if ($emailSent) {
                // Marcar email como enviado
                $payment->update(['email_sent' => true]);
                $this->info("    ✅ Email enviado exitosamente");
            } else {
                $this->warn("    ⚠️  No se pudo enviar el email");
            }
        } catch (\Exception $e) {
            $this->error("    ❌ Error enviando email: {$e->getMessage()}");
            Log::error('ReconfirmPayments: Error enviando email', [
                'payment_id' => $payment->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Marcar cuota como pagada
     */
    private function markInstallmentAsPaid(OrderDetail $orderDetail, Payment $payment): void
    {
        try {
            $installment = \App\Models\Installment::where('installment_number', $orderDetail->installment_number)
                ->whereHas('installmentPlan', function($query) use ($orderDetail) {
                    $query->where('participant_id', $orderDetail->order->participant_id)
                          ->where('program_id', $orderDetail->order->program_id);
                })
                ->first();

            if ($installment && !$installment->is_paid) {
                $installment->markAsPaid(
                    $orderDetail->order_id,
                    $orderDetail->id,
                    $payment->id
                );

                $this->line("    💰 Cuota #{$installment->installment_number} marcada como pagada");
            }
        } catch (\Exception $e) {
            Log::warning('ReconfirmPayments: Error marcando cuota como pagada', [
                'order_detail_id' => $orderDetail->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
