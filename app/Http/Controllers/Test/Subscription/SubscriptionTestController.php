<?php

namespace App\Http\Controllers\Test\Subscription;

use App\Http\Controllers\Controller;
use App\Services\Subscription\VirtualPosSubscriptionService;
use App\Models\ProgramSubscription;
use App\Models\Participant;
use App\Models\Program;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Controlador de prueba para testing de suscripciones VirtualPos
 */
class SubscriptionTestController extends Controller
{
    protected VirtualPosSubscriptionService $subscriptionService;

    public function __construct(VirtualPosSubscriptionService $subscriptionService)
    {
        $this->subscriptionService = $subscriptionService;
    }

    /**
     * Mapear estado de VirtualPos a estados válidos del ENUM
     */
    protected function mapSubscriptionStatus(?string $status): string
    {
        return match($status) {
            'ACTIVA', 'SUSCRIBIENDO', 'CANCELADA', 'FINALIZADA' => $status,
            'SUSCRIPCION_FALLIDA', 'NOK', 'ERROR' => 'SUSCRIPCION_FALLIDA',
            default => 'SUSCRIBIENDO'
        };
    }

    /**
     * Página principal de pruebas
     */
    public function index()
    {
        try {
            // Obtener suscripciones de VirtualPos API
            $vpResponse = $this->subscriptionService->listSubscriptions();

            // Sincronizar con base de datos local
            if (isset($vpResponse['suscriptions']) && is_array($vpResponse['suscriptions'])) {
                foreach ($vpResponse['suscriptions'] as $vpSubscription) {
                    $subscriptionId = $vpSubscription['id'] ?? null;

                    if (!$subscriptionId) continue;

                    // Buscar o crear suscripción local
                    $localSubscription = ProgramSubscription::firstOrCreate(
                        ['virtualpos_subscription_id' => $subscriptionId],
                        [
                            'status' => $this->mapSubscriptionStatus($vpSubscription['status'] ?? null),
                            'amount' => $vpSubscription['amount'] ?? 0,
                            'currency' => $vpSubscription['currency'] ?? 'CLP',
                            'virtualpos_plan_id' => $vpSubscription['plan_id'] ?? null,
                            'start_date' => isset($vpSubscription['suscription_date'])
                                ? \Carbon\Carbon::parse($vpSubscription['suscription_date'])
                                : now(),
                        ]
                    );

                    // Actualizar estado si cambió
                    if ($localSubscription->status !== $this->mapSubscriptionStatus($vpSubscription['status'] ?? null)) {
                        $localSubscription->update([
                            'status' => $this->mapSubscriptionStatus($vpSubscription['status'] ?? null),
                        ]);
                    }
                }
            }
        } catch (\Exception $e) {
            Log::error('Error al sincronizar suscripciones desde VirtualPos', [
                'error' => $e->getMessage()
            ]);
        }

        // Mostrar suscripciones locales ya sincronizadas
        $subscriptions = ProgramSubscription::with(['participant', 'program'])
            ->orderBy('created_at', 'desc')
            ->paginate(20); // Aumentar a 20 para ver más suscripciones

        return view('test.subscriptions.index', compact('subscriptions'));
    }

    /**
     * Página de gestión de planes
     */
    public function plansIndex(Request $request)
    {
        $plans = null;

        // Si se solicita listar planes
        if ($request->has('list_all')) {
            try {
                $plans = $this->subscriptionService->listPlans();
            } catch (\Exception $e) {
                return view('test.subscriptions.plans')->withErrors(['error' => 'Error al listar planes: ' . $e->getMessage()]);
            }
        }

        return view('test.subscriptions.plans', compact('plans'));
    }

    /**
     * Crear un plan de prueba
     */
    public function createPlan(Request $request)
    {
        try {
            $request->validate([
                'plan_id' => 'required|string',
                'plan_name' => 'required|string',
                'description' => 'required|string',
                'amount' => 'required|numeric|min:1',
                'currency' => 'required|in:CLP,UF',
                'periodicity' => 'required|in:DAILY,WEEKLY,MONTHLY,SEMESTRAL,YEARLY',
            ]);

            // Construir datos del plan
            $planData = $this->subscriptionService->buildPlanData([
                'plan_id' => $request->plan_id,
                'plan_name' => $request->plan_name,
                'description' => $request->description,
                'amount' => $request->amount,
                'currency' => $request->currency,
                'periodicity' => $request->periodicity,
                'trial_period_days' => $request->trial_period_days ?? 0,
                'charges_number' => $request->charges_number ?? 0,
                'fixed_amount_day_charge' => $request->fixed_amount_day_charge ?? '01',
            ]);

            // Crear plan en VirtualPos
            $response = $this->subscriptionService->createPlan($planData);

            return back()->with([
                'success' => 'Plan creado exitosamente',
                'plan_data' => $response
            ]);

        } catch (\Exception $e) {
            Log::error('Error al crear plan de prueba', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return back()
                ->withErrors(['error' => 'Error al crear plan: ' . $e->getMessage()])
                ->with('error_details', $e->getMessage());
        }
    }

    /**
     * Obtener información de un plan
     */
    public function getPlan(Request $request)
    {
        try {
            $request->validate([
                'plan_id' => 'required|string',
            ]);

            $response = $this->subscriptionService->getPlan($request->plan_id);

            return back()->with([
                'success' => 'Plan encontrado',
                'plan_data' => $response
            ]);

        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Error al obtener plan: ' . $e->getMessage()]);
        }
    }

    /**
     * Formulario para crear una suscripción de prueba
     */
    public function createForm()
    {
        try {
            // Obtener lista de planes disponibles desde VirtualPos
            $plans = $this->subscriptionService->listPlans();

            return view('test.subscriptions.create', compact('plans'));
        } catch (\Exception $e) {
            Log::warning('No se pudieron cargar planes para el formulario', [
                'error' => $e->getMessage()
            ]);

            // Si falla la carga de planes, mostrar el formulario sin planes (fallback)
            return view('test.subscriptions.create', ['plans' => null]);
        }
    }

    /**
     * Crear una suscripción de prueba con datos hardcodeados
     */
    public function create(Request $request)
    {
        try {
            $request->validate([
                'plan_id' => 'required|string',
                'amount' => 'required|numeric|min:0',
                'currency' => 'required|in:CLP,UF',
            ]);

            // Datos de prueba hardcodeados
            $testClient = [
                'email' => 'maria.gonzalez.test@gmail.com',
                'name' => 'María',
                'surname' => 'González',
                'rut' => '11111111-1',
                'phone' => '56987654321',
                'address' => [
                    'street' => 'Av. Libertador 456',
                    'city' => 'Santiago',
                    'country' => 'CL'
                ]
            ];

            // Construir datos de la suscripción
            $subscriptionData = $this->subscriptionService->buildSubscriptionData([
                'plan_id' => $request->plan_id,
                'service_id' => 'test_' . time(),
                'amount' => $request->amount,
                'currency' => $request->currency,
                'automatic_renewal' => 'T',
                'channel' => 'WEB',
                'client' => $testClient
            ]);

            Log::info('Creando suscripción de prueba', [
                'plan_id' => $request->plan_id,
                'amount' => $request->amount,
                'client_email' => $testClient['email']
            ]);

            // Crear suscripción en VirtualPos
            $response = $this->subscriptionService->createSubscription($subscriptionData);

            // Guardar en base de datos local (sin participant_id ni program_id)
            $subscription = ProgramSubscription::create([
                'participant_id' => null,
                'program_id' => null,
                'virtualpos_subscription_id' => $response['suscription']['id'] ?? $response['id'] ?? null,
                'virtualpos_plan_id' => $request->plan_id,
                'plan_name' => $response['suscription']['plan_name'] ?? $response['plan_name'] ?? 'Plan de prueba',
                'status' => $this->mapSubscriptionStatus($response['suscription']['status'] ?? $response['status'] ?? null),
                'amount' => $request->amount,
                'currency' => $request->currency,
                'automatic_renewal' => 'T',
                'subscription_date' => $response['suscription']['suscription_date'] ?? $response['suscription_date'] ?? now(),
                'channel' => 'WEB',
                'service_id' => $subscriptionData['service_id'],
                'payment_method' => $response['suscription']['payment_method'] ?? $response['payment_method'] ?? null,
                'charge_program' => $response['suscription']['charge_program'] ?? $response['charge_program'] ?? null,
                'client_data' => $testClient,
                'api_response' => $response,
            ]);

            Log::info('Suscripción guardada en BD, redirigiendo a VirtualPos', [
                'subscription_id' => $subscription->id,
                'url_redirect' => $response['url_redirect'] ?? null
            ]);

            // LOG DETALLADO: Datos enviados y respuesta recibida
            Log::info('=== DATOS ENVIADOS A VIRTUALPOS ===', [
                'subscription_data' => $subscriptionData
            ]);

            Log::info('=== RESPUESTA COMPLETA DE VIRTUALPOS ===', [
                'full_response' => $response
            ]);

            // Redirigir al usuario a VirtualPos para completar el pago
            if (!empty($response['url_redirect'])) {
                return redirect()->away($response['url_redirect']);
            }

            // Fallback: si no hay URL de redirect, ir a la página de detalles
            return redirect()
                ->route('test.subscriptions.show', $subscription->id)
                ->with('success', 'Suscripción creada exitosamente');

        } catch (\Exception $e) {
            Log::error('Error al crear suscripción de prueba', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return back()
                ->withInput()
                ->withErrors(['error' => 'Error al crear suscripción: ' . $e->getMessage()])
                ->with('error_details', $e->getMessage());
        }
    }

    /**
     * Ver detalles de una suscripción
     */
    public function show($id)
    {
        $subscription = ProgramSubscription::with(['participant', 'program'])->findOrFail($id);

        return view('test.subscriptions.show', compact('subscription'));
    }

    /**
     * Sincronizar estado de una suscripción con VirtualPos
     */
    public function sync($id)
    {
        try {
            $subscription = ProgramSubscription::findOrFail($id);

            if (!$subscription->virtualpos_subscription_id) {
                return back()->withErrors(['error' => 'Esta suscripción no tiene ID de VirtualPos']);
            }

            // Obtener datos actualizados de VirtualPos
            $response = $this->subscriptionService->getSubscription($subscription->virtualpos_subscription_id);

            // Actualizar datos locales
            $subscription->update([
                'status' => $this->mapSubscriptionStatus($response['status'] ?? $subscription->status),
                'payment_method' => $response['payment_method'] ?? $subscription->payment_method,
                'charge_program' => $response['charge_program'] ?? $subscription->charge_program,
                'api_response' => $response,
            ]);

            return back()->with('success', 'Suscripción sincronizada exitosamente');

        } catch (\Exception $e) {
            Log::error('Error al sincronizar suscripción', [
                'subscription_id' => $id,
                'error' => $e->getMessage()
            ]);

            return back()->withErrors(['error' => 'Error al sincronizar: ' . $e->getMessage()]);
        }
    }

    /**
     * Cancelar una suscripción
     */
    public function cancel($id)
    {
        try {
            $subscription = ProgramSubscription::findOrFail($id);

            if (!$subscription->virtualpos_subscription_id) {
                return back()->withErrors(['error' => 'Esta suscripción no tiene ID de VirtualPos']);
            }

            // Cancelar en VirtualPos
            $response = $this->subscriptionService->cancelSubscription($subscription->virtualpos_subscription_id);

            // Actualizar estado local
            $subscription->update([
                'status' => 'CANCELADA',
                'cancelled_at' => now(),
                'api_response' => $response,
            ]);

            return back()->with('success', 'Suscripción cancelada exitosamente');

        } catch (\Exception $e) {
            Log::error('Error al cancelar suscripción', [
                'subscription_id' => $id,
                'error' => $e->getMessage()
            ]);

            return back()->withErrors(['error' => 'Error al cancelar: ' . $e->getMessage()]);
        }
    }

    /**
     * Generar link de cambio de tarjeta
     */
    public function generateCardChangeLink($id)
    {
        try {
            $subscription = ProgramSubscription::findOrFail($id);

            if (!$subscription->virtualpos_subscription_id) {
                return back()->withErrors(['error' => 'Esta suscripción no tiene ID de VirtualPos']);
            }

            // Generar link en VirtualPos
            $response = $this->subscriptionService->generateCardChangeLink($subscription->virtualpos_subscription_id);

            // Guardar link
            $subscription->update([
                'card_change_link' => $response['link'] ?? null,
                'card_change_link_generated_at' => now(),
                'api_response' => $response,
            ]);

            return back()->with('success', 'Link de cambio de tarjeta generado exitosamente');

        } catch (\Exception $e) {
            Log::error('Error al generar link de cambio de tarjeta', [
                'subscription_id' => $id,
                'error' => $e->getMessage()
            ]);

            return back()->withErrors(['error' => 'Error al generar link: ' . $e->getMessage()]);
        }
    }

    /**
     * Listar todas las suscripciones de VirtualPos
     */
    public function listAll(Request $request)
    {
        try {
            $filters = $request->only(['status', 'page', 'limit']);
            $response = $this->subscriptionService->listSubscriptions($filters);

            return view('test.subscriptions.list-all', compact('response'));

        } catch (\Exception $e) {
            Log::error('Error al listar suscripciones', [
                'error' => $e->getMessage()
            ]);

            return back()->withErrors(['error' => 'Error al listar suscripciones: ' . $e->getMessage()]);
        }
    }

    /**
     * Cancelar todas las suscripciones activas en VirtualPos
     * Útil para limpiar datos de prueba y cache de usuarios
     */
    public function cancelAll()
    {
        try {
            Log::info('=== INICIANDO CANCELACIÓN MASIVA ===');

            // Obtener todas las suscripciones de VirtualPos
            $response = $this->subscriptionService->listSubscriptions();

            // LOG: Ver respuesta completa del listado
            Log::info('=== RESPUESTA DE listSubscriptions() ===', [
                'full_response' => $response
            ]);

            $cancelled = 0;
            $errors = 0;
            $skipped = 0;

            if (isset($response['suscriptions']) && is_array($response['suscriptions'])) {
                foreach ($response['suscriptions'] as $subscription) {
                    $subscriptionId = $subscription['id'] ?? null;
                    $status = $subscription['status'] ?? null;

                    Log::info("Procesando suscripción", [
                        'subscription_id' => $subscriptionId,
                        'status_actual' => $status,
                        'subscription_full_data' => $subscription
                    ]);

                    if (!$subscriptionId) {
                        continue;
                    }

                    // Solo cancelar suscripciones activas o en proceso
                    if (in_array($status, ['ACTIVA', 'SUSCRIBIENDO'])) {
                        try {
                            Log::info("=== CANCELANDO SUSCRIPCIÓN {$subscriptionId} ===", [
                                'status_antes' => $status,
                                'email' => $subscription['email'] ?? 'N/A'
                            ]);

                            $cancelResponse = $this->subscriptionService->cancelSubscription($subscriptionId);

                            // LOG: Ver respuesta literal de la API al cancelar
                            Log::info("=== RESPUESTA DE cancelSubscription({$subscriptionId}) ===", [
                                'full_cancel_response' => $cancelResponse
                            ]);

                            $cancelled++;

                            // Actualizar en BD local si existe
                            $localSubscription = ProgramSubscription::where('virtualpos_subscription_id', $subscriptionId)->first();
                            if ($localSubscription) {
                                $localSubscription->update([
                                    'status' => 'CANCELADA',
                                    'cancelled_at' => now(),
                                ]);
                                Log::info("Suscripción local actualizada", [
                                    'local_id' => $localSubscription->id
                                ]);
                            } else {
                                Log::warning("No se encontró suscripción local para {$subscriptionId}");
                            }

                        } catch (\Exception $e) {
                            Log::error("=== ERROR AL CANCELAR {$subscriptionId} ===", [
                                'error' => $e->getMessage(),
                                'trace' => $e->getTraceAsString()
                            ]);
                            $errors++;
                        }
                    } else {
                        Log::info("Suscripción {$subscriptionId} omitida (estado: {$status})");
                        $skipped++;
                    }
                }
            }

            Log::info('Cancelación masiva completada', [
                'cancelled' => $cancelled,
                'errors' => $errors,
                'skipped' => $skipped
            ]);

            $message = "Proceso completado: {$cancelled} suscripciones canceladas";
            if ($errors > 0) {
                $message .= ", {$errors} errores";
            }
            if ($skipped > 0) {
                $message .= ", {$skipped} omitidas (ya canceladas/finalizadas)";
            }

            return back()->with('success', $message);

        } catch (\Exception $e) {
            Log::error('Error al cancelar todas las suscripciones', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return back()->withErrors(['error' => 'Error al cancelar suscripciones: ' . $e->getMessage()]);
        }
    }

    /**
     * Webhook callback de VirtualPos
     * Recibe notificaciones sobre cambios en el estado de la suscripción
     */
    public function callback(Request $request)
    {
        try {
            Log::info('VirtualPos Webhook: Callback recibido', [
                'all_data' => $request->all(),
                'headers' => $request->headers->all()
            ]);

            // Obtener el ID de la suscripción del callback
            $subscriptionId = $request->input('id') ?? $request->input('subscription_id');

            if ($subscriptionId) {
                // Buscar la suscripción en nuestra base de datos
                $subscription = ProgramSubscription::where('virtualpos_subscription_id', $subscriptionId)->first();

                if ($subscription) {
                    // Actualizar el estado basado en los datos del callback
                    $subscription->update([
                        'status' => $this->mapSubscriptionStatus($request->input('status')),
                        'payment_method' => $request->input('payment_method') ?? $subscription->payment_method,
                        'api_response' => $request->all(),
                    ]);

                    Log::info('Suscripción actualizada desde callback', [
                        'subscription_id' => $subscription->id,
                        'new_status' => $subscription->status
                    ]);
                }
            }

            // VirtualPos espera una respuesta 200 OK
            return response()->json(['status' => 'OK', 'message' => 'Callback procesado'], 200);

        } catch (\Exception $e) {
            Log::error('Error al procesar callback de VirtualPos', [
                'error' => $e->getMessage(),
                'data' => $request->all()
            ]);

            return response()->json(['status' => 'ERROR', 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * URL de retorno después de completar el pago
     * El usuario es redirigido aquí después de pagar en VirtualPos
     */
    public function returnUrl(Request $request)
    {
        try {
            Log::info('VirtualPos: Usuario retornado después de pago', [
                'all_data' => $request->all()
            ]);

            // Intentar encontrar la suscripción por service_id o subscription_id
            $subscriptionId = $request->input('subscription_id') ?? $request->input('id');
            $serviceId = $request->input('service_id');

            $subscription = null;

            if ($subscriptionId) {
                $subscription = ProgramSubscription::where('virtualpos_subscription_id', $subscriptionId)->first();
            } elseif ($serviceId) {
                $subscription = ProgramSubscription::where('service_id', $serviceId)->first();
            }

            if ($subscription) {
                // Sincronizar con VirtualPos para obtener el estado más reciente
                try {
                    $response = $this->subscriptionService->getSubscription($subscription->virtualpos_subscription_id);

                    $subscription->update([
                        'status' => $this->mapSubscriptionStatus($response['status'] ?? $subscription->status),
                        'payment_method' => $response['payment_method'] ?? $subscription->payment_method,
                        'api_response' => $response,
                    ]);
                } catch (\Exception $e) {
                    Log::warning('No se pudo sincronizar después del retorno', [
                        'error' => $e->getMessage()
                    ]);
                }

                return redirect()
                    ->route('test.subscriptions.show', $subscription->id)
                    ->with('success', '¡Gracias! El proceso de suscripción ha sido completado.');
            }

            // Si no encontramos la suscripción, redirigir al índice
            return redirect()
                ->route('test.subscriptions.index')
                ->with('info', 'Proceso completado. Revisa el estado de tu suscripción en el listado.');

        } catch (\Exception $e) {
            Log::error('Error al procesar retorno de VirtualPos', [
                'error' => $e->getMessage(),
                'data' => $request->all()
            ]);

            return redirect()
                ->route('test.subscriptions.index')
                ->withErrors(['error' => 'Hubo un error al procesar el retorno: ' . $e->getMessage()]);
        }
    }

    /**
     * Página de configuración de pruebas
     */
    public function config()
    {
        $config = [
            'api_url' => config('services.virtualpos.api_url'),
            'api_key' => config('services.virtualpos.api_key') ? '********' . substr(config('services.virtualpos.api_key'), -4) : 'No configurado',
            'secret_key' => config('services.virtualpos.secret_key') ? '********' . substr(config('services.virtualpos.secret_key'), -4) : 'No configurado',
            'merchant_code' => config('services.virtualpos.merchant_code'),
        ];

        return view('test.subscriptions.config', compact('config'));
    }
}
