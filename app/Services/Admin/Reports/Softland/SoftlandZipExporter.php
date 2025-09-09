<?php

namespace App\Services\Admin\Reports\Softland;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use ZipArchive;

class SoftlandZipExporter
{
    private SoftlandAuxiliaresService $auxiliaresService;
    private AuxiliaresExporter $auxiliaresExporter;
    private SoftlandDataService $softlandDataService;
    private SoftlandB2DataService $softlandB2DataService;
    private ExcelExporter $excelExporter;

    public function __construct(
        SoftlandAuxiliaresService $auxiliaresService,
        AuxiliaresExporter $auxiliaresExporter,
        SoftlandDataService $softlandDataService,
        SoftlandB2DataService $softlandB2DataService,
        ExcelExporter $excelExporter
    ) {
        $this->auxiliaresService = $auxiliaresService;
        $this->auxiliaresExporter = $auxiliaresExporter;
        $this->softlandDataService = $softlandDataService;
        $this->softlandB2DataService = $softlandB2DataService;
        $this->excelExporter = $excelExporter;
    }

    /**
     * Genera un archivo ZIP con ambos archivos Softland
     */
    public function exportToZip(array $filters = [], string $format = 'excel'): string
    {
        try {
            // Crear directorio temporal si no existe
            $tempDir = storage_path('app/temp');
            if (!file_exists($tempDir)) {
                mkdir($tempDir, 0755, true);
            }

            // Generar timestamp para nombres únicos
            $timestamp = now()->format('Y-m-d_H-i-s');
            $zipFileName = "softland_completo_{$timestamp}.zip";
            $zipPath = "{$tempDir}/{$zipFileName}";

            // Crear archivo ZIP
            $zip = new ZipArchive();
            if ($zip->open($zipPath, ZipArchive::CREATE) !== TRUE) {
                throw new \Exception("No se pudo crear el archivo ZIP: {$zipPath}");
            }

            // 1. Generar archivo de Auxiliares
            $auxiliaresFileName = $this->generateAuxiliaresFile($format, $timestamp);
            if ($auxiliaresFileName && file_exists($auxiliaresFileName)) {
                $auxiliaresContent = file_get_contents($auxiliaresFileName);
                $zip->addFromString(basename($auxiliaresFileName), $auxiliaresContent);
            }

            // 2. Generar archivo de Movimientos Contables (B2 + AC)
            $movimientosFileName = $this->generateMovimientosFile($filters, $format, $timestamp);
            if ($movimientosFileName && file_exists($movimientosFileName)) {
                $movimientosContent = file_get_contents($movimientosFileName);
                $zip->addFromString(basename($movimientosFileName), $movimientosContent);
            }

            // 3. Generar archivo de Movimientos B2 únicamente
            $movimientosB2FileName = $this->generateMovimientosB2File($filters, $format, $timestamp);
            if ($movimientosB2FileName && file_exists($movimientosB2FileName)) {
                $movimientosB2Content = file_get_contents($movimientosB2FileName);
                $zip->addFromString(basename($movimientosB2FileName), $movimientosB2Content);
            }

            // Cerrar ZIP
            $zip->close();

            // Limpiar archivos temporales individuales
            $this->cleanupTempFiles([$auxiliaresFileName, $movimientosFileName, $movimientosB2FileName]);

            return $zipPath;

        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * Genera el archivo de auxiliares
     */
    private function generateAuxiliaresFile(string $format, string $timestamp): ?string
    {
        try {
            $fileName = "auxiliares_softland_{$timestamp}." . ($format === 'excel' ? 'xlsx' : 'csv');
            $filePath = storage_path("app/temp/{$fileName}");

            if ($format === 'excel') {
                // Generar Excel de auxiliares usando el método existente
                $response = $this->auxiliaresExporter->exportToExcel();
                
                // Obtener el contenido del StreamedResponse
                ob_start();
                $response->sendContent();
                $content = ob_get_clean();
                
                // Guardar el contenido en el archivo
                file_put_contents($filePath, $content);
            } else {
                // Generar CSV de auxiliares
                $tempCsvPath = $this->auxiliaresExporter->exportToCsv();
                if (file_exists($tempCsvPath)) {
                    // Simplemente usar el archivo temporal como archivo final
                    $filePath = $tempCsvPath;
                }
            }

            return $filePath;

        } catch (\Exception $e) {
            Log::error('Error al generar archivo de auxiliares', [
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Genera el archivo de movimientos contables
     */
    private function generateMovimientosFile(array $filters, string $format, string $timestamp): ?string
    {
        try {
            $fileName = "movimientos_softland_{$timestamp}." . ($format === 'excel' ? 'xlsx' : 'csv');
            $filePath = storage_path("app/temp/{$fileName}");

            // Generar archivo de movimientos usando el método existente
            $response = $this->excelExporter->export($fileName, $filters, $format);
            
            // Obtener el contenido del StreamedResponse
            ob_start();
            $response->sendContent();
            $content = ob_get_clean();
            
            // Guardar el contenido en el archivo
            file_put_contents($filePath, $content);

            return $filePath;

        } catch (\Exception $e) {
            Log::error('Error al generar archivo de movimientos', [
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Genera el archivo de movimientos B2 únicamente
     */
    private function generateMovimientosB2File(array $filters, string $format, string $timestamp): ?string
    {
        try {
            $fileName = "movimientos_b2_softland_{$timestamp}." . ($format === 'excel' ? 'xlsx' : 'csv');
            $filePath = storage_path("app/temp/{$fileName}");

            // Generar datos B2 usando el nuevo servicio
            $movements = $this->softlandB2DataService->generateMovements($filters);
            
            if ($format === 'excel') {
                // Crear Excel para B2 con el mismo formato profesional
                $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
                $sheet = $spreadsheet->getActiveSheet();
                $sheet->setTitle('Softland Captura B2');
                
                // Headers profesionales (mismo formato que ExcelExporter)
                $headers = [
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
                
                // Escribir encabezados (fila 1)
                $colIndex = 1;
                foreach ($headers as $header) {
                    $columnLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIndex);
                    $sheet->setCellValue($columnLetter . '1', $header);
                    $colIndex++;
                }
                
                // Escribir datos de movimientos
                $rowIndex = 2;
                foreach ($movements as $movement) {
                    $colIndex = 1;
                    foreach ($movement as $value) {
                        $columnLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIndex);
                        $sheet->setCellValue($columnLetter . $rowIndex, $value);
                        $colIndex++;
                    }
                    $rowIndex++;
                }
                
                // Aplicar estilos profesionales (mismo que ExcelExporter)
                $lastColumnIndex = count($headers);
                $lastColumnLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($lastColumnIndex);
                
                // Estilos de encabezado
                $sheet->getStyle('A1:' . $lastColumnLetter . '1')->applyFromArray([
                    'font' => ['bold' => false],
                    'alignment' => [
                        'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                        'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
                        'wrapText' => true
                    ],
                    'borders' => [
                        'allBorders' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN],
                        'outline' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN],
                    ],
                    'fill' => [
                        'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'FFFF00']
                    ],
                ]);
                $sheet->getRowDimension(1)->setRowHeight(28);
                
                // Ajuste de ancho de columnas
                for ($i = 1; $i <= $lastColumnIndex; $i++) {
                    $columnLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($i);
                    $sheet->getColumnDimension($columnLetter)->setAutoSize(true);
                }
                
                // Aplicar bordes a toda la hoja
                $lastRow = $movements->count() > 0 ? $movements->count() + 1 : 10;
                $sheet->getStyle('A1:' . $lastColumnLetter . $lastRow)->applyFromArray([
                    'borders' => [
                        'allBorders' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN],
                        'outline' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN],
                    ],
                ]);
                
                // Congelar la fila de encabezado
                $sheet->freezePane('A2');
                
                // Centrar datos de la columna E en adelante
                $sheet->getStyle('E2:' . $lastColumnLetter . $lastRow)->applyFromArray([
                    'alignment' => [
                        'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                        'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER
                    ],
                ]);
                
                // Guardar archivo
                $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
                $writer->setPreCalculateFormulas(false);
                $writer->save($filePath);
            } else {
                // Generar CSV para B2
                $handle = fopen($filePath, 'w');
                
                // Headers
                if ($movements->isNotEmpty()) {
                    fputcsv($handle, array_keys($movements->first()));
                    
                    // Datos
                    foreach ($movements as $movement) {
                        fputcsv($handle, array_values($movement));
                    }
                }
                
                fclose($handle);
            }

            return $filePath;

        } catch (\Exception $e) {
            Log::error('Error al generar archivo de movimientos B2', [
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Limpia archivos temporales
     */
    private function cleanupTempFiles(array $files): void
    {
        foreach ($files as $file) {
            if ($file && file_exists($file)) {
                unlink($file);
            }
        }
    }

    /**
     * Limpia archivos ZIP antiguos (más de 1 hora)
     */
    public function cleanupOldFiles(): void
    {
        try {
            $tempDir = storage_path('app/temp');
            $files = glob("{$tempDir}/softland_completo_*.zip");
            
            foreach ($files as $file) {
                if (filemtime($file) < (time() - 3600)) { // 1 hora
                    unlink($file);
                }
            }
        } catch (\Exception $e) {
            // Silenciar errores de limpieza
        }
    }
}
