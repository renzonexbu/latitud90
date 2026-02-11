<?php

namespace App\Services\Admin\Reports\PartialReport;

use Illuminate\Support\Collection;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use App\Traits\ExcelReportHeader;

class ExcelExporter
{
    use ExcelReportHeader;
    /**
     * Exporta los datos a formato Excel
     */
    public function export(Collection $data, string $filename): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        try {
            // Crear nuevo spreadsheet
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle('Reporte Estado de Cuenta');

            // Header con logo, título y fecha de exportación
            $startRow = $this->addReportHeader($sheet, 'Estado de Cuenta Parcial');

            // Obtener headers de la primera fila
            $headers = [];
            if ($data->count() > 0) {
                $headers = array_keys($data->first());
            }

            // Escribir headers
            foreach ($headers as $index => $header) {
                $col = chr(65 + $index);
                $sheet->setCellValue($col . $startRow, $header);
            }

            // Escribir datos
            foreach ($data as $rowIndex => $rowData) {
                $row = $rowIndex + $startRow + 1;
                $colIndex = 0; // Contador numérico para las columnas
                foreach ($rowData as $value) {
                    $col = chr(65 + $colIndex); // A, B, C, D...

                    // Limpiar y validar valores
                    if (is_null($value)) {
                        $value = '';
                    } elseif (is_numeric($value)) {
                        // Verificar si es un código de inscripción (más de 8 dígitos)
                        if (strlen((string) $value) > 8) {
                            // Forzar como texto para evitar notación científica
                            $sheet->getStyle($col . $row)->getNumberFormat()->setFormatCode('@');
                            $value = (string) $value;
                        } else {
                            $value = (float) $value;
                        }
                    } else {
                        // Limpiar caracteres especiales que pueden causar problemas
                        $value = mb_convert_encoding((string) $value, 'UTF-8', 'UTF-8');
                    }

                    $sheet->setCellValue($col . $row, $value);
                    $colIndex++; // Incrementar el contador de columnas
                }
            }

            // Aplicar formato
            $this->applyExcelFormatting($sheet, $headers, $startRow);

            // Crear el writer
            $writer = new Xlsx($spreadsheet);

            // Configurar el writer para mejor compatibilidad
            $writer->setPreCalculateFormulas(false);
            $writer->setIncludeCharts(false);
            $writer->setUseDiskCaching(false);

            // Generar nombre de archivo único - sanitizar caracteres especiales
            $sanitizedFilename = preg_replace('/[^a-zA-Z0-9_-]/', '_', $filename);
            $uniqueFilename = $sanitizedFilename . '.xlsx';

            // Retornar descarga usando StreamedResponse SIN archivo temporal
            return new \Symfony\Component\HttpFoundation\StreamedResponse(
                function () use ($writer) {
                    // Limpiar cualquier output previo
                    if (ob_get_level()) {
                        ob_end_clean();
                    }

                    // Escribir directamente al output sin archivo temporal
                    $writer->save('php://output');
                },
                200,
                [
                    'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                    'Content-Disposition' => "attachment; filename=\"$uniqueFilename\"",
                    'Cache-Control' => 'max-age=0',
                    'Expires' => 'Mon, 26 Jul 1997 05:00:00 GMT',
                    'Last-Modified' => gmdate('D, d M Y H:i:s') . ' GMT',
                    'Pragma' => 'public',
                ]
            );
        } catch (\Exception $e) {
            // Fallback a CSV si hay error
            $csvExporter = new CsvExporter();
            $csvResponse = $csvExporter->export($data, $filename);

            // Convertir BinaryFileResponse a StreamedResponse si es necesario
            if ($csvResponse instanceof \Symfony\Component\HttpFoundation\BinaryFileResponse) {
                $filePath = $csvResponse->getFile()->getPathname();
                $filename = basename($filePath);

                return new \Symfony\Component\HttpFoundation\StreamedResponse(
                    function () use ($filePath) {
                        $handle = fopen($filePath, 'rb');
                        while (!feof($handle)) {
                            echo fread($handle, 8192);
                            flush();
                        }
                        fclose($handle);
                        unlink($filePath);
                    },
                    200,
                    [
                        'Content-Type' => 'text/csv; charset=UTF-8',
                        'Content-Disposition' => "attachment; filename=\"$filename\"",
                        'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
                        'Expires' => '0',
                        'Pragma' => 'no-cache',
                    ]
                );
            }

            return $csvResponse;
        }
    }

    /**
     * Aplica formato al archivo Excel
     */
    private function applyExcelFormatting($sheet, array $headers, int $headerRow = 1): void
    {
        $lastRow = $sheet->getHighestRow();
        $lastCol = $sheet->getHighestColumn();
        $dataStartRow = $headerRow + 1;

        // Formato para headers
        $headerRange = "A{$headerRow}:{$lastCol}{$headerRow}";
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
        if ($lastRow >= $dataStartRow) {
            $dataRange = "A{$dataStartRow}:{$lastCol}{$lastRow}";
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
        }

        // Formato específico para columnas numéricas
        $numericColumns = ['Precio Total', 'Descuentos', 'Monto Neto', 'Total Pagado', 'Saldo Pendiente', 'Progreso de Pago (%)'];
        $textColumns = ['Código de Inscripción', 'Documento'];

        foreach ($headers as $index => $header) {
            $col = chr(65 + $index);
            $range = "{$col}{$dataStartRow}:{$col}{$lastRow}";

            if (in_array($header, $numericColumns)) {
                if ($header === 'Progreso de Pago (%)') {
                    $sheet->getStyle($range)->getNumberFormat()->setFormatCode('0.00%');
                } else {
                    $sheet->getStyle($range)->getNumberFormat()->setFormatCode('#,##0');
                }
                $sheet->getStyle($range)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            } elseif (in_array($header, $textColumns)) {
                $sheet->getStyle($range)->getNumberFormat()->setFormatCode('@');
                $sheet->getStyle($range)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
            }
        }

        // Autoajustar columnas
        foreach (range('A', $lastCol) as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // Altura de fila para headers
        $sheet->getRowDimension($headerRow)->setRowHeight(25);
    }
}
