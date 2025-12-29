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
        GetParticipantPaymentStatusService $getParticipantPaymentStatusService
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
            'program_id' => 'required|exists:programs,id',
            'participant_id' => 'required|exists:participants,id',
            'amount' => 'required|numeric|min:0',
            'status' => 'required|in:pending,completed,failed,authorized',
            'presential_payment_type' => 'required|in:BX,TE,CH,DP',
            'payment_code' => 'required|string|max:255',
            'authorization_code' => 'nullable|string',
            'notes' => 'nullable|string',
            'installment_id' => 'nullable|exists:installments,id', // Opcional: ID de cuota a marcar como pagada

            // Datos del comprador
            'buyer_full_name' => 'required|string|max:255',
            'buyer_document_type' => 'required|exists:document,id',
            'buyer_document_number' => 'required|string|max:255',
            'buyer_email' => 'required|email|max:255',
            'buyer_phone' => 'required|string|max:255',
            'buyer_code_phone' => 'required|string|max:10',
            'buyer_country' => 'required|exists:countries,id',
            'buyer_region' => 'required|exists:regions,id',
            'buyer_city' => 'required|exists:comunes,id',
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
}
