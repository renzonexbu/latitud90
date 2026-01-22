<?php

namespace Database\Seeders;

use App\Models\SiteContent;
use Illuminate\Database\Seeder;

class PaymentFormContentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $paymentFormContent = [
            [
                'section' => 'payment_form',
                'key' => 'title',
                'type' => 'text',
                'value' => 'Seleccione la forma de pago',
                'default_value' => 'Seleccione la forma de pago',
                'use_default' => false,
                'label' => 'Título principal',
                'order' => 1,
                'is_active' => true,
            ],
            [
                'section' => 'payment_form',
                'key' => 'subtitle',
                'type' => 'textarea',
                'value' => 'Selecciona la forma de pago que mejor se adapte a ti, pago con tarjeta de crédito, débito o Khipu, o pago automático con PAT. Para cualquier consulta, no dudes en escribirnos por WhatsApp',
                'default_value' => 'Selecciona la forma de pago que mejor se adapte a ti, pago con tarjeta de crédito, débito o Khipu, o pago automático con PAT. Para cualquier consulta, no dudes en escribirnos por WhatsApp',
                'use_default' => false,
                'label' => 'Subtítulo/Descripción',
                'order' => 2,
                'is_active' => true,
            ],
            [
                'section' => 'payment_form',
                'key' => 'total_payment_title',
                'type' => 'text',
                'value' => 'Pagar con Tarjeta Crédito, Débito',
                'default_value' => 'Pagar con Tarjeta Crédito, Débito',
                'use_default' => false,
                'label' => 'Título opción "Pago Total"',
                'order' => 3,
                'is_active' => true,
            ],
            [
                'section' => 'payment_form',
                'key' => 'subscription_title',
                'type' => 'text',
                'value' => 'Suscribir pago Automático (PAT)',
                'default_value' => 'Suscribir pago Automático (PAT)',
                'use_default' => false,
                'label' => 'Título opción "Suscripción"',
                'order' => 4,
                'is_active' => true,
            ],
            [
                'section' => 'payment_form',
                'key' => 'khipu_warning',
                'type' => 'textarea',
                'value' => '⚠️ Importante: la primera transferencia a una cuenta nueva tiene un límite bancario de $250.000. Si el monto supera este valor, escríbanos a pagos@latitud90.com para recibir un link de pago.',
                'default_value' => '⚠️ Importante: la primera transferencia a una cuenta nueva tiene un límite bancario de $250.000. Si el monto supera este valor, escríbanos a pagos@latitud90.com para recibir un link de pago.',
                'use_default' => false,
                'label' => 'Mensaje de advertencia Khipu',
                'order' => 5,
                'is_active' => true,
            ],
        ];

        foreach ($paymentFormContent as $content) {
            SiteContent::updateOrCreate(
                [
                    'section' => $content['section'],
                    'key' => $content['key'],
                ],
                $content
            );
        }
    }
}
