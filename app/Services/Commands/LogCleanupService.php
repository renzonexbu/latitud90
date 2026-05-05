<?php

namespace App\Services\Commands;

use Illuminate\Support\Facades\Log;

class LogCleanupService
{
    /**
     * Limpiar logs antiguos del sistema
     */
    public function cleanupOldLogs(int $daysOld = 30): array
    {
        $results = [
            'files_deleted' => 0,
            'total_space_freed' => 0,
            'errors' => []
        ];

        try {
            $logPath = storage_path('logs');
            $cutoff = strtotime("-{$daysOld} days");

            if (!is_dir($logPath)) {
                return $results;
            }

            // Recorrido recursivo: captura logs/, logs/bsale/, logs/schedules/, logs/charge_retries/, etc.
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($logPath, \RecursiveDirectoryIterator::SKIP_DOTS)
            );

            foreach ($iterator as $file) {
                if (!$file->isFile()) {
                    continue;
                }
                $ext = strtolower($file->getExtension());
                if (!in_array($ext, ['log', 'txt'], true)) {
                    continue;
                }

                $path = $file->getPathname();
                if (filemtime($path) < $cutoff) {
                    $size = (int) filesize($path);
                    if (@unlink($path)) {
                        $results['files_deleted']++;
                        $results['total_space_freed'] += $size;
                    } else {
                        $results['errors'][] = "No se pudo eliminar: {$path}";
                    }
                }
            }

            Log::info('Logs antiguos limpiados', $results);

        } catch (\Exception $e) {
            Log::error('Error limpiando logs antiguos', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }

        return $results;
    }
}
