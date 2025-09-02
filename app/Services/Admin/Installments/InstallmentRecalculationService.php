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
            $plan = InstallmentPlan::with(['participant', 'program'])->findOrFail($installmentPlanId);
            
            // Calcular el nuevo precio final con descuentos
            $priceData = ParticipantPriceHelper::calculateParticipantPrice($plan->participant, $plan->program);
            $newTotalAmount = $priceData['final_price'];
            
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
            
            // Calcular nuevo saldo pendiente
            $newRemainingBalance = $newTotalAmount - $paidAmount;
            
            if ($newRemainingBalance <= 0) {
                throw new Exception("No hay monto pendiente después del descuento");
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

        // Calcular monto base por cuota
        $baseAmount = floor($totalAmount / $installmentsCount);
        $remainder = $totalAmount - ($baseAmount * $installmentsCount);

        $amounts = [];
        
        for ($i = 0; $i < $installmentsCount; $i++) {
            // Distribuir el resto en las primeras cuotas
            $amount = $baseAmount + ($i < $remainder ? 1 : 0);
            $amounts[] = $amount;
        }

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
