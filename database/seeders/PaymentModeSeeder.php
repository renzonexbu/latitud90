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
                'name' => 'Mensual | Cuota Lat90',
                'code' => 'lat90_installments',
                'description' => 'Pago en cuotas mensuales Latitud 90',
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

        // Obtener IDs de métodos específicos por nombre
        $khipuId    = DB::table('payment_methods')->where('name', 'Transferencia bancaria (Khipu)')->value('id');
        $webpay1Id  = DB::table('payment_methods')->where('name', 'Débito y crédito sin cuotas (Webpay)')->value('id');
        $webpay3Id  = DB::table('payment_methods')->where('name', 'Débito y crédito 3 cuotas sin interés (Webpay)')->value('id');
        $webpay6Id  = DB::table('payment_methods')->where('name', 'Débito y crédito 6 cuotas sin interés (Webpay)')->value('id');
        $webpay12Id = DB::table('payment_methods')->where('name', 'Débito y crédito 12 cuotas sin interés (Webpay)')->value('id');

        // Asignar métodos a Pago Total (Khipu + Webpay sin cuotas)
        foreach (array_filter([$khipuId, $webpay1Id]) as $methodId) {
            DB::table('payment_mode_method')->insert([
                'payment_mode_id' => $pagoTotalId,
                'payment_method_id' => $methodId,
                'created_at' => now(),
                'updated_at' => now()
            ]);
        }

        // Asignar métodos a Cuota Lat90 (Khipu + Webpay 1/3/6/12)
        foreach (array_filter([$khipuId, $webpay1Id, $webpay3Id, $webpay6Id, $webpay12Id]) as $methodId) {
            DB::table('payment_mode_method')->insert([
                'payment_mode_id' => $cuotaLat90Id,
                'payment_method_id' => $methodId,
                'created_at' => now(),
                'updated_at' => now()
            ]);
        }

        $this->command->info('✅ Modalidades De Pago De Latitud 90 Creadas Exitosamente:');
        $this->command->info('💰 Pago Total - Con Khipu Y Webpay Sin Cuotas');
        $this->command->info('📅 Mensual | Cuota Lat90 - Khipu + Webpay 1/3/6/12 Cuotas Sin Interés');
    }
} 