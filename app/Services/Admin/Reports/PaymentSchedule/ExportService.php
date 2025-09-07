<?php

namespace App\Services\Admin\Reports\PaymentSchedule;

use Illuminate\Support\Collection;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportService
{
    public function export(array $data, string $filename, string $format = 'xlsx'): StreamedResponse
    {
        try {
            // Limpiar cualquier output buffer
            while (ob_get_level()) {
                ob_end_clean();
            }
            
            $spreadsheet = new Spreadsheet();
            
            // Crear hoja de resumen general
            $this->createSummarySheet($spreadsheet, $data);
            
            // Crear hoja de detalles
            $this->createDetailsSheet($spreadsheet, $data);
            
            // Configurar writer XLSX
            $writer = new Xlsx($spreadsheet);
            $writer->setPreCalculateFormulas(false);
            $writer->setIncludeCharts(false);
            
            // Crear respuesta streaming
            $response = new StreamedResponse(function () use ($writer, $filename) {
                $writer->save('php://output');
            });
            
            // Headers para Excel
            $response->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            $response->headers->set('Content-Disposition', 'attachment; filename="' . $filename . '.xlsx"');
            $response->headers->set('Cache-Control', 'no-cache, must-revalidate');
            $response->headers->set('Expires', '0');
            $response->headers->set('Pragma', 'public');
            
            return $response;
            
        } catch (\Exception $e) {
            throw new \RuntimeException('Error al exportar cronograma de pagos: ' . $e->getMessage());
        }
    }
    
    private function createSummarySheet(Spreadsheet $spreadsheet, array $data): void
    {
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Resumen General');
        
        // Convertir Collection a array si es necesario
        $executiveSummary = $data['executiveSummary'] ?? [];
        if ($executiveSummary instanceof \Illuminate\Support\Collection) {
            $executiveSummary = $executiveSummary->toArray();
        }
        
        $this->buildSummarySheet($sheet, $executiveSummary);
    }
    
    private function buildSummarySheet($sheet, array $summary): void
    {
        $rowIndex = 1;

        foreach ($summary as $group) {
            try {
                // Agrupar meses correctamente ordenados
                $months = collect($group['months'] ?? [])->sortBy(fn($m) => ($m['year'] * 100) + $m['month'])->values();

                // Encabezado Ejecutivo/Programa
                $sheet->setCellValue("A{$rowIndex}", "Ejecutivo: " . ($group['sales_executive_name'] ?? 'N/A'));
                $sheet->setCellValue("B{$rowIndex}", "Programa: " . ($group['program_code'] ?? '') . " - " . ($group['program_name'] ?? ''));
                $sheet->getStyle("A{$rowIndex}:B{$rowIndex}")->getFont()->setBold(true);
                $sheet->getStyle("A{$rowIndex}:B{$rowIndex}")->getFill()
                    ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                    ->getStartColor()->setRGB('1c4f4a');
                $sheet->getStyle("A{$rowIndex}:B{$rowIndex}")->getFont()->getColor()->setRGB('FFFFFF');
                $rowIndex += 2;

                foreach ($months as $m) {
                    // Header del mes
                    $sheet->setCellValue("A{$rowIndex}", mb_strtoupper($m['month_name'] . " " . $m['year']));
                    $sheet->getStyle("A{$rowIndex}")->getFont()->setBold(true);
                    $sheet->getStyle("A{$rowIndex}")->getFill()
                        ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                        ->getStartColor()->setRGB('4472C4');
                    $sheet->getStyle("A{$rowIndex}")->getFont()->getColor()->setRGB('FFFFFF');
                    $rowIndex++;

                    // Datos del mes
                    $rows = [
                        ['Cuotas No Pagadas N°', (int)($m['cuotas_no_pagadas_count'] ?? 0)],
                        ['Cuotas No Pagadas $', (int)round($m['cuotas_no_pagadas_amount'] ?? 0)],
                        ['Cuotas Pagadas TC N°', (int)($m['cuotas_pagadas_tc_count'] ?? 0)],
                        ['Cuotas Pagadas TC $', (int)round($m['cuotas_pagadas_tc_amount'] ?? 0)],
                        ['Cuotas Pagadas PAT N°', (int)($m['cuotas_pagadas_pat_count'] ?? 0)],
                        ['Cuotas Pagadas PAT $', (int)round($m['cuotas_pagadas_pat_amount'] ?? 0)],
                    ];

                    foreach ($rows as $r) {
                        $sheet->setCellValue("A{$rowIndex}", $r[0]);
                        $sheet->setCellValue("B{$rowIndex}", $r[1]);
                        $rowIndex++;
                    }

                    // Fila vacía de separación
                    $rowIndex++;
                }

                // Fila vacía de separación entre grupos
                $rowIndex++;

            } catch (\Throwable $e) {
                throw $e;
            }
        }
        
        // Auto-ajustar columnas
        $sheet->getColumnDimension('A')->setAutoSize(true);
        $sheet->getColumnDimension('B')->setAutoSize(true);
    }
    
    private function createDetailsSheet(Spreadsheet $spreadsheet, array $data): void
    {
        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle('Detalles de Cuotas');
        
        // Headers de los detalles
        $headers = [
            'Ejecutivo Comercial',
            'Programa',
            'Participante',
            'Documento',
            'N° Cuota',
            'Fecha Vencimiento',
            'Monto Cuota',
            'Estado',
            'Método de Pago',
            'Fecha Pago',
            'Monto Pagado'
        ];
        
        // Escribir headers
        $colIndex = 0;
        foreach ($headers as $header) {
            $col = chr(65 + $colIndex);
            $sheet->setCellValue($col . '1', $header);
            $colIndex++;
        }
        
        // Escribir datos de los detalles
        $rowIndex = 2;
        $paymentSchedules = $data['paymentSchedules'] ?? collect([]);
        
        // Convertir Collection a array si es necesario
        if ($paymentSchedules instanceof \Illuminate\Support\Collection) {
            $paymentSchedules = $paymentSchedules->toArray();
        }
        
        if (is_array($paymentSchedules)) {
            foreach ($paymentSchedules as $schedule) {
                $scheduleArray = (array) $schedule;
                $sheet->setCellValue('A' . $rowIndex, $scheduleArray['sales_executive_name'] ?? 'N/A');
                $sheet->setCellValue('B' . $rowIndex, $scheduleArray['program_name'] ?? 'N/A');
                $sheet->setCellValue('C' . $rowIndex, $scheduleArray['participant_name'] ?? 'N/A');
                $sheet->setCellValue('D' . $rowIndex, $scheduleArray['participant_document'] ?? 'N/A');
                $sheet->setCellValue('E' . $rowIndex, $scheduleArray['installment_number'] ?? 'N/A');
                $sheet->setCellValue('F' . $rowIndex, $scheduleArray['due_date'] ?? 'N/A');
                $sheet->setCellValue('G' . $rowIndex, $scheduleArray['installment_amount'] ?? 0);
                $sheet->setCellValue('H' . $rowIndex, $scheduleArray['installment_status'] ?? 'N/A');
                $sheet->setCellValue('I' . $rowIndex, $scheduleArray['gateway_code'] ?? 'N/A');
                $sheet->setCellValue('J' . $rowIndex, $scheduleArray['transaction_date'] ?? 'N/A');
                $sheet->setCellValue('K' . $rowIndex, $scheduleArray['payment_amount'] ?? 0);
                $rowIndex++;
            }
        }
        
        // Aplicar formato
        $this->applyDetailsFormatting($sheet, $headers);
    }
    
    
    private function applyDetailsFormatting($sheet, array $headers): void
    {
        // Formato para headers
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
        
        $sheet->getStyle('A1:' . chr(65 + count($headers) - 1) . '1')->applyFromArray($headerStyle);
        
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
        
        $lastRow = $sheet->getHighestRow();
        $sheet->getStyle('A1:' . chr(65 + count($headers) - 1) . $lastRow)->applyFromArray($borderStyle);
    }
}