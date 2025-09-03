<?php

namespace App\Services\Admin\Reports;

use Illuminate\Support\Collection;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use Symfony\Component\HttpFoundation\StreamedResponse;
use App\Models\AdminLog;
use App\Traits\AdminLogging;
use Carbon\Carbon;

class AdminLogsExportService
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
            $this->createAdminLogsSheet($spreadsheet, $filters);

            // Configurar writer XLSX
            $writer = new Xlsx($spreadsheet);
            $writer->setPreCalculateFormulas(false);
            $writer->setIncludeCharts(false);

            // Generar nombre de archivo
            $filename = 'admin_logs_' . now()->format('Y-m-d_H-i-s');

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
                'admin_logs',
                "Exportación de logs administrativos: {$filename}",
                [
                    'filename' => $filename,
                    'filters' => $filters,
                    'export_type' => 'admin_logs_excel',
                ]
            );

            return $response;

        } catch (\Exception $e) {
            throw new \RuntimeException('Error al exportar logs administrativos: ' . $e->getMessage());
        }
    }

    private function createAdminLogsSheet(Spreadsheet $spreadsheet, array $filters): void
    {
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Logs Administrativos');

        // Obtener datos con filtros
        $query = AdminLog::query();

        // Aplicar filtros
        if (!empty($filters['search'])) {
            $query->where(function($q) use ($filters) {
                $q->where('user_name', 'like', '%' . $filters['search'] . '%')
                  ->orWhere('description', 'like', '%' . $filters['search'] . '%');
            });
        }

        if (!empty($filters['action'])) {
            $query->where('action', $filters['action']);
        }

        if (!empty($filters['module'])) {
            $query->where('module', $filters['module']);
        }

        if (!empty($filters['method'])) {
            $query->where('request_method', $filters['method']);
        }

        // Aplicar filtros de fecha
        if (!empty($filters['date_range'])) {
            $this->applyDateRangeFilter($query, $filters['date_range']);
        }

        // Ordenar
        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortOrder = $filters['sort_order'] ?? 'desc';
        $query->orderBy($sortBy, $sortOrder);

        // Obtener todos los datos (sin paginación para exportar)
        $data = $query->get();

        if ($data->isEmpty()) {
            $this->addEmptySheetMessage($sheet, 'No hay logs administrativos para exportar');
            return;
        }

        // Headers
        $headers = [
            'ID',
            'Usuario',
            'Email Usuario',
            'Acción',
            'Módulo',
            'Tipo de Recurso',
            'ID del Recurso',
            'Descripción',
            'Valores Anteriores',
            'Valores Nuevos',
            'Datos Adicionales',
            'IP',
            'User Agent',
            'Método HTTP',
            'URL de la Petición',
            'Fecha de Creación'
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
            $sheet->setCellValue('B' . $row, $item->user_name ?? 'Sistema');
            $sheet->setCellValue('C' . $row, $item->user_email ?? '');
            $sheet->setCellValue('D' . $row, $this->formatAction($item->action));
            $sheet->setCellValue('E' . $row, $this->formatModule($item->module));
            $sheet->setCellValue('F' . $row, $item->resource_type ?? '');
            $sheet->setCellValue('G' . $row, $item->resource_id ?? '');
            $sheet->setCellValue('H' . $row, $item->description ?? '');
            
            // Valores anteriores (JSON formateado)
            $oldValues = '';
            if ($item->old_values) {
                $decoded = json_decode($item->old_values, true);
                if ($decoded) {
                    $oldValues = json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
                }
            }
            $sheet->setCellValue('I' . $row, $oldValues);
            
            // Valores nuevos (JSON formateado)
            $newValues = '';
            if ($item->new_values) {
                $decoded = json_decode($item->new_values, true);
                if ($decoded) {
                    $newValues = json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
                }
            }
            $sheet->setCellValue('J' . $row, $newValues);
            
            // Datos adicionales (JSON formateado)
            $additionalData = '';
            if ($item->additional_data) {
                $decoded = json_decode($item->additional_data, true);
                if ($decoded) {
                    $additionalData = json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
                }
            }
            $sheet->setCellValue('K' . $row, $additionalData);

            $sheet->setCellValue('L' . $row, $item->ip_address ?? '');
            $sheet->setCellValue('M' . $row, $item->user_agent ?? '');
            $sheet->setCellValue('N' . $row, $item->request_method ?? '');
            $sheet->setCellValue('O' . $row, $item->request_url ?? '');
            $sheet->setCellValue('P' . $row, $item->created_at ? $item->created_at->format('d/m/Y H:i:s') : '');

            // Aplicar bordes a las celdas de datos
            $sheet->getStyle('A' . $row . ':P' . $row)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
            
            $row++;
        }

        // Autoajustar columnas
        foreach (range('A', 'P') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // Aplicar estilo alternado a las filas
        for ($i = 2; $i < $row; $i++) {
            if ($i % 2 == 0) {
                $sheet->getStyle('A' . $i . ':P' . $i)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('F9F9F9');
            }
        }
    }

    private function applyDateRangeFilter($query, string $dateRange): void
    {
        $now = Carbon::now();
        
        switch ($dateRange) {
            case 'today':
                $query->whereDate('created_at', $now->toDateString());
                break;
            case 'week':
                $query->whereBetween('created_at', [$now->startOfWeek(), $now->endOfWeek()]);
                break;
            case 'month':
                $query->whereMonth('created_at', $now->month)->whereYear('created_at', $now->year);
                break;
            case 'quarter':
                $query->whereBetween('created_at', [$now->startOfQuarter(), $now->endOfQuarter()]);
                break;
            case 'year':
                $query->whereYear('created_at', $now->year);
                break;
        }
    }

    private function formatAction(string $action): string
    {
        $actions = [
            'create' => 'Crear',
            'update' => 'Actualizar',
            'delete' => 'Eliminar',
            'view' => 'Ver',
            'export' => 'Exportar',
            'login' => 'Iniciar Sesión',
            'logout' => 'Cerrar Sesión',
        ];

        return $actions[$action] ?? ucfirst($action);
    }

    private function formatModule(string $module): string
    {
        return ucwords(str_replace('_', ' ', $module));
    }

    private function addEmptySheetMessage($sheet, string $message): void
    {
        $sheet->setCellValue('A1', $message);
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet->getColumnDimension('A')->setAutoSize(true);
    }
}
