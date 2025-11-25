<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProgramCoursePaymentOptionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Asocia opciones de pago a todos los program_courses existentes.
     */
    public function run(): void
    {
        // Obtener todos los program_courses
        $programCourses = DB::table('program_courses')->pluck('id');

        if ($programCourses->isEmpty()) {
            $this->command->info('No hay program_courses en la base de datos.');
            return;
        }

        // Obtener todas las opciones de pago
        $paymentOptions = DB::table('payment_options')->pluck('id', 'code');

        if ($paymentOptions->isEmpty()) {
            $this->command->info('No hay payment_options en la base de datos. Ejecuta PaymentOptionSeeder primero.');
            return;
        }

        // Opciones de pago para modo "full" (pago total)
        $fullPaymentCodes = [
            'full_transfer_khipu',
            'full_debit_credit_0',
            'full_debit_credit_3',
            'full_debit_credit_6',
            'full_debit_credit_9',
            'full_debit_credit_12',
            'full_international',
        ];

        // Opciones de pago para modo "lat90" (suscripcion/cuotas)
        $lat90PaymentCodes = [
            'lat90_transfer_khipu',
            'lat90_debit_credit_0',
        ];

        $now = now();
        $insertData = [];

        foreach ($programCourses as $programCourseId) {
            // Agregar opciones de pago total
            foreach ($fullPaymentCodes as $code) {
                if (isset($paymentOptions[$code])) {
                    $insertData[] = [
                        'program_course_id' => $programCourseId,
                        'payment_option_id' => $paymentOptions[$code],
                        'enabled' => true,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }
            }

            // Agregar opciones de pago en cuotas (lat90)
            foreach ($lat90PaymentCodes as $code) {
                if (isset($paymentOptions[$code])) {
                    $insertData[] = [
                        'program_course_id' => $programCourseId,
                        'payment_option_id' => $paymentOptions[$code],
                        'enabled' => true,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }
            }
        }

        // Insertar en la base de datos (evitar duplicados)
        foreach (array_chunk($insertData, 100) as $chunk) {
            DB::table('program_course_payment_option')->upsert(
                $chunk,
                ['program_course_id', 'payment_option_id'],
                ['enabled', 'updated_at']
            );
        }

        $this->command->info('Opciones de pago agregadas exitosamente a ' . $programCourses->count() . ' program_course(s).');
        $this->command->info('Total opciones procesadas: ' . count($insertData));
    }
}
