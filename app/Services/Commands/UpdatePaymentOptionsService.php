<?php

namespace App\Services\Commands;

use App\Models\ProgramCourse;
use App\Models\PaymentOption;
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
            'program_courses_processed' => 0,
            'options_updated' => 0,
            'installments_updated' => 0,
            'errors' => []
        ];

        try {
            // Obtener program_courses activos con fecha de salida futura
            $programCourses = ProgramCourse::where('active', true)
                ->where('departure_date', '>', $today)
                ->whereNotNull('final_payment_date')
                ->get();

            $results['program_courses_processed'] = $programCourses->count();

            foreach ($programCourses as $programCourse) {
                try {
                    $this->updateProgramCoursePaymentOptions($programCourse, $today);
                    $results['options_updated']++;
                } catch (\Exception $e) {
                    $error = "Error en program_course ID {$programCourse->id}: " . $e->getMessage();
                    $results['errors'][] = $error;
                    Log::error('Error actualizando opciones de pago del program_course', [
                        'program_course_id' => $programCourse->id,
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
     * Actualizar opciones de pago para un program_course específico
     */
    private function updateProgramCoursePaymentOptions(ProgramCourse $programCourse, Carbon $today): void
    {
        $finalPaymentDate = Carbon::parse($programCourse->final_payment_date);
        
        // Calcular meses disponibles hasta la fecha final de pago
        $availableMonths = $this->calculateAvailableMonths($today, $finalPaymentDate);
        
        // Obtener TODAS las opciones de pago disponibles en el sistema
        $allOptions = PaymentOption::where('active', true)->get();

        // Filtrar opciones válidas según los meses disponibles
        $validOptions = $this->filterValidPaymentOptions($allOptions, $availableMonths);

        // Actualizar opciones de pago si es necesario
        $this->syncProgramCoursePaymentOptions($programCourse, $validOptions);

        // Actualizar número máximo de cuotas si es necesario
        $this->updateMaxInstallments($programCourse, $availableMonths);
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
     * Sincronizar opciones de pago del program_course
     */
    private function syncProgramCoursePaymentOptions(ProgramCourse $programCourse, array $validOptions): void
    {
        // Obtener todas las opciones de pago disponibles en el sistema
        $allPaymentOptions = PaymentOption::where('active', true)->get();

        // Obtener opciones actualmente configuradas para este program_course
        $currentOptions = DB::table('program_course_payment_option')
            ->where('program_course_id', $programCourse->id)
            ->pluck('payment_option_id')
            ->toArray();

        // Crear o actualizar todas las opciones de pago disponibles
        foreach ($allPaymentOptions as $paymentOption) {
            $isValid = in_array($paymentOption->code, $validOptions);
            $exists = in_array($paymentOption->id, $currentOptions);

            if ($exists) {
                // Actualizar estado de opción existente
                DB::table('program_course_payment_option')
                    ->where('program_course_id', $programCourse->id)
                    ->where('payment_option_id', $paymentOption->id)
                    ->update(['enabled' => $isValid, 'updated_at' => now()]);
            } else {
                // Crear nueva opción para el program_course
                DB::table('program_course_payment_option')->insert([
                    'program_course_id' => $programCourse->id,
                    'payment_option_id' => $paymentOption->id,
                    'enabled' => $isValid,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        Log::info('Opciones de pago sincronizadas para program_course', [
            'program_course_id' => $programCourse->id,
            'total_options' => $allPaymentOptions->count(),
            'valid_options' => count($validOptions)
        ]);
    }

    /**
     * Actualizar número máximo de cuotas según los meses disponibles
     */
    private function updateMaxInstallments(ProgramCourse $programCourse, int $availableMonths): void
    {
        // Calcular máximo de cuotas permitido
        $maxInstallments = min(12, $availableMonths);

        // Si no hay meses disponibles, establecer en 1
        if ($maxInstallments <= 0) {
            $maxInstallments = 1;
        }

        // Actualizar subscription_max_months si es diferente al valor actual
        if ($programCourse->subscription_max_months != $maxInstallments) {
            $oldMax = $programCourse->subscription_max_months;
            $programCourse->update(['subscription_max_months' => $maxInstallments]);

            Log::info('Máximo de cuotas actualizado automáticamente', [
                'program_course_id' => $programCourse->id,
                'old_max' => $oldMax,
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

        $total = ProgramCourse::where('active', true)
            ->where('departure_date', '>', $today)
            ->whereNotNull('final_payment_date')
            ->count();

        return [
            'total_program_courses' => $total,
            'date' => $today->format('Y-m-d')
        ];
    }
}
