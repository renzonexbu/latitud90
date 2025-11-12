<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PaymentGatewaySeeder extends Seeder
{
    public function run(): void
    {
        $paymentGateways = [
            [
                'name' => 'Transbank',
                'code' => 'transbank',
                'description' => 'Pagos con tarjeta de débito y crédito a través de Webpay Plus',
                'active' => true
            ],
            [
                'name' => 'Khipu',
                'code' => 'khipu',
                'description' => 'Transferencias bancarias a través de Khipu',
                'active' => true
            ],
            [
                'name' => 'VirtualPos',
                'code' => 'virtualpos',
                'description' => 'Suscripciones recurrentes mensuales a través de VirtualPos (Transbank)',
                'active' => true
            ],
            [
                'name' => 'Presencial',
                'code' => 'presencial',
                'description' => 'Pagos presenciales en efectivo o tarjeta',
                'active' => true
            ],
            [
                'name' => 'Reembolso',
                'code' => 'refund',
                'description' => 'Reembolsos procesados manualmente',
                'active' => true
            ]
        ];

        foreach ($paymentGateways as $gateway) {
            DB::table('payment_gateways')->updateOrInsert(
                ['code' => $gateway['code']],
                array_merge($gateway, [
                    'created_at' => now(),
                    'updated_at' => now()
                ])
            );
        }

        $this->command->info('✅ Gateways de pago creados exitosamente:');
        $this->command->info('💳 Transbank (Débito/Crédito)');
        $this->command->info('🏦 Khipu (Transferencia)');
        $this->command->info('🔄 VirtualPos (Suscripciones)');
        $this->command->info('🏪 Presencial (Efectivo/Tarjeta)');
        $this->command->info('💰 Reembolso (Manual)');
    }
} 