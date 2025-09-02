<?php

namespace App\Services\Admin\Reports\ConsolidatedPayments;

use Illuminate\Support\Collection;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Csv;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

class ConsolidatedPaymentsCsvExporter
{
    public function export(Collection $data, string $filename, array $selectedFields = []): BinaryFileResponse
    {
        try {
            // Validar que tenemos datos
            if ($data->isEmpty()) {
                throw new \InvalidArgumentException('No hay datos para exportar');
            }
            
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            
            // Headers fijos en español según requerimientos
            $headers = [
                'Código (Programa)',
                'Rut Alumno',
                'Nombre del Alumno',
                'Pago o Devolución $',
                'Documentos N° Boleta o NC',
                'Tipo de Documento',
                'N° Reserva',
                'Forma de Pago',
                'N° Cuotas Pagadas',
                'Fecha de Pago',
                'Aporte o becas',
                'Liberado',
                'Valor total prog.'
            ];
            
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
                foreach ($headers as $header) {
                    $col = chr(65 + $colIndex);
                    $value = $rowData[$header] ?? '';
                    
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
            $tempDir = storage_path('app/temp');
            if (!is_dir($tempDir)) {
                mkdir($tempDir, 0755, true);
            }
            $tempFile = $tempDir . '/' . $filename . '.csv';
            
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
        // Agregar BOM UTF-8 al inicio del archivo para Excel
        $content = file_get_contents($filePath);
        if (substr($content, 0, 3) !== "\xEF\xBB\xBF") {
            file_put_contents($filePath, "\xEF\xBB\xBF" . $content);
        }
    }
}
