<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Program;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Carbon\Carbon;
use App\Services\Admin\Reports\RecoverySchedule\RecoveryScheduleService;
use App\Services\Admin\Reports\RecoverySchedule\ExportService;
use App\Services\Admin\Reports\Executives\ExportService as ExecutivesExportService;
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
use App\Services\Admin\Reports\PaymentSchedule\ExportService as PaymentScheduleExportService;
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
        private ExecutivesExportService $executivesExportService,
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
        private PaymentScheduleExportService $paymentScheduleExportService,
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
            
            // Obtener los datos para exportar
            $data = $this->recoveryScheduleService->getAllPaymentSchedules($filters);
            
            // Generar nombre de archivo
            $filename = 'cronograma_cuotas_' . now('America/Santiago')->format('Y-m-d_H-i-s');
            
            return $this->exportService->export($data, $filename, $format);
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
            $filters = $request->only(['dateFrom', 'dateTo', 'programId', 'participantId', 'participantSearch', 'paymentStatus', 'page']);
            
            // Use the new PartialAccountService
            $partialAccountService = app(\App\Services\Admin\Reports\PartialReport\PartialAccountService::class);
            $partialAccounts = $partialAccountService->getPartialAccounts($filters);
            $filterData = $partialAccountService->getFilterData();
            
            return Inertia::render('Admin/Reports/PartialAccount', [
                'partialAccounts' => $partialAccounts,
                'programs' => $filterData['programs'],
                'participants' => $filterData['participants'],
                'filters' => $filters
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error al generar estado de cuenta parcial: ' . $e->getMessage()], 500);
        }
    }

    public function searchParticipants(Request $request)
    {
        try {
            $search = $request->get('search', '');
            
            if (strlen($search) < 2) {
                return response()->json([]);
            }
            
            $partialAccountService = app(\App\Services\Admin\Reports\PartialReport\PartialAccountService::class);
            $participants = $partialAccountService->searchParticipants($search);
            
            return response()->json($participants);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error al buscar participantes: ' . $e->getMessage()], 500);
        }
    }

    public function paymentSchedule(Request $request)
    {
        try {
            $filters = $request->only(['dateFrom', 'dateTo', 'programId', 'participantId']);
            $data = $this->paymentScheduleSummaryService->getSummaryData($filters);
            
            return Inertia::render('Admin/Reports/PaymentSchedule', [
                'paymentSchedules' => $data['paymentSchedules'] ?? [],
                'programs' => $data['programs'] ?? [],
                'salesExecutives' => $data['salesExecutives'] ?? [],
                'summary' => $data['summary'] ?? [],
                'executiveSummary' => $data['executiveSummary'] ?? [],
                'filters' => $filters
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error al generar cronograma de pagos: ' . $e->getMessage()], 500);
        }
    }

    public function paymentScheduleDetails(Request $request)
    {
        try {
            $filters = $request->only(['dateFrom', 'dateTo', 'programId', 'participantId', 'salesExecutiveId', 'yearMonth']);
            $data = $this->paymentScheduleDetailService->getDetailData($filters);
            
            return response()->json([
                'success' => true,
                'data' => $data['scheduleDetails'] ?? [],
                'liberated' => $data['liberatedParticipants'] ?? []
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener detalles del cronograma: ' . $e->getMessage()
            ], 500);
        }
    }

    public function dailyPayments(Request $request)
    {
        try {
            $filters = $request->only(['dateFrom', 'dateTo', 'programId', 'status']);
            
            $data = $this->dailyPaymentsService->getData($filters);
            
            return Inertia::render('Admin/Reports/DailyPayments', [
                'dailyPayments' => $data['dailyPayments'],
                'programs' => $data['programs'],
                'salesExecutives' => $data['salesExecutives'],
                'financingTypes' => $data['financingTypes'],
                'paymentMethods' => $data['paymentMethods'],
                'summary' => $data['summary'],
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
                'consolidatedPayments' => $data['consolidatedPayments'],
                'paymentMethods' => $data['paymentMethods'],
                'programs' => \App\Models\Program::select('id', 'code', 'name')->orderBy('code')->get(),
                'filters' => $filters,
                'summary' => $data['summary']
            ]);
        } catch (\Exception $e) {
            Log::error('Error al generar reporte consolidado: ' . $e->getMessage());
            return response()->json(['error' => 'Error al generar reporte consolidado'], 500);
        }
    }

    public function installmentSchedule(Request $request)
    {
        try {
            $filters = $request->only(['dateFrom', 'dateTo', 'programId', 'participantId']);
            $data = $this->recoveryScheduleService->getPaymentSchedules($filters);
            
            return Inertia::render('Admin/Reports/InstallmentSchedule', [
                'data' => $data,
                'filters' => $filters
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error al generar cronograma de cuotas: ' . $e->getMessage()], 500);
        }
    }

    public function exportDailyPayments(Request $request)
    {
        try {
            $filters = $request->only(['dateFrom', 'dateTo', 'programId', 'status']);
            $format = $request->get('format', 'excel');
            $selectedFields = $request->get('selectedFields', []);
            
            // Obtener los datos para exportar
            $data = $this->dailyPaymentsService->getAllDailyPayments($filters, $selectedFields);
            
            // Generar nombre de archivo
            $filename = 'pagos_diarios_' . now('America/Santiago')->format('Y-m-d_H-i-s');
            
            return $this->dailyPaymentsExportService->export($data, $filename, $format, $selectedFields);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error al exportar pagos diarios: ' . $e->getMessage()], 500);
        }
    }

    public function exportConsolidatedPayments(Request $request)
    {
        try {
            $filters = $request->only(['dateFrom', 'dateTo', 'programId', 'status']);
            $format = $request->get('format', 'xlsx');
            $selectedFields = $request->get('selectedFields', []);
            
            // Obtener los datos para exportar
            $data = $this->consolidatedPaymentsService->getAllConsolidatedPayments($filters, $selectedFields);
            
            // Generar nombre de archivo
            $filename = 'pagos_consolidados_' . now('America/Santiago')->format('Y-m-d_H-i-s');
            
            return $this->consolidatedPaymentsExportService->export($data, $filename, $format, $selectedFields);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error al exportar pagos consolidados: ' . $e->getMessage()], 500);
        }
    }

    public function exportPartialAccount(Request $request)
    {
        try {
            $filters = $request->only(['dateFrom', 'dateTo', 'programId', 'participantId', 'paymentStatus']);
            $selectedFields = json_decode($request->get('fields', '{}'), true);
            $format = $request->get('format', 'xlsx');
            $includeAll = $request->get('include_all', 'current');
            
            $partialAccountService = app(\App\Services\Admin\Reports\PartialReport\PartialAccountService::class);
            $data = $partialAccountService->getExportData($filters, $selectedFields, $includeAll);
            
            // Generate filename with timestamp
            $timestamp = now('America/Santiago')->format('Y-m-d_H-i-s');
            $baseFilename = 'estado_cuenta_parcial_' . $timestamp;
            
            if ($format === 'csv') {
                return $this->exportToCsv($data, $baseFilename);
            }
            
            return $this->exportToExcel($data, $baseFilename, $selectedFields);
            
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error al exportar: ' . $e->getMessage()], 500);
        }
    }

    private function exportToCsv($data, $filename)
    {
        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '.csv"',
        ];

        $callback = function() use ($data) {
            $file = fopen('php://output', 'w');
            
            // Add BOM for UTF-8
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));
            
            // Add headers if data is not empty
            if ($data->isNotEmpty()) {
                fputcsv($file, array_keys($data->first()), ';');
                
                foreach ($data as $row) {
                    fputcsv($file, array_values($row), ';');
                }
            }
            
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    private function exportToExcel($data, $filename, $selectedFields)
    {
        try {
            $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle('Estado de Cuenta Parcial');

            // Add headers
            if ($data->isNotEmpty()) {
                $headers = array_keys($data->first());
                
                $col = 'A';
                foreach ($headers as $header) {
                    $sheet->setCellValue($col . '1', $header);
                    $col++;
                }

                // Style headers
                $lastCol = chr(64 + count($headers));
                $sheet->getStyle('A1:' . $lastCol . '1')->getFont()->setBold(true);
                $sheet->getStyle('A1:' . $lastCol . '1')->getFill()
                    ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                    ->getStartColor()->setARGB('FFE0E0E0');

                // Add data
                $row = 2;
                foreach ($data as $item) {
                    $col = 'A';
                    foreach ($item as $value) {
                        try {
                            // Clean and handle values properly
                            if ($value === null) {
                                $sheet->setCellValue($col . $row, '');
                            } elseif (is_numeric($value)) {
                                $sheet->setCellValueExplicit($col . $row, (float)$value, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);
                            } elseif (is_bool($value)) {
                                $sheet->setCellValue($col . $row, $value ? 'Sí' : 'No');
                            } elseif (is_array($value) || is_object($value)) {
                                $sheet->setCellValue($col . $row, json_encode($value));
                            } else {
                                // Clean string values - remove any control characters
                                $cleanValue = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', (string)$value);
                                $sheet->setCellValue($col . $row, $cleanValue);
                            }
                            $col++;
                        } catch (\Exception $cellError) {
                            // Set empty value and continue
                            $sheet->setCellValue($col . $row, '');
                            $col++;
                        }
                    }
                    $row++;
                }

                // Auto-size columns
                foreach (range('A', $lastCol) as $columnID) {
                    $sheet->getColumnDimension($columnID)->setAutoSize(true);
                }
            }

            $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
            $tempFile = tempnam(sys_get_temp_dir(), 'partial_account_') . '.xlsx';
            $writer->save($tempFile);

            if (!file_exists($tempFile) || filesize($tempFile) === 0) {
                throw new \Exception('Failed to create Excel file');
            }

            // Save to public directory and return URL
            $publicPath = public_path('exports');
            if (!file_exists($publicPath)) {
                mkdir($publicPath, 0755, true);
            }
            
            $publicFilename = $filename . '.xlsx';
            $publicFilePath = $publicPath . '/' . $publicFilename;
            
            if (copy($tempFile, $publicFilePath)) {
                unlink($tempFile);
                
                return response()->json([
                    'success' => true,
                    'download_url' => url('exports/' . $publicFilename),
                    'filename' => $publicFilename,
                    'message' => 'Archivo generado exitosamente'
                ]);
            } else {
                throw new \Exception('Failed to save file for download');
            }

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('=== EXCEL EXPORT ERROR ===');
            \Illuminate\Support\Facades\Log::error('Error message: ' . $e->getMessage());
            \Illuminate\Support\Facades\Log::error('Error file: ' . $e->getFile());
            \Illuminate\Support\Facades\Log::error('Error line: ' . $e->getLine());
            \Illuminate\Support\Facades\Log::error('Stack trace: ' . $e->getTraceAsString());
            return response()->json(['error' => 'Error generating Excel file: ' . $e->getMessage()], 500);
        }
    }

    public function exportPaymentSchedule(Request $request)
    {
        try {
            $filters = $request->only(['dateFrom', 'dateTo', 'programId', 'participantId', 'salesExecutiveId', 'yearMonth']);
            $format = $request->get('format', 'xlsx');
            
            // Obtener los datos para exportar
            $data = $this->paymentScheduleSummaryService->getSummaryData($filters);
            
            // Generar nombre de archivo
            $filename = 'cronograma_pagos_' . now('America/Santiago')->format('Y-m-d_H-i-s');
            
            // Usar el exportador específico para cronograma de pagos
            return $this->paymentScheduleExportService->export($data, $filename, $format);
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

            // Limpiar archivos antiguos
            $this->softlandZipExporter->cleanupOldFiles();

            // Generar archivo ZIP
            $zipPath = $this->softlandZipExporter->exportToZip($filters, $format);

            if (!file_exists($zipPath)) {
                throw new \Exception('No se pudo generar el archivo ZIP');
            }

            $fileName = basename($zipPath);

            // Descargar el archivo
            return response()->download($zipPath, $fileName)->deleteFileAfterSend(true);

        } catch (\Exception $e) {
            Log::error('Error en exportSoftlandZip: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
            return response()->json(['error' => 'Error al generar el archivo ZIP: ' . $e->getMessage()], 500);
        }
    }


}