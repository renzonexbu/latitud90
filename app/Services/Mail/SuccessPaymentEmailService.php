<?php

namespace App\Services\Mail;

use App\Models\OrderDetail;
use App\Models\Payment;
use App\Services\PDF\PaymentReceiptService;
use App\Services\PDF\ContractService;
use App\Traits\SystemLogging;
use Illuminate\Support\Facades\Mail;

class SuccessPaymentEmailService
{
    use SystemLogging;
    /**
     * Enviar email de confirmación de pago exitoso
     */
    public function sendSuccessPaymentEmail(OrderDetail $orderDetail, Payment $payment): bool
    {
        $pdfPath = null;
        $contractPdfPath = null;

        try {
            $emailData = $this->prepareEmailData($orderDetail, $payment);

            // Generar PDF de comprobante
            $pdfService = new PaymentReceiptService();
            $pdfPath = $pdfService->generatePaymentReceipt($orderDetail, $payment);

            // Verificar si se debe enviar el contrato
            $shouldSendContract = $this->shouldSendContract($orderDetail, $payment);

            if ($shouldSendContract) {
                $contractService = new ContractService();
                $contractPdfPath = $contractService->generateContract($orderDetail, $payment);
            }

            // Descargar PDF de Bsale si existe
            $bsalePdfPath = null;
            if ($payment->bsale_document_id && $payment->bsale_number) {
                $bsalePdfPath = $this->downloadBsalePdf($payment);
            }

            Mail::send('Mails.success_payment', $emailData, function ($message) use ($emailData, $pdfPath, $contractPdfPath, $shouldSendContract, $bsalePdfPath) {
                $message->to($emailData['customer_email'], $emailData['customer_name'])
                    ->subject($emailData['subject'])
                    ->attach($pdfPath, [
                        'as' => 'Comprobante_Pago_' . $emailData['order_number'] . '.pdf',
                        'mime' => 'application/pdf',
                    ]);

                // Adjuntar contrato si corresponde
                if ($shouldSendContract && $contractPdfPath) {
                    $message->attach($contractPdfPath, [
                        'as' => 'Contrato_Reserva_' . $emailData['order_number'] . '.pdf',
                        'mime' => 'application/pdf',
                    ]);
                }

                // Adjuntar PDF de Bsale si existe
                if ($bsalePdfPath) {
                    $message->attach($bsalePdfPath, [
                        'as' => 'Boleta_Bsale_' . $emailData['order_number'] . '.pdf',
                        'mime' => 'application/pdf',
                    ]);
                }
            });

            $this->logInfo('SuccessPaymentEmailService: Email con PDF adjunto enviado exitosamente', [
                'order_detail_id' => $orderDetail->id,
                'payment_id' => $payment->id,
                'customer_email' => $emailData['customer_email'],
                'pdf_path' => $pdfPath,
                'contract_sent' => $shouldSendContract,
                'contract_pdf_path' => $contractPdfPath,
            ]);

            return true;
        } catch (\Exception $e) {
            $this->logError('SuccessPaymentEmailService: Error enviando email', [
                'order_detail_id' => $orderDetail->id,
                'payment_id' => $payment->id,
                'error' => $e->getMessage(),
            ], $e);

            return false;
        } finally {
            // Almacenar archivos permanentemente antes de limpiar temporales
            if ($pdfPath && file_exists($pdfPath)) {
                $this->storePaymentReceipt($pdfPath, $payment);
                $pdfService = new PaymentReceiptService();
                $pdfService->cleanupTempFile($pdfPath);
            }

            if ($contractPdfPath && file_exists($contractPdfPath)) {
                $this->storeContract($contractPdfPath, $orderDetail);
                $contractService = new ContractService();
                $contractService->cleanupTempFile($contractPdfPath);
            }

            // No eliminar archivos BSale ya que ahora se almacenan permanentemente
            // Los archivos BSale se mantienen en storage/app/bsale_documents/
        }
    }

    /**
     * Almacenar comprobante de pago permanentemente
     */
    private function storePaymentReceipt(string $tempPath, Payment $payment): void
    {
        try {
            $year = $payment->created_at->year;
            $storageDir = storage_path("app/payment_receipts/{$year}");

            if (!file_exists($storageDir)) {
                mkdir($storageDir, 0755, true);
            }

            $filename = 'comprobante_pago_' . $payment->id . '.pdf';
            $permanentPath = $storageDir . '/' . $filename;

            copy($tempPath, $permanentPath);

            $this->logInfo('SuccessPaymentEmailService: Comprobante almacenado permanentemente', [
                'payment_id' => $payment->id,
                'temp_path' => $tempPath,
                'permanent_path' => $permanentPath,
                'year' => $year,
            ]);
        } catch (\Exception $e) {
            $this->logError('SuccessPaymentEmailService: Error almacenando comprobante', [
                'payment_id' => $payment->id,
                'temp_path' => $tempPath,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Almacenar contrato permanentemente
     */
    private function storeContract(string $tempPath, OrderDetail $orderDetail): void
    {
        try {
            $storageDir = storage_path('app/contracts');

            if (!file_exists($storageDir)) {
                mkdir($storageDir, 0755, true);
            }

            $filename = 'contrato_' . $orderDetail->order->participant_id . '_' . $orderDetail->order->program_id . '.pdf';
            $permanentPath = $storageDir . '/' . $filename;

            copy($tempPath, $permanentPath);

            $this->logInfo('SuccessPaymentEmailService: Contrato almacenado permanentemente', [
                'order_detail_id' => $orderDetail->id,
                'participant_id' => $orderDetail->order->participant_id,
                'program_id' => $orderDetail->order->program_id,
                'temp_path' => $tempPath,
                'permanent_path' => $permanentPath,
            ]);
        } catch (\Exception $e) {
            $this->logError('SuccessPaymentEmailService: Error almacenando contrato', [
                'order_detail_id' => $orderDetail->id,
                'temp_path' => $tempPath,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Verificar si se debe enviar el contrato
     * Se envía solo en pago total o primera cuota del pago mensual
     * NO se envía contrato de reserva si el programa inicia en el mismo año
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

        // Verificar si el programa inicia en el mismo año
        $program = $orderDetail->order->program;
        $currentYear = now()->year;
        $programStartYear = null;

        if ($program && $program->departure_date) {
            $programStartYear = $program->departure_date->year;
        }

        $isSameYear = ($programStartYear === $currentYear);

        // Log para debugging
        $this->logInfo('SuccessPaymentEmailService: Verificando envío de contrato', [
            'order_detail_id' => $orderDetail->id,
            'payment_id' => $payment->id,
            'total_installments' => $totalInstallments,
            'current_installment' => $currentInstallment,
            'is_total_payment' => ($totalInstallments == 1),
            'is_first_installment' => ($currentInstallment == 1),
            'installment_plan_id' => $installmentPlanId,
            'installment_id' => $installment ? $installment->id : 'null',
            'program_start_year' => $programStartYear,
            'current_year' => $currentYear,
            'is_same_year' => $isSameYear,
        ]);

        // NO enviar contrato de reserva si el programa inicia en el mismo año
        if ($isSameYear) {
            $this->logInfo('SuccessPaymentEmailService: NO enviando contrato - Programa inicia en el mismo año', [
                'order_detail_id' => $orderDetail->id,
                'payment_id' => $payment->id,
                'program_start_year' => $programStartYear,
                'current_year' => $currentYear,
            ]);
            return false;
        }

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
        $program = $orderDetail->order->program;
        $paymentGateway = $orderDetail->paymentGateway;

        return [
            'customer_name' => $orderDetail->name,
            'customer_email' => $orderDetail->email,
            'program_name' => $program->name ?? 'Programa',
            'program_description' => $program->description ?? '',
            'payment_amount' => number_format($payment->amount, 0, ',', '.'),
            'payment_currency' => 'CLP',
            'payment_date' => $payment->created_at->format('d/m/Y H:i'),
            'transaction_id' => $payment->external_payment_id ?? $payment->id,
            'payment_method' => $paymentGateway->name ?? 'Método de pago',
            'installment_number' => $orderDetail->installment_number,
            'total_installments' => $orderDetail->installments_number ?? 1,
            'subject' => 'Confirmación de Pago - ' . ($program->name ?? 'Programa'),
            'order_number' => $orderDetail->order->order_number ?? '',
            'company_name' => config('lat90.company.name'),
            'company_email' => config('lat90.company.email'),
            'company_phone' => config('lat90.email.support.phone'),
        ];
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
