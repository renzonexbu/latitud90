<?php

namespace App\Console\Commands;

use App\Models\GuardianUser;
use Illuminate\Console\Command;

class VerifyGuardianEmail extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'guardian:verify-email {email : Email del guardian user}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Verificar manualmente el email de un guardian user (para testing o emergencias)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $email = $this->argument('email');

        // Buscar el guardian user
        $guardian = GuardianUser::where('email', $email)->first();

        if (!$guardian) {
            $this->error("✗ No se encontró ningún guardian user con el email: {$email}");
            return Command::FAILURE;
        }

        // Verificar si ya está verificado
        if ($guardian->email_verified_at) {
            $this->info("✓ El email ya estaba verificado desde: {$guardian->email_verified_at->format('Y-m-d H:i:s')}");
            return Command::SUCCESS;
        }

        // Verificar el email
        $guardian->update(['email_verified_at' => now()]);

        $this->info("✓ Email verificado exitosamente para: {$guardian->name} ({$email})");
        $this->line("  Usuario ID: {$guardian->id}");
        $this->line("  Fecha de verificación: " . now()->format('Y-m-d H:i:s'));

        return Command::SUCCESS;
    }
}
