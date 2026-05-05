<?php

namespace App\Services\Admin\Payments;

use App\Models\Payment;
use App\Models\PaymentConfirmationLog;
use App\Models\AdminLog;
use App\Services\PDF\DocumentTemplateService;
use App\Services\Client\Integration\BsaleService;
use App\Traits\SystemLogging;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;

class ResendPaymentConfirmationService
{
    use SystemLogging;

    protected $documentService;
    protected $bsaleService;

    public function __construct(
        DocumentTemplateService $documentService,
        BsaleService $bsaleService
    ) {
        $this->documentService = $documentService;
        $this->bsaleService = $bsaleService;
    }

    /**
     * Reenviar solamente el contrato de reserva (CR) al pagador.
     * Útil para pagos previos donde el contrato no se adjuntó.
     */
    public function resendContract(int $paymentId, int $userId): array
    {
        try {
            $payment = Payment::with(['orderDetail.order.participant', 'orderDetail.order.programCourse'])
                ->findOrFail($paymentId);

            $orderDetail = $payment->orderDetail;
            if (!$orderDetail) {
                throw new \Exception('No se encontró el detalle de la orden asociado al pago');
            }

            // Generar el contrato
            $contractPath = $this->documentService->generatePdfFromTemplate(
                \App\Models\DocumentTemplate::TYPE_CONTRACT,
                $orderDetail,
                $payment
            );
            if (!$contractPath || !file_exists($contractPath)) {
                throw new \Exception('No se pudo generar el contrato de reserva');
            }

            $attachments = [[
                'path' => $contractPath,
                'name' => 'Contrato_Reserva_' . $orderDetail->order->order_number . '.pdf',
                'type' => 'contract_generated',
            ]];

            $emailData = $this->prepareEmailData($orderDetail, $payment);
            $emailData['subject'] = 'Contrato de Reserva - Latitud 90';

            Mail::send('Mails.success_payment', $emailData, function ($message) use ($emailData, $attachments) {
                $message->to($emailData['customer_email'], $emailData['customer_name'])
                    ->subject($emailData['subject']);
                foreach ($attachments as $attachment) {
                    $message->attach($attachment['path'], [
                        'as' => $attachment['name'],
                        'mime' => 'application/pdf',
                    ]);
                }
            });

            PaymentConfirmationLog::logEmailResent(
                $payment,
                $orderDetail,
                $emailData['customer_email'],
                $attachments,
                $userId,
                ['attachments_count' => 1, 'resend_type' => 'contract_only']
            );

            $user = \App\Models\User::find($userId);
            AdminLog::create([
                'user_id' => $userId,
                'user_name' => $user->name ?? 'Sistema',
                'user_email' => $user->email ?? '',
                'action' => 'resend_contract',
                'module' => 'payments',
                'resource_type' => 'payment',
                'resource_id' => $payment->id,
                'description' => 'Contrato de reserva reenviado a ' . $emailData['customer_email'],
            ]);

            return [
                'success' => true,
                'message' => 'Contrato enviado exitosamente a ' . $emailData['customer_email'],
            ];
        } catch (\Exception $e) {
            $this->logError('ResendPaymentConfirmationService: Error reenviando contrato', [
                'payment_id' => $paymentId,
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ], $e);

            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Reenviar email de confirmación de pago
     */
    public function resendConfirmation(int $paymentId, int $userId): array
    {
        try {
            // Cargar payment con relaciones necesarias
            $payment = Payment::with(['orderDetail.order.participant', 'orderDetail.order.programCourse'])
                ->findOrFail($paymentId);

            $orderDetail = $payment->orderDetail;
            if (!$orderDetail) {
                throw new \Exception('No se encontró el detalle de la orden asociado al pago');
            }

            // Intentar obtener documentos de logs existentes o regenerarlos
            $attachments = $this->getOrGenerateDocuments($payment, $orderDetail);

            if (empty($attachments)) {
                throw new \Exception('No se pudieron obtener o generar documentos para adjuntar al email');
            }

            // Preparar datos del email
            $emailData = $this->prepareEmailData($orderDetail, $payment);

            // Enviar email
            Mail::send('Mails.success_payment', $emailData, function ($message) use ($emailData, $attachments) {
                $message->to($emailData['customer_email'], $emailData['customer_name'])
                    ->subject($emailData['subject']);

                foreach ($attachments as $attachment) {
                    $message->attach($attachment['path'], [
                        'as' => $attachment['name'],
                        'mime' => 'application/pdf',
                    ]);
                }
            });

            // Registrar evento de reenvío
            $attachmentsList = array_map(function ($att) {
                return [
                    'type' => $att['type'],
                    'name' => $att['name'],
                    'path' => $att['path']
                ];
            }, $attachments);

            PaymentConfirmationLog::logEmailResent(
                $payment,
                $orderDetail,
                $emailData['customer_email'],
                $attachmentsList,
                $userId,
                ['attachments_count' => count($attachments)]
            );

            // Log de auditoría administrativa
            $user = \App\Models\User::find($userId);
            AdminLog::create([
                'user_id' => $userId,
                'user_name' => $user->name ?? 'Sistema',
                'user_email' => $user->email ?? '',
                'action' => 'resend',
                'module' => 'payments',
                'resource_type' => 'payment',
                'resource_id' => $payment->id,
                'description' => 'Email de confirmación reenviado a ' . $emailData['customer_email'],
            ]);

            $this->logInfo('ResendPaymentConfirmationService: Email reenviado exitosamente', [
                'payment_id' => $paymentId,
                'user_id' => $userId,
                'recipient' => $emailData['customer_email'],
                'attachments_count' => count($attachments),
            ]);

            return [
                'success' => true,
                'message' => 'Email de confirmación reenviado exitosamente a ' . $emailData['customer_email'],
                'recipient' => $emailData['customer_email'],
                'attachments_count' => count($attachments),
            ];

        } catch (\Exception $e) {
            $this->logError('ResendPaymentConfirmationService: Error reenviando email', [
                'payment_id' => $paymentId,
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ], $e);

            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Obtener documentos de logs existentes o regenerarlos
     */
    private function getOrGenerateDocuments($payment, $orderDetail): array
    {
        $attachments = [];

        // Primero intentar obtener de logs existentes
        $logs = PaymentConfirmationLog::where('payment_id', $payment->id)
            ->where('status', 'success')
            ->whereIn('event_type', ['payment_receipt_generated', 'contract_generated', 'bsale_invoice_generated'])
            ->get();

        foreach ($logs as $log) {
            if ($log->file_path && file_exists($log->file_path)) {
                $attachments[] = [
                    'path' => $log->file_path,
                    'name' => $log->file_name ?? basename($log->file_path),
                    'type' => $log->event_type,
                ];
            }
        }

        // Si no hay documentos en logs, regenerar según PaymentDocumentTypeHelper
        if (empty($attachments)) {
            $documentTypes = \App\Helpers\PaymentDocumentTypeHelper::determineDocumentTypes($payment, $orderDetail);

            $this->logInfo('ResendPaymentConfirmationService: No hay logs, regenerando documentos según tipo', [
                'payment_id' => $payment->id,
                'document_types' => $documentTypes,
            ]);

            $shouldSendBoleta = in_array(\App\Helpers\PaymentDocumentTypeHelper::TYPE_BOLETA, $documentTypes);
            $shouldSendContract = in_array(\App\Helpers\PaymentDocumentTypeHelper::TYPE_CONTRATO, $documentTypes);
            $shouldSendReceipt = in_array(\App\Helpers\PaymentDocumentTypeHelper::TYPE_ANTICIPO, $documentTypes);

            // Generar comprobante de pago solo si corresponde (AC - año siguiente)
            if ($shouldSendReceipt) {
                try {
                    $receiptPath = $this->documentService->generatePdfFromTemplate(
                        \App\Models\DocumentTemplate::TYPE_PAYMENT_RECEIPT,
                        $orderDetail,
                        $payment
                    );
                    if ($receiptPath && file_exists($receiptPath)) {
                        $attachments[] = [
                            'path' => $receiptPath,
                            'name' => 'Comprobante_Pago_' . $orderDetail->order->order_number . '.pdf',
                            'type' => 'payment_receipt_generated',
                        ];
                    }
                } catch (\Exception $e) {
                    $this->logError('Error generando comprobante de pago', ['error' => $e->getMessage()]);
                }
            }

            // Generar contrato solo si corresponde (CR - primera cuota año siguiente)
            if ($shouldSendContract) {
                try {
                    $contractPath = $this->documentService->generatePdfFromTemplate(
                        \App\Models\DocumentTemplate::TYPE_CONTRACT,
                        $orderDetail,
                        $payment
                    );
                    if ($contractPath && file_exists($contractPath)) {
                        $attachments[] = [
                            'path' => $contractPath,
                            'name' => 'Contrato_Reserva_' . $orderDetail->order->order_number . '.pdf',
                            'type' => 'contract_generated',
                        ];
                    }
                } catch (\Exception $e) {
                    $this->logError('Error generando contrato', ['error' => $e->getMessage()]);
                }
            }

            // Intentar obtener boleta Bsale si corresponde (B2 - mismo año)
            if ($shouldSendBoleta && $payment->bsale_document_id && $payment->bsale_token) {
                try {
                    $bsalePath = $this->getBsalePdfPath($payment);
                    if ($bsalePath && file_exists($bsalePath)) {
                        $attachments[] = [
                            'path' => $bsalePath,
                            'name' => 'Boleta_' . $payment->bsale_document_id . '.pdf',
                            'type' => 'bsale_invoice_generated',
                        ];
                    }
                } catch (\Exception $e) {
                    $this->logError('Error descargando boleta Bsale', ['error' => $e->getMessage()]);
                }
            }
        }

        return $attachments;
    }

    /**
     * Obtener la ruta del PDF de Bsale (buscar existente o descargar)
     */
    private function getBsalePdfPath(Payment $payment): ?string
    {
        // Buscar archivo existente en el directorio de Bsale
        $bsaleDir = storage_path('app/bsale_documents');
        $yearDir = $bsaleDir . '/' . $payment->created_at->format('Y');

        // Buscar por diferentes patrones de nombre
        $possibleFilenames = [
            'bsale_' . $payment->bsale_document_id . '_payment_' . $payment->id . '.pdf',
            'bsale_' . $payment->id . '.pdf',
        ];

        foreach ($possibleFilenames as $filename) {
            $filePath = $yearDir . '/' . $filename;
            if (file_exists($filePath)) {
                return $filePath;
            }
            // También buscar en directorio principal
            $altPath = $bsaleDir . '/' . $filename;
            if (file_exists($altPath)) {
                return $altPath;
            }
        }

        // Si no existe, intentar descargar
        if ($payment->bsale_token) {
            $bsaleUrl = "https://app2.bsale.cl/view/90370/" . $payment->bsale_token . ".pdf?sfd=99";

            // Crear directorios si no existen
            if (!file_exists($yearDir)) {
                mkdir($yearDir, 0755, true);
            }

            $filename = 'bsale_' . $payment->bsale_document_id . '_payment_' . $payment->id . '.pdf';
            $filePath = $yearDir . '/' . $filename;

            $response = Http::timeout(30)->get($bsaleUrl);

            if ($response->successful()) {
                file_put_contents($filePath, $response->body());
                return $filePath;
            }
        }

        return null;
    }

    /**
     * Preparar datos para el email
     */
    private function prepareEmailData($orderDetail, $payment): array
    {
        $order = $orderDetail->order;
        $participant = $order->participant;
        $program = $order->programCourse;

        // Mapear método de pago
        $paymentMethods = [
            'webpay' => 'Webpay Plus',
            'transfer' => 'Transferencia Bancaria',
            'cash' => 'Efectivo',
            'check' => 'Cheque',
            'paypal' => 'PayPal',
            'flow' => 'Flow',
        ];

        return [
            'customer_name' => $orderDetail->name,
            'customer_email' => $orderDetail->email,
            'subject' => 'Confirmación de Pago - ' . ($program->name ?? 'Programa'),
            'order_number' => $order->order_number,
            'program_name' => $program->name ?? 'Programa',
            'program_description' => $program->destination ?? '',
            'program_destination' => $program->destination ?? '',
            'participant_name' => $participant->full_name ?? '',
            'payment_amount' => number_format($payment->amount, 0, ',', '.'),
            'payment_currency' => 'CLP',
            'payment_date' => $payment->created_at->format('d/m/Y'),
            'payment_method' => $paymentMethods[$payment->payment_method ?? 'webpay'] ?? 'Webpay Plus',
            'transaction_id' => $payment->transaction_id ?? $payment->id,
            'installment_number' => $orderDetail->installment_number,
            'total_installments' => $order->total_installments ?? 1,
            'payment_type' => $order->payment_type,
            'company_name' => config('app.name', 'Latitud 90'),
            'is_subscription' => false,
        ];
    }
}
