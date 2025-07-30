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
                'name' => 'Cuota Lat90',
                'code' => 'lat90_installments',
                'description' => 'Pago en cuotas mensuales Lat90',
                'active' => true
            ]
        ];

        foreach ($paymentModes as $mode) {
            DB::table('payment_modes')->insert(array_merge($mode, [
                'created_at' => now(),
                'updated_at' => now()
            ]));
        }

        $this->command->info('✅ Modalidades de pago creadas exitosamente:');
        $this->command->info('💰 Pago Total');
        $this->command->info('📅 Cuota Lat90');
    }
} 