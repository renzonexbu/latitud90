<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::create([
            'name' => 'Administrador',
            'email' => 'admin@test.com',
            'email_verified_at' => now(),
            'password' => Hash::make('12345678'),
            'remember_token' => null,
        ]);
    }
}
