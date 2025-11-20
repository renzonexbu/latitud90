<?php

namespace App\Console\Commands;

use App\Mail\NoPaymentReminderMail;
use App\Models\Participant;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class SendTestNoPaymentReminder extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'email:test-no-payment-reminder {email}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Enviar un email de recordatorio de no pago de prueba';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $email = $this->argument('email');

        $this->info("Enviando email de recordatorio de no pago de prueba a: {$email}");

        // Usar datos de prueba
        $participantName = "Juan Pérez García";
        $incorporationDate = "15/11/2025";
        $programAmount = "1.500.000";

        try {
            Mail::to($email)->send(
                new NoPaymentReminderMail(
                    $participantName,
                    $incorporationDate,
                    $programAmount
                )
            );

            $this->info("✅ Email enviado exitosamente a: {$email}");
            $this->line("Datos de prueba usados:");
            $this->line("  - Participante: {$participantName}");
            $this->line("  - Fecha de incorporación: {$incorporationDate}");
            $this->line("  - Monto del programa: \${$programAmount}");

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error("❌ Error enviando email: " . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
