<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\Participant;

class GuardianPermissionController extends Controller
{
    /**
     * Valida si el guardian logeado tiene permiso para pagar por un participante
     */
    public function validatePaymentPermission(Request $request)
    {
        try {
            $request->validate([
                'participant_document' => 'required|string'
            ]);

            $participantDocument = $request->input('participant_document');

            // Limpiar el documento (quitar puntos y guiones)
            $cleanDocument = preg_replace('/[.-]/', '', $participantDocument);

            // Buscar participante
            $participant = Participant::where('document_number', $cleanDocument)->first();

            if (!$participant) {
                return response()->json([
                    'has_permission' => false,
                    'reason' => 'participant_not_found'
                ]);
            }

            // Verificar si hay un guardian logeado
            if (!auth('guardian')->check()) {
                // No hay guardian logeado, permitir continuar (flujo anónimo)
                return response()->json([
                    'has_permission' => true,
                    'reason' => 'no_guardian_logged_in'
                ]);
            }

            $guardian = auth('guardian')->user();

            // VALIDACIÓN SIMPLIFICADA: Solo importa que haya un guardian logeado
            // No importa si es o no el apoderado del participante

            // LOG INFORMATIVO: Registrar quién está haciendo el pago
            Log::info('✅ Guardian logeado procesando pago de suscripción', [
                'guardian_id' => $guardian->id,
                'guardian_email' => $guardian->email,
                'guardian_nombre' => $guardian->name,
                'guardian_documento' => $guardian->document,
                'participante_id' => $participant->id,
                'participante_documento' => $participant->document_number,
                'participante_nombre' => $participant->full_name,
                'timestamp' => now()->toDateTimeString()
            ]);

            return response()->json([
                'has_permission' => true,
                'reason' => 'guardian_logged_in',
                'guardian_email' => $guardian->email
            ]);

        } catch (\Exception $e) {
            Log::error('Error validando permisos de guardian', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            // En caso de error, permitir continuar (fail-open)
            return response()->json([
                'has_permission' => true,
                'reason' => 'error_occurred'
            ]);
        }
    }
}
