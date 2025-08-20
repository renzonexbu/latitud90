<?php

namespace App\Services\Admin\Reports\ConsolidatedPayments;

use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\Response;

class ExportService
{
    public function __construct(
        private ConsolidatedPaymentsCsvExporter $csvExporter,
        private ConsolidatedPaymentsExcelExporter $excelExporter
    ) {}

    public function export(Collection $data, string $filename, string $format = 'xlsx', array $selectedFields = []): Response
    {
        $this->validateExportParams($data, $filename, $format);
        $uniqueFilename = $this->generateFilename($filename);
        
        switch (strtolower($format)) {
            case 'csv':
                return $this->csvExporter->export($data, $uniqueFilename, $selectedFields);
            case 'xlsx':
            default:
                return $this->excelExporter->export($data, $uniqueFilename, $selectedFields);
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
        // Si el nombre base ya contiene fecha, usarlo tal como viene
        if (str_contains($baseFilename, 'consolidado_pagos_')) {
            return $baseFilename;
        }
        
        // Si no, agregar el prefijo y fecha
        $date = now()->format('Y-m-d_H-i-s');
        return "consolidado_pagos_{$baseFilename}_{$date}";
    }
}
