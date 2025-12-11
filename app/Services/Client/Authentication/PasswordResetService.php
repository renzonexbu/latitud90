<?php

namespace App\Services\Client\Authentication;

use App\Models\GuardianUser;
use App\Mail\GuardianPasswordReset;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class PasswordResetService
{
    /**
     * Solicitar reseteo de contraseña
     */
    public function requestReset(string $email): array
    {
        try {
            $guardianUser = GuardianUser::where('email', $email)->first();

            if (!$guardianUser) {
                // Por seguridad, no revelamos si el email existe o no
                return [
                    'success' => true,
                    'message' => 'Si el email está registrado, recibirás un enlace para resetear tu contraseña.'
                ];
            }

            // Generar token de reseteo
            $resetToken = Str::random(64);

            // Guardar token en cache por 1 hora
            cache()->put(
                'password_reset_' . $guardianUser->id,
                $resetToken,
                now()->addHour()
            );

            // Enviar email (no rompe el flujo si falla)
            try {
                Mail::to($guardianUser->email)->send(
                    new GuardianPasswordReset($guardianUser, $resetToken)
                );
            } catch (\Exception $mailError) {
                \Log::warning('No se pudo enviar email de reseteo de contraseña: ' . $mailError->getMessage());

                return [
                    'success' => false,
                    'message' => 'No se pudo enviar el email en este momento. Por favor intenta más tarde.'
                ];
            }

            return [
                'success' => true,
                'message' => 'Si el email está registrado, recibirás un enlace para resetear tu contraseña.'
            ];

        } catch (\Exception $e) {
            \Log::error('Error en solicitud de reseteo: ' . $e->getMessage());

            return [
                'success' => false,
                'message' => 'Ocurrió un error. Por favor intenta más tarde.'
            ];
        }
    }

    /**
     * Validar token de reseteo
     */
    public function validateToken(int $userId, string $token): array
    {
        $cachedToken = cache()->get('password_reset_' . $userId);

        if (!$cachedToken || $cachedToken !== $token) {
            return [
                'success' => false,
                'message' => 'Token inválido o expirado.'
            ];
        }

        return [
            'success' => true,
            'message' => 'Token válido.'
        ];
    }

    /**
     * Resetear contraseña
     */
    public function resetPassword(int $userId, string $token, string $newPassword): array
    {
        try {
            // Validar token
            $validation = $this->validateToken($userId, $token);

            if (!$validation['success']) {
                return $validation;
            }

            // Obtener usuario
            $guardianUser = GuardianUser::find($userId);

            if (!$guardianUser) {
                return [
                    'success' => false,
                    'message' => 'Usuario no encontrado.'
                ];
            }

            // Actualizar contraseña
            $guardianUser->update([
                'password' => Hash::make($newPassword)
            ]);

            // Eliminar token del cache
            cache()->forget('password_reset_' . $userId);

            return [
                'success' => true,
                'message' => 'Contraseña actualizada exitosamente.'
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Error al resetear contraseña: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Cambiar contraseña (usuario autenticado)
     */
    public function changePassword(GuardianUser $user, string $currentPassword, string $newPassword): array
    {
        try {
            // Verificar contraseña actual
            if (!Hash::check($currentPassword, $user->password)) {
                return [
                    'success' => false,
                    'message' => 'La contraseña actual es incorrecta.'
                ];
            }

            // Actualizar contraseña
            $user->update([
                'password' => Hash::make($newPassword)
            ]);

            return [
                'success' => true,
                'message' => 'Contraseña cambiada exitosamente.'
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Error al cambiar contraseña: ' . $e->getMessage()
            ];
        }
    }
}
