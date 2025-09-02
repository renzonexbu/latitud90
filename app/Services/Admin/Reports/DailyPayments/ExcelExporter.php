<?php

namespace App\Services\Admin\Reports\DailyPayments;

use Illuminate\Support\Collection;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExcelExporter
{
    public function export(Collection $data, string $filename, array $selectedFields = []): StreamedResponse
    {
        try {
            // Limpiar cualquier output buffer
            while (ob_get_level()) {
                ob_end_clean();
            }
            
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
                        $sheet->getStyle($col . $rowIndex)->getNumberFormat()->setFormatCode('@');
                        $value = (string) $value;
                    }
                    
                    $sheet->setCellValue($col . $rowIndex, $value);
                    $colIndex++;
                }
                $rowIndex++;
            }
            
            // Aplicar formato Excel
            $this->applyExcelFormatting($sheet, $headers);
            
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
            throw new \RuntimeException('Error al exportar Excel: ' . $e->getMessage());
        }
    }
    
    private function applyExcelFormatting($sheet, array $headers): void
    {
        // Formato para headers
        $headerStyle = [
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
            ],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['rgb' => '4472C4'],
            ],
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
            ],
        ];
        
        $sheet->getStyle('A1:' . chr(65 + count($headers) - 1) . '1')->applyFromArray($headerStyle);
        
        // Formato para columnas específicas
        $textColumns = ['N° Orden', 'Documento'];
        
        foreach ($headers as $index => $header) {
            $col = chr(65 + $index);
            
            if (in_array($header, $textColumns)) {
                // Aplicar formato de texto para columnas que deben ser tratadas como texto
                $sheet->getStyle($col . '2:' . $col . '1000')->getNumberFormat()->setFormatCode('@');
                $sheet->getStyle($col . '2:' . $col . '1000')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT);
            }
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
        
        $lastRow = $sheet->getHighestRow();
        $sheet->getStyle('A1:' . chr(65 + count($headers) - 1) . $lastRow)->applyFromArray($borderStyle);
    }
}
