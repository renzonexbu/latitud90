<?php

namespace App\Console\Commands;

use App\Mail\FailedPaymentMail;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class SendTestFailedPaymentEmail extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'email:test-failed-payment {email}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Enviar un email de cobro rechazado de prueba';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $email = $this->argument('email');

        $this->info("Enviando email de cobro rechazado de prueba a: {$email}");

        // Usar datos de prueba
        $participantName = "Juan Pérez García";
        $installmentNumber = 3;
        $amount = "125.000";
        $chargeDate = "15/11/2025";
        $cardUpdateLink = "https://portal.virtualpos.cl/card-update/test123";
        $programName = "Programa Intercambio Francia 2025";

        try {
            Mail::to($email)->send(
                new FailedPaymentMail(
                    $participantName,
                    $installmentNumber,
                    $amount,
                    $chargeDate,
                    $cardUpdateLink,
                    $programName
                )
            );

            $this->info("✅ Email enviado exitosamente a: {$email}");
            $this->line("Datos de prueba usados:");
            $this->line("  - Participante: {$participantName}");
            $this->line("  - Programa: {$programName}");
            $this->line("  - Cuota rechazada: {$installmentNumber}");
            $this->line("  - Monto: \${$amount}");
            $this->line("  - Fecha de intento: {$chargeDate}");
            $this->line("  - Link de actualización: {$cardUpdateLink}");

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error("❌ Error enviando email: " . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
