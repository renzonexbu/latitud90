<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\MarketingMail;

class MarketingMailSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $emails = [
            'usuario1@example.com',
            'usuario2@example.com',
            'cliente@empresa.cl',
            'marketing@latitud90.cl',
            'ventas@latitud90.cl',
            'info@latitud90.cl',
            'admin@latitud90.cl',
            'soporte@latitud90.cl',
            'newsletter@latitud90.cl',
            'contacto@latitud90.cl',
            'prueba@test.com',
            'demo@example.org',
            'usuario.test@latitud90.cl',
            'cliente.nuevo@empresa.com',
            'marketing.digital@latitud90.cl'
        ];

        foreach ($emails as $email) {
            MarketingMail::create([
                'email' => $email,
                'is_active' => rand(0, 1) == 1, // 50% chance de estar activo
            ]);
        }
    }
}
