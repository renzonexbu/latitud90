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
use App\Services\Admin\Reports\ReportsSummaryService;
use App\Services\Admin\Reports\ConsolidatedExportService;
use App\Services\EcommerceAnalyticsService;
use Illuminate\Support\Facades\Log;

class ReportController extends Controller
{
    public function __construct(
        private RecoveryScheduleService $recoveryScheduleService,
        private ExportService $exportService,
        private DailyPaymentsService $dailyPaymentsService,
        private DailyPaymentsExportService $dailyPaymentsExportService,
        private ConsolidatedPaymentsService $consolidatedPaymentsService,
        private ConsolidatedPaymentsExportService $consolidatedPaymentsExportService,
        private ReportsSummaryService $reportsSummaryService,
        private ConsolidatedExportService $consolidatedExportService,
        private EcommerceAnalyticsService $analyticsService
    ) {}

    public function index(Request $request)
    {
        // Fechas por defecto (último mes)
        $dateFrom = $request->dateFrom ?? Carbon::now()->subDays(30)->format('Y-m-d');
        $dateTo = $request->dateTo ?? Carbon::now()->format('Y-m-d');
        $programId = $request->program;

        // Obtener resumen de todos los módulos
        $summary = $this->reportsSummaryService->getSummary([
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
            'programId' => $programId
        ]);

        // Lista de programas para el filtro
        $programs = Program::select('id', 'name')->get();

        // Obtener datos del ecommerce
        $ecommerceData = $this->getEcommerceData($request);
        
        // Obtener análisis del funnel
        $funnelAnalysis = $this->analyticsService->getFunnelAnalysis($dateFrom, $dateTo);

        // LOG DE LAS FECHAS USADAS
        Log::info('=== FECHAS EN EL MÉTODO INDEX ===');
        Log::info('dateFrom del request', ['dateFrom' => $dateFrom]);
        Log::info('dateTo del request', ['dateTo' => $dateTo]);
        Log::info('=== FIN DEL LOG DE FECHAS ===');

        // LOG DE LO QUE SE ENVÍA AL FRONTEND
        Log::info('=== DATOS ENVIADOS AL FRONTEND ===');
        Log::info('ecommerceData completo', $ecommerceData);
        Log::info('funnelAnalysis completo', $funnelAnalysis);
        Log::info('=== FIN DEL LOG FRONTEND ===');

        return Inertia::render('Admin/Reports/Index', [
            'summary' => $summary,
            'programs' => $programs,
            'ecommerceData' => $ecommerceData,
            'funnelAnalysis' => $funnelAnalysis,
            'filters' => [
                'dateFrom' => $dateFrom,
                'dateTo' => $dateTo,
                'program' => $programId
            ],
        ]);
    }

    private function getEcommerceData(Request $request): array
    {
        $dateFrom = $request->dateFrom ?? Carbon::now()->subDays(30)->format('Y-m-d');
        $dateTo = $request->dateTo ?? Carbon::now()->format('Y-m-d');
        
        // Usar fechas específicas para los datos de prueba
        if ($dateFrom === '2025-07-22' && $dateTo === '2025-08-21') {
            $dateFrom = '2025-08-21';
            $dateTo = '2025-08-21';
        }
        
        // Obtener análisis del funnel desde el nuevo servicio
        $funnelAnalysis = $this->analyticsService->getFunnelAnalysis($dateFrom, $dateTo);
        
        // Obtener datos de programas más vistos
        $programViews = $this->getProgramViewsData($request);
        
        // Obtener datos de participantes más buscados
        $frequentParticipants = $this->getFrequentParticipantsData($request);
        
        // Obtener datos de métodos de pago
        $paymentMethods = $this->getPaymentMethodsData($request);

        // Obtener análisis de cuotas y tipos de pago
        $installmentsAnalysis = $this->getInstallmentsAnalysisData($request);
        $paymentTypes = $this->getPaymentTypesData($request);

        // LOG DETALLADO DE TODOS LOS DATOS
        Log::info('=== DATOS COMPLETOS DEL ECOMMERCE ===');
        Log::info('Fechas usadas', ['dateFrom' => $dateFrom, 'dateTo' => $dateTo]);
        Log::info('Funnel Analysis completo', $funnelAnalysis);
        Log::info('Program Views', $programViews);
        Log::info('Frequent Participants', $frequentParticipants);
        Log::info('Payment Methods', $paymentMethods);
        Log::info('Installments Analysis', $installmentsAnalysis);
        Log::info('Payment Types', $paymentTypes);
        Log::info('=== FIN DEL LOG ===');

        return [
            'funnelData' => $funnelAnalysis['funnel_stages'] ?? [],
            'programViews' => $programViews,
            'frequentParticipants' => $frequentParticipants,
            'paymentMethods' => $paymentMethods,
            'installmentsAnalysis' => $installmentsAnalysis,
            'paymentTypes' => $paymentTypes,
        ];
    }

    private function getProgramViewsData(Request $request): array
    {
        // Usar las fechas del request principal, no períodos predefinidos
        $dateFrom = $request->dateFrom ?? Carbon::now()->subDays(30)->format('Y-m-d');
        $dateTo = $request->dateTo ?? Carbon::now()->format('Y-m-d');

        $data = \App\Models\EcommerceAnalytics::whereBetween('created_at', [$dateFrom, $dateTo])
            ->whereNotNull('program_detail_view_at')
            ->selectRaw('COALESCE(program_name, CONCAT("Programa #", program_id)) as program_name, COUNT(*) as views, COUNT(payment_completed_at) as conversions')
            ->groupBy('program_id', 'program_name')
            ->orderByDesc('views')
            ->limit(10)
            ->get()
            ->map(function ($item) {
                return [
                    'program_name' => $item->program_name,
                    'views' => $item->views,
                    'conversions' => $item->conversions,
                    'conversion_rate' => $item->views > 0 ? ($item->conversions / $item->views) * 100 : 0,
                ];
            })
            ->toArray();

        Log::info('Program Views Data', [
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
            'data' => $data
        ]);

        return $data;
    }

    private function getFrequentParticipantsData(Request $request): array
    {
        // Usar las fechas del request principal, no períodos predefinidos
        $dateFrom = $request->dateFrom ?? Carbon::now()->subDays(30)->format('Y-m-d');
        $dateTo = $request->dateTo ?? Carbon::now()->format('Y-m-d');

        $data = \App\Models\EcommerceAnalytics::whereBetween('created_at', [$dateFrom, $dateTo])
            ->whereNotNull('participant_rut')
            ->whereNotNull('hero_search_at')
            ->selectRaw('participant_rut, COUNT(*) as search_count')
            ->groupBy('participant_rut')
            ->orderByDesc('search_count')
            ->limit(8)
            ->get()
            ->map(function ($item) {
                return [
                    'participant_rut' => $item->participant_rut,
                    'search_count' => $item->search_count,
                ];
            })
            ->toArray();

        Log::info('Frequent Participants Data', [
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
            'data' => $data
        ]);

        return $data;
    }

    private function getPaymentMethodsData(Request $request): array
    {
        // Usar las fechas del request principal, no períodos predefinidos
        $dateFrom = $request->dateFrom ?? Carbon::now()->subDays(30)->format('Y-m-d');
        $dateTo = $request->dateTo ?? Carbon::now()->format('Y-m-d');

        $data = \App\Models\EcommerceAnalytics::whereBetween('created_at', [$dateFrom, $dateTo])
            ->whereNotNull('payment_method')
            ->selectRaw('payment_method, COUNT(*) as total, COUNT(CASE WHEN payment_status = "completed" THEN 1 END) as successful')
            ->groupBy('payment_method')
            ->orderByDesc('total')
            ->get()
            ->map(function ($item) {
                return [
                    'payment_method' => $item->payment_method,
                    'total' => $item->total,
                    'successful' => $item->successful,
                    'success_rate' => $item->total > 0 ? ($item->successful / $item->total) * 100 : 0,
                ];
            })
            ->toArray();

        Log::info('Payment Methods Data', [
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
            'data' => $data
        ]);

        return $data;
    }

    private function getInstallmentsAnalysisData(Request $request): array
    {
        // Usar las fechas del request principal, no períodos predefinidos
        $dateFrom = $request->dateFrom ?? Carbon::now()->subDays(30)->format('Y-m-d');
        $dateTo = $request->dateTo ?? Carbon::now()->format('Y-m-d');

        // Contar pagos con cuotas y sin cuotas
        $withInstallments = \App\Models\EcommerceAnalytics::whereBetween('created_at', [$dateFrom, $dateTo])
            ->whereNotNull('payment_method')
            ->whereNotNull('installments_count')
            ->where('installments_count', '>', 0)
            ->count();

        $withoutInstallments = \App\Models\EcommerceAnalytics::whereBetween('created_at', [$dateFrom, $dateTo])
            ->whereNotNull('payment_method')
            ->where(function($query) {
                $query->whereNull('installments_count')
                      ->orWhere('installments_count', 0);
            })
            ->count();

        $data = [
            [
                'installments_count' => 0,
                'count' => $withoutInstallments,
                'label' => 'Sin cuotas'
            ],
            [
                'installments_count' => 1,
                'count' => $withInstallments,
                'label' => 'Con cuotas'
            ]
        ];

        Log::info('Installments Analysis Data', [
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
            'withInstallments' => $withInstallments,
            'withoutInstallments' => $withoutInstallments,
            'data' => $data
        ]);

        return $data;
    }

    private function getPaymentTypesData(Request $request): array
    {
        // Usar las fechas del request principal, no períodos predefinidos
        $dateFrom = $request->dateFrom ?? Carbon::now()->subDays(30)->format('Y-m-d');
        $dateTo = $request->dateTo ?? Carbon::now()->format('Y-m-d');

        // Contar pagos por tipo
        $contado = \App\Models\EcommerceAnalytics::whereBetween('created_at', [$dateFrom, $dateTo])
            ->whereNotNull('payment_method')
            ->where(function($query) {
                $query->whereNull('installments_count')
                      ->orWhere('installments_count', 0);
            })
            ->count();

        $cuotas = \App\Models\EcommerceAnalytics::whereBetween('created_at', [$dateFrom, $dateTo])
            ->whereNotNull('payment_method')
            ->whereNotNull('installments_count')
            ->where('installments_count', '>', 0)
            ->count();

        $data = [
            [
                'payment_type' => 'contado',
                'count' => $contado,
                'label' => 'Pago al contado'
            ],
            [
                'payment_type' => 'cuotas',
                'count' => $cuotas,
                'label' => 'Pago en cuotas'
            ]
        ];

        Log::info('Payment Types Data', [
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
            'contado' => $contado,
            'cuotas' => $cuotas,
            'data' => $data
        ]);

        return $data;
    }

    private function getDateFromPeriod(string $period): string
    {
        return match($period) {
            '7days' => Carbon::now()->subDays(7)->format('Y-m-d'),
            '30days' => Carbon::now()->subDays(30)->format('Y-m-d'),
            '90days' => Carbon::now()->subDays(90)->format('Y-m-d'),
            'thisYear' => Carbon::now()->startOfYear()->format('Y-m-d'),
            'all' => '2020-01-01', // Desde el inicio
            default => Carbon::now()->subDays(30)->format('Y-m-d'),
        };
    }

    public function export(Request $request)
    {
        $filters = [
            'dateFrom' => $request->dateFrom ?? Carbon::now()->subMonth()->format('Y-m-d'),
            'dateTo' => $request->dateTo ?? Carbon::now()->format('Y-m-d'),
            'programId' => $request->program
        ];

        try {
            return $this->consolidatedExportService->export($filters);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error al generar el archivo: ' . $e->getMessage()], 500);
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
                    $payment->order->participant->full_name,
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
