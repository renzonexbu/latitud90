<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SalesExecutiveSeeder extends Seeder
{
	/**
	 * Run the database seeds.
	 */
	public function run(): void
	{
		DB::table('sales_executives')->insert([
			['code' => 'SE-001', 'name' => 'Ejecutivo Uno', 'email' => 'ejecutivo1@example.com', 'phone' => ''],
			['code' => 'SE-002', 'name' => 'Ejecutivo Dos', 'email' => 'ejecutivo2@example.com', 'phone' => ''],
			['code' => 'SE-003', 'name' => 'Ejecutivo Tres', 'email' => 'ejecutivo3@example.com', 'phone' => ''],
		]);
	}
}


