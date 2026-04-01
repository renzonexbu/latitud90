<?php

namespace App\Services\Mail;

use App\Models\GeneratedDocument;
use App\Models\OrderDetail;
use App\Models\Payment;
use App\Models\PaymentConfirmationLog;
use App\Services\DocumentStorageService;
use App\Services\PDF\PaymentReceiptService;
use App\Services\PDF\ContractService;
use App\Traits\SystemLogging;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

class SuccessPaymentEmailService
{
    use SystemLogging;

    protected $documentStorageService;

    public function __construct()
    {
        $this->documentStorageService = new DocumentStorageService();
    }

    /**
     * Enviar email de confirmación de pago exitoso
     *
     * Lógica de adjuntos (determinada por PaymentDocumentTypeHelper):
     * - B2 (Boleta): Pago mismo año o posterior al programa → Boleta BSale
     * - CR + AC (Contrato + Anticipo): Primera cuota suscripción año anterior → Contrato + Comprobante
     * - AC (Anticipo): Cuotas siguientes año anterior → Solo Comprobante
     */
    public function sendSuccessPaymentEmail(OrderDetail $orderDetail, Payment $payment): bool
    {
        $pdfPath = null;
        $contractPdfPath = null;
        $receiptDoc = null;
        $contractDoc = null;
        $bsaleDoc = null;

        try {
            $emailData = $this->prepareEmailData($orderDetail, $payment);

            // Validar que exista un email destino antes de continuar
            if (empty($emailData['customer_email'])) {
                $this->logError('SuccessPaymentEmailService: No se puede enviar email - customer_email vacío', [
                    'payment_id' => $payment->id,
                    'order_detail_id' => $orderDetail->id,
                    'customer_name' => $emailData['customer_name'] ?? 'N/A',
                ]);
                return false;
            }

            // Usar los tipos de documento pre-calculados del Payment (si existen)
            // Estos son establecidos por el comando payments:send-pending-emails
            $documentTypes = $payment->generated_document_types ?? [];

            // Si no hay tipos pre-calculados, usar el helper para determinarlos (fallback)
            if (empty($documentTypes)) {
                $documentTypes = \App\Helpers\PaymentDocumentTypeHelper::determineDocumentTypes($payment, $orderDetail);
                $this->logInfo('SuccessPaymentEmailService: Usando tipos de documento calculados (fallback)', [
                    'payment_id' => $payment->id,
                    'document_types' => $documentTypes,
                ]);
            } else {
                $this->logInfo('SuccessPaymentEmailService: Usando tipos de documento pre-calculados', [
                    'payment_id' => $payment->id,
                    'document_types' => $documentTypes,
                ]);
            }

            // Determinar qué adjuntos enviar según los tipos de documento
            $shouldSendBoleta = in_array(\App\Helpers\PaymentDocumentTypeHelper::TYPE_BOLETA, $documentTypes);
            $shouldSendContract = in_array(\App\Helpers\PaymentDocumentTypeHelper::TYPE_CONTRATO, $documentTypes);
            $shouldSendReceipt = in_array(\App\Helpers\PaymentDocumentTypeHelper::TYPE_ANTICIPO, $documentTypes);

            // La boleta BSale se envía en un email separado 1 hora después (SendBsaleEmailJob)
            // El email inmediato NO adjunta la boleta
            $bsalePdfPath = null;

            $this->logInfo('SuccessPaymentEmailService: Documentos a adjuntar', [
                'order_detail_id' => $orderDetail->id,
                'payment_id' => $payment->id,
                'document_types' => $documentTypes,
                'should_send_boleta' => $shouldSendBoleta,
                'should_send_contract' => $shouldSendContract,
                'should_send_receipt' => $shouldSendReceipt,
                'has_bsale_pdf' => !empty($bsalePdfPath),
            ]);

            // Usar PDF de comprobante pre-generado o generar nuevo
            if ($shouldSendReceipt) {
                $pdfPath = $payment->receipt_path;
                if (!$pdfPath || !file_exists($pdfPath)) {
                    $pdfService = new PaymentReceiptService();
                    $pdfPath = $pdfService->generatePaymentReceipt($orderDetail, $payment);
                }
            }

            // Usar PDF de contrato pre-generado o generar nuevo
            if ($shouldSendContract) {
                $contractPdfPath = $payment->contract_path;
                if (!$contractPdfPath || !file_exists($contractPdfPath)) {
                    $contractService = new ContractService();
                    $contractPdfPath = $contractService->generateContract($orderDetail, $payment);
                }
            }

            Mail::send('Mails.success_payment', $emailData, function ($message) use ($emailData, $pdfPath, $contractPdfPath, $shouldSendContract, $shouldSendReceipt, $bsalePdfPath) {
                $message->to($emailData['customer_email'], $emailData['customer_name'])
                    ->subject($emailData['subject']);

                // Adjuntar comprobante de anticipo solo si corresponde (año posterior)
                if ($shouldSendReceipt && $pdfPath) {
                    $message->attach($pdfPath, [
                        'as' => 'Comprobante_Pago_' . $emailData['order_number'] . '.pdf',
                        'mime' => 'application/pdf',
                    ]);
                }

                // Adjuntar contrato si corresponde (primera cuota año posterior)
                if ($shouldSendContract && $contractPdfPath) {
                    $message->attach($contractPdfPath, [
                        'as' => 'Contrato_Reserva_' . $emailData['order_number'] . '.pdf',
                        'mime' => 'application/pdf',
                    ]);
                }

                // Adjuntar PDF de Bsale si existe (programa mismo año)
                if ($bsalePdfPath) {
                    $message->attach($bsalePdfPath, [
                        'as' => 'Boleta_Bsale_' . $emailData['order_number'] . '.pdf',
                        'mime' => 'application/pdf',
                    ]);
                }
            });

            // Almacenar archivos permanentemente y registrar en BD
            if ($pdfPath && file_exists($pdfPath)) {
                // Convertir ruta absoluta a relativa desde storage/app
                $relativePath = str_replace(storage_path('app/'), '', $pdfPath);
                $receiptDoc = $this->documentStorageService->storePaymentReceipt(
                    $relativePath,
                    $payment,
                    $orderDetail,
                    GeneratedDocument::SOURCE_PAYMENT_CONFIRMATION
                );
            }

            if ($contractPdfPath && file_exists($contractPdfPath)) {
                $relativePath = str_replace(storage_path('app/'), '', $contractPdfPath);
                $contractDoc = $this->documentStorageService->storeContract(
                    $relativePath,
                    $orderDetail,
                    GeneratedDocument::SOURCE_PAYMENT_CONFIRMATION
                );
            }

            if ($bsalePdfPath && file_exists($bsalePdfPath)) {
                $relativePath = str_replace(storage_path('app/'), '', $bsalePdfPath);
                $bsaleDoc = $this->documentStorageService->storeBsaleInvoice(
                    $relativePath,
                    $payment,
                    $orderDetail,
                    $payment->bsale_number,
                    GeneratedDocument::SOURCE_PAYMENT_CONFIRMATION
                );
            }

            // Marcar documentos como enviados por email
            if ($receiptDoc) {
                $this->documentStorageService->markDocumentAsEmailSent($receiptDoc, $emailData['customer_email']);
            }
            if ($contractDoc) {
                $this->documentStorageService->markDocumentAsEmailSent($contractDoc, $emailData['customer_email']);
            }
            if ($bsaleDoc) {
                $this->documentStorageService->markDocumentAsEmailSent($bsaleDoc, $emailData['customer_email']);
            }

            // Preparar lista de attachments para el log
            $attachmentsList = [];
            if ($shouldSendReceipt && $pdfPath) {
                $attachmentsList[] = [
                    'type' => 'payment_receipt',
                    'name' => 'Comprobante_Pago_' . $emailData['order_number'] . '.pdf',
                    'path' => $pdfPath
                ];
            }
            if ($shouldSendContract && $contractPdfPath) {
                $attachmentsList[] = [
                    'type' => 'contract',
                    'name' => 'Contrato_Reserva_' . $emailData['order_number'] . '.pdf',
                    'path' => $contractPdfPath
                ];
            }
            if ($bsalePdfPath) {
                $attachmentsList[] = [
                    'type' => 'bsale_invoice',
                    'name' => 'Boleta_Bsale_' . $emailData['order_number'] . '.pdf',
                    'path' => $bsalePdfPath
                ];
            }

            // Registrar envío exitoso en payment_confirmation_logs
            PaymentConfirmationLog::logEmailSent(
                $payment,
                $orderDetail,
                $emailData['customer_email'],
                $attachmentsList,
                [
                    'order_number' => $emailData['order_number'],
                    'receipt_doc_id' => $receiptDoc ? $receiptDoc->id : null,
                    'contract_doc_id' => $contractDoc ? $contractDoc->id : null,
                    'bsale_doc_id' => $bsaleDoc ? $bsaleDoc->id : null,
                ]
            );

            $this->logInfo('SuccessPaymentEmailService: Email con PDF adjunto enviado exitosamente', [
                'order_detail_id' => $orderDetail->id,
                'payment_id' => $payment->id,
                'customer_email' => $emailData['customer_email'],
                'pdf_path' => $pdfPath,
                'contract_sent' => $shouldSendContract,
                'contract_pdf_path' => $contractPdfPath,
                'receipt_doc_id' => $receiptDoc ? $receiptDoc->id : null,
                'contract_doc_id' => $contractDoc ? $contractDoc->id : null,
                'bsale_doc_id' => $bsaleDoc ? $bsaleDoc->id : null,
            ]);

            return true;
        } catch (\Exception $e) {
            // Registrar fallo en payment_confirmation_logs
            $emailAddress = isset($emailData) && isset($emailData['customer_email'])
                ? $emailData['customer_email']
                : $orderDetail->email;

            PaymentConfirmationLog::logEmailFailed(
                $payment,
                $orderDetail,
                $emailAddress,
                $e->getMessage(),
                [
                    'error_line' => $e->getLine(),
                    'error_file' => $e->getFile(),
                ]
            );

            $this->logError('SuccessPaymentEmailService: Error enviando email', [
                'order_detail_id' => $orderDetail->id,
                'payment_id' => $payment->id,
                'error' => $e->getMessage(),
            ], $e);

            return false;
        } finally {
            // Limpiar archivos temporales
            if ($pdfPath && file_exists($pdfPath)) {
                $pdfService = new PaymentReceiptService();
                $pdfService->cleanupTempFile($pdfPath);
            }

            if ($contractPdfPath && file_exists($contractPdfPath)) {
                $contractService = new ContractService();
                $contractService->cleanupTempFile($contractPdfPath);
            }
        }
    }


    /**
     * Verificar si el programa es del mismo año actual
     * Determina si se debe enviar boleta bsale (mismo año) o comprobante de anticipo (año posterior)
     */
    private function isProgramSameYear(OrderDetail $orderDetail): bool
    {
        $programCourse = $orderDetail->order->programCourse;
        $currentYear = now()->year;
        $programStartYear = null;

        if ($programCourse && $programCourse->departure_date) {
            $programStartYear = $programCourse->departure_date->year;
        }

        $isSameYear = ($programStartYear === $currentYear);

        $this->logInfo('SuccessPaymentEmailService: Verificando año del programa', [
            'order_detail_id' => $orderDetail->id,
            'program_start_year' => $programStartYear,
            'current_year' => $currentYear,
            'is_same_year' => $isSameYear,
        ]);

        return $isSameYear;
    }

    /**
     * Verificar si se debe enviar el contrato
     * Se envía solo en pago total o primera cuota del pago mensual
     * NOTA: Este método solo se llama para programas de año posterior (no mismo año)
     * La verificación del año ya se hace en sendSuccessPaymentEmail()
     */
    private function shouldSendContract(OrderDetail $orderDetail, Payment $payment): bool
    {
        // Buscar la cuota que se está pagando para obtener el plan de cuotas
        $installment = \App\Models\Installment::where('payment_order_detail_id', $orderDetail->id)
            ->where('payment_id', $payment->id)
            ->first();

        $totalInstallments = 1;
        $installmentPlanId = null;

        if ($installment && $installment->installmentPlan) {
            $totalInstallments = $installment->installmentPlan->total_installments;
            $installmentPlanId = $installment->installmentPlan->id;
        }

        $currentInstallment = $orderDetail->installment_number;

        // Log para debugging
        $this->logInfo('SuccessPaymentEmailService: Verificando envío de contrato (año posterior)', [
            'order_detail_id' => $orderDetail->id,
            'payment_id' => $payment->id,
            'total_installments' => $totalInstallments,
            'current_installment' => $currentInstallment,
            'is_total_payment' => ($totalInstallments == 1),
            'is_first_installment' => ($currentInstallment == 1),
            'installment_plan_id' => $installmentPlanId,
            'installment_id' => $installment ? $installment->id : 'null',
        ]);

        // Si es pago total (una sola cuota)
        if ($totalInstallments == 1) {
            $this->logInfo('SuccessPaymentEmailService: Enviando contrato - Pago total', [
                'order_detail_id' => $orderDetail->id,
                'payment_id' => $payment->id,
            ]);
            return true;
        }

        // Si es pago mensual y es la primera cuota
        if ($currentInstallment == 1) {
            $this->logInfo('SuccessPaymentEmailService: Enviando contrato - Primera cuota', [
                'order_detail_id' => $orderDetail->id,
                'payment_id' => $payment->id,
            ]);
            return true;
        }

        $this->logInfo('SuccessPaymentEmailService: NO enviando contrato - No cumple condiciones', [
            'order_detail_id' => $orderDetail->id,
            'payment_id' => $payment->id,
            'total_installments' => $totalInstallments,
            'current_installment' => $currentInstallment,
        ]);

        return false;
    }

    /**
     * Preparar datos para el email
     */
    private function prepareEmailData(OrderDetail $orderDetail, Payment $payment): array
    {
        $programCourse = $orderDetail->order->programCourse;
        $paymentGateway = $payment->paymentGateway;
        $order = $orderDetail->order;

        // Detectar si es un pago de suscripción
        $isSubscription = ($paymentGateway->code ?? '') === 'virtualpos' && !empty($payment->external_payment_id);

        // Preparar datos base
        $data = [
            'customer_name' => $orderDetail->name,
            'customer_email' => $orderDetail->email,
            'program_name' => $programCourse->name ?? 'Programa',
            'program_description' => $programCourse->description ?? '',
            'payment_amount' => number_format($payment->amount, 0, ',', '.'),
            'payment_currency' => 'CLP',
            'payment_date' => $payment->created_at->format('d/m/Y H:i'),
            'transaction_id' => $payment->external_payment_id ?? $payment->id,
            'payment_method' => $isSubscription ? 'Suscripción' : ($paymentGateway->name ?? 'Método de pago'),
            'installment_number' => $orderDetail->installment_number,
            'total_installments' => $orderDetail->installments_number ?? 1,
            'subject' => 'Confirmación de Pago - ' . ($programCourse->name ?? 'Programa'),
            'order_number' => $orderDetail->order->order_number ?? '',
            'company_name' => config('lat90.company.name'),
            'company_email' => config('lat90.company.email'),
            'company_phone' => config('lat90.email.support.phone'),
            'is_subscription' => $isSubscription,
        ];

        // Si es suscripción, agregar información de próxima cuota
        if ($isSubscription) {
            $subscriptionData = $this->getSubscriptionData($order);
            $data = array_merge($data, $subscriptionData);
        }

        return $data;
    }

    /**
     * Obtener datos adicionales de la suscripción
     */
    private function getSubscriptionData(\App\Models\Order $order): array
    {
        $data = [
            'next_payment_date' => null,
            'next_payment_amount' => null,
            'total_installments' => 1,
        ];

        try {
            // Buscar el plan de cuotas
            $installmentPlan = \App\Models\InstallmentPlan::where('participant_id', $order->participant_id)
                ->where('program_id', $order->program_id)
                ->first();

            if ($installmentPlan) {
                // Contar total de cuotas
                $totalInstallments = \App\Models\Installment::where('installment_plan_id', $installmentPlan->id)->count();
                $data['total_installments'] = $totalInstallments;

                // Buscar próxima cuota pendiente
                $nextInstallment = \App\Models\Installment::where('installment_plan_id', $installmentPlan->id)
                    ->whereIn('status', ['pending', 'overdue'])
                    ->orderBy('due_date', 'asc')
                    ->first();

                if ($nextInstallment) {
                    $data['next_payment_date'] = $nextInstallment->due_date
                        ? \Carbon\Carbon::parse($nextInstallment->due_date)->format('d/m/Y')
                        : null;
                    $data['next_payment_amount'] = number_format($nextInstallment->amount, 0, ',', '.');
                }
            }
        } catch (\Exception $e) {
            $this->logError('SuccessPaymentEmailService: Error obteniendo datos de suscripción', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ], $e);
        }

        return $data;
    }

    /**
     * Obtener PDF de Bsale (ya almacenado permanentemente por BsaleService)
     */
    private function downloadBsalePdf(Payment $payment): ?string
    {
        try {
            // Verificar que tenemos el token de Bsale
            if (!$payment->bsale_token) {
                $this->logWarning('SuccessPaymentEmailService: No hay token de Bsale para obtener PDF', [
                    'payment_id' => $payment->id,
                    'bsale_document_id' => $payment->bsale_document_id,
                    'bsale_number' => $payment->bsale_number,
                ]);
                return null;
            }

            // Buscar el archivo ya almacenado por BsaleService
            $bsaleDir = storage_path('app/bsale_documents');
            $yearDir = $bsaleDir . '/' . date('Y');

            // Generar nombre de archivo (mismo formato que BsaleService)
            $bsaleNumber = $payment->bsale_number ?? $payment->id;
            $filename = 'bsale_' . $bsaleNumber . '_payment_' . $payment->id . '.pdf';
            $filePath = $yearDir . '/' . $filename;

            // Verificar si el archivo ya existe (debería existir por BsaleService)
            if (file_exists($filePath)) {
                $this->logInfo('SuccessPaymentEmailService: PDF de Bsale encontrado (almacenado por BsaleService)', [
                    'payment_id' => $payment->id,
                    'file_path' => $filePath,
                    'file_size' => filesize($filePath),
                    'found_existing' => true,
                ]);
                return $filePath;
            }

            // Si no existe, intentar descargarlo como fallback
            $this->logWarning('SuccessPaymentEmailService: PDF de Bsale no encontrado, descargando como fallback', [
                'payment_id' => $payment->id,
                'expected_path' => $filePath,
            ]);

            // Crear directorios si no existen
            if (!file_exists($bsaleDir)) {
                mkdir($bsaleDir, 0755, true);
            }
            if (!file_exists($yearDir)) {
                mkdir($yearDir, 0755, true);
            }

            // Construir la URL del PDF de Bsale
            $bsaleUrl = "https://app2.bsale.cl/view/90370/" . $payment->bsale_token . ".pdf?sfd=99";

            // Descargar el PDF como fallback
            $response = \Illuminate\Support\Facades\Http::timeout(30)->get($bsaleUrl);

            if ($response->successful()) {
                file_put_contents($filePath, $response->body());

                $this->logInfo('SuccessPaymentEmailService: PDF de Bsale descargado como fallback', [
                    'payment_id' => $payment->id,
                    'bsale_document_id' => $payment->bsale_document_id,
                    'bsale_number' => $bsaleNumber,
                    'bsale_token' => $payment->bsale_token,
                    'file_path' => $filePath,
                    'file_size' => filesize($filePath),
                    'fallback_download' => true,
                ]);

                return $filePath;
            } else {
                $this->logError('SuccessPaymentEmailService: Error descargando PDF de Bsale como fallback', [
                    'payment_id' => $payment->id,
                    'bsale_url' => $bsaleUrl,
                    'response_status' => $response->status(),
                    'response_body' => $response->body(),
                ]);

                return null;
            }
        } catch (\Exception $e) {
            $this->logError('SuccessPaymentEmailService: Error obteniendo PDF de Bsale', [
                'payment_id' => $payment->id,
                'error' => $e->getMessage(),
            ], $e);

            return null;
        }
    }
}
