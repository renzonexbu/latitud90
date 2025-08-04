<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Institution;

class InstitutionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $institutions = [
            [
                'name' => 'Colegio San Agustín',
                'type' => 'school',
                'address' => 'Av. Principal 123, Santiago',
                'phone' => '+56 2 2345 6789',
                'email' => 'contacto@sanagustin.cl',
                'website' => 'https://www.sanagustin.cl',
                'active' => true,
            ],
            [
                'name' => 'Universidad de Chile',
                'type' => 'university',
                'address' => 'Av. Libertador Bernardo O\'Higgins 1058, Santiago',
                'phone' => '+56 2 2978 2000',
                'email' => 'contacto@uchile.cl',
                'website' => 'https://www.uchile.cl',
                'active' => true,
            ],
            [
                'name' => 'Liceo Bicentenario',
                'type' => 'school',
                'address' => 'Calle Los Aromos 456, Valparaíso',
                'phone' => '+56 32 2345 6789',
                'email' => 'info@liceobicentenario.cl',
                'website' => 'https://www.liceobicentenario.cl',
                'active' => true,
            ],
            [
                'name' => 'Pontificia Universidad Católica de Chile',
                'type' => 'university',
                'address' => 'Av. Libertador Bernardo O\'Higgins 340, Santiago',
                'phone' => '+56 2 2354 2000',
                'email' => 'contacto@uc.cl',
                'website' => 'https://www.uc.cl',
                'active' => true,
            ],
            [
                'name' => 'Instituto Técnico Profesional',
                'type' => 'other',
                'address' => 'Av. Providencia 1234, Santiago',
                'phone' => '+56 2 2345 1234',
                'email' => 'info@itp.cl',
                'website' => 'https://www.itp.cl',
                'active' => true,
            ],
        ];

        foreach ($institutions as $institution) {
            Institution::create($institution);
        }
    }
}
