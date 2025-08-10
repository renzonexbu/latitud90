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

class PaymentController extends Controller
{
    public function __construct(private ReportsService $reportsService) {}
    public function index(Request $request)
    {
        $query = Payment::with(['passenger.program']);

        // Filtros
        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('id', 'like', '%' . $request->search . '%')
                  ->orWhere('transaction_id', 'like', '%' . $request->search . '%')
                  ->orWhereHas('passenger', function ($sq) use ($request) {
                      $sq->where('first_name', 'like', '%' . $request->search . '%')
                        ->orWhere('last_name', 'like', '%' . $request->search . '%')
                        ->orWhere('email', 'like', '%' . $request->search . '%');
                  });
            });
        }

        if ($request->status) {
            $query->where('status', $request->status);
        }

        if ($request->gateway) {
            $query->where('payment_method', $request->gateway);
        }

        if ($request->date_from) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->date_to) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $payments = $query->latest()->paginate(15);

        // Estadísticas
        $stats = [
            'total_revenue' => Payment::where('status', 'approved')->sum('amount'),
            'completed' => Payment::where('status', 'approved')->count(),
            'pending' => Payment::where('status', 'pending')->count(),
            'failed' => Payment::where('status', 'rejected')->count(),
            'refunded' => Payment::where('status', 'refunded')->count(),
        ];

        return Inertia::render('Admin/Payments/Index', [
            'payments' => $payments,
            'stats' => $stats,
            'filters' => $request->only(['search', 'status', 'gateway', 'date_from', 'date_to'])
        ]);
    }

    public function show(Payment $payment)
    {
        $payment->load(['passenger.program']);
        
        return Inertia::render('Admin/Payments/Show', [
            'payment' => $payment
        ]);
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
        $upcomingPayments = Payment::with(['passenger.program'])
            ->where('status', 'pending')
            ->where('payment_type', 'installment')
            ->orderBy('created_at', 'asc')
            ->get();

        return Inertia::render('Admin/Reports/InstallmentSchedule', [
            'upcomingPayments' => $upcomingPayments
        ]);
    }

    public function accountStatement(Passenger $passenger)
    {
        $passenger->load(['program', 'payments', 'contracts']);
        
        $statement = [
            'passenger' => $passenger,
            'total_program_price' => $passenger->individual_price + $passenger->price_adjustments,
            'total_paid' => $passenger->total_paid,
            'pending_amount' => $passenger->pending_amount,
            'payments_history' => $passenger->payments()->orderBy('created_at', 'desc')->get(),
            'payment_progress' => $passenger->total_paid > 0 ? 
                round(($passenger->total_paid / ($passenger->individual_price + $passenger->price_adjustments)) * 100, 2) : 0
        ];

        return Inertia::render('Admin/Reports/AccountStatement', [
            'statement' => $statement
        ]);
    }
}
