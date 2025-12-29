<?php

namespace App\Services\Admin\Subscriptions\ChargeAttempts;

use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CsvExporter
{
    public function export(Collection $data, string $filename): StreamedResponse
    {
        try {
            // Limpiar cualquier output buffer
            while (ob_get_level()) {
                ob_end_clean();
            }

            $response = new StreamedResponse(function () use ($data) {
                $handle = fopen('php://output', 'w');

                // BOM para UTF-8
                fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));

                // Obtener headers del primer registro
                $firstRow = $data->first();
                if (!$firstRow) {
                    throw new \InvalidArgumentException('No hay datos para exportar');
                }

                $headers = array_keys($firstRow);

                // Escribir headers
                fputcsv($handle, $headers, ';');

                // Escribir datos
                foreach ($data as $rowData) {
                    $row = [];
                    foreach ($rowData as $value) {
                        // Convertir valores a string
                        if (is_null($value)) {
                            $value = '';
                        } elseif (is_bool($value)) {
                            $value = $value ? 'Sí' : 'No';
                        } else {
                            $value = (string) $value;
                        }
                        $row[] = $value;
                    }
                    fputcsv($handle, $row, ';');
                }

                fclose($handle);
            });

            // Headers para CSV
            $response->headers->set('Content-Type', 'text/csv; charset=UTF-8');
            $response->headers->set('Content-Disposition', 'attachment; filename="' . $filename . '.csv"');
            $response->headers->set('Cache-Control', 'no-cache, must-revalidate');
            $response->headers->set('Expires', '0');
            $response->headers->set('Pragma', 'public');

            return $response;

        } catch (\Exception $e) {
            throw new \RuntimeException('Error al exportar CSV: ' . $e->getMessage());
        }
    }
}
