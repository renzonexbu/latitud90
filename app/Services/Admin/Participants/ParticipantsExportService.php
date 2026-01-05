<?php

namespace App\Services\Admin\Participants;

use App\Models\Participant;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Writer\Csv;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use Illuminate\Support\Facades\Log;

class ParticipantsExportService
{
    /**
     * Exportar participantes a Excel o CSV
     *
     * @param string $status 'all', 'active', 'inactive'
     * @param string $format 'xlsx' o 'csv'
     * @return array
     */
    public function export(string $status = 'all', string $format = 'xlsx'): array
    {
        try {
            // Obtener los participantes según el filtro
            $participants = $this->getParticipants($status);

            // Crear el spreadsheet
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();

            // Configurar el título de la hoja
            $sheetTitle = $this->getSheetTitle($status);
            $sheet->setTitle(substr($sheetTitle, 0, 31)); // Límite de Excel

            // Definir encabezados
            $headers = [
                'ID',
                'Primer Nombre',
                'Segundo Nombre',
                'Primer Apellido',
                'Segundo Apellido',
                'Nombre Completo',
                'Email',
                'Código Teléfono',
                'Teléfono',
                'Tipo Documento',
                'Número Documento',
                'País',
                'Fecha Nacimiento',
                'Nacionalidad',
                'Género',
                'Dirección',
                'Restricciones Dietéticas',
                'Intolerancias',
                'Alergias',
                'Estado',
                'Activo',
                'Fecha Registro',
            ];

            // Escribir encabezados
            $col = 'A';
            foreach ($headers as $header) {
                $sheet->setCellValue($col . '1', $header);
                $col++;
            }

            // Estilo para encabezados (solo si es Excel)
            if ($format === 'xlsx') {
                $headerStyle = [
                    'font' => [
                        'bold' => true,
                        'color' => ['rgb' => 'FFFFFF'],
                    ],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => '007E93'],
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                    ],
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                        ],
                    ],
                ];
                $sheet->getStyle('A1:' . $col . '1')->applyFromArray($headerStyle);
            }

            // Escribir datos
            $row = 2;
            foreach ($participants as $participant) {
                $sheet->setCellValue('A' . $row, $participant->id);
                $sheet->setCellValue('B' . $row, $participant->first_name ?? '');
                $sheet->setCellValue('C' . $row, $participant->second_name ?? '');
                $sheet->setCellValue('D' . $row, $participant->first_last_name ?? '');
                $sheet->setCellValue('E' . $row, $participant->second_last_name ?? '');
                $sheet->setCellValue('F' . $row, $participant->full_name ?? '');
                $sheet->setCellValue('G' . $row, $participant->email ?? '');
                $sheet->setCellValue('H' . $row, $participant->code_phone ?? '');
                $sheet->setCellValue('I' . $row, $participant->phone ?? '');
                $sheet->setCellValue('J' . $row, $participant->document_type ?? '');
                $sheet->setCellValue('K' . $row, $participant->document_number ?? '');
                $sheet->setCellValue('L' . $row, $participant->country ?? '');
                $sheet->setCellValue('M' . $row, $participant->birth_date ? $participant->birth_date->format('d/m/Y') : '');
                $sheet->setCellValue('N' . $row, $participant->nationality ?? '');
                $sheet->setCellValue('O' . $row, $participant->gender ?? '');
                $sheet->setCellValue('P' . $row, $participant->address ?? '');
                $sheet->setCellValue('Q' . $row, $participant->dietary_restrictions ?? '');
                $sheet->setCellValue('R' . $row, $participant->intolerances ?? '');
                $sheet->setCellValue('S' . $row, $participant->allergies ?? '');
                $sheet->setCellValue('T' . $row, $participant->getStatusLabel());
                $sheet->setCellValue('U' . $row, $participant->is_active ? 'Sí' : 'No');
                $sheet->setCellValue('V' . $row, $participant->registration_date ? $participant->registration_date->format('d/m/Y H:i') : '');

                $row++;
            }

            // Ajustar ancho de columnas automáticamente (solo para Excel)
            if ($format === 'xlsx') {
                foreach (range('A', 'V') as $columnID) {
                    $sheet->getColumnDimension($columnID)->setAutoSize(true);
                }
            }

            // Generar el archivo
            $fileName = $this->getFileName($status, $format);
            $filePath = storage_path('app/exports/' . $fileName);

            // Crear directorio si no existe
            if (!file_exists(storage_path('app/exports'))) {
                mkdir(storage_path('app/exports'), 0755, true);
            }

            // Escribir el archivo según el formato
            if ($format === 'csv') {
                $writer = new Csv($spreadsheet);
                $writer->setDelimiter(';');
                $writer->setEnclosure('"');
                $writer->setLineEnding("\r\n");
                $writer->setSheetIndex(0);
            } else {
                $writer = new Xlsx($spreadsheet);
            }

            $writer->save($filePath);

            Log::info('Exportación de participantes generada', [
                'status' => $status,
                'format' => $format,
                'total_records' => $participants->count(),
                'file_path' => $filePath
            ]);

            return [
                'success' => true,
                'file_path' => $filePath,
                'file_name' => $fileName,
                'total_records' => $participants->count()
            ];
        } catch (\Exception $e) {
            Log::error('Error al exportar participantes', [
                'status' => $status,
                'format' => $format,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            throw $e;
        }
    }

    /**
     * Obtener participantes según el filtro de estado
     *
     * @param string $status
     * @return \Illuminate\Database\Eloquent\Collection
     */
    private function getParticipants(string $status)
    {
        $query = Participant::query();

        switch ($status) {
            case 'active':
                $query->where('is_active', true);
                break;
            case 'inactive':
                $query->where('is_active', false);
                break;
            case 'all':
            default:
                // No aplicar filtro, obtener todos
                break;
        }

        return $query->orderBy('created_at', 'desc')->get();
    }

    /**
     * Obtener título de la hoja según el estado
     *
     * @param string $status
     * @return string
     */
    private function getSheetTitle(string $status): string
    {
        switch ($status) {
            case 'active':
                return 'Participantes Activos';
            case 'inactive':
                return 'Participantes Inactivos';
            case 'all':
            default:
                return 'Todos los Participantes';
        }
    }

    /**
     * Obtener nombre del archivo según el estado y formato
     *
     * @param string $status
     * @param string $format
     * @return string
     */
    private function getFileName(string $status, string $format): string
    {
        $statusText = match ($status) {
            'active' => 'activos',
            'inactive' => 'inactivos',
            default => 'todos',
        };

        $timestamp = now()->format('Y-m-d_His');
        return "participantes_{$statusText}_{$timestamp}.{$format}";
    }
}
