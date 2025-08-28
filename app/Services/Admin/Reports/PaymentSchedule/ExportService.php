<?php

namespace App\Services\Admin\Reports\PaymentSchedule;

use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\Response;

class ExportService
{
    public function __construct(
        private ExcelExporter $excelExporter,
    ) {}

    public function export(Collection $summary, Collection $details, string $filename, string $format = 'xlsx'): Response
    {
        $this->validateExportParams($summary, $details, $filename, $format);
        $uniqueFilename = $this->generateFilename($filename);

        switch (strtolower($format)) {
            case 'xlsx':
            default:
                return $this->excelExporter->export($summary, $details, $uniqueFilename);
        }
    }

    private function validateExportParams(Collection $summary, Collection $details, string $filename, string $format): void
    {
        if ($summary->isEmpty()) {
            throw new \InvalidArgumentException('No hay datos de resumen para exportar');
        }
        if ($details->isEmpty()) {
            throw new \InvalidArgumentException('No hay datos de detalle para exportar');
        }
        if (empty($filename)) {
            throw new \InvalidArgumentException('El nombre del archivo es requerido');
        }
        $validFormats = ['xlsx'];
        if (!in_array(strtolower($format), $validFormats)) {
            throw new \InvalidArgumentException('Formato de archivo no válido. Use: ' . implode(', ', $validFormats));
        }
    }

    public function generateFilename(string $baseFilename): string
    {
        $date = now()->format('Y-m-d_H-i-s');
        return "cronograma_recuperacion_{$baseFilename}_{$date}";
    }
}


