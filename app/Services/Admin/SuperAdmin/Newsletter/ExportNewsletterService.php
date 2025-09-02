<?php

namespace App\Services\Admin\SuperAdmin\Newsletter;

use App\Models\Newsletter;
use App\Traits\AdminLogging;
use App\Traits\SystemLogging;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportNewsletterService
{
    use AdminLogging, SystemLogging;

    public function execute(Request $request): StreamedResponse
    {
        $this->logOperationStart('Exportación de newsletters', ['request' => $request->all()]);
        
        try {
            // Limpiar cualquier output buffer
            while (ob_get_level()) {
                ob_end_clean();
            }
            
            $query = Newsletter::query();

            // Aplicar los mismos filtros que en la vista
            if ($request->filled('search')) {
                $search = $request->search;
                $query->where('email', 'like', "%{$search}%");
            }

            if ($request->filled('status')) {
                $status = $request->status;
                if ($status === 'active') {
                    $query->where('is_active', true);
                } elseif ($status === 'inactive') {
                    $query->where('is_active', false);
                }
            }

            // Ordenar por fecha de suscripción más reciente
            $query->orderBy('subscribed_at', 'desc');

            // Obtener todos los newsletters (sin paginación para exportación)
            $newsletters = $query->get();
            $this->logInfo('Newsletters obtenidos para exportación', ['count' => $newsletters->count()]);

            // Preparar datos para exportación
            $data = collect();
            foreach ($newsletters as $newsletter) {
                $data->push([
                    'ID' => $newsletter->id,
                    'Email' => $newsletter->email,
                    'Estado' => $newsletter->is_active ? 'Activo' : 'Inactivo',
                    'Fecha de Suscripción' => $newsletter->subscribed_at ? $newsletter->subscribed_at->format('d/m/Y H:i:s') : 'N/A',
                    'Fecha de Creación' => $newsletter->created_at->format('d/m/Y H:i:s'),
                    'Última Actualización' => $newsletter->updated_at->format('d/m/Y H:i:s')
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
            $filename = 'newsletters_' . now()->format('Y-m-d_H-i-s');
            
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
                "Exportación de newsletters generada",
                [
                    'total_newsletters' => $newsletters->count(),
                    'filters_applied' => $request->only(['search', 'status']),
                    'filename' => $filename . '.xlsx',
                ]
            );

            $this->logOperationSuccess('Exportación de newsletters', ['filename' => $filename . '.xlsx']);
            
            return $response;

        } catch (\Exception $e) {
            $this->logOperationFailure('Exportación de newsletters', $e->getMessage());
            throw new \RuntimeException('Error al exportar newsletters: ' . $e->getMessage());
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
