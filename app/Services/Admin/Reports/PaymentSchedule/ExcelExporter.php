<?php

namespace App\Services\Admin\Reports\PaymentSchedule;

use Illuminate\Support\Collection;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExcelExporter
{
    public function export(Collection $summary, Collection $details, string $filename): StreamedResponse
    {
        // Limpiar buffers
        while (ob_get_level()) {
            ob_end_clean();
        }

        $spreadsheet = new Spreadsheet();

        // Hoja 1: Resumen
        $summarySheet = $spreadsheet->getActiveSheet();
        $summarySheet->setTitle('Resumen');
        $this->buildSummarySheet($summarySheet, $summary);

        // Hoja 2: Detalle
        $detailSheet = $spreadsheet->createSheet();
        $detailSheet->setTitle('Detalle');
        $this->buildDetailSheet($detailSheet, $details);

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

        return $response;
    }

    private function buildSummarySheet($sheet, Collection $summary): void
    {
        // Comienza en la fila 1. Cada bloque de programa ocupa:
        // 1 fila de encabezado (Ejecutivo/Programa) + 2 filas de encabezado de meses + 3 filas de datos = 6 filas
        $rowIndex = 1;

        foreach ($summary as $group) {
            // Normalizar y ordenar meses POR BLOQUE (como en el frontend)
            $months = collect($group['months'] ?? [])->map(function ($m) {
                $keyRaw = trim((string)($m['year_month'] ?? ''));
                $yearParsed = null;
                $monthParsed = null;
                if ($keyRaw && preg_match('/^(\d{4})-(\d{1,2})$/', $keyRaw, $mm)) {
                    $yearParsed = (int) $mm[1];
                    $monthParsed = (int) $mm[2];
                }
                return [
                    'key' => $keyRaw,
                    'label' => (string)($m['month_name'] ?? $keyRaw),
                    'year' => $yearParsed ?? (int)($m['year'] ?? 0),
                    'month' => $monthParsed ?? (int)($m['month'] ?? 0),
                ];
            })->values();
            // Construir una línea de tiempo continua desde el primer al último mes y usarla para headers
            if ($months->isNotEmpty()) {
                $monthsArr = $months->all();
                usort($monthsArr, function ($a, $b) {
                    $va = ($a['year'] * 100) + $a['month'];
                    $vb = ($b['year'] * 100) + $b['month'];
                    return $va <=> $vb;
                });

                $startYear = $monthsArr[0]['year'];
                $startMonth = $monthsArr[0]['month'];
                $endYear = $monthsArr[count($monthsArr) - 1]['year'];
                $endMonth = $monthsArr[count($monthsArr) - 1]['month'];

                $cursor = \Carbon\Carbon::createFromDate($startYear, $startMonth, 1)->startOfMonth();
                $end = \Carbon\Carbon::createFromDate($endYear, $endMonth, 1)->startOfMonth();

                $timeline = [];
                while ($cursor->lte($end)) {
                    $timeline[] = [
                        'key' => $cursor->format('Y-m'),
                        'label' => mb_strtolower($cursor->locale('es')->translatedFormat('F Y')),
                        'year' => (int)$cursor->format('Y'),
                        'month' => (int)$cursor->format('n'),
                    ];
                    $cursor->addMonth();
                }
                $months = collect($timeline);
            }

            $blockTotalCols = max(1, $months->count() * 2);
            $blockLastColForHeader = $this->col($blockTotalCols);

            // Encabezado del bloque: "Ejecutivo: X, Programa: CODE - NAME" (merge across all month columns del bloque)
            $headerText = 'Ejecutivo: ' . ($group['sales_executive_name'] ?? 'N/A') . '    Programa: ' . trim(($group['program_code'] ?? '') . ' - ' . ($group['program_name'] ?? ''));
            $sheet->mergeCells('A' . $rowIndex . ':' . $blockLastColForHeader . $rowIndex);
            $sheet->setCellValue('A' . $rowIndex, $headerText);
            $sheet->getStyle('A' . $rowIndex . ':' . $blockLastColForHeader . $rowIndex)->applyFromArray([
                'font' => ['bold' => true],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT, 'vertical' => Alignment::VERTICAL_CENTER],
            ]);

            // Fila de encabezados de meses (rowIndex+1) y sub-encabezados N° / $ (rowIndex+2)
            $colIndex = 1; // desde la columna A
            foreach ($months as $m) {
                $startCol = $this->col($colIndex);
                $endCol = $this->col($colIndex + 1);
                $sheet->mergeCells("{$startCol}" . ($rowIndex + 1) . ":{$endCol}" . ($rowIndex + 1));
                $sheet->setCellValue("{$startCol}" . ($rowIndex + 1), mb_strtoupper($m['label']));
                $sheet->setCellValue("{$startCol}" . ($rowIndex + 2), 'N°');
                $sheet->setCellValue("{$endCol}" . ($rowIndex + 2), '$');
                // Estilos por par de columnas del mes
                $sheet->getStyle("{$startCol}" . ($rowIndex + 1) . ":{$endCol}" . ($rowIndex + 2))->applyFromArray([
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                            'color' => ['rgb' => '000000']
                        ]
                    ],
                    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1C4F4A']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                ]);
                $colIndex += 2;
            }

            // Última columna real del bloque tras renderizar encabezados
            $blockLastCol = $this->col(max(1, $colIndex - 1));

            // Estilo de encabezados de meses
            $sheet->getStyle('A' . ($rowIndex + 1) . ':' . $blockLastCol . ($rowIndex + 2))->applyFromArray([
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1C4F4A']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            ]);
            $sheet->getRowDimension($rowIndex + 2)->setRowHeight(18);

            // Datos: 3 filas (No Pagadas, Pagadas TC, Pagadas PAT)
            $groupMonths = collect($group['months'] ?? [])->keyBy('year_month');
            $firstDataRow = $rowIndex + 3;
            $colIndex = 1;
            foreach ($months as $m) {
                $monthData = $groupMonths->get($m['key']);
                $noPagadasN = (int) ($monthData['cuotas_no_pagadas_count'] ?? 0);
                $noPagadasAmount = (int) round($monthData['cuotas_no_pagadas_amount'] ?? 0);
                $tcN = (int) ($monthData['cuotas_pagadas_tc_count'] ?? 0);
                $tcAmount = (int) round($monthData['cuotas_pagadas_tc_amount'] ?? 0);
                $patN = (int) ($monthData['cuotas_pagadas_pat_count'] ?? 0);
                $patAmount = (int) round($monthData['cuotas_pagadas_pat_amount'] ?? 0);

                // Fila 1 (No Pagadas) -> valores numéricos
                $sheet->setCellValue($this->col($colIndex) . $firstDataRow, $noPagadasN);
                $sheet->setCellValue($this->col($colIndex + 1) . $firstDataRow, $noPagadasAmount);
                // Fila 2 (Pagadas TC)
                $sheet->setCellValue($this->col($colIndex) . ($firstDataRow + 1), $tcN);
                $sheet->setCellValue($this->col($colIndex + 1) . ($firstDataRow + 1), $tcAmount);
                // Fila 3 (Pagadas PAT)
                $sheet->setCellValue($this->col($colIndex) . ($firstDataRow + 2), $patN);
                $sheet->setCellValue($this->col($colIndex + 1) . ($firstDataRow + 2), $patAmount);

                // Formatos numéricos
                // Columnas de montos ($) son las pares del par (colIndex+1)
                $sheet->getStyle($this->col($colIndex + 1) . $firstDataRow)->getNumberFormat()->setFormatCode('#,##0');
                $sheet->getStyle($this->col($colIndex + 1) . ($firstDataRow + 1))->getNumberFormat()->setFormatCode('#,##0');
                $sheet->getStyle($this->col($colIndex + 1) . ($firstDataRow + 2))->getNumberFormat()->setFormatCode('#,##0');

                // Alinear cantidades al centro y montos a la derecha
                $sheet->getStyle($this->col($colIndex) . $firstDataRow . ':' . $this->col($colIndex) . ($firstDataRow + 2))
                      ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle($this->col($colIndex + 1) . $firstDataRow . ':' . $this->col($colIndex + 1) . ($firstDataRow + 2))
                      ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

                $colIndex += 2;
            }

            // Anchos fijos: pares ($) más anchos
            for ($i = 1; $i <= ($colIndex - 1); $i++) {
                $isAmountCol = ($i % 2) === 0;
                $sheet->getColumnDimension($this->col($i))->setWidth($isAmountCol ? 16 : 10);
            }

            // Bordes del bloque (desde fila de encabezados de meses hasta la última fila de datos)
            $sheet->getStyle('A' . ($rowIndex + 1) . ':' . $blockLastCol . ($firstDataRow + 2))->applyFromArray([
                'borders' => ['allBorders' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN, 'color' => ['rgb' => '000000']]],
            ]);

            // Avanzar a la siguiente sección (dejar una fila en blanco entre bloques)
            $rowIndex = $firstDataRow + 3 + 1;
        }
    }

    private function buildDetailSheet($sheet, Collection $details): void
    {
        // Ordenar por próxima fecha de vencimiento (null al final)
        $ordered = $details->sortBy(function ($row) {
            $key = $row->next_due_date ?? null;
            return $key ? (new \DateTime($key))->format('Y-m-d') : '9999-12-31';
        })->values();

        $headers = [
            'Ejecutivo Comercial', 'Código Programa', 'Nombre Programa', 'Nombre del Alumno', 'Tipo de Dcto', 'N° Documento',
            'Fecha de Inicio de Programa', 'Valor Total Prog.', 'Abonos + becas', 'Valor alumno liberado', 'Monto Total por Cobrar', 'Estado de cuotas', 'Próxima Venc.', 'Cuota #', 'Monto Cuota'
        ];

        // Headers
        foreach ($headers as $idx => $h) {
            $sheet->setCellValue($this->col($idx + 1) . '1', $h);
        }
        $lastCol = $this->col(count($headers));
        $sheet->getStyle('A1:' . $lastCol . '1')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1c4f4a']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);

        // Rows
        $rowIndex = 2;
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
            $sheet->getColumnDimension($this->col($i))->setWidth($widths[$i-1] ?? 18);
        }

        $sheet->getStyle('A1:' . $lastCol . ($rowIndex - 1))->applyFromArray([
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
        try { return (new \DateTime($date))->format('d-m-Y'); } catch (\Exception) { return (string) $date; }
    }
}


