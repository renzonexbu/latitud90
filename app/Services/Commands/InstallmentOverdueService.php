<?php

namespace App\Services\Commands;

use App\Models\Installment;
use App\Models\OrderDetail;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class InstallmentOverdueService
{
    /**
     * Verificar y actualizar cuotas vencidas
     */
    public function checkAndUpdateOverdue(): array
    {
        $today = Carbon::today()->setTimezone('America/Santiago');
        $results = [
            'date' => $today->format('Y-m-d'),
            'installments_updated' => 0,
            'order_details_updated' => 0,
            'total_updated' => 0
        ];
        
        try {
            // Procesar tabla installments
            $results['installments_updated'] = $this->updateInstallmentsTable($today);
            
            // Procesar tabla orders_detail
            $results['order_details_updated'] = $this->updateOrderDetailsTable($today);
            
            $results['total_updated'] = $results['installments_updated'] + $results['order_details_updated'];
            
            Log::info('Cuotas vencidas actualizadas', $results);
            
        } catch (\Exception $e) {
            Log::error('Error actualizando cuotas vencidas', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
        
        return $results;
    }
    
    /**
     * Actualizar cuotas vencidas en tabla installments
     */
    private function updateInstallmentsTable(Carbon $today): int
    {
        return Installment::where('status', 'pending')
            ->where('due_date', '<', $today)
            ->update([
                'status' => 'overdue',
                'updated_at' => now()->setTimezone('America/Santiago')
            ]);
    }
    
    /**
     * Actualizar cuotas vencidas en tabla orders_detail
     */
    private function updateOrderDetailsTable(Carbon $today): int
    {
        return OrderDetail::where('status', 'pending')
            ->where('due_date', '<', $today)
            ->where('is_paid', false)
            ->update([
                'status' => 'overdue',
                'updated_at' => now()->setTimezone('America/Santiago')
            ]);
    }
}
