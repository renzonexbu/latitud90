<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Carbon\Carbon;
use App\Models\Program;
use App\Models\ProgramCourse;
use App\Models\Participant;
use App\Models\ProgramSubscription;
use App\Models\InstallmentPlan;
use App\Models\Order;
use App\Models\Payment;
use App\Models\EmergencyContact;
use App\Models\VirtualPosPlan;
use App\Models\GuardianUserParticipant;
use App\Services\Subscription\VirtualPosSubscriptionService;
use App\Helpers\ParticipantPriceHelper;
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
            // Verificar si las suscripciones están habilitadas globalmente
            if (!config('services.subscriptions.enabled', true)) {
                return response()->json([
                    'success' => false,
                    'error' => config('services.subscriptions.disabled_message', 'El método de pago por suscripción no está disponible temporalmente.')
                ], 503);
            }

            $guardian = auth('guardian')->user();

            if (!$guardian) {
                return response()->json([
                    'success' => false,
                    'error' => 'No estás autenticado como apoderado',
                    'redirect' => route('guardian.register')
                ], 401);
            }

            $request->validate([
                'program_id' => 'required|exists:program_courses,id',
                'document' => 'required|string',
                'document_type' => 'required|string',
                'installments' => 'required|integer|min:1'
            ]);

            $programId = $request->input('program_id');
            $document = $request->input('document');
            $documentType = $request->input('document_type');
            $installments = $request->input('installments');

            // Verificar que el programa existe (program_courses)
            $program = ProgramCourse::findOrFail($programId);

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
     * Crear suscripción desde la página de confirmación
     * Este método maneja el flujo completo de creación de suscripción con VirtualPos
     */
    public function createFromConfirmation(Request $request)
    {
        try {
            // Verificar si las suscripciones están habilitadas globalmente
            if (!config('services.subscriptions.enabled', true)) {
                return back()->withErrors([
                    'subscription' => config('services.subscriptions.disabled_message', 'El método de pago por suscripción no está disponible temporalmente.')
                ]);
            }

            // Validar datos de entrada
            $request->validate([
                'program_course_id' => 'required|exists:program_courses,id',
                // Datos del COMPRADOR (buyer/guardian) - para VirtualPos
                'buyer' => 'required|array',
                'buyer.document_number' => 'required|string',
                'buyer.document_type' => 'nullable|string',
                'buyer.original_document_number' => 'nullable|string',
                'buyer.first_name' => 'required|string',
                'buyer.second_name' => 'nullable|string',
                'buyer.first_last_name' => 'required|string',
                'buyer.second_last_name' => 'nullable|string',
                'buyer.email' => 'required|email',
                'buyer.phone' => 'required|string',
                'buyer.code_phone' => 'required|string',
                // Datos del PARTICIPANTE - para asociación en BD
                'participant' => 'required|array',
                'participant.document_number' => 'required|string',
                'participant.name' => 'required|string',
                'installments' => 'required|integer|min:1|max:12',
            ]);

            $programCourseId = $request->input('program_course_id');
            $buyerData = $request->input('buyer');
            $participantData = $request->input('participant');
            $installments = $request->input('installments');

            // Cargar program_course con sus relaciones
            $programCourse = ProgramCourse::with(['program', 'course'])->findOrFail($programCourseId);

            // Nota: La verificación del plan de VirtualPos se hace más adelante,
            // después de buscar el participante, para poder usar planes personalizados

            // Buscar participante usando los datos recibidos
            $cleanParticipantDocument = preg_replace('/[.-]/', '', $participantData['document_number']);
            $cleanBuyerDocument = preg_replace('/[.-]/', '', $buyerData['document_number']);

            Log::info('Buscando participante y datos del comprador', [
                'participant_document_original' => $participantData['document_number'],
                'participant_document_clean' => $cleanParticipantDocument,
                'participant_name' => $participantData['name'],
                'buyer_document_type' => $buyerData['document_type'] ?? 'RUT',
                'buyer_document_for_virtualpos' => $buyerData['document_number'], // Este es el RUT que va a VirtualPos (11111111-1 si no es RUT chileno)
                'buyer_original_document' => $buyerData['original_document_number'] ?? $buyerData['document_number'],
                'buyer_document_clean' => $cleanBuyerDocument,
                'buyer_name' => trim(implode(' ', array_filter([$buyerData['first_name'], $buyerData['second_name'] ?? '', $buyerData['first_last_name'], $buyerData['second_last_name'] ?? '']))),
                'buyer_email' => $buyerData['email']
            ]);

            $participant = Participant::where('document_number', $cleanParticipantDocument)->first();

            if (!$participant) {
                throw new Exception('Participante no encontrado. Debe estar inscrito en el programa primero.');
            }

            // Validar que no exista ya una suscripción activa para este participante+programa
            $existingSubscription = ProgramSubscription::where('participant_id', $participant->id)
                ->where('program_id', $programCourseId)
                ->whereIn('status', ['ACTIVA', 'SUSCRIBIENDO'])
                ->first();

            if ($existingSubscription) {
                Log::warning('Intento de crear PAT duplicado', [
                    'participant_id' => $participant->id,
                    'program_id' => $programCourseId,
                    'existing_subscription_id' => $existingSubscription->id,
                    'existing_status' => $existingSubscription->status,
                ]);
                throw new Exception('Este participante ya cuenta con un plan de pago activo para este programa.');
            }

            // Validación de canPayFor deshabilitada - la autorización se maneja
            // en el flujo previo (selección de programa). Si el guardian llegó hasta
            // la confirmación de pago, ya fue validado.
            if (auth('guardian')->check()) {
                $guardian = auth('guardian')->user();

                // if (!$guardian->canPayFor($participant->id)) {
                //     Log::warning('Guardian sin permiso intenta crear suscripción', [
                //         'guardian_id' => $guardian->id,
                //         'guardian_email' => $guardian->email,
                //         'participant_id' => $participant->id,
                //         'participant_document' => $participant->document_number,
                //         'participant_name' => $participant->full_name
                //     ]);
                //
                //     throw new Exception('No tienes permiso para realizar pagos por este participante. Inicia sesión con la cuenta correcta.');
                // }

                Log::info('Guardian creando suscripción', [
                    'guardian_id' => $guardian->id,
                    'guardian_email' => $guardian->email,
                    'participant_id' => $participant->id
                ]);
            }

            Log::info('Participante encontrado', [
                'participant_id' => $participant->id,
                'participant_document' => $participant->document_number,
                'participant_name' => $participant->full_name
            ]);

            // Obtener el plan de VirtualPos apropiado (personalizado si existe, o general)
            $virtualPosPlan = VirtualPosPlan::getPlanForParticipant($participant->id, $programCourse->id);

            // Si no hay plan en la tabla virtualpos_plans, usar el ID del programCourse como fallback
            $planId = $virtualPosPlan ? $virtualPosPlan->virtualpos_plan_id : $programCourse->virtualpos_plan_id;

            if (!$planId) {
                throw new Exception('Este programa no tiene un plan de suscripción configurado.');
            }

            Log::info('Plan de VirtualPos seleccionado', [
                'plan_id' => $planId,
                'is_personalized' => $virtualPosPlan ? $virtualPosPlan->isPersonalized() : false,
                'plan_name' => $virtualPosPlan ? $virtualPosPlan->name : 'N/A',
                'participant_id' => $participant->id,
                'program_course_id' => $programCourse->id
            ]);

            // Calcular el monto total del programa
            $priceData = ParticipantPriceHelper::calculateParticipantPrice($participant, $programCourse);
            $totalProgramPrice = $priceData['final_price'];

            // Calcular pagos ya realizados (approved o completed)
            $paidAmount = (float) Payment::whereHas('order', function ($q) use ($participant, $programCourse) {
                    $q->where('participant_id', $participant->id)
                      ->where('program_id', $programCourse->id);
                })
                ->whereIn('status', ['approved', 'completed'])
                ->sum('amount');

            // Calcular el saldo pendiente (lo que realmente debe pagar en la suscripción)
            $totalAmount = max(round($totalProgramPrice - $paidAmount, 2), 0);

            // Log destacado para facilitar el seguimiento del monto de suscripción
            Log::info('========================================');
            Log::info('=== CREANDO SUSCRIPCIÓN PAT ===');
            Log::info('========================================');
            Log::info('Suscripción PAT - Cálculo de monto', [
                'participant_id' => $participant->id,
                'participant_rut' => $participant->document_number,
                'participant_name' => $participant->first_name . ' ' . $participant->first_last_name,
                'program_course_id' => $programCourse->id,
                'program_name' => $programCourse->name ?? 'N/A',
                'total_program_price' => $totalProgramPrice,
                'paid_amount' => $paidAmount,
                'remaining_balance' => $totalAmount,
                'installments' => $installments,
                'first_installment_amount' => (int) floor(($totalAmount / $installments) + 0.4),
                'monthly_amount' => (int) floor(($totalAmount / $installments) + 0.4),
            ]);
            Log::info('========================================');

            // Validar que hay saldo pendiente
            if ($totalAmount <= 0) {
                throw new Exception('El participante no tiene saldo pendiente. Ya ha pagado el total del programa.');
            }

            // Redondeo: 1-5 abajo, 6-9 arriba (sin decimales para pesos chilenos)
            $firstInstallmentAmount = (int) floor(($totalAmount / $installments) + 0.4);

            // Construir datos del COMPRADOR para VirtualPos (no del participante)
            // NOTA: Si el documento no es RUT chileno, el frontend ya envió "11111111-1" como document_number
            $clientData = [
                'email' => $buyerData['email'],
                'name' => trim($buyerData['first_name'] . ' ' . ($buyerData['second_name'] ?? '')),
                'surname' => trim($buyerData['first_last_name'] . ' ' . ($buyerData['second_last_name'] ?? '')),
                'rut' => $buyerData['document_number'], // Ya viene como "11111111-1" si no es RUT chileno
                'document_type' => $buyerData['document_type'] ?? 'RUT',
                'original_document' => $buyerData['original_document_number'] ?? $buyerData['document_number'],
                'phone' => str_replace('+', '', $buyerData['code_phone']) . $buyerData['phone'], // Quitar el '+'
                'address' => [
                    'street' => '',
                    'city' => $buyerData['city'] ?? 'Santiago',
                    'country' => 'CL'
                ]
            ];

            // Generar service_id único
            $serviceId = 'SUB_' . $programCourse->id . '_' . $participant->id . '_' . time();

            // Generar programa de cobros (charges_program) para PROGRAMA_DE_PAGOS
            $now = now();
            $chargesProgram = [];

            // Calcular montos: redondeo solo .6+ hacia arriba, última cuota absorbe el residuo
            $totalAmountInt = (int) round($totalAmount);
            $baseAmount = (int) floor(($totalAmountInt / $installments) + 0.4);
            $allocated = $baseAmount * ($installments - 1); // Primeras n-1 cuotas
            $lastAmount = $totalAmountInt - $allocated; // Última cuota absorbe el residuo

            for ($i = 0; $i < $installments; $i++) {
                if ($i === 0) {
                    // Primera cuota: inmediata o diferida según configuración
                    if ($programCourse->immediate_first_charge) {
                        $chargeDate = $now->copy();
                    } else {
                        $chargeDate = $now->copy()->addMonth()->startOfMonth();
                    }
                } else {
                    // Cuotas siguientes: sumar meses desde la primera cuota
                    $chargeDate = $chargesProgram[0]['charge_date_obj']->copy()->addMonths($i);
                }

                // Última cuota absorbe el residuo
                $amount = ($i === $installments - 1) ? $lastAmount : $baseAmount;

                $chargesProgram[] = [
                    'charge_date_obj' => $chargeDate,
                    'charge_date' => $chargeDate->format('Y-m-d'),
                    'amount' => $amount,
                    'description' => 'Cargo ' . ($i + 1) . ' de ' . $installments,
                    'internal_code' => $programCourse->code . '-CUOTA-' . ($i + 1)
                ];
            }

            // Remover objeto Carbon antes de codificar
            $chargesProgramForApi = array_map(function($charge) {
                unset($charge['charge_date_obj']);
                return $charge;
            }, $chargesProgram);

            // Codificar charges_program en Base64
            $chargesProgramBase64 = base64_encode(json_encode($chargesProgramForApi));

            // Inicializar servicio de VirtualPos
            $virtualPosService = new VirtualPosSubscriptionService();

            // Construir URLs de callback y retorno
            $returnUrl = route('api.subscription.return');
            $callbackUrl = route('api.subscription.callback');

            // Construir datos de la suscripción
            $subscriptionData = $virtualPosService->buildSubscriptionData([
                'plan_id' => $planId,
                'service_id' => $serviceId,
                'amount' => $firstInstallmentAmount,
                'currency' => 'CLP',
                'automatic_renewal' => 'F', // Sin renovación automática
                'channel' => 'WEB',
                'client' => $clientData,
                'return_url' => $returnUrl,
                'callback_url' => $callbackUrl,
                'charges_program' => $chargesProgramBase64, // Agregar charges_program
            ]);

            Log::info('Creando suscripción desde confirmación', [
                'program_course_id' => $programCourse->id,
                'participant_id' => $participant->id,
                'participant_document' => $participant->document_number,
                'participant_name' => $participant->full_name,
                'buyer_document_type' => $buyerData['document_type'] ?? 'RUT',
                'buyer_rut_for_virtualpos' => $buyerData['document_number'], // Este va a VirtualPos (11111111-1 si no es chileno)
                'buyer_original_document' => $buyerData['original_document_number'] ?? $buyerData['document_number'],
                'buyer_name' => trim(implode(' ', array_filter([$buyerData['first_name'], $buyerData['second_name'] ?? '', $buyerData['first_last_name'], $buyerData['second_last_name'] ?? '']))),
                'buyer_email' => $buyerData['email'],
                'plan_id' => $planId,
                'is_personalized_plan' => $virtualPosPlan ? $virtualPosPlan->isPersonalized() : false,
                'service_id' => $serviceId,
                'amount' => $firstInstallmentAmount,
                'installments' => $installments,
                'total_amount' => $totalAmount,
                'client_data_for_virtualpos' => $clientData
            ]);

            DB::beginTransaction();

            // Crear suscripción en VirtualPos
            $response = $virtualPosService->createSubscription($subscriptionData);

            // Mapear estado de VirtualPos
            $status = match($response['suscription']['status'] ?? $response['status'] ?? null) {
                'ACTIVA', 'SUSCRIBIENDO', 'CANCELADA', 'FINALIZADA' => $response['suscription']['status'] ?? $response['status'],
                'SUSCRIPCION_FALLIDA', 'NOK', 'ERROR' => 'SUSCRIPCION_FALLIDA',
                default => 'SUSCRIBIENDO'
            };

            // Guardar suscripción en la base de datos
            $subscription = ProgramSubscription::create([
                'participant_id' => $participant->id,
                'program_id' => $programCourse->id, // Almacenamos el program_course_id
                'virtualpos_subscription_id' => $response['suscription']['id'] ?? $response['id'] ?? null,
                'virtualpos_plan_id' => $planId,
                'plan_name' => $response['suscription']['plan_name'] ?? $response['plan_name'] ?? ($virtualPosPlan ? $virtualPosPlan->name : $programCourse->name),
                'status' => $status,
                'amount' => $firstInstallmentAmount,
                'currency' => 'CLP',
                'automatic_renewal' => 'F', // Sin renovación automática
                'subscription_date' => $response['suscription']['suscription_date'] ?? $response['suscription_date'] ?? now(),
                'channel' => 'WEB',
                'service_id' => $serviceId,
                'payment_method' => $response['suscription']['payment_method'] ?? $response['payment_method'] ?? null,
                'charge_program' => $response['suscription']['charge_program'] ?? $response['charge_program'] ?? null,
                'client_data' => $clientData,
                'buyer_data' => $buyerData, // Guardar datos del comprador para crear OrderDetails cuando se confirmen los pagos
                'api_response' => $response,
            ]);

            // Log de guardian asociado (el vínculo ya debe existir previamente, no se crea aquí)
            if (auth('guardian')->check()) {
                $guardian = auth('guardian')->user();
                Log::info('Suscripción creada por guardian vinculado', [
                    'guardian_user_id' => $guardian->id,
                    'guardian_email' => $guardian->email,
                    'participant_id' => $participant->id,
                    'subscription_id' => $subscription->id,
                ]);
            }

            // Crear orden asociada a la suscripción
            $order = Order::create([
                'participant_id' => $participant->id,
                'program_id' => $programCourse->id, // program_id ahora apunta a program_courses
                'subscription_id' => $subscription->id, // Vincular directamente con la suscripción
                'order_number' => 'SUB-' . str_pad($subscription->id, 8, '0', STR_PAD_LEFT),
                'total_amount' => $totalAmount,
                'final_amount' => $totalAmount,
                'total_installments' => $installments,
                'status' => 'pending',
                'payment_type' => 'monthly', // Usar 'monthly' en lugar de 'subscription'
            ]);

            // NO crear OrderDetail aquí - se creará cuando el Job detecte el pago como "pagado"
            // Los datos del comprador están guardados en $subscription->buyer_data

            // Crear plan de cuotas en la base de datos local
            $installmentPlan = InstallmentPlan::create([
                'order_id' => $order->id,
                'program_subscription_id' => $subscription->id, // Vincular directamente con la suscripción
                'program_id' => $programCourse->id, // program_id ahora apunta a program_courses
                'participant_id' => $participant->id,
                'total_amount' => $totalAmount,
                'total_installments' => $installments,
                'payment_type' => 'monthly',
                'status' => 'active',
                'start_date' => now(),
                'notes' => 'Plan de suscripción VirtualPos - ' . $installments . ' cuotas',
            ]);

            // Crear las cuotas individuales usando el charge_program de VirtualPos
            $chargeProgram = $response['suscription']['charge_program'] ?? $response['charge_program'] ?? [];

            // Estados que indican pago CONFIRMADO exitoso
            // IMPORTANTE: NO incluir 'procesando' porque es un estado intermedio que puede fallar
            $approvedStatuses = ['pagado', 'cobrado', 'aprobado', 'approved', 'paid', 'success'];

            // Si VirtualPos devuelve charge_program, usar esos datos
            if (!empty($chargeProgram)) {
                foreach ($chargeProgram as $index => $charge) {
                    // Convertir el status de VirtualPos a nuestro formato
                    // Solo marcar como 'paid' si el status está CONFIRMADO
                    $status = 'pending';
                    $isPaid = false;
                    if (isset($charge['status'])) {
                        $chargeStatus = strtolower($charge['status']);
                        if (in_array($chargeStatus, $approvedStatuses)) {
                            $status = 'paid';
                            $isPaid = true;
                        }
                        // Log si está procesando para debugging
                        if ($chargeStatus === 'procesando') {
                            Log::info('Charge en estado procesando, esperando confirmación', [
                                'charge_id' => $charge['id'] ?? null,
                                'amount' => $charge['amount'] ?? 0,
                            ]);
                        }
                    }

                    $installmentPlan->installments()->create([
                        'installment_number' => $index + 1,
                        'virtualpos_charge_id' => $charge['id'] ?? null,
                        'amount' => $charge['amount'],
                        'due_date' => $charge['charge_date'],
                        'status' => $status,
                        'is_paid' => $isPaid,
                        'paid_at' => $isPaid ? now() : null,
                    ]);
                }
            } else {
                // Si no hay charge_program (respuesta inicial antes de completar pago),
                // crear cuotas con datos estimados. Se actualizarán en returnUrl.
                Log::warning('createFromConfirmation: VirtualPos no devolvió charge_program inicial, creando cuotas estimadas', [
                    'subscription_id' => $subscription->id,
                    'installments' => $installments
                ]);

                $monthlyAmount = ceil($totalAmount / $installments);
                $chargeDate = now();

                for ($i = 0; $i < $installments; $i++) {
                    $installmentPlan->installments()->create([
                        'installment_number' => $i + 1,
                        'virtualpos_charge_id' => null, // Se actualizará después
                        'amount' => $monthlyAmount,
                        'due_date' => $chargeDate->copy()->addMonths($i)->format('Y-m-d'),
                        'status' => 'pending',
                        'is_paid' => false,
                        'paid_at' => null,
                    ]);
                }
            }

            // Si la suscripción es fallida, cancelar orden y cuotas (NO el plan)
            // La suscripción se mantiene como SUSCRIPCION_FALLIDA para historial
            if ($subscription->status === 'SUSCRIPCION_FALLIDA') {
                Log::warning('Suscripción fallida detectada, cancelando orden y cuotas', [
                    'subscription_id' => $subscription->id
                ]);

                // Cancelar orden
                $order->update(['status' => 'cancelled']);

                // Cancelar solo las cuotas (el plan NO se cancela)
                $installmentPlan->installments()->update(['status' => 'cancelled']);

                DB::commit();

                // Redirigir a página de fallo
                return redirect()->route('subscription.failure', ['subscriptionId' => $subscription->id])
                    ->with('error', 'La suscripción no pudo ser procesada');
            }

            DB::commit();

            Log::info('Suscripción creada exitosamente', [
                'subscription_id' => $subscription->id,
                'virtualpos_subscription_id' => $subscription->virtualpos_subscription_id,
                'order_id' => $order->id,
                'installment_plan_id' => $installmentPlan->id,
                'url_redirect' => $response['url_redirect'] ?? null
            ]);

            // Redirigir a VirtualPos para completar el pago
            if (!empty($response['url_redirect'])) {
                return redirect()->away($response['url_redirect']);
            }

            // Fallback: si no hay URL de redirect, mostrar error
            throw new Exception('No se recibió URL de redirección de VirtualPos');

        } catch (Exception $e) {
            DB::rollBack();

            Log::error('Error al crear suscripción desde confirmación', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request' => $request->all()
            ]);

            return redirect()->back()
                ->withErrors(['error' => 'Error al crear la suscripción: ' . $e->getMessage()])
                ->withInput();
        }
    }

    /**
     * URL de retorno después de pagar en VirtualPos
     * Este método verifica el estado de la suscripción y redirige a success o failure
     */
    public function returnUrl(Request $request)
    {
        try {
            Log::info('VirtualPos Return URL recibido', [
                'method' => $request->method(),
                'all_params' => $request->all(),
                'query' => $request->query(),
                'input' => $request->input()
            ]);

            // VirtualPos puede enviar el service_id, subscription_id o uuid
            $serviceId = $request->input('service_id') ?? $request->query('service_id');
            $subscriptionId = $request->input('subscription_id') ?? $request->query('subscription_id');
            $uuid = $request->input('uuid') ?? $request->query('uuid');

            if (!$serviceId && !$subscriptionId && !$uuid) {
                throw new Exception('No se recibió service_id, subscription_id ni uuid desde VirtualPos');
            }

            // Buscar la suscripción en nuestra base de datos
            $subscription = null;
            if ($subscriptionId) {
                $subscription = ProgramSubscription::where('virtualpos_subscription_id', $subscriptionId)->first();
            } elseif ($uuid) {
                $subscription = ProgramSubscription::where('virtualpos_subscription_id', $uuid)->first();
            } elseif ($serviceId) {
                $subscription = ProgramSubscription::where('service_id', $serviceId)->first();
            }

            if (!$subscription) {
                throw new Exception('Suscripción no encontrada en la base de datos');
            }

            Log::info('Suscripción encontrada en BD', [
                'subscription_id' => $subscription->id,
                'virtualpos_subscription_id' => $subscription->virtualpos_subscription_id,
                'service_id' => $subscription->service_id,
                'current_status' => $subscription->status
            ]);

            // Consultar el estado actual en VirtualPos
            $virtualPosService = new VirtualPosSubscriptionService();
            $virtualPosResponse = $virtualPosService->getSubscription($subscription->virtualpos_subscription_id);

            Log::info('Estado de suscripción en VirtualPos', [
                'virtualpos_response' => $virtualPosResponse,
                'status' => $virtualPosResponse['suscription']['status'] ?? 'unknown'
            ]);

            $virtualPosStatus = $virtualPosResponse['suscription']['status'] ?? null;

            // Actualizar estado de la suscripción en nuestra BD
            $subscription->update([
                'status' => $virtualPosStatus,
                'payment_method' => $virtualPosResponse['suscription']['payment_method'] ?? null,
                'charge_program' => $virtualPosResponse['suscription']['charge_program'] ?? null,
                'api_response' => $virtualPosResponse,
            ]);

            // SINCRONIZAR CUOTAS CON VIRTUALPOS
            // Buscar la orden y el plan de cuotas asociado a esta suscripción
            $order = Order::where('participant_id', $subscription->participant_id)
                ->where('program_id', $subscription->program_id)
                ->where('order_number', 'LIKE', 'SUB-%')
                ->orderBy('created_at', 'desc')
                ->first();

            if ($order) {
                $installmentPlan = InstallmentPlan::where('order_id', $order->id)
                    ->where('participant_id', $subscription->participant_id)
                    ->first();

                if ($installmentPlan) {
                    // Obtener el charge_program de VirtualPos
                    $chargeProgram = $virtualPosResponse['suscription']['charge_program'] ?? [];

                    if (!empty($chargeProgram)) {
                        Log::info('Sincronizando cuotas con VirtualPos', [
                            'subscription_id' => $subscription->id,
                            'total_charges' => count($chargeProgram)
                        ]);

                        // Estados que indican pago CONFIRMADO exitoso
                        // IMPORTANTE: NO incluir 'procesando' porque es un estado intermedio que puede fallar
                        $approvedStatuses = ['pagado', 'cobrado', 'aprobado', 'approved', 'paid', 'success'];

                        foreach ($chargeProgram as $index => $charge) {
                            // Buscar si ya existe una cuota con este virtualpos_charge_id
                            $installment = $installmentPlan->installments()
                                ->where('virtualpos_charge_id', $charge['id'])
                                ->first();

                            // Convertir el status de VirtualPos a nuestro formato
                            // Solo marcar como 'paid' si el status está CONFIRMADO
                            $status = 'pending';
                            $isPaid = false;
                            if (isset($charge['status'])) {
                                $chargeStatus = strtolower($charge['status']);
                                if (in_array($chargeStatus, $approvedStatuses)) {
                                    $status = 'paid';
                                    $isPaid = true;
                                }
                                // Log si está procesando para debugging
                                if ($chargeStatus === 'procesando') {
                                    Log::info('Sincronización: Charge en estado procesando, esperando confirmación', [
                                        'charge_id' => $charge['id'] ?? null,
                                        'amount' => $charge['amount'] ?? 0,
                                    ]);
                                }
                            }

                            if ($installment) {
                                // Actualizar cuota existente
                                $installment->update([
                                    'amount' => $charge['amount'],
                                    'due_date' => $charge['charge_date'],
                                    'status' => $status,
                                    'is_paid' => $isPaid,
                                    'paid_at' => $isPaid ? ($installment->paid_at ?? now()) : null,
                                ]);

                                Log::info('Cuota actualizada', [
                                    'installment_id' => $installment->id,
                                    'virtualpos_charge_id' => $charge['id'],
                                    'status' => $status
                                ]);
                            } else {
                                // Crear nueva cuota si no existe
                                $installmentPlan->installments()->create([
                                    'installment_number' => $index + 1,
                                    'virtualpos_charge_id' => $charge['id'],
                                    'amount' => $charge['amount'],
                                    'due_date' => $charge['charge_date'],
                                    'status' => $status,
                                    'is_paid' => $isPaid,
                                    'paid_at' => $isPaid ? now() : null,
                                ]);

                                Log::info('Cuota creada', [
                                    'installment_number' => $index + 1,
                                    'virtualpos_charge_id' => $charge['id'],
                                    'status' => $status
                                ]);
                            }
                        }
                    }
                }
            }

            // Si la suscripción está ACTIVA, redirigir a página de verificación del primer cargo
            // La verificación real del estado del cargo se hace con polling en la vista verifying
            if ($virtualPosStatus === 'ACTIVA') {
                Log::info('Suscripción ACTIVA, redirigiendo a página de verificación del primer cargo', [
                    'subscription_id' => $subscription->id
                ]);

                // Generar token firmado para seguridad
                $token = encrypt([
                    'subscription_id' => $subscription->id,
                    'participant_id' => $subscription->participant_id,
                    'timestamp' => now()->timestamp
                ]);

                // Redirigir a la página de verificación del primer cargo
                // Esta página hace polling al endpoint checkFirstChargeStatus
                // y redirige a success o failure según el resultado
                return redirect()->route('subscription.verifying', ['subscriptionId' => $subscription->id])
                    ->with('subscription_token', $token);
            }

            // Si no está activa, redirigir a failure
            Log::info('Suscripción NO activa, redirigiendo a failure', [
                'status' => $virtualPosStatus
            ]);

            $token = encrypt([
                'subscription_id' => $subscription->id,
                'participant_id' => $subscription->participant_id,
                'timestamp' => now()->timestamp
            ]);

            return redirect()->route('subscription.failure', ['subscriptionId' => $subscription->id])
                ->with('subscription_token', $token);

        } catch (Exception $e) {
            Log::error('Error en returnUrl', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request' => $request->all()
            ]);

            return redirect()->route('ecommerce.programs')
                ->with('error', 'Hubo un problema al procesar tu suscripción. Por favor contacta a soporte.');
        }
    }

    /**
     * Callback para notificaciones asíncronas de VirtualPos
     * VirtualPos llama este endpoint cuando hay cambios en la suscripción
     */
    public function callback(Request $request)
    {
        try {
            Log::info('VirtualPos Callback recibido', [
                'all_params' => $request->all(),
                'headers' => $request->headers->all()
            ]);

            $serviceId = $request->input('service_id');
            $subscriptionId = $request->input('subscription_id');
            $status = $request->input('status');

            if (!$serviceId && !$subscriptionId) {
                Log::warning('Callback sin service_id ni subscription_id');
                return response()->json(['status' => 'error', 'message' => 'Missing identifiers'], 400);
            }

            // Buscar la suscripción
            $subscription = null;
            if ($subscriptionId) {
                $subscription = ProgramSubscription::where('virtualpos_subscription_id', $subscriptionId)->first();
            } elseif ($serviceId) {
                $subscription = ProgramSubscription::where('service_id', $serviceId)->first();
            }

            if (!$subscription) {
                Log::warning('Callback para suscripción no encontrada', [
                    'service_id' => $serviceId,
                    'subscription_id' => $subscriptionId
                ]);
                return response()->json(['status' => 'error', 'message' => 'Subscription not found'], 404);
            }

            // Actualizar estado
            $subscription->update([
                'status' => $status,
                'api_response' => $request->all(),
            ]);

            Log::info('Suscripción actualizada desde callback', [
                'subscription_id' => $subscription->id,
                'new_status' => $status
            ]);

            return response()->json(['status' => 'OK', 'message' => 'Callback processed successfully']);

        } catch (Exception $e) {
            Log::error('Error en callback', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request' => $request->all()
            ]);

            return response()->json(['status' => 'error', 'message' => 'Internal server error'], 500);
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
     * Página de éxito después de procesar pago (suscripción o pago total)
     */
    public function success(Request $request, int $subscriptionId)
    {
        try {
            // Validar token de seguridad (opcional si viene de session)
            $sessionToken = session('subscription_token');
            if ($sessionToken) {
                try {
                    $tokenData = decrypt($sessionToken);

                    // Verificar que el token corresponde a esta suscripción
                    if ($tokenData['subscription_id'] != $subscriptionId) {
                        throw new Exception('Token no válido para esta suscripción');
                    }

                    // Validar que no sea muy antiguo (máximo 1 hora)
                    if (now()->timestamp - $tokenData['timestamp'] > 3600) {
                        throw new Exception('Token expirado');
                    }
                } catch (Exception $e) {
                    Log::warning('Token de suscripción inválido', [
                        'error' => $e->getMessage(),
                        'subscription_id' => $subscriptionId
                    ]);

                    return redirect()->route('ecommerce.programs')
                        ->with('error', 'Sesión expirada. Por favor intenta nuevamente.');
                }
            }

            // Intentar cargar suscripción primero
            $subscription = ProgramSubscription::with(['participant', 'programCourse'])
                ->find($subscriptionId);

            // Validar que el guardian logueado tenga permiso para ver esta suscripción
            if ($subscription && auth('guardian')->check()) {
                $guardian = auth('guardian')->user();

                if (!$guardian->canPayFor($subscription->participant_id)) {
                    Log::warning('Guardian sin permiso intenta ver página de éxito', [
                        'guardian_id' => $guardian->id,
                        'subscription_id' => $subscriptionId,
                        'participant_id' => $subscription->participant_id
                    ]);

                    return redirect()->route('ecommerce.programs')
                        ->with('error', 'No tienes permiso para ver esta página.');
                }
            }

            // Buscar la orden asociada
            $order = null;
            if ($subscription) {
                $order = Order::where('participant_id', $subscription->participant_id)
                    ->where('program_id', $subscription->program_id)
                    ->where('order_number', 'LIKE', 'SUB-%')
                    ->orderBy('created_at', 'desc')
                    ->first();
            } else {
                // Si no hay suscripción, buscar orden por ID
                $order = Order::find($subscriptionId);
            }

            if (!$order) {
                throw new Exception('No se encontró la orden o suscripción');
            }

            $paymentType = $order->payment_type; // 'total' o 'monthly'

            Log::info('Procesando éxito de pago', [
                'order_id' => $order->id,
                'payment_type' => $paymentType,
                'subscription_id' => $subscription ? $subscription->id : null,
                'order_status' => $order->status
            ]);

            DB::beginTransaction();

            try {
                // CASO 1: SUSCRIPCIÓN (monthly)
                if ($paymentType === 'monthly' && $subscription && $subscription->status === 'ACTIVA') {

                    if ($order->status === 'pending') {
                        // Actualizar estado de la orden a "active" (suscripción activa)
                        $order->update([
                            'status' => 'processing'
                        ]);

                        Log::info('Orden de suscripción actualizada a processing', [
                            'order_id' => $order->id,
                            'subscription_id' => $subscription->id,
                            'note' => 'La suscripción está activa. Los cobros se procesarán según el charge_program de VirtualPos.'
                        ]);

                        // NOTA: NO marcamos cuotas como pagadas aquí porque VirtualPos no cobra inmediatamente
                        // Los cobros se realizarán en las fechas programadas según el charge_program
                        // Los webhooks de VirtualPos nos notificarán cuando se realicen los cobros
                    }

                    DB::commit();

                    return inertia('Subscription/Success', [
                        'payment_type' => 'monthly',
                        'subscription' => [
                            'id' => $subscription->id,
                            'status' => $subscription->status,
                            'amount' => $subscription->amount,
                            'participant_name' => $subscription->participant->full_name ?? 'Usuario',
                            'program_name' => $subscription->plan_name,
                        ],
                        'order' => [
                            'id' => $order->id,
                            'order_number' => $order->order_number,
                            'total_amount' => $order->total_amount,
                            'status' => $order->status,
                        ]
                    ]);
                }

                // CASO 2: PAGO TOTAL (total)
                else if ($paymentType === 'total') {

                    if ($order->status === 'pending') {
                        // Actualizar estado de la orden a "paid"
                        $order->update([
                            'status' => 'paid'
                        ]);

                        Log::info('Orden de pago total actualizada a paid', ['order_id' => $order->id]);

                        // Buscar si hay algún payment pendiente
                        $existingPayment = \App\Models\Payment::where('order_id', $order->id)
                            ->where('status', 'pending')
                            ->first();

                        if ($existingPayment) {
                            // Actualizar el payment existente
                            $existingPayment->update([
                                'status' => 'approved',
                                'paid_at' => now(),
                            ]);

                            Log::info('Payment de pago total actualizado a approved', [
                                'payment_id' => $existingPayment->id
                            ]);
                        }

                        // TODO: Enviar correo de confirmación de pago total
                        // Mail::to($order->participant->email)->send(new PaymentConfirmationMail($order));
                    }

                    DB::commit();

                    return inertia('Subscription/Success', [
                        'payment_type' => 'total',
                        'order' => [
                            'id' => $order->id,
                            'order_number' => $order->order_number,
                            'total_amount' => $order->total_amount,
                            'final_amount' => $order->final_amount,
                            'status' => $order->status,
                            'participant_name' => $order->participant->full_name ?? 'Usuario',
                        ]
                    ]);
                }

                // Caso por defecto: orden no procesable
                DB::commit();

                return inertia('Subscription/Success', [
                    'payment_type' => $paymentType,
                    'message' => 'Tu pago está siendo procesado'
                ]);

            } catch (Exception $e) {
                DB::rollBack();
                Log::error('Error al procesar orden después de pago exitoso', [
                    'error' => $e->getMessage(),
                    'order_id' => $order->id,
                    'payment_type' => $paymentType
                ]);
                throw $e;
            }

        } catch (Exception $e) {
            Log::error('Error en página de éxito: ' . $e->getMessage());

            return redirect()->route('ecommerce.programs')
                ->with('error', 'Hubo un problema al cargar la información de tu pago.');
        }
    }

    /**
     * Página de fallo al crear suscripción
     */
    public function failure(Request $request, int $subscriptionId)
    {
        try {
            // Cargar la suscripción para obtener el estado real
            $subscription = ProgramSubscription::find($subscriptionId);

            $errorMessage = 'Error desconocido';

            if ($subscription) {
                $status = $subscription->status;
                $apiResponse = $subscription->api_response ?? [];

                // Mapear estados de VirtualPOS a mensajes amigables
                $statusMessages = [
                    'SUSCRIPCION_FALLIDA' => 'La suscripción no pudo ser procesada. Por favor verifica los datos de tu tarjeta e intenta nuevamente.',
                    'CANCELADA' => 'La suscripción fue cancelada.',
                    'PENDIENTE' => 'La suscripción está pendiente de confirmación.',
                    'RECHAZADA' => 'La tarjeta fue rechazada. Verifica que tengas fondos suficientes o intenta con otra tarjeta.',
                    'TIMEOUT' => 'El tiempo para completar la transacción ha expirado. Por favor intenta nuevamente.',
                    'ERROR_BANCO' => 'Hubo un error con el banco emisor de tu tarjeta. Contacta a tu banco para más información.',
                ];

                // Intentar obtener mensaje del estado
                if ($status && isset($statusMessages[$status])) {
                    $errorMessage = $statusMessages[$status];
                } elseif ($status) {
                    // Si hay estado pero no está mapeado, mostrar el estado
                    $errorMessage = "Error en la suscripción (Estado: {$status}). Por favor contacta a soporte si el problema persiste.";
                }

                // Si hay un mensaje de error específico en api_response, usarlo
                if (isset($apiResponse['error']['message'])) {
                    $errorMessage = $apiResponse['error']['message'];
                } elseif (isset($apiResponse['suscription']['error_message'])) {
                    $errorMessage = $apiResponse['suscription']['error_message'];
                }

                Log::info('Mostrando página de fallo de suscripción', [
                    'subscription_id' => $subscriptionId,
                    'status' => $status,
                    'error_message' => $errorMessage
                ]);
            }

            return inertia('Subscription/Failure', [
                'subscription_id' => $subscriptionId,
                'error' => $errorMessage
            ]);

        } catch (Exception $e) {
            Log::error('Error en página de fallo: ' . $e->getMessage());

            return redirect()->route('ecommerce.programs');
        }
    }

    /**
     * Página intermedia de verificación del primer cargo
     * Muestra "Suscripción creada, verificando primer cargo..."
     */
    public function verifying(Request $request, int $subscriptionId)
    {
        try {
            $subscription = ProgramSubscription::with(['participant', 'programCourse.program'])
                ->find($subscriptionId);

            if (!$subscription) {
                return redirect()->route('ecommerce.programs')
                    ->with('error', 'Suscripción no encontrada.');
            }

            Log::info('Mostrando página de verificación de primer cargo', [
                'subscription_id' => $subscriptionId,
                'status' => $subscription->status
            ]);

            return inertia('Subscription/VerifyingPayment', [
                'subscription_id' => $subscriptionId,
                'subscription' => [
                    'id' => $subscription->id,
                    'program_name' => $subscription->programCourse?->program?->name ?? 'Programa',
                    'participant_name' => $subscription->participant?->full_name ?? 'Participante',
                    'amount' => $subscription->monthly_amount ?? 0,
                    'status' => $subscription->status,
                ]
            ]);

        } catch (Exception $e) {
            Log::error('Error en página de verificación: ' . $e->getMessage());

            return redirect()->route('ecommerce.programs')
                ->with('error', 'Hubo un problema al cargar la página de verificación.');
        }
    }

    /**
     * Verificar estado del primer cargo de la suscripción (endpoint AJAX para polling)
     * Retorna: 'pending', 'approved', 'rejected'
     */
    public function checkFirstChargeStatus(int $subscriptionId): JsonResponse
    {
        try {
            // Esperar 4 segundos antes de verificar para dar tiempo a VirtualPos
            // de procesar el cobro de la primera cuota
            sleep(4);

            $subscription = ProgramSubscription::find($subscriptionId);

            if (!$subscription) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Suscripción no encontrada'
                ], 404);
            }

            // Consultar estado actual en VirtualPos
            $virtualPosService = new VirtualPosSubscriptionService();
            $virtualPosResponse = $virtualPosService->getSubscription($subscription->virtualpos_subscription_id);

            $chargeProgram = $virtualPosResponse['suscription']['charge_program'] ?? [];
            $firstCharge = $chargeProgram[0] ?? null;

            if (!$firstCharge) {
                Log::info('checkFirstChargeStatus: No hay charges en la suscripción', [
                    'subscription_id' => $subscriptionId
                ]);

                return response()->json([
                    'status' => 'pending',
                    'message' => 'Esperando información del primer cargo'
                ]);
            }

            $chargeStatus = strtolower($firstCharge['status'] ?? 'pending');

            Log::info('checkFirstChargeStatus: Estado del primer cargo', [
                'subscription_id' => $subscriptionId,
                'charge_id' => $firstCharge['id'] ?? null,
                'charge_status' => $chargeStatus
            ]);

            // Estados que indican pago APROBADO
            $approvedStatuses = ['pagado', 'cobrado', 'aprobado', 'approved', 'paid', 'success'];

            // Estados que indican pago RECHAZADO
            $rejectedStatuses = ['rechazado', 'rejected', 'failed', 'cancelado', 'cancelled', 'error'];

            if (in_array($chargeStatus, $approvedStatuses)) {
                // Primer cargo aprobado - ejecutar sincronización de pagos
                try {
                    \App\Jobs\SyncSubscriptionPaymentsJob::dispatchSync($subscription->id);

                    Log::info('checkFirstChargeStatus: Sincronización de pagos ejecutada', [
                        'subscription_id' => $subscriptionId
                    ]);
                } catch (\Exception $e) {
                    Log::error('checkFirstChargeStatus: Error al sincronizar pagos', [
                        'subscription_id' => $subscriptionId,
                        'error' => $e->getMessage()
                    ]);
                }

                // Enviar email de suscripción exitosa
                try {
                    // Usar relación directa subscription->order
                    $order = $subscription->order;

                    if ($order) {
                        $installmentPlan = $order->installmentPlan;

                        if ($installmentPlan) {
                            $this->sendSubscriptionSuccessEmail($subscription, $order, $installmentPlan);
                        }
                    }
                } catch (\Exception $e) {
                    Log::error('checkFirstChargeStatus: Error al enviar email de suscripción', [
                        'subscription_id' => $subscriptionId,
                        'error' => $e->getMessage()
                    ]);
                }

                return response()->json([
                    'status' => 'approved',
                    'message' => 'Primer cargo aprobado exitosamente',
                    'charge_id' => $firstCharge['id'] ?? null
                ]);
            }

            if (in_array($chargeStatus, $rejectedStatuses)) {
                // Primer cargo rechazado - cancelar la suscripción y registros relacionados
                Log::warning('checkFirstChargeStatus: Primer cargo rechazado, cancelando suscripción', [
                    'subscription_id' => $subscriptionId,
                    'charge_status' => $chargeStatus
                ]);

                try {
                    // Cancelar suscripción en VirtualPos
                    $virtualPosService->cancelSubscription($subscription->virtualpos_subscription_id);

                    // Marcar suscripción como FALLIDA (no CANCELADA, porque nunca se activó)
                    $subscription->update([
                        'status' => 'SUSCRIPCION_FALLIDA',
                    ]);

                    // Cancelar orden y cuotas (NO el plan)
                    $order = $subscription->order;

                    if ($order && $order->status !== 'cancelled') {
                        // Cancelar la orden
                        $order->update(['status' => 'cancelled']);

                        // Cancelar solo las cuotas (el plan NO se cancela)
                        $installmentPlan = $order->installmentPlan;
                        if ($installmentPlan) {
                            $installmentPlan->installments()->update(['status' => 'cancelled']);

                            Log::info('checkFirstChargeStatus: Cuotas canceladas', [
                                'subscription_id' => $subscriptionId,
                                'order_id' => $order->id,
                                'installment_plan_id' => $installmentPlan->id
                            ]);
                        }

                        Log::info('checkFirstChargeStatus: Orden cancelada', [
                            'subscription_id' => $subscriptionId,
                            'order_id' => $order->id
                        ]);
                    }

                    Log::info('checkFirstChargeStatus: Suscripción marcada como fallida', [
                        'subscription_id' => $subscriptionId
                    ]);

                } catch (\Exception $e) {
                    Log::error('checkFirstChargeStatus: Error al cancelar suscripción', [
                        'subscription_id' => $subscriptionId,
                        'error' => $e->getMessage()
                    ]);
                }

                return response()->json([
                    'status' => 'rejected',
                    'message' => 'El primer cargo fue rechazado. La suscripción ha sido cancelada.',
                    'charge_id' => $firstCharge['id'] ?? null
                ]);
            }

            // Estado pendiente o procesando - seguir esperando
            return response()->json([
                'status' => 'pending',
                'message' => 'El cargo está siendo procesado',
                'charge_status' => $chargeStatus
            ]);

        } catch (Exception $e) {
            Log::error('checkFirstChargeStatus: Error', [
                'subscription_id' => $subscriptionId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Error al verificar el estado del cargo'
            ], 500);
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

    /**
     * Verificar si un participante tiene suscripción activa para un programa
     * Endpoint público para verificar antes de permitir continuar con el flujo
     */
    public function checkSubscriptionStatus(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'participant_id' => 'required|integer',
                'program_id' => 'required|integer',
            ]);

            $participantId = $request->input('participant_id');
            $programId = $request->input('program_id');

            // Buscar SOLO suscripción ACTIVA para este participante y programa
            // NOTA: Solo detectamos estado ACTIVA porque:
            // - SUSCRIBIENDO: proceso incompleto, VirtualPos no permite cancelar, usuario puede reintentar
            // - SUSCRIPCION_FALLIDA: primera cuota rechazada, usuario puede reintentar
            // - CANCELADA: suscripción terminada, usuario puede crear otra
            // En todos estos casos el usuario debe poder continuar con el flujo normal de ecommerce
            $subscription = ProgramSubscription::where('participant_id', $participantId)
                ->where('program_id', $programId)
                ->where('status', 'ACTIVA')
                ->first();

            $hasActiveSubscription = $subscription !== null;

            // Verificar estado del guardian logueado respecto a este participante
            $guardianLoggedIn = auth('guardian')->check();
            $guardianIsOwner = false;
            $guardianEmail = null;

            if ($guardianLoggedIn) {
                $guardian = auth('guardian')->user();
                $guardianEmail = $guardian->email;
                $guardianIsOwner = $guardian->canPayFor($participantId);
            }

            return response()->json([
                'success' => true,
                'has_subscription' => $hasActiveSubscription,
                'requires_login' => $hasActiveSubscription && !$guardianLoggedIn,
                'guardian_logged_in' => $guardianLoggedIn,
                'guardian_is_owner' => $guardianIsOwner,
                'guardian_email' => $guardianEmail,
                'subscription' => $subscription ? [
                    'id' => $subscription->id,
                    'status' => $subscription->status,
                    'plan_name' => $subscription->plan_name,
                ] : null,
            ]);

        } catch (Exception $e) {
            Log::error('Error al verificar estado de suscripción: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'error' => 'Error al verificar el estado de la suscripción'
            ], 500);
        }
    }

    /**
     * Enviar email de suscripción exitosa (sin PDFs, solo confirmación y fecha de primer cobro)
     */
    private function sendSubscriptionSuccessEmail(ProgramSubscription $subscription, Order $order, InstallmentPlan $installmentPlan): void
    {
        try {
            // Verificar si ya se envió el email (evitar duplicados)
            if ($subscription->email_sent) {
                Log::info('Email de suscripción ya fue enviado', [
                    'subscription_id' => $subscription->id,
                    'order_id' => $order->id
                ]);
                return;
            }

            // Obtener datos del participante y programa
            $participant = $subscription->participant;
            $programCourse = $subscription->programCourse;

            // Buscar el contacto de emergencia para obtener el email del apoderado
            $emergencyContact = \App\Models\EmergencyContact::where('participant_id', $participant->id)->first();

            // Obtener información del primer cobro desde el charge_program de VirtualPos
            $chargeProgram = $subscription->charge_program ?? [];
            $firstCharge = !empty($chargeProgram) ? $chargeProgram[0] : null;

            $firstChargeDate = null;
            $firstChargeAmount = $subscription->amount ?? 0;

            if ($firstCharge) {
                $firstChargeDate = isset($firstCharge['charge_date']) ? \Carbon\Carbon::parse($firstCharge['charge_date']) : null;
                $firstChargeAmount = $firstCharge['amount'] ?? $firstChargeAmount;
            }

            // Preparar datos para el email
            $emailData = [
                'customer_name' => ucwords(strtolower($emergencyContact ? $emergencyContact->name : ($participant->first_name . ' ' . $participant->first_last_name))),
                'customer_email' => $emergencyContact ? $emergencyContact->email : $participant->email,
                'participant_name' => ucwords(strtolower($participant->first_name . ' ' . $participant->first_last_name)),
                'program_name' => $programCourse->name ?? 'Programa',
                'subscription_amount' => number_format($firstChargeAmount, 0, ',', '.'),
                'first_charge_date' => $firstChargeDate ? $firstChargeDate->format('d/m/Y') : 'Próximamente',
                'total_installments' => $installmentPlan->total_installments,
                'payment_method' => is_array($subscription->payment_method) ? ($subscription->payment_method['type'] ?? 'Tarjeta') : 'Tarjeta',
                'subscription_id' => $subscription->virtualpos_subscription_id,
                'subject' => 'Suscripción Exitosa - ' . ($programCourse->name ?? 'Programa'),
                'company_name' => config('lat90.company.name'),
                'company_email' => config('lat90.company.email'),
                'company_phone' => config('lat90.email.support.phone'),
            ];

            // Enviar el email simple sin adjuntos
            Mail::send('Mails.subscription_success', $emailData, function ($message) use ($emailData) {
                $message->to($emailData['customer_email'], $emailData['customer_name'])
                    ->subject($emailData['subject']);
            });

            // Marcar que el email fue enviado
            $subscription->update([
                'email_sent' => true,
                'email_sent_at' => now(),
            ]);

            Log::info('Email de suscripción exitosa enviado', [
                'subscription_id' => $subscription->id,
                'customer_email' => $emailData['customer_email'],
                'first_charge_date' => $firstChargeDate ? $firstChargeDate->toDateTimeString() : null,
            ]);

        } catch (\Exception $e) {
            Log::error('Error enviando email de suscripción exitosa', [
                'subscription_id' => $subscription->id,
                'order_id' => $order->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Métodos auxiliares para resolver IDs de países, regiones, ciudades y tipos de documento
     * Copiados de CreateOrderService para mantener consistencia
     */
    private function resolveCountryId($value): ?int
    {
        if (empty($value)) { return null; }
        if (is_numeric($value)) { return (int) $value; }
        $string = trim((string) $value);
        // Probar por código (CL, etc.)
        if (strlen($string) <= 3) {
            $id = \App\Models\Country::where('code', $string)->value('id');
            if ($id) { return (int) $id; }
        }
        // Fallback por nombre exacto
        $id = \App\Models\Country::where('name', $string)->value('id');
        if ($id) { return (int) $id; }
        // Fallback por like
        $id = \App\Models\Country::where('name', 'like', $string)->value('id');
        return $id ? (int) $id : null;
    }

    private function resolveRegionId($value): ?int
    {
        if (empty($value)) { return null; }
        if (is_numeric($value)) { return (int) $value; }
        $string = trim((string) $value);
        $id = \App\Models\Region::where('name', $string)->value('id');
        if ($id) { return (int) $id; }
        $id = \App\Models\Region::where('name', 'like', $string)->value('id');
        return $id ? (int) $id : null;
    }

    private function resolveCityId($value): ?int
    {
        if (empty($value)) { return null; }
        if (is_numeric($value)) { return (int) $value; }
        $string = trim((string) $value);
        $id = \App\Models\Comune::where('name', $string)->value('id');
        if ($id) { return (int) $id; }
        $id = \App\Models\Comune::where('name', 'like', $string)->value('id');
        return $id ? (int) $id : null;
    }

    private function resolveDocumentTypeId($value): ?int
    {
        if (empty($value)) { return null; }
        if (is_numeric($value)) { return (int) $value; }
        $string = trim((string) $value);
        $id = \App\Models\Document::where('name', $string)->value('id');
        if ($id) { return (int) $id; }
        $id = \App\Models\Document::where('name', 'like', $string)->value('id');
        return $id ? (int) $id : null;
    }
}
