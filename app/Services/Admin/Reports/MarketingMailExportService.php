<?php

namespace App\Services\Admin\Reports;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use Symfony\Component\HttpFoundation\StreamedResponse;
use App\Models\MarketingMail;
use App\Traits\AdminLogging;

class MarketingMailExportService
{
    use AdminLogging;

    public function export(array $filters = []): StreamedResponse
    {
        try {
            // Log para debugging
            Log::info('MarketingMailExportService: Iniciando exportación', [
                'filters' => $filters,
                'user_id' => auth()->id(),
                'timestamp' => now()
            ]);

            // Limpiar cualquier output buffer
            while (ob_get_level()) {
                ob_end_clean();
            }

            $spreadsheet = new Spreadsheet();
            $this->createMarketingMailSheet($spreadsheet, $filters);

            // Configurar writer XLSX
            $writer = new Xlsx($spreadsheet);
            $writer->setPreCalculateFormulas(false);
            $writer->setIncludeCharts(false);

            // Generar nombre de archivo
            $filename = 'marketing_mails_' . now()->format('Y-m-d_H-i-s');

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
                'marketing_mails',
                "Exportación de emails de marketing: {$filename}",
                [
                    'filename' => $filename,
                    'filters' => $filters,
                    'export_type' => 'marketing_mails_excel',
                ]
            );

            Log::info('MarketingMailExportService: Exportación completada exitosamente', [
                'filename' => $filename,
                'response_headers' => $response->headers->all()
            ]);

            return $response;

        } catch (\Exception $e) {
            Log::error('MarketingMailExportService: Error en exportación', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'filters' => $filters
            ]);
            
            throw new \RuntimeException('Error al exportar emails de marketing: ' . $e->getMessage());
        }
    }

    private function createMarketingMailSheet(Spreadsheet $spreadsheet, array $filters): void
    {
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Marketing Mails');

        // Obtener datos con filtros
        $query = MarketingMail::query();

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
            $this->addEmptySheetMessage($sheet, 'No hay emails de marketing para exportar');
            return;
        }

        // Headers
        $headers = [
            'ID',
            'Email',
            'Estado',
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
            $sheet->setCellValue('A' . $row, $item->id);
            $sheet->setCellValue('B' . $row, $item->email);
            $sheet->setCellValue('C' . $row, $item->is_active ? 'Activo' : 'Inactivo');
            $sheet->setCellValue('D' . $row, $item->created_at ? $item->created_at->format('d/m/Y H:i:s') : '');
            $sheet->setCellValue('E' . $row, $item->updated_at ? $item->updated_at->format('d/m/Y H:i:s') : '');

            // Aplicar bordes a las celdas de datos
            $sheet->getStyle('A' . $row . ':E' . $row)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
            
            $row++;
        }

        // Autoajustar columnas
        foreach (range('A', 'E') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // Aplicar estilo alternado a las filas
        for ($i = 2; $i < $row; $i++) {
            if ($i % 2 == 0) {
                $sheet->getStyle('A' . $i . ':E' . $i)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('F9F9F9');
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
