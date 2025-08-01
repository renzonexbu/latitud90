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
                'name' => 'Colegio San Ignacio',
                'type' => 'school',
                'address' => 'Av. Pocuro 2801, Providencia, Metropolitana',
                'phone' => '+56 2 2345 6789',
                'email' => 'contacto@sanignacio.cl',
                'website' => 'https://www.sanignacio.cl',
                'active' => true,
            ],
            [
                'name' => 'Liceo Manuel de Salas',
                'type' => 'school',
                'address' => 'Av. Manuel de Salas 201, Ñuñoa, Metropolitana',
                'phone' => '+56 2 2345 1234',
                'email' => 'contacto@manuelsalas.cl',
                'website' => 'https://www.manuelsalas.cl',
                'active' => true,
            ],
            [
                'name' => 'Colegio San Pedro Nolasco',
                'type' => 'school',
                'address' => 'Av. Ricardo Lyon 1227, Providencia, Metropolitana',
                'phone' => '+56 2 2345 5678',
                'email' => 'contacto@sanpedronolasco.cl',
                'website' => 'https://www.sanpedronolasco.cl',
                'active' => true,
            ],
            [
                'name' => 'Instituto Nacional',
                'type' => 'school',
                'address' => 'Arturo Prat 33, Santiago, Metropolitana',
                'phone' => '+56 2 2345 9012',
                'email' => 'contacto@institutonacional.cl',
                'website' => 'https://www.institutonacional.cl',
                'active' => true,
            ],
            [
                'name' => 'Colegio San Agustín',
                'type' => 'school',
                'address' => 'Av. Las Condes 12.345, Las Condes, Metropolitana',
                'phone' => '+56 2 2345 3456',
                'email' => 'contacto@sanagustin.cl',
                'website' => 'https://www.sanagustin.cl',
                'active' => true,
            ],
            [
                'name' => 'Liceo 1 Javiera Carrera',
                'type' => 'school',
                'address' => 'Av. Libertador Bernardo O\'Higgins 79, Santiago, Metropolitana',
                'phone' => '+56 2 2345 7890',
                'email' => 'contacto@javieracarrera.cl',
                'website' => 'https://www.javieracarrera.cl',
                'active' => true,
            ],
            [
                'name' => 'Colegio Sagrados Corazones',
                'type' => 'school',
                'address' => 'Av. Alameda 1234, Santiago, Metropolitana',
                'phone' => '+56 2 2345 4567',
                'email' => 'contacto@sagradoscorazones.cl',
                'website' => 'https://www.sagradoscorazones.cl',
                'active' => true,
            ],
            [
                'name' => 'Liceo Experimental Manuel de Salas',
                'type' => 'school',
                'address' => 'Av. Manuel de Salas 201, Ñuñoa, Metropolitana',
                'phone' => '+56 2 2345 2345',
                'email' => 'contacto@experimental.cl',
                'website' => 'https://www.experimental.cl',
                'active' => true,
            ],
            [
                'name' => 'Colegio San Benito',
                'type' => 'school',
                'address' => 'Av. Las Condes 12.678, Las Condes, Metropolitana',
                'phone' => '+56 2 2345 6789',
                'email' => 'contacto@sanbenito.cl',
                'website' => 'https://www.sanbenito.cl',
                'active' => true,
            ],
            [
                'name' => 'Liceo 7 de Niñas',
                'type' => 'school',
                'address' => 'Av. Providencia 1234, Providencia, Metropolitana',
                'phone' => '+56 2 2345 8901',
                'email' => 'contacto@liceo7.cl',
                'website' => 'https://www.liceo7.cl',
                'active' => true,
            ],
        ];

        foreach ($institutions as $institution) {
            Institution::create($institution);
        }
    }
}
