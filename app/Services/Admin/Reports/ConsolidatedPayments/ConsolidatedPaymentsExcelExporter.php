<?php

namespace App\Services\Admin\Reports\ConsolidatedPayments;

use Illuminate\Support\Collection;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use Symfony\Component\HttpFoundation\StreamedResponse;
use App\Traits\ExcelReportHeader;

class ConsolidatedPaymentsExcelExporter
{
    use ExcelReportHeader;
    public function export(Collection $data, string $filename, array $selectedFields = []): StreamedResponse
    {
        try {
            // Validar que tenemos datos
            if ($data->isEmpty()) {
                throw new \InvalidArgumentException('No hay datos para exportar');
            }
            
            // Limpiar cualquier output buffer
            while (ob_get_level()) {
                ob_end_clean();
            }
            
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();

            // Header con logo, título y fecha de exportación
            $startRow = $this->addReportHeader($sheet, 'Consolidado de Pagos');

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
                $sheet->setCellValue($col . $startRow, $header);
                $colIndex++;
            }

            // Escribir datos
            $rowIndex = $startRow + 1;
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
                        $sheet->getStyle($col . $rowIndex)->getNumberFormat()->setFormatCode('@');
                        $value = (string) $value;
                    }
                    
                    $sheet->setCellValue($col . $rowIndex, $value);
                    $colIndex++;
                }
                $rowIndex++;
            }
            
            // Aplicar estilos profesionales
            $this->applyProfessionalStyles($sheet, $startRow, $startRow + $data->count());
            
            // Autoajustar el ancho de columnas según contenido
            $highestColumnLetter = $sheet->getHighestColumn();
            $highestColumnIndex = Coordinate::columnIndexFromString($highestColumnLetter);
            for ($col = 1; $col <= $highestColumnIndex; $col++) {
                $columnLetter = Coordinate::stringFromColumnIndex($col);
                $sheet->getColumnDimension($columnLetter)->setAutoSize(true);
            }
            
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
    
    private function applyProfessionalStyles($sheet, int $headerRow, int $lastRow): void
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

        $sheet->getStyle("A{$headerRow}:" . $sheet->getHighestColumn() . $headerRow)->applyFromArray($headerStyle);

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

        $dataStartRow = $headerRow + 1;
        if ($lastRow >= $dataStartRow) {
            $sheet->getStyle("A{$dataStartRow}:" . $sheet->getHighestColumn() . $lastRow)->applyFromArray($dataStyle);
        }
    }
}
