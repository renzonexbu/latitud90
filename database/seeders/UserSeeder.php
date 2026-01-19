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

        // Crear/Actualizar Admin de Contabilidad - Clave: 12345678
        $adminContabilidad = User::updateOrCreate(
            ['email' => 'admin.contabilidad@test.com'],
            [
                'name' => 'Admin Contabilidad',
                'email_verified_at' => now(),
                'password' => Hash::make('12345678'),
                'remember_token' => null,
                'is_active' => true,
            ]
        );
        $adminContabilidad->syncRoles(['admin_contabilidad']);

        // Crear/Actualizar Admin de Marketing - Clave: 12345678
        $adminMarketing = User::updateOrCreate(
            ['email' => 'admin.marketing@test.com'],
            [
                'name' => 'Admin Marketing',
                'email_verified_at' => now(),
                'password' => Hash::make('12345678'),
                'remember_token' => null,
                'is_active' => true,
            ]
        );
        $adminMarketing->syncRoles(['admin_marketing']);

        // Crear/Actualizar Editor de Contabilidad - Clave: 12345678
        $editorContabilidad = User::updateOrCreate(
            ['email' => 'editor.contabilidad@test.com'],
            [
                'name' => 'Editor Contabilidad',
                'email_verified_at' => now(),
                'password' => Hash::make('12345678'),
                'remember_token' => null,
                'is_active' => true,
            ]
        );
        $editorContabilidad->syncRoles(['editor_contabilidad']);

        // Crear/Actualizar Super Admin - Liliam García - Clave: 12345678
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
        $adminLorena->syncRoles(['super_admin']);

        // Crear/Actualizar Super Admin - Camila Gutiérrez - Clave: 12345678
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
        $adminCamila->syncRoles(['super_admin']);

        // Crear/Actualizar Super Admin - Pagos - Clave: 12345678
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
        $adminPagos->syncRoles(['super_admin']);

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

        // Crear/Actualizar Editor de Marketing - Clave: 12345678
        $editorMarketing = User::updateOrCreate(
            ['email' => 'editor.marketing@test.com'],
            [
                'name' => 'Editor Marketing',
                'email_verified_at' => now(),
                'password' => Hash::make('12345678'),
                'remember_token' => null,
                'is_active' => true,
            ]
        );
        $editorMarketing->syncRoles(['editor_marketing']);

        // Crear/Actualizar Ejecutivo Comercial - Clave: 12345678
        $ejecutivoComercial = User::updateOrCreate(
            ['email' => 'ejecutivo.comercial@test.com'],
            [
                'name' => 'Ejecutivo Comercial',
                'email_verified_at' => now(),
                'password' => Hash::make('12345678'),
                'remember_token' => null,
                'is_active' => true,
            ]
        );
        $ejecutivoComercial->syncRoles(['ejecutivo_comercial']);
    }
}
