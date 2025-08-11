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
                'name' => 'Todos los medios (Débito/Crédito/Transferencia)',
                'description' => 'Acepta débito, crédito y transferencia bancaria.',
                'active' => true
            ],
            [
                'name' => 'Solo pago con Tarjeta (Débito/Crédito)',
                'description' => 'Solo acepta pagos con tarjetas de débito y crédito.',
                'active' => true
            ],
            [
                'name' => 'Solo pago con Transferencia',
                'description' => 'Solo acepta pagos por transferencia bancaria.',
                'active' => true
            ],
            [
                'name' => 'Solo pago contado (Débito/Transferencia)',
                'description' => 'Solo acepta pagos al contado vía débito o transferencia.',
                'active' => true
            ],
        ];

        foreach ($paymentMethods as $method) {
            DB::table('payment_methods')->insert(array_merge($method, [
                'created_at' => now(),
                'updated_at' => now()
            ]));
        }

        $this->command->info('✅ Métodos de pago creados/actualizados exitosamente:');
        foreach ($paymentMethods as $method) {
            $this->command->info('💳 ' . $method['name']);
        }
    }
}