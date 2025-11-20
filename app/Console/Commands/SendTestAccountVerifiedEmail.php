<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Mail\GuardianAccountVerified;
use Illuminate\Support\Facades\Mail;

class SendTestAccountVerifiedEmail extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'email:test-account-verified {email}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Enviar un email de cuenta verificada de prueba';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $email = $this->argument('email');

        $this->info("Enviando email de cuenta verificada de prueba a: {$email}");

        // Buscar un guardian existente o usar el primero
        $guardian = \App\Models\GuardianUser::first();

        if (!$guardian) {
            $this->error("No hay guardianes en la base de datos para hacer la prueba");
            return Command::FAILURE;
        }

        // Crear instancia del mailable
        $mailable = new GuardianAccountVerified($guardian);

        try {
            Mail::to($email)->send($mailable);

            $this->info("✅ Email enviado exitosamente a: {$email}");
            $this->line("Guardian usado: {$guardian->name} ({$guardian->email})");

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error("❌ Error enviando email: " . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
