<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Program;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Carbon\Carbon;
use App\Services\Admin\Reports\RecoverySchedule\RecoveryScheduleService;
use App\Services\Admin\Reports\RecoverySchedule\ExportService;
use App\Services\Admin\Reports\DailyPayments\DailyPaymentsService;
use App\Services\Admin\Reports\DailyPayments\ExportService as DailyPaymentsExportService;
use App\Services\Admin\Reports\ConsolidatedPayments\ConsolidatedPaymentsService;
use App\Services\Admin\Reports\ConsolidatedPayments\ExportService as ConsolidatedPaymentsExportService;
use App\Services\Admin\Reports\ReportsSummaryService;
use App\Services\Admin\Reports\ConsolidatedExportService;
use App\Services\Admin\Reports\GetIndexDataService;
use App\Services\Admin\Reports\GetSalesChartService;
use App\Services\Admin\Reports\GetInstallmentScheduleService;
use App\Services\Admin\Reports\GetRevenueChartService;
use App\Services\Admin\Reports\PaymentSchedule\PaymentScheduleSummaryService;
use App\Services\Admin\Reports\PaymentSchedule\PaymentScheduleDetailService;
use App\Services\EcommerceAnalyticsService;

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
        private GetIndexDataService $getIndexDataService,
        private GetSalesChartService $getSalesChartService,
        private GetInstallmentScheduleService $getInstallmentScheduleService,
        private GetRevenueChartService $getRevenueChartService,
        private PaymentScheduleSummaryService $paymentScheduleSummaryService,
        private PaymentScheduleDetailService $paymentScheduleDetailService,
        private EcommerceAnalyticsService $analyticsService
    ) {}

    public function index(Request $request)
    {
        $data = $this->getIndexDataService->execute($request);

        return Inertia::render('Admin/Reports/Index', $data);
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



    public function salesChart(Request $request)
    {
        $data = $this->getSalesChartService->execute($request);

        return response()->json($data);
    }



    public function consolidatedPayments(Request $request)
    {
        $filters = $request->only([
            'paymentMethodId',
            'programId',
            'participantQuery',
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
        $programs = \App\Models\Program::select('id', 'code', 'name', 'destination')->orderBy('code')->get();

        return Inertia::render('Admin/Reports/ConsolidatedPayments', [
            'consolidatedPayments' => $consolidatedPayments,
            'paymentMethods' => $paymentMethods,
            'programs' => $programs,
            'filters' => $filters,
            'summary' => $summary
        ]);
    }

    public function installmentSchedule(Request $request)
    {
        $data = $this->getInstallmentScheduleService->execute($request);

        return Inertia::render('Admin/Reports/InstallmentSchedule', $data);
    }

    public function revenueChart(Request $request)
    {
        $data = $this->getRevenueChartService->execute($request);

        return Inertia::render('Admin/Reports/RevenueChart', $data);
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
        $filters = $request->only(['programId', 'salesExecutiveId', 'dateFrom', 'dateTo', 'status', 'page']);

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

        // Obtener resumen ejecutivo
        $executiveSummary = $this->paymentScheduleSummaryService->getExecutiveSummary($filters);

        // Obtener ejecutivos para filtros
        $salesExecutives = $this->paymentScheduleSummaryService->getSalesExecutives();

        return Inertia::render('Admin/Reports/PaymentSchedule', [
            'paymentSchedules' => $paymentSchedules,
            'programs' => $programs,
            'salesExecutives' => $salesExecutives,
            'filters' => $filters,
            'summary' => $summary,
            'executiveSummary' => $executiveSummary
        ]);
    }

    public function paymentScheduleDetails(Request $request)
    {
        $filters = $request->only(['programId', 'salesExecutiveId', 'yearMonth', 'dateFrom', 'dateTo']);

        try {
            // Obtener detalles del cronograma de cuotas (solo cuotas que vencen en el mes)
            $scheduleDetails = $this->paymentScheduleDetailService->getScheduleDetails($filters);
            
            // Obtener participantes liberados (siempre se muestran)
            $liberatedParticipants = $this->paymentScheduleDetailService->getLiberatedParticipants($filters);

            return response()->json([
                'success' => true,
                'data' => $scheduleDetails,
                'liberated' => $liberatedParticipants,
                'filters' => $filters
            ]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Payment Schedule Details Error:', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'filters' => $filters,
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al obtener los detalles: ' . $e->getMessage(),
                'data' => [],
                'liberated' => []
            ], 500);
        }
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
        $filters = $request->only(['programId', 'salesExecutiveId', 'dateFrom', 'dateTo', 'status']);
        $format = $request->get('format', 'xlsx');

        try {
            // Resumen por ejecutivo/programa/mes
            $executiveSummary = $this->paymentScheduleSummaryService->getExecutiveSummary($filters);

            // Detalle por participante (incluye suscritos sin pagos) para el rango/mes
            $details = $this->paymentScheduleDetailService->getScheduleDetails($filters);

            if ($executiveSummary->isEmpty() || $details->isEmpty()) {
                return response()->json(['error' => 'No hay datos válidos para exportar'], 500);
            }

            // Exportar en 2 hojas
            $filename = 'payment_schedule_' . now()->format('Y-m-d_H-i-s');
            $exporter = app(\App\Services\Admin\Reports\PaymentSchedule\ExportService::class);
            return $exporter->export($executiveSummary, $details, $filename, $format);
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
