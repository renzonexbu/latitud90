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
        // Crear Super Admin
        $superAdmin = User::create([
            'name' => 'Super Administrador',
            'email' => 'admin@test.com',
            'email_verified_at' => now(),
            'password' => Hash::make('12345678'),
            'remember_token' => null,
        ]);
        $superAdmin->assignRole('super_admin');

        // Crear Admin de Contabilidad
        $adminContabilidad = User::create([
            'name' => 'Admin Contabilidad',
            'email' => 'admin.contabilidad@test.com',
            'email_verified_at' => now(),
            'password' => Hash::make('12345678'),
            'remember_token' => null,
        ]);
        $adminContabilidad->assignRole('admin_contabilidad');

        // Crear Admin de Marketing
        $adminMarketing = User::create([
            'name' => 'Admin Marketing',
            'email' => 'admin.marketing@test.com',
            'email_verified_at' => now(),
            'password' => Hash::make('12345678'),
            'remember_token' => null,
        ]);
        $adminMarketing->assignRole('admin_marketing');
    }
}
