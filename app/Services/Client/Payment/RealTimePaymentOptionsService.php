<?php

namespace App\Services\Client\Payment;

use App\Models\Program;
use App\Models\PaymentOption;
use App\Models\ProgramPaymentOption;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RealTimePaymentOptionsService
{
    /**
     * Verificar y actualizar opciones de pago en tiempo real para un programa específico
     */
    public function updateProgramPaymentOptionsRealTime(int $programId): array
    {
        try {
            $program = Program::findOrFail($programId);
            $today = Carbon::today()->setTimezone('America/Santiago');
            
            // Verificar si el programa necesita actualización
            if (!$this->needsUpdate($program, $today)) {
                return [
                    'updated' => false,
                    'message' => 'El programa no requiere actualización de opciones de pago',
                    'available_months' => $this->calculateAvailableMonths($today, Carbon::parse($program->final_payment_date))
                ];
            }
            
            // Actualizar opciones de pago
            $this->updateProgramPaymentOptions($program, $today);
            
            // Obtener opciones actualizadas
            $updatedOptions = $this->getUpdatedPaymentOptions($program);
            
            Log::info('Opciones de pago actualizadas en tiempo real', [
                'program_id' => $program->id,
                'program_name' => $program->name,
                'available_months' => $this->calculateAvailableMonths($today, Carbon::parse($program->final_payment_date))
            ]);
            
            return [
                'updated' => true,
                'message' => 'Opciones de pago actualizadas automáticamente',
                'available_months' => $this->calculateAvailableMonths($today, Carbon::parse($program->final_payment_date)),
                'payment_options' => $updatedOptions
            ];
            
        } catch (\Exception $e) {
            Log::error('Error actualizando opciones de pago en tiempo real', [
                'program_id' => $programId,
                'error' => $e->getMessage()
            ]);
            
            return [
                'updated' => false,
                'message' => 'Error actualizando opciones de pago: ' . $e->getMessage(),
                'available_months' => 0
            ];
        }
    }
    
    /**
     * Verificar si un programa necesita actualización de opciones de pago
     */
    private function needsUpdate(Program $program, Carbon $today): bool
    {
        // Solo actualizar si tiene fecha final de pago
        if (!$program->final_payment_date) {
            return false;
        }
        
        $finalPaymentDate = Carbon::parse($program->final_payment_date);
        $availableMonths = $this->calculateAvailableMonths($today, $finalPaymentDate);
        
        // Verificar si hay opciones de pago que ya no son válidas
        $invalidOptions = $this->getInvalidPaymentOptions($program, $availableMonths);
        
        return !empty($invalidOptions);
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
     * Obtener opciones de pago inválidas según los meses disponibles
     */
    private function getInvalidPaymentOptions(Program $program, int $availableMonths): array
    {
        $invalidOptions = [];
        
        $currentOptions = $program->paymentOptions()
            ->wherePivot('enabled', true)
            ->get();
        
        foreach ($currentOptions as $option) {
            // Si tiene cuotas y excede los meses disponibles, es inválida
            if ($option->installments !== null && $option->installments > 0 && $option->installments > $availableMonths) {
                $invalidOptions[] = $option->code;
            }
        }
        
        return $invalidOptions;
    }
    
    /**
     * Actualizar opciones de pago del programa
     */
    private function updateProgramPaymentOptions(Program $program, Carbon $today): void
    {
        $finalPaymentDate = Carbon::parse($program->final_payment_date);
        $availableMonths = $this->calculateAvailableMonths($today, $finalPaymentDate);
        
        // Obtener opciones de pago actuales del programa
        $currentOptions = $program->paymentOptions()
            ->wherePivot('enabled', true)
            ->get();
        
        // Filtrar opciones según los meses disponibles
        $validOptions = $this->filterValidPaymentOptions($currentOptions, $availableMonths);
        
        // Sincronizar opciones de pago
        $this->syncProgramPaymentOptions($program, $validOptions);
        
        // Actualizar número máximo de cuotas si es necesario
        $this->updateMaxInstallments($program, $availableMonths);
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
        // Obtener IDs de las opciones válidas
        $validOptionIds = PaymentOption::whereIn('code', $validOptions)
            ->pluck('id')
            ->toArray();
        
        // Deshabilitar opciones que ya no son válidas
        DB::table('program_payment_option')
            ->where('program_id', $program->id)
            ->whereNotIn('payment_option_id', $validOptionIds)
            ->update(['enabled' => false]);
        
        // Habilitar opciones válidas
        DB::table('program_payment_option')
            ->where('program_id', $program->id)
            ->whereIn('payment_option_id', $validOptionIds)
            ->update(['enabled' => true]);
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
        }
    }
    
    /**
     * Obtener opciones de pago actualizadas del programa
     */
    private function getUpdatedPaymentOptions(Program $program): array
    {
        $options = $program->paymentOptions()
            ->wherePivot('enabled', true)
            ->get()
            ->map(function ($option) {
                return [
                    'code' => $option->code,
                    'label' => $option->label,
                    'mode' => $option->mode,
                    'installments' => $option->installments,
                    'gateway_code' => $option->gateway_code
                ];
            })
            ->toArray();
        
        return $options;
    }
    
    /**
     * Verificar si un programa tiene opciones de pago válidas
     */
    public function hasValidPaymentOptions(int $programId): bool
    {
        try {
            $program = Program::findOrFail($programId);
            
            if (!$program->final_payment_date) {
                return true; // Si no tiene fecha final, considerar válido
            }
            
            $today = Carbon::today()->setTimezone('America/Santiago');
            $finalPaymentDate = Carbon::parse($program->final_payment_date);
            $availableMonths = $this->calculateAvailableMonths($today, $finalPaymentDate);
            
            // Verificar si hay al menos una opción válida
            $validOptions = $program->paymentOptions()
                ->wherePivot('enabled', true)
                ->get()
                ->filter(function ($option) use ($availableMonths) {
                    if ($option->installments === null || $option->installments === 0) {
                        return true;
                    }
                    return $option->installments <= $availableMonths;
                });
            
            return $validOptions->count() > 0;
            
        } catch (\Exception $e) {
            Log::error('Error verificando opciones de pago válidas', [
                'program_id' => $programId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
}
