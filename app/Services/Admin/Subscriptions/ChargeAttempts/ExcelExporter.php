<?php

namespace App\Services\Admin\Subscriptions\ChargeAttempts;

use Illuminate\Support\Collection;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExcelExporter
{
    public function export(Collection $data, string $filename): StreamedResponse
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
            $colIndex = 1;
            foreach ($headers as $header) {
                $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIndex);
                $sheet->setCellValue($colLetter . '1', $header);
                $colIndex++;
            }

            // Escribir datos
            $rowIndex = 2;
            foreach ($data as $rowData) {
                $colIndex = 1;
                foreach ($rowData as $key => $value) {
                    $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIndex);
                    $cellCoordinate = $colLetter . $rowIndex;

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
                        $sheet->getStyle($cellCoordinate)->getNumberFormat()->setFormatCode('@');
                        $value = (string) $value;
                    }

                    // Asegurar que columnas específicas sean tratadas como texto
                    if (in_array($key, ['Documento', 'ID Suscripción VirtualPos', 'ID Cargo VirtualPos', 'Código Plan', 'ID Cuota'])) {
                        $sheet->getStyle($cellCoordinate)->getNumberFormat()->setFormatCode('@');
                        $value = (string) $value;
                    }

                    $sheet->setCellValue($cellCoordinate, $value);
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
            $response = new StreamedResponse(function () use ($writer) {
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
        $numColumns = count($headers);
        $lastColumn = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($numColumns);

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

        $sheet->getStyle('A1:' . $lastColumn . '1')->applyFromArray($headerStyle);

        // Formato para columnas específicas
        $textColumns = ['ID Intento Cobro', 'ID Suscripción', 'ID Plan VirtualPos', 'Código Plan',
                        'ID Cuota', 'Documento', 'ID Cargo VirtualPos', 'ID Suscripción VirtualPos',
                        'ID Cargo Original'];

        foreach ($headers as $index => $header) {
            $colIndex = $index + 1;
            $col = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIndex);

            if (in_array($header, $textColumns)) {
                // Aplicar formato de texto para columnas que deben ser tratadas como texto
                $sheet->getStyle($col . '2:' . $col . '1000')->getNumberFormat()->setFormatCode('@');
                $sheet->getStyle($col . '2:' . $col . '1000')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT);
            }

            // Auto-ajustar columna
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
        $sheet->getStyle('A1:' . $lastColumn . $lastRow)->applyFromArray($borderStyle);
    }
}
