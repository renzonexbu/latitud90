<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DocumentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('document')->delete();

        $documents = array(
            array('name' => 'RUT', 'country' => 'CL'),
            array('name' => 'PASAPORTE', 'country' => null),
            array('name' => 'DNI', 'country' => 'AR'),
        );

        DB::table('document')->insert($documents);
    }
}
