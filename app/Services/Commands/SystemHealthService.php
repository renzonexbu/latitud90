<?php

namespace App\Services\Commands;

use App\Models\Installment;
use App\Models\OrderDetail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SystemHealthService
{
    /**
     * Verificar estado general del sistema
     */
    public function checkSystemHealth(): array
    {
        $results = [
            'database_status' => 'unknown',
            'disk_usage_percent' => 0,
            'overdue_installments_count' => 0,
            'total_installments_count' => 0,
            'errors' => []
        ];
        
        try {
            // Verificar conexión a base de datos
            $results['database_status'] = $this->checkDatabaseConnection();
            
            // Verificar espacio en disco
            $results['disk_usage_percent'] = $this->checkDiskUsage();
            
            // Verificar cuotas vencidas
            $results['overdue_installments_count'] = $this->getOverdueInstallmentsCount();
            
            // Verificar total de cuotas
            $results['total_installments_count'] = $this->getTotalInstallmentsCount();
            
            Log::info('Verificación de salud del sistema completada', $results);
            
        } catch (\Exception $e) {
            Log::error('Error verificando salud del sistema', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            $results['errors'][] = $e->getMessage();
        }
        
        return $results;
    }
    
    /**
     * Verificar conexión a base de datos
     */
    private function checkDatabaseConnection(): string
    {
        try {
            DB::connection()->getPdo();
            return 'connected';
        } catch (\Exception $e) {
            return 'error';
        }
    }
    
    /**
     * Verificar uso de disco
     */
    private function checkDiskUsage(): float
    {
        $freeSpace = disk_free_space(storage_path());
        $totalSpace = disk_total_space(storage_path());
        $usedSpace = $totalSpace - $freeSpace;
        
        return round(($usedSpace / $totalSpace) * 100, 2);
    }
    
    /**
     * Obtener conteo de cuotas vencidas
     */
    private function getOverdueInstallmentsCount(): int
    {
        return Installment::where('status', 'overdue')->count() +
               OrderDetail::where('status', 'overdue')->count();
    }
    
    /**
     * Obtener conteo total de cuotas
     */
    private function getTotalInstallmentsCount(): int
    {
        return Installment::count() + OrderDetail::count();
    }
}
