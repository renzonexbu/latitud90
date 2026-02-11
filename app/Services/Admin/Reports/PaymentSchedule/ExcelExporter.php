<?php

namespace App\Services\Admin\Reports\PaymentSchedule;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use Symfony\Component\HttpFoundation\StreamedResponse;
use App\Traits\ExcelReportHeader;

class ExcelExporter
{
    use ExcelReportHeader;
    public function export(Collection $summary, Collection $details, string $filename): StreamedResponse
    {
        // Limpiar buffers
        while (ob_get_level()) {
            ob_end_clean();
        }

        Log::info('PaymentSchedule Excel export: start', [
            'summary_groups' => $summary->count(),
            'detail_rows' => $details->count(),
            'filename' => $filename,
            'memory_usage_mb' => round(memory_get_usage(true) / 1048576, 2),
        ]);

        $spreadsheet = new Spreadsheet();

        // Hoja 1: Resumen
        $summarySheet = $spreadsheet->getActiveSheet();
        $summarySheet->setTitle('Resumen');
        $this->buildSummarySheet($summarySheet, $summary);
        Log::info('PaymentSchedule Excel export: summary sheet built', [
            'highest_row' => $summarySheet->getHighestRow(),
            'highest_col' => $summarySheet->getHighestColumn(),
        ]);

        // Hoja 2: Detalle
        $detailSheet = $spreadsheet->createSheet();
        $detailSheet->setTitle('Detalle');
        $this->buildDetailSheet($detailSheet, $details);
        Log::info('PaymentSchedule Excel export: detail sheet built', [
            'highest_row' => $detailSheet->getHighestRow(),
            'highest_col' => $detailSheet->getHighestColumn(),
        ]);

        $writer = new Xlsx($spreadsheet);
        $writer->setPreCalculateFormulas(false);
        $writer->setIncludeCharts(false);

        $response = new StreamedResponse(function () use ($writer) {
            $writer->save('php://output');
        });

        $response->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $response->headers->set('Content-Disposition', 'attachment; filename="' . $filename . '.xlsx"');
        $response->headers->set('Cache-Control', 'no-cache, must-revalidate');
        $response->headers->set('Expires', '0');
        $response->headers->set('Pragma', 'public');

        Log::info('PaymentSchedule Excel export: response ready', [
            'memory_usage_mb' => round(memory_get_usage(true) / 1048576, 2),
            'peak_memory_usage_mb' => round(memory_get_peak_usage(true) / 1048576, 2),
        ]);

        return $response;
    }

    private function buildSummarySheet($sheet, Collection $summary): void
    {
        // Header con logo, título y fecha de exportación
        $rowIndex = $this->addReportHeader($sheet, 'Cronograma de Pagos - Resumen');

        foreach ($summary as $group) {
            try {
                // Agrupar meses correctamente ordenados
                $months = collect($group['months'] ?? [])->sortBy(fn($m) => ($m['year'] * 100) + $m['month'])->values();
    
                // Encabezado Ejecutivo/Programa
                $sheet->setCellValue("A{$rowIndex}", "Ejecutivo: " . ($group['sales_executive_name'] ?? 'N/A'));
                $sheet->setCellValue("B{$rowIndex}", "Programa: " . ($group['program_code'] ?? '') . " - " . ($group['program_name'] ?? ''));
                $rowIndex += 2;
    
                foreach ($months as $m) {
                    // Header del mes
                    $sheet->setCellValue("A{$rowIndex}", mb_strtoupper($m['month_name'] . " " . $m['year']));
                    $sheet->getStyle("A{$rowIndex}")->getFont()->setBold(true);
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
    
            } catch (\Throwable $e) {
                throw $e;
            }
        }
    }

    private function buildDetailSheet($sheet, Collection $details): void
    {
        // Header con logo, título y fecha de exportación
        $startRow = $this->addReportHeader($sheet, 'Cronograma de Pagos - Detalle');

        // Ordenar por próxima fecha de vencimiento (null al final)
        $ordered = $details->sortBy(function ($row) {
            $key = $row->next_due_date ?? null;
            return $key ? (new \DateTime($key))->format('Y-m-d') : '9999-12-31';
        })->values();

        $headers = [
            'Ejecutivo Comercial',
            'Código Programa',
            'Nombre Programa',
            'Nombre del Alumno',
            'Tipo de Dcto',
            'N° Documento',
            'Fecha de Inicio de Programa',
            'Valor Total Prog.',
            'Abonos + becas',
            'Valor alumno liberado',
            'Monto Total por Cobrar',
            'Estado de cuotas',
            'Próxima Venc.',
            'Cuota #',
            'Monto Cuota'
        ];

        // Headers
        foreach ($headers as $idx => $h) {
            $sheet->setCellValue($this->col($idx + 1) . $startRow, $h);
        }
        $lastCol = $this->col(count($headers));
        $sheet->getStyle("A{$startRow}:{$lastCol}{$startRow}")->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1c4f4a']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);

        // Rows
        $rowIndex = $startRow + 1;
        foreach ($ordered as $row) {
            $values = [
                $row->sales_executive_name ?? 'N/A',
                $row->program_code ?? '',
                $row->program_name ?? '',
                $row->participant_name ?? '',
                $row->document_type_code ?? '',
                $row->participant_document ?? '',
                $this->fmtDate($row->program_departure_date ?? null),
                (int) round($row->program_price ?? 0),
                (int) round(($row->abonos_becas_amount ?? 0)),
                (int) round($row->released_amount ?? 0),
                (int) round($row->total_pending_amount ?? 0),
                $row->paid_installments_display ?? '0/0',
                $this->fmtDate($row->next_due_date ?? null),
                $row->installment_number ?? '',
                (int) round($row->installment_amount ?? 0),
            ];
            foreach ($values as $i => $val) {
                $sheet->setCellValue($this->col($i + 1) . $rowIndex, $val);
            }
            $rowIndex++;
        }

        // Anchos fijos para evitar autoSize
        $widths = [24, 16, 38, 28, 14, 18, 18, 16, 18, 18, 20, 14, 10, 16];
        foreach (range(1, count($headers)) as $i) {
            $sheet->getColumnDimension($this->col($i))->setWidth($widths[$i - 1] ?? 18);
        }

        $sheet->getStyle("A{$startRow}:{$lastCol}" . ($rowIndex - 1))->applyFromArray([
            'borders' => ['allBorders' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN, 'color' => ['rgb' => '000000']]],
        ]);
    }

    private function col(int $index): string
    {
        // 1-based index to Excel column name
        $dividend = $index;
        $columnName = '';
        while ($dividend > 0) {
            $modulo = ($dividend - 1) % 26;
            $columnName = chr(65 + $modulo) . $columnName;
            $dividend = intdiv($dividend - $modulo, 26) - 1;
        }
        return $columnName;
    }

    private function fmtDate($date): string
    {
        if (!$date) return '';
        try {
            return (new \DateTime($date))->format('d-m-Y');
        } catch (\Exception) {
            return (string) $date;
        }
    }
}
