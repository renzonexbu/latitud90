<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Participant;
use App\Models\Program;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use App\Helpers\ParticipantPriceHelper;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        // Fechas por defecto (último mes)
        $dateFrom = $request->date_from ?? Carbon::now()->subDays(30)->format('Y-m-d');
        $dateTo = $request->date_to ?? Carbon::now()->format('Y-m-d');
        $programId = $request->program;

        // Consulta base con filtros
        $paymentsQuery = Payment::where('status', 'completed');

        $passengersQuery = Participant::query();

        if ($programId) {
            $paymentsQuery->whereHas('order.program', function($q) use ($programId) {
                $q->where('id', $programId);
            });
            $passengersQuery->whereHas('programs', function($q) use ($programId) {
                $q->where('program_id', $programId);
            });
        }

        // Estadísticas principales
        $totalRevenue = $paymentsQuery->sum('amount');
        $totalReservations = $passengersQuery->count();
        $totalVisitors = $passengersQuery->count(); // Simplificado, podría ser más complejo
        $conversionRate = $totalVisitors > 0 ? round(($totalReservations / $totalVisitors) * 100, 2) : 0;
        $averageOrderValue = $totalReservations > 0 ? round($totalRevenue / $totalReservations, 2) : 0;

        // Debug logs
        \Illuminate\Support\Facades\Log::info('ReportController Debug', [
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
            'totalRevenue' => $totalRevenue,
            'totalReservations' => $totalReservations,
            'totalPayments' => Payment::count(),
            'totalParticipants' => Participant::count(),
            'programsCount' => Program::count()
        ]);

        // Programas más populares
        $popularPrograms = Program::withCount('participants')
            ->orderBy('participants_count', 'desc')
            ->limit(5)
            ->get()
            ->map(function($program) {
                // Calcular ingresos totales del programa
                $totalRevenue = Payment::whereHas('order.program', function($q) use ($program) {
                    $q->where('id', $program->id);
                })
                ->where('status', 'completed')
                ->sum('amount');

                return [
                    'id' => $program->id,
                    'name' => $program->name,
                    'reservations_count' => $program->participants_count,
                    'total_revenue' => $totalRevenue
                ];
            });

        // Métodos de pago
        $paymentMethods = Payment::where('status', 'completed')
            ->selectRaw('payment_gateway_id as gateway, COUNT(*) as count, SUM(amount) as total')
            ->groupBy('payment_gateway_id')
            ->get()
            ->map(function($item) use ($totalRevenue) {
                return [
                    'gateway' => $item->gateway ?? 'unknown',
                    'count' => $item->count,
                    'total' => $item->total,
                    'percentage' => $totalRevenue > 0 ? round(($item->total / $totalRevenue) * 100, 2) : 0
                ];
            });

        // Datos del reporte
        $reportData = [
            'totalRevenue' => $totalRevenue,
            'totalReservations' => $totalReservations,
            'conversionRate' => $conversionRate,
            'averageOrderValue' => $averageOrderValue,
            'popularPrograms' => $popularPrograms,
            'paymentMethods' => $paymentMethods
        ];

        // Lista de programas para el filtro
        $programs = Program::select('id', 'name')->get();

        return Inertia::render('Admin/Reports/Index', [
            'totalRevenue' => $totalRevenue,
            'totalParticipants' => $totalReservations,
            'totalPrograms' => $programs->count(),
            'paymentMethods' => $paymentMethods,
            'popularPrograms' => $popularPrograms,
            'programs' => $programs,
            'filters' => [
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
                'program' => $programId
            ],
            'chartData' => [
                'labels' => ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio'],
                'datasets' => [
                    [
                        'label' => 'Ingresos',
                        'data' => [12000, 19000, 15000, 25000, 22000, 30000],
                        'borderColor' => '#3B82F6',
                        'backgroundColor' => 'rgba(59, 130, 246, 0.1)'
                    ]
                ]
            ]
        ]);
    }

    public function export(Request $request)
    {
        $format = $request->format ?? 'csv';
        $dateFrom = $request->date_from ?? Carbon::now()->subMonth()->format('Y-m-d');
        $dateTo = $request->date_to ?? Carbon::now()->format('Y-m-d');
        $programId = $request->program;

        // Obtener datos
        $query = Payment::with(['order.participant', 'order.program'])
            ->whereBetween('created_at', [$dateFrom, $dateTo])
            ->where('status', 'completed');

        if ($programId) {
            $query->whereHas('order.program', function($q) use ($programId) {
                $q->where('id', $programId);
            });
        }

        $payments = $query->get();

        switch ($format) {
            case 'csv':
                return $this->exportCSV($payments, $dateFrom, $dateTo);
            case 'excel':
                return $this->exportExcel($payments, $dateFrom, $dateTo);
            case 'pdf':
                return $this->exportPDF($payments, $dateFrom, $dateTo);
            default:
                return $this->exportCSV($payments, $dateFrom, $dateTo);
        }
    }

    private function exportCSV($payments, $dateFrom, $dateTo)
    {
        $filename = "reporte_ventas_{$dateFrom}_a_{$dateTo}.csv";

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function() use ($payments) {
            $file = fopen('php://output', 'w');

            // Encabezados CSV
            fputcsv($file, [
                'Fecha', 'ID Pago', 'Pasajero', 'Email', 'Programa',
                'Método Pago', 'Monto', 'Estado'
            ]);

            foreach ($payments as $payment) {
                fputcsv($file, [
                    $payment->created_at->format('Y-m-d'),
                    $payment->id,
                    $payment->order->participant->first_name . ' ' . $payment->order->participant->last_name,
                    $payment->order->participant->email,
                    $payment->order->program->name,
                    $payment->payment_gateway_id,
                    $payment->amount,
                    $payment->status
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    private function exportExcel($payments, $dateFrom, $dateTo)
    {
        // Implementar exportación a Excel usando Laravel Excel
        // return Excel::download(new PaymentsExport($payments), "reporte_ventas_{$dateFrom}_a_{$dateTo}.xlsx");

        // Por ahora, devolver CSV como fallback
        return $this->exportCSV($payments, $dateFrom, $dateTo);
    }

    private function exportPDF($payments, $dateFrom, $dateTo)
    {
        // Implementar exportación a PDF
        // $pdf = PDF::loadView('admin.reports.pdf', compact('payments', 'dateFrom', 'dateTo'));
        // return $pdf->download("reporte_ventas_{$dateFrom}_a_{$dateTo}.pdf");

        // Por ahora, devolver CSV como fallback
        return $this->exportCSV($payments, $dateFrom, $dateTo);
    }

    public function salesChart(Request $request)
    {
        $period = $request->period ?? 'daily';
        $dateFrom = $request->date_from ?? Carbon::now()->subMonth()->format('Y-m-d');
        $dateTo = $request->date_to ?? Carbon::now()->format('Y-m-d');

        $query = Payment::whereBetween('created_at', [$dateFrom, $dateTo])
            ->where('status', 'completed');

        switch ($period) {
            case 'daily':
                $data = $query->selectRaw('DATE(created_at) as date, SUM(amount) as total, COUNT(*) as count')
                    ->groupBy('date')
                    ->orderBy('date')
                    ->get();
                break;
            case 'weekly':
                $data = $query->selectRaw('YEARWEEK(created_at) as week, SUM(amount) as total, COUNT(*) as count')
                    ->groupBy('week')
                    ->orderBy('week')
                    ->get();
                break;
            case 'monthly':
                $data = $query->selectRaw('DATE_FORMAT(created_at, "%Y-%m") as month, SUM(amount) as total, COUNT(*) as count')
                    ->groupBy('month')
                    ->orderBy('month')
                    ->get();
                break;
            default:
                $data = collect();
        }

        return response()->json($data);
    }

    public function dailyPayments(Request $request)
    {
        $filters = $request->only(['programId', 'executiveId', 'paymentMethod', 'dateFrom', 'dateTo']);
        
        $query = Payment::with([
            'order.participant',
            'order.program',
            'orderDetail',
            'paymentGateway'
        ])
        ->where('status', 'completed');

        // Aplicar filtros
        if (!empty($filters['programId'])) {
            $query->whereHas('order.program', function($q) use ($filters) {
                $q->where('id', $filters['programId']);
            });
        }

        if (!empty($filters['executiveId'])) {
            $query->whereHas('order.program', function($q) use ($filters) {
                $q->where('sales_executive_id', $filters['executiveId']);
            });
        }

        if (!empty($filters['paymentMethod'])) {
            $query->whereHas('paymentGateway', function($q) use ($filters) {
                $q->where('code', $filters['paymentMethod']);
            });
        }

        if (!empty($filters['dateFrom'])) {
            $query->where('created_at', '>=', $filters['dateFrom']);
        }

        if (!empty($filters['dateTo'])) {
            $query->where('created_at', '<=', $filters['dateTo'] . ' 23:59:59');
        }

        $payments = $query->get()->map(function($payment) {
            return [
                'id' => $payment->id,
                'participant_name' => $payment->order->participant->full_name ?? 'N/A',
                'program_name' => $payment->order->program->name ?? 'N/A',
                'program_price' => $payment->order->program->price ?? 0,
                'amount' => $payment->amount,
                'released_amount' => 0, // TODO: Implementar lógica de monto liberado
                'external_contribution' => 0, // TODO: Implementar lógica de aporte externo
                'remaining_balance' => 0, // TODO: Implementar lógica de saldo pendiente
                'created_at' => $payment->created_at
            ];
        });

        return Inertia::render('Admin/Reports/DailyPayments', [
            'dailyPayments' => $payments,
            'programs' => Program::all(['id', 'name']),
            'executives' => \App\Models\SalesExecutive::all(['id', 'name']),
            'filters' => $filters
        ]);
    }

    public function consolidatedPayments(Request $request)
    {
        $filters = $request->only(['dateFrom', 'dateTo', 'paymentMethod', 'transactionType']);
        
        $query = Payment::with([
            'order.participant',
            'order.program',
            'orderDetail',
            'paymentGateway'
        ])
        ->where('status', 'completed');

        // Aplicar filtros
        if (!empty($filters['dateFrom'])) {
            $query->where('created_at', '>=', $filters['dateFrom']);
        }

        if (!empty($filters['dateTo'])) {
            $query->where('created_at', '<=', $filters['dateTo'] . ' 23:59:59');
        }

        if (!empty($filters['paymentMethod'])) {
            $query->whereHas('paymentGateway', function($q) use ($filters) {
                $q->where('code', $filters['paymentMethod']);
            });
        }

        if (!empty($filters['transactionType'])) {
            if ($filters['transactionType'] === 'refund') {
                $query->where('amount', '<', 0);
            } else {
                $query->where('amount', '>', 0);
            }
        }

        $payments = $query->get()->map(function($payment) {
            return [
                'id' => $payment->id,
                'program_id' => $payment->order->program->id ?? 'N/A',
                'authorization_number' => $payment->gateway_response['authorization_code'] ?? null,
                'participant_document' => $payment->order->participant->document_number ?? 'N/A',
                'amount' => $payment->amount,
                'receipt_number' => $payment->gateway_response['receipt_number'] ?? null,
                'payment_method' => $payment->paymentGateway->code ?? 'N/A',
                'installments_number' => $payment->order->total_installments ?? 1,
                'created_at' => $payment->created_at,
                'buyer_name' => $payment->orderDetail->name ?? 'N/A',
                'buyer_email' => $payment->orderDetail->email ?? 'N/A'
            ];
        });

        return Inertia::render('Admin/Reports/ConsolidatedPayments', [
            'consolidatedPayments' => $payments,
            'filters' => $filters
        ]);
    }

    public function installmentSchedule(Request $request)
    {
        $filters = $request->only(['programId', 'installmentStatus', 'dateFrom']);
        
        // TODO: Implementar lógica de cuotas
        // Por ahora retornamos datos de ejemplo
        $installments = collect();

        return Inertia::render('Admin/Reports/InstallmentSchedule', [
            'installmentSchedule' => $installments,
            'programs' => Program::all(['id', 'name']),
            'filters' => $filters
        ]);
    }

    public function revenueChart(Request $request)
    {
        $filters = $request->only(['period', 'dateFrom', 'dateTo']);
        
        $period = $filters['period'] ?? 'daily';
        $dateFrom = $filters['dateFrom'] ?? Carbon::now()->subDays(30)->format('Y-m-d');
        $dateTo = $filters['dateTo'] ?? Carbon::now()->format('Y-m-d');

        // Generar datos de ingresos por período
        $revenueData = $this->generateRevenueData($period, $dateFrom, $dateTo);
        
        // Datos de métodos de pago
        $paymentMethodsData = Payment::where('status', 'completed')
            ->whereBetween('created_at', [$dateFrom, $dateTo])
            ->join('payment_gateways', 'payments.payment_gateway_id', '=', 'payment_gateways.id')
            ->selectRaw('payment_gateways.name as method, SUM(payments.amount) as amount')
            ->groupBy('payment_gateway_id', 'payment_gateways.name')
            ->get();

        return Inertia::render('Admin/Reports/RevenueChart', [
            'revenueData' => $revenueData,
            'paymentMethodsData' => $paymentMethodsData,
            'filters' => $filters
        ]);
    }

    private function generateRevenueData($period, $dateFrom, $dateTo)
    {
        $data = [];
        $startDate = Carbon::parse($dateFrom);
        $endDate = Carbon::parse($dateTo);

        while ($startDate <= $endDate) {
            $date = $startDate->format('Y-m-d');
            
            $revenue = Payment::where('status', 'completed')
                ->whereDate('created_at', $date)
                ->sum('amount');

            $transactions = Payment::where('status', 'completed')
                ->whereDate('created_at', $date)
                ->count();

            $data[] = [
                'date' => $startDate->format('d/m/Y'),
                'revenue' => $revenue,
                'transactions' => $transactions
            ];

            if ($period === 'daily') {
                $startDate->addDay();
            } elseif ($period === 'weekly') {
                $startDate->addWeek();
            } else {
                $startDate->addMonth();
            }
        }

        return $data;
    }

    public function exportDailyPayments(Request $request)
    {
        // TODO: Implementar exportación a CSV
        return response()->json(['message' => 'Exportación implementada']);
    }

    public function exportConsolidatedPayments(Request $request)
    {
        // TODO: Implementar exportación a CSV
        return response()->json(['message' => 'Exportación implementada']);
    }

    public function partialAccount(Request $request)
    {
        $filters = $request->only(['programId', 'participantId', 'dateFrom', 'dateTo', 'page']);
        
        // Usar la misma lógica que funciona en ParticipantsController
        $query = DB::table('participants as p')
            ->leftJoin('participant_program as pp', 'pp.participant_id', '=', 'p.id')
            ->leftJoin('programs as pr', 'pr.id', '=', 'pp.program_id')
            ->leftJoin('courses as c', 'c.program_id', '=', 'pr.id')
            ->leftJoin('institutions as i', 'i.id', '=', 'c.institution_id')
            ->leftJoin('orders as o', function($join) {
                $join->on('o.participant_id', '=', 'p.id')
                     ->on('o.program_id', '=', 'pr.id');
            })
            ->leftJoin('orders_detail as od', function ($join) {
                $join->on('od.order_id', '=', 'o.id')
                    ->where('od.is_paid', true);
            });

        // Aplicar filtros
        if (!empty($filters['programId'])) {
            $query->where('pr.id', $filters['programId']);
        }

        if (!empty($filters['participantId'])) {
            $query->where('p.id', $filters['participantId']);
        }

        if (!empty($filters['dateFrom'])) {
            $query->where('pp.created_at', '>=', $filters['dateFrom']);
        }

        if (!empty($filters['dateTo'])) {
            $query->where('pp.created_at', '<=', $filters['dateTo'] . ' 23:59:59');
        }

        $enrollmentsBase = $query
            ->groupBy([
                'p.id', 'p.first_name', 'p.last_name', 'p.email', 'p.document_number', 'p.phone',
                'pp.id', 'pp.enrollment_code', 'pp.individual_price', 'pp.status', 'pp.created_at',
                'pr.id', 'pr.name', 'pr.departure_date',
                'c.education_level', 'c.course_number',
                'i.name',
            ])
            ->select([
                'p.id as participant_id',
                'p.first_name',
                'p.last_name',
                'p.email',
                'p.document_number',
                'p.phone',
                'pp.id as participant_program_id',
                'pp.enrollment_code',
                'pp.individual_price',
                'pp.status as enrollment_status',
                'pp.created_at',
                'pr.id as program_id',
                'pr.name as program_name',
                'pr.departure_date',
                'c.education_level',
                'c.course_number',
                'i.name as institution_name',
                DB::raw('COALESCE(SUM(od.amount), 0) as paid_amount'),
            ])
            ->orderByDesc('pp.created_at')
            ->paginate(10); // Paginación de 10 registros por página

        $partialAccounts = collect($enrollmentsBase->items())->map(function($enrollment) {
            // Usar el mismo helper que usa ParticipantsController
            $participant = Participant::find($enrollment->participant_id);
            $program = Program::find($enrollment->program_id);
            
            $priceData = null;
            if ($participant && $program) {
                $priceData = ParticipantPriceHelper::calculateParticipantPrice($participant, $program);
            }
            
            $totalAmount = $enrollment->individual_price ?? 0;
            $totalDiscounts = $priceData ? $priceData['discounts'] : 0;
            $netAmount = $priceData ? $priceData['final_price'] : $totalAmount;
            $totalPaid = $enrollment->paid_amount ?? 0;
            $pendingAmount = $netAmount - $totalPaid;
            
            // Determinar estado
            $status = 'pending';
            if ($pendingAmount <= 0) {
                $status = 'paid';
            } elseif ($pendingAmount < $netAmount) {
                $status = 'partial';
            }

            // Calcular porcentaje de avance
            $progressPercentage = $netAmount > 0 ? round(($totalPaid / $netAmount) * 100, 2) : 0;

            return [
                'id' => $enrollment->participant_program_id,
                'participant_id' => $enrollment->participant_id,
                'program_id' => $enrollment->program_id,
                'created_at' => $enrollment->created_at,
                'participant_name' => $enrollment->first_name . ' ' . $enrollment->last_name,
                'participant_email' => $enrollment->email,
                'participant_document' => $enrollment->document_number,
                'participant_phone' => $enrollment->phone,
                'program_name' => $enrollment->program_name,
                'program_departure_date' => $enrollment->departure_date,
                'enrollment_code' => $enrollment->enrollment_code,
                'total_amount' => $totalAmount,
                'total_discounts' => $totalDiscounts,
                'net_amount' => $netAmount,
                'total_paid' => $totalPaid,
                'pending_amount' => $pendingAmount,
                'progress_percentage' => $progressPercentage,
                'status' => $status,
                'payment_history' => [], // TODO: Implementar si es necesario
                'upcoming_payments' => [], // TODO: Implementar si es necesario
                'discounts_detail' => [] // TODO: Implementar si es necesario
            ];
        });

        // Crear objeto de paginación personalizado
        $paginatedAccounts = new \Illuminate\Pagination\LengthAwarePaginator(
            $partialAccounts,
            $enrollmentsBase->total(),
            $enrollmentsBase->perPage(),
            $enrollmentsBase->currentPage(),
            [
                'path' => request()->url(),
                'pageName' => 'page',
            ]
        );

        // Debug logs finales
        \Illuminate\Support\Facades\Log::info('PartialAccount Response', [
            'total' => $paginatedAccounts->total(),
            'per_page' => $paginatedAccounts->perPage(),
            'current_page' => $paginatedAccounts->currentPage(),
            'last_page' => $paginatedAccounts->lastPage(),
            'items_count' => $paginatedAccounts->count(),
        ]);

        return Inertia::render('Admin/Reports/PartialAccount', [
            'partialAccounts' => $paginatedAccounts,
            'programs' => Program::all(['id', 'name']),
            'participants' => Participant::all(['id', 'first_name', 'last_name']),
            'filters' => $filters
        ]);
    }

    public function paymentSchedule(Request $request)
    {
        $filters = $request->only(['programId', 'dateFrom', 'dateTo', 'status']);
        
        // TODO: Implementar lógica de cronograma de recuperación
        // Por ahora retornamos datos de ejemplo
        $paymentSchedules = collect();

        return Inertia::render('Admin/Reports/PaymentSchedule', [
            'paymentSchedules' => $paymentSchedules,
            'programs' => Program::all(['id', 'name']),
            'filters' => $filters
        ]);
    }

    public function exportPartialAccount(Request $request)
    {
        // TODO: Implementar exportación a CSV
        return response()->json(['message' => 'Exportación implementada']);
    }

    public function exportPaymentSchedule(Request $request)
    {
        // TODO: Implementar exportación a CSV
        return response()->json(['message' => 'Exportación implementada']);
    }
}
