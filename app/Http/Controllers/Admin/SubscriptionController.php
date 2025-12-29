<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ProgramSubscription;
use App\Models\Installment;
use App\Models\InstallmentPlan;
use App\Models\VirtualPosPlan;
use App\Services\Admin\Subscriptions\GetSubscriptionsService;
use App\Services\Admin\Subscriptions\ChargeAttempts\ChargeAttemptsService;
use App\Services\Admin\Subscriptions\ChargeAttempts\ChargeAttemptsDataProvider;
use App\Services\Admin\Subscriptions\ChargeAttempts\ExportService as ChargeAttemptsExportService;
use App\Services\VirtualPos\CancelSubscriptionService;
use App\Services\VirtualPos\SyncSubscriptionService;
use App\Services\VirtualPos\CreateChargeService;
use App\Models\ProgramCourse;
use App\Models\SalesExecutive;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
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

        // Cargar el plan de cuotas manualmente
        $installmentPlan = \App\Models\InstallmentPlan::where('participant_id', $subscription->participant_id)
            ->where('program_id', $subscription->program_id)
            ->with(['installments' => function ($query) {
                $query->orderBy('installment_number');
            }])
            ->first();

        $installments = [];
        $totalInstallments = 0;
        $paidInstallments = 0;

        if ($installmentPlan) {
            $totalInstallments = $installmentPlan->total_installments;
            $paidInstallments = $installmentPlan->installments->where('is_paid', true)->count();

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

        // Obtener información del plan de VirtualPos
        $virtualPosPlan = VirtualPosPlan::where('virtualpos_plan_id', $subscription->virtualpos_plan_id)->first();
        $planInfo = null;

        if ($virtualPosPlan) {
            $planInfo = [
                'id' => $virtualPosPlan->id,
                'name' => $virtualPosPlan->name,
                'is_personalized' => $virtualPosPlan->isPersonalized(),
                'discount_type' => $virtualPosPlan->discount_type,
                'discount_reason' => $virtualPosPlan->discount_reason,
                'discount_amount' => $virtualPosPlan->discount_amount,
                'original_price' => $virtualPosPlan->original_price,
                'trip_price' => $virtualPosPlan->trip_price,
                'monthly_amount' => $virtualPosPlan->monthly_amount,
                'max_installments' => $virtualPosPlan->max_installments,
            ];
        }

        return Inertia::render('Admin/Subscriptions/Show', [
            'subscription' => [
                'id' => $subscription->id,
                'virtualpos_subscription_id' => $subscription->virtualpos_subscription_id,
                'status' => $subscription->status,
                'payment_method' => $subscription->payment_method,
                'created_at' => $subscription->created_at->toDateString(),
                'participant' => [
                    'id' => $subscription->participant->id,
                    'name' => $subscription->participant->full_name,
                    'document' => $subscription->participant->document_number,
                    'document_type' => $subscription->participant->documentType?->name ?? 'N/A',
                    'email' => $subscription->participant->email,
                ],
                'program' => [
                    'id' => $subscription->programCourse->id,
                    'name' => $subscription->programCourse->name,
                    'destination' => $subscription->programCourse->program->destination ?? '',
                    'departure_date' => $subscription->programCourse->departure_date,
                ],
                'institution' => [
                    'name' => $subscription->programCourse->course->institution->name ?? 'N/A',
                ],
                'plan' => $planInfo,
                'installments' => $installments,
                'total_installments' => $totalInstallments,
                'paid_installments' => $paidInstallments,
                'additional_charges' => $additionalCharges,
            ]
        ]);
    }

    /**
     * Sincronizar suscripción con VirtualPos
     */
    public function syncWithVirtualPos(ProgramSubscription $subscription)
    {
        Log::channel('daily')->info('=== ADMIN: Sincronizar suscripción con VirtualPos ===', [
            'subscription_id' => $subscription->id,
            'virtualpos_subscription_id' => $subscription->virtualpos_subscription_id,
            'current_status' => $subscription->status,
            'user_id' => auth()->id(),
        ]);

        $result = $this->syncSubscriptionService->sync($subscription);

        Log::channel('daily')->info('ADMIN: Resultado de sincronización', [
            'subscription_id' => $subscription->id,
            'success' => $result['success'],
            'message' => $result['message'],
            'data' => $result['data'] ?? null,
        ]);

        if ($result['success']) {
            return back()->with('success', $result['message']);
        }

        return back()->with('error', $result['message']);
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
        Log::channel('daily')->info('=== ADMIN: Reintentar cargo de cuota ===', [
            'subscription_id' => $subscription->id,
            'virtualpos_subscription_id' => $subscription->virtualpos_subscription_id,
            'installment_id' => $request->installment_id,
            'user_id' => auth()->id(),
        ]);

        $request->validate([
            'installment_id' => 'required|integer|exists:installments,id',
        ]);

        $installment = Installment::findOrFail($request->installment_id);

        Log::channel('daily')->info('ADMIN: Datos de la cuota para reintento', [
            'installment_id' => $installment->id,
            'installment_number' => $installment->installment_number,
            'amount' => $installment->amount,
            'current_charge_id' => $installment->virtualpos_charge_id,
        ]);

        // Verificar que la cuota pertenece a la suscripción
        $installmentPlan = InstallmentPlan::where('participant_id', $subscription->participant_id)
            ->where('program_id', $subscription->program_id)
            ->first();

        if (!$installmentPlan || $installment->installment_plan_id !== $installmentPlan->id) {
            Log::channel('daily')->warning('ADMIN: Cuota no pertenece a la suscripción en reintento');
            return back()->with('error', 'La cuota no pertenece a esta suscripción.');
        }

        $result = $this->createChargeService->retryCharge($subscription, $installment);

        Log::channel('daily')->info('ADMIN: Resultado de reintentar cargo', [
            'subscription_id' => $subscription->id,
            'installment_id' => $installment->id,
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
        $salesExecutives = SalesExecutive::select('id', 'first_name', 'last_name')
            ->orderBy('first_name')
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
