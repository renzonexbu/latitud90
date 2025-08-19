<?php

namespace App\Services\Admin\Reports\PartialReport;

use Illuminate\Support\Collection;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Csv as CsvWriter;

class CsvExporter
{
    /**
     * Exporta los datos a formato CSV usando PhpSpreadsheet
     */
    public function export(Collection $data, string $filename): \Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        try {
            // Crear nuevo spreadsheet
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();

            // Obtener headers de la primera fila
            $headers = [];
            if ($data->count() > 0) {
                $headers = array_keys($data->first());
            }

            // Escribir headers en la primera fila
            foreach ($headers as $index => $header) {
                $col = chr(65 + $index); // A, B, C, D...
                $sheet->setCellValue($col . '1', $header);
            }

            // Escribir datos
            foreach ($data as $rowIndex => $rowData) {
                $row = $rowIndex + 2; // Empezar en fila 2
                $colIndex = 0; // Contador numérico para las columnas
                foreach ($rowData as $value) {
                    $col = chr(65 + $colIndex); // A, B, C, D...

                    // Limpiar y validar valores
                    if (is_null($value)) {
                        $value = '';
                    } elseif (is_numeric($value)) {
                        // Verificar si es un código de inscripción (más de 8 dígitos)
                        if (strlen((string) $value) > 8) {
                            // Forzar como texto para evitar notación científica en Excel
                            $value = '="' . (string) $value . '"';
                        } else {
                            $value = (float) $value;
                        }
                    } else {
                        // Limpiar caracteres especiales y asegurar UTF-8
                        $value = mb_convert_encoding((string) $value, 'UTF-8', 'UTF-8');
                    }

                    $sheet->setCellValue($col . $row, $value);
                    $colIndex++; // Incrementar el contador de columnas
                }
            }



            // Crear el writer CSV con configuración UTF-8
            $writer = new CsvWriter($spreadsheet);
            $writer->setDelimiter(';'); // Usar punto y coma para mejor compatibilidad con Excel en español
            $writer->setEnclosure('"');
            $writer->setLineEnding("\r\n");
            $writer->setSheetIndex(0);

            // Configurar para UTF-8
            $writer->setPreCalculateFormulas(false);

            // Configurar BOM UTF-8 para Excel
            $writer->setUseBOM(true);

            // Generar nombre de archivo único - sanitizar caracteres especiales
            $sanitizedFilename = preg_replace('/[^a-zA-Z0-9_-]/', '_', $filename);
            $uniqueFilename = $sanitizedFilename . '.csv';
            $tempPath = storage_path('app/temp/' . $uniqueFilename);

            // Crear directorio si no existe
            if (!file_exists(dirname($tempPath))) {
                mkdir(dirname($tempPath), 0755, true);
            }

            // Guardar archivo
            $writer->save($tempPath);

            // Post-procesamiento para Excel
            $this->optimizeForExcel($tempPath);

            // Verificar que el archivo se creó correctamente
            if (!file_exists($tempPath) || filesize($tempPath) === 0) {
                throw new \Exception('No se pudo crear el archivo CSV');
            }



            // Retornar descarga con headers optimizados para Excel
            return response()->download($tempPath, $uniqueFilename, [
                'Content-Type' => 'text/csv; charset=UTF-8',
                'Content-Disposition' => "attachment; filename=\"$uniqueFilename\"",
                'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
                'Expires' => '0',
                'Pragma' => 'no-cache',
            ])->deleteFileAfterSend();
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Error al exportar CSV: ' . $e->getMessage());

            throw $e;
        }
    }

    /**
     * Optimiza el archivo CSV para que Excel lo lea correctamente
     */
    private function optimizeForExcel(string $filePath): void
    {
        try {
            // Leer el contenido del archivo
            $content = file_get_contents($filePath);

            // Verificar si ya tiene BOM UTF-8
            if (substr($content, 0, 3) !== "\xEF\xBB\xBF") {
                // Agregar BOM UTF-8 al inicio para que Excel detecte la codificación
                $content = "\xEF\xBB\xBF" . $content;

                // Escribir el contenido de vuelta al archivo
                file_put_contents($filePath, $content);
            }

            // Verificar que el archivo se puede leer correctamente
            $testContent = file_get_contents($filePath);
            if (mb_detect_encoding($testContent, 'UTF-8', true) === false) {
                \Illuminate\Support\Facades\Log::warning('El archivo CSV no tiene codificación UTF-8 válida');
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Error al optimizar archivo CSV para Excel', [
                'message' => $e->getMessage(),
                'file_path' => $filePath
            ]);
        }
    }
}
