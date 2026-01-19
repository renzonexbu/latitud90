<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class CheckGuardianEmailVerification extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'guardian:check-email-verification';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Verifica si la verificación de email está habilitada para guardian users';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $requiresVerification = config('lat90.guardian.require_email_verification', true);
        $envValue = env('GUARDIAN_REQUIRE_EMAIL_VERIFICATION');

        $this->info('=== Configuración de Verificación de Email Guardian ===');
        $this->newLine();

        // Mostrar valor del .env
        if ($envValue !== null) {
            $this->line("Valor en .env: <fg=yellow>{$envValue}</>");
        } else {
            $this->line("Valor en .env: <fg=red>NO CONFIGURADO</> (usando valor por defecto)");
        }

        // Mostrar valor efectivo
        $status = $requiresVerification ? '<fg=green>HABILITADA</>' : '<fg=red>DESHABILITADA</>';
        $this->line("Estado actual: {$status}");

        $this->newLine();

        // Mostrar explicación
        if ($requiresVerification) {
            $this->info('✓ Los guardian users DEBEN verificar su email antes de poder iniciar sesión.');
        } else {
            $this->warn('⚠ Los guardian users PUEDEN iniciar sesión SIN verificar su email.');
            $this->warn('  Para mayor seguridad, agrega en tu .env: GUARDIAN_REQUIRE_EMAIL_VERIFICATION=true');
        }

        $this->newLine();

        // Estadísticas
        $totalGuardians = \App\Models\GuardianUser::count();
        $verifiedGuardians = \App\Models\GuardianUser::whereNotNull('email_verified_at')->count();
        $unverifiedGuardians = \App\Models\GuardianUser::whereNull('email_verified_at')->count();

        $this->info('=== Estadísticas de Guardian Users ===');
        $this->line("Total de guardians: <fg=cyan>{$totalGuardians}</>");
        $this->line("Emails verificados: <fg=green>{$verifiedGuardians}</>");
        $this->line("Emails sin verificar: <fg=yellow>{$unverifiedGuardians}</>");

        if ($unverifiedGuardians > 0 && $requiresVerification) {
            $this->newLine();
            $this->warn("⚠ Hay {$unverifiedGuardians} guardians que NO podrán iniciar sesión hasta verificar su email.");
        }

        return Command::SUCCESS;
    }
}
