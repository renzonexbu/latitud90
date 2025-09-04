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
use App\Services\Admin\Reports\Softland\ExcelExporter as SoftlandExcelExporter;
use App\Services\Admin\Reports\Softland\AuxiliaresExporter;
use App\Services\Admin\Reports\Softland\SoftlandAuxiliaresService;
use App\Services\Admin\Reports\Softland\SoftlandZipExporter;
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
        private GetIndexDataService $getIndexDataService,
        private GetSalesChartService $getSalesChartService,
        private GetInstallmentScheduleService $getInstallmentScheduleService,
        private GetRevenueChartService $getRevenueChartService,
        private PaymentScheduleSummaryService $paymentScheduleSummaryService,
        private PaymentScheduleDetailService $paymentScheduleDetailService,
        private EcommerceAnalyticsService $analyticsService,
        private SoftlandExcelExporter $softlandExcelExporter,
        private AuxiliaresExporter $auxiliaresExporter,
        private SoftlandAuxiliaresService $auxiliaresService,
        private SoftlandZipExporter $softlandZipExporter
    ) {}

    public function index(Request $request)
    {
        $data = $this->getIndexDataService->execute($request);
        return Inertia::render('Admin/Reports/Index', $data);
    }

    public function export(Request $request)
    {
        try {
            $filters = $request->only(['dateFrom', 'dateTo', 'programId', 'status']);
            $format = $request->get('format', 'excel');
            
            return $this->exportService->export($filters, $format);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error al exportar: ' . $e->getMessage()], 500);
        }
    }

    public function salesChart(Request $request)
    {
        try {
            $data = $this->getSalesChartService->execute($request);
            return response()->json($data);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error al obtener datos del gráfico: ' . $e->getMessage()], 500);
        }
    }

    public function partialAccount(Request $request)
    {
        try {
            $filters = $request->only(['dateFrom', 'dateTo', 'programId', 'participantId']);
            $data = $this->reportsSummaryService->getPartialAccountData($filters);
            
            return Inertia::render('Admin/Reports/PartialAccount', [
                'data' => $data,
                'filters' => $filters
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error al generar estado de cuenta parcial: ' . $e->getMessage()], 500);
        }
    }

    public function paymentSchedule(Request $request)
    {
        try {
            $filters = $request->only(['dateFrom', 'dateTo', 'programId', 'participantId']);
            $data = $this->paymentScheduleSummaryService->getSummaryData($filters);
            
            return Inertia::render('Admin/Reports/PaymentSchedule', [
                'data' => $data,
                'filters' => $filters
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error al generar cronograma de pagos: ' . $e->getMessage()], 500);
        }
    }

    public function paymentScheduleDetails(Request $request)
    {
        try {
            $filters = $request->only(['dateFrom', 'dateTo', 'programId', 'participantId']);
            $data = $this->paymentScheduleDetailService->getDetailData($filters);
            
            return response()->json($data);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error al obtener detalles del cronograma: ' . $e->getMessage()], 500);
        }
    }

    public function dailyPayments(Request $request)
    {
        try {
            $filters = $request->only(['dateFrom', 'dateTo', 'programId', 'status']);
            $data = $this->dailyPaymentsService->getData($filters);
            
            return Inertia::render('Admin/Reports/DailyPayments', [
                'data' => $data,
                'filters' => $filters
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error al generar reporte de pagos diarios: ' . $e->getMessage()], 500);
        }
    }

    public function consolidatedPayments(Request $request)
    {
        try {
            $filters = $request->only(['dateFrom', 'dateTo', 'programId', 'status']);
            $data = $this->consolidatedPaymentsService->getData($filters);
            
            return Inertia::render('Admin/Reports/ConsolidatedPayments', [
                'data' => $data,
                'filters' => $filters
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error al generar reporte consolidado: ' . $e->getMessage()], 500);
        }
    }

    public function installmentSchedule(Request $request)
    {
        try {
            $filters = $request->only(['dateFrom', 'dateTo', 'programId', 'participantId']);
            $data = $this->getInstallmentScheduleService->execute($filters);
            
            return Inertia::render('Admin/Reports/InstallmentSchedule', [
                'data' => $data,
                'filters' => $filters
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error al generar cronograma de cuotas: ' . $e->getMessage()], 500);
        }
    }

    public function revenueChart(Request $request)
    {
        try {
            $data = $this->getRevenueChartService->execute($request);
            return response()->json($data);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error al obtener datos del gráfico de ingresos: ' . $e->getMessage()], 500);
        }
    }

    public function exportDailyPayments(Request $request)
    {
        try {
            $filters = $request->only(['dateFrom', 'dateTo', 'programId', 'status']);
            $format = $request->get('format', 'excel');
            
            return $this->dailyPaymentsExportService->export($filters, $format);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error al exportar pagos diarios: ' . $e->getMessage()], 500);
        }
    }

    public function exportConsolidatedPayments(Request $request)
    {
        try {
            $filters = $request->only(['dateFrom', 'dateTo', 'programId', 'status']);
            $format = $request->get('format', 'excel');
            
            return $this->consolidatedPaymentsExportService->export($filters, $format);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error al exportar pagos consolidados: ' . $e->getMessage()], 500);
        }
    }

    public function exportPartialAccount(Request $request)
    {
        try {
            $filters = $request->only(['dateFrom', 'dateTo', 'programId', 'participantId']);
            $format = $request->get('format', 'excel');
            
            return $this->exportService->exportPartialAccount($filters, $format);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error al exportar estado de cuenta parcial: ' . $e->getMessage()], 500);
        }
    }

    public function exportPaymentSchedule(Request $request)
    {
        try {
            $filters = $request->only(['dateFrom', 'dateTo', 'programId', 'participantId']);
            $format = $request->get('format', 'excel');
            
            return $this->exportService->exportPaymentSchedule($filters, $format);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error al exportar cronograma de pagos: ' . $e->getMessage()], 500);
        }
    }

    public function softland(Request $request)
    {
        $programs = Program::select('id', 'name', 'code')->orderBy('name')->get();
        
        return Inertia::render('Admin/Reports/SoftlandReport', [
            'programs' => $programs
        ]);
    }

    public function exportSoftlandTemplate(Request $request)
    {
        try {
            $filters = $request->only(['dateFrom', 'dateTo', 'programId']);
            $format = $request->get('format', 'excel'); // Por defecto Excel
            $filename = 'softland_movimientos_' . now('America/Santiago')->format('Y-m-d_H-i-s');
            
            return $this->softlandExcelExporter->export($filename, $filters, $format);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error al generar el archivo: ' . $e->getMessage()], 500);
        }
    }

    public function previewSoftlandAuxiliares(Request $request)
    {
        try {
            Log::info('Generando preview JSON de auxiliares Softland');
            
            $jsonData = $this->auxiliaresService->generateJsonData();
            
            Log::info('JSON generado exitosamente con ' . $jsonData['total_auxiliares'] . ' auxiliares');
            
            return response()->json($jsonData);
            
        } catch (\Exception $e) {
            Log::error('Error en previewSoftlandAuxiliares: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
            return response()->json(['error' => 'Error al generar preview de auxiliares: ' . $e->getMessage()], 500);
        }
    }

    public function exportSoftlandAuxiliares(Request $request)
    {
        try {
            Log::info('Iniciando exportación de auxiliares Softland');
            $format = $request->get('format', 'excel'); // Por defecto Excel
            Log::info('Formato solicitado: ' . $format);
            
            // Limpiar archivos temporales antiguos
            Log::info('Limpiando archivos temporales antiguos');
            $this->auxiliaresExporter->cleanTempFiles();
            
            if ($format === 'csv') {
                Log::info('Generando archivo CSV');
                $filepath = $this->auxiliaresExporter->exportToCsv();
                $filename = basename($filepath);
                $mimeType = 'text/csv';
                
                // Verificar que el archivo existe antes de la descarga
                if (!file_exists($filepath)) {
                    Log::error('ERROR: El archivo no existe en la ruta: ' . $filepath);
                    throw new \Exception('El archivo generado no se encuentra');
                }
                
                $fileSize = filesize($filepath);
                Log::info('Tamaño del archivo: ' . $fileSize . ' bytes');
                
                if ($fileSize === 0) {
                    Log::error('ERROR: El archivo está vacío');
                    throw new \Exception('El archivo generado está vacío');
                }
                
                Log::info('Iniciando descarga del archivo CSV');
                return response()->download($filepath, $filename, [
                    'Content-Type' => $mimeType,
                    'Content-Disposition' => 'attachment; filename="' . $filename . '"',
                ])->deleteFileAfterSend(true);
            } else {
                Log::info('Generando archivo Excel con StreamedResponse');
                $response = $this->auxiliaresExporter->exportToExcel();
                Log::info('StreamedResponse creado exitosamente');
                return $response;
            }
            
        } catch (\Exception $e) {
            Log::error('Error en exportSoftlandAuxiliares: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
            return response()->json(['error' => 'Error al generar el archivo de auxiliares: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Exporta ambos archivos Softland en un ZIP
     */
    public function exportSoftlandZip(Request $request)
    {
        try {
            Log::info('Iniciando exportación ZIP de archivos Softland', [
                'request_params' => $request->all()
            ]);

            // Obtener parámetros
            $format = $request->get('format', 'excel');
            $dateFrom = $request->get('dateFrom');
            $dateTo = $request->get('dateTo');
            $programId = $request->get('programId');

            // Construir filtros
            $filters = [];
            if ($dateFrom) $filters['dateFrom'] = $dateFrom;
            if ($dateTo) $filters['dateTo'] = $dateTo;
            if ($programId) $filters['programId'] = $programId;

            Log::info('Filtros aplicados', ['filters' => $filters, 'format' => $format]);

            // Limpiar archivos antiguos
            $this->softlandZipExporter->cleanupOldFiles();

            // Generar archivo ZIP
            $zipPath = $this->softlandZipExporter->exportToZip($filters, $format);

            if (!file_exists($zipPath)) {
                throw new \Exception('No se pudo generar el archivo ZIP');
            }

            $fileName = basename($zipPath);
            $fileSize = filesize($zipPath);

            Log::info('Archivo ZIP generado exitosamente', [
                'file_name' => $fileName,
                'file_size' => $fileSize
            ]);

            // Descargar el archivo
            return response()->download($zipPath, $fileName)->deleteFileAfterSend(true);

        } catch (\Exception $e) {
            Log::error('Error en exportSoftlandZip: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
            return response()->json(['error' => 'Error al generar el archivo ZIP: ' . $e->getMessage()], 500);
        }
    }


}