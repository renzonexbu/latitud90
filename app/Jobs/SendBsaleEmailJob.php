<?php

namespace App\Jobs;

use App\Models\Payment;
use App\Models\OrderDetail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendBsaleEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 120;

    public function __construct(
        protected int $paymentId,
        protected int $orderDetailId
    ) {}

    public function handle(): void
    {
        $payment = Payment::find($this->paymentId);
        $orderDetail = OrderDetail::find($this->orderDetailId);

        if (!$payment || !$orderDetail) {
            Log::warning('SendBsaleEmailJob: Payment u OrderDetail no encontrado', [
                'payment_id' => $this->paymentId,
                'order_detail_id' => $this->orderDetailId,
            ]);
            return;
        }

        // Evitar reenvío si ya se envió
        if ($payment->bsale_email_sent) {
            Log::info('SendBsaleEmailJob: Email de boleta ya enviado, omitiendo', [
                'payment_id' => $payment->id,
            ]);
            return;
        }

        // Verificar que la boleta esté generada
        if (!$payment->bsale_document_id || !$payment->bsale_token) {
            Log::warning('SendBsaleEmailJob: Boleta BSale aún no generada, reintentando', [
                'payment_id' => $payment->id,
            ]);
            // Reintentar en 10 minutos
            $this->release(600);
            return;
        }

        $email = $orderDetail->email;
        $name  = $orderDetail->name;

        if (empty($email)) {
            Log::warning('SendBsaleEmailJob: Sin email destino', ['payment_id' => $payment->id]);
            return;
        }

        // Obtener PDF de BSale
        $bsalePdfPath = $this->getBsalePdf($payment);

        if (!$bsalePdfPath) {
            Log::warning('SendBsaleEmailJob: No se pudo obtener el PDF de BSale', [
                'payment_id' => $payment->id,
            ]);
            // Reintentar en 15 minutos
            $this->release(900);
            return;
        }

        $programCourse = $orderDetail->order?->programCourse;
        $orderNumber   = $orderDetail->order?->order_number ?? '';

        $emailData = [
            'customer_name'  => $name,
            'program_name'   => $programCourse?->name ?? 'Programa',
            'bsale_number'   => $payment->bsale_number,
            'order_number'   => $orderNumber,
            'company_name'   => config('lat90.company.name'),
            'company_email'  => config('lat90.company.email'),
            'company_phone'  => config('lat90.email.support.phone'),
        ];

        try {
            Mail::send('Mails.bsale_invoice', $emailData, function ($message) use ($email, $name, $bsalePdfPath, $orderNumber, $emailData) {
                $message->to($email, $name)
                    ->subject('Boleta Electrónica - ' . $emailData['program_name']);

                $message->attach($bsalePdfPath, [
                    'as'   => 'Boleta_Bsale_' . $orderNumber . '.pdf',
                    'mime' => 'application/pdf',
                ]);
            });

            $payment->update([
                'bsale_email_sent'    => true,
                'bsale_email_sent_at' => now(),
            ]);

            Log::info('SendBsaleEmailJob: Email de boleta enviado exitosamente', [
                'payment_id'    => $payment->id,
                'bsale_number'  => $payment->bsale_number,
                'email'         => $email,
            ]);

        } catch (\Exception $e) {
            Log::error('SendBsaleEmailJob: Error enviando email de boleta', [
                'payment_id' => $payment->id,
                'error'      => $e->getMessage(),
            ]);
            throw $e;
        } finally {
            // No eliminar el PDF — es el archivo permanente de BSale
        }
    }

    private function getBsalePdf(Payment $payment): ?string
    {
        $bsaleDir = storage_path('app/bsale_documents');
        $yearDir  = $bsaleDir . '/' . date('Y');
        $filename = 'bsale_' . $payment->bsale_number . '_payment_' . $payment->id . '.pdf';
        $filePath = $yearDir . '/' . $filename;

        if (file_exists($filePath)) {
            return $filePath;
        }

        // Fallback: descargar desde BSale
        try {
            if (!file_exists($yearDir)) {
                mkdir($yearDir, 0755, true);
            }

            $bsaleUrl = "https://app2.bsale.cl/view/90370/" . $payment->bsale_token . ".pdf?sfd=99";
            $response = \Illuminate\Support\Facades\Http::timeout(30)->get($bsaleUrl);

            if ($response->successful()) {
                file_put_contents($filePath, $response->body());
                return $filePath;
            }
        } catch (\Exception $e) {
            Log::error('SendBsaleEmailJob: Error descargando PDF de BSale', [
                'payment_id' => $payment->id,
                'error'      => $e->getMessage(),
            ]);
        }

        return null;
    }
}
