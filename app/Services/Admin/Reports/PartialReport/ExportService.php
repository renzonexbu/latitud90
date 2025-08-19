<?php

namespace App\Services\Admin\Reports\PartialReport;

use Illuminate\Support\Collection;

class ExportService
{
    public function __construct(
        private CsvExporter $csvExporter,
        private ExcelExporter $excelExporter
    ) {}

    /**
     * Exporta los datos según el formato especificado
     */
    public function export(Collection $data, string $format, string $filename): \Symfony\Component\HttpFoundation\Response
    {
        return match ($format) {
            'csv' => $this->csvExporter->export($data, $filename),
            'xlsx' => $this->excelExporter->export($data, $filename),
            default => $this->csvExporter->export($data, $filename),
        };
    }

    /**
     * Valida los campos seleccionados para exportación
     */
    public function validateExportFields(array $fields): bool
    {
        if (empty($fields) || !is_array($fields)) {
            return false;
        }
        
        // Validar que al menos hay un campo seleccionado
        foreach ($fields as $category => $categoryFields) {
            if (is_array($categoryFields) && !empty($categoryFields)) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Genera un nombre de archivo único
     */
    public function generateFilename(string $baseName): string
    {
        return $baseName . '_' . date('Y-m-d_H-i-s');
    }
}
