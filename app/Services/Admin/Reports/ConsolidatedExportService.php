<?php

namespace App\Services\Admin\Reports;

use Illuminate\Support\Collection;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use Symfony\Component\HttpFoundation\StreamedResponse;
use App\Services\Admin\Reports\DailyPayments\DailyPaymentsService;
use App\Services\Admin\Reports\ConsolidatedPayments\ConsolidatedPaymentsService;
use App\Services\Admin\Reports\RecoverySchedule\RecoveryScheduleService;
use App\Services\Admin\Reports\PartialReport\PartialAccountService;
use App\Traits\AdminLogging;

class ConsolidatedExportService
{
    use AdminLogging;
    public function __construct(
        private DailyPaymentsService $dailyPaymentsService,
        private ConsolidatedPaymentsService $consolidatedPaymentsService,
        private RecoveryScheduleService $recoveryScheduleService,
        private PartialAccountService $partialAccountService
    ) {}

    public function export(array $filters = []): StreamedResponse
    {
        try {
            // Limpiar cualquier output buffer
            while (ob_get_level()) {
                ob_end_clean();
            }

            $spreadsheet = new Spreadsheet();
            
            // Crear pestañas para cada reporte
            $this->createDailyPaymentsSheet($spreadsheet, $filters);
            $this->createConsolidatedPaymentsSheet($spreadsheet, $filters);
            $this->createPaymentScheduleSheet($spreadsheet, $filters);
            $this->createPartialAccountSheet($spreadsheet, $filters);
            $this->createSummarySheet($spreadsheet, $filters);

            // Configurar writer XLSX
            $writer = new Xlsx($spreadsheet);
            $writer->setPreCalculateFormulas(false);
            $writer->setIncludeCharts(false);

            // Generar nombre de archivo
            $filename = 'reporte_consolidado_' . now()->format('Y-m-d_H-i-s');

            // Crear respuesta streaming
            $response = new StreamedResponse(function () use ($writer) {
                $writer->save('php://output');
            });

            // Headers para Excel
            $response->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            $response->headers->set('Content-Disposition', 'attachment; filename="' . $filename . '.xlsx"');
            $response->headers->set('Cache-Control', 'no-cache, must-revalidate');
            $response->headers->set('Expires', '0');
            $response->headers->set('Pragma', 'public');

            // Log the export action
            $this->logExport(
                'reports',
                "Exportación de reporte consolidado: {$filename}",
                [
                    'filename' => $filename,
                    'filters' => $filters,
                    'sheets_count' => 5, // Daily payments, consolidated payments, payment schedule, partial account, summary
                    'export_type' => 'consolidated_excel',
                ]
            );

            return $response;

        } catch (\Exception $e) {
            throw new \RuntimeException('Error al exportar reporte consolidado: ' . $e->getMessage());
        }
    }

    private function createDailyPaymentsSheet(Spreadsheet $spreadsheet, array $filters): void
    {
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Pagos Diarios');

        // Obtener datos
        $data = $this->dailyPaymentsService->getAllDailyPayments($filters, []);

        if ($data->isEmpty()) {
            $this->addEmptySheetMessage($sheet, 'No hay datos de pagos diarios');
            return;
        }

        // Headers
        $firstItem = $data->first();
        if (!$firstItem || !is_array($firstItem)) {
            $this->addEmptySheetMessage($sheet, 'Formato de datos inválido para pagos diarios');
            return;
        }

        $headers = array_keys($firstItem);
        $this->writeHeaders($sheet, $headers);
        $this->writeData($sheet, $data, 2);
        $this->applySheetFormatting($sheet, $headers);
    }

    private function createConsolidatedPaymentsSheet(Spreadsheet $spreadsheet, array $filters): void
    {
        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle('Consolidado de Pagos');

        // Obtener datos
        $data = $this->consolidatedPaymentsService->getAllConsolidatedPayments($filters, []);

        if ($data->isEmpty()) {
            $this->addEmptySheetMessage($sheet, 'No hay datos de consolidado de pagos');
            return;
        }

        // Headers
        $firstItem = $data->first();
        if (!$firstItem || !is_array($firstItem)) {
            $this->addEmptySheetMessage($sheet, 'Formato de datos inválido para consolidado de pagos');
            return;
        }

        $headers = array_keys($firstItem);
        $this->writeHeaders($sheet, $headers);
        $this->writeData($sheet, $data, 2);
        $this->applySheetFormatting($sheet, $headers);
    }

    private function createPaymentScheduleSheet(Spreadsheet $spreadsheet, array $filters): void
    {
        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle('Cronograma de Pagos');

        // Obtener datos
        $data = $this->recoveryScheduleService->getAllPaymentSchedules($filters, []);

        if ($data->isEmpty()) {
            $this->addEmptySheetMessage($sheet, 'No hay datos de cronograma de pagos');
            return;
        }

        // Headers
        $firstItem = $data->first();
        if (!$firstItem || !is_array($firstItem)) {
            $this->addEmptySheetMessage($sheet, 'Formato de datos inválido para cronograma de pagos');
            return;
        }

        $headers = array_keys($firstItem);
        $this->writeHeaders($sheet, $headers);
        $this->writeData($sheet, $data, 2);
        $this->applySheetFormatting($sheet, $headers);
    }

    private function createPartialAccountSheet(Spreadsheet $spreadsheet, array $filters): void
    {
        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle('Estado de Cuenta');

        // Definir campos para exportación consolidada (todos los campos disponibles)
        $selectedFields = [
            'participant' => ['name', 'email', 'document', 'phone'],
            'program' => ['name', 'departureDate', 'enrollmentCode', 'salesExecutive'],
            'financial' => ['totalAmount', 'discounts', 'netAmount', 'totalPaid', 'pendingAmount', 'progressPercentage']
        ];

        // Obtener datos
        $data = $this->partialAccountService->getExportData($filters, $selectedFields, 'all');

        if ($data->isEmpty()) {
            $this->addEmptySheetMessage($sheet, 'No hay datos de estado de cuenta');
            return;
        }

        // Headers
        $firstItem = $data->first();
        if (!$firstItem || !is_array($firstItem)) {
            $this->addEmptySheetMessage($sheet, 'Formato de datos inválido para estado de cuenta');
            return;
        }

        $headers = array_keys($firstItem);
        $this->writeHeaders($sheet, $headers);
        $this->writeData($sheet, $data, 2);
        $this->applySheetFormatting($sheet, $headers);
    }

    private function createSummarySheet(Spreadsheet $spreadsheet, array $filters): void
    {
        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle('Resumen Ejecutivo');

        // Obtener resumen
        $summaryService = new ReportsSummaryService();
        $summary = $summaryService->getSummary($filters);

        // Crear tabla de resumen
        $row = 1;
        
        // Título
        $sheet->setCellValue('A1', 'RESUMEN EJECUTIVO DE REPORTES');
        $sheet->mergeCells('A1:D1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $row = 3;

        // Pagos Diarios
        $sheet->setCellValue("A{$row}", 'PAGOS DIARIOS');
        $sheet->getStyle("A{$row}")->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle("A{$row}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('FFF3CD');
        $row++;

        $sheet->setCellValue("A{$row}", 'Total Pagos:');
        $sheet->setCellValue("B{$row}", $summary['dailyPayments']['totalPayments'] ?? 0);
        $row++;

        $sheet->setCellValue("A{$row}", 'Monto Total:');
        $sheet->setCellValue("B{$row}", '$' . number_format($summary['dailyPayments']['totalAmount'] ?? 0, 0, ',', '.'));
        $row++;

        $sheet->setCellValue("A{$row}", 'Pagos Hoy:');
        $sheet->setCellValue("B{$row}", $summary['dailyPayments']['paymentsToday'] ?? 0);
        $row += 2;

        // Consolidado de Pagos
        $sheet->setCellValue("A{$row}", 'CONSOLIDADO DE PAGOS');
        $sheet->getStyle("A{$row}")->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle("A{$row}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('E2D9F3');
        $row++;

        $sheet->setCellValue("A{$row}", 'Total Programas:');
        $sheet->setCellValue("B{$row}", $summary['consolidatedPayments']['totalPrograms'] ?? 0);
        $row++;

        $sheet->setCellValue("A{$row}", 'Total Transacciones:');
        $sheet->setCellValue("B{$row}", $summary['consolidatedPayments']['totalTransactions'] ?? 0);
        $row++;

        $sheet->setCellValue("A{$row}", 'Monto Total:');
        $sheet->setCellValue("B{$row}", '$' . number_format($summary['consolidatedPayments']['totalAmount'] ?? 0, 0, ',', '.'));
        $row += 2;

        // Cronograma de Recuperación
        $sheet->setCellValue("A{$row}", 'CRONOGRAMA DE RECUPERACIÓN');
        $sheet->getStyle("A{$row}")->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle("A{$row}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('D1E7DD');
        $row++;

        $sheet->setCellValue("A{$row}", 'Total Programados:');
        $sheet->setCellValue("B{$row}", $summary['paymentSchedule']['totalScheduled'] ?? 0);
        $row++;

        $sheet->setCellValue("A{$row}", 'Total Pagados:');
        $sheet->setCellValue("B{$row}", $summary['paymentSchedule']['totalPaid'] ?? 0);
        $row++;

        $sheet->setCellValue("A{$row}", 'Tasa Completitud:');
        $sheet->setCellValue("B{$row}", ($summary['paymentSchedule']['completionRate'] ?? 0) . '%');
        $row += 2;

        // Estado de Cuenta Parcial
        $sheet->setCellValue("A{$row}", 'ESTADO DE CUENTA PARCIAL');
        $sheet->getStyle("A{$row}")->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle("A{$row}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('CCE5FF');
        $row++;

        $sheet->setCellValue("A{$row}", 'Total Participantes:');
        $sheet->setCellValue("B{$row}", $summary['partialAccount']['totalParticipants'] ?? 0);
        $row++;

        $sheet->setCellValue("A{$row}", 'Participantes Activos:');
        $sheet->setCellValue("B{$row}", $summary['partialAccount']['activeParticipants'] ?? 0);
        $row++;

        $sheet->setCellValue("A{$row}", 'Balance Total:');
        $sheet->setCellValue("B{$row}", '$' . number_format($summary['partialAccount']['totalBalance'] ?? 0, 0, ',', '.'));

        // Auto-dimensionar columnas
        $sheet->getColumnDimension('A')->setAutoSize(true);
        $sheet->getColumnDimension('B')->setAutoSize(true);
    }

    private function writeHeaders($sheet, array $headers): void
    {
        if (empty($headers)) {
            return;
        }

        $colIndex = 0;
        foreach ($headers as $header) {
            $col = chr(65 + $colIndex);
            $sheet->setCellValue($col . '1', $header);
            $colIndex++;
        }
    }

    private function writeData($sheet, Collection $data, int $startRow): void
    {
        if ($data->isEmpty()) {
            return;
        }

        $rowIndex = $startRow;
        foreach ($data as $rowData) {
            if (!is_array($rowData) && !is_object($rowData)) {
                continue;
            }

            $colIndex = 0;
            foreach ($rowData as $value) {
                $col = chr(65 + $colIndex);
                
                // Limpiar y validar valores
                if (is_null($value)) {
                    $value = '';
                } elseif (is_numeric($value)) {
                    $value = (float) $value;
                } else {
                    $value = (string) $value;
                }
                
                // Prevenir notación científica para códigos largos
                if (is_numeric($value) && strlen((string)$value) > 8) {
                    $sheet->getStyle($col . $rowIndex)->getNumberFormat()->setFormatCode('@');
                    $value = (string) $value;
                }
                
                $sheet->setCellValue($col . $rowIndex, $value);
                $colIndex++;
            }
            $rowIndex++;
        }
    }

    private function applySheetFormatting($sheet, array $headers): void
    {
        if (empty($headers)) {
            return;
        }

        // Formato para headers
        $headerStyle = [
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '4472C4'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => '000000'],
                ],
            ],
        ];

        $lastCol = chr(65 + count($headers) - 1);
        $sheet->getStyle('A1:' . $lastCol . '1')->applyFromArray($headerStyle);

        // Auto-dimensionar columnas
        foreach (range('A', $lastCol) as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // Bordes para toda la tabla
        $lastRow = $sheet->getHighestRow();
        
        if ($lastRow > 1) {
            $borderStyle = [
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['rgb' => '000000'],
                    ],
                ],
            ];

            $sheet->getStyle("A1:{$lastCol}{$lastRow}")->applyFromArray($borderStyle);
        }
    }

    private function addEmptySheetMessage($sheet, string $message): void
    {
        $sheet->setCellValue('A1', $message);
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->mergeCells('A1:D1');
    }
}
