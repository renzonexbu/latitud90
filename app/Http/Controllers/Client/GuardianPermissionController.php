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

            // Verificar que el guardian esté vinculado al participante
            if (!$guardian->canPayFor($participant->id)) {
                Log::warning('Guardian sin permiso para pagar por participante', [
                    'guardian_id' => $guardian->id,
                    'guardian_email' => $guardian->email,
                    'participant_id' => $participant->id,
                    'participant_document' => $participant->document_number,
                ]);

                return response()->json([
                    'has_permission' => false,
                    'reason' => 'guardian_not_linked',
                    'guardian_email' => $guardian->email
                ]);
            }

            Log::info('Guardian autorizado para pagar por participante', [
                'guardian_id' => $guardian->id,
                'guardian_email' => $guardian->email,
                'participant_id' => $participant->id,
                'participant_document' => $participant->document_number,
            ]);

            return response()->json([
                'has_permission' => true,
                'reason' => 'guardian_linked',
                'guardian_email' => $guardian->email
            ]);

        } catch (\Exception $e) {
            Log::error('Error validando permisos de guardian', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'has_permission' => false,
                'reason' => 'error_occurred'
            ], 500);
        }
    }
}
