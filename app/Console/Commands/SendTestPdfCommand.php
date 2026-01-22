<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use App\Models\Payment;
use App\Models\OrderDetail;
use App\Services\PDF\PaymentReceiptService;
use App\Services\PDF\ContractService;

class SendTestPdfCommand extends Command
{
    protected $signature = 'pdf:send-test {email : Email de destino} {--payment= : ID del pago a usar (opcional)}';
    protected $description = 'Genera PDFs de prueba (contrato y comprobante) y los envía por correo';

    public function handle()
    {
        $email = $this->argument('email');
        $paymentId = $this->option('payment');

        $this->info("Generando PDFs de prueba para enviar a: {$email}");

        // Buscar un pago para usar como base
        if ($paymentId) {
            $payment = Payment::find($paymentId);
            if (!$payment) {
                $this->error("No se encontró el pago con ID: {$paymentId}");
                return Command::FAILURE;
            }
        } else {
            // Buscar el último pago completado
            $payment = Payment::where('status', 'completed')
                ->whereHas('orderDetail')
                ->latest()
                ->first();

            if (!$payment) {
                $this->error("No se encontró ningún pago completado en la base de datos");
                return Command::FAILURE;
            }
        }

        $orderDetail = $payment->orderDetail;

        if (!$orderDetail) {
            $this->error("El pago no tiene un OrderDetail asociado");
            return Command::FAILURE;
        }

        $this->info("Usando pago ID: {$payment->id}");
        $this->info("Order Detail ID: {$orderDetail->id}");
        $this->info("Monto: $" . number_format($payment->amount, 0, ',', '.'));

        $pdfPath = null;
        $contractPdfPath = null;

        try {
            // Generar comprobante de pago
            $this->info('Generando comprobante de pago...');
            $pdfService = new PaymentReceiptService();
            $pdfPath = $pdfService->generatePaymentReceipt($orderDetail, $payment);
            $this->info("   ✓ Comprobante generado: {$pdfPath}");

            // Generar contrato
            $this->info('Generando contrato de reserva...');
            $contractService = new ContractService();
            $contractPdfPath = $contractService->generateContract($orderDetail, $payment);
            $this->info("   ✓ Contrato generado: {$contractPdfPath}");

            // Enviar por correo
            $this->info('Enviando correo...');

            $programCourse = $orderDetail->order->programCourse ?? $orderDetail->order->program;
            $programName = $programCourse->name ?? 'Programa de Prueba';

            Mail::send('Mails.success_payment', [
                'customer_name' => $orderDetail->name ?? 'Cliente de Prueba',
                'customer_email' => $email,
                'program_name' => $programName,
                'program_description' => $programCourse->description ?? '',
                'payment_amount' => number_format($payment->amount, 0, ',', '.'),
                'payment_currency' => 'CLP',
                'payment_date' => $payment->created_at->format('d/m/Y H:i'),
                'transaction_id' => $payment->external_payment_id ?? $payment->id,
                'payment_method' => $payment->paymentGateway->name ?? 'Método de pago',
                'installment_number' => $orderDetail->installment_number ?? 1,
                'total_installments' => $orderDetail->installments_number ?? 1,
                'subject' => '[TEST] Prueba de PDFs - Contrato y Comprobante',
                'order_number' => $orderDetail->order->order_number ?? 'TEST-001',
                'company_name' => config('lat90.company.name'),
                'company_email' => config('lat90.company.email'),
                'company_phone' => config('lat90.email.support.phone'),
                'is_subscription' => false,
            ], function ($message) use ($email, $pdfPath, $contractPdfPath, $orderDetail) {
                $orderNumber = $orderDetail->order->order_number ?? 'TEST';

                $message->to($email)
                    ->subject('[TEST] Prueba de PDFs - Contrato y Comprobante')
                    ->attach($pdfPath, [
                        'as' => 'Comprobante_Pago_' . $orderNumber . '.pdf',
                        'mime' => 'application/pdf',
                    ])
                    ->attach($contractPdfPath, [
                        'as' => 'Contrato_Reserva_' . $orderNumber . '.pdf',
                        'mime' => 'application/pdf',
                    ]);
            });

            $this->info("   ✓ Correo enviado exitosamente a: {$email}");
            $this->newLine();
            $this->info('¡PDFs generados y enviados correctamente!');
            $this->info('Revisa tu bandeja de entrada para verificar las fechas en español.');

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error('Error: ' . $e->getMessage());
            $this->error('Archivo: ' . $e->getFile() . ':' . $e->getLine());
            return Command::FAILURE;
        } finally {
            // Limpiar archivos temporales
            if ($pdfPath && file_exists($pdfPath)) {
                unlink($pdfPath);
            }
            if ($contractPdfPath && file_exists($contractPdfPath)) {
                unlink($contractPdfPath);
            }
        }
    }
}
