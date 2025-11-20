<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Mail\GuardianEmailVerification;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;

class SendTestVerificationEmail extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'email:test-verification {email}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Enviar un email de verificación de prueba';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $email = $this->argument('email');

        $this->info("Enviando email de verificación de prueba a: {$email}");

        // Buscar un guardian existente o usar el primero
        $guardian = \App\Models\GuardianUser::first();

        if (!$guardian) {
            $this->error("No hay guardianes en la base de datos para hacer la prueba");
            return Command::FAILURE;
        }

        // Generar URL de verificación de prueba (token temporal)
        $token = bin2hex(random_bytes(32));

        // Crear instancia del mailable
        $mailable = new GuardianEmailVerification($guardian, $token);

        try {
            Mail::to($email)->send($mailable);

            $verificationUrl = route('guardian.verify-email', [
                'userId' => $guardian->id,
                'token' => $token
            ]);

            $this->info("✅ Email enviado exitosamente a: {$email}");
            $this->line("Guardian usado: {$guardian->name} ({$guardian->email})");
            $this->line("URL de verificación: {$verificationUrl}");

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error("❌ Error enviando email: " . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
