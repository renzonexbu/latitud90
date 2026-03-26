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
     *
     * IMPORTANTE:
     * - Pago total (full_*): usa departure_date como referencia
     * - Suscripción (subscription_*): usa final_payment_date como referencia
     */
    private function updateProgramCoursePaymentOptions(ProgramCourse $programCourse, Carbon $today): void
    {
        // Calcular días/meses disponibles para cada tipo de pago
        $departureDate = Carbon::parse($programCourse->departure_date);
        $finalPaymentDate = Carbon::parse($programCourse->final_payment_date);

        // Para pago total (tarjeta de crédito): cuenta por mes calendario
        // Marzo → Agosto = 6 meses (el día exacto no importa, solo el mes)
        $availableMonthsForFullPayment = $this->calculateAvailableMonthsByCalendar($today, $departureDate);

        // Para suscripción (PAT): cuenta por días exactos (cada 30 días)
        $availableMonthsForSubscription = $this->calculateAvailableMonths($today, $finalPaymentDate);

        // Actualizar opciones de pago existentes (NO crear nuevas)
        $this->syncProgramCoursePaymentOptions(
            $programCourse,
            $availableMonthsForFullPayment,
            $availableMonthsForSubscription
        );

        // Actualizar número máximo de cuotas para suscripción
        $this->updateMaxInstallments($programCourse, $availableMonthsForSubscription);
    }
    
    /**
     * Calcular meses disponibles por calendario (para pago total / tarjeta de crédito)
     * Solo cuenta la diferencia en meses, sin importar el día exacto.
     * Ejemplo: Marzo 2026 → Agosto 2026 = 6 meses (incluye mes actual y mes destino)
     */
    private function calculateAvailableMonthsByCalendar(Carbon $today, Carbon $departureDate): int
    {
        if ($departureDate->lt($today)) {
            return 0;
        }

        // Diferencia en meses calendario + 1 (incluye mes actual)
        $diffMonths = ($departureDate->year - $today->year) * 12 + ($departureDate->month - $today->month);

        return $diffMonths + 1;
    }

    /**
     * Calcular cuotas disponibles por días exactos (para suscripción / PAT)
     * Cada cuota = 30 días aproximadamente.
     * +1 porque la primera cuota se paga el día 0 (hoy).
     */
    private function calculateAvailableMonths(Carbon $today, Carbon $finalPaymentDate): int
    {
        $diffDays = $today->diffInDays($finalPaymentDate, false);

        if ($diffDays < 0) {
            return 0;
        }

        return (int) floor($diffDays / 30) + 1;
    }
    
    /**
     * Verificar si una opción de pago es válida según los meses disponibles
     *
     * @param object $option Opción de pago con code e installments
     * @param int $availableMonths Meses disponibles hasta la fecha de referencia
     * @return bool
     */
    private function isPaymentOptionValid(object $option, int $availableMonths): bool
    {
        // Si no tiene cuotas (null) o es 0, siempre está disponible
        if ($option->installments === null || $option->installments === 0) {
            return true;
        }

        // Si tiene cuotas, verificar que no exceda los meses disponibles
        return $option->installments <= $availableMonths;
    }

    /**
     * Sincronizar opciones de pago del program_course
     *
     * IMPORTANTE: Solo actualiza el estado 'enabled' de opciones que YA fueron
     * seleccionadas por el administrador. NO agrega nuevas opciones que nunca
     * fueron configuradas para este programa.
     *
     * @param ProgramCourse $programCourse
     * @param int $availableMonthsForFullPayment Meses hasta departure_date
     * @param int $availableMonthsForSubscription Meses hasta final_payment_date
     */
    private function syncProgramCoursePaymentOptions(
        ProgramCourse $programCourse,
        int $availableMonthsForFullPayment,
        int $availableMonthsForSubscription
    ): void {
        // Obtener SOLO las opciones actualmente configuradas para este program_course
        // (las que el admin seleccionó originalmente)
        $currentOptions = DB::table('program_course_payment_option')
            ->join('payment_options', 'payment_options.id', '=', 'program_course_payment_option.payment_option_id')
            ->where('program_course_payment_option.program_course_id', $programCourse->id)
            ->select(
                'program_course_payment_option.payment_option_id',
                'program_course_payment_option.enabled',
                'payment_options.code',
                'payment_options.installments'
            )
            ->get();

        // Si no hay opciones configuradas, no hay nada que actualizar
        if ($currentOptions->isEmpty()) {
            Log::info('No hay opciones de pago configuradas para actualizar', [
                'program_course_id' => $programCourse->id
            ]);
            return;
        }

        $updatedCount = 0;
        $disabledCount = 0;
        $enabledCount = 0;

        // Solo actualizar el estado de las opciones YA existentes
        foreach ($currentOptions as $option) {
            // Determinar qué fecha usar según el tipo de opción
            if (str_starts_with($option->code, 'full_')) {
                // Pago total: usa departure_date
                $availableMonths = $availableMonthsForFullPayment;
            } elseif (str_starts_with($option->code, 'subscription_')) {
                // Suscripción: usa final_payment_date
                $availableMonths = $availableMonthsForSubscription;
            } else {
                // Otros tipos (presential_, etc.): no se actualizan automáticamente
                continue;
            }

            $isValid = $this->isPaymentOptionValid($option, $availableMonths);
            $wasEnabled = (bool) $option->enabled;

            // Solo actualizar si el estado cambió
            if ($wasEnabled !== $isValid) {
                DB::table('program_course_payment_option')
                    ->where('program_course_id', $programCourse->id)
                    ->where('payment_option_id', $option->payment_option_id)
                    ->update(['enabled' => $isValid, 'updated_at' => now()]);

                $updatedCount++;
                if ($isValid) {
                    $enabledCount++;
                } else {
                    $disabledCount++;
                }

                Log::info('Opción de pago actualizada', [
                    'program_course_id' => $programCourse->id,
                    'option_code' => $option->code,
                    'installments' => $option->installments,
                    'available_months' => $availableMonths,
                    'new_status' => $isValid ? 'enabled' : 'disabled'
                ]);
            }
        }

        if ($updatedCount > 0) {
            Log::info('Resumen de actualización de opciones de pago', [
                'program_course_id' => $programCourse->id,
                'total_configured' => $currentOptions->count(),
                'options_updated' => $updatedCount,
                'options_enabled' => $enabledCount,
                'options_disabled' => $disabledCount,
                'months_to_departure' => $availableMonthsForFullPayment,
                'months_to_final_payment' => $availableMonthsForSubscription
            ]);
        }
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
