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
            $files = glob($logPath . '/*.log');
            
            foreach ($files as $file) {
                if (filemtime($file) < strtotime("-{$daysOld} days")) {
                    $fileSize = filesize($file);
                    
                    if (unlink($file)) {
                        $results['files_deleted']++;
                        $results['total_space_freed'] += $fileSize;
                    } else {
                        $results['errors'][] = "No se pudo eliminar: {$file}";
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
