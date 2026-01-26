<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;

class UpdateUserRolesSeeder extends Seeder
{
    /**
     * Actualizar roles de usuarios existentes sin modificar contraseñas u otros datos.
     */
    public function run(): void
    {
        // Super Admins
        $superAdmins = [
            'yohan@nexbu.com',
            'jmiller@latitud90.com',
            'ccampillay@latitud90.com',
        ];

        foreach ($superAdmins as $email) {
            $user = User::where('email', $email)->first();
            if ($user) {
                $user->syncRoles(['super_admin']);
                $this->command->info("✓ Usuario {$email} actualizado a super_admin");
            } else {
                $this->command->warn("⚠ Usuario {$email} no encontrado");
            }
        }

        // Contabilidad
        $contabilidadUsers = [
            'lgarcia@latitud90.com',
            'cgutierrez@latitud90.com',
            'pagos@latitud90.com',
        ];

        foreach ($contabilidadUsers as $email) {
            $user = User::where('email', $email)->first();
            if ($user) {
                $user->syncRoles(['contabilidad']);
                $this->command->info("✓ Usuario {$email} actualizado a contabilidad");
            } else {
                $this->command->warn("⚠ Usuario {$email} no encontrado");
            }
        }

        $this->command->info('');
        $this->command->info('✅ Actualización de roles completada');
    }
}
