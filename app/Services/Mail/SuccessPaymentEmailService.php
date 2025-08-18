<?php

namespace App\Services\Mail;

use App\Models\OrderDetail;
use App\Models\Payment;
use App\Services\PDF\PaymentReceiptService;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class SuccessPaymentEmailService
{
    /**
     * Enviar email de confirmación de pago exitoso
     */
    public function sendSuccessPaymentEmail(OrderDetail $orderDetail, Payment $payment): bool
    {
        $pdfPath = null;
        
        try {
            $emailData = $this->prepareEmailData($orderDetail, $payment);
            
            // Generar PDF de comprobante
            $pdfService = new PaymentReceiptService();
            $pdfPath = $pdfService->generatePaymentReceipt($orderDetail, $payment);
            
            Mail::send('Mails.success_payment', $emailData, function ($message) use ($emailData, $pdfPath) {
                $message->to($emailData['customer_email'], $emailData['customer_name'])
                        ->subject($emailData['subject'])
                        ->attach($pdfPath, [
                            'as' => 'Comprobante_Pago_' . $emailData['order_number'] . '.pdf',
                            'mime' => 'application/pdf',
                        ]);
            });

            Log::info('SuccessPaymentEmailService: Email con PDF adjunto enviado exitosamente', [
                'order_detail_id' => $orderDetail->id,
                'payment_id' => $payment->id,
                'customer_email' => $emailData['customer_email'],
                'pdf_path' => $pdfPath,
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('SuccessPaymentEmailService: Error enviando email', [
                'order_detail_id' => $orderDetail->id,
                'payment_id' => $payment->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        } finally {
            // Limpiar archivo temporal
            if ($pdfPath && file_exists($pdfPath)) {
                $pdfService = new PaymentReceiptService();
                $pdfService->cleanupTempFile($pdfPath);
            }
        }
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
}
