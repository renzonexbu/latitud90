<?php

namespace App\Services\Client\Authentication;

use App\Models\GuardianEmergencyContact;
use App\Models\GuardianUser;
use App\Mail\GuardianEmailVerification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class RegisterGuardianService
{
    /**
     * Registrar un nuevo guardian user
     */
    public function register(array $data): array
    {
        try {
            DB::beginTransaction();

            // Verificar si el email ya está registrado
            if (GuardianUser::where('email', $data['email'])->exists()) {
                return [
                    'success' => false,
                    'message' => 'Este email ya está registrado.'
                ];
            }

            // Crear el guardian user con todos los datos del formulario
            $guardianUser = GuardianUser::create([
                'document_id' => $data['document_type_id'],
                'document' => $data['document_number'],
                'name' => $data['name'],
                'email' => $data['email'],
                'phone_code' => $data['phone_code'],
                'phone' => $data['phone'],
                'country_id' => $data['country_id'],
                'region_id' => $data['region_id'],
                'comune_id' => $data['comune_id'],
                'password' => Hash::make($data['password']),
                'status' => 'active', // Activo desde el registro
                'email_verified_at' => null, // Se verifica después por email
            ]);

            // Generar token de verificación
            $verificationToken = Str::random(64);

            // Guardar el token en cache por 24 horas
            cache()->put(
                'email_verification_' . $guardianUser->id,
                $verificationToken,
                now()->addHours(24)
            );

            // Enviar email de verificación de manera inmediata
            \Log::info('=== INICIO ENVÍO EMAIL VERIFICACIÓN ===');
            \Log::info('Guardian User ID: ' . $guardianUser->id);
            \Log::info('Email destino: ' . $guardianUser->email);
            \Log::info('Token generado: ' . $verificationToken);

            try {
                Mail::to($guardianUser->email)->sendNow(
                    new GuardianEmailVerification($guardianUser, $verificationToken)
                );
                \Log::info('Email enviado exitosamente');
            } catch (\Exception $e) {
                \Log::error('Error al enviar email de verificación: ' . $e->getMessage());
                \Log::error('Stack trace: ' . $e->getTraceAsString());
                throw $e;
            }

            \Log::info('=== FIN ENVÍO EMAIL VERIFICACIÓN ===');

            DB::commit();

            return [
                'success' => true,
                'message' => 'Registro exitoso. Por favor verifica tu email para activar tu cuenta.',
                'user' => $guardianUser,
                'requires_verification' => true
            ];

        } catch (\Exception $e) {
            DB::rollBack();

            return [
                'success' => false,
                'message' => 'Error al registrar usuario: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Validar invitación
     */
    private function validateInvitation(string $email, string $code): ?GuardianEmergencyContact
    {
        return GuardianEmergencyContact::with('emergencyContact')
            ->where('invitation_code', $code)
            ->where('invitation_status', 'pending')
            ->whereNull('guardian_user_id')
            ->where(function ($query) {
                $query->whereNull('invitation_expires_at')
                    ->orWhere('invitation_expires_at', '>', now());
            })
            ->first();
    }

    /**
     * Verificar email del usuario
     */
    public function verifyEmail(int $userId, string $token): array
    {
        try {
            // Verificar token en cache
            $cachedToken = cache()->get('email_verification_' . $userId);

            if (!$cachedToken || $cachedToken !== $token) {
                return [
                    'success' => false,
                    'message' => 'Token de verificación inválido o expirado.'
                ];
            }

            // Obtener el usuario
            $guardianUser = GuardianUser::find($userId);

            if (!$guardianUser) {
                return [
                    'success' => false,
                    'message' => 'Usuario no encontrado.'
                ];
            }

            // Marcar email como verificado
            $guardianUser->update([
                'email_verified_at' => now()
            ]);

            // Eliminar el token del cache
            cache()->forget('email_verification_' . $userId);

            return [
                'success' => true,
                'message' => '¡Email verificado exitosamente! Ya puedes iniciar sesión.',
                'user' => $guardianUser
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Error al verificar email: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Reenviar email de verificación
     */
    public function resendVerificationEmail(string $email): array
    {
        try {
            $guardianUser = GuardianUser::where('email', $email)->first();

            if (!$guardianUser) {
                return [
                    'success' => false,
                    'message' => 'Usuario no encontrado.'
                ];
            }

            if ($guardianUser->email_verified_at) {
                return [
                    'success' => false,
                    'message' => 'Este email ya está verificado.'
                ];
            }

            // Generar nuevo token
            $verificationToken = Str::random(64);

            cache()->put(
                'email_verification_' . $guardianUser->id,
                $verificationToken,
                now()->addHours(24)
            );

            // Reenviar email de manera inmediata
            \Log::info('=== INICIO REENVÍO EMAIL VERIFICACIÓN ===');
            \Log::info('Guardian User ID: ' . $guardianUser->id);
            \Log::info('Email destino: ' . $guardianUser->email);
            \Log::info('Nuevo token generado: ' . $verificationToken);

            try {
                Mail::to($guardianUser->email)->sendNow(
                    new GuardianEmailVerification($guardianUser, $verificationToken)
                );
                \Log::info('Email reenviado exitosamente');
            } catch (\Exception $e) {
                \Log::error('Error al reenviar email de verificación: ' . $e->getMessage());
                \Log::error('Stack trace: ' . $e->getTraceAsString());
                throw $e;
            }

            \Log::info('=== FIN REENVÍO EMAIL VERIFICACIÓN ===');

            return [
                'success' => true,
                'message' => 'Email de verificación reenviado exitosamente.'
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Error al reenviar email: ' . $e->getMessage()
            ];
        }
    }
}
