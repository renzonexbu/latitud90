<?php

namespace App\Services\Admin\Reports\RecoverySchedule;

use Illuminate\Support\Collection;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Csv;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

class CsvExporter
{
    public function export(Collection $data, string $filename): BinaryFileResponse
    {
        try {
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            
            // Obtener headers del primer registro
            $firstRow = $data->first();
            if (!$firstRow) {
                throw new \InvalidArgumentException('No hay datos para exportar');
            }
            
            $headers = array_keys($firstRow);
            
            // Escribir headers
            $colIndex = 0;
            foreach ($headers as $header) {
                $col = chr(65 + $colIndex);
                $sheet->setCellValue($col . '1', $header);
                $colIndex++;
            }
            
            // Escribir datos
            $rowIndex = 2;
            foreach ($data as $rowData) {
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
                        $value = '="' . $value . '"';
                    }
                    
                    $sheet->setCellValue($col . $rowIndex, $value);
                    $colIndex++;
                }
                $rowIndex++;
            }
            
            // Configurar writer CSV
            $writer = new Csv($spreadsheet);
            $writer->setUseBOM(true);
            $writer->setDelimiter(';');
            
            // Crear archivo temporal
            $tempFile = storage_path('app/temp/' . $filename . '.csv');
            if (!is_dir(dirname($tempFile))) {
                mkdir(dirname($tempFile), 0755, true);
            }
            
            $writer->save($tempFile);
            
            // Optimizar para Excel
            $this->optimizeForExcel($tempFile);
            
            // Crear respuesta
            $response = new BinaryFileResponse($tempFile);
            $response->setContentDisposition(
                ResponseHeaderBag::DISPOSITION_ATTACHMENT,
                $filename . '.csv'
            );
            
            // Headers para CSV
            $response->headers->set('Content-Type', 'text/csv; charset=UTF-8');
            $response->headers->set('Cache-Control', 'no-cache, must-revalidate');
            $response->headers->set('Expires', '0');
            $response->headers->set('Pragma', 'public');
            $response->headers->set('Last-Modified', gmdate('D, d M Y H:i:s') . ' GMT');
            
            return $response;
            
        } catch (\Exception $e) {
            throw new \RuntimeException('Error al exportar CSV: ' . $e->getMessage());
        }
    }
    
    private function optimizeForExcel(string $filePath): void
    {
        if (!file_exists($filePath)) {
            return;
        }
        
        $content = file_get_contents($filePath);
        
        // Asegurar que el BOM esté presente
        if (substr($content, 0, 3) !== "\xEF\xBB\xBF") {
            $content = "\xEF\xBB\xBF" . $content;
        }
        
        file_put_contents($filePath, $content);
    }
}
