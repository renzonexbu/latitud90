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
        // Crear/Actualizar Super Admin - Clave: 12345678
        $superAdmin = User::updateOrCreate(
            ['email' => 'admin@test.com'],
            [
                'name' => 'Super Administrador',
                'email_verified_at' => now(),
                'password' => Hash::make('12345678'),
                'remember_token' => null,
            ]
        );
        $superAdmin->syncRoles(['super_admin']);

        // Crear/Actualizar Admin de Contabilidad - Clave: 12345678
        $adminContabilidad = User::updateOrCreate(
            ['email' => 'admin.contabilidad@test.com'],
            [
                'name' => 'Admin Contabilidad',
                'email_verified_at' => now(),
                'password' => Hash::make('12345678'),
                'remember_token' => null,
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
            ]
        );
        $editorContabilidad->syncRoles(['editor_contabilidad']);

        // Crear/Actualizar Editor de Marketing - Clave: 12345678
        $editorMarketing = User::updateOrCreate(
            ['email' => 'editor.marketing@test.com'],
            [
                'name' => 'Editor Marketing',
                'email_verified_at' => now(),
                'password' => Hash::make('12345678'),
                'remember_token' => null,
            ]
        );
        $editorMarketing->syncRoles(['editor_marketing']);

        // Crear/Actualizar Visualizador de Contabilidad - Clave: 12345678
        $visualizadorContabilidad = User::updateOrCreate(
            ['email' => 'visualizador.contabilidad@test.com'],
            [
                'name' => 'Visualizador Contabilidad',
                'email_verified_at' => now(),
                'password' => Hash::make('12345678'),
                'remember_token' => null,
            ]
        );
        $visualizadorContabilidad->syncRoles(['visualizador_contabilidad']);

        // Crear/Actualizar Visualizador de Marketing - Clave: 12345678
        $visualizadorMarketing = User::updateOrCreate(
            ['email' => 'visualizador.marketing@test.com'],
            [
                'name' => 'Visualizador Marketing',
                'email_verified_at' => now(),
                'password' => Hash::make('12345678'),
                'remember_token' => null,
            ]
        );
        $visualizadorMarketing->syncRoles(['visualizador_marketing']);

        // Crear/Actualizar Ejecutivo Comercial - Clave: 12345678
        $ejecutivoComercial = User::updateOrCreate(
            ['email' => 'ejecutivo.comercial@test.com'],
            [
                'name' => 'Ejecutivo Comercial',
                'email_verified_at' => now(),
                'password' => Hash::make('12345678'),
                'remember_token' => null,
            ]
        );
        $ejecutivoComercial->syncRoles(['ejecutivo_comercial']);
    }
}
