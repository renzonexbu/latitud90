<?php       

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PaymentMethodSeeder extends Seeder
{
    public function run(): void
    {
        $paymentMethods = [
            [
                'name' => 'Transbank',
                'description' => 'Pagos con tarjeta de débito y crédito a través de Webpay Plus',
                'active' => true
            ],
            [
                'name' => 'Khipu',
                'description' => 'Transferencias bancarias a través de Khipu',
                'active' => true
            ],
            [
                'name' => 'Presencial',
                'description' => 'Pagos presenciales en efectivo o tarjeta',
                'active' => true
            ]
        ];

        foreach ($paymentMethods as $method) {
            DB::table('payment_methods')->insert(array_merge($method, [
                'created_at' => now(),
                'updated_at' => now()
            ]));
        }

        $this->command->info('✅ Métodos de pago creados exitosamente:');
        $this->command->info('💳 Transbank (Débito/Crédito)');
        $this->command->info('🏦 Khipu (Transferencia)');
        $this->command->info('🏪 Presencial (Efectivo/Tarjeta)');
    }
}