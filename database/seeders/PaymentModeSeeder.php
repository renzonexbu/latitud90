<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PaymentModeSeeder extends Seeder
{
    public function run(): void
    {
        $paymentModes = [
            [
                'name' => 'Pago Total',
                'code' => 'full',
                'description' => 'Pago completo del monto total',
                'active' => true
            ],
            [
                'name' => 'Mensual | Cuota Lat 90',
                'code' => 'lat90_installments',
                'description' => 'Pago en cuotas mensuales Lat90',
                'active' => true
            ]
        ];

        // Insertar modalidades de pago
        foreach ($paymentModes as $mode) {
            DB::table('payment_modes')->insert(array_merge($mode, [
                'created_at' => now(),
                'updated_at' => now()
            ]));
        }

        // Obtener IDs de las modalidades
        $pagoTotalId = DB::table('payment_modes')->where('code', 'full')->first()->id;
        $cuotaLat90Id = DB::table('payment_modes')->where('code', 'lat90_installments')->first()->id;

        // Obtener todos los métodos de pago
        $paymentMethods = DB::table('payment_methods')->get();

        // Asignar métodos a Pago Total (todos los métodos)
        foreach ($paymentMethods as $method) {
            DB::table('payment_mode_method')->insert([
                'payment_mode_id' => $pagoTotalId,
                'payment_method_id' => $method->id,
                'created_at' => now(),
                'updated_at' => now()
            ]);
        }

        // Asignar métodos a Cuota Lat90 (todos los métodos también)
        foreach ($paymentMethods as $method) {
            DB::table('payment_mode_method')->insert([
                'payment_mode_id' => $cuotaLat90Id,
                'payment_method_id' => $method->id,
                'created_at' => now(),
                'updated_at' => now()
            ]);
        }

        $this->command->info('✅ Modalidades de pago creadas exitosamente:');
        $this->command->info('💰 Pago Total - con todos los métodos de pago');
        $this->command->info('📅 Mensual | Cuota Lat 90 - con todos los métodos de pago');
    }
} 