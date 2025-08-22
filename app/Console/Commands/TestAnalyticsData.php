<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\EcommerceAnalytics;
use Carbon\Carbon;

class TestAnalyticsData extends Command
{
    protected $signature = 'analytics:test-data';
    protected $description = 'Test analytics data to see what is being stored';

    public function handle()
    {
        $this->info('=== TESTING ANALYTICS DATA ===');
        
        // Test 1: Verificar datos básicos
        $this->info('1. Datos básicos de analytics:');
        $totalRecords = EcommerceAnalytics::count();
        $this->line("   - Total de registros: {$totalRecords}");
        
        if ($totalRecords > 0) {
            $latestRecord = EcommerceAnalytics::latest()->first();
            $this->line("   - Último registro ID: {$latestRecord->id}");
            $this->line("   - Session ID: {$latestRecord->session_id}");
            $this->line("   - Participant RUT: {$latestRecord->participant_rut}");
            $this->line("   - Program ID: {$latestRecord->program_id}");
        }
        
        // Test 2: Verificar timestamps del funnel
        $this->info('\n2. Timestamps del funnel:');
        $funnelData = EcommerceAnalytics::select([
            'hero_search_at',
            'program_list_view_at',
            'program_detail_view_at',
            'payment_details_view_at',
            'confirmation_view_at',
            'payment_initiated_at',
            'payment_completed_at',
            'payment_failed_at'
        ])->get();
        
        foreach ($funnelData as $index => $record) {
            $this->line("   - Registro #" . ($index + 1) . ":");
            $this->line("     * Hero Search: " . ($record->hero_search_at ? $record->hero_search_at->format('Y-m-d H:i:s') : 'NULL'));
            $this->line("     * Program List: " . ($record->program_list_view_at ? $record->program_list_view_at->format('Y-m-d H:i:s') : 'NULL'));
            $this->line("     * Program Detail: " . ($record->program_detail_view_at ? $record->program_detail_view_at->format('Y-m-d H:i:s') : 'NULL'));
            $this->line("     * Payment Details: " . ($record->payment_details_view_at ? $record->payment_details_view_at->format('Y-m-d H:i:s') : 'NULL'));
            $this->line("     * Confirmation: " . ($record->confirmation_view_at ? $record->confirmation_view_at->format('Y-m-d H:i:s') : 'NULL'));
            $this->line("     * Payment Initiated: " . ($record->payment_initiated_at ? $record->payment_initiated_at->format('Y-m-d H:i:s') : 'NULL'));
            $this->line("     * Payment Completed: " . ($record->payment_completed_at ? $record->payment_completed_at->format('Y-m-d H:i:s') : 'NULL'));
            $this->line("     * Payment Failed: " . ($record->payment_failed_at ? $record->payment_failed_at->format('Y-m-d H:i:s') : 'NULL'));
        }
        
        // Test 3: Verificar datos de pagos
        $this->info('\n3. Datos de pagos:');
        $paymentData = EcommerceAnalytics::select([
            'payment_method',
            'payment_type',
            'installments_count',
            'payment_status',
            'payment_amount',
            'order_number',
            'order_id',
            'order_detail_id'
        ])->whereNotNull('payment_method')->get();
        
        foreach ($paymentData as $index => $record) {
            $this->line("   - Pago #" . ($index + 1) . ":");
            $this->line("     * Método: {$record->payment_method}");
            $this->line("     * Tipo: " . ($record->payment_type ?? 'NULL'));
            $this->line("     * Cuotas: " . ($record->installments_count ?? 'NULL'));
            $this->line("     * Estado: " . ($record->payment_status ?? 'NULL'));
            $this->line("     * Monto: " . ($record->payment_amount ?? 'NULL'));
            $this->line("     * Order Number: " . ($record->order_number ?? 'NULL'));
            $this->line("     * Order ID: " . ($record->order_id ?? 'NULL'));
            $this->line("     * Order Detail ID: " . ($record->order_detail_id ?? 'NULL'));
        }
        
        // Test 4: Verificar tiempos calculados
        $this->info('\n4. Tiempos calculados:');
        $timeData = EcommerceAnalytics::select([
            'time_to_program_detail',
            'time_to_payment',
            'time_to_completion'
        ])->get();
        
        foreach ($timeData as $index => $record) {
            $this->line("   - Registro #" . ($index + 1) . ":");
            $this->line("     * Time to Program Detail: " . ($record->time_to_program_detail ?? 'NULL') . " segundos");
            $this->line("     * Time to Payment: " . ($record->time_to_payment ?? 'NULL') . " segundos");
            $this->line("     * Time to Completion: " . ($record->time_to_completion ?? 'NULL') . " segundos");
        }
        
        // Test 5: Verificar datos de cuotas
        $this->info('\n5. Datos de cuotas:');
        $installmentData = EcommerceAnalytics::whereNotNull('installments_count')->get();
        
        if ($installmentData->count() > 0) {
            foreach ($installmentData as $index => $record) {
                $this->line("   - Registro #" . ($index + 1) . ":");
                $this->line("     * Cuotas: {$record->installments_count}");
                $this->line("     * Tipo: " . ($record->payment_type ?? 'NULL'));
                $this->line("     * Método: " . ($record->payment_method ?? 'NULL'));
            }
        } else {
            $this->line("   - No hay registros con datos de cuotas");
        }
        
        $this->info('\n=== TEST COMPLETED ===');
    }
}
