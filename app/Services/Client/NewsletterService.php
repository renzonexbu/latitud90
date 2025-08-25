<?php

namespace App\Services\Client;

use App\Models\Newsletter;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class NewsletterService
{
    /**
     * Suscribir un email al newsletter
     */
    public function subscribe(string $email): array
    {
        // Validar el email
        $validator = Validator::make(['email' => $email], [
            'email' => 'required|email|max:255'
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        // Verificar si ya existe
        $existingSubscription = Newsletter::where('email', $email)->first();

        if ($existingSubscription) {
            if ($existingSubscription->is_active) {
                return [
                    'success' => false,
                    'message' => 'Este email ya está suscrito al newsletter.'
                ];
            } else {
                // Reactivar suscripción
                $existingSubscription->update(['is_active' => true]);
                return [
                    'success' => true,
                    'message' => '¡Bienvenido de vuelta! Tu suscripción ha sido reactivada.'
                ];
            }
        }

        // Crear nueva suscripción
        Newsletter::create([
            'email' => $email,
            'is_active' => true
        ]);

        return [
            'success' => true,
            'message' => '¡Gracias por suscribirte a nuestro newsletter!'
        ];
    }

    /**
     * Desuscribir un email del newsletter
     */
    public function unsubscribe(string $email): array
    {
        $subscription = Newsletter::where('email', $email)->first();

        if (!$subscription) {
            return [
                'success' => false,
                'message' => 'Este email no está registrado en nuestro newsletter.'
            ];
        }

        if (!$subscription->is_active) {
            return [
                'success' => false,
                'message' => 'Este email ya está desuscrito del newsletter.'
            ];
        }

        $subscription->update(['is_active' => false]);

        return [
            'success' => true,
            'message' => 'Has sido desuscrito exitosamente del newsletter.'
        ];
    }

    /**
     * Obtener todas las suscripciones activas
     */
    public function getActiveSubscriptions()
    {
        return Newsletter::where('is_active', true)
            ->orderBy('subscribed_at', 'desc')
            ->get();
    }

    /**
     * Obtener estadísticas del newsletter
     */
    public function getStatistics(): array
    {
        $total = Newsletter::count();
        $active = Newsletter::where('is_active', true)->count();
        $inactive = $total - $active;

        return [
            'total' => $total,
            'active' => $active,
            'inactive' => $inactive
        ];
    }
}
