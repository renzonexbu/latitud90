<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): string|null
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();

        // Datos base del usuario
        $userData = null;

        if ($user) {
            $userData = [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ];

            // Solo agregar roles y permisos si es un usuario del panel admin (tiene el trait HasRoles)
            if (method_exists($user, 'getRoleNames')) {
                $userData['roles'] = $user->getRoleNames()->toArray();
                $userData['permissions'] = $user->getAllPermissions()->pluck('name')->toArray();
            }
        }

        // Verificar si hay un guardian autenticado
        $guardianUser = auth('guardian')->user();
        if ($guardianUser) {
            if (!$userData) {
                $userData = [
                    'id' => $guardianUser->id,
                    'name' => $guardianUser->name,
                    'email' => $guardianUser->email,
                ];
            }

            // Agregar información del guardian
            $userData['guardian_account'] = [
                'id' => $guardianUser->id,
                'name' => $guardianUser->name,
                'email' => $guardianUser->email,
                'status' => $guardianUser->status ?? 'active',
            ];
        }

        return [
            ...parent::share($request),
            'auth' => [
                'user' => $userData,
            ],
            '_csrf' => csrf_token(),
        ];
    }
}
