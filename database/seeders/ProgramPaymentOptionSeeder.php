<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProgramPaymentOptionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Obtener todos los programas (templates)
        $programs = DB::table('programs')->pluck('id');

        if ($programs->isEmpty()) {
            $this->command->info('No hay programas en la base de datos.');
            return;
        }

        // Opciones de pago para modo "full" (pago total)
        $fullPaymentOptions = [
            1,  // full_transfer_khipu
            2,  // full_debit_credit_0
            3,  // full_debit_credit_3
            4,  // full_debit_credit_6
            5,  // full_debit_credit_9
            6,  // full_debit_credit_12
            7,  // full_international
        ];

        // Opciones de pago para modo "lat90" (suscripción/cuotas)
        $lat90PaymentOptions = [
            13, // lat90_transfer_khipu
            14, // lat90_debit_credit_0
        ];

        $now = now();
        $insertData = [];

        foreach ($programs as $programId) {
            // Agregar opciones de pago total
            foreach ($fullPaymentOptions as $paymentOptionId) {
                $insertData[] = [
                    'program_id' => $programId,
                    'payment_option_id' => $paymentOptionId,
                    'enabled' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            // Agregar opciones de pago en cuotas (lat90)
            foreach ($lat90PaymentOptions as $paymentOptionId) {
                $insertData[] = [
                    'program_id' => $programId,
                    'payment_option_id' => $paymentOptionId,
                    'enabled' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        // Insertar en la base de datos
        DB::table('program_payment_option')->insert($insertData);

        $this->command->info('Opciones de pago agregadas exitosamente a ' . $programs->count() . ' programa(s).');
        $this->command->info('Total opciones insertadas: ' . count($insertData));
    }
}
