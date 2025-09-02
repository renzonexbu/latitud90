<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\MarketingMail;

class MarketingMailsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Crear algunos emails de marketing de ejemplo
        $emails = [
            'ejemplo1@test.com',
            'ejemplo2@test.com',
            'ejemplo3@test.com',
        ];

        foreach ($emails as $email) {
            MarketingMail::firstOrCreate(
                ['email' => $email],
                [
                    'email' => $email,
                    'is_active' => true
                ]
            );
        }

        $this->command->info('✅ Seeder de MarketingMails ejecutado correctamente');
    }
}
