<?php

namespace App\Services\Admin\Reports\PartialReport;

use Illuminate\Support\Collection;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class ExcelExporter
{
    /**
     * Exporta los datos a formato Excel
     */
    public function export(Collection $data, string $filename): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        try {
            // Crear nuevo spreadsheet
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();

            // Obtener headers de la primera fila
            $headers = [];
            if ($data->count() > 0) {
                $headers = array_keys($data->first());
            }

            // Escribir headers en la primera fila
            foreach ($headers as $index => $header) {
                $col = chr(65 + $index); // A, B, C, D...
                $sheet->setCellValue($col . '1', $header);
            }

            // Escribir datos
            foreach ($data as $rowIndex => $rowData) {
                $row = $rowIndex + 2; // Empezar en fila 2
                foreach ($rowData as $colIndex => $value) {
                    $col = chr(65 + $colIndex); // A, B, C, D...

                    // Limpiar y validar valores
                    if (is_null($value)) {
                        $value = '';
                    } elseif (is_numeric($value)) {
                        $value = (float) $value;
                    } else {
                        $value = (string) $value;
                    }

                    $sheet->setCellValue($col . $row, $value);
                }
            }

            // Aplicar formato
            $this->applyExcelFormatting($sheet, $headers);

            // Crear el writer
            $writer = new Xlsx($spreadsheet);

            // Generar nombre de archivo único
            $uniqueFilename = $filename . '_' . date('Y-m-d_H-i-s') . '.xlsx';
            $tempPath = storage_path('app/temp/' . $uniqueFilename);

            // Crear directorio si no existe
            if (!file_exists(dirname($tempPath))) {
                mkdir(dirname($tempPath), 0755, true);
            }

            // Guardar archivo
            $writer->save($tempPath);

            // Verificar que el archivo se creó correctamente
            if (!file_exists($tempPath) || filesize($tempPath) === 0) {
                throw new \Exception('No se pudo crear el archivo Excel');
            }

            // Retornar descarga
            return response()->download($tempPath, $uniqueFilename)->deleteFileAfterSend();
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Error al exportar Excel', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);

            // Fallback a CSV si hay error
            $csvExporter = new CsvExporter();
            return $csvExporter->export($data, $filename);
        }
    }

    /**
     * Aplica formato al archivo Excel
     */
    private function applyExcelFormatting($sheet, array $headers): void
    {
        $lastRow = $sheet->getHighestRow();
        $lastCol = $sheet->getHighestColumn();

        // Formato para headers (fila 1)
        $headerRange = 'A1:' . $lastCol . '1';
        $sheet->getStyle($headerRange)->applyFromArray([
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
        ]);

        // Formato para datos
        $dataRange = 'A2:' . $lastCol . $lastRow;
        $sheet->getStyle($dataRange)->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => '000000'],
                ],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_LEFT,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ]);

        // Formato específico para columnas numéricas
        $numericColumns = ['Precio Total', 'Descuentos', 'Monto Neto', 'Total Pagado', 'Saldo Pendiente', 'Progreso de Pago (%)'];
        foreach ($headers as $index => $header) {
            if (in_array($header, $numericColumns)) {
                $col = chr(65 + $index); // Convertir índice a letra de columna
                $range = $col . '2:' . $col . $lastRow;

                if ($header === 'Progreso de Pago (%)') {
                    // Formato de porcentaje
                    $sheet->getStyle($range)->getNumberFormat()->setFormatCode('0.00%');
                } else {
                    // Formato de moneda chilena
                    $sheet->getStyle($range)->getNumberFormat()->setFormatCode('#,##0');
                }

                // Alinear números a la derecha
                $sheet->getStyle($range)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            }
        }

        // Autoajustar columnas
        foreach (range('A', $lastCol) as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // Altura de fila para headers
        $sheet->getRowDimension('1')->setRowHeight(25);
    }
}
