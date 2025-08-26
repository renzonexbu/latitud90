<?php

namespace App\Services\Admin\SuperAdmin\SystemLogs;

use App\Models\AdminLog;
use App\Traits\AdminLogging;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportAdminLogsService
{
    use AdminLogging;

    public function execute(Request $request): StreamedResponse
    {
        Log::info('Iniciando exportación de logs', ['request' => $request->all()]);
        
        try {
            // Limpiar cualquier output buffer
            while (ob_get_level()) {
                ob_end_clean();
            }
            
            $query = AdminLog::query();

            // Aplicar los mismos filtros que en la vista
            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('user_name', 'like', "%{$search}%")
                      ->orWhere('user_email', 'like', "%{$search}%")
                      ->orWhere('description', 'like', "%{$search}%");
                });
            }

            if ($request->filled('action')) {
                $query->where('action', $request->action);
            }

            if ($request->filled('module')) {
                $query->where('module', $request->module);
            }

            if ($request->filled('method')) {
                $query->where('request_method', $request->method);
            }

            if ($request->filled('date_range')) {
                $dateRange = $request->date_range;
                $now = now();
                
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

            // Ordenar por fecha más reciente
            $query->orderBy('created_at', 'desc');

            // Obtener todos los logs (sin paginación para exportación)
            $logs = $query->get();
            Log::info('Logs obtenidos', ['count' => $logs->count()]);

            // Preparar datos para exportación
            $data = collect();
            foreach ($logs as $log) {
                $data->push([
                    'ID' => $log->id,
                    'Usuario' => $log->user_name ?? 'N/A',
                    'Email del Usuario' => $log->user_email ?? 'N/A',
                    'Acción' => $this->getActionText($log->action),
                    'Módulo' => $this->capitalizeWords($log->module),
                    'Descripción' => $log->description,
                    'Método HTTP' => $log->request_method ?? 'N/A',
                    'IP' => $log->ip_address ?? 'N/A',
                    'User Agent' => $log->user_agent ?? 'N/A',
                    'Recurso Afectado' => $log->resource_affected ?? 'N/A',
                    'Datos de Cambio' => $this->formatJsonData($log->data_changes),
                    'Datos Adicionales' => $this->formatJsonData($log->additional_data),
                    'Fecha de Creación' => $log->created_at->format('d/m/Y H:i:s')
                ]);
            }

            // Crear el archivo Excel usando el patrón que funciona
            $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
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
            $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
            $writer->setPreCalculateFormulas(false);
            $writer->setIncludeCharts(false);
            
            // Generar nombre del archivo
            $filename = 'logs_sistema_' . now()->format('Y-m-d_H-i-s');
            
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
                "Exportación de logs del sistema generada",
                [
                    'total_logs' => $logs->count(),
                    'filters_applied' => $request->only(['search', 'action', 'module', 'method', 'date_range']),
                    'filename' => $filename . '.xlsx',
                ]
            );

            Log::info('Archivo creado exitosamente', ['filename' => $filename . '.xlsx']);
            
            return $response;

        } catch (\Exception $e) {
            Log::error('Error al exportar logs', ['error' => $e->getMessage()]);
            throw new \RuntimeException('Error al exportar logs: ' . $e->getMessage());
        }
    }

    private function getActionText($action)
    {
        $actions = [
            'create' => 'Crear',
            'update' => 'Actualizar',
            'delete' => 'Eliminar',
            'view' => 'Ver',
            'export' => 'Exportar',
            'status_change' => 'Cambio de Estado',
            'login' => 'Inicio de Sesión',
            'logout' => 'Cierre de Sesión',
        ];

        return $actions[$action] ?? ucfirst($action);
    }

    private function capitalizeWords($text)
    {
        return ucwords(str_replace('_', ' ', $text));
    }

    private function formatJsonData($data)
    {
        if (empty($data)) {
            return 'N/A';
        }
        
        if (is_string($data)) {
            return $data;
        }
        
        try {
            $jsonString = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            return $jsonString ?: 'N/A';
        } catch (\Exception $e) {
            return 'Error al formatear datos';
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
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['rgb' => '4472C4'],
            ],
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    'color' => ['rgb' => '000000'],
                ],
            ],
        ];
        
        $sheet->getStyle('A1:' . $sheet->getHighestColumn() . '1')->applyFromArray($headerStyle);
        
        // Estilo para datos
        $dataStyle = [
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    'color' => ['rgb' => 'CCCCCC'],
                ],
            ],
            'alignment' => [
                'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
            ],
        ];
        
        if ($lastRow > 1) {
            $sheet->getStyle('A2:' . $sheet->getHighestColumn() . $lastRow)->applyFromArray($dataStyle);
        }
    }
}
