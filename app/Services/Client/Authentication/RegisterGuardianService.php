<?php

namespace App\Services\Client\Authentication;

use App\Models\GuardianEmergencyContact;
use App\Models\GuardianUser;
use App\Models\GuardianUserParticipant;
use App\Mail\GuardianEmailVerification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class RegisterGuardianService
{
    /**
     * Registrar un nuevo guardian user
     *
     * @param array $data Datos del guardian
     * @param int|null $participantId ID del participante a vincular (opcional)
     */
    public function register(array $data, ?int $participantId = null): array
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

            // Verificar si el RUT/documento ya está registrado para ese tipo
            if (GuardianUser::where('document_id', $data['document_type_id'])
                ->where('document', $data['document_number'])
                ->whereNull('deleted_at')
                ->exists()) {
                return [
                    'success' => false,
                    'message' => 'Este RUT/documento ya tiene una cuenta registrada. Si olvidaste tu contraseña, usa la opción de recuperación.'
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

            // Si viene un participante, crear la vinculacion
            if ($participantId) {
                GuardianUserParticipant::create([
                    'guardian_user_id' => $guardianUser->id,
                    'participant_id' => $participantId,
                    'can_pay' => true,
                ]);

                \Log::info('Guardian vinculado con participante', [
                    'guardian_user_id' => $guardianUser->id,
                    'participant_id' => $participantId
                ]);
            }

            // Generar token de verificación y guardarlo en BD
            $verificationToken = Str::random(64);
            $guardianUser->update([
                'email_verification_token' => $verificationToken,
                'email_verification_expires_at' => now()->addHours(48),
            ]);

            // Enviar email de verificación de manera inmediata (no bloquea el registro si falla)
            try {
                Mail::to($guardianUser->email)->sendNow(
                    new GuardianEmailVerification($guardianUser, $verificationToken)
                );
                \Log::info('Email de verificación enviado a: ' . $guardianUser->email);
            } catch (\Exception $e) {
                // Solo loguear el error, no romper el flujo de registro
                \Log::warning('No se pudo enviar email de verificación (el registro continúa): ' . $e->getMessage());
            }

            DB::commit();

            $requiresVerification = config('lat90.guardian.require_email_verification', true);

            return [
                'success' => true,
                'message' => $requiresVerification
                    ? '¡Registro exitoso! Revisa tu email para verificar tu cuenta.'
                    : '¡Registro exitoso! Ya puedes iniciar sesión.',
                'user' => $guardianUser,
                'requires_verification' => $requiresVerification
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
            $guardianUser = GuardianUser::find($userId);

            if (!$guardianUser) {
                return [
                    'success' => false,
                    'message' => 'Usuario no encontrado.'
                ];
            }

            // Verificar token en BD
            if (!$guardianUser->email_verification_token || $guardianUser->email_verification_token !== $token) {
                return [
                    'success' => false,
                    'message' => 'Token de verificación inválido.'
                ];
            }

            // Verificar expiración
            if ($guardianUser->email_verification_expires_at && $guardianUser->email_verification_expires_at->isPast()) {
                return [
                    'success' => false,
                    'message' => 'El enlace de verificación ha expirado. Solicita uno nuevo.',
                    'expired' => true,
                    'email' => $guardianUser->email
                ];
            }

            // Marcar email como verificado y limpiar token
            $guardianUser->update([
                'email_verified_at' => now(),
                'email_verification_token' => null,
                'email_verification_expires_at' => null,
            ]);

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

            // Generar nuevo token y guardarlo en BD
            $verificationToken = Str::random(64);
            $guardianUser->update([
                'email_verification_token' => $verificationToken,
                'email_verification_expires_at' => now()->addHours(48),
            ]);

            // Reenviar email
            try {
                Mail::to($guardianUser->email)->sendNow(
                    new GuardianEmailVerification($guardianUser, $verificationToken)
                );
                \Log::info('Email de verificación reenviado a: ' . $guardianUser->email);

                return [
                    'success' => true,
                    'message' => 'Email de verificación reenviado exitosamente.'
                ];
            } catch (\Exception $e) {
                \Log::warning('No se pudo reenviar email de verificación: ' . $e->getMessage());

                return [
                    'success' => false,
                    'message' => 'No se pudo enviar el email en este momento. Por favor intenta más tarde.'
                ];
            }

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Error al reenviar email: ' . $e->getMessage()
            ];
        }
    }
}
