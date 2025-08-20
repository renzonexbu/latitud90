<?php

namespace App\Services\Admin\Reports\RecoverySchedule;

use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\Response;

class ExportService
{
    public function __construct(
        private CsvExporter $csvExporter,
        private ExcelExporter $excelExporter
    ) {}

    public function export(Collection $data, string $filename, string $format = 'xlsx'): Response
    {
        $this->validateExportParams($data, $filename, $format);
        $uniqueFilename = $this->generateFilename($filename);
        
        switch (strtolower($format)) {
            case 'csv':
                return $this->csvExporter->export($data, $uniqueFilename);
            case 'xlsx':
            default:
                return $this->excelExporter->export($data, $uniqueFilename);
        }
    }

    private function validateExportParams(Collection $data, string $filename, string $format): void
    {
        if ($data->isEmpty()) {
            throw new \InvalidArgumentException('No hay datos para exportar');
        }
        if (empty($filename)) {
            throw new \InvalidArgumentException('El nombre del archivo es requerido');
        }
        $validFormats = ['csv', 'xlsx'];
        if (!in_array(strtolower($format), $validFormats)) {
            throw new \InvalidArgumentException('Formato de archivo no válido. Use: ' . implode(', ', $validFormats));
        }
    }

    private function generateFilename(string $baseFilename): string
    {
        $date = now()->format('Y-m-d_H-i-s');
        return "cronograma_cuotas_{$baseFilename}_{$date}";
    }
}
