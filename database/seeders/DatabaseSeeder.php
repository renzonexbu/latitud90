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
        $this->call([
            CountrySeeder::class,
            DocumentSeeder::class,
            PaymentMethodSeeder::class,
            PaymentGatewaySeeder::class,
            PaymentModeSeeder::class,
            UserSeeder::class,
			SalesExecutiveSeeder::class,
			// ProgramsSeeder::class,
            InstitutionSeeder::class,
            RegionsSeeder::class,
            ComunesSeeder::class,
        ]);
    }
}
