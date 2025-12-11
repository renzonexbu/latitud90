<?php

namespace App\Http\Controllers\Admin\Maintainer;

use App\Http\Controllers\Controller;
use App\Models\SiteContent;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PaymentFormController extends Controller
{
    /**
     * Mostrar la vista de edición del formulario de pago.
     */
    public function index()
    {
        $content = SiteContent::where('section', 'payment_form')
            ->where('is_active', true)
            ->orderBy('order')
            ->get()
            ->keyBy('key')
            ->map(function ($item) {
                return [
                    'value' => $item->value,
                    'default_value' => $item->default_value,
                    'use_default' => (bool) $item->use_default,
                    'key' => $item->key,
                ];
            });

        return Inertia::render('Admin/Maintainer/PaymentForm', [
            'content' => $content,
        ]);
    }

    /**
     * Actualizar el contenido del formulario de pago.
     */
    public function update(Request $request)
    {
        $validated = $request->validate([
            'items' => 'required|array',
            'items.*.key' => 'required|string',
            'items.*.value' => 'required|string',
            'items.*.use_default' => 'boolean',
        ]);

        $fieldsConfig = [
            'title' => [
                'label' => 'Título principal',
                'type' => 'text',
                'order' => 1,
                'default_value' => 'Seleccione la forma de pago',
            ],
            'subtitle' => [
                'label' => 'Subtítulo/Descripción',
                'type' => 'textarea',
                'order' => 2,
                'default_value' => 'Selecciona la forma de pago que mejor se adapte a ti, pago con tarjeta de crédito, débito o Khipu, o pago automático con PAT. Para cualquier consulta, no dudes en escribirnos por WhatsApp',
            ],
            'total_payment_title' => [
                'label' => 'Título opción "Pago Total"',
                'type' => 'text',
                'order' => 3,
                'default_value' => 'Pago total',
            ],
            'subscription_title' => [
                'label' => 'Título opción "Suscripción"',
                'type' => 'text',
                'order' => 4,
                'default_value' => 'Suscripción',
            ],
            'khipu_warning' => [
                'label' => 'Mensaje de advertencia Khipu',
                'type' => 'textarea',
                'order' => 5,
                'default_value' => '⚠️ Importante: la primera transferencia a una cuenta nueva tiene un límite bancario de $250.000. Si el monto supera este valor, escríbanos a pagos@latitud90.com para recibir un link de pago.',
            ],
        ];

        foreach ($validated['items'] as $item) {
            $key = $item['key'];

            if (!isset($fieldsConfig[$key])) {
                continue;
            }

            $fieldConfig = $fieldsConfig[$key];
            $useDefault = $item['use_default'] ?? false;

            SiteContent::updateOrCreate(
                [
                    'section' => 'payment_form',
                    'key' => $key,
                ],
                [
                    'value' => $item['value'],
                    'default_value' => $fieldConfig['default_value'],
                    'use_default' => $useDefault,
                    'label' => $fieldConfig['label'],
                    'type' => $fieldConfig['type'],
                    'order' => $fieldConfig['order'],
                    'is_active' => true,
                ]
            );
        }

        return redirect()->back()->with('success', 'Contenido del formulario de pago actualizado correctamente.');
    }
}
