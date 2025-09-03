<?php

namespace App\Services\Admin\Reports;

use Illuminate\Support\Collection;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use Symfony\Component\HttpFoundation\StreamedResponse;
use App\Models\Newsletter;
use App\Traits\AdminLogging;

class NewsletterExportService
{
    use AdminLogging;

    public function export(array $filters = []): StreamedResponse
    {
        try {
            // Limpiar cualquier output buffer
            while (ob_get_level()) {
                ob_end_clean();
            }

            $spreadsheet = new Spreadsheet();
            $this->createNewsletterSheet($spreadsheet, $filters);

            // Configurar writer XLSX
            $writer = new Xlsx($spreadsheet);
            $writer->setPreCalculateFormulas(false);
            $writer->setIncludeCharts(false);

            // Generar nombre de archivo
            $filename = 'newsletter_suscriptores_' . now()->format('Y-m-d_H-i-s');

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

            // Log the export action
            $this->logExport(
                'newsletter',
                "Exportación de suscriptores del newsletter: {$filename}",
                [
                    'filename' => $filename,
                    'filters' => $filters,
                    'export_type' => 'newsletter_excel',
                ]
            );

            return $response;

        } catch (\Exception $e) {
            throw new \RuntimeException('Error al exportar suscriptores del newsletter: ' . $e->getMessage());
        }
    }

    private function createNewsletterSheet(Spreadsheet $spreadsheet, array $filters): void
    {
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Suscriptores Newsletter');

        // Obtener datos con filtros
        $query = Newsletter::query();

        // Aplicar filtros
        if (!empty($filters['search'])) {
            $query->where('email', 'like', '%' . $filters['search'] . '%');
        }

        if (!empty($filters['status'])) {
            if ($filters['status'] === 'active') {
                $query->where('is_active', true);
            } elseif ($filters['status'] === 'inactive') {
                $query->where('is_active', false);
            }
        }

        // Ordenar
        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortOrder = $filters['sort_order'] ?? 'desc';
        $query->orderBy($sortBy, $sortOrder);

        // Obtener todos los datos (sin paginación para exportar)
        $data = $query->get();

        if ($data->isEmpty()) {
            $this->addEmptySheetMessage($sheet, 'No hay suscriptores del newsletter para exportar');
            return;
        }

        // Headers
        $headers = [
            'ID',
            'Email',
            'Estado',
            'Fecha de Suscripción',
            'Fecha de Creación',
            'Fecha de Actualización'
        ];

        // Aplicar estilos a los headers
        $headerStyle = [
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '1C4F4A'],
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

        // Escribir headers
        foreach ($headers as $colIndex => $header) {
            $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIndex + 1);
            $sheet->setCellValue($colLetter . '1', $header);
            $sheet->getStyle($colLetter . '1')->applyFromArray($headerStyle);
        }

        // Escribir datos
        $row = 2;
        foreach ($data as $item) {
            $status = $item->is_active ? 'Activo' : 'Inactivo';

            $sheet->setCellValue('A' . $row, $item->id);
            $sheet->setCellValue('B' . $row, $item->email);
            $sheet->setCellValue('C' . $row, $status);
            $sheet->setCellValue('D' . $row, $item->subscribed_at ? $item->subscribed_at->format('d/m/Y H:i:s') : '');
            $sheet->setCellValue('E' . $row, $item->created_at ? $item->created_at->format('d/m/Y H:i:s') : '');
            $sheet->setCellValue('F' . $row, $item->updated_at ? $item->updated_at->format('d/m/Y H:i:s') : '');

            // Aplicar bordes a las celdas de datos
            $sheet->getStyle('A' . $row . ':F' . $row)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
            
            $row++;
        }

        // Autoajustar columnas
        foreach (range('A', 'F') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // Aplicar estilo alternado a las filas
        for ($i = 2; $i < $row; $i++) {
            if ($i % 2 == 0) {
                $sheet->getStyle('A' . $i . ':F' . $i)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('F9F9F9');
            }
        }
    }

    private function addEmptySheetMessage($sheet, string $message): void
    {
        $sheet->setCellValue('A1', $message);
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet->getColumnDimension('A')->setAutoSize(true);
    }
}
