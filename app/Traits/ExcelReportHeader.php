<?php

namespace App\Traits;

use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use Carbon\Carbon;

trait ExcelReportHeader
{
    /**
     * Agrega el header estándar con logo, título y fecha/hora de exportación.
     *
     * @param \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet
     * @param string $title Título del reporte
     * @return int Fila donde debe comenzar el contenido (headers de columnas)
     */
    protected function addReportHeader($sheet, string $title): int
    {
        // Fondo blanco para el área del logo
        $sheet->getStyle('A1:B3')->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setRGB('FFFFFF');
        $sheet->mergeCells('A1:B3');

        // Altura de filas del header
        for ($r = 1; $r <= 3; $r++) {
            $sheet->getRowDimension($r)->setRowHeight(20);
        }

        // Logo
        $logoPath = base_path('resources/images/logo-color.png');
        if (file_exists($logoPath)) {
            $drawing = new Drawing();
            $drawing->setPath($logoPath);
            $drawing->setWorksheet($sheet);
            $drawing->setCoordinates('A1');
            $drawing->setHeight(55);
            $drawing->setOffsetX(5);
            $drawing->setOffsetY(2);
        }

        // Título
        $sheet->setCellValue('C1', $title);
        $sheet->getStyle('C1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 18],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_LEFT,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ]);

        // Fecha y hora de exportación
        $exportDateTime = Carbon::now('America/Santiago')->format('d/m/Y H:i:s');
        $sheet->setCellValue('C2', 'Fecha de exportación: ' . $exportDateTime);
        $sheet->getStyle('C2')->applyFromArray([
            'font' => ['size' => 11, 'italic' => true],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_LEFT,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ]);

        // Fila 4 vacía como separador
        // Retorna la fila 5 como inicio del contenido
        return 5;
    }
}
