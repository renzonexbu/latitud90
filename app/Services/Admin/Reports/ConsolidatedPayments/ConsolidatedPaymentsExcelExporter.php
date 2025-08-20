<?php

namespace App\Services\Admin\Reports\ConsolidatedPayments;

use Illuminate\Support\Collection;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ConsolidatedPaymentsExcelExporter
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
            
            // Aplicar estilos profesionales
            $this->applyProfessionalStyles($sheet, $data->count() + 1);
            
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
    
    private function applyProfessionalStyles($sheet, int $lastRow): void
    {
        // Estilo para headers
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
        
        $sheet->getStyle('A1:' . $sheet->getHighestColumn() . '1')->applyFromArray($headerStyle);
        
        // Estilo para datos
        $dataStyle = [
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => 'CCCCCC'],
                ],
            ],
            'alignment' => [
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ];
        
        if ($lastRow > 1) {
            $sheet->getStyle('A2:' . $sheet->getHighestColumn() . $lastRow)->applyFromArray($dataStyle);
        }
    }
}
