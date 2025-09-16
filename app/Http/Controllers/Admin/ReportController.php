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
use App\Services\Admin\Reports\PaymentSchedule\PaymentScheduleSummaryDataProvider;
use App\Services\Admin\Reports\PaymentSchedule\PaymentScheduleSummaryFilters;
use App\Services\Admin\Reports\PaymentSchedule\PaymentScheduleSummaryTransformer;
use App\Models\SalesExecutive;
use App\Services\EcommerceAnalyticsService;
use App\Services\Admin\Reports\Softland\ExcelExporter as SoftlandExcelExporter;
use App\Services\Admin\Reports\Softland\AuxiliaresExporter;
use App\Services\Admin\Reports\Softland\SoftlandAuxiliaresService;
use App\Services\Admin\Reports\Softland\SoftlandZipExporter;
use App\Services\Admin\Reports\BsaleDocuments\BsaleZipDownloadService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

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
        private SoftlandZipExporter $softlandZipExporter,
        private BsaleZipDownloadService $bsaleZipDownloadService
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
            return response()->json([
                'error' => 'Error al obtener documentos BSale: ' . $e->getMessage()
            ], 500);
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
            Log::error('ReportController: Error in partialAccount', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
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
        $dataProvider = new PaymentScheduleSummaryDataProvider();
        $filters = new PaymentScheduleSummaryFilters();
        $transformer = new PaymentScheduleSummaryTransformer();
        
        $summaryService = new PaymentScheduleSummaryService($dataProvider, $filters, $transformer);
        $summaryData = $summaryService->getSummaryData($request->all());
        
        return Inertia::render('Admin/Reports/PaymentSchedule', [
            'executiveSummary' => $summaryData['executiveSummary'],
            'salesExecutives' => $summaryData['salesExecutives'],
            'programs' => $summaryData['programs'],
            'filters' => $request->all()
        ]);
    }

    public function noPayment(Request $request)
    {
        $dataProvider = new PaymentScheduleSummaryDataProvider();
        $participantsWithoutPayments = $dataProvider->getParticipantsWithoutPayments($request->all());
        
        // Add pagination
        $perPage = 15;
        $currentPage = $request->get('page', 1);
        $total = $participantsWithoutPayments->count();
        $items = $participantsWithoutPayments->forPage($currentPage, $perPage)->values();
        
        $paginatedData = new \Illuminate\Pagination\LengthAwarePaginator(
            $items,
            $total,
            $perPage,
            $currentPage,
            [
                'path' => $request->url(),
                'pageName' => 'page',
            ]
        );
        
        $paginatedData->appends($request->query());
        
        return Inertia::render('Admin/Reports/NoPayment', [
            'participantsWithoutPayments' => $paginatedData,
            'programs' => Program::select('id', 'name')->get(),
            'salesExecutives' => SalesExecutive::select('id', 'name')->get(),
            'filters' => $request->all()
        ]);
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
            Log::error('=== EXCEL EXPORT ERROR ===');
            Log::error('Error message: ' . $e->getMessage());
            Log::error('Error file: ' . $e->getFile());
            Log::error('Error line: ' . $e->getLine());
            Log::error('Stack trace: ' . $e->getTraceAsString());
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

    /**
     * Show BSale documents management page
     */
    public function bsaleDocuments()
    {
        return Inertia::render('Admin/Reports/BsaleDocuments');
    }

    /**
     * Get list of BSale documents
     */
    public function bsaleDocumentsList(Request $request)
    {
        try {
            $year = $request->get('year');
            $search = $request->get('search');
            $perPage = (int) $request->get('per_page', 50);

            // Get all BSale documents from storage
            $documents = collect();
            $basePath = 'bsale_documents';
            
            // If year is specified, look in that year's folder
            if ($year) {
                $yearPath = $basePath . '/' . $year;
                if (Storage::exists($yearPath)) {
                    $files = Storage::files($yearPath);
                    foreach ($files as $file) {
                        $documents->push($this->formatBsaleDocument($file));
                    }
                }
            } else {
                // Get all years
                $yearFolders = Storage::directories($basePath);
                foreach ($yearFolders as $yearFolder) {
                    $files = Storage::files($yearFolder);
                    foreach ($files as $file) {
                        $documents->push($this->formatBsaleDocument($file));
                    }
                }
            }

            // Filter by search term if provided
            if ($search) {
                $documents = $documents->filter(function ($doc) use ($search) {
                    return stripos($doc['filename'], $search) !== false ||
                           stripos($doc['bsale_number'], $search) !== false ||
                           stripos($doc['payment_id'], $search) !== false;
                });
            }

            // Sort by date (newest first)
            $documents = $documents->sortByDesc('modified_at');

            // Get available years for filter
            $availableYears = collect(Storage::directories($basePath))
                ->map(function ($path) {
                    return basename($path);
                })
                ->sort()
                ->values();

            // Paginate results
            $total = $documents->count();
            $page = (int) $request->get('page', 1);
            $offset = ($page - 1) * $perPage;
            $paginatedDocs = $documents->slice($offset, $perPage)->values();

            return response()->json([
                'documents' => $paginatedDocs,
                'total' => $total,
                'per_page' => $perPage,
                'current_page' => $page,
                'last_page' => ceil($total / $perPage),
                'available_years' => $availableYears
            ]);

        } catch (\Exception $e) {
            Log::error('Error listing BSale documents: ' . $e->getMessage());
            return response()->json(['error' => 'Error al listar documentos BSale'], 500);
        }
    }

    /**
     * Download a specific BSale document
     */
    public function downloadBsaleDocument(string $filename): BinaryFileResponse
    {
        try {
            // Find the file in any year folder
            $basePath = 'bsale_documents';
            $yearFolders = Storage::directories($basePath);
            
            $filePath = null;
            foreach ($yearFolders as $yearFolder) {
                $possiblePath = $yearFolder . '/' . $filename;
                if (Storage::exists($possiblePath)) {
                    $filePath = $possiblePath;
                    break;
                }
            }

            if (!$filePath) {
                abort(404, 'Documento BSale no encontrado');
            }

            $fullPath = Storage::path($filePath);
            
            return response()->download($fullPath, $filename, [
                'Content-Type' => 'application/pdf',
            ]);

        } catch (\Exception $e) {
            Log::error('Error downloading BSale document: ' . $e->getMessage());
            abort(500, 'Error al descargar el documento');
        }
    }

    /**
     * Format BSale document information
     */
    private function formatBsaleDocument(string $filePath): array
    {
        $filename = basename($filePath);
        $size = Storage::size($filePath);
        $modifiedAt = Storage::lastModified($filePath);
        
        // Extract info from filename: bsale_{bsale_number}_payment_{payment_id}.pdf
        $bsaleNumber = '';
        $paymentId = '';
        
        if (preg_match('/bsale_(.+?)_payment_(\d+)\.pdf/', $filename, $matches)) {
            $bsaleNumber = $matches[1];
            $paymentId = $matches[2];
        }

        return [
            'filename' => $filename,
            'path' => $filePath,
            'size' => $size,
            'size_formatted' => $this->formatBytes($size),
            'modified_at' => $modifiedAt,
            'modified_at_formatted' => Carbon::createFromTimestamp($modifiedAt)->format('d/m/Y H:i'),
            'bsale_number' => $bsaleNumber,
            'payment_id' => $paymentId,
            'year' => dirname($filePath) !== 'bsale_documents' ? basename(dirname($filePath)) : '',
        ];
    }

    /**
     * Download all BSale documents as ZIP using streaming directo
     */
    public function downloadBsaleDocumentsZip(Request $request)
    {
        try {
            // Get filters from request
            $filters = [
                'year' => $request->get('year'),
                'search' => $request->get('search')
            ];

            // Get all BSale documents based on filters
            $documents = $this->getBsaleDocumentsForZip($filters);
            
            if ($documents->isEmpty()) {
                return response()->json(['error' => 'No se encontraron documentos BSale para descargar'], 404);
            }

            // Usar streaming directo como SoftlandZipExporter
            $fileName = $this->generateBsaleZipFileName($filters);
            
            return response()->streamDownload(function () use ($documents, $filters) {
                // Crear ZIP en memoria
                $zip = new \ZipArchive();
                $tempZipPath = tempnam(sys_get_temp_dir(), 'bsale_zip_') . '.zip';
                
                if ($zip->open($tempZipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== TRUE) {
                    throw new \Exception('No se pudo crear ZIP BSale');
                }
                
                // Agregar archivos directamente desde storage (SIN COPIAR)
                foreach ($documents as $document) {
                    $filePath = \Illuminate\Support\Facades\Storage::path($document['path']);
                    
                    if (file_exists($filePath) && is_readable($filePath)) {
                        $fileContent = file_get_contents($filePath);
                        if ($fileContent !== false && strlen($fileContent) > 0) {
                            // Organize files in folders by year within the zip
                            $zipEntryName = $document['year'] ? 
                                $document['year'] . '/' . $document['filename'] : 
                                $document['filename'];
                            
                            $zip->addFromString($zipEntryName, $fileContent);
                        }
                    }
                }
                
                $zip->close();
                
                // Leer y enviar el ZIP
                if (file_exists($tempZipPath)) {
                    readfile($tempZipPath);
                    unlink($tempZipPath);
                }
                
            }, $fileName, [
                'Content-Type' => 'application/zip',
                'Content-Disposition' => 'attachment; filename="' . $fileName . '"'
            ]);

        } catch (\Exception $e) {
            Log::error('Error downloading BSale documents ZIP: ' . $e->getMessage());
            return response()->json(['error' => 'Error al descargar los documentos en ZIP: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Format bytes to human readable format
     */
    private function formatBytes($bytes)
    {
        if ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        } elseif ($bytes >= 1024) {
            return number_format($bytes / 1024, 2) . ' KB';
        } else {
            return $bytes . ' bytes';
        }
    }

    /**
     * Descargar comprobante de pago individual
     */
    public function downloadPaymentReceipt($paymentId)
    {
        try {
            $payment = \App\Models\Payment::with(['orderDetail.order.program', 'orderDetail.order.participant'])->findOrFail($paymentId);
            
            // Buscar archivo de comprobante existente
            $year = $payment->created_at->year;
            $filename = 'comprobante_pago_' . $payment->id . '.pdf';
            $filePath = storage_path("app/payment_receipts/{$year}/{$filename}");
            
            if (!file_exists($filePath)) {
                return response()->json(['error' => 'Comprobante no encontrado'], 404);
            }
            
            return response()->download($filePath);
            
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error al descargar comprobante: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Descargar contrato de reserva
     */
    public function downloadReservationContract($participantId, $programId)
    {
        try {
            $participant = \App\Models\Participant::findOrFail($participantId);
            $program = \App\Models\Program::findOrFail($programId);
            
            // Buscar archivo de contrato existente
            $filename = 'contrato_' . $participantId . '_' . $programId . '.pdf';
            $filePath = storage_path("app/contracts/{$filename}");
            
            if (!file_exists($filePath)) {
                return response()->json(['error' => 'Contrato no encontrado'], 404);
            }
            
            return response()->download($filePath);
            
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error al descargar contrato: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Método de diagnóstico para comparar archivos individuales vs ZIP
     */
    public function debugZipCreation($participantId, $programId)
    {
        try {
            $payments = \App\Models\Payment::whereHas('orderDetail.order', function($query) use ($participantId, $programId) {
                $query->where('participant_id', $participantId)
                      ->where('program_id', $programId);
            })->where('status', 'completed')->first();
            
            if (!$payments) {
                return response()->json(['error' => 'No se encontraron pagos'], 404);
            }
            
            $year = $payments->created_at->year;
            $filename = 'comprobante_pago_' . $payments->id . '.pdf';
            $filePath = storage_path("app/payment_receipts/{$year}/{$filename}");
            
            if (!file_exists($filePath)) {
                return response()->json(['error' => 'Archivo no encontrado'], 404);
            }
            
            // Leer archivo original
            $originalContent = file_get_contents($filePath);
            $originalSize = strlen($originalContent);
            $originalMd5 = md5($originalContent);
            
            // Crear ZIP simple con un solo archivo
            $zipPath = storage_path('app/temp/debug_test.zip');
            if (file_exists($zipPath)) {
                unlink($zipPath);
            }
            
            $zip = new \ZipArchive();
            if ($zip->open($zipPath, \ZipArchive::CREATE) !== TRUE) {
                return response()->json(['error' => 'No se pudo crear ZIP'], 500);
            }
            
            // Agregar archivo al ZIP
            $zip->addFromString($filename, $originalContent);
            $zip->close();
            
            // Verificar ZIP creado
            $zipSize = filesize($zipPath);
            
            // Extraer archivo del ZIP para comparar
            $extractZip = new \ZipArchive();
            if ($extractZip->open($zipPath) === TRUE) {
                $extractedContent = $extractZip->getFromName($filename);
                $extractedSize = strlen($extractedContent);
                $extractedMd5 = md5($extractedContent);
                $extractZip->close();
            } else {
                return response()->json(['error' => 'No se pudo leer el ZIP'], 500);
            }
            
            // Limpiar archivo temporal
            unlink($zipPath);
            
            return response()->json([
                'debug_info' => [
                    'original_file' => [
                        'path' => $filePath,
                        'size' => $originalSize,
                        'md5' => $originalMd5,
                        'readable' => is_readable($filePath),
                        'exists' => file_exists($filePath)
                    ],
                    'zip_process' => [
                        'zip_size' => $zipSize,
                        'zip_created' => file_exists($zipPath)
                    ],
                    'extracted_file' => [
                        'size' => $extractedSize,
                        'md5' => $extractedMd5,
                        'content_matches' => ($originalMd5 === $extractedMd5),
                        'size_matches' => ($originalSize === $extractedSize)
                    ],
                    'comparison' => [
                        'files_identical' => ($originalMd5 === $extractedMd5 && $originalSize === $extractedSize),
                        'size_difference' => $originalSize - $extractedSize,
                        'first_100_chars_original' => substr($originalContent, 0, 100),
                        'first_100_chars_extracted' => substr($extractedContent, 0, 100)
                    ]
                ]
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error en diagnóstico: ' . $e->getMessage(),
                'line' => $e->getLine(),
                'file' => basename($e->getFile())
            ], 500);
        }
    }

    /**
     * Descargar todos los comprobantes de pago como ZIP usando streaming directo
     */
    public function downloadAllPaymentReceipts($participantId, $programId)
    {
        try {
            $payments = \App\Models\Payment::whereHas('orderDetail.order', function($query) use ($participantId, $programId) {
                $query->where('participant_id', $participantId)
                      ->where('program_id', $programId);
            })->where('status', 'completed')->get();
            
            if ($payments->isEmpty()) {
                return response()->json(['error' => 'No se encontraron pagos completados'], 404);
            }
            
            // Usar streaming directo como SoftlandZipExporter
            $fileName = 'comprobantes_' . $participantId . '_' . $programId . '.zip';
            
            return response()->streamDownload(function () use ($payments, $participantId, $programId) {
                // Crear ZIP en memoria
                $zip = new \ZipArchive();
                $tempZipPath = tempnam(sys_get_temp_dir(), 'comprobantes_') . '.zip';
                
                if ($zip->open($tempZipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== TRUE) {
                    throw new \Exception('No se pudo crear ZIP');
                }
                
                // Agregar archivos directamente desde storage (SIN COPIAR)
                foreach ($payments as $payment) {
                    $year = $payment->created_at->year;
                    $filename = 'comprobante_pago_' . $payment->id . '.pdf';
                    $sourcePath = storage_path("app/payment_receipts/{$year}/{$filename}");
                    
                    if (file_exists($sourcePath) && is_readable($sourcePath)) {
                        $fileContent = file_get_contents($sourcePath);
                        if ($fileContent !== false && strlen($fileContent) > 0) {
                            $zip->addFromString($filename, $fileContent);
                        }
                    }
                }
                
                $zip->close();
                
                // Leer y enviar el ZIP
                if (file_exists($tempZipPath)) {
                    readfile($tempZipPath);
                    unlink($tempZipPath);
                }
                
            }, $fileName, [
                'Content-Type' => 'application/zip',
                'Content-Disposition' => 'attachment; filename="' . $fileName . '"'
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Descargar todos los documentos BSale como ZIP usando streaming directo
     */
    public function downloadAllBsaleDocuments($participantId, $programId)
    {
        try {
            $payments = \App\Models\Payment::whereHas('orderDetail.order', function($query) use ($participantId, $programId) {
                $query->where('participant_id', $participantId)
                      ->where('program_id', $programId);
            })->where('status', 'completed')
              ->whereNotNull('bsale_document_id')
              ->whereNotNull('bsale_number')
              ->get();
            
            if ($payments->isEmpty()) {
                return response()->json(['error' => 'No se encontraron documentos BSale'], 404);
            }
            
            // Usar streaming directo como SoftlandZipExporter
            $fileName = 'boletas_bsale_' . $participantId . '_' . $programId . '.zip';
            
            return response()->streamDownload(function () use ($payments, $participantId, $programId) {
                // Crear ZIP en memoria
                $zip = new \ZipArchive();
                $tempZipPath = tempnam(sys_get_temp_dir(), 'bsale_') . '.zip';
                
                if ($zip->open($tempZipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== TRUE) {
                    throw new \Exception('No se pudo crear ZIP BSale');
                }
                
                // Agregar archivos directamente desde storage (SIN COPIAR)
                foreach ($payments as $payment) {
                    $year = $payment->created_at->year;
                    $originalFilename = 'bsale_' . $payment->bsale_number . '_payment_' . $payment->id . '.pdf';
                    $sourcePath = storage_path("app/bsale_documents/{$year}/{$originalFilename}");
                    
                    if (file_exists($sourcePath) && is_readable($sourcePath)) {
                        $fileContent = file_get_contents($sourcePath);
                        if ($fileContent !== false && strlen($fileContent) > 0) {
                            $destFilename = 'boleta_' . $payment->bsale_number . '.pdf';
                            $zip->addFromString($destFilename, $fileContent);
                        }
                    }
                }
                
                $zip->close();
                
                // Leer y enviar el ZIP
                if (file_exists($tempZipPath)) {
                    readfile($tempZipPath);
                    unlink($tempZipPath);
                }
                
            }, $fileName, [
                'Content-Type' => 'application/zip',
                'Content-Disposition' => 'attachment; filename="' . $fileName . '"'
            ]);
            
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Get BSale documents for ZIP (moved from BsaleZipDownloadService)
     */
    private function getBsaleDocumentsForZip(array $filters)
    {
        $documents = collect();
        $basePath = 'bsale_documents';
        $year = $filters['year'] ?? null;
        $search = $filters['search'] ?? null;
        
        // If year is specified, look in that year's folder
        if ($year) {
            $yearPath = $basePath . '/' . $year;
            if (\Illuminate\Support\Facades\Storage::exists($yearPath)) {
                $files = \Illuminate\Support\Facades\Storage::files($yearPath);
                foreach ($files as $file) {
                    $documents->push($this->formatBsaleDocument($file));
                }
            }
        } else {
            // Get all years
            $yearFolders = \Illuminate\Support\Facades\Storage::directories($basePath);
            foreach ($yearFolders as $yearFolder) {
                $files = \Illuminate\Support\Facades\Storage::files($yearFolder);
                foreach ($files as $file) {
                    $documents->push($this->formatBsaleDocument($file));
                }
            }
        }

        // Filter by search term if provided
        if ($search) {
            $documents = $documents->filter(function ($doc) use ($search) {
                return stripos($doc['filename'], $search) !== false ||
                       stripos($doc['bsale_number'], $search) !== false ||
                       stripos($doc['payment_id'], $search) !== false;
            });
        }

        return $documents->sortBy('filename');
    }

    /**
     * Generate BSale ZIP filename (moved from BsaleZipDownloadService)
     */
    private function generateBsaleZipFileName(array $filters): string
    {
        $timestamp = now('America/Santiago')->format('Y-m-d_H-i-s');
        $year = $filters['year'] ?? null;
        $search = $filters['search'] ?? null;
        
        if ($year && $search) {
            $searchSafe = preg_replace('/[^a-zA-Z0-9_-]/', '_', $search);
            return "bsale_documentos_{$year}_{$searchSafe}_{$timestamp}.zip";
        } elseif ($year) {
            return "bsale_documentos_{$year}_{$timestamp}.zip";
        } elseif ($search) {
            $searchSafe = preg_replace('/[^a-zA-Z0-9_-]/', '_', $search);
            return "bsale_documentos_busqueda_{$searchSafe}_{$timestamp}.zip";
        } else {
            return "bsale_documentos_todos_{$timestamp}.zip";
        }
    }

    /**
     * Validate PDF file integrity
     */
    private function validatePdfFile(string $filePath): bool
    {
        try {
            // Check if file exists and is readable
            if (!file_exists($filePath) || !is_readable($filePath)) {
                return false;
            }

            // Check file size (should be at least 1KB for a valid PDF)
            $fileSize = filesize($filePath);
            if ($fileSize < 1024) {
                return false;
            }

            // Read first few bytes to check PDF header
            $handle = fopen($filePath, 'rb');
            if (!$handle) {
                return false;
            }

            $header = fread($handle, 4);
            fclose($handle);

            // Check if it starts with PDF header
            if ($header !== '%PDF') {
                return false;
            }

            // Additional check: try to read the file content
            $content = file_get_contents($filePath);
            if ($content === false || strlen($content) !== $fileSize) {
                return false;
            }

            return true;
        } catch (\Exception $e) {
            Log::warning("Error validating PDF file {$filePath}: " . $e->getMessage());
            return false;
        }
    }
}