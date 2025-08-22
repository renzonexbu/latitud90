<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Participant as Passenger;
use App\Models\Program;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\PaymentGateway;
use App\Models\PaymentOption;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Http\Requests\Admin\Payments\DailyReportRequest;
use App\Http\Requests\Admin\Payments\ConsolidatedReportRequest;
use App\Models\Country;
use App\Models\Document;
use App\Models\Region;
use App\Services\Admin\Payments\ReportsService;
use Illuminate\Support\Facades\DB;

class PaymentController extends Controller
{
    public function __construct(private ReportsService $reportsService) {}
    
    public function index(Request $request)
    {
        $query = Payment::with([
            'order', 
            'order.participant.documentType', 
            'order.program.course.institution', 
            'orderDetail.country', 
            'orderDetail.region', 
            'orderDetail.city', 
            'paymentGateway', 
            'paymentOption'
        ]);

        // Filtros
        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('id', 'like', '%' . $request->search . '%')
                  ->orWhere('buy_order', 'like', '%' . $request->search . '%')
                  ->orWhere('authorization_code', 'like', '%' . $request->search . '%')
                  ->orWhereHas('order.participant', function ($sq) use ($request) {
                      $sq->where('first_last_name', 'like', '%' . $request->search . '%')
                        ->orWhere('second_last_name', 'like', '%' . $request->search . '%')
                        ->orWhere('first_name', 'like', '%' . $request->search . '%')
                        ->orWhere('second_name', 'like', '%' . $request->search . '%');
                  })
                  ->orWhereHas('order.program', function ($sq) use ($request) {
                      $sq->where('name', 'like', '%' . $request->search . '%');
                  });
            });
        }

        if ($request->status) {
            $query->where('status', $request->status);
        }

        if ($request->gateway) {
            $query->whereHas('paymentGateway', function ($q) use ($request) {
                $q->where('code', $request->gateway);
            });
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
            'total_revenue' => Payment::where('status', 'completed')->sum('amount'),
            'completed' => Payment::where('status', 'completed')->count(),
            'pending' => Payment::where('status', 'pending')->count(),
            'failed' => Payment::where('status', 'failed')->count(),
            'authorized' => Payment::where('status', 'authorized')->count(),
        ];

        return Inertia::render('Admin/Payments/Index', [
            'payments' => $payments,
            'stats' => $stats,
            'filters' => $request->only(['search', 'status', 'gateway', 'date_from', 'date_to'])
        ]);
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
        $programs = Program::with(['course.participants'])->where('active', true)->get();
        
        // Cargar datos para el formulario del comprador
        $countries = Country::where('name', 'Chile')->get();
        $regions = Region::with('comunes')->get();
        $documentTypes = Document::all();
        
        // Métodos de pago presenciales
        $presentialPaymentMethods = [
            ['id' => 'cash', 'name' => 'Efectivo'],
            ['id' => 'debit', 'name' => 'Tarjeta de Débito'],
            ['id' => 'credit', 'name' => 'Tarjeta de Crédito'],
            ['id' => 'transfer', 'name' => 'Transferencia Bancaria'],
        ];

        return Inertia::render('Admin/Payments/Create', [
            'programs' => $programs,
            'countries' => $countries,
            'regions' => $regions,
            'documentTypes' => $documentTypes,
            'presentialPaymentMethods' => $presentialPaymentMethods
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'program_id' => 'required|exists:programs,id',
            'participant_id' => 'required|exists:participants,id',
            'amount' => 'required|numeric|min:0',
            'payment_gateway_id' => 'required|exists:payment_gateways,id',
            'payment_option_id' => 'required|exists:payment_options,id',
            'status' => 'required|in:pending,completed,failed,authorized',
            'transaction_date' => 'required|date',
            'authorization_code' => 'nullable|string',
            'card_number' => 'nullable|string',
            'card_type' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        try {
            DB::beginTransaction();

            // Buscar o crear la orden
            $order = Order::where('participant_id', $request->participant_id)
                         ->where('program_id', $request->program_id)
                         ->first();

            if (!$order) {
                $order = Order::create([
                    'participant_id' => $request->participant_id,
                    'program_id' => $request->program_id,
                    'total_amount' => $request->amount,
                    'final_amount' => $request->amount,
                    'total_installments' => 1,
                    'payment_type' => 'total',
                    'status' => 'pending',
                    'order_number' => 'ORD-' . date('Ymd') . '-' . rand(1000, 9999),
                    'notes' => 'Orden creada desde pago presencial'
                ]);
            }

            // Crear el detalle de la orden
            $orderDetail = OrderDetail::create([
                'order_id' => $order->id,
                'payment_option_id' => $request->payment_option_id,
                'payment_gateway_id' => $request->payment_gateway_id,
                'installment_number' => 1,
                'installments_number' => 1,
                'amount' => $request->amount,
                'due_date' => now(),
                'is_paid' => $request->status === 'completed',
                'status' => $request->status,
                'paid_at' => $request->status === 'completed' ? now() : null,
            ]);

            // Crear el pago
            $payment = Payment::create([
                'order_id' => $order->id,
                'order_detail_id' => $orderDetail->id,
                'payment_gateway_id' => $request->payment_gateway_id,
                'payment_option_id' => $request->payment_option_id,
                'buy_order' => $order->order_number,
                'amount' => $request->amount,
                'status' => $request->status,
                'transaction_date' => $request->transaction_date,
                'authorization_code' => $request->authorization_code,
                'card_number' => $request->card_number,
                'card_type' => $request->card_type,
                'gateway_response' => [
                    'notes' => $request->notes,
                    'created_manually' => true
                ],
                'currency' => 'CLP',
            ]);

            // Actualizar estado de la orden
            $order->refreshStatus();

            DB::commit();

            return redirect()->route('admin.payments.index')
                ->with('success', 'Pago registrado exitosamente.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Error al registrar el pago: ' . $e->getMessage()]);
        }
    }

    public function edit(Payment $payment)
    {
        $payment->load([
            'order', 
            'order.participant', 
            'order.program.course.institution', 
            'orderDetail.country', 
            'orderDetail.region', 
            'orderDetail.city', 
            'paymentGateway', 
            'paymentOption'
        ]);
        
        // Obtener programas con participantes
        $programs = Program::with(['course.participants', 'course.institution'])
            ->where('active', true)
            ->orderBy('name')
            ->get();
            
        $paymentGateways = PaymentGateway::where('active', true)->get();
        $paymentOptions = PaymentOption::where('active', true)->get();

        return Inertia::render('Admin/Payments/Edit', [
            'payment' => $payment,
            'programs' => $programs,
            'paymentGateways' => $paymentGateways,
            'paymentOptions' => $paymentOptions
        ]);
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
            DB::beginTransaction();

            $payment->update([
                'amount' => $request->amount,
                'status' => $request->status,
                'transaction_date' => $request->transaction_date,
                'authorization_code' => $request->authorization_code,
                'card_number' => $request->card_number,
                'card_type' => $request->card_type,
                'gateway_response' => array_merge($payment->gateway_response ?? [], [
                    'notes' => $request->notes,
                    'updated_manually' => true
                ]),
            ]);

            // Actualizar el detalle de la orden
            if ($payment->orderDetail) {
                $payment->orderDetail->update([
                    'amount' => $request->amount,
                    'is_paid' => $request->status === 'completed',
                    'status' => $request->status,
                    'paid_at' => $request->status === 'completed' ? now() : null,
                ]);
            }

            // Actualizar estado de la orden
            if ($payment->order) {
                $payment->order->refreshStatus();
            }

            DB::commit();

            return redirect()->route('admin.payments.index')
                ->with('success', 'Pago actualizado exitosamente.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Error al actualizar el pago: ' . $e->getMessage()]);
        }
    }

    public function destroy(Payment $payment)
    {
        try {
            DB::beginTransaction();

            // Actualizar el detalle de la orden
            if ($payment->orderDetail) {
                $payment->orderDetail->update([
                    'is_paid' => false,
                    'status' => 'pending',
                    'paid_at' => null,
                ]);
            }

            // Actualizar estado de la orden
            if ($payment->order) {
                $payment->order->refreshStatus();
            }

            $payment->delete();

            DB::commit();

            return redirect()->route('admin.payments.index')
                ->with('success', 'Pago eliminado exitosamente.');

        } catch (\Exception $e) {
            DB::rollBack();
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
        $upcomingPayments = Payment::with(['order.participant', 'order.program'])
            ->where('status', 'pending')
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

    public function getParticipantPaymentStatus(Request $request)
    {
        $request->validate([
            'program_id' => 'required|exists:programs,id',
            'participant_id' => 'required|exists:participants,id',
        ]);

        $programId = $request->program_id;
        $participantId = $request->participant_id;

        try {
            // Obtener el participante
            $participant = \App\Models\Participant::find($participantId);
            $program = \App\Models\Program::find($programId);

            if (!$participant || !$program) {
                return response()->json([
                    'success' => false,
                    'message' => 'Participante o programa no encontrado'
                ], 404);
            }

            // Calcular montos usando el helper
            $priceData = \App\Helpers\ParticipantPriceHelper::calculateParticipantPrice($participant, $program);
            $totalAmount = $priceData['final_price'];

            // Buscar planes de cuotas del participante para este programa
            $installmentPlans = \App\Models\InstallmentPlan::where('participant_id', $participantId)
                ->where('program_id', $programId)
                ->with(['installments'])
                ->get();

            // Calcular cuotas pagadas y total de cuotas desde la tabla installments
            $totalInstallments = 0;
            $paidInstallments = 0;
            $totalPaidAmount = 0;

            foreach ($installmentPlans as $plan) {
                $totalInstallments += $plan->total_installments;
                
                foreach ($plan->installments as $installment) {
                    if ($installment->status === 'paid') {
                        $paidInstallments++;
                        $totalPaidAmount += $installment->amount;
                    }
                }
            }

            // Si no hay planes de cuotas, buscar en orders_detail como fallback
            if ($totalInstallments == 0) {
                $orders = \App\Models\Order::where('participant_id', $participantId)
                    ->where('program_id', $programId)
                    ->with(['orderDetails'])
                    ->get();

                foreach ($orders as $order) {
                    $totalInstallments += $order->total_installments;
                    
                    foreach ($order->orderDetails as $detail) {
                        if ($detail->is_paid) {
                            $paidInstallments++;
                            $totalPaidAmount += $detail->amount;
                        }
                    }
                }
            }

            $totalPaidAmount = round($totalPaidAmount, 2);
            $balance = max(round($totalAmount - $totalPaidAmount, 2), 0);
            $paymentPercentage = $totalAmount > 0 ? round(($totalPaidAmount / $totalAmount) * 100, 2) : 0;

            // Determinar estado de inscripción
            $isEnrolled = $program->course && $program->course->participants->contains($participantId);
            
            // Estado de pagos
            $paymentStatus = 'no_enrolled';
            if ($isEnrolled) {
                if ($totalPaidAmount == 0) {
                    $paymentStatus = 'no_payments';
                } elseif ($totalPaidAmount < $totalAmount) {
                    $paymentStatus = 'partial_payments';
                } else {
                    $paymentStatus = 'fully_paid';
                }
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'participant' => [
                        'id' => $participant->id,
                        'name' => $participant->full_name,
                        'document_number' => $participant->document_number,
                        'email' => $participant->email,
                    ],
                    'program' => [
                        'id' => $program->id,
                        'name' => $program->name,
                        'destination' => $program->destination,
                    ],
                    'payment_info' => [
                        'total_amount' => $totalAmount,
                        'paid_amount' => $totalPaidAmount,
                        'balance' => $balance,
                        'payment_percentage' => $paymentPercentage,
                        'payment_status' => $paymentStatus,
                        'is_enrolled' => $isEnrolled,
                        'total_installments' => $totalInstallments,
                        'paid_installments' => $paidInstallments,
                        'installments_summary' => $totalInstallments > 0 ? "{$paidInstallments}/{$totalInstallments}" : "0/0",
                    ],
                    'installment_plans' => $installmentPlans->map(function($plan) {
                        return [
                            'id' => $plan->id,
                            'total_installments' => $plan->total_installments,
                            'total_amount' => $plan->total_amount,
                            'status' => $plan->status,
                            'installments' => $plan->installments->map(function($installment) {
                                return [
                                    'id' => $installment->id,
                                    'installment_number' => $installment->installment_number,
                                    'amount' => $installment->amount,
                                    'status' => $installment->status,
                                    'due_date' => $installment->due_date ? $installment->due_date->format('d/m/Y') : null,
                                    'paid_at' => $installment->paid_at ? $installment->paid_at->format('d/m/Y H:i') : null,
                                ];
                            })
                        ];
                    })
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener el estado de pagos: ' . $e->getMessage()
            ], 500);
        }
    }
}
