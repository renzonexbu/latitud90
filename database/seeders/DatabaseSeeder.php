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
            PaymentGatewaySeeder::class,
            PaymentOptionSeeder::class,
            SalesExecutiveSeeder::class,
            // ProgramsSeeder::class,
            InstitutionSeeder::class,
            RegionsSeeder::class,
            ComunesSeeder::class,
            RolesAndPermissionsSeeder::class,
            UserSeeder::class,
            DocumentTemplateSeeder::class,
            SiteContentSeeder::class,
            TermsConditionsSeeder::class,
            PaymentFormContentSeeder::class,
            SchoolSeeder::class,
        ]);
    }
}
