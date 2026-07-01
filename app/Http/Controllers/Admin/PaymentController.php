<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Participant as Passenger;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Http\Requests\Admin\Payments\DailyReportRequest;
use App\Http\Requests\Admin\Payments\ConsolidatedReportRequest;
use App\Services\Admin\Payments\ReportsService;
use App\Services\Admin\Payments\GetPaymentsService;
use App\Services\Admin\Payments\GetCreateDataService;
use App\Services\Admin\Payments\StorePaymentService;
use App\Services\Admin\Payments\GetEditDataService;
use App\Services\Admin\Payments\UpdatePaymentService;
use App\Services\Admin\Payments\DeletePaymentService;
use App\Services\Admin\Payments\GetInstallmentScheduleService;
use App\Services\Admin\Payments\GetAccountStatementService;
use App\Services\Admin\Payments\GetParticipantPaymentStatusService;
use App\Services\Admin\Payments\ReconfirmPaymentService;
use App\Models\BsaleRequest;
use App\Services\Bsale\BsaleQueueService;

class PaymentController extends Controller
{
    protected $reportsService;
    protected $getPaymentsService;
    protected $getCreateDataService;
    protected $storePaymentService;
    protected $getEditDataService;
    protected $updatePaymentService;
    protected $deletePaymentService;
    protected $getInstallmentScheduleService;
    protected $getAccountStatementService;
    protected $getParticipantPaymentStatusService;
    protected $reconfirmPaymentService;

    public function __construct(
        ReportsService $reportsService,
        GetPaymentsService $getPaymentsService,
        GetCreateDataService $getCreateDataService,
        StorePaymentService $storePaymentService,
        GetEditDataService $getEditDataService,
        UpdatePaymentService $updatePaymentService,
        DeletePaymentService $deletePaymentService,
        GetInstallmentScheduleService $getInstallmentScheduleService,
        GetAccountStatementService $getAccountStatementService,
        GetParticipantPaymentStatusService $getParticipantPaymentStatusService,
        ReconfirmPaymentService $reconfirmPaymentService
    ) {
        $this->reportsService = $reportsService;
        $this->getPaymentsService = $getPaymentsService;
        $this->getCreateDataService = $getCreateDataService;
        $this->storePaymentService = $storePaymentService;
        $this->getEditDataService = $getEditDataService;
        $this->updatePaymentService = $updatePaymentService;
        $this->deletePaymentService = $deletePaymentService;
        $this->getInstallmentScheduleService = $getInstallmentScheduleService;
        $this->getAccountStatementService = $getAccountStatementService;
        $this->getParticipantPaymentStatusService = $getParticipantPaymentStatusService;
        $this->reconfirmPaymentService = $reconfirmPaymentService;
    }

    public function index(Request $request)
    {
        $data = $this->getPaymentsService->execute($request);

        return Inertia::render('Admin/Payments/Index', $data);
    }

    public function show(Payment $payment)
    {
        $payment->load([
            'order',
            'order.participant.documentType',
            'order.program.course.institution',
            'orderDetail.country',
            'orderDetail.region',
            'orderDetail.city',
            'paymentGateway',
            'paymentOption'
        ]);

        return Inertia::render('Admin/Payments/Show', [
            'payment' => $payment
        ]);
    }

    public function create()
    {
        $data = $this->getCreateDataService->execute();

        return Inertia::render('Admin/Payments/Create', $data);
    }

    public function store(Request $request)
    {
        $request->validate([
            'program_id' => 'required|exists:program_courses,id',
            'participant_id' => 'required|exists:participants,id',
            'amount' => 'required|numeric|min:0',
            'status' => 'required|in:pending,completed,failed,authorized',
            'presential_payment_type' => 'required|in:BX,TE,CH,DP,AP,CT',
            'payment_code' => 'required|string|max:255',
            'authorization_code' => 'nullable|string',
            'notes' => 'nullable|string',
            'installment_id' => 'nullable|exists:installments,id', // Opcional: ID de cuota a marcar como pagada

            // Datos del comprador
            'buyer_first_name' => 'required|string|max:255',
            'buyer_last_name' => 'required|string|max:255',
            'buyer_document_type' => 'required|exists:document,id',
            'buyer_document_number' => 'required|string|max:255',
            'buyer_email' => 'required|email|max:255',
            'fiscal_document_type' => 'nullable|in:B2,FF',
        ]);

        try {
            $result = $this->storePaymentService->execute($request);

            return redirect()->route('admin.payments.index')
                ->with('success', 'Pago presencial registrado exitosamente. Se han reestructurado las cuotas pendientes.');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Error al registrar el pago: ' . $e->getMessage()]);
        }
    }

    public function edit(Payment $payment)
    {
        $data = $this->getEditDataService->execute($payment);

        return Inertia::render('Admin/Payments/Edit', $data);
    }

    public function update(Request $request, Payment $payment)
    {
        $request->validate([
            'amount' => 'required|numeric|min:0',
            'status' => 'required|in:pending,completed,failed,authorized',
            'transaction_date' => 'required|date',
            'authorization_code' => 'nullable|string',
            'card_number' => 'nullable|string',
            'card_type' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        try {
            $this->updatePaymentService->execute($request, $payment);

            return redirect()->route('admin.payments.index')
                ->with('success', 'Pago actualizado exitosamente.');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Error al actualizar el pago: ' . $e->getMessage()]);
        }
    }

    public function destroy(Payment $payment)
    {
        try {
            $this->deletePaymentService->execute($payment);

            return redirect()->route('admin.payments.index')
                ->with('success', 'Pago eliminado exitosamente.');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Error al eliminar el pago: ' . $e->getMessage()]);
        }
    }

    public function dailyReport(DailyReportRequest $request)
    {
        $validated = $request->validated();
        [$payments, $summary] = $this->reportsService->daily($validated);
        return Inertia::render('Admin/Reports/DailyPayments', [
            'payments' => $payments,
            'summary' => $summary,
            'filters' => $validated
        ]);
    }

    public function consolidatedReport(ConsolidatedReportRequest $request)
    {
        $validated = $request->validated();
        $payments = $this->reportsService->consolidated($validated);

        return Inertia::render('Admin/Reports/ConsolidatedPayments', [
            'payments' => $payments,
            'filters' => $validated
        ]);
    }

    public function installmentSchedule()
    {
        $data = $this->getInstallmentScheduleService->execute();

        return Inertia::render('Admin/Reports/InstallmentSchedule', $data);
    }

    public function accountStatement(Passenger $passenger)
    {
        $data = $this->getAccountStatementService->execute($passenger);

        return Inertia::render('Admin/Reports/AccountStatement', $data);
    }

    public function getParticipantPaymentStatus(Request $request)
    {
        // program_id ahora apunta a program_courses (instancias específicas)
        $request->validate([
            'program_id' => 'required|exists:program_courses,id',
            'participant_id' => 'required|exists:participants,id',
        ]);

        try {
            $result = $this->getParticipantPaymentStatusService->execute($request);
            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener el estado de pagos: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Regenerar boleta BSale para un pago
     */
    public function retryBsale(Payment $payment)
    {
        try {
            // Validar que el pago esté completado
            if (!in_array($payment->status, ['completed', 'approved'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Solo se puede generar boleta para pagos completados.',
                ], 422);
            }

            // Validar que no tenga boleta ya generada
            if (!empty($payment->bsale_document_id)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Este pago ya tiene una boleta generada.',
                ], 422);
            }

            $queueService = app(BsaleQueueService::class);

            // Buscar solicitud existente
            $existingRequest = BsaleRequest::where('payment_id', $payment->id)
                ->where('document_type', BsaleRequest::DOC_TYPE_BOLETA)
                ->latest()
                ->first();

            if ($existingRequest) {
                // Si está en processing "reciente" (arrancó hace menos de PROCESSING_TIMEOUT_MINUTES),
                // asumimos que el cron BSale la está procesando ahora — NO forzar reprocess
                // para evitar generar boletas duplicadas.
                // Caso reportado: payment 9705, boletas #28835 y #28836 emitidas con 2 seg de
                // diferencia porque el cron y el retry manual corrieron simultáneamente.
                $PROCESSING_TIMEOUT_MINUTES = 10;
                if ($existingRequest->status === 'processing') {
                    $startedAt = $existingRequest->processing_started_at;
                    $isStuck = $startedAt && $startedAt->diffInMinutes(now()) >= $PROCESSING_TIMEOUT_MINUTES;
                    if (!$isStuck) {
                        return response()->json([
                            'success' => false,
                            'message' => 'La boleta se está generando en este momento. Espera unos minutos y refresca la página.',
                        ], 409);
                    }
                    // Está stuck (>= 10 min) → sí forzar
                    $queueService->forceReprocess($existingRequest, auth()->id());
                    $bsaleRequest = $existingRequest;
                } elseif (in_array($existingRequest->status, ['failed', 'cancelled'])) {
                    $queueService->forceReprocess($existingRequest, auth()->id());
                    $bsaleRequest = $existingRequest;
                } elseif ($existingRequest->status === 'pending') {
                    // Ya está pendiente, procesarla directamente
                    $bsaleRequest = $existingRequest;
                } elseif ($existingRequest->status === 'completed') {
                    return response()->json([
                        'success' => false,
                        'message' => 'Ya existe una solicitud completada para este pago.',
                    ], 422);
                } else {
                    $bsaleRequest = $existingRequest;
                }
            } else {
                // Crear nueva solicitud
                $bsaleRequest = BsaleRequest::createFromPayment(
                    $payment,
                    BsaleRequest::DOC_TYPE_BOLETA,
                    'manual_retry',
                    ['manual_retry_by' => auth()->id(), 'manual_retry_at' => now()->toIso8601String()]
                );
            }

            // Refrescar para asegurar estado pending
            $bsaleRequest->refresh();

            // Procesar inmediatamente
            $success = $queueService->processRequest($bsaleRequest);

            // Recargar payment para obtener datos actualizados
            $payment->refresh();

            if ($success) {
                return response()->json([
                    'success' => true,
                    'message' => 'Boleta generada exitosamente.',
                    'bsale_number' => $payment->bsale_number,
                    'bsale_document_id' => $payment->bsale_document_id,
                    'bsale_token' => $payment->bsale_token,
                ]);
            } else {
                $bsaleRequest->refresh();
                return response()->json([
                    'success' => false,
                    'message' => 'No se pudo generar la boleta: ' . ($bsaleRequest->error_message ?? 'Error desconocido'),
                ], 500);
            }

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al generar boleta: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Reconfirmar un pago consultando VirtualPOS
     */
    public function reconfirm(Request $request, Payment $payment)
    {
        try {
            $skipEmail = $request->boolean('skip_email', false);
            $skipBsale = $request->boolean('skip_bsale', false);

            $result = $this->reconfirmPaymentService->execute($payment, $skipEmail, $skipBsale);

            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al reconfirmar el pago: ' . $e->getMessage()
            ], 500);
        }
    }
}
