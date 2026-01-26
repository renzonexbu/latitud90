<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Crear/Actualizar Super Admin Principal (oculto en listado) - Clave: 12345678
        $superAdminDev = User::updateOrCreate(
            ['email' => 'yohan@nexbu.com'],
            [
                'name' => 'Super Admin',
                'email_verified_at' => now(),
                'password' => Hash::make('12345678'),
                'remember_token' => null,
                'is_active' => true,
            ]
        );
        $superAdminDev->syncRoles(['super_admin']);

        // Crear/Actualizar Super Admin Carolina - Clave: 12345678
        $superAdminCarolina = User::updateOrCreate(
            ['email' => 'ccampillay@latitud90.com'],
            [
                'name' => 'Carolina Campillai',
                'email_verified_at' => now(),
                'password' => Hash::make('12345678'),
                'remember_token' => null,
                'is_active' => true,
            ]
        );
        $superAdminCarolina->syncRoles(['super_admin']);

        // Crear/Actualizar Contabilidad - Clave: 12345678
        $contabilidad = User::updateOrCreate(
            ['email' => 'contabilidad@test.com'],
            [
                'name' => 'Contabilidad',
                'email_verified_at' => now(),
                'password' => Hash::make('12345678'),
                'remember_token' => null,
                'is_active' => true,
            ]
        );
        $contabilidad->syncRoles(['contabilidad']);

        // Crear/Actualizar Marketing - Clave: 12345678
        $marketing = User::updateOrCreate(
            ['email' => 'marketing@test.com'],
            [
                'name' => 'Marketing',
                'email_verified_at' => now(),
                'password' => Hash::make('12345678'),
                'remember_token' => null,
                'is_active' => true,
            ]
        );
        $marketing->syncRoles(['marketing']);

        // Crear/Actualizar Contabilidad - Liliam García - Clave: 12345678
        $adminLorena = User::updateOrCreate(
            ['email' => 'lgarcia@latitud90.com'],
            [
                'name' => 'Liliam García',
                'email_verified_at' => now(),
                'password' => Hash::make('12345678'),
                'remember_token' => null,
                'is_active' => true,
            ]
        );
        $adminLorena->syncRoles(['contabilidad']);

        // Crear/Actualizar Contabilidad - Camila Gutiérrez - Clave: 12345678
        $adminCamila = User::updateOrCreate(
            ['email' => 'cgutierrez@latitud90.com'],
            [
                'name' => 'Camila Gutiérrez',
                'email_verified_at' => now(),
                'password' => Hash::make('12345678'),
                'remember_token' => null,
                'is_active' => true,
            ]
        );
        $adminCamila->syncRoles(['contabilidad']);

        // Crear/Actualizar Contabilidad - Pagos - Clave: 12345678
        $adminPagos = User::updateOrCreate(
            ['email' => 'pagos@latitud90.com'],
            [
                'name' => 'Pagos Latitud 90',
                'email_verified_at' => now(),
                'password' => Hash::make('12345678'),
                'remember_token' => null,
                'is_active' => true,
            ]
        );
        $adminPagos->syncRoles(['contabilidad']);

        // Crear/Actualizar Super Admin - Jonathan Miller - Clave: 12345678
        $adminJonathan = User::updateOrCreate(
            ['email' => 'jmiller@latitud90.com'],
            [
                'name' => 'Jonathan Miller',
                'email_verified_at' => now(),
                'password' => Hash::make('12345678'),
                'remember_token' => null,
                'is_active' => true,
            ]
        );
        $adminJonathan->syncRoles(['super_admin']);
    }
}
