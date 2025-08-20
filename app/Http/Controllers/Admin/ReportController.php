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
use App\Services\Admin\Reports\RecoverySchedule\RecoveryScheduleService;
use App\Services\Admin\Reports\RecoverySchedule\ExportService;
use App\Services\Admin\Reports\DailyPayments\DailyPaymentsService;
use App\Services\Admin\Reports\DailyPayments\ExportService as DailyPaymentsExportService;
use App\Services\Admin\Reports\ConsolidatedPayments\ConsolidatedPaymentsService;
use App\Services\Admin\Reports\ConsolidatedPayments\ExportService as ConsolidatedPaymentsExportService;

class ReportController extends Controller
{
    public function __construct(
        private RecoveryScheduleService $recoveryScheduleService,
        private ExportService $exportService,
        private DailyPaymentsService $dailyPaymentsService,
        private DailyPaymentsExportService $dailyPaymentsExportService,
        private ConsolidatedPaymentsService $consolidatedPaymentsService,
        private ConsolidatedPaymentsExportService $consolidatedPaymentsExportService
    ) {}

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
            $paymentsQuery->whereHas('order.program', function ($q) use ($programId) {
                $q->where('id', $programId);
            });
            $passengersQuery->whereHas('programs', function ($q) use ($programId) {
                $q->where('program_id', $programId);
            });
        }

        // Estadísticas principales
        $totalRevenue = $paymentsQuery->sum('amount');
        $totalReservations = $passengersQuery->count();
        $totalVisitors = $passengersQuery->count(); // Simplificado, podría ser más complejo
        $conversionRate = $totalVisitors > 0 ? round(($totalReservations / $totalVisitors) * 100, 2) : 0;
        $averageOrderValue = $totalReservations > 0 ? round($totalRevenue / $totalReservations, 2) : 0;

        // Programas más populares
        $popularPrograms = Program::withCount('participants')
            ->orderBy('participants_count', 'desc')
            ->limit(5)
            ->get()
            ->map(function ($program) {
                // Calcular ingresos totales del programa
                $totalRevenue = Payment::whereHas('order.program', function ($q) use ($program) {
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
            ->map(function ($item) use ($totalRevenue) {
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
            $query->whereHas('order.program', function ($q) use ($programId) {
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

        $callback = function () use ($payments) {
            $file = fopen('php://output', 'w');

            // Encabezados CSV
            fputcsv($file, [
                'Fecha',
                'ID Pago',
                'Pasajero',
                'Email',
                'Programa',
                'Método Pago',
                'Monto',
                'Estado'
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



    public function consolidatedPayments(Request $request)
    {
        $filters = $request->only([
            'paymentMethodId',
            'dateFrom',
            'dateTo',
            'page'
        ]);
        
        // Obtener datos paginados
        $page = $request->get('page', 1);
        $consolidatedPayments = $this->consolidatedPaymentsService->getConsolidatedPayments($filters, $page);
        
        // Obtener resumen
        $summary = $this->consolidatedPaymentsService->getSummary($filters);
        
        // Obtener datos para filtros
        $paymentMethods = $this->consolidatedPaymentsService->getPaymentMethods();

        return Inertia::render('Admin/Reports/ConsolidatedPayments', [
            'consolidatedPayments' => $consolidatedPayments,
            'paymentMethods' => $paymentMethods,
            'filters' => $filters,
            'summary' => $summary
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



    public function exportConsolidatedPayments(Request $request)
    {
        $filters = $request->only([
            'paymentMethodId',
            'dateFrom',
            'dateTo'
        ]);
        $fields = json_decode($request->get('fields', '{}'), true);
        $format = $request->get('format', 'xlsx');
        $includeAll = $request->get('include_all', 'current');
        
        try {
            // Obtener datos para exportación
            if ($includeAll === 'all') {
                $exportData = $this->consolidatedPaymentsService->getAllConsolidatedPayments($filters, $fields);
            } else {
                // Solo página actual (implementar lógica si es necesario)
                $exportData = $this->consolidatedPaymentsService->getAllConsolidatedPayments($filters, $fields);
            }
            
            // Validar que tenemos datos para exportar
            if ($exportData->isEmpty()) {
                return response()->json(['error' => 'No hay datos válidos para exportar'], 500);
            }
            
            // Generar nombre de archivo
            $filename = 'consolidado_pagos_' . now()->format('Y-m-d_H-i-s');
            
            // Exportar según el formato
            return $this->consolidatedPaymentsExportService->export($exportData, $filename, $format, $fields);
            
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error al generar el archivo: ' . $e->getMessage()], 500);
        }
    }

    public function partialAccount(Request $request)
    {
        $filters = $request->only(['programId', 'participantId', 'dateFrom', 'dateTo', 'page']);
        
        $partialAccountService = app(\App\Services\Admin\Reports\PartialReport\PartialAccountService::class);

        // Obtener datos del estado de cuenta parcial
        $partialAccounts = $partialAccountService->getPartialAccounts($filters);

        // Obtener datos de filtros
        $filterData = $partialAccountService->getFilterData();

        return Inertia::render('Admin/Reports/PartialAccount', [
            'partialAccounts' => $partialAccounts,
            'programs' => $filterData['programs'],
            'participants' => $filterData['participants'],
            'filters' => $filters
        ]);
    }

    public function paymentSchedule(Request $request)
    {
        $filters = $request->only(['programId', 'dateFrom', 'dateTo', 'status', 'page']);
        
        // Solo aplicar filtros de fecha si el usuario los especifica explícitamente
        // Si no hay filtros, mostrar TODOS los datos
        if (!empty($filters['dateFrom']) && !empty($filters['dateTo'])) {
            // Validar que las fechas sean válidas
            if (strtotime($filters['dateFrom']) > strtotime($filters['dateTo'])) {
                $filters['dateFrom'] = now()->format('Y-m-d');
                $filters['dateTo'] = now()->addMonth()->format('Y-m-d');
            }
        } else {
            // Si no hay filtros de fecha, no aplicar restricciones
            unset($filters['dateFrom']);
            unset($filters['dateTo']);
        }
        
        // Obtener datos del cronograma de cuotas
        $paymentSchedules = $this->recoveryScheduleService->getPaymentSchedules($filters, $filters['page'] ?? 1);
        
        // Obtener resumen
        $summary = $this->recoveryScheduleService->getSummary($filters);
        
        // Obtener programas para filtros
        $programs = $this->recoveryScheduleService->getPrograms();

        return Inertia::render('Admin/Reports/PaymentSchedule', [
            'paymentSchedules' => $paymentSchedules,
            'programs' => $programs,
            'filters' => $filters,
            'summary' => $summary
        ]);
    }

    public function exportPartialAccount(Request $request)
    {
        $filters = $request->only(['programId', 'participantId', 'dateFrom', 'dateTo']);
        $fields = json_decode($request->get('fields', '{}'), true);
        $format = $request->get('format', 'xlsx');
        $includeAll = $request->get('include_all', 'current');
        
        $partialAccountService = app(\App\Services\Admin\Reports\PartialReport\PartialAccountService::class);
        $exportService = app(\App\Services\Admin\Reports\PartialReport\ExportService::class);

        // Validar campos de exportación
        if (!$exportService->validateExportFields($fields)) {
            return response()->json(['error' => 'Debe seleccionar al menos un campo para exportar'], 400);
        }
        
        try {
            // Obtener datos para exportación
            $exportData = $partialAccountService->getExportData($filters, $fields, $includeAll);

        // Validar que tenemos datos para exportar
        if ($exportData->isEmpty()) {
            return response()->json(['error' => 'No hay datos válidos para exportar'], 400);
        }

            // Generar nombre de archivo
            $filename = $exportService->generateFilename('estado_cuenta_parcial');

            // Exportar según el formato
            return $exportService->export($exportData, $format, $filename);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Export Error', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            return response()->json(['error' => 'Error al generar el archivo: ' . $e->getMessage()], 500);
        }
    }





    public function exportPaymentSchedule(Request $request)
    {
        $filters = $request->only(['programId', 'dateFrom', 'dateTo', 'status']);
        $fields = json_decode($request->get('fields', '{}'), true);
        $format = $request->get('format', 'xlsx');
        $includeAll = $request->get('include_all', 'current');
        
        try {
            // Obtener datos para exportación
            if ($includeAll === 'all') {
                $exportData = $this->recoveryScheduleService->getAllPaymentSchedules($filters, $fields);
            } else {
                // Solo página actual (implementar lógica si es necesario)
                $exportData = $this->recoveryScheduleService->getAllPaymentSchedules($filters, $fields);
            }
            
            // Validar que tenemos datos para exportar
            if ($exportData->isEmpty()) {
                return response()->json(['error' => 'No hay datos válidos para exportar'], 500);
            }
            
            // Generar nombre de archivo
            $filename = 'cronograma_cuotas_' . now()->format('Y-m-d_H-i-s');
            
            // Exportar según el formato
            return $this->exportService->export($exportData, $filename, $format, $fields);
            
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Export Payment Schedule Error', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            return response()->json(['error' => 'Error al generar el archivo: ' . $e->getMessage()], 500);
        }
    }

    public function dailyPayments(Request $request)
    {
        $filters = $request->only([
            'programId',
            'salesExecutiveId',
            'financingType',
            'paymentMethodId',
            'dateFrom',
            'dateTo',
            'page'
        ]);
        
        // Obtener datos paginados
        $page = $request->get('page', 1);
        $dailyPayments = $this->dailyPaymentsService->getDailyPayments($filters, $page);
        
        // Obtener resumen
        $summary = $this->dailyPaymentsService->getSummary($filters);
        
        // Obtener datos para filtros
        $programs = $this->dailyPaymentsService->getPrograms();
        $salesExecutives = $this->dailyPaymentsService->getSalesExecutives();
        $financingTypes = $this->dailyPaymentsService->getFinancingTypes();
        $paymentMethods = $this->dailyPaymentsService->getPaymentMethods();

        return Inertia::render('Admin/Reports/DailyPayments', [
            'dailyPayments' => $dailyPayments,
            'programs' => $programs,
            'salesExecutives' => $salesExecutives,
            'financingTypes' => $financingTypes,
            'paymentMethods' => $paymentMethods,
            'filters' => $filters,
            'summary' => $summary
        ]);
    }

    public function exportDailyPayments(Request $request)
    {
        $filters = $request->only([
            'programId',
            'salesExecutiveId',
            'financingType',
            'paymentMethodId',
            'dateFrom',
            'dateTo'
        ]);
        $fields = json_decode($request->get('fields', '{}'), true);
        $format = $request->get('format', 'xlsx');
        $includeAll = $request->get('include_all', 'current');
        
        try {
            // Obtener datos para exportación
            if ($includeAll === 'all') {
                $exportData = $this->dailyPaymentsService->getAllDailyPayments($filters, $fields);
            } else {
                // Solo página actual (implementar lógica si es necesario)
                $exportData = $this->dailyPaymentsService->getAllDailyPayments($filters, $fields);
            }
            
            // Validar que tenemos datos para exportar
            if ($exportData->isEmpty()) {
                return response()->json(['error' => 'No hay datos válidos para exportar'], 500);
            }
            
            // Generar nombre de archivo
            $filename = 'pagos_diarios_' . now()->format('Y-m-d_H-i-s');
            
            // Exportar según el formato
            return $this->dailyPaymentsExportService->export($exportData, $filename, $format, $fields);
            
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Export Daily Payments Error', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            return response()->json(['error' => 'Error al generar el archivo: ' . $e->getMessage()], 500);
        }
    }

}
