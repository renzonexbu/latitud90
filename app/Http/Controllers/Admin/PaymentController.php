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
            'status' => 'required|in:pending,completed,failed,authorized',
            'payment_code' => 'required|string|max:255',
            'authorization_code' => 'nullable|string',
            'notes' => 'nullable|string',
            
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

            // Para pagos presenciales, usar gateway y option fijos
            $paymentGateway = \App\Models\PaymentGateway::where('code', 'presencial')->firstOrFail();
            $paymentOption = \App\Models\PaymentOption::where('code', 'full_debit_credit_0')->firstOrFail();

            // Crear el detalle de la orden
            $orderDetail = OrderDetail::create([
                'order_id' => $order->id,
                'payment_option_id' => $paymentOption->id,
                'payment_gateway_id' => $paymentGateway->id,
                'name' => $request->buyer_full_name,
                'email' => $request->buyer_email,
                'country' => $request->buyer_country,
                'region' => $request->buyer_region,
                'city' => $request->buyer_city,
                'code_phone' => $request->buyer_code_phone,
                'phone' => $request->buyer_phone,
                'document_type' => $request->buyer_document_type,
                'document_number' => $request->buyer_document_number,
                'billing_address' => null, // No tenemos dirección en el formulario
                'billing_city' => $request->buyer_city, // Usar la misma ciudad
                'billing_country' => $request->buyer_country, // Usar el mismo país
                'billing_postal_code' => null, // No tenemos código postal en el formulario
                'terms_accepted' => true,
                'marketing_accepted' => false,
                'terms_accepted_confirmation' => true,
                'installment_number' => 1,
                'base_amount' => $request->amount,
                'discount_amount' => 0,
                'amount' => $request->amount,
                'due_date' => now(),
                'is_paid' => $request->status === 'completed',
                'status' => $request->status === 'completed' ? 'paid' : $request->status,
                'paid_at' => $request->status === 'completed' ? now() : null,
                'gateway_response' => [
                    'notes' => $request->notes,
                    'created_manually' => true,
                    'payment_type' => 'presential'
                ]
            ]);

            // Crear el pago
            $payment = Payment::create([
                'order_id' => $order->id,
                'order_detail_id' => $orderDetail->id,
                'payment_gateway_id' => $paymentGateway->id,
                'payment_option_id' => $paymentOption->id,
                'buy_order' => $order->order_number,
                'amount' => $request->amount,
                'status' => $request->status,
                'transaction_date' => now(), // Usar fecha actual para pagos presenciales
                'accounting_date' => now(), // Fecha contable
                'authorization_code' => $request->authorization_code,
                'payment_code' => $request->payment_code,
                'gateway_response' => [
                    'notes' => $request->notes,
                    'created_manually' => true,
                    'payment_type' => 'presential',
                    'buyer_data' => [
                        'full_name' => $request->buyer_full_name,
                        'document_type' => $request->buyer_document_type,
                        'document_number' => $request->buyer_document_number,
                        'email' => $request->buyer_email,
                        'phone' => $request->buyer_phone,
                        'country' => $request->buyer_country,
                        'region' => $request->buyer_region,
                        'city' => $request->buyer_city,
                    ]
                ],
                'currency' => 'CLP',
            ]);

            // Actualizar estado de la orden
            $order->refreshStatus();

            // Manejar plan de cuotas y reestructuración si es necesario
            if ($request->status === 'completed') {
                $this->handleInstallmentPlan($order, $request->participant_id, $request->program_id, $request->amount);
            }

            DB::commit();

            return redirect()->route('admin.payments.index')
                ->with('success', 'Pago presencial registrado exitosamente. Se han reestructurado las cuotas pendientes.');

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
            try {
                $priceData = \App\Helpers\ParticipantPriceHelper::calculateParticipantPrice($participant, $program);
                $totalAmount = $priceData['final_price'];
            } catch (\Exception $e) {
                // Fallback al precio del programa si el helper falla
                $totalAmount = $program->trip_price ?? 0;
                \Illuminate\Support\Facades\Log::warning('Error calculando precio con helper, usando precio del programa', [
                    'error' => $e->getMessage(),
                    'participant_id' => $participantId,
                    'program_id' => $programId
                ]);
            }

            // Inicializar variables
            $totalInstallments = 0;
            $paidInstallments = 0;
            $totalPaidAmount = 0;
            $orders = collect(); // Inicializar como colección vacía

            // Buscar planes de cuotas del participante para este programa (más confiable)
            $installmentPlans = \App\Models\InstallmentPlan::where('participant_id', $participantId)
                ->where('program_id', $programId)
                ->with(['installments'])
                ->get();

            foreach ($installmentPlans as $plan) {
                $totalInstallments = $plan->installments->count();
                
                foreach ($plan->installments as $installment) {
                    if ($installment->status === 'paid') {
                        $paidInstallments++;
                        // NO sumar aquí el monto, solo contar cuotas
                    }
                }
            }

            // CALCULAR EL MONTO REAL PAGADO desde la tabla payments
            $totalPaidAmount = \App\Models\Payment::whereHas('order', function ($q) use ($participantId, $programId) {
                    $q->where('participant_id', $participantId)
                      ->where('program_id', $programId);
                })
                ->whereIn('status', ['completed', 'approved'])
                ->sum('amount');

            // Si no hay planes de cuotas, buscar en orders como fallback
            if ($totalInstallments == 0) {
                $orders = \App\Models\Order::where('participant_id', $participantId)
                    ->where('program_id', $programId)
                    ->with(['orderDetails', 'payments'])
                    ->get();

                foreach ($orders as $order) {
                    if ($order->orderDetails) {
                        $totalInstallments = $order->orderDetails->count();
                        
                        foreach ($order->orderDetails as $detail) {
                            if ($detail->is_paid) {
                                $paidInstallments++;
                                // NO sumar aquí tampoco, ya calculamos desde payments
                            }
                        }
                    }
                }
            }

            $totalPaidAmount = round($totalPaidAmount, 2);
            $balance = max(round($totalAmount - $totalPaidAmount, 2), 0);
            $paymentPercentage = $totalAmount > 0 ? round(($totalPaidAmount / $totalAmount) * 100, 2) : 0;

            // Validar que los números sean lógicos
            if ($paidInstallments > $totalInstallments) {
                // Corregir el error lógico
                $paidInstallments = min($paidInstallments, $totalInstallments);
            }

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
                    'orders' => $orders->map(function($order) {
                        return [
                            'id' => $order->id,
                            'order_number' => $order->order_number,
                            'total_amount' => $order->total_amount,
                            'final_amount' => $order->final_amount,
                            'total_installments' => $order->total_installments,
                            'status' => $order->status,
                            'created_at' => $order->created_at ? $order->created_at->format('d/m/Y H:i') : null,
                            'order_details' => $order->orderDetails->map(function($detail) {
                                return [
                                    'id' => $detail->id,
                                    'installment_number' => $detail->installment_number,
                                    'amount' => $detail->amount,
                                    'status' => $detail->status,
                                    'is_paid' => $detail->is_paid,
                                    'due_date' => $detail->due_date ? $detail->due_date->format('d/m/Y') : null,
                                    'paid_at' => $detail->paid_at ? $detail->paid_at->format('d/m/Y H:i') : null,
                                ];
                            }),
                            'payments' => $order->payments->map(function($payment) {
                                return [
                                    'id' => $payment->id,
                                    'amount' => $payment->amount,
                                    'status' => $payment->status,
                                    'payment_code' => $payment->payment_code,
                                    'created_at' => $payment->created_at ? $payment->created_at->format('d/m/Y H:i') : null,
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

    /**
     * Manejar plan de cuotas y reestructuración
     */
    private function handleInstallmentPlan(Order $order, int $participantId, int $programId, float $paymentAmount): void
    {
        try {
            // Calcular monto total del programa
            $program = \App\Models\Program::findOrFail($programId);
            $participant = \App\Models\Participant::findOrFail($participantId);
            
            $priceData = \App\Helpers\ParticipantPriceHelper::calculateParticipantPrice($participant, $program);
            $totalAmount = $priceData['final_price'];
            
            // Calcular monto ya pagado
            $paidAmount = \App\Models\Payment::whereHas('order', function ($q) use ($participantId, $programId) {
                    $q->where('participant_id', $participantId)
                      ->where('program_id', $programId);
                })
                ->whereIn('status', ['completed', 'approved'])
                ->sum('amount');
            
            $newPaidAmount = $paidAmount + $paymentAmount;
            
            // Buscar plan de cuotas existente
            $installmentPlan = \App\Models\InstallmentPlan::where('participant_id', $participantId)
                                                         ->where('program_id', $programId)
                                                         ->first();

            if (!$installmentPlan) {
                // Crear nuevo plan de cuotas si no existe
                $installmentPlan = \App\Models\InstallmentPlan::create([
                    'order_id' => $order->id,
                    'program_id' => $programId,
                    'participant_id' => $participantId,
                    'total_amount' => $totalAmount,
                    'total_installments' => 1,
                    'payment_type' => 'monthly',
                    'status' => 'active',
                    'start_date' => now(),
                    'notes' => 'Plan de cuotas creado desde pago presencial'
                ]);

                // Crear cuota inicial
                \App\Models\Installment::create([
                    'installment_plan_id' => $installmentPlan->id,
                    'installment_number' => 1,
                    'amount' => $totalAmount,
                    'due_date' => now(),
                    'status' => 'pending',
                    'notes' => 'Cuota inicial del programa'
                ]);
            }

            // Reestructurar cuotas pendientes
            $this->restructureInstallments($installmentPlan, $totalAmount, $newPaidAmount);
            
            // También actualizar OrderDetail para mantener consistencia
            $this->updateOrderDetailsFromInstallments($order, $installmentPlan);

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Error manejando plan de cuotas', [
                'error' => $e->getMessage(),
                'participant_id' => $participantId,
                'program_id' => $programId
            ]);
        }
    }

    /**
     * Reestructurar cuotas pendientes
     */
    private function restructureInstallments(\App\Models\InstallmentPlan $installmentPlan, float $totalAmount, float $paidAmount): void
    {
        $remainingBalance = max($totalAmount - $paidAmount, 0);
        
        if ($remainingBalance <= 0) {
            // Si ya está completamente pagado, marcar todas las cuotas como pagadas
            $installmentPlan->installments()->update(['status' => 'paid', 'paid_at' => now()]);
            $installmentPlan->update(['status' => 'completed', 'end_date' => now()]);
            return;
        }

        // Obtener cuotas pendientes
        $pendingInstallments = $installmentPlan->installments()
            ->where('status', 'pending')
            ->orderBy('installment_number')
            ->get();

        if ($pendingInstallments->isEmpty()) {
            return;
        }

        // Calcular cuántas cuotas quedan por pagar
        $remainingInstallments = $pendingInstallments->count();
        
        // Distribuir el saldo restante entre las cuotas pendientes
        $amountPerInstallment = $remainingBalance / $remainingInstallments;
        $remainder = $remainingBalance - ($amountPerInstallment * $remainingInstallments);

        foreach ($pendingInstallments as $index => $installment) {
            $installmentAmount = $amountPerInstallment;
            
            // Distribuir centavos restantes en las primeras cuotas
            if ($remainder > 0) {
                $installmentAmount += 0.01;
                $remainder -= 0.01;
            }

            $installment->update([
                'amount' => round($installmentAmount, 2),
                'adjusted_at' => now(),
                'adjustment_reason' => 'Reestructuración por pago presencial'
            ]);
        }

        // Actualizar el plan
        $installmentPlan->update([
            'total_installments' => $pendingInstallments->count()
        ]);

        \Illuminate\Support\Facades\Log::info('Cuotas reestructuradas exitosamente', [
            'installment_plan_id' => $installmentPlan->id,
            'total_amount' => $totalAmount,
            'paid_amount' => $paidAmount,
            'remaining_balance' => $remainingBalance,
            'remaining_installments' => $remainingInstallments
        ]);
    }

    /**
     * Actualizar OrderDetail basado en Installments para mantener consistencia
     */
    private function updateOrderDetailsFromInstallments(Order $order, \App\Models\InstallmentPlan $installmentPlan): void
    {
        try {
            // Obtener todas las cuotas del plan
            $installments = $installmentPlan->installments()->orderBy('installment_number')->get();
            
            // Eliminar OrderDetails existentes
            $order->orderDetails()->delete();
            
            // Crear nuevos OrderDetails basados en las cuotas
            foreach ($installments as $installment) {
                $order->orderDetails()->create([
                    'payment_option_id' => $order->orderDetails->first()->payment_option_id ?? null,
                    'payment_gateway_id' => $order->orderDetails->first()->payment_gateway_id ?? null,
                    'name' => $order->orderDetails->first()->name ?? 'Participante',
                    'email' => $order->orderDetails->first()->email ?? '',
                    'country' => $order->orderDetails->first()->country ?? null,
                    'region' => $order->orderDetails->first()->region ?? null,
                    'city' => $order->orderDetails->first()->city ?? null,
                    'code_phone' => $order->orderDetails->first()->code_phone ?? null,
                    'phone' => $order->orderDetails->first()->phone ?? null,
                    'document_type' => $order->orderDetails->first()->document_type ?? null,
                    'document_number' => $order->orderDetails->first()->document_number ?? null,
                    'installment_number' => $installment->installment_number,
                    'base_amount' => $installment->amount,
                    'discount_amount' => 0,
                    'amount' => $installment->amount,
                    'due_date' => $installment->due_date,
                    'is_paid' => $installment->status === 'paid',
                    'status' => $installment->status === 'paid' ? 'paid' : 'pending',
                    'paid_at' => $installment->status === 'paid' ? $installment->paid_at : null,
                    'gateway_response' => [
                        'notes' => 'Actualizado desde InstallmentPlan',
                        'installment_id' => $installment->id
                    ]
                ]);
            }
            
            // Actualizar el total de cuotas en la orden
            $order->update([
                'total_installments' => $installments->count()
            ]);
            
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Error actualizando OrderDetails desde Installments', [
                'error' => $e->getMessage(),
                'order_id' => $order->id,
                'installment_plan_id' => $installmentPlan->id
            ]);
        }
    }
}
