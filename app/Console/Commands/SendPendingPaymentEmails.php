<?php

namespace App\Console\Commands;

use App\Models\Payment;
use App\Models\OrderDetail;
use App\Helpers\PaymentDocumentTypeHelper;
use App\Services\Mail\SuccessPaymentEmailService;
use App\Services\Client\PaymentGateway\VirtualPosService;
use App\Services\Client\Integration\BsaleService;
use App\Services\Bsale\BsaleQueueService;
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
    protected BsaleQueueService $bsaleQueueService;
    protected ContractService $contractService;
    protected PaymentReceiptService $receiptService;

    public function __construct(
        SuccessPaymentEmailService $emailService,
        VirtualPosService $virtualPosService,
        BsaleService $bsaleService,
        BsaleQueueService $bsaleQueueService,
        ContractService $contractService,
        PaymentReceiptService $receiptService
    ) {
        parent::__construct();
        $this->emailService = $emailService;
        $this->virtualPosService = $virtualPosService;
        $this->bsaleService = $bsaleService;
        $this->bsaleQueueService = $bsaleQueueService;
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

        // Resolver pagos en estado 'pending' consultando la pasarela
        if (!$isDryRun) {
            $this->resolvePendingGatewayPayments();
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
                            // Incrementar intentos para evitar loop infinito
                            $payment->update([
                                'email_attempts' => ($payment->email_attempts ?? 0) + 1,
                            ]);
                            $this->line("  🔄 Payment #{$payment->id} - Reprogramado (verificación pendiente, intento " . $payment->email_attempts . ")");
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
        $emailSent = $this->emailService->sendSuccessPaymentEmail($orderDetail, $payment);

        // PASO 6: Marcar como enviado solo si el email se envió correctamente
        if ($emailSent) {
            $payment->update([
                'email_sent' => true,
                'email_sent_at' => now(),
                'email_attempts' => ($payment->email_attempts ?? 0) + 1,
            ]);
        } else {
            // Email no enviado (ej: email destino null) - incrementar intentos para no bloquear la cola
            $payment->update([
                'email_attempts' => ($payment->email_attempts ?? 0) + 1,
                'email_last_error' => 'Email no enviado - dirección de correo vacía o inválida',
            ]);
            Log::warning('Pago procesado pero email no enviado', [
                'payment_id' => $payment->id,
                'order_detail_id' => $orderDetail->id,
            ]);
            return 'skipped';
        }

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
     * Hace hasta 3 intentos antes de fallar
     */
    protected function verifyPaymentWithGateway(Payment $payment): array
    {
        // Pagos de suscripción ya fueron verificados por SyncSubscriptionPayments.
        // Sus tokens (cid_xxx) no son compatibles con la API de single-payment.
        if ($payment->payment_source === 'subscription') {
            Log::info('Payment de suscripción: verificación omitida (ya confirmado por sync)', [
                'payment_id' => $payment->id,
                'payment_source' => $payment->payment_source,
            ]);
            return ['confirmed' => true, 'status' => 'subscription_confirmed'];
        }

        // Obtener el ID externo del pago para consultar a VirtualPOS
        $externalPaymentId = $payment->external_payment_id ?? $payment->token;

        if (!$externalPaymentId) {
            Log::warning('Payment sin external_payment_id para verificar', [
                'payment_id' => $payment->id,
            ]);
            // Si no hay ID externo, asumimos que el pago ya está confirmado
            return ['confirmed' => true, 'status' => 'assumed_confirmed'];
        }

        // Configuración de reintentos
        $maxAttempts = 3;
        $retryDelaySeconds = 2;

        Log::info('Iniciando verificación con VirtualPOS (hasta 3 intentos)', [
            'payment_id' => $payment->id,
            'external_payment_id' => $externalPaymentId,
        ]);

        // Intentar hasta 3 veces
        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
            try {
                Log::info("VirtualPOS - Intento {$attempt}/{$maxAttempts}", [
                    'payment_id' => $payment->id,
                    'external_payment_id' => $externalPaymentId,
                ]);

                $result = $this->virtualPosService->confirmTransaction($externalPaymentId);

                $isConfirmed = $result['success'] &&
                               in_array($result['status'] ?? '', VirtualPosService::APPROVED_STATUSES);

                Log::info("VirtualPOS - Resultado intento {$attempt}", [
                    'payment_id' => $payment->id,
                    'success' => $result['success'] ?? false,
                    'status' => $result['status'] ?? 'unknown',
                    'is_confirmed' => $isConfirmed,
                ]);

                // Si la verificación fue exitosa, retornar inmediatamente
                if ($isConfirmed) {
                    Log::info("VirtualPOS - Verificación exitosa en intento {$attempt}", [
                        'payment_id' => $payment->id,
                        'status' => $result['status'],
                    ]);

                    return [
                        'confirmed' => true,
                        'status' => $result['status'] ?? 'unknown',
                        'data' => $result,
                        'attempts' => $attempt,
                    ];
                }

                // Si no está confirmado pero la API respondió, retornar el resultado
                if ($result['success'] === false && isset($result['status'])) {
                    Log::warning("VirtualPOS - Pago no confirmado en intento {$attempt}", [
                        'payment_id' => $payment->id,
                        'status' => $result['status'],
                    ]);

                    return [
                        'confirmed' => false,
                        'status' => $result['status'],
                        'data' => $result,
                        'attempts' => $attempt,
                    ];
                }

            } catch (Exception $e) {
                Log::warning("VirtualPOS - Error en intento {$attempt}/{$maxAttempts}", [
                    'payment_id' => $payment->id,
                    'error' => $e->getMessage(),
                ]);

                // Si es el último intento, retornar fallo
                if ($attempt === $maxAttempts) {
                    Log::error('VirtualPOS - Todos los intentos fallaron', [
                        'payment_id' => $payment->id,
                        'attempts' => $maxAttempts,
                        'last_error' => $e->getMessage(),
                    ]);

                    // Retornar NO confirmado para que el cron lo reintente después
                    return [
                        'confirmed' => false,
                        'status' => 'verification_error_after_retries',
                        'error' => $e->getMessage(),
                        'attempts' => $maxAttempts,
                    ];
                }

                // Esperar antes del siguiente intento
                sleep($retryDelaySeconds);
            }
        }

        // Fallback: si llegamos aquí, todos los intentos fallaron sin confirmación
        Log::error('VirtualPOS - Verificación falló después de todos los intentos', [
            'payment_id' => $payment->id,
            'attempts' => $maxAttempts,
        ]);

        return [
            'confirmed' => false,
            'status' => 'verification_failed_after_retries',
            'attempts' => $maxAttempts,
        ];
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
     * Encolar solicitud de boleta BSale (NO genera directamente)
     * La generación real se hace por separado via comando bsale:process
     */
    protected function generateBsaleInvoice(Payment $payment, OrderDetail $orderDetail): void
    {
        try {
            // Usar el servicio de cola para encolar la solicitud
            // El servicio ya hace todas las validaciones:
            // - BSale habilitado
            // - Payment confirmado
            // - No existe boleta previa
            // - No existe solicitud activa
            // - Tipo de documento correcto
            $bsaleRequest = $this->bsaleQueueService->queueBoleta(
                $payment,
                'payment_email_command',
                [
                    'order_detail_id' => $orderDetail->id,
                    'triggered_by' => 'SendPendingPaymentEmails',
                ]
            );

            if ($bsaleRequest) {
                Log::info('SendPendingPaymentEmails: Solicitud BSale encolada', [
                    'payment_id' => $payment->id,
                    'bsale_request_id' => $bsaleRequest->id,
                    'bsale_request_status' => $bsaleRequest->status,
                ]);
            } else {
                Log::info('SendPendingPaymentEmails: No se encoló BSale (ya existe o no aplica)', [
                    'payment_id' => $payment->id,
                ]);
            }

        } catch (Exception $e) {
            Log::error('SendPendingPaymentEmails: Error al encolar solicitud BSale', [
                'payment_id' => $payment->id,
                'error' => $e->getMessage(),
            ]);
            // No lanzar excepción para no interrumpir el flujo de emails
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
        ->where('status', 'completed')
        // Excluir pagos presenciales (gateway 4): su boleta se genera al registrarlos
        // y no requieren envío de email al cliente
        ->where('payment_gateway_id', '!=', 4)
        // Excluir reversos administrativos (RA) y montos negativos: nunca generan email al cliente
        ->where('document_type', '!=', 'RA')
        ->where('amount', '>', 0);

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
        // Excluir gateway 4 (presencial) para que coincida con getPendingPayments()
        $pendingEmails = Payment::where('status', 'completed')
            ->where('payment_gateway_id', '!=', 4)
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

    /**
     * Determinar si un pago es de suscripción (PAT) o de pago total/contado
     *
     * Es suscripción si:
     * - Order tiene payment_type = 'monthly' o 'subscription'
     * - O tiene external_payment_id (charge de VirtualPOS suscripción)
     * - O el order_number empieza con 'SUB-'
     */
    protected function isSubscriptionPayment(Payment $payment, $order): bool
    {
        // Verificar payment_type de la orden
        $paymentType = $order->payment_type ?? null;
        if (in_array($paymentType, ['monthly', 'subscription', 'pat'])) {
            return true;
        }

        // Verificar si tiene external_payment_id (típico de charges de suscripción)
        if (!empty($payment->external_payment_id)) {
            return true;
        }

        // Verificar si el order_number indica suscripción
        $orderNumber = $order->order_number ?? '';
        if (str_starts_with($orderNumber, 'SUB-')) {
            return true;
        }

        // Verificar si existe una ProgramSubscription para este participante/programa
        if ($order->participant_id && $order->program_id) {
            $hasSubscription = \App\Models\ProgramSubscription::where('participant_id', $order->participant_id)
                ->where('program_id', $order->program_id)
                ->whereIn('status', ['ACTIVA', 'PAUSADA'])
                ->exists();

            if ($hasSubscription) {
                return true;
            }
        }

        // Por defecto, es pago total
        return false;
    }

    /**
     * Resolver pagos en estado 'pending' consultando la pasarela de pago.
     * - Si Virtualpos reporta 'pagado' → actualiza a 'completed'
     * - Si lleva más de 2 horas en 'pending' y la pasarela no lo aprobó → marca como 'failed'
     */
    protected function resolvePendingGatewayPayments(): void
    {
        $staleThresholdHours = 2;

        // Obtener pagos pending de las últimas 48 horas que tengan token (Virtualpos)
        // Excluir pagos de suscripción (payment_source puede ser null o distinto de 'subscription')
        $pendingPayments = Payment::where('status', 'pending')
            ->where('created_at', '>=', Carbon::now()->subHours(48))
            ->whereNotNull('token')
            ->where('token', '!=', '')
            ->where(function ($q) {
                $q->whereNull('payment_source')
                  ->orWhere('payment_source', '!=', 'subscription');
            })
            ->with(['orderDetail.order'])
            ->limit(20)
            ->get();

        if ($pendingPayments->isEmpty()) {
            return;
        }

        $this->info("🔄 Verificando {$pendingPayments->count()} pagos en estado 'pending' con la pasarela...");

        $resolved = 0;
        $markedFailed = 0;

        foreach ($pendingPayments as $payment) {
            try {
                $token = $payment->external_payment_id ?? $payment->token;
                if (!$token) {
                    continue;
                }

                $result = $this->virtualPosService->confirmTransaction($token);
                $gatewayStatus = $result['status'] ?? 'unknown';
                $isApproved = in_array($gatewayStatus, VirtualPosService::APPROVED_STATUSES);
                $isRejected = in_array($gatewayStatus, VirtualPosService::REJECTED_STATUSES);

                if ($isApproved) {
                    // Pago aprobado en pasarela → actualizar a completed
                    $payment->update([
                        'status' => 'completed',
                        'authorization_code' => $result['authorization_code'] ?? $payment->authorization_code,
                        'external_payment_id' => $result['transaction_id'] ?? $payment->external_payment_id,
                        'installments_number' => $result['installments'] ?? $payment->installments_number,
                        'installment_amount' => $result['installment_amount'] ?? $payment->installment_amount,
                        'gateway_response' => json_encode($result['full_response'] ?? $result),
                    ]);

                    // Actualizar order_detail y order
                    if ($payment->orderDetail) {
                        $payment->orderDetail->update([
                            'status' => 'paid',
                            'is_paid' => true,
                            'paid_at' => $payment->orderDetail->paid_at ?? now(),
                        ]);
                        if ($payment->orderDetail->order) {
                            $payment->orderDetail->order->update(['status' => 'paid']);
                        }
                    }

                    $resolved++;
                    $this->line("  ✅ Payment #{$payment->id} → completed (pasarela: {$gatewayStatus})");

                } elseif ($isRejected) {
                    // Rechazado explícitamente
                    $this->markPaymentAsFailed($payment, $gatewayStatus);
                    $markedFailed++;
                    $this->line("  ❌ Payment #{$payment->id} → failed (pasarela: {$gatewayStatus})");

                } else {
                    // Sigue pendiente en pasarela: si lleva más de 2h, marcar como failed
                    $ageHours = Carbon::now()->diffInHours($payment->created_at);
                    if ($ageHours >= $staleThresholdHours) {
                        $this->markPaymentAsFailed($payment, "stale_pending_{$ageHours}h");
                        $markedFailed++;
                        $this->line("  ⏰ Payment #{$payment->id} → failed (pendiente por {$ageHours}h, pasarela: {$gatewayStatus})");
                    }
                }
            } catch (Exception $e) {
                Log::warning('Error verificando pago pending con pasarela', [
                    'payment_id' => $payment->id,
                    'error' => $e->getMessage(),
                ]);

                // Si la API falla pero el pago lleva más de 2h, marcarlo como failed
                // para evitar que quede atascado indefinidamente
                $ageHours = Carbon::now()->diffInHours($payment->created_at);
                if ($ageHours >= $staleThresholdHours) {
                    $this->markPaymentAsFailed($payment, "stale_pending_{$ageHours}h_api_error");
                    $markedFailed++;
                    $this->line("  ⏰ Payment #{$payment->id} → failed (pendiente por {$ageHours}h, error API: {$e->getMessage()})");
                } else {
                    $this->line("  ⚠️  Payment #{$payment->id} → error API ({$ageHours}h), reintentará");
                }
            }
        }

        if ($resolved > 0 || $markedFailed > 0) {
            $this->info("   Resultado: {$resolved} aprobados, {$markedFailed} marcados como fallidos");
        }
        $this->newLine();
    }

    /**
     * Marcar un pago y sus registros asociados como failed
     */
    protected function markPaymentAsFailed(Payment $payment, string $reason): void
    {
        $payment->update([
            'status' => 'failed',
            'error_message' => "Auto-resolved: {$reason}",
        ]);

        if ($payment->orderDetail) {
            $payment->orderDetail->update([
                'status' => 'failed',
                'is_paid' => false,
            ]);
            if ($payment->orderDetail->order && $payment->orderDetail->order->status === 'pending') {
                $payment->orderDetail->order->update(['status' => 'cancelled']);
            }
        }

        Log::info('Pago pending resuelto automáticamente como failed', [
            'payment_id' => $payment->id,
            'reason' => $reason,
        ]);
    }
}
