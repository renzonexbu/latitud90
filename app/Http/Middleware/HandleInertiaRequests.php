<?php

namespace App\Http\Middleware;

use App\Models\Payment;
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
        // Usuario admin (guard 'web')
        $user = $request->user();
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

        // Usuario guardian (guard 'guardian') - SEPARADO del usuario admin
        $guardianUser = auth('guardian')->user();
        $guardianData = null;

        if ($guardianUser) {
            $guardianData = [
                'id' => $guardianUser->id,
                'name' => $guardianUser->name,
                'email' => $guardianUser->email,
                'status' => $guardianUser->status ?? 'active',
            ];
        }

        return [
            ...parent::share($request),
            'auth' => [
                'user' => $userData,        // Solo usuarios admin (guard 'web')
                'guardian' => $guardianData, // Solo usuarios guardian (guard 'guardian')
            ],
            '_csrf' => csrf_token(),
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
                'warning' => fn () => $request->session()->get('warning'),
                'info' => fn () => $request->session()->get('info'),
                'conversion_results' => fn () => $request->session()->get('conversion_results'),
            ],
            'ac_conversion_pending' => fn () => $user ? Payment::where('document_type', 'AC')
                ->whereNull('bsale_number')
                ->where('status', 'completed')
                ->whereRaw("(gateway_response IS NULL OR JSON_EXTRACT(gateway_response, '$.ac_converted') IS NULL)")
                ->count() : 0,
        ];
    }
}
