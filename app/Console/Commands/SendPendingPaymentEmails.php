<?php

namespace App\Console\Commands;

use App\Models\Payment;
use App\Models\OrderDetail;
use App\Helpers\PaymentDocumentTypeHelper;
use App\Services\Mail\SuccessPaymentEmailService;
use App\Services\Client\PaymentGateway\VirtualPosService;
use App\Services\Client\Integration\BsaleService;
use App\Services\PDF\ContractService;
use App\Services\PDF\PaymentReceiptService;
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
                            {--skip-verification : Omitir verificación con pasarela}
                            {--dry-run : Mostrar qué emails se enviarían sin enviarlos realmente}
                            {--limit=50 : Límite de emails a enviar por ejecución}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Procesar pagos pendientes: verificar con pasarela, generar documentos y enviar emails';

    protected SuccessPaymentEmailService $emailService;
    protected VirtualPosService $virtualPosService;
    protected BsaleService $bsaleService;
    protected ContractService $contractService;
    protected PaymentReceiptService $receiptService;

    public function __construct(
        SuccessPaymentEmailService $emailService,
        VirtualPosService $virtualPosService,
        BsaleService $bsaleService,
        ContractService $contractService,
        PaymentReceiptService $receiptService
    ) {
        parent::__construct();
        $this->emailService = $emailService;
        $this->virtualPosService = $virtualPosService;
        $this->bsaleService = $bsaleService;
        $this->contractService = $contractService;
        $this->receiptService = $receiptService;
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $delayMinutes = config('lat90.payment.email_delay_minutes', 10);
        $isDryRun = $this->option('dry-run') || env('PAYMENT_EMAIL_DRY_RUN', false);
        $skipVerification = $this->option('skip-verification');

        // Log estadístico al inicio
        $this->logStatistics($delayMinutes);

        if ($isDryRun) {
            $this->warn("🔍 [DRY-RUN] Modo simulación - NO se procesarán pagos");
        }

        $this->info("🔍 Buscando pagos pendientes (delay: {$delayMinutes} min)...");

        try {
            $payments = $this->getPendingPayments();

            if ($payments->isEmpty()) {
                $this->info('✅ No hay pagos pendientes de procesar');
                return Command::SUCCESS;
            }

            $this->info("📧 Encontrados {$payments->count()} pagos pendientes\n");

            if ($isDryRun) {
                $this->showDryRunDetails($payments);
                return Command::SUCCESS;
            }

            $successCount = 0;
            $errorCount = 0;
            $skippedCount = 0;
            $verificationFailedCount = 0;

            foreach ($payments as $payment) {
                try {
                    $result = $this->processPayment($payment, $skipVerification);

                    switch ($result) {
                        case 'success':
                            $successCount++;
                            $this->line("  ✅ Payment #{$payment->id} - Procesado y email enviado");
                            break;
                        case 'skipped':
                            $skippedCount++;
                            $this->line("  ⏭️  Payment #{$payment->id} - Omitido (sin datos requeridos)");
                            break;
                        case 'verification_failed':
                            $verificationFailedCount++;
                            $this->line("  🔄 Payment #{$payment->id} - Reprogramado (verificación pendiente)");
                            break;
                        default:
                            $errorCount++;
                            $this->line("  ⚠️  Payment #{$payment->id} - Error en procesamiento");
                    }
                } catch (Exception $e) {
                    $errorCount++;
                    $this->error("  ❌ Payment #{$payment->id} - Error: {$e->getMessage()}");
                    $this->handleProcessingFailure($payment, $e->getMessage());

                    Log::error('Error procesando pago', [
                        'payment_id' => $payment->id,
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString()
                    ]);
                }
            }

            $this->newLine();
            $this->info("📊 Resumen:");
            $this->info("   ✅ Procesados: {$successCount}");
            $this->info("   🔄 Verificación pendiente: {$verificationFailedCount}");
            $this->info("   ⏭️  Omitidos: {$skippedCount}");
            $this->info("   ❌ Errores: {$errorCount}");

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
     * Procesar un pago: verificar, generar documentos y enviar email
     * @return string 'success', 'skipped', 'verification_failed', 'error'
     */
    protected function processPayment(Payment $payment, bool $skipVerification = false): string
    {
        $orderDetail = $payment->orderDetail;

        if (!$orderDetail) {
            Log::warning('Payment sin OrderDetail asociado', [
                'payment_id' => $payment->id
            ]);
            return 'skipped';
        }

        Log::info('=== INICIO: Procesando pago para email ===', [
            'payment_id' => $payment->id,
            'order_detail_id' => $orderDetail->id,
            'installment_number' => $orderDetail->installment_number,
            'attempt' => ($payment->email_attempts ?? 0) + 1,
        ]);

        // PASO 1: Re-verificar estado del pago con la pasarela
        if (!$skipVerification) {
            $verificationResult = $this->verifyPaymentWithGateway($payment);

            if (!$verificationResult['confirmed']) {
                Log::info('Pago no confirmado en pasarela, reprogramando', [
                    'payment_id' => $payment->id,
                    'gateway_status' => $verificationResult['status'] ?? 'unknown',
                ]);
                return 'verification_failed';
            }
        }

        // PASO 2: Determinar tipos de documento a generar
        $documentTypes = PaymentDocumentTypeHelper::determineDocumentTypes($payment, $orderDetail);

        Log::info('Tipos de documento determinados', [
            'payment_id' => $payment->id,
            'document_types' => $documentTypes,
            'descriptions' => PaymentDocumentTypeHelper::getDocumentTypeDescriptions($documentTypes),
        ]);

        // PASO 3: Generar documentos según el tipo
        $generatedDocuments = $this->generateDocuments($payment, $orderDetail, $documentTypes);

        // PASO 4: Actualizar payment con tipos de documento generados
        $payment->update([
            'generated_document_types' => $documentTypes,
            'contract_path' => $generatedDocuments['contract_path'] ?? null,
            'receipt_path' => $generatedDocuments['receipt_path'] ?? null,
        ]);

        // PASO 5: Enviar email con los documentos
        $this->emailService->sendSuccessPaymentEmail($orderDetail, $payment);

        // PASO 6: Marcar como enviado
        $payment->update([
            'email_sent' => true,
            'email_sent_at' => now(),
            'email_attempts' => ($payment->email_attempts ?? 0) + 1,
        ]);

        Log::info('=== FIN: Pago procesado exitosamente ===', [
            'payment_id' => $payment->id,
            'document_types' => $documentTypes,
            'bsale_generated' => !empty($payment->bsale_document_id),
            'contract_generated' => !empty($generatedDocuments['contract_path']),
            'receipt_generated' => !empty($generatedDocuments['receipt_path']),
        ]);

        return 'success';
    }

    /**
     * Verificar estado del pago con la pasarela (VirtualPOS)
     */
    protected function verifyPaymentWithGateway(Payment $payment): array
    {
        try {
            // Obtener el ID externo del pago para consultar a VirtualPOS
            $externalPaymentId = $payment->external_payment_id ?? $payment->token;

            if (!$externalPaymentId) {
                Log::warning('Payment sin external_payment_id para verificar', [
                    'payment_id' => $payment->id,
                ]);
                // Si no hay ID externo, asumimos que el pago ya está confirmado
                return ['confirmed' => true, 'status' => 'assumed_confirmed'];
            }

            Log::info('Verificando pago con VirtualPOS', [
                'payment_id' => $payment->id,
                'external_payment_id' => $externalPaymentId,
            ]);

            $result = $this->virtualPosService->confirmTransaction($externalPaymentId);

            $isConfirmed = $result['success'] &&
                           in_array($result['status'] ?? '', VirtualPosService::APPROVED_STATUSES);

            Log::info('Resultado verificación VirtualPOS', [
                'payment_id' => $payment->id,
                'success' => $result['success'] ?? false,
                'status' => $result['status'] ?? 'unknown',
                'is_confirmed' => $isConfirmed,
            ]);

            return [
                'confirmed' => $isConfirmed,
                'status' => $result['status'] ?? 'unknown',
                'data' => $result,
            ];

        } catch (Exception $e) {
            Log::error('Error verificando pago con VirtualPOS', [
                'payment_id' => $payment->id,
                'error' => $e->getMessage(),
            ]);

            // En caso de error de conexión, asumimos confirmado para no bloquear
            return ['confirmed' => true, 'status' => 'error_assumed_confirmed'];
        }
    }

    /**
     * Generar documentos según los tipos determinados
     */
    protected function generateDocuments(Payment $payment, OrderDetail $orderDetail, array $documentTypes): array
    {
        $result = [
            'contract_path' => null,
            'receipt_path' => null,
            'bsale_generated' => false,
        ];

        foreach ($documentTypes as $docType) {
            try {
                switch ($docType) {
                    case PaymentDocumentTypeHelper::TYPE_BOLETA:
                        // Generar Boleta BSale
                        $this->generateBsaleInvoice($payment, $orderDetail);
                        $result['bsale_generated'] = true;
                        break;

                    case PaymentDocumentTypeHelper::TYPE_CONTRATO:
                        // Generar Contrato de Reserva
                        $contractPath = $this->contractService->generateContract($orderDetail, $payment);
                        $result['contract_path'] = $contractPath;
                        Log::info('Contrato de Reserva generado', [
                            'payment_id' => $payment->id,
                            'path' => $contractPath,
                        ]);
                        break;

                    case PaymentDocumentTypeHelper::TYPE_ANTICIPO:
                        // Generar Comprobante de Anticipo
                        $receiptPath = $this->receiptService->generatePaymentReceipt($orderDetail, $payment);
                        $result['receipt_path'] = $receiptPath;
                        Log::info('Comprobante de Anticipo generado', [
                            'payment_id' => $payment->id,
                            'path' => $receiptPath,
                        ]);
                        break;
                }
            } catch (Exception $e) {
                Log::error("Error generando documento {$docType}", [
                    'payment_id' => $payment->id,
                    'document_type' => $docType,
                    'error' => $e->getMessage(),
                ]);
                // Continuar con los demás documentos aunque uno falle
            }
        }

        return $result;
    }

    /**
     * Generar boleta en BSale
     */
    protected function generateBsaleInvoice(Payment $payment, OrderDetail $orderDetail): void
    {
        try {
            // Verificar si ya se generó la boleta
            if ($payment->bsale_document_id) {
                Log::info('Boleta BSale ya existe, omitiendo generación', [
                    'payment_id' => $payment->id,
                    'bsale_document_id' => $payment->bsale_document_id,
                ]);
                return;
            }

            $bsaleResult = $this->bsaleService->generateInvoice($orderDetail, $payment);

            if ($bsaleResult) {
                $payment->update([
                    'bsale_document_id' => $bsaleResult['id'] ?? null,
                    'bsale_number' => $bsaleResult['number'] ?? null,
                    'bsale_token' => $bsaleResult['token'] ?? null,
                ]);

                Log::info('Boleta BSale generada exitosamente', [
                    'payment_id' => $payment->id,
                    'bsale_document_id' => $bsaleResult['id'] ?? null,
                    'bsale_number' => $bsaleResult['number'] ?? null,
                ]);
            }
        } catch (Exception $e) {
            Log::error('Error generando Boleta BSale', [
                'payment_id' => $payment->id,
                'error' => $e->getMessage(),
            ]);
            // No lanzar excepción para no interrumpir el flujo
        }
    }

    /**
     * Mostrar detalles en modo dry-run
     */
    protected function showDryRunDetails($payments): void
    {
        $delayMinutes = config('lat90.payment.email_delay_minutes', 10);
        $maxAttempts = config('lat90.payment.email_max_attempts', 5);

        $this->newLine();
        $this->info("📋 Configuración actual:");
        $this->line("   • Delay antes de enviar: {$delayMinutes} minutos");
        $this->line("   • Máximo de intentos: {$maxAttempts}");
        $this->newLine();

        $this->info("📧 Pagos que serían procesados:");
        $this->newLine();

        $tableData = [];
        foreach ($payments as $payment) {
            $orderDetail = $payment->orderDetail;
            $participant = $orderDetail?->order?->participant;
            $program = $orderDetail?->order?->programCourse;

            $ageMinutes = $payment->created_at ? Carbon::now()->diffInMinutes($payment->created_at) : 0;

            // Determinar tipos de documento
            $docTypes = PaymentDocumentTypeHelper::determineDocumentTypes($payment, $orderDetail);

            $tableData[] = [
                'ID' => $payment->id,
                'Monto' => '$' . number_format($payment->amount ?? 0, 0, ',', '.'),
                'Email' => mb_substr($participant?->email ?? $orderDetail?->email ?? 'N/A', 0, 25),
                'Participante' => mb_substr($participant?->full_name ?? 'N/A', 0, 20),
                'Docs' => implode('+', $docTypes),
                'Antigüedad' => "{$ageMinutes} min",
                'Intentos' => ($payment->email_attempts ?? 0) . "/{$maxAttempts}",
            ];
        }

        $this->table(
            ['ID', 'Monto', 'Email', 'Participante', 'Docs', 'Antigüedad', 'Intentos'],
            $tableData
        );

        $this->newLine();
        $this->info("📝 Leyenda de Documentos:");
        $this->line("   B2 = Boleta Electrónica (BSale)");
        $this->line("   CR = Contrato de Reserva");
        $this->line("   AC = Comprobante de Anticipo");
        $this->newLine();
        $this->warn("⚠️  [DRY-RUN] No se procesaron pagos. Ejecuta sin --dry-run para procesar.");
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
            'paymentGateway',
            'paymentOption'
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
     * Manejar fallo de procesamiento
     */
    protected function handleProcessingFailure(Payment $payment, ?string $errorMessage = null): void
    {
        $maxAttempts = config('lat90.payment.email_max_attempts', 5);
        $currentAttempts = ($payment->email_attempts ?? 0) + 1;

        $payment->update([
            'email_attempts' => $currentAttempts,
            'email_last_error' => $errorMessage,
        ]);

        Log::warning('Fallo en procesamiento de pago', [
            'payment_id' => $payment->id,
            'attempt' => $currentAttempts,
            'max_attempts' => $maxAttempts,
            'error' => $errorMessage,
        ]);

        if ($currentAttempts >= $maxAttempts) {
            $this->notifyAdminOfFailure($payment, $errorMessage);
        }
    }

    /**
     * Log estadístico del estado de pagos y emails
     */
    protected function logStatistics(int $delayMinutes): void
    {
        $now = Carbon::now();
        $cutoffTime = $now->copy()->subMinutes($delayMinutes);
        $maxAttempts = config('lat90.payment.email_max_attempts', 5);

        // Pagos completados totales (últimas 24 horas)
        $completedPayments24h = Payment::where('status', 'completed')
            ->where('created_at', '>=', $now->copy()->subHours(24))
            ->count();

        // Pagos con email ya enviado (últimas 24 horas)
        $emailsSent24h = Payment::where('status', 'completed')
            ->where('email_sent', true)
            ->where('email_sent_at', '>=', $now->copy()->subHours(24))
            ->count();

        // Pagos pendientes de envío de email (cumplen condiciones)
        $pendingEmails = Payment::where('status', 'completed')
            ->where(function ($q) {
                $q->where('email_sent', false)->orWhereNull('email_sent');
            })
            ->where('created_at', '<=', $cutoffTime)
            ->where(function ($q) use ($maxAttempts) {
                $q->whereNull('email_attempts')->orWhere('email_attempts', '<', $maxAttempts);
            })
            ->count();

        // Pagos esperando el delay (aún no cumplen tiempo mínimo)
        $waitingDelay = Payment::where('status', 'completed')
            ->where(function ($q) {
                $q->where('email_sent', false)->orWhereNull('email_sent');
            })
            ->where('created_at', '>', $cutoffTime)
            ->count();

        // Pagos con errores (alcanzaron max intentos)
        $failedEmails = Payment::where('status', 'completed')
            ->where(function ($q) {
                $q->where('email_sent', false)->orWhereNull('email_sent');
            })
            ->where('email_attempts', '>=', $maxAttempts)
            ->count();

        // Pagos pendientes de confirmación (no completed)
        $pendingPayments = Payment::whereIn('status', ['pending', 'processing'])
            ->where('created_at', '>=', $now->copy()->subHours(24))
            ->count();

        $stats = [
            'timestamp' => $now->format('Y-m-d H:i:s'),
            'delay_config_minutes' => $delayMinutes,
            'max_attempts' => $maxAttempts,
            'last_24h' => [
                'completed_payments' => $completedPayments24h,
                'emails_sent' => $emailsSent24h,
                'pending_confirmation' => $pendingPayments,
            ],
            'email_queue' => [
                'ready_to_send' => $pendingEmails,
                'waiting_delay' => $waitingDelay,
                'failed_max_attempts' => $failedEmails,
            ],
        ];

        Log::info('📊 [SCHEDULER] Estadísticas de pagos y emails', $stats);

        // También mostrar en consola
        $this->newLine();
        $this->info("═══════════════════════════════════════════════════════════");
        $this->info("📊 ESTADÍSTICAS DE PAGOS - {$now->format('d/m/Y H:i:s')}");
        $this->info("═══════════════════════════════════════════════════════════");
        $this->line("   Configuración: delay={$delayMinutes}min, max_intentos={$maxAttempts}");
        $this->newLine();
        $this->info("   📈 Últimas 24 horas:");
        $this->line("      • Pagos completados: {$completedPayments24h}");
        $this->line("      • Emails enviados: {$emailsSent24h}");
        $this->line("      • Pagos pendientes confirmación: {$pendingPayments}");
        $this->newLine();
        $this->info("   📧 Cola de emails:");
        $this->line("      • Listos para enviar: {$pendingEmails}");
        $this->line("      • Esperando delay ({$delayMinutes}min): {$waitingDelay}");
        if ($failedEmails > 0) {
            $this->error("      • ⚠️  Fallidos (requieren revisión): {$failedEmails}");
        } else {
            $this->line("      • Fallidos: {$failedEmails}");
        }
        $this->info("═══════════════════════════════════════════════════════════");
        $this->newLine();
    }

    /**
     * Notificar al administrador sobre fallo
     */
    protected function notifyAdminOfFailure(Payment $payment, ?string $errorMessage = null): void
    {
        $maxAttempts = config('lat90.payment.email_max_attempts', 5);

        Log::error('Procesamiento de pago falló después de todos los intentos', [
            'payment_id' => $payment->id,
            'max_attempts' => $maxAttempts,
            'error' => $errorMessage,
        ]);

        try {
            $orderDetail = $payment->orderDetail;
            $participant = $orderDetail?->order?->participant;
            $adminEmail = config('lat90.company.email', 'info@latitud90.cl');

            Mail::raw(
                "⚠️ ALERTA: Procesamiento de pago falló\n\n" .
                "Payment ID: {$payment->id}\n" .
                "Participante: " . ($participant?->full_name ?? 'N/A') . "\n" .
                "Email destino: " . ($participant?->email ?? 'N/A') . "\n" .
                "Monto: $" . number_format($payment->amount ?? 0, 0, ',', '.') . "\n" .
                "Intentos realizados: {$maxAttempts}\n" .
                "Último error: " . ($errorMessage ?? 'Sin mensaje de error') . "\n\n" .
                "Por favor revise manualmente este pago en el panel de administración.",
                function ($message) use ($adminEmail, $payment) {
                    $message->to($adminEmail)
                        ->subject("⚠️ Pago #{$payment->id} no pudo ser procesado");
                }
            );

            Log::info('Notificación de fallo enviada al admin', [
                'payment_id' => $payment->id,
                'admin_email' => $adminEmail,
            ]);

        } catch (Exception $e) {
            Log::error('Error al notificar admin sobre fallo', [
                'payment_id' => $payment->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
