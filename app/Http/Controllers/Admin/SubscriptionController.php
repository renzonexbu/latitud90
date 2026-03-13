<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ProgramSubscription;
use App\Models\Installment;
use App\Models\InstallmentPlan;
use App\Models\VirtualPosPlan;
use App\Models\OrderDetail;
use App\Models\Payment;
use App\Services\Admin\Subscriptions\GetSubscriptionsService;
use App\Services\Admin\Subscriptions\ChargeAttempts\ChargeAttemptsService;
use App\Services\Admin\Subscriptions\ChargeAttempts\ChargeAttemptsDataProvider;
use App\Services\Admin\Subscriptions\ChargeAttempts\ExportService as ChargeAttemptsExportService;
use App\Services\VirtualPos\CancelSubscriptionService;
use App\Services\VirtualPos\SyncSubscriptionService;
use App\Services\VirtualPos\CreateChargeService;
use App\Services\Mail\SuccessPaymentEmailService;
use App\Jobs\SyncSubscriptionPaymentsJob;
use App\Models\ProgramCourse;
use App\Models\SalesExecutive;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Inertia\Inertia;
use Inertia\Response;

class SubscriptionController extends Controller
{
    public function __construct(
        private GetSubscriptionsService $getSubscriptionsService,
        private CancelSubscriptionService $cancelSubscriptionService,
        private SyncSubscriptionService $syncSubscriptionService,
        private CreateChargeService $createChargeService,
        private ChargeAttemptsService $chargeAttemptsService,
        private ChargeAttemptsDataProvider $chargeAttemptsDataProvider,
        private ChargeAttemptsExportService $chargeAttemptsExportService
    ) {}

    /**
     * Mostrar lista de suscripciones
     */
    public function index(Request $request): Response
    {
        $data = $this->getSubscriptionsService->execute($request);

        return Inertia::render('Admin/Subscriptions/Index', $data);
    }

    /**
     * Listado de cuotas (charges) de todas las suscripciones
     */
    public function charges(Request $request): Response
    {
        $query = ProgramSubscription::query()
            ->whereNotNull('virtualpos_subscription_id')
            ->whereNotNull('charge_program')
            ->with(['participant', 'programCourse.program']);

        $subscriptions = $query->get();

        // Extraer todos los charges de charge_program y aplanarlos
        $allCharges = [];
        foreach ($subscriptions as $sub) {
            $charges = $sub->charge_program ?? [];
            foreach ($charges as $index => $charge) {
                $status = strtolower($charge['status'] ?? 'pendiente');

                $allCharges[] = [
                    'charge_id' => $charge['id'] ?? null,
                    'subscription_id' => $sub->id,
                    'subscription_status' => $sub->status,
                    'participant_name' => $sub->participant?->full_name ?? 'N/A',
                    'program_name' => $sub->programCourse?->name ?? 'N/A',
                    'program_code' => $sub->programCourse?->program?->code ?? 'N/A',
                    'installment_number' => $index + 1,
                    'amount' => $charge['amount'] ?? 0,
                    'charge_date' => $charge['charge_date'] ?? null,
                    'status' => $status,
                    'description' => $charge['description'] ?? null,
                ];
            }
        }

        // Filtros
        $filterStatus = $request->get('charge_status', 'all');
        $filterSearch = $request->get('search', '');
        $filterSubStatus = $request->get('subscription_status', 'all');

        $filtered = collect($allCharges);

        if ($filterStatus && $filterStatus !== 'all') {
            $filtered = $filtered->filter(fn($c) => $c['status'] === $filterStatus);
        }

        if ($filterSubStatus && $filterSubStatus !== 'all') {
            $filtered = $filtered->filter(fn($c) => $c['subscription_status'] === $filterSubStatus);
        }

        if ($filterSearch) {
            $search = strtolower($filterSearch);
            $filtered = $filtered->filter(fn($c) =>
                str_contains(strtolower($c['participant_name']), $search) ||
                str_contains(strtolower($c['program_code']), $search) ||
                str_contains((string) $c['subscription_id'], $search) ||
                str_contains((string) $c['charge_id'], $search)
            );
        }

        // Estadísticas
        $allCollection = collect($allCharges);
        $stats = [
            'total' => $allCollection->count(),
            'pagado' => $allCollection->where('status', 'pagado')->count(),
            'pendiente' => $allCollection->where('status', 'pendiente')->count(),
            'rechazado' => $allCollection->where('status', 'rechazado')->count(),
            'reintentando' => $allCollection->where('status', 'reintentando')->count(),
            'cancelado' => $allCollection->where('status', 'cancelado')->count(),
            'procesando' => $allCollection->where('status', 'procesando')->count(),
        ];

        // Paginación manual
        $page = (int) $request->get('page', 1);
        $perPage = 20;
        $sorted = $filtered->sortByDesc('charge_date')->values();
        $paginated = $sorted->slice(($page - 1) * $perPage, $perPage)->values();
        $totalPages = (int) ceil($sorted->count() / $perPage);

        // Construir links para paginación
        $links = [];
        $baseUrl = route('admin.subscriptions.charges');
        $queryParams = $request->except('page');

        $links[] = [
            'url' => $page > 1 ? $baseUrl . '?' . http_build_query(array_merge($queryParams, ['page' => $page - 1])) : null,
            'label' => '&laquo; Anterior',
            'active' => false,
        ];

        // Mostrar máximo 7 páginas alrededor de la actual
        $windowSize = 3;
        $startPage = max(1, $page - $windowSize);
        $endPage = min($totalPages, $page + $windowSize);

        if ($startPage > 1) {
            $links[] = ['url' => $baseUrl . '?' . http_build_query(array_merge($queryParams, ['page' => 1])), 'label' => '1', 'active' => false];
            if ($startPage > 2) {
                $links[] = ['url' => null, 'label' => '...', 'active' => false];
            }
        }

        for ($i = $startPage; $i <= $endPage; $i++) {
            $links[] = [
                'url' => $baseUrl . '?' . http_build_query(array_merge($queryParams, ['page' => $i])),
                'label' => (string) $i,
                'active' => $i === $page,
            ];
        }

        if ($endPage < $totalPages) {
            if ($endPage < $totalPages - 1) {
                $links[] = ['url' => null, 'label' => '...', 'active' => false];
            }
            $links[] = ['url' => $baseUrl . '?' . http_build_query(array_merge($queryParams, ['page' => $totalPages])), 'label' => (string) $totalPages, 'active' => false];
        }

        $links[] = [
            'url' => $page < $totalPages ? $baseUrl . '?' . http_build_query(array_merge($queryParams, ['page' => $page + 1])) : null,
            'label' => 'Siguiente &raquo;',
            'active' => false,
        ];

        return Inertia::render('Admin/Subscriptions/Charges', [
            'charges' => [
                'data' => $paginated->all(),
                'links' => $links,
            ],
            'stats' => $stats,
            'filters' => [
                'charge_status' => $filterStatus,
                'search' => $filterSearch,
                'subscription_status' => $filterSubStatus,
            ],
        ]);
    }

    /**
     * Mostrar detalles de una suscripción
     */
    public function show(ProgramSubscription $subscription): Response
    {
        // Sincronizar automáticamente con VirtualPos al ver los detalles
        if (!empty($subscription->virtualpos_subscription_id)) {
            Log::channel('daily')->info('ADMIN SHOW: Sincronizando automáticamente con VirtualPos', [
                'subscription_id' => $subscription->id,
                'virtualpos_subscription_id' => $subscription->virtualpos_subscription_id,
            ]);

            $syncResult = $this->syncSubscriptionService->sync($subscription);

            Log::channel('daily')->info('ADMIN SHOW: Resultado de sincronización automática', [
                'success' => $syncResult['success'],
                'message' => $syncResult['message'],
            ]);

            // Recargar la suscripción después de sincronizar
            $subscription->refresh();
        }

        $subscription->load([
            'participant.documentType',
            'programCourse.program',
            'programCourse.course.institution',
        ]);

        // Cargar el plan de cuotas usando program_subscription_id (relación directa)
        $installmentPlan = \App\Models\InstallmentPlan::where('program_subscription_id', $subscription->id)
            ->with(['installments' => function ($query) {
                $query->orderBy('installment_number');
            }])
            ->first();

        // Fallback para suscripciones antiguas sin program_subscription_id
        if (!$installmentPlan) {
            $installmentPlan = \App\Models\InstallmentPlan::where('participant_id', $subscription->participant_id)
                ->where('program_id', $subscription->program_id)
                ->with(['installments' => function ($query) {
                    $query->orderBy('installment_number');
                }])
                ->first();
        }

        $installments = [];
        $totalInstallments = 0;
        $paidInstallments = 0;

        if ($installmentPlan) {
            $totalInstallments = $installmentPlan->total_installments;
            $paidInstallments = $installmentPlan->installments->where('status', 'paid')->count();

            $installments = $installmentPlan->installments->map(function ($installment) {
                return [
                    'id' => $installment->id,
                    'installment_number' => $installment->installment_number,
                    'amount' => $installment->amount,
                    'due_date' => \Carbon\Carbon::parse($installment->due_date)->format('d-m-Y'),
                    'status' => $installment->status,
                    'is_paid' => $installment->is_paid,
                    'paid_at' => $installment->paid_at ? \Carbon\Carbon::parse($installment->paid_at)->format('d-m-Y H:i') : null,
                    'virtualpos_charge_id' => $installment->virtualpos_charge_id,
                    'retry_count' => $installment->retry_count ?? 0,
                    'last_retry_at' => $installment->last_retry_at ? \Carbon\Carbon::parse($installment->last_retry_at)->format('d-m-Y H:i') : null,
                ];
            })->toArray();
        }

        // Obtener cargos adicionales del api_response
        $additionalCharges = [];
        $apiResponse = $subscription->api_response ?? [];
        if (!empty($apiResponse['additional_charges'])) {
            $additionalCharges = collect($apiResponse['additional_charges'])->map(function ($charge) {
                return [
                    'id' => $charge['id'] ?? null,
                    'amount' => $charge['amount'] ?? 0,
                    'charge_date' => isset($charge['charge_date']) ? \Carbon\Carbon::parse($charge['charge_date'])->format('d-m-Y') : null,
                    'status' => $charge['status'] ?? 'pendiente',
                    'description' => $charge['description'] ?? 'Cargo adicional',
                ];
            })->toArray();
        }

        // Calcular precio real del participante usando ParticipantPriceHelper
        $priceData = null;
        $paidAmount = 0;
        $discountDetails = [];

        if ($subscription->participant && $subscription->programCourse) {
            $priceData = \App\Helpers\ParticipantPriceHelper::calculateParticipantPrice(
                $subscription->participant,
                $subscription->programCourse
            );

            // 1. Pagos normales desde payments - EXCLUIR pagos de suscripción para evitar doble conteo
            $normalPayments = \App\Models\Payment::whereHas('order', function ($q) use ($subscription) {
                    $q->where('participant_id', $subscription->participant_id)
                      ->where('program_id', $subscription->program_id);
                })
                ->whereIn('status', ['completed', 'approved'])
                ->where(function($query) {
                    // Excluir pagos de suscripción (se cuentan abajo en installments)
                    $query->whereNull('payment_source')
                          ->orWhere('payment_source', '!=', 'subscription');
                })
                ->sum('amount');

            // 2. Cuotas de suscripción pagadas (installments)
            $subscriptionPayments = \Illuminate\Support\Facades\DB::table('installments')
                ->join('installment_plans', 'installments.installment_plan_id', '=', 'installment_plans.id')
                ->where('installment_plans.participant_id', $subscription->participant_id)
                ->where('installment_plans.program_id', $subscription->program_id)
                ->where('installments.status', 'paid')
                ->sum('installments.amount');

            $paidAmount = (float) $normalPayments + (float) $subscriptionPayments;

            // Obtener descuentos aplicados del participant_program
            $participantProgram = \Illuminate\Support\Facades\DB::table('participant_program')
                ->where('participant_id', $subscription->participant_id)
                ->where('program_id', $subscription->program_id)
                ->first();

            if ($participantProgram) {
                $discounts = \Illuminate\Support\Facades\DB::table('participant_program_discounts')
                    ->where('participant_program_id', $participantProgram->id)
                    ->get();

                foreach ($discounts as $discount) {
                    $discountDetails[] = [
                        'reason' => $discount->description ?? 'Descuento',
                        'percent' => $discount->percent,
                        'amount' => $discount->amount,
                    ];
                }
            }
        }

        // También revisar si hay info del VirtualPosPlan para el tipo de descuento (Beca, etc.)
        $virtualPosPlan = VirtualPosPlan::where('virtualpos_plan_id', $subscription->virtualpos_plan_id)->first();

        $planInfo = [
            'base_price' => (int) round($priceData['base_price'] ?? 0),
            'adjustments' => (int) round($priceData['adjustments'] ?? 0),
            'discounts' => (int) round($priceData['discounts'] ?? 0),
            'final_price' => (int) round($priceData['final_price'] ?? 0),
            'paid_amount' => (int) round($paidAmount),
            'pending_amount' => (int) max(0, round(($priceData['final_price'] ?? 0) - $paidAmount)),
            'discount_details' => $discountDetails,
            'discount_type' => $virtualPosPlan?->discount_type,
            'discount_reason' => $virtualPosPlan?->discount_reason ?? ($discountDetails[0]['reason'] ?? null),
            'is_personalized' => $virtualPosPlan?->isPersonalized() ?? (count($discountDetails) > 0),
        ];

        return Inertia::render('Admin/Subscriptions/Show', [
            'subscription' => [
                'id' => $subscription->id,
                'virtualpos_subscription_id' => $subscription->virtualpos_subscription_id,
                'status' => $subscription->status,
                'payment_method' => $subscription->payment_method,
                'created_at' => $subscription->created_at->toDateString(),
                'participant' => $subscription->participant ? [
                    'id' => $subscription->participant->id,
                    'name' => $subscription->participant->full_name,
                    'document' => $subscription->participant->document_number,
                    'document_type' => $subscription->participant->documentType?->name ?? 'N/A',
                    'email' => $subscription->participant->email,
                ] : [
                    'id' => null,
                    'name' => 'N/A (Suscripción de prueba)',
                    'document' => 'N/A',
                    'document_type' => 'N/A',
                    'email' => 'N/A',
                ],
                'program' => $subscription->programCourse ? [
                    'id' => $subscription->programCourse->id,
                    'name' => $subscription->programCourse->name,
                    'destination' => $subscription->programCourse->program->destination ?? '',
                    'departure_date' => $subscription->programCourse->departure_date,
                ] : [
                    'id' => null,
                    'name' => 'N/A (Suscripción de prueba)',
                    'destination' => '',
                    'departure_date' => null,
                ],
                'institution' => [
                    'name' => $subscription->programCourse?->course?->institution?->name ?? 'N/A',
                ],
                'plan' => $planInfo,
                'installments' => $installments,
                'total_installments' => $totalInstallments,
                'paid_installments' => $paidInstallments,
                'additional_charges' => $additionalCharges,
                'charge_program' => $subscription->charge_program ?? [],
            ]
        ]);
    }

    /**
     * Sincronizar suscripción con VirtualPos
     * Este método usa el job de sincronización para detectar pagos nuevos y enviar emails
     */
    public function syncWithVirtualPos(ProgramSubscription $subscription)
    {
        Log::channel('daily')->info('=== ADMIN: Sincronizar suscripción con VirtualPos (con detección de pagos) ===', [
            'subscription_id' => $subscription->id,
            'virtualpos_subscription_id' => $subscription->virtualpos_subscription_id,
            'current_status' => $subscription->status,
            'user_id' => auth()->id(),
        ]);

        try {
            // Usar el job de sincronización de forma síncrona
            // Este job detecta pagos nuevos, crea Payment, marca cuotas como pagadas y envía emails
            SyncSubscriptionPaymentsJob::dispatchSync($subscription->id);

            Log::channel('daily')->info('ADMIN: Sincronización con detección de pagos completada', [
                'subscription_id' => $subscription->id,
            ]);

            // Recargar la suscripción para mostrar datos actualizados
            $subscription->refresh();

            return back()->with('success', 'Suscripción sincronizada correctamente. Si se detectaron pagos nuevos, se enviaron los correos correspondientes.');

        } catch (\Exception $e) {
            Log::channel('daily')->error('ADMIN: Error en sincronización', [
                'subscription_id' => $subscription->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return back()->with('error', 'Error al sincronizar: ' . $e->getMessage());
        }
    }

    /**
     * Reenviar email de confirmación de pago para una cuota específica
     */
    public function resendPaymentEmail(ProgramSubscription $subscription, Request $request)
    {
        Log::channel('daily')->info('=== ADMIN: Reenviar email de pago ===', [
            'subscription_id' => $subscription->id,
            'installment_number' => $request->installment_number,
            'user_id' => auth()->id(),
        ]);

        $request->validate([
            'installment_number' => 'required|integer|min:1',
        ]);

        $installmentNumber = $request->installment_number;

        try {
            // Buscar el plan de cuotas
            $installmentPlan = InstallmentPlan::where('participant_id', $subscription->participant_id)
                ->where('program_id', $subscription->program_id)
                ->first();

            if (!$installmentPlan) {
                return back()->with('error', 'No se encontró plan de cuotas para esta suscripción.');
            }

            // Buscar la cuota
            $installment = $installmentPlan->installments()
                ->where('installment_number', $installmentNumber)
                ->first();

            if (!$installment) {
                return back()->with('error', "No se encontró la cuota #{$installmentNumber}.");
            }

            if (!$installment->is_paid) {
                return back()->with('error', "La cuota #{$installmentNumber} no está marcada como pagada.");
            }

            // Obtener el Payment - primero por payment_id, si no existe buscar por virtualpos_charge_id
            $payment = null;

            if ($installment->payment_id) {
                $payment = Payment::find($installment->payment_id);
            }

            // Si no tiene payment_id pero tiene virtualpos_charge_id, buscar el Payment por external_payment_id
            if (!$payment && $installment->virtualpos_charge_id) {
                $payment = Payment::where('external_payment_id', $installment->virtualpos_charge_id)->first();

                // Si encontramos el Payment, actualizar la cuota para futuras consultas
                if ($payment) {
                    $installment->update([
                        'payment_id' => $payment->id,
                        'payment_order_detail_id' => $payment->order_detail_id,
                    ]);

                    Log::channel('daily')->info('ADMIN: Vinculación de pago corregida automáticamente', [
                        'installment_id' => $installment->id,
                        'payment_id' => $payment->id,
                        'virtualpos_charge_id' => $installment->virtualpos_charge_id,
                    ]);
                }
            }

            if (!$payment) {
                return back()->with('error', "No se encontró el registro de pago para la cuota #{$installmentNumber}. Verifique que el pago exista en la tabla payments.");
            }

            $orderDetail = $payment->orderDetail;
            if (!$orderDetail) {
                Log::channel('daily')->error('ADMIN: No se encontró orderDetail para el payment', [
                    'payment_id' => $payment->id,
                    'order_detail_id' => $payment->order_detail_id,
                ]);
                return back()->with('error', 'No se encontró el detalle de la orden.');
            }

            Log::channel('daily')->info('ADMIN: Preparando envío de email de pago', [
                'subscription_id' => $subscription->id,
                'installment_number' => $installmentNumber,
                'payment_id' => $payment->id,
                'order_detail_id' => $orderDetail->id,
                'participant_id' => $orderDetail->participant_id ?? 'N/A',
            ]);

            // Enviar email usando el servicio existente
            $emailService = new SuccessPaymentEmailService();
            $result = $emailService->sendSuccessPaymentEmail($orderDetail, $payment);

            Log::channel('daily')->info('ADMIN: Resultado del envío de email', [
                'subscription_id' => $subscription->id,
                'installment_number' => $installmentNumber,
                'result' => $result ? 'ENVIADO' : 'FALLIDO',
            ]);

            if ($result) {
                Log::channel('daily')->info('ADMIN: Email de pago reenviado exitosamente', [
                    'subscription_id' => $subscription->id,
                    'installment_number' => $installmentNumber,
                    'payment_id' => $payment->id,
                ]);

                return back()->with('success', "Email de confirmación de pago de la cuota #{$installmentNumber} reenviado exitosamente.");
            }

            Log::channel('daily')->warning('ADMIN: El servicio de email retornó false', [
                'subscription_id' => $subscription->id,
                'installment_number' => $installmentNumber,
            ]);

            return back()->with('error', 'Error al enviar el email. Revise los logs para más detalles.');

        } catch (\Exception $e) {
            Log::channel('daily')->error('ADMIN: Error reenviando email de pago', [
                'subscription_id' => $subscription->id,
                'installment_number' => $installmentNumber,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'Error: ' . $e->getMessage());
        }
    }

    /**
     * Reenviar email de confirmación de suscripción exitosa
     */
    public function resendSubscriptionEmail(ProgramSubscription $subscription)
    {
        Log::channel('daily')->info('=== ADMIN: Reenviar email de suscripción ===', [
            'subscription_id' => $subscription->id,
            'virtualpos_subscription_id' => $subscription->virtualpos_subscription_id,
            'user_id' => auth()->id(),
        ]);

        try {
            // Cargar relaciones necesarias
            $subscription->load(['participant', 'programCourse.program', 'programCourse.course.institution']);

            $participant = $subscription->participant;
            $programCourse = $subscription->programCourse;

            if (!$participant || !$programCourse) {
                return back()->with('error', 'Datos incompletos de la suscripción.');
            }

            // Obtener el plan de cuotas para información adicional
            $installmentPlan = InstallmentPlan::where('participant_id', $subscription->participant_id)
                ->where('program_id', $subscription->program_id)
                ->with(['installments' => function ($query) {
                    $query->orderBy('installment_number');
                }])
                ->first();

            // Determinar datos del comprador
            $buyerData = $subscription->buyer_data ?? [];
            $buyerEmail = $buyerData['email'] ?? $participant->email;

            // Construir nombre del comprador
            $buyerName = trim(
                ($buyerData['first_name'] ?? '') . ' ' .
                ($buyerData['second_name'] ?? '') . ' ' .
                ($buyerData['first_last_name'] ?? '') . ' ' .
                ($buyerData['second_last_name'] ?? '')
            );
            $buyerName = preg_replace('/\s+/', ' ', $buyerName);
            if (empty(trim($buyerName))) {
                $buyerName = $participant->full_name;
            }

            // Obtener primera fecha de cobro
            $firstChargeDate = 'Por confirmar';
            if ($installmentPlan && $installmentPlan->installments->isNotEmpty()) {
                $firstInstallment = $installmentPlan->installments->first();
                $firstChargeDate = \Carbon\Carbon::parse($firstInstallment->due_date)->format('d/m/Y');
            } elseif (!empty($subscription->charge_program) && count($subscription->charge_program) > 0) {
                $firstCharge = $subscription->charge_program[0];
                $firstChargeDate = isset($firstCharge['charge_date'])
                    ? \Carbon\Carbon::parse($firstCharge['charge_date'])->format('d/m/Y')
                    : 'Por confirmar';
            }

            // Formatear método de pago
            $paymentMethodData = $subscription->payment_method;
            $paymentMethod = 'Tarjeta de Crédito/Débito';
            if (is_array($paymentMethodData)) {
                $brand = $paymentMethodData['card_brand'] ?? $paymentMethodData['brand'] ?? '';
                $last4 = $paymentMethodData['last_four_digits'] ?? $paymentMethodData['last4'] ?? '';
                if ($brand && $last4) {
                    $paymentMethod = strtoupper($brand) . ' •••• ' . $last4;
                }
            }

            // Preparar datos del email según la plantilla subscription_success
            $emailData = [
                'company_name' => config('lat90.company.name', 'Latitud 90'),
                'customer_name' => $buyerName,
                'participant_name' => $participant->full_name,
                'program_name' => $programCourse->name,
                'first_charge_date' => $firstChargeDate,
                'subscription_amount' => number_format($subscription->amount, 0, ',', '.'),
                'total_installments' => $subscription->installments,
                'payment_method' => $paymentMethod,
                'subscription_id' => $subscription->virtualpos_subscription_id,
            ];

            Log::channel('daily')->info('ADMIN: Preparando envío de email de suscripción', [
                'subscription_id' => $subscription->id,
                'email_destinatario' => $buyerEmail,
                'buyer_name' => $buyerName,
                'participant_name' => $participant->full_name,
                'program_name' => $programCourse->name,
            ]);

            // Enviar email de confirmación de suscripción
            Mail::send('Mails.subscription_success', $emailData, function ($message) use ($buyerEmail, $buyerName, $programCourse) {
                $message->to($buyerEmail, $buyerName)
                    ->subject('Confirmación de Suscripción - ' . $programCourse->name);
            });

            Log::channel('daily')->info('ADMIN: Email de suscripción ENVIADO exitosamente', [
                'subscription_id' => $subscription->id,
                'email' => $buyerEmail,
                'resultado' => 'ENVIADO',
            ]);

            return back()->with('success', 'Email de confirmación de suscripción reenviado exitosamente a ' . $buyerEmail);

        } catch (\Exception $e) {
            Log::channel('daily')->error('ADMIN: Error reenviando email de suscripción', [
                'subscription_id' => $subscription->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return back()->with('error', 'Error al enviar el email: ' . $e->getMessage());
        }
    }

    /**
     * Cancelar suscripción
     */
    public function cancel(ProgramSubscription $subscription)
    {
        Log::channel('daily')->info('=== ADMIN: Cancelar suscripción ===', [
            'subscription_id' => $subscription->id,
            'virtualpos_subscription_id' => $subscription->virtualpos_subscription_id,
            'current_status' => $subscription->status,
            'user_id' => auth()->id(),
        ]);

        $result = $this->cancelSubscriptionService->cancel($subscription);

        Log::channel('daily')->info('ADMIN: Resultado de cancelación', [
            'subscription_id' => $subscription->id,
            'success' => $result['success'],
            'message' => $result['message'],
        ]);

        if ($result['success']) {
            return back()->with('success', $result['message']);
        }

        return back()->with('error', $result['message']);
    }

    /**
     * Crear un cargo manual para una cuota
     */
    public function createCharge(ProgramSubscription $subscription, Request $request)
    {
        Log::channel('daily')->info('=== ADMIN: Crear cargo para cuota ===', [
            'subscription_id' => $subscription->id,
            'virtualpos_subscription_id' => $subscription->virtualpos_subscription_id,
            'installment_id' => $request->installment_id,
            'user_id' => auth()->id(),
        ]);

        $request->validate([
            'installment_id' => 'required|integer|exists:installments,id',
        ]);

        $installment = Installment::findOrFail($request->installment_id);

        Log::channel('daily')->info('ADMIN: Datos de la cuota', [
            'installment_id' => $installment->id,
            'installment_number' => $installment->installment_number,
            'amount' => $installment->amount,
            'is_paid' => $installment->is_paid,
            'virtualpos_charge_id' => $installment->virtualpos_charge_id,
        ]);

        // Verificar que la cuota pertenece a la suscripción
        $installmentPlan = InstallmentPlan::where('participant_id', $subscription->participant_id)
            ->where('program_id', $subscription->program_id)
            ->first();

        if (!$installmentPlan || $installment->installment_plan_id !== $installmentPlan->id) {
            Log::channel('daily')->warning('ADMIN: Cuota no pertenece a la suscripción', [
                'installment_plan_id' => $installment->installment_plan_id,
                'expected_plan_id' => $installmentPlan?->id,
            ]);
            return back()->with('error', 'La cuota no pertenece a esta suscripción.');
        }

        $result = $this->createChargeService->createCharge($subscription, $installment);

        Log::channel('daily')->info('ADMIN: Resultado de crear cargo', [
            'subscription_id' => $subscription->id,
            'installment_id' => $installment->id,
            'success' => $result['success'],
            'message' => $result['message'],
            'data' => $result['data'] ?? null,
        ]);

        // Sincronizar después de crear el cargo
        if ($result['success']) {
            $this->syncSubscriptionService->sync($subscription);
            return back()->with('success', $result['message']);
        }

        return back()->with('error', $result['message']);
    }

    /**
     * Reintentar un cargo fallido
     */
    public function retryCharge(ProgramSubscription $subscription, Request $request)
    {
        Log::channel('charge_retries')->info('=== ADMIN: Reintentar cargo de cuota ===', [
            'subscription_id' => $subscription->id,
            'virtualpos_subscription_id' => $subscription->virtualpos_subscription_id,
            'installment_id' => $request->installment_id,
            'user_id' => auth()->id(),
            'user_email' => auth()->user()->email ?? 'N/A',
        ]);

        $request->validate([
            'installment_id' => 'required|integer|exists:installments,id',
        ]);

        $installment = Installment::findOrFail($request->installment_id);

        Log::channel('charge_retries')->info('ADMIN: Datos de la cuota para reintento', [
            'installment_id' => $installment->id,
            'installment_number' => $installment->installment_number,
            'amount' => $installment->amount,
            'current_charge_id' => $installment->virtualpos_charge_id,
            'current_status' => $installment->status,
        ]);

        // Verificar que la cuota pertenece a la suscripción
        $installmentPlan = InstallmentPlan::where('participant_id', $subscription->participant_id)
            ->where('program_id', $subscription->program_id)
            ->first();

        if (!$installmentPlan || $installment->installment_plan_id !== $installmentPlan->id) {
            Log::channel('charge_retries')->warning('ADMIN: Cuota no pertenece a la suscripción en reintento', [
                'subscription_id' => $subscription->id,
                'installment_id' => $installment->id,
            ]);
            return back()->with('error', 'La cuota no pertenece a esta suscripción.');
        }

        $result = $this->createChargeService->retryCharge($subscription, $installment);

        Log::channel('charge_retries')->info('ADMIN: Resultado de reintentar cargo', [
            'subscription_id' => $subscription->id,
            'installment_id' => $installment->id,
            'charge_id' => $installment->virtualpos_charge_id,
            'success' => $result['success'],
            'message' => $result['message'],
        ]);

        // Sincronizar después de reintentar el cargo
        if ($result['success']) {
            $this->syncSubscriptionService->sync($subscription);
            return back()->with('success', $result['message']);
        }

        return back()->with('error', $result['message']);
    }

    /**
     * Eliminar un cargo de una cuota
     */
    public function deleteCharge(ProgramSubscription $subscription, Request $request)
    {
        Log::channel('daily')->info('=== ADMIN: Eliminar cargo de cuota ===', [
            'subscription_id' => $subscription->id,
            'virtualpos_subscription_id' => $subscription->virtualpos_subscription_id,
            'installment_id' => $request->installment_id,
            'user_id' => auth()->id(),
        ]);

        $request->validate([
            'installment_id' => 'required|integer|exists:installments,id',
        ]);

        $installment = Installment::findOrFail($request->installment_id);

        Log::channel('daily')->info('ADMIN: Datos del cargo a eliminar', [
            'installment_id' => $installment->id,
            'installment_number' => $installment->installment_number,
            'virtualpos_charge_id' => $installment->virtualpos_charge_id,
            'is_paid' => $installment->is_paid,
        ]);

        // Verificar que la cuota pertenece a la suscripción
        $installmentPlan = InstallmentPlan::where('participant_id', $subscription->participant_id)
            ->where('program_id', $subscription->program_id)
            ->first();

        if (!$installmentPlan || $installment->installment_plan_id !== $installmentPlan->id) {
            Log::channel('daily')->warning('ADMIN: Cuota no pertenece a la suscripción en eliminación');
            return back()->with('error', 'La cuota no pertenece a esta suscripción.');
        }

        $result = $this->createChargeService->deleteCharge($subscription, $installment);

        Log::channel('daily')->info('ADMIN: Resultado de eliminar cargo', [
            'subscription_id' => $subscription->id,
            'installment_id' => $installment->id,
            'success' => $result['success'],
            'message' => $result['message'],
            'data' => $result['data'] ?? null,
        ]);

        // Sincronizar después de eliminar el cargo
        if ($result['success']) {
            $this->syncSubscriptionService->sync($subscription);
            return back()->with('success', $result['message']);
        }

        return back()->with('error', $result['message']);
    }

    /**
     * Crear un nuevo cargo para la suscripción
     */
    public function createNewCharge(ProgramSubscription $subscription, Request $request)
    {
        Log::channel('daily')->info('=== ADMIN: Crear nuevo cargo para suscripción ===', [
            'subscription_id' => $subscription->id,
            'virtualpos_subscription_id' => $subscription->virtualpos_subscription_id,
            'amount' => $request->amount,
            'description' => $request->description,
            'due_date' => $request->due_date,
            'user_id' => auth()->id(),
        ]);

        $request->validate([
            'amount' => 'required|integer|min:1',
            'description' => 'required|string|max:255',
            'due_date' => 'nullable|date',
        ]);

        Log::channel('daily')->info('ADMIN: Datos validados para nuevo cargo', [
            'subscription_status' => $subscription->status,
            'amount' => $request->amount,
            'description' => $request->description,
            'due_date' => $request->due_date,
        ]);

        $result = $this->createChargeService->createNewChargeForSubscription(
            $subscription,
            $request->amount,
            $request->description,
            $request->due_date
        );

        Log::channel('daily')->info('ADMIN: Resultado de crear nuevo cargo', [
            'subscription_id' => $subscription->id,
            'success' => $result['success'],
            'message' => $result['message'],
            'charge_id' => $result['charge_id'] ?? null,
            'data' => $result['data'] ?? null,
        ]);

        // Sincronizar después de crear el cargo
        if ($result['success']) {
            $this->syncSubscriptionService->sync($subscription);
            return back()->with('success', $result['message']);
        }

        return back()->with('error', $result['message']);
    }

    /**
     * Mostrar reporte de intentos de cobro de VirtualPos
     */
    public function chargeAttempts(Request $request): Response
    {
        $data = $this->chargeAttemptsService->getChargeAttempts($request);

        // Obtener programas para filtros
        $programs = ProgramCourse::select('id', 'name')
            ->orderBy('name')
            ->get();

        // Obtener ejecutivos de ventas para filtros
        $salesExecutives = SalesExecutive::select('id', 'name')
            ->where('active', true)
            ->orderBy('name')
            ->get()
            ->map(function ($executive) {
                return [
                    'id' => $executive->id,
                    'name' => $executive->first_name . ' ' . $executive->last_name,
                ];
            });

        return Inertia::render('Admin/Subscriptions/ChargeAttempts', [
            'chargeAttempts' => $data['chargeAttempts'],
            'statistics' => $data['statistics'],
            'filterOptions' => $data['filterOptions'],
            'filters' => $data['filters'],
            'programs' => $programs,
            'salesExecutives' => $salesExecutives,
        ]);
    }

    /**
     * Exportar reporte de intentos de cobro de suscripciones seleccionadas
     */
    public function exportChargeAttempts(Request $request)
    {
        try {
            $request->validate([
                'subscription_ids' => 'required|array|min:1',
                'subscription_ids.*' => 'exists:program_subscriptions,id',
                'format' => 'nullable|in:xlsx,csv',
            ]);

            $subscriptionIds = $request->input('subscription_ids');
            $format = $request->get('format', 'xlsx');

            Log::info('Exportando intentos de cobro de suscripciones', [
                'subscription_ids' => $subscriptionIds,
                'format' => $format,
                'user_id' => auth()->id(),
            ]);

            // Obtener los datos filtrados por subscription_ids
            $filters = ['subscriptionIds' => $subscriptionIds];
            $data = $this->chargeAttemptsDataProvider->getData($filters);

            if ($data->isEmpty()) {
                return back()->with('error', 'No hay intentos de cobro registrados para las suscripciones seleccionadas.');
            }

            // Generar nombre de archivo
            $filename = 'cobros_virtualpos_' . now('America/Santiago')->format('Y-m-d_H-i-s');

            return $this->chargeAttemptsExportService->export($data, $filename, $format);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return back()->with('error', 'Debe seleccionar al menos una suscripción para exportar.');
        } catch (\Exception $e) {
            Log::error('Error al exportar intentos de cobro', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return back()->with('error', 'Error al exportar: ' . $e->getMessage());
        }
    }
}
