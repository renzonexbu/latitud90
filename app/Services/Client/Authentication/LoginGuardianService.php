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
            \Log::info('=== LOGIN GUARDIAN SERVICE START ===', [
                'email' => $credentials['email'],
                'remember' => $remember,
            ]);

            // Buscar el usuario
            $guardianUser = GuardianUser::where('email', $credentials['email'])->first();

            \Log::info('Guardian user lookup', [
                'email' => $credentials['email'],
                'found' => $guardianUser ? 'yes' : 'no',
                'user_id' => $guardianUser ? $guardianUser->id : null,
            ]);

            if (!$guardianUser) {
                \Log::warning('Guardian user not found', [
                    'email' => $credentials['email']
                ]);

                return [
                    'success' => false,
                    'message' => 'Credenciales incorrectas. Si no tienes cuenta, debes registrarte primero.'
                ];
            }

            // Verificar contraseña
            $passwordMatches = Hash::check($credentials['password'], $guardianUser->password);

            \Log::info('Password verification', [
                'user_id' => $guardianUser->id,
                'matches' => $passwordMatches
            ]);

            if (!$passwordMatches) {
                \Log::warning('Password mismatch', [
                    'user_id' => $guardianUser->id,
                    'email' => $credentials['email']
                ]);

                return [
                    'success' => false,
                    'message' => 'Credenciales incorrectas.'
                ];
            }

            // Verificar que la cuenta no esté suspendida
            \Log::info('Account status check', [
                'user_id' => $guardianUser->id,
                'status' => $guardianUser->status
            ]);

            if ($guardianUser->status === 'suspended') {
                \Log::warning('Account suspended', [
                    'user_id' => $guardianUser->id,
                    'email' => $guardianUser->email
                ]);

                return [
                    'success' => false,
                    'message' => 'Tu cuenta está suspendida. Contacta al administrador.'
                ];
            }

            // Verificar si requiere verificación de email (por defecto SIEMPRE es true)
            $requiresVerification = config('lat90.guardian.require_email_verification', true);

            \Log::info('Email verification check', [
                'user_id' => $guardianUser->id,
                'requires_verification' => $requiresVerification,
                'email_verified_at' => $guardianUser->email_verified_at
            ]);

            if ($requiresVerification && !$guardianUser->email_verified_at) {
                \Log::warning('Email not verified', [
                    'user_id' => $guardianUser->id,
                    'email' => $guardianUser->email
                ]);

                return [
                    'success' => false,
                    'message' => 'Debes verificar tu email antes de iniciar sesión. Revisa tu bandeja de entrada.',
                    'requires_verification' => true,
                    'email' => $guardianUser->email
                ];
            }

            // Autenticar con el guard 'guardian'
            \Log::info('Attempting to authenticate with guardian guard', [
                'user_id' => $guardianUser->id,
                'remember' => $remember
            ]);

            Auth::guard('guardian')->login($guardianUser, $remember);

            \Log::info('Authentication successful', [
                'user_id' => $guardianUser->id,
                'session_id' => session()->getId(),
                'guard_check' => Auth::guard('guardian')->check(),
                'authenticated_user_id' => Auth::guard('guardian')->id()
            ]);

            // Actualizar último login
            $guardianUser->updateLastLogin();

            \Log::info('=== LOGIN GUARDIAN SERVICE END - SUCCESS ===');

            return [
                'success' => true,
                'message' => '¡Bienvenido!',
                'user' => $guardianUser
            ];

        } catch (\Exception $e) {
            \Log::error('=== LOGIN GUARDIAN SERVICE ERROR ===', [
                'email' => $credentials['email'] ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

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
