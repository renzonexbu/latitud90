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
                'name' => 'Transferencia bancaria (Khipu)',
                'description' => 'Pago vía Khipu con redirección segura a tu banco.',
                'active' => true
            ],
            [
                'name' => 'Débito y crédito sin cuotas (Webpay)',
                'description' => 'Pago con Webpay en una sola cuota (sin interés).',
                'active' => true
            ],
            [
                'name' => 'Débito y crédito 3 cuotas sin interés (Webpay)',
                'description' => 'Pago con Webpay en 3 cuotas precio contado (sin interés).',
                'active' => true
            ],
            [
                'name' => 'Débito y crédito 6 cuotas sin interés (Webpay)',
                'description' => 'Pago con Webpay en 6 cuotas precio contado (sin interés).',
                'active' => true
            ],
            [
                'name' => 'Débito y crédito 12 cuotas sin interés (Webpay)',
                'description' => 'Pago con Webpay en 12 cuotas precio contado (sin interés).',
                'active' => true
            ],
        ];

        foreach ($paymentMethods as $method) {
            DB::table('payment_methods')->insert(array_merge($method, [
                'created_at' => now(),
                'updated_at' => now()
            ]));
        }

        $this->command->info('✅ Métodos de pago creados exitosamente:');
        foreach ($paymentMethods as $method) {
            $this->command->info('💳 ' . $method['name']);
        }
    }
}