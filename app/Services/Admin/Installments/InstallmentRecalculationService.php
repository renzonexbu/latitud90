<?php

namespace App\Services\Admin\Installments;

use App\Models\InstallmentPlan;
use App\Models\Installment;
use App\Helpers\ParticipantPriceHelper;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class InstallmentRecalculationService
{
    /**
     * Recalcular cuotas cuando se aplica un descuento
     * 
     * @param int $installmentPlanId
     * @return array
     * @throws Exception
     */
    public function recalculateInstallmentsAfterDiscount(int $installmentPlanId): array
    {
        DB::beginTransaction();
        
        try {
            $plan = InstallmentPlan::with(['participant', 'programCourse'])->findOrFail($installmentPlanId);

            // program_id del plan apunta a program_courses.id (no a programs.id),
            // por eso usamos la relación programCourse en vez de program.
            $programCourse = $plan->programCourse;
            if (!$programCourse) {
                throw new Exception("No se encontró el program_course asociado al plan de cuotas (program_id: {$plan->program_id})");
            }

            // Calcular el nuevo precio final con descuentos (entero, CLP sin centavos)
            $priceData = ParticipantPriceHelper::calculateParticipantPrice($plan->participant, $programCourse);
            $newTotalAmount = (int) round($priceData['final_price']);
            
            // Obtener cuotas pagadas
            $paidInstallments = $plan->installments()
                ->whereNotNull('payment_id')
                ->orderBy('installment_number', 'asc')
                ->get();
            
            // Obtener cuotas pendientes
            $pendingInstallments = $plan->installments()
                ->whereNull('payment_id')
                ->orderBy('installment_number', 'asc')
                ->get();
            
            // Calcular monto pagado
            $paidAmount = $paidInstallments->sum('amount');
            
            // Calcular nuevo saldo pendiente (entero)
            $newRemainingBalance = (int) round($newTotalAmount - $paidAmount);

            // CASO EXCEDENTE: el participante ya pagó igual o más que el nuevo precio
            // (ej: PAT cancelado + descuento liberado que baja el precio bajo lo ya abonado).
            // No se lanza excepción: se cancelan las cuotas pendientes, se actualiza el total
            // y el excedente queda reflejado en los reportes (saldo a favor = pagado - precio).
            if ($newRemainingBalance <= 0) {
                $pendingInstallments->each(function ($installment) {
                    $installment->update([
                        'status' => 'cancelled',
                        'notes' => 'Cancelada: el abono ya cubre el precio con descuento (saldo a favor)',
                        'updated_at' => now(),
                    ]);
                });

                $plan->update([
                    'total_amount' => $newTotalAmount,
                    'updated_at' => now(),
                ]);

                DB::commit();

                Log::info('Recálculo con saldo a favor (excedente)', [
                    'installment_plan_id' => $plan->id,
                    'new_total_amount' => $newTotalAmount,
                    'paid_amount' => $paidAmount,
                    'excedente' => $paidAmount - $newTotalAmount,
                ]);

                return [
                    'success' => true,
                    'plan_id' => $plan->id,
                    'new_total_amount' => $newTotalAmount,
                    'paid_amount' => $paidAmount,
                    'new_remaining_balance' => 0,
                    'excedente' => $paidAmount - $newTotalAmount,
                    'cuotas_eliminadas' => $pendingInstallments->count(),
                    'cuotas_nuevas' => 0,
                    'has_credit_balance' => true,
                ];
            }

            // Eliminar cuotas pendientes existentes
            $pendingInstallments->each(function ($installment) {
                $installment->delete();
            });
            
            // Calcular nuevas cuotas pendientes
            $newInstallmentsCount = $pendingInstallments->count();
            $newAmounts = $this->splitAmountInInstallments($newRemainingBalance, $newInstallmentsCount);
            
            // Crear nuevas cuotas pendientes
            $nextNumber = $paidInstallments->count() + 1;
            for ($i = 0; $i < count($newAmounts); $i++) {
                Installment::create([
                    'installment_plan_id' => $plan->id,
                    'installment_number' => $nextNumber + $i,
                    'amount' => $newAmounts[$i],
                    'due_date' => $this->calculateDueDate($nextNumber + $i, $plan->start_date),
                    'status' => 'pending',
                    'notes' => "Cuota recalculada después de aplicar descuento",
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
            
            // Actualizar el plan con el nuevo monto total
            $plan->update([
                'total_amount' => $newTotalAmount,
                'updated_at' => now()
            ]);
            
            // Log de la recalculación
            Log::info('Cuotas recalculadas después de aplicar descuento', [
                'installment_plan_id' => $plan->id,
                'old_total_amount' => $plan->getOriginal('total_amount'),
                'new_total_amount' => $newTotalAmount,
                'paid_amount' => $paidAmount,
                'new_remaining_balance' => $newRemainingBalance,
                'cuotas_eliminadas' => $pendingInstallments->count(),
                'cuotas_nuevas' => $newInstallmentsCount,
                'discount_applied' => $plan->getOriginal('total_amount') - $newTotalAmount
            ]);
            
            DB::commit();
            
            return [
                'success' => true,
                'plan_id' => $plan->id,
                'old_total_amount' => $plan->getOriginal('total_amount'),
                'new_total_amount' => $newTotalAmount,
                'paid_amount' => $paidAmount,
                'new_remaining_balance' => $newRemainingBalance,
                'cuotas_eliminadas' => $pendingInstallments->count(),
                'cuotas_nuevas' => $newInstallmentsCount,
                'discount_applied' => $plan->getOriginal('total_amount') - $newTotalAmount
            ];
            
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error al recalcular cuotas después del descuento: ' . $e->getMessage(), [
                'installment_plan_id' => $installmentPlanId,
                'trace' => $e->getTraceAsString()
            ]);
            
            throw $e;
        }
    }
    
    /**
     * Dividir un monto en cuotas
     * 
     * @param float $totalAmount
     * @param int $installmentsCount
     * @return array
     */
    private function splitAmountInInstallments(float $totalAmount, int $installmentsCount): array
    {
        if ($installmentsCount <= 0) {
            return [];
        }

        // Calcular monto base por cuota: solo .6+ hacia arriba (1-5 abajo, 6-9 arriba)
        $totalAmount = (int) round($totalAmount);
        $baseAmount = (int) floor(($totalAmount / $installmentsCount) + 0.4);

        // Última cuota absorbe el residuo
        $allocated = $baseAmount * ($installmentsCount - 1);
        $lastAmount = $totalAmount - $allocated;

        // Primeras n-1 cuotas iguales, última absorbe residuo
        $amounts = [];
        for ($i = 0; $i < $installmentsCount - 1; $i++) {
            $amounts[] = $baseAmount;
        }
        $amounts[] = $lastAmount;

        return $amounts;
    }

    /**
     * Calcular fecha de vencimiento para una cuota
     * 
     * @param int $installmentNumber
     * @param string $startDate
     * @return string
     */
    private function calculateDueDate(int $installmentNumber, string $startDate): string
    {
        // Asumimos cuotas mensuales, empezando desde la fecha de inicio
        $monthsToAdd = $installmentNumber - 1; // La primera cuota vence en la fecha de inicio
        
        $date = new \DateTime($startDate);
        $date->add(new \DateInterval("P{$monthsToAdd}M"));
        
        return $date->format('Y-m-d');
    }
}
