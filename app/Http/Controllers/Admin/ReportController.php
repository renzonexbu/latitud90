<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Program;
use App\Models\ProgramCourse;
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
use App\Models\OrderDetail;
use App\Services\EcommerceAnalyticsService;
use App\Services\Admin\Reports\Softland\ExcelExporter as SoftlandExcelExporter;
use App\Services\Admin\Reports\Softland\AuxiliaresExporter;
use App\Services\Admin\Reports\Softland\SoftlandAuxiliaresService;
use App\Services\Admin\Reports\Softland\SoftlandZipExporter;
use App\Services\Admin\Reports\BsaleDocuments\BsaleZipDownloadService;
use App\Services\Admin\Reports\TermsAcceptance\TermsAcceptanceDataProvider;
use App\Services\Admin\Reports\PaidInstallments\PaidInstallmentsDataProvider;
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
        // Si el usuario es ejecutivo comercial (y no es contabilidad ni super admin),
        // redirigir a reportes de ejecutivos
        $user = auth()->user();
        if ($user && $user->hasRole('ejecutivo_comercial') &&
            !$user->hasRole('contabilidad') &&
            !$user->hasRole('super_admin')) {
            return redirect()->route('admin.reports.executives.index');
        }

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
            $filters = $request->only(['dateFrom', 'dateTo', 'programId', 'participantId', 'participantSearch', 'paymentStatus', 'participantStatusFilter', 'page']);
            
            
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
            'salesExecutives' => SalesExecutive::select('id', 'name')->where('active', true)->orderBy('name')->get(),
            'filters' => $request->all()
        ]);
    }

    public function exportNoPayment(Request $request)
    {
        try {
            $filters = $request->only(['program_id', 'sales_executive_id', 'search']);

            // Obtener todos los participantes sin pagos (sin paginación)
            $dataProvider = new PaymentScheduleSummaryDataProvider();
            $participants = $dataProvider->getParticipantsWithoutPayments($filters);

            // Crear el Excel
            $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle('Participantes Sin Pagos');

            // Verificar si tiene permiso para ver contacto pagador
            $canViewPayerContact = auth()->user()->hasPermissionTo('ver_contacto_pagador');

            // Headers base
            $headers = [
                'Participante',
                'Documento',
                'Email Participante',
            ];

            // Agregar columnas de contacto pagador solo si tiene permiso
            if ($canViewPayerContact) {
                $headers = array_merge($headers, [
                    'Contacto Pagador',
                    'Email Contacto Pagador',
                    'Teléfono Contacto Pagador',
                    'Relación',
                ]);
            }

            // Agregar resto de columnas
            $headers = array_merge($headers, [
                'Fecha Inscripción',
                'Fecha Final Pago',
                'Precio Individual',
                'Descuento',
                'Monto Final',
                'Programa',
                'Código Programa',
                'Ejecutivo Comercial'
            ]);

            // Estilo para headers
            $headerStyle = [
                'font' => [
                    'bold' => true,
                    'color' => ['rgb' => 'FFFFFF'],
                ],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '1c4f4a'],
                ],
                'alignment' => [
                    'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                    'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
                ],
            ];

            // Escribir headers
            $colIndex = 0;
            foreach ($headers as $header) {
                $col = chr(65 + $colIndex);
                $sheet->setCellValue($col . '1', $header);
                $colIndex++;
            }

            // Aplicar estilo a headers
            $sheet->getStyle('A1:' . chr(65 + count($headers) - 1) . '1')->applyFromArray($headerStyle);

            // Escribir datos
            $rowIndex = 2;
            foreach ($participants as $participant) {
                $fullName = trim(
                    ($participant->first_name ?? '') . ' ' .
                    ($participant->first_last_name ?? '') . ' ' .
                    ($participant->second_last_name ?? '')
                );

                $incorporationDate = isset($participant->incorporation_date)
                    ? date('d/m/Y', strtotime($participant->incorporation_date))
                    : 'N/A';

                $finalPaymentDate = isset($participant->final_payment_date)
                    ? date('d/m/Y', strtotime($participant->final_payment_date))
                    : 'N/A';

                $sheet->setCellValue('A' . $rowIndex, $fullName);
                $sheet->setCellValue('B' . $rowIndex, $this->formatRut($participant->document_number ?? ''));
                $sheet->setCellValue('C' . $rowIndex, $participant->participant_email ?? 'N/A');

                if ($canViewPayerContact) {
                    // Con columnas de contacto pagador
                    $sheet->setCellValue('D' . $rowIndex, $participant->emergency_contact_name ?? 'Sin contacto');
                    $sheet->setCellValue('E' . $rowIndex, $participant->emergency_contact_email ?? 'Sin email');
                    $sheet->setCellValue('F' . $rowIndex, $participant->emergency_contact_phone ?? 'N/A');
                    $sheet->setCellValue('G' . $rowIndex, $participant->emergency_contact_relationship ?? 'N/A');
                    $sheet->setCellValue('H' . $rowIndex, $incorporationDate);
                    $sheet->setCellValue('I' . $rowIndex, $finalPaymentDate);
                    $sheet->setCellValue('J' . $rowIndex, (int)($participant->individual_price ?? 0));
                    $sheet->setCellValue('K' . $rowIndex, (int)($participant->discount_amount ?? 0));
                    $sheet->setCellValue('L' . $rowIndex, (int)($participant->final_amount ?? 0));
                    $sheet->setCellValue('M' . $rowIndex, $participant->program_name ?? 'N/A');
                    $sheet->setCellValue('N' . $rowIndex, $participant->program_code ?? 'N/A');
                    $sheet->setCellValue('O' . $rowIndex, $participant->sales_executive_name ?? 'Sin asignar');
                } else {
                    // Sin columnas de contacto pagador
                    $sheet->setCellValue('D' . $rowIndex, $incorporationDate);
                    $sheet->setCellValue('E' . $rowIndex, $finalPaymentDate);
                    $sheet->setCellValue('F' . $rowIndex, (int)($participant->individual_price ?? 0));
                    $sheet->setCellValue('G' . $rowIndex, (int)($participant->discount_amount ?? 0));
                    $sheet->setCellValue('H' . $rowIndex, (int)($participant->final_amount ?? 0));
                    $sheet->setCellValue('I' . $rowIndex, $participant->program_name ?? 'N/A');
                    $sheet->setCellValue('J' . $rowIndex, $participant->program_code ?? 'N/A');
                    $sheet->setCellValue('K' . $rowIndex, $participant->sales_executive_name ?? 'Sin asignar');
                }

                $rowIndex++;
            }

            // Auto-ajustar columnas
            foreach (range('A', chr(65 + count($headers) - 1)) as $col) {
                $sheet->getColumnDimension($col)->setAutoSize(true);
            }

            // Bordes para toda la tabla
            $borderStyle = [
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                        'color' => ['rgb' => '000000'],
                    ],
                ],
            ];

            if ($rowIndex > 2) {
                $sheet->getStyle('A1:' . chr(65 + count($headers) - 1) . ($rowIndex - 1))->applyFromArray($borderStyle);
            }

            // Generar nombre de archivo
            $filename = 'participantes_sin_pagos_' . now('America/Santiago')->format('Y-m-d_H-i-s');

            // Limpiar buffers
            while (ob_get_level()) {
                ob_end_clean();
            }

            // Crear respuesta streaming
            $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
            $writer->setPreCalculateFormulas(false);
            $writer->setIncludeCharts(false);

            $response = new \Symfony\Component\HttpFoundation\StreamedResponse(function () use ($writer) {
                $writer->save('php://output');
            });

            $response->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            $response->headers->set('Content-Disposition', 'attachment; filename="' . $filename . '.xlsx"');
            $response->headers->set('Cache-Control', 'no-cache, must-revalidate');
            $response->headers->set('Expires', '0');
            $response->headers->set('Pragma', 'public');

            return $response;

        } catch (\Exception $e) {
            return response()->json(['error' => 'Error al exportar: ' . $e->getMessage()], 500);
        }
    }

    private function formatRut($rut): string
    {
        if (!$rut) return 'N/A';

        // Limpiar el RUT de puntos y guiones
        $rutLimpio = preg_replace('/[^0-9kK]/', '', (string)$rut);

        if (strlen($rutLimpio) < 2) return (string)$rut;

        // Separar número y dígito verificador
        $dv = substr($rutLimpio, -1);
        $numero = substr($rutLimpio, 0, -1);

        // Formatear número con puntos
        $numeroFormateado = number_format((int)$numero, 0, '', '.');

        // Retornar RUT formateado
        return $numeroFormateado . '-' . strtoupper($dv);
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
            $filters = $request->only(['dateFrom', 'dateTo', 'programId', 'status', 'perPage']);

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
                'programs' => \App\Models\ProgramCourse::select('id', 'code', 'name', 'destination')->where('active', true)->orderBy('code')->get(),
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
            $format = $request->get('format', 'xlsx');
            $fieldsJson = $request->get('fields', '{}');
            $selectedFields = is_string($fieldsJson) ? json_decode($fieldsJson, true) ?? [] : (array) $fieldsJson;

            // Obtener todos los datos para exportar (sin paginación)
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
            $filters = $request->only(['dateFrom', 'dateTo', 'programId', 'participantId', 'paymentStatus', 'participantStatusFilter']);
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

                // Identificar columna de porcentaje
                $percentageCol = null;
                $col = 'A';
                foreach ($headers as $header) {
                    $sheet->setCellValue($col . '1', $header);
                    if (stripos($header, 'Progreso de Pago') !== false || stripos($header, 'porcentaje') !== false) {
                        $percentageCol = $col;
                    }
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

                // Aplicar formato de porcentaje a la columna correspondiente
                if ($percentageCol && $row > 2) {
                    $sheet->getStyle($percentageCol . '2:' . $percentageCol . ($row - 1))
                        ->getNumberFormat()
                        ->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_PERCENTAGE_00);
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

            // Obtener datos detallados con información de participantes para la hoja de detalles
            $detailData = $this->paymentScheduleDetailService->getScheduleDetails($filters);

            // Agregar los datos detallados al array de datos
            $data['paymentSchedules'] = $detailData;

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
        $programs = \App\Models\ProgramCourse::select('id', 'name', 'code')->where('active', true)->orderBy('name')->get();

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

            Log::info('exportSoftlandZip: Filtros recibidos', [
                'dateFrom' => $dateFrom,
                'dateTo' => $dateTo,
                'programId' => $programId,
                'filters' => $filters,
                'all_request' => $request->all()
            ]);

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
     * Get list of all generated documents (comprobantes, contratos, boletas Bsale)
     */
    public function bsaleDocumentsList(Request $request)
    {
        try {
            $year = $request->get('year');
            $search = $request->get('search');
            $documentType = $request->get('document_type');
            $perPage = (int) $request->get('per_page', 50);

            // Query generated_documents table
            $query = \App\Models\GeneratedDocument::with(['payment', 'orderDetail', 'participant', 'program'])
                ->orderBy('created_at', 'desc');

            // Filter by year
            if ($year) {
                $query->whereYear('created_at', $year);
            }

            // Filter by document type
            if ($documentType) {
                $query->where('document_type', $documentType);
            }

            // Filter by search term
            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('file_name', 'like', "%{$search}%")
                      ->orWhere('email_sent_to', 'like', "%{$search}%")
                      ->orWhereHas('participant', function ($pq) use ($search) {
                          $pq->where('first_name', 'like', "%{$search}%")
                             ->orWhere('last_name_1', 'like', "%{$search}%")
                             ->orWhere('last_name_2', 'like', "%{$search}%");
                      })
                      ->orWhereHas('program', function ($pgq) use ($search) {
                          $pgq->where('name', 'like', "%{$search}%");
                      });
                });
            }

            // Paginate
            $paginated = $query->paginate($perPage);

            // Format documents for frontend
            $documents = $paginated->map(function ($doc) {
                return [
                    'id' => $doc->id,
                    'filename' => $doc->file_name,
                    'document_type' => $doc->document_type,
                    'document_type_label' => $doc->getTypeLabel(),
                    'file_size' => $doc->file_size,
                    'size_formatted' => $doc->getFileSizeFormatted(),
                    'created_at' => $doc->created_at?->format('d/m/Y H:i'),
                    'email_sent' => $doc->email_sent,
                    'email_sent_at' => $doc->email_sent_at?->format('d/m/Y H:i'),
                    'email_sent_to' => $doc->email_sent_to,
                    'email_send_count' => $doc->email_send_count,
                    'payment_id' => $doc->payment_id,
                    'order_number' => $doc->orderDetail?->order?->order_number,
                    'participant_name' => $doc->participant?->full_name,
                    'program_name' => $doc->program?->name,
                    'bsale_number' => $doc->metadata['bsale_number'] ?? null,
                    'year' => $doc->created_at?->year,
                    'file_exists' => $doc->exists(),
                ];
            });

            // Get available years
            $availableYears = \App\Models\GeneratedDocument::selectRaw('YEAR(created_at) as year')
                ->distinct()
                ->orderBy('year', 'desc')
                ->pluck('year');

            return response()->json([
                'documents' => $documents,
                'total' => $paginated->total(),
                'per_page' => $paginated->perPage(),
                'current_page' => $paginated->currentPage(),
                'last_page' => $paginated->lastPage(),
                'available_years' => $availableYears
            ]);

        } catch (\Exception $e) {
            Log::error('Error listing generated documents: ' . $e->getMessage(), [
                'exception' => $e,
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['error' => 'Error al listar documentos: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Download a generated document by ID
     */
    public function downloadBsaleDocument($documentId): BinaryFileResponse
    {
        try {
            // Try to find as document ID first
            $document = \App\Models\GeneratedDocument::find($documentId);

            // If not found and it looks like a filename, try the old way for backwards compatibility
            if (!$document && is_string($documentId) && str_contains($documentId, '.pdf')) {
                return $this->downloadBsaleDocumentLegacy($documentId);
            }

            if (!$document) {
                abort(404, 'Documento no encontrado');
            }

            $fullPath = $document->getFullPath();

            if (!file_exists($fullPath)) {
                abort(404, 'Archivo físico no encontrado');
            }

            return response()->download($fullPath, $document->file_name, [
                'Content-Type' => 'application/pdf',
            ]);

        } catch (\Exception $e) {
            Log::error('Error downloading document: ' . $e->getMessage());
            abort(500, 'Error al descargar el documento');
        }
    }

    /**
     * Download a BSale document by filename (legacy support)
     */
    private function downloadBsaleDocumentLegacy(string $filename): BinaryFileResponse
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
            Log::error('Error downloading BSale document (legacy): ' . $e->getMessage());
            abort(500, 'Error al descargar el documento');
        }
    }

    /**
     * Resend a document by email
     */
    public function resendDocument(Request $request, $documentId)
    {
        try {
            $document = \App\Models\GeneratedDocument::with(['orderDetail', 'payment'])->findOrFail($documentId);

            $email = $request->input('email') ?? $document->email_sent_to ?? $document->orderDetail?->email;

            if (!$email) {
                return response()->json(['error' => 'No se encontró un email de destino'], 400);
            }

            if (!$document->exists()) {
                return response()->json(['error' => 'El archivo físico no existe'], 404);
            }

            // Send email with document attached
            $orderDetail = $document->orderDetail;
            $payment = $document->payment;

            if (!$orderDetail || !$payment) {
                return response()->json(['error' => 'Faltan datos necesarios para enviar el documento'], 400);
            }

            Mail::send('Mails.resend_document', [
                'customer_name' => $orderDetail->name,
                'document_type' => $document->getTypeLabel(),
                'company_name' => config('lat90.company.name'),
            ], function ($message) use ($document, $email, $orderDetail) {
                $message->to($email, $orderDetail->name)
                    ->subject('Reenvío de documento - ' . $document->getTypeLabel())
                    ->attach($document->getFullPath(), [
                        'as' => $document->file_name,
                        'mime' => 'application/pdf',
                    ]);
            });

            // Mark as sent again
            $document->markAsEmailSent($email);

            return response()->json([
                'success' => true,
                'message' => 'Documento reenviado exitosamente a ' . $email
            ]);

        } catch (\Exception $e) {
            Log::error('Error resending document: ' . $e->getMessage(), [
                'document_id' => $documentId,
                'exception' => $e
            ]);
            return response()->json(['error' => 'Error al reenviar el documento: ' . $e->getMessage()], 500);
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

    /**
     * Reporte de aceptación de términos y condiciones
     */
    public function termsAcceptance(Request $request)
    {
        $dataProvider = new TermsAcceptanceDataProvider();
        $termsAcceptanceData = $dataProvider->getData($request->all());

        // Paginación
        $perPage = 15;
        $currentPage = $request->get('page', 1);
        $total = $termsAcceptanceData->count();
        $items = $termsAcceptanceData->forPage($currentPage, $perPage)->values();

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

        return Inertia::render('Admin/Reports/TermsAcceptance', [
            'termsAcceptanceData' => $paginatedData,
            'programs' => ProgramCourse::select('id', 'code', 'name')->orderBy('code', 'desc')->get(),
            'filters' => $request->all()
        ]);
    }

    /**
     * Exportar reporte de aceptación de términos a Excel
     */
    public function exportTermsAcceptance(Request $request)
    {
        try {
            $filters = $request->only(['program_id', 'date_from', 'date_to', 'search']);

            $dataProvider = new TermsAcceptanceDataProvider();
            $data = $dataProvider->getData($filters);

            $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle('Aceptación TyC');

            // Headers en el orden especificado
            $headers = [
                'Nombre del Pagador',
                'RUT',
                'Documento',
                'Nombre del Participante',
                'Código del Programa',
                'Fecha de Aceptación',
            ];

            // Estilo para headers
            $headerStyle = [
                'font' => [
                    'bold' => true,
                    'color' => ['rgb' => 'FFFFFF'],
                ],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '1c4f4a'],
                ],
                'alignment' => [
                    'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                    'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
                ],
            ];

            // Escribir headers
            foreach ($headers as $index => $header) {
                $col = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($index + 1);
                $sheet->setCellValue("{$col}1", $header);
                $sheet->getStyle("{$col}1")->applyFromArray($headerStyle);
                $sheet->getColumnDimension($col)->setAutoSize(true);
            }

            // Escribir datos
            $row = 2;
            foreach ($data as $record) {
                $sheet->setCellValue("A{$row}", $record['pagador_name'] ?? '');
                $sheet->setCellValueExplicit("B{$row}", $record['document_number'] ?? '', \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
                $sheet->setCellValue("C{$row}", $record['document_type'] ?? '');
                $sheet->setCellValue("D{$row}", $record['participant_name'] ?? '');
                $sheet->setCellValue("E{$row}", $record['program_code'] ?? '');
                $sheet->setCellValue("F{$row}", $record['terms_accepted_at'] ?? '');
                $row++;
            }

            // Crear archivo temporal
            $filename = 'aceptacion_tyc_' . now('America/Santiago')->format('Y-m-d_H-i-s') . '.xlsx';
            $tempPath = storage_path('app/temp/' . $filename);

            if (!file_exists(storage_path('app/temp'))) {
                mkdir(storage_path('app/temp'), 0755, true);
            }

            $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
            $writer->save($tempPath);

            return response()->download($tempPath, $filename)->deleteFileAfterSend(true);
        } catch (\Exception $e) {
            Log::error('Error al exportar reporte de aceptación TyC: ' . $e->getMessage());
            return response()->json(['error' => 'Error al exportar: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Descargar PDF de evidencia de aceptación de términos y condiciones
     */
    public function downloadTermsAcceptancePdf(int $orderDetailId)
    {
        try {
            $orderDetail = OrderDetail::with(['order.programCourse', 'order.participant', 'termsCondition'])
                ->findOrFail($orderDetailId);

            // Verificar que tenga términos aceptados
            if (!$orderDetail->terms_accepted || !$orderDetail->terms_accepted_at) {
                return response()->json([
                    'error' => 'Este registro no tiene términos y condiciones aceptados'
                ], 400);
            }

            $pdfService = new \App\Services\PDF\TermsAcceptanceEvidenceService();
            return $pdfService->download($orderDetail);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['error' => 'Registro no encontrado'], 404);
        } catch (\Exception $e) {
            Log::error('Error al generar PDF de evidencia TyC: ' . $e->getMessage());
            return response()->json(['error' => 'Error al generar PDF: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Descargar múltiples PDFs de evidencia de aceptación de términos como ZIP
     */
    public function downloadTermsAcceptancePdfsZip(Request $request)
    {
        try {
            $ids = $request->input('ids', []);

            if (empty($ids)) {
                return response()->json(['error' => 'No se seleccionaron registros'], 400);
            }

            // Limitar a 50 PDFs por descarga para evitar sobrecarga
            if (count($ids) > 50) {
                return response()->json(['error' => 'Máximo 50 PDFs por descarga'], 400);
            }

            $orderDetails = OrderDetail::with(['order.programCourse', 'order.participant', 'termsCondition'])
                ->whereIn('id', $ids)
                ->where('terms_accepted', true)
                ->whereNotNull('terms_accepted_at')
                ->get();

            if ($orderDetails->isEmpty()) {
                return response()->json(['error' => 'No se encontraron registros válidos'], 404);
            }

            $pdfService = new \App\Services\PDF\TermsAcceptanceEvidenceService();
            $tempFiles = [];
            $zipFilename = 'evidencias_tyc_' . date('Y-m-d_His') . '.zip';
            $zipPath = storage_path('app/temp/' . $zipFilename);

            // Asegurar que el directorio existe
            if (!file_exists(dirname($zipPath))) {
                mkdir(dirname($zipPath), 0755, true);
            }

            // Crear archivo ZIP
            $zip = new \ZipArchive();
            if ($zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
                return response()->json(['error' => 'Error al crear archivo ZIP'], 500);
            }

            foreach ($orderDetails as $orderDetail) {
                try {
                    $pdfPath = $pdfService->generatePdf($orderDetail);
                    $tempFiles[] = $pdfPath;

                    // Nombre del archivo en el ZIP
                    $pdfName = 'Evidencia_TyC_' . preg_replace('/[^A-Za-z0-9_\-]/', '_', $orderDetail->name) . '_' . $orderDetail->id . '.pdf';
                    $zip->addFile($pdfPath, $pdfName);
                } catch (\Exception $e) {
                    Log::warning('Error generando PDF para order_detail ' . $orderDetail->id . ': ' . $e->getMessage());
                    continue;
                }
            }

            $zip->close();

            // Limpiar archivos temporales de PDFs después de cerrar el ZIP
            foreach ($tempFiles as $tempFile) {
                if (file_exists($tempFile)) {
                    @unlink($tempFile);
                }
            }

            // Retornar el ZIP para descarga
            return response()->download($zipPath, $zipFilename)->deleteFileAfterSend(true);

        } catch (\Exception $e) {
            Log::error('Error al generar ZIP de evidencias TyC: ' . $e->getMessage());
            return response()->json(['error' => 'Error al generar ZIP: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Reporte de cuotas pagadas
     */
    public function paidInstallments(Request $request)
    {
        $dataProvider = new PaidInstallmentsDataProvider();
        $paidInstallmentsData = $dataProvider->getData($request->all());

        // Paginación
        $perPage = 15;
        $currentPage = $request->get('page', 1);
        $total = $paidInstallmentsData->count();
        $items = $paidInstallmentsData->forPage($currentPage, $perPage)->values();

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

        return Inertia::render('Admin/Reports/PaidInstallments', [
            'paidInstallmentsData' => $paginatedData,
            'programs' => ProgramCourse::select('id', 'code', 'name')->orderBy('code', 'desc')->get(),
            'filters' => $request->all()
        ]);
    }

    /**
     * Exportar reporte de cuotas pagadas a Excel
     */
    public function exportPaidInstallments(Request $request)
    {
        try {
            $filters = $request->only(['program_id', 'date_from', 'date_to', 'search']);

            $dataProvider = new PaidInstallmentsDataProvider();
            $data = $dataProvider->getData($filters);

            $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle('Cuotas Pagadas');

            // Headers
            $headers = [
                'Código de Inscripción',
                'Participante',
                'Monto Recaudado',
                'Fecha',
                'Cuota',
                'Total Pagado',
                'Saldo',
            ];

            // Estilo para headers
            $headerStyle = [
                'font' => [
                    'bold' => true,
                    'color' => ['rgb' => 'FFFFFF'],
                ],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '1c4f4a'],
                ],
                'alignment' => [
                    'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                    'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
                ],
            ];

            // Escribir headers
            foreach ($headers as $index => $header) {
                $col = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($index + 1);
                $sheet->setCellValue("{$col}1", $header);
                $sheet->getStyle("{$col}1")->applyFromArray($headerStyle);
                $sheet->getColumnDimension($col)->setAutoSize(true);
            }

            // Escribir datos
            $row = 2;
            foreach ($data as $record) {
                $sheet->setCellValueExplicit("A{$row}", $record['enrollment_code'] ?? '', \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
                $sheet->setCellValue("B{$row}", $record['participant_name'] ?? '');
                $sheet->setCellValue("C{$row}", round($record['amount'] ?? 0));
                $sheet->setCellValue("D{$row}", $record['paid_at'] ?? '');
                $sheet->setCellValue("E{$row}", $record['installment_label'] ?? '');
                $sheet->setCellValue("F{$row}", round($record['total_paid'] ?? 0));
                $sheet->setCellValue("G{$row}", round($record['saldo'] ?? 0));
                $row++;
            }

            // Formato de moneda para columnas C (Monto Recaudado), F (Total Pagado), G (Saldo)
            $lastRow = max($row - 1, 2);
            $sheet->getStyle("C2:C{$lastRow}")->getNumberFormat()->setFormatCode('#,##0');
            $sheet->getStyle("F2:F{$lastRow}")->getNumberFormat()->setFormatCode('#,##0');
            $sheet->getStyle("G2:G{$lastRow}")->getNumberFormat()->setFormatCode('#,##0');

            // Crear archivo temporal
            $filename = 'cuotas_pagadas_' . now('America/Santiago')->format('Y-m-d_H-i-s') . '.xlsx';
            $tempPath = storage_path('app/temp/' . $filename);

            if (!file_exists(storage_path('app/temp'))) {
                mkdir(storage_path('app/temp'), 0755, true);
            }

            $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
            $writer->save($tempPath);

            return response()->download($tempPath, $filename)->deleteFileAfterSend(true);
        } catch (\Exception $e) {
            Log::error('Error al exportar reporte de cuotas pagadas: ' . $e->getMessage());
            return response()->json(['error' => 'Error al exportar: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Reporte simple para TI: Numero de Negocio y Monto Recaudado
     */
    public function itSimpleReport(Request $request)
    {
        $dataProvider = new \App\Services\Admin\Reports\ITSimple\ITSimpleReportDataProvider();
        $data = $dataProvider->getData($request->all());

        // Paginación
        $perPage = 20;
        $currentPage = $request->get('page', 1);
        $total = $data->count();
        $items = $data->forPage($currentPage, $perPage)->values();

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

        // Calcular totales
        $totals = [
            'total_collected' => $data->sum('total_collected'),
            'total_installments' => $data->sum('installments_count'),
            'total_programs' => $data->count(),
        ];

        return Inertia::render('Admin/Reports/ITSimpleReport', [
            'reportData' => $paginatedData,
            'programs' => ProgramCourse::select('id', 'code', 'name')->orderBy('code', 'desc')->get(),
            'filters' => $request->all(),
            'totals' => $totals,
        ]);
    }

    /**
     * Exportar reporte simple TI a Excel
     */
    public function exportItSimpleReport(Request $request)
    {
        try {
            $filters = $request->only(['program_id', 'date_from', 'date_to']);

            $dataProvider = new \App\Services\Admin\Reports\ITSimple\ITSimpleReportDataProvider();
            $data = $dataProvider->getData($filters);

            $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle('Reporte TI');

            // Headers
            $headers = [
                'Número de Negocio',
                'Monto Recaudado',
                'Cantidad Cuotas',
            ];

            // Estilo para headers
            $headerStyle = [
                'font' => [
                    'bold' => true,
                    'color' => ['rgb' => 'FFFFFF'],
                ],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '1c4f4a'],
                ],
                'alignment' => [
                    'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                    'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
                ],
            ];

            // Escribir headers
            foreach ($headers as $index => $header) {
                $col = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($index + 1);
                $sheet->setCellValue("{$col}1", $header);
                $sheet->getStyle("{$col}1")->applyFromArray($headerStyle);
                $sheet->getColumnDimension($col)->setAutoSize(true);
            }

            // Escribir datos
            $row = 2;
            foreach ($data as $record) {
                $sheet->setCellValue("A{$row}", $record['program_code']);
                $sheet->setCellValue("B{$row}", $record['total_collected']);
                $sheet->setCellValue("C{$row}", $record['installments_count']);

                // Formatear monto como número
                $sheet->getStyle("B{$row}")->getNumberFormat()
                    ->setFormatCode('#,##0');

                $row++;
            }

            // Agregar fila de totales
            $totalRow = $row;
            $sheet->setCellValue("A{$totalRow}", 'TOTAL');
            $sheet->setCellValue("B{$totalRow}", $data->sum('total_collected'));
            $sheet->setCellValue("C{$totalRow}", $data->sum('installments_count'));

            // Estilo para totales
            $totalStyle = [
                'font' => ['bold' => true],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'E0E0E0'],
                ],
            ];
            $sheet->getStyle("A{$totalRow}:C{$totalRow}")->applyFromArray($totalStyle);
            $sheet->getStyle("B{$totalRow}")->getNumberFormat()->setFormatCode('#,##0');

            // Preparar respuesta
            $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);

            $filename = 'Reporte_TI_' . date('Y-m-d_His') . '.xlsx';

            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment;filename="' . $filename . '"');
            header('Cache-Control: max-age=0');

            $writer->save('php://output');
            exit;

        } catch (\Exception $e) {
            Log::error('Error al exportar reporte TI: ' . $e->getMessage());
            return response()->json(['error' => 'Error al exportar: ' . $e->getMessage()], 500);
        }
    }
}