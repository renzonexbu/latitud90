<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use App\Mail\FailedPaymentMail;
use App\Mail\NoPaymentReminderMail;

class SendTestEmailsCommand extends Command
{
    protected $signature = 'mail:send-test {email : Email de destino}';
    protected $description = 'Envía correos de prueba de todos los templates transaccionales';

    public function handle()
    {
        $email = $this->argument('email');
        $this->info("Enviando correos de prueba a: {$email}");

        // 1. Failed Payment (Cobro Rechazado)
        $this->info('1. Enviando: Cobro Rechazado...');
        try {
            Mail::to($email)->send(new FailedPaymentMail(
                participantName: 'Juan Pérez',
                installmentNumber: 3,
                amount: '150.000',
                chargeDate: now()->format('d/m/Y'),
                cardUpdateLink: 'https://latitud90.com/update-card-test',
                programName: 'Programa Educativo 2025'
            ));
            $this->info('   ✓ Cobro Rechazado enviado');
        } catch (\Exception $e) {
            $this->error('   ✗ Error: ' . $e->getMessage());
        }

        // 2. Success Payment (Pago Exitoso)
        $this->info('2. Enviando: Pago Exitoso...');
        try {
            $emailData = [
                'customer_name' => 'María González',
                'customer_email' => $email,
                'program_name' => 'Programa Educativo 2025',
                'program_description' => 'Experiencia educativa internacional',
                'payment_amount' => '150.000',
                'payment_currency' => 'CLP',
                'payment_date' => now()->format('d/m/Y H:i'),
                'transaction_id' => 'TXN-TEST-123456',
                'payment_method' => 'Tarjeta de Crédito',
                'installment_number' => 2,
                'total_installments' => 6,
                'subject' => 'Confirmación de Pago - Programa Educativo 2025',
                'order_number' => 'ORD-2025-TEST',
                'company_name' => config('lat90.company.name', 'Latitud 90'),
                'company_email' => config('lat90.company.email', 'contacto@latitud90.com'),
                'company_phone' => config('lat90.email.support.phone', ''),
                'is_subscription' => true,
                'next_payment_date' => now()->addMonth()->format('d/m/Y'),
                'next_payment_amount' => '150.000',
            ];

            Mail::send('Mails.success_payment', $emailData, function ($message) use ($email, $emailData) {
                $message->to($email, $emailData['customer_name'])
                    ->subject($emailData['subject']);
            });
            $this->info('   ✓ Pago Exitoso enviado');
        } catch (\Exception $e) {
            $this->error('   ✗ Error: ' . $e->getMessage());
        }

        // 3. Subscription Success (Suscripción Exitosa)
        $this->info('3. Enviando: Suscripción Exitosa...');
        try {
            $emailData = [
                'customer_name' => 'Pedro Sánchez',
                'customer_email' => $email,
                'participant_name' => 'Sofía Sánchez',
                'program_name' => 'Programa Educativo 2025',
                'first_charge_date' => now()->addDays(15)->format('d/m/Y'),
                'subscription_amount' => '150.000',
                'total_installments' => 6,
                'payment_method' => 'Tarjeta de Crédito',
                'subscription_id' => 'SUB-TEST-789012',
                'subject' => 'Suscripción Exitosa - Programa Educativo 2025',
                'company_name' => config('lat90.company.name', 'Latitud 90'),
                'company_email' => config('lat90.company.email', 'contacto@latitud90.com'),
                'company_phone' => config('lat90.email.support.phone', ''),
            ];

            Mail::send('Mails.subscription_success', $emailData, function ($message) use ($email, $emailData) {
                $message->to($email, $emailData['customer_name'])
                    ->subject($emailData['subject']);
            });
            $this->info('   ✓ Suscripción Exitosa enviado');
        } catch (\Exception $e) {
            $this->error('   ✗ Error: ' . $e->getMessage());
        }

        // 4. No Payment (Sin Pagos)
        $this->info('4. Enviando: Aviso Sin Pagos...');
        try {
            Mail::to($email)->send(new NoPaymentReminderMail(
                participantName: 'Carlos Rodríguez',
                incorporationDate: now()->subMonths(2)->format('d/m/Y'),
                programAmount: '900.000'
            ));
            $this->info('   ✓ Aviso Sin Pagos enviado');
        } catch (\Exception $e) {
            $this->error('   ✗ Error: ' . $e->getMessage());
        }

        // 5. Guardian Account Verified (Cuenta Verificada)
        $this->info('5. Enviando: Cuenta Verificada...');
        try {
            $testUser = new \stdClass();
            $testUser->name = 'Ana López';
            $testUser->email = $email;

            Mail::send('emails.guardian.account-verified', ['user' => $testUser], function ($message) use ($email) {
                $message->to($email, 'Ana López')
                    ->subject('Bienvenido al Portal de Pago Latitud 90');
            });
            $this->info('   ✓ Cuenta Verificada enviado');
        } catch (\Exception $e) {
            $this->error('   ✗ Error: ' . $e->getMessage());
        }

        // 6. Guardian Email Verification (Verificación de Email)
        $this->info('6. Enviando: Verificación de Email...');
        try {
            $testUser = new \stdClass();
            $testUser->name = 'Luis Martínez';
            $testUser->email = $email;

            $emailData = [
                'user' => $testUser,
                'verificationUrl' => 'https://latitud90.com/guardian/verify-email/test/token-test-123',
            ];

            Mail::send('emails.guardian.verify-email', $emailData, function ($message) use ($email) {
                $message->to($email, 'Luis Martínez')
                    ->subject('Verifica tu cuenta - Latitud90');
            });
            $this->info('   ✓ Verificación de Email enviado');
        } catch (\Exception $e) {
            $this->error('   ✗ Error: ' . $e->getMessage());
        }

        $this->newLine();
        $this->info('¡Todos los correos de prueba han sido procesados!');

        return Command::SUCCESS;
    }
}
