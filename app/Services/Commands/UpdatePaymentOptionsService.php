<?php

namespace App\Services\Commands;

use App\Models\Program;
use App\Models\PaymentOption;
use App\Models\ProgramPaymentOption;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class UpdatePaymentOptionsService
{
    /**
     * Actualizar automáticamente las opciones de pago y cuotas según el tiempo transcurrido
     */
    public function updatePaymentOptions(): array
    {
        $today = Carbon::today()->setTimezone('America/Santiago');
        $results = [
            'date' => $today->format('Y-m-d'),
            'programs_processed' => 0,
            'options_updated' => 0,
            'installments_updated' => 0,
            'errors' => []
        ];
        
        try {
            // Obtener programas activos con fecha de salida futura
            $programs = Program::where('active', true)
                ->where('departure_date', '>', $today)
                ->whereNotNull('final_payment_date')
                ->get();
            
            $results['programs_processed'] = $programs->count();
            
            foreach ($programs as $program) {
                try {
                    $this->updateProgramPaymentOptions($program, $today);
                    $results['options_updated']++;
                } catch (\Exception $e) {
                    $error = "Error en programa ID {$program->id}: " . $e->getMessage();
                    $results['errors'][] = $error;
                    Log::error('Error actualizando opciones de pago del programa', [
                        'program_id' => $program->id,
                        'error' => $e->getMessage()
                    ]);
                }
            }
            
            Log::info('Opciones de pago actualizadas automáticamente', $results);
            
        } catch (\Exception $e) {
            Log::error('Error en UpdatePaymentOptionsService', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
        
        return $results;
    }
    
    /**
     * Actualizar opciones de pago para un programa específico
     */
    private function updateProgramPaymentOptions(Program $program, Carbon $today): void
    {
        $finalPaymentDate = Carbon::parse($program->final_payment_date);
        
        // Calcular meses disponibles hasta la fecha final de pago
        $availableMonths = $this->calculateAvailableMonths($today, $finalPaymentDate);
        
        // Obtener TODAS las opciones de pago disponibles en el sistema
        $allOptions = PaymentOption::where('active', true)->get();
        
        // Filtrar opciones válidas según los meses disponibles
        $validOptions = $this->filterValidPaymentOptions($allOptions, $availableMonths);
        
        // Actualizar opciones de pago si es necesario
        $this->syncProgramPaymentOptions($program, $validOptions);
        
        // Actualizar número máximo de cuotas si es necesario
        $this->updateMaxInstallments($program, $availableMonths);
    }
    
    /**
     * Calcular meses disponibles hasta la fecha final de pago
     */
    private function calculateAvailableMonths(Carbon $today, Carbon $finalPaymentDate): int
    {
        $months = ($finalPaymentDate->year - $today->year) * 12 + ($finalPaymentDate->month - $today->month);
        
        // Si el día del mes de hoy es mayor al de la fecha final, restar un mes (mes incompleto)
        if ($today->day > $finalPaymentDate->day) {
            $months -= 1;
        }
        
        return max(0, $months);
    }
    
    /**
     * Filtrar opciones de pago válidas según los meses disponibles
     */
    private function filterValidPaymentOptions($options, int $availableMonths): array
    {
        $validOptions = [];
        
        foreach ($options as $option) {
            // Si no tiene cuotas (null) o es 0, siempre está disponible
            if ($option->installments === null || $option->installments === 0) {
                $validOptions[] = $option->code;
                continue;
            }
            
            // Si tiene cuotas, verificar que no exceda los meses disponibles
            if ($option->installments <= $availableMonths) {
                $validOptions[] = $option->code;
            }
        }
        
        return $validOptions;
    }
    
    /**
     * Sincronizar opciones de pago del programa
     */
    private function syncProgramPaymentOptions(Program $program, array $validOptions): void
    {
        // Obtener todas las opciones de pago disponibles en el sistema
        $allPaymentOptions = PaymentOption::where('active', true)->get();
        
        // Obtener opciones actualmente configuradas para este programa
        $currentProgramOptions = DB::table('program_payment_option')
            ->where('program_id', $program->id)
            ->get(['payment_option_id', 'enabled', 'manually_disabled']);
        
        // Crear o actualizar todas las opciones de pago disponibles
        foreach ($allPaymentOptions as $paymentOption) {
            $isValid = in_array($paymentOption->code, $validOptions);
            $existingOption = $currentProgramOptions->where('payment_option_id', $paymentOption->id)->first();
            
            if ($existingOption) {
                // Si la opción ya existe, verificar si fue deshabilitada manualmente
                $wasManuallyDisabled = $existingOption->manually_disabled;
                
                if ($wasManuallyDisabled) {
                    // Si fue deshabilitada manualmente, NO reactivar automáticamente
                    Log::info('Opción deshabilitada manualmente, no se reactiva automáticamente', [
                        'program_id' => $program->id,
                        'payment_option_id' => $paymentOption->id,
                        'code' => $paymentOption->code,
                        'manually_disabled' => true
                    ]);
                    continue; // Saltar esta opción
                }
                
                // Solo actualizar si no fue deshabilitada manualmente
                if ($existingOption->enabled != $isValid) {
                    DB::table('program_payment_option')
                        ->where('program_id', $program->id)
                        ->where('payment_option_id', $paymentOption->id)
                        ->update(['enabled' => $isValid]);
                    
                    Log::info('Estado de opción actualizado automáticamente', [
                        'program_id' => $program->id,
                        'payment_option_id' => $paymentOption->id,
                        'code' => $paymentOption->code,
                        'old_enabled' => $existingOption->enabled,
                        'new_enabled' => $isValid
                    ]);
                }
            } else {
                // Crear nueva opción para el programa
                DB::table('program_payment_option')->insert([
                    'program_id' => $program->id,
                    'payment_option_id' => $paymentOption->id,
                    'enabled' => $isValid,
                    'manually_disabled' => false,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                
                Log::info('Nueva opción de pago creada para programa', [
                    'program_id' => $program->id,
                    'payment_option_id' => $paymentOption->id,
                    'code' => $paymentOption->code,
                    'enabled' => $isValid
                ]);
            }
        }
        
        Log::info('Opciones de pago sincronizadas para programa', [
            'program_id' => $program->id,
            'total_options' => $allPaymentOptions->count(),
            'valid_options' => count($validOptions),
            'enabled_options' => count(array_filter($validOptions, fn($code) => $allPaymentOptions->where('code', $code)->first()))
        ]);
    }
    
    /**
     * Actualizar número máximo de cuotas según los meses disponibles
     */
    private function updateMaxInstallments(Program $program, int $availableMonths): void
    {
        // Calcular máximo de cuotas permitido
        $maxInstallments = min(12, $availableMonths);
        
        // Si no hay meses disponibles, establecer en 1
        if ($maxInstallments <= 0) {
            $maxInstallments = 1;
        }
        
        // Actualizar solo si es diferente al valor actual
        if ($program->lat90_max_installments != $maxInstallments) {
            $program->update(['lat90_max_installments' => $maxInstallments]);
            
            Log::info('Máximo de cuotas actualizado automáticamente', [
                'program_id' => $program->id,
                'old_max' => $program->lat90_max_installments,
                'new_max' => $maxInstallments,
                'available_months' => $availableMonths
            ]);
        }
    }
    
    /**
     * Obtener estadísticas de actualización
     */
    public function getUpdateStats(): array
    {
        $today = Carbon::today()->setTimezone('America/Santiago');
        
        $totalPrograms = Program::where('active', true)
            ->where('departure_date', '>', $today)
            ->whereNotNull('final_payment_date')
            ->count();
            
        $programsWithOptions = Program::where('active', true)
            ->where('departure_date', '>', $today)
            ->whereNotNull('final_payment_date')
            ->whereHas('paymentOptions', function($query) {
                $query->wherePivot('enabled', true);
            })
            ->count();
            
        return [
            'total_programs' => $totalPrograms,
            'programs_with_options' => $programsWithOptions,
            'date' => $today->format('Y-m-d')
        ];
    }
}
