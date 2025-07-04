<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Crear datos de prueba con factories
        \App\Models\User::factory(10)->create();

        // Crear usuario de prueba específico
        \App\Models\User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        // Ejecutar seeders específicos con datos de demostración
        $this->call([
            AdminDemoSeeder::class,
            EnhancedDemoSeeder::class,
        ]);
    }
}
