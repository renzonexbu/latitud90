<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Models\Program;
use App\Models\Participant;
use Exception;

class SubscriptionController extends Controller
{
    /**
     * Iniciar proceso de suscripción para pago mensual
     * Esta ruta solo es accesible si el guardian está autenticado
     */
    public function initiate(Request $request): JsonResponse
    {
        try {
            $guardian = auth('guardian')->user();

            if (!$guardian) {
                return response()->json([
                    'success' => false,
                    'error' => 'No estás autenticado como apoderado',
                    'redirect' => route('guardian.register')
                ], 401);
            }

            $request->validate([
                'program_id' => 'required|exists:programs,id',
                'document' => 'required|string',
                'document_type' => 'required|string',
                'installments' => 'required|integer|min:1'
            ]);

            $programId = $request->input('program_id');
            $document = $request->input('document');
            $documentType = $request->input('document_type');
            $installments = $request->input('installments');

            // Verificar que el programa existe
            $program = Program::findOrFail($programId);

            // Buscar participante
            $participant = Participant::where('document', $document)
                ->where('document_type', $documentType)
                ->first();

            if (!$participant) {
                return response()->json([
                    'success' => false,
                    'error' => 'Participante no encontrado. Verifica el RUT ingresado.'
                ], 404);
            }

            // TODO: Aquí se integrará con VirtualPOS para crear la suscripción
            // Por ahora, guardamos la información en sesión para el siguiente paso

            session()->put('pending_subscription', [
                'guardian_id' => $guardian->id,
                'program_id' => $programId,
                'participant_id' => $participant->id,
                'document' => $document,
                'document_type' => $documentType,
                'installments' => $installments,
                'timestamp' => now()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Suscripción iniciada correctamente',
                'data' => [
                    'guardian' => [
                        'id' => $guardian->id,
                        'name' => $guardian->name,
                        'email' => $guardian->email
                    ],
                    'program' => [
                        'id' => $program->id,
                        'name' => $program->name,
                        'price' => $program->price
                    ],
                    'participant' => [
                        'id' => $participant->id,
                        'name' => $participant->name,
                        'document' => $participant->document
                    ],
                    'installments' => $installments,
                    'next_step' => route('subscription.details', ['programId' => $programId])
                ]
            ]);

        } catch (Exception $e) {
            Log::error('Error al iniciar suscripción: ' . $e->getMessage(), [
                'request' => $request->all(),
                'guardian_id' => auth('guardian')->id(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Error al iniciar la suscripción: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mostrar detalles de suscripción antes de confirmar
     */
    public function showDetails(Request $request, int $programId)
    {
        try {
            $guardian = auth('guardian')->user();
            $pendingSubscription = session('pending_subscription');

            if (!$pendingSubscription) {
                return redirect()->route('ecommerce.programs')
                    ->with('error', 'No hay una suscripción pendiente. Por favor, inicia el proceso nuevamente.');
            }

            $program = Program::with(['category', 'subcategory'])->findOrFail($programId);
            $participant = Participant::findOrFail($pendingSubscription['participant_id']);

            return inertia('Subscription/Details', [
                'guardian' => $guardian,
                'program' => $program,
                'participant' => $participant,
                'subscription' => $pendingSubscription
            ]);

        } catch (Exception $e) {
            Log::error('Error al mostrar detalles de suscripción: ' . $e->getMessage());

            return redirect()->route('ecommerce.programs')
                ->with('error', 'Error al cargar los detalles de la suscripción.');
        }
    }

    /**
     * Procesar suscripción y redirigir a pasarela de pago
     * TODO: Integrar con VirtualPOS Subscriptions API
     */
    public function process(Request $request): JsonResponse
    {
        try {
            $guardian = auth('guardian')->user();
            $pendingSubscription = session('pending_subscription');

            if (!$pendingSubscription) {
                return response()->json([
                    'success' => false,
                    'error' => 'No hay una suscripción pendiente'
                ], 400);
            }

            // TODO: Implementar integración con VirtualPOS
            // 1. Crear suscripción en VirtualPOS
            // 2. Obtener URL de pago
            // 3. Redirigir al usuario

            // Por ahora, retornamos un mensaje indicando que está en desarrollo
            return response()->json([
                'success' => false,
                'error' => 'La integración con VirtualPOS está en desarrollo. Contacta al administrador.',
                'info' => 'Este es el paso donde se crearía la suscripción en VirtualPOS y se redirigiría al pago.'
            ], 501); // 501 Not Implemented

        } catch (Exception $e) {
            Log::error('Error al procesar suscripción: ' . $e->getMessage(), [
                'guardian_id' => auth('guardian')->id(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Error al procesar la suscripción'
            ], 500);
        }
    }

    /**
     * Listar suscripciones del guardian
     */
    public function mySubscriptions(Request $request)
    {
        try {
            $guardian = auth('guardian')->user();

            // TODO: Implementar listado de suscripciones desde la base de datos
            // cuando se tenga la tabla de subscriptions

            return inertia('Subscription/MySubscriptions', [
                'guardian' => $guardian,
                'subscriptions' => [] // Por ahora vacío
            ]);

        } catch (Exception $e) {
            Log::error('Error al listar suscripciones: ' . $e->getMessage());

            return redirect()->back()
                ->with('error', 'Error al cargar las suscripciones.');
        }
    }

    /**
     * Cancelar una suscripción
     * TODO: Integrar con VirtualPOS para cancelar suscripción
     */
    public function cancel(Request $request, int $subscriptionId): JsonResponse
    {
        try {
            $guardian = auth('guardian')->user();

            // TODO: Implementar cancelación de suscripción en VirtualPOS

            return response()->json([
                'success' => false,
                'error' => 'La cancelación de suscripciones está en desarrollo.'
            ], 501);

        } catch (Exception $e) {
            Log::error('Error al cancelar suscripción: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'error' => 'Error al cancelar la suscripción'
            ], 500);
        }
    }

    /**
     * Ver detalles de una suscripción específica
     */
    public function show(Request $request, int $subscriptionId)
    {
        try {
            $guardian = auth('guardian')->user();

            // TODO: Implementar cuando se tenga la tabla de subscriptions

            return inertia('Subscription/Show', [
                'guardian' => $guardian,
                'subscription' => null // Por ahora null
            ]);

        } catch (Exception $e) {
            Log::error('Error al mostrar suscripción: ' . $e->getMessage());

            return redirect()->back()
                ->with('error', 'Error al cargar la suscripción.');
        }
    }

    /**
     * Página de éxito después de crear suscripción
     */
    public function success(Request $request, int $subscriptionId)
    {
        try {
            // TODO: Cargar suscripción desde BD cuando esté implementado

            return inertia('Subscription/Success', [
                'subscription_id' => $subscriptionId
            ]);

        } catch (Exception $e) {
            Log::error('Error en página de éxito: ' . $e->getMessage());

            return redirect()->route('ecommerce.programs');
        }
    }

    /**
     * Página de fallo al crear suscripción
     */
    public function failure(Request $request, int $subscriptionId)
    {
        try {
            // TODO: Cargar información del error

            return inertia('Subscription/Failure', [
                'subscription_id' => $subscriptionId,
                'error' => $request->query('error', 'Error desconocido')
            ]);

        } catch (Exception $e) {
            Log::error('Error en página de fallo: ' . $e->getMessage());

            return redirect()->route('ecommerce.programs');
        }
    }

    /**
     * Webhook para notificaciones de VirtualPOS
     * Se llama cuando hay un cargo automático de suscripción
     */
    public function webhook(Request $request): JsonResponse
    {
        try {
            // TODO: Implementar validación de webhook de VirtualPOS
            // TODO: Verificar firma/token de seguridad
            // TODO: Procesar cargo de suscripción
            // TODO: Actualizar estado en BD
            // TODO: Enviar notificaciones al guardian

            Log::info('Webhook de suscripción recibido', [
                'payload' => $request->all()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Webhook recibido'
            ]);

        } catch (Exception $e) {
            Log::error('Error en webhook de suscripción: ' . $e->getMessage(), [
                'payload' => $request->all(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Error al procesar webhook'
            ], 500);
        }
    }
}
