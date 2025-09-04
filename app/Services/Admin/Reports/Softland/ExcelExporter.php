<?php

namespace App\Services\Admin\Reports\Softland;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use Symfony\Component\HttpFoundation\StreamedResponse;
use App\Services\Admin\Reports\Softland\SoftlandDataService;

class ExcelExporter
{
    /**
     * Genera un archivo Excel o CSV con la estructura base requerida por Softland.
     */
    public function export(?string $filename = null, array $filters = [], string $format = 'excel'): StreamedResponse
    {
        // Asegurar zona horaria solicitada por el usuario
        $dateSuffix = now('America/Santiago')->format('Y-m-d_H-i-s');
        $filename = $filename ?: "softland_movimientos_mensuales_{$dateSuffix}";

        // Limpiar buffers de salida para evitar corrupción del archivo
        while (ob_get_level()) {
            ob_end_clean();
        }

        // Generar datos de movimientos contables
        $dataService = new SoftlandDataService();
        $movements = $dataService->generateMovements($filters);
        $headers = $this->getHeaders();

        if ($format === 'csv') {
            return $this->exportCsv($filename, $headers, $movements);
        } else {
            return $this->exportExcel($filename, $headers, $movements);
        }
    }

    /**
     * Columnas solicitadas (1..91) en Title Case.
     *
     * @return array<int, string>
     */
    private function getHeaders(): array
    {
        return [
            'Código Plan De Cuenta',
            'Debe',
            'Haber',
            'Descripción Movimiento',
            'Equivalencia Moneda',
            'Monto Al Debe Moneda Adicional',
            'Monto Al Haber Moneda Adicional',
            'Código Condición De Venta',
            'Código Vendedor',
            'Código Ubicación',
            'Código Concepto De Caja',
            'Código Instrumento Financiero',
            'Cantidad Instrumento Financiero',
            'Código Detalle De Gasto',
            'Cantidad Concepto De Gasto',
            'Código Centro De Costo',
            'Tipo Docto. Conciliación',
            'Nro. Docto. Conciliación',
            'Código Auxiliar',
            'Tipo Documento',
            'Nro. Documento',
            'Fecha Emisión Docto.(DD/MM/AAAA)',
            'Fecha Vencimiento Docto.(DD/MM/AAAA)',
            'Tipo Docto. Referencia',
            'Nro. Docto. Referencia',
            'Nro. Correlativo Interno',
            'Monto 1 Detalle Libro',
            'Monto 2 Detalle Libro',
            'Monto 3 Detalle Libro',
            'Monto 4 Detalle Libro',
            'Monto 5 Detalle Libro',
            'Monto 6 Detalle Libro',
            'Monto 7 Detalle Libro',
            'Monto 8 Detalle Libro',
            'Monto 9 Detalle Libro',
            'Monto Suma Detalle Libro',
            'Graba El Detalle De Libro (S/N)',
            'Documento Nulo (S/N)',
            'Código Flujo Efectivo 1',
            'Monto Flujo 1',
            'Código Flujo Efectivo 2',
            'Monto Flujo 2',
            'Código Flujo Efectivo 3',
            'Monto Flujo 3',
            'Código Flujo Efectivo 4',
            'Monto Flujo 4',
            'Código Flujo Efectivo 5',
            'Monto Flujo 5',
            'Código Flujo Efectivo 6',
            'Monto Flujo 6',
            'Código Flujo Efectivo 7',
            'Monto Flujo 7',
            'Código Flujo Efectivo 8',
            'Monto Flujo 8',
            'Código Flujo Efectivo 9',
            'Monto Flujo 9',
            'Código Flujo Efectivo 10',
            'Monto Flujo 10',
            'Número Cuota De Pago',
            'Número Documento Desde',
            'Número Documento Hasta',
            'Centro Costo Concepto Presupuesto Caja 1',
            'Monto Moneda Base Centro Costo Concepto Presupuesto Caja 1',
            'Monto Moneda Adicional Centro Costo Concepto Presupuesto Caja 1',
            'Centro Costo Concepto Presupuesto Caja 2',
            'Monto Moneda Base Centro Costo Concepto Presupuesto Caja 2',
            'Monto Moneda Adicional Centro Costo Concepto Presupuesto Caja 2',
            'Centro Costo Concepto Presupuesto Caja 3',
            'Monto Moneda Base Centro Costo Concepto Presupuesto Caja 3',
            'Monto Moneda Adicional Centro Costo Concepto Presupuesto Caja 3',
            'Centro Costo Concepto Presupuesto Caja 4',
            'Monto Moneda Base Centro Costo Concepto Presupuesto Caja 4',
            'Monto Moneda Adicional Centro Costo Concepto Presupuesto Caja 4',
            'Centro Costo Concepto Presupuesto Caja 5',
            'Monto Moneda Base Centro Costo Concepto Presupuesto Caja 5',
            'Monto Moneda Adicional Centro Costo Concepto Presupuesto Caja 5',
            'Centro Costo Concepto Presupuesto Caja 6',
            'Monto Moneda Base Centro Costo Concepto Presupuesto Caja 6',
            'Monto Moneda Adicional Centro Costo Concepto Presupuesto Caja 6',
            'Centro Costo Concepto Presupuesto Caja 7',
            'Monto Moneda Base Centro Costo Concepto Presupuesto Caja 7',
            'Monto Moneda Adicional Centro Costo Concepto Presupuesto Caja 7',
            'Centro Costo Concepto Presupuesto Caja 8',
            'Monto Moneda Base Centro Costo Concepto Presupuesto Caja 8',
            'Monto Moneda Adicional Centro Costo Concepto Presupuesto Caja 8',
            'Centro Costo Concepto Presupuesto Caja 9',
            'Monto Moneda Base Centro Costo Concepto Presupuesto Caja 9',
            'Monto Moneda Adicional Centro Costo Concepto Presupuesto Caja 9',
            'Centro Costo Concepto Presupuesto Caja 10',
            'Monto Moneda Base Centro Costo Concepto Presupuesto Caja 10',
            'Monto Moneda Adicional Centro Costo Concepto Presupuesto Caja 10',
        ];
    }

    /**
     * Exporta los datos en formato Excel
     */
    private function exportExcel(string $filename, array $headers, $movements): StreamedResponse
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Softland Captura');

        // Escribir encabezados (fila 1)
        $colIndex = 1; // 1 = columna A
        foreach ($headers as $header) {
            $columnLetter = Coordinate::stringFromColumnIndex($colIndex);
            $sheet->setCellValue($columnLetter . '1', $header);
            $colIndex++;
        }

        // Escribir datos de movimientos
        $rowIndex = 2;
        foreach ($movements as $movement) {
            $colIndex = 1;
            foreach ($movement as $value) {
                $columnLetter = Coordinate::stringFromColumnIndex($colIndex);
                $sheet->setCellValue($columnLetter . $rowIndex, $value);
                $colIndex++;
            }
            $rowIndex++;
        }

        // Estilos de encabezado
        $lastColumnIndex = count($headers);
        $lastColumnLetter = Coordinate::stringFromColumnIndex($lastColumnIndex);
        $sheet->getStyle('A1:' . $lastColumnLetter . '1')->applyFromArray([
            'font' => ['bold' => false],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
            'borders' => [
                'allBorders' => ['borderStyle' => Border::BORDER_THIN],
                'outline' => ['borderStyle' => Border::BORDER_THIN],
            ],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FFFF00']],
        ]);
        $sheet->getRowDimension(1)->setRowHeight(28);

        // Ajuste de ancho de columnas
        for ($i = 1; $i <= $lastColumnIndex; $i++) {
            $columnLetter = Coordinate::stringFromColumnIndex($i);
            $sheet->getColumnDimension($columnLetter)->setAutoSize(true);
        }

        // Aplicar bordes a toda la hoja (incluyendo datos)
        $lastRow = $movements->count() > 0 ? $movements->count() + 1 : 10;
        $sheet->getStyle('A1:' . $lastColumnLetter . $lastRow)->applyFromArray([
            'borders' => [
                'allBorders' => ['borderStyle' => Border::BORDER_THIN],
                'outline' => ['borderStyle' => Border::BORDER_THIN],
            ],
        ]);

        // Congelar la fila de encabezado
        $sheet->freezePane('A2');

        // Centrar datos de la columna E en adelante (aplicar al final para asegurar prioridad)
        $sheet->getStyle('E2:' . $lastColumnLetter . $lastRow)->applyFromArray([
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);

        $writer = new Xlsx($spreadsheet);
        $writer->setPreCalculateFormulas(false);

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

    /**
     * Exporta los datos en formato CSV
     */
    private function exportCsv(string $filename, array $headers, $movements): StreamedResponse
    {
        $response = new StreamedResponse(function () use ($headers, $movements) {
            $handle = fopen('php://output', 'w');
            
            // Escribir BOM para UTF-8 (para compatibilidad con Excel)
            fwrite($handle, "\xEF\xBB\xBF");
            
            // Escribir encabezados
            fputcsv($handle, $headers, ';');
            
            // Escribir datos
            foreach ($movements as $movement) {
                fputcsv($handle, $movement, ';');
            }
            
            fclose($handle);
        });

        $response->headers->set('Content-Type', 'text/csv; charset=UTF-8');
        $response->headers->set('Content-Disposition', 'attachment; filename="' . $filename . '.csv"');
        $response->headers->set('Cache-Control', 'no-cache, must-revalidate');
        $response->headers->set('Expires', '0');
        $response->headers->set('Pragma', 'public');

        return $response;
    }
}


