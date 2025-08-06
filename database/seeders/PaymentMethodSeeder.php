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
                'description' => 'Acepta todos los métodos de pago disponibles',
                'active' => true
            ],
            [
                'name' => 'Solo pago con Tarjeta (Débito/Crédito)',
                'description' => 'Solo acepta pagos con tarjeta de débito o crédito',
                'active' => true
            ],
            [
                'name' => 'Solo pago transferencia',
                'description' => 'Solo acepta transferencias bancarias',
                'active' => true
            ],
            [
                'name' => 'Solo pago contado (Débito/Transferencia)',
                'description' => 'Solo acepta pagos en efectivo, débito o transferencia',
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
        foreach ($paymentMethods as $method) {
            $this->command->info('💳 ' . $method['name']);
        }
    }
}