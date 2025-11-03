<?php

namespace App\Services\Client\Authentication;

use App\Models\GuardianUser;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class LoginGuardianService
{
    /**
     * Intentar autenticar al guardian user
     */
    public function login(array $credentials, bool $remember = false): array
    {
        try {
            // Buscar el usuario
            $guardianUser = GuardianUser::where('email', $credentials['email'])->first();

            if (!$guardianUser) {
                return [
                    'success' => false,
                    'message' => 'Credenciales incorrectas.'
                ];
            }

            // Verificar contraseña
            if (!Hash::check($credentials['password'], $guardianUser->password)) {
                return [
                    'success' => false,
                    'message' => 'Credenciales incorrectas.'
                ];
            }

            // Verificar que el email esté verificado
            if (!$guardianUser->email_verified_at) {
                return [
                    'success' => false,
                    'message' => 'Debes verificar tu email antes de iniciar sesión. Revisa tu bandeja de entrada.',
                    'requires_verification' => true,
                    'email' => $guardianUser->email
                ];
            }

            // Verificar que la cuenta no esté suspendida
            if ($guardianUser->status === 'suspended') {
                return [
                    'success' => false,
                    'message' => 'Tu cuenta está suspendida. Contacta al administrador.'
                ];
            }

            // Autenticar con el guard 'guardian'
            Auth::guard('guardian')->login($guardianUser, $remember);

            // Actualizar último login
            $guardianUser->updateLastLogin();

            return [
                'success' => true,
                'message' => '¡Bienvenido!',
                'user' => $guardianUser
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Error al iniciar sesión: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Cerrar sesión
     */
    public function logout(): array
    {
        try {
            Auth::guard('guardian')->logout();

            return [
                'success' => true,
                'message' => 'Sesión cerrada exitosamente.'
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Error al cerrar sesión: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Obtener el usuario autenticado
     */
    public function user(): ?GuardianUser
    {
        return Auth::guard('guardian')->user();
    }

    /**
     * Verificar si está autenticado
     */
    public function check(): bool
    {
        return Auth::guard('guardian')->check();
    }
}
