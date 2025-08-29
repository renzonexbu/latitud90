<?php

namespace App\Services\Admin\Reports\Executives;

use Symfony\Component\HttpFoundation\StreamedResponse;
use Carbon\Carbon;
use Illuminate\Pagination\LengthAwarePaginator;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Font;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class ExportService
{
    public function exportConsolidated(array $filters, string $format = 'xlsx')
    {
        try {
            // Obtener datos usando el servicio SIN PAGINACIÓN
            $consolidatedService = app(\App\Services\Admin\Reports\Executives\ExecutivesConsolidatedService::class);
            $data = $consolidatedService->getConsolidatedForExport($filters);
            \Illuminate\Support\Facades\Log::info('Datos recibidos del servicio de exportación', [
                'data_keys' => array_keys($data),
                'has_items' => isset($data['items']),
                'items_count' => isset($data['items']) ? count($data['items']) : 0,
                'filters' => $filters
            ]);
            // Asegurar que no haya interrupciones del flujo
            $filename = 'consolidado_de_pagos_' . Carbon::now('America/Santiago')->format('Y-m-d_H-i-s') . '.xlsx';
            
            // Crear nuevo spreadsheet
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            
            // Línea 1: Título
            $sheet->setCellValue('A1', 'Consolidado de Pagos');
            $sheet->mergeCells('A1:N1');
            $this->styleTitle($sheet, 'A1');
            
            // Línea 2: Rango de fechas
            $dateFrom = $filters['dateFrom'] ?? '';
            $dateTo = $filters['dateTo'] ?? '';
            $dateRange = '';
            if ($dateFrom && $dateTo) {
                $dateRange = 'Período: ' . Carbon::parse($dateFrom)->format('d/m/Y') . ' - ' . Carbon::parse($dateTo)->format('d/m/Y');
            }
            $sheet->setCellValue('A2', $dateRange);
            $sheet->mergeCells('A2:N2');
            $this->styleSubtitle($sheet, 'A2');
            
            // Línea 3: Vacía (espacio)
            $sheet->setCellValue('A3', '');
            
            // Línea 4: Cabecera de la tabla
            $headers = [
                'A4' => 'Nro. Programa',
                'B4' => 'N° de Identificación',
                'C4' => 'Nombres y Apellidos',
                'D4' => 'Estado',
                'E4' => 'Pago y/o Dev.',
                'F4' => 'Nro. Documento',
                'G4' => 'Tipo de Documento',
                'H4' => 'Forma Pago',
                'I4' => 'Fecha de Pago',
                'J4' => 'Contacto Pagador',
                'K4' => 'Email Contacto Pagador',
                'L4' => 'Aporte o Beca',
                'M4' => 'Liberado',
                'N4' => 'Precio'
            ];
            
            foreach ($headers as $cell => $header) {
                $sheet->setCellValue($cell, $header);
            }
            
            // Aplicar estilos a la cabecera
            $this->styleHeader($sheet, 'A4:N4');
            
            // Obtener los items del servicio asegurando estructura de arreglo
            $resolvedItems = [];
            if (isset($data['items'])) {
                if (is_array($data['items'])) {
                    $resolvedItems = $data['items'];
                } elseif ($data['items'] instanceof \Illuminate\Support\Collection) {
                    $resolvedItems = $data['items']->toArray();
                }
            }

            \Illuminate\Support\Facades\Log::info('Datos para exportación Excel', [
                'items_count' => count($resolvedItems),
                'filters' => $filters
            ]);

            // Datos dinámicos desde línea 5
            $row = 5;
            foreach ($resolvedItems as $item) {
                    $sheet->setCellValue('A' . $row, $item['program_number'] ?? 'N/A');
                    $sheet->setCellValue('B' . $row, isset($item['identification_number']) ? $this->formatRut($item['identification_number']) : 'N/A');
                    $sheet->setCellValue('C' . $row, $item['full_name'] ?? 'N/A');
                    $sheet->setCellValue('D' . $row, $item['status'] ?? 'N/A');
                    $sheet->setCellValue('E' . $row, $item['payment_or_refund'] ?? 0);
                    $sheet->setCellValue('F' . $row, $item['document_number'] ?? 'N/A');
                    $sheet->setCellValue('G' . $row, $item['document_type'] ?? 'N/A');
                    $sheet->setCellValue('H' . $row, $item['payment_form'] ?? 'N/A');
                    $sheet->setCellValue('I' . $row, $item['payment_date'] ?? 'N/A');
                    $sheet->setCellValue('J' . $row, $item['payer_contact'] ?? 'N/A');
                    $sheet->setCellValue('K' . $row, $item['payer_email'] ?? 'N/A');
                    $sheet->setCellValue('L' . $row, $item['scholarship_or_grant'] ?? 0);
                    $sheet->setCellValue('M' . $row, $item['liberated'] ?? 0);
                    $sheet->setCellValue('N' . $row, $item['price'] ?? 0);
                    
                    // Aplicar formato de moneda a las columnas numéricas
                    $sheet->getStyle('E' . $row)->getNumberFormat()->setFormatCode('#,##0');
                    $sheet->getStyle('L' . $row)->getNumberFormat()->setFormatCode('#,##0');
                    $sheet->getStyle('M' . $row)->getNumberFormat()->setFormatCode('#,##0');
                    $sheet->getStyle('N' . $row)->getNumberFormat()->setFormatCode('#,##0');
                    
                    $row++;
            }
            
            // Ajustar ancho de columnas automáticamente
            foreach (range('A', 'N') as $column) {
                $sheet->getColumnDimension($column)->setAutoSize(true);
            }
            
            // Escribir a archivo temporal y descargar
            $writer = new Xlsx($spreadsheet);
            $tmpPath = tempnam(sys_get_temp_dir(), 'xlsx_');
            if ($tmpPath === false) {
                \Illuminate\Support\Facades\Log::error('No se pudo crear archivo temporal para Excel');
                return $this->exportConsolidatedCsv($filters);
            }

            // Asegurar buffers limpios antes de escribir
            while (ob_get_level() > 0) {
                ob_end_clean();
            }

            $writer->save($tmpPath);
            \Illuminate\Support\Facades\Log::info('Excel generado correctamente', [
                'path' => $tmpPath,
            ]);

            return response()->download(
                $tmpPath,
                $filename,
                [
                    'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                    'Cache-Control' => 'no-cache, must-revalidate',
                    'Pragma' => 'public',
                ]
            )->deleteFileAfterSend(true);
            
        } catch (\Exception $e) {
            // Log del error para debugging
            \Illuminate\Support\Facades\Log::error('Error en exportación Excel: ' . $e->getMessage());
            \Illuminate\Support\Facades\Log::error('Stack trace: ' . $e->getTraceAsString());
            
            // Fallback a CSV si hay error
            return $this->exportConsolidatedCsv($filters);
        }
    }

    public function exportPartialAccount(array $filters, string $format = 'xlsx')
    {
        $filename = 'executives_partial_account_' . Carbon::now('America/Santiago')->format('Y-m-d_H-i-s') . '.xlsx';
        return $this->streamCsv($filename, [
            ['Rut Apoderado', 'Nombre Apoderado', 'Participante', 'Programa', 'Saldo', 'Último Pago']
        ]);
    }

    private function exportConsolidatedCsv(array $filters)
    {
        // Obtener datos usando el servicio SIN PAGINACIÓN
        $consolidatedService = app(ExecutivesConsolidatedService::class);
        $data = $consolidatedService->getConsolidatedForExport($filters);
        
        $filename = 'consolidado_de_pagos_' . Carbon::now('America/Santiago')->format('Y-m-d_H-i-s') . '.csv';
        
        $rows = [
            ['Consolidado de Pagos'],
            ['Período: ' . ($filters['dateFrom'] ?? '') . ' - ' . ($filters['dateTo'] ?? '')],
            [''],
            [
                'Nro. Programa',
                'N° de Identificación', 
                'Nombres y Apellidos',
                'Estado',
                'Pago y/o Dev.',
                'Nro. Documento',
                'Tipo de Documento',
                'Forma Pago',
                'Fecha de Pago',
                'Contacto Pagador',
                'Email Contacto Pagador',
                'Aporte o Beca',
                'Liberado',
                'Precio'
            ]
        ];
        
        // Usar los items del método de exportación
        if (isset($data['items']) && is_array($data['items'])) {
            foreach ($data['items'] as $item) {
                $rows[] = [
                    $item['program_number'] ?? 'N/A',
                    $item['identification_number'] ?? 'N/A',
                    $item['full_name'] ?? 'N/A',
                    $item['status'] ?? 'N/A',
                    number_format($item['payment_or_refund'] ?? 0, 0, ',', '.'),
                    $item['document_number'] ?? 'N/A',
                    $item['document_type'] ?? 'N/A',
                    $item['payment_form'] ?? 'N/A',
                    $item['payment_date'] ?? 'N/A',
                    $item['payer_contact'] ?? 'N/A',
                    $item['payer_email'] ?? 'N/A',
                    number_format($item['scholarship_or_grant'] ?? 0, 0, ',', '.'),
                    number_format($item['liberated'] ?? 0, 0, ',', '.'),
                    number_format($item['price'] ?? 0, 0, ',', '.')
                ];
            }
        }
        
        return $this->streamCsv($filename, $rows);
    }

    private function styleTitle($sheet, $cell)
    {
        $sheet->getStyle($cell)->applyFromArray([
            'font' => [
                'bold' => true,
                'size' => 16,
                'color' => ['rgb' => '1C4F4A']
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_LEFT,
                'vertical' => Alignment::VERTICAL_CENTER
            ]
        ]);
    }

    private function styleSubtitle($sheet, $cell)
    {
        $sheet->getStyle($cell)->applyFromArray([
            'font' => [
                'bold' => false,
                'size' => 12,
                'color' => ['rgb' => '666666']
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_LEFT,
                'vertical' => Alignment::VERTICAL_CENTER
            ]
        ]);
    }

    private function styleHeader($sheet, $range)
    {
        $sheet->getStyle($range)->applyFromArray([
            'font' => [
                'bold' => true,
                'size' => 11,
                'color' => ['rgb' => 'FFFFFF']
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '1C4F4A']
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => 'FFFFFF']
                ]
            ]
        ]);
    }

    private function streamCsv(string $filename, array $rows)
    {
        $response = new StreamedResponse(function () use ($rows) {
            $handle = fopen('php://output', 'w');
            foreach ($rows as $row) {
                fputcsv($handle, $row, ';');
            }
            fclose($handle);
        });

        $response->headers->set('Content-Type', 'text/csv; charset=UTF-8');
        $response->headers->set('Content-Disposition', 'attachment; filename="' . $filename . '"');
        $response->headers->set('Cache-Control', 'no-cache, must-revalidate');
        $response->headers->set('Pragma', 'public');

        return $response;
    }

    private function formatRut($rut): string
    {
        if (!$rut) {
            return 'N/A';
        }
        $rutStr = (string)$rut;
        if (str_contains($rutStr, '.')) {
            return $rutStr;
        }
        $clean = str_replace(['.', '-'], '', $rutStr);
        if (preg_match('/^\d{7,8}[\dKk]$/', $clean) === 1) {
            $dv = strtoupper(substr($clean, -1));
            $num = substr($clean, 0, -1);
            $numFmt = number_format((int)$num, 0, '', '.');
            return $numFmt . '-' . $dv;
        }
        return $rutStr;
    }
}


