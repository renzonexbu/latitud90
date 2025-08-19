<?php

namespace App\Services\Admin\Reports\PartialReport;

use Illuminate\Support\Collection;

class CsvExporter
{
    /**
     * Exporta los datos a formato CSV
     */
    public function export(Collection $data, string $filename): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $rows = $data->toArray();
        $filename = $filename . '.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        $callback = function() use ($rows) {
            $out = fopen('php://output', 'w');
            // Escribir BOM UTF-8
            fwrite($out, "\xEF\xBB\xBF");

            if (!empty($rows)) {
                // Escribir encabezados desde las llaves del primer registro
                fputcsv($out, array_keys($rows[0]));
                // Escribir filas
                foreach ($rows as $r) {
                    // Asegurar strings en UTF-8 y limpiar caracteres mal codificados
                    $clean = array_map(function($v) {
                        if (is_null($v)) return '';
                        if ($v instanceof \DateTimeInterface) return $v->format('Y-m-d H:i:s');
                        if (is_bool($v)) return $v ? '1' : '0';
                        if (is_scalar($v)) {
                            $str = (string)$v;
                            // Limpiar caracteres mal codificados directamente aquí
                            $str = str_replace(
                                ['Ã©', 'Ã³', 'Ã­', 'Ã¡', 'Ãº', 'Ã±', 'Ã', 'Ã', 'Ã', 'Ã', 'Ã', 'Ã'],
                                ['é', 'ó', 'í', 'á', 'ú', 'ñ', 'Á', 'É', 'Í', 'Ó', 'Ú', 'Ñ'],
                                $str
                            );
                            return $str;
                        }
                        return json_encode($v, JSON_UNESCAPED_UNICODE);
                    }, $r);
                    fputcsv($out, $clean);
                }
            }
            fclose($out);
        };

        return response()->stream($callback, 200, $headers);
    }
}
