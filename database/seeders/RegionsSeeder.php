<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RegionsSeeder extends Seeder
{
    public function run()
    {
        $regions = [
            [
                'id' => 1,
                'name' => 'Arica y Parinacota',
                'created_at' => '2025-08-07 12:51:55',
                'updated_at' => '2025-08-07 12:51:55',
            ],
            [
                'id' => 2,
                'name' => 'Tarapacá',
                'created_at' => '2025-08-07 12:51:55',
                'updated_at' => '2025-08-07 12:51:55',
            ],
            [
                'id' => 3,
                'name' => 'Antofagasta',
                'created_at' => '2025-08-07 12:51:55',
                'updated_at' => '2025-08-07 12:51:55',
            ],
            [
                'id' => 4,
                'name' => 'Atacama',
                'created_at' => '2025-08-07 12:51:55',
                'updated_at' => '2025-08-07 12:51:55',
            ],
            [
                'id' => 5,
                'name' => 'Coquimbo',
                'created_at' => '2025-08-07 12:51:55',
                'updated_at' => '2025-08-07 12:51:55',
            ],
            [
                'id' => 6,
                'name' => 'Valparaíso',
                'created_at' => '2025-08-07 12:51:55',
                'updated_at' => '2025-08-07 12:51:55',
            ],
            [
                'id' => 7,
                'name' => 'Región del Libertador Gral. Bernardo O’Higgins',
                'created_at' => '2025-08-07 12:51:55',
                'updated_at' => '2025-08-07 12:51:55',
            ],
            [
                'id' => 8,
                'name' => 'Región del Maule',
                'created_at' => '2025-08-07 12:51:55',
                'updated_at' => '2025-08-07 12:51:55',
            ],
            [
                'id' => 9,
                'name' => 'Región del Biobío',
                'created_at' => '2025-08-07 12:51:55',
                'updated_at' => '2025-08-07 12:51:55',
            ],
            [
                'id' => 10,
                'name' => 'Región de la Araucanía',
                'created_at' => '2025-08-07 12:51:55',
                'updated_at' => '2025-08-07 12:51:55',
            ],
            [
                'id' => 11,
                'name' => 'Región de Los Ríos',
                'created_at' => '2025-08-07 12:51:55',
                'updated_at' => '2025-08-07 12:51:55',
            ],
            [
                'id' => 12,
                'name' => 'Región de Los Lagos',
                'created_at' => '2025-08-07 12:51:55',
                'updated_at' => '2025-08-07 12:51:55',
            ],
            [
                'id' => 13,
                'name' => 'Región Aisén del Gral. Carlos Ibáñez del Campo',
                'created_at' => '2025-08-07 12:51:55',
                'updated_at' => '2025-08-07 12:51:55',
            ],
            [
                'id' => 14,
                'name' => 'Región de Magallanes y de la AntárVca Chilena',
                'created_at' => '2025-08-07 12:51:55',
                'updated_at' => '2025-08-07 12:51:55',
            ],
            [
                'id' => 15,
                'name' => 'Región Metropolitana de Santiago',
                'created_at' => '2025-08-07 12:51:55',
                'updated_at' => '2025-08-07 12:51:55',
            ],
            [
                'id' => 16,
                'name' => 'Región de Ñuble',
                'created_at' => '2025-08-07 12:51:55',
                'updated_at' => '2025-08-07 12:51:55',
            ],
        ];


        DB::table('regions')->insert($regions);
    }
}
