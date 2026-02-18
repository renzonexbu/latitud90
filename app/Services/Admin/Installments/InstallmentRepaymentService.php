<?php

namespace App\Services\Admin\Installments;

use App\Models\InstallmentPlan;
use App\Models\Installment;
use App\Models\InstallmentRestructure;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class InstallmentRepaymentService
{
    /**
     * Reestructurar cuotas de un plan existente
     * 
     * @param int $installmentPlanId
     * @param int $newTotalInstallments
     * @param string $reason
     * @return array
     * @throws Exception
     */
    public function repactInstallments(
        int $installmentPlanId,
        int $newTotalInstallments,
        string $reason
    ): array {
        DB::beginTransaction();
        
        try {
            $plan = InstallmentPlan::findOrFail($installmentPlanId);
            
            // Obtener cuotas pagadas (que tienen payment_id)
            $paidInstallments = $plan->installments()
                ->whereNotNull('payment_id')
                ->orderBy('installment_number', 'asc')
                ->get();
            
            // Obtener cuotas pendientes (que NO tienen payment_id)
            $pendingInstallments = $plan->installments()
                ->whereNull('payment_id')
                ->orderBy('installment_number', 'asc')
                ->get();

            // Validar que se puede repactar
            if ($newTotalInstallments < $paidInstallments->count()) {
                throw new Exception("No se puede reducir a menos cuotas que las ya pagadas ({$paidInstallments->count()})");
            }

            if ($newTotalInstallments > 24) {
                throw new Exception("El máximo permitido es 24 cuotas");
            }

            // Calcular el monto restante a distribuir (entero, CLP sin centavos)
            $paidAmount = (int) round($paidInstallments->sum('amount'));
            $remainingBalance = (int) round($plan->total_amount - $paidAmount);
            
            if ($remainingBalance <= 0) {
                throw new Exception("No hay monto pendiente para reestructurar");
            }

            // Eliminar cuotas pendientes existentes para liberar los números
            $pendingInstallments->each(function ($installment) {
                $installment->delete();
            });

            // Crear nuevas cuotas
            $newInstallmentsCount = $newTotalInstallments - $paidInstallments->count();
            $newAmounts = $this->splitAmountInInstallments($remainingBalance, $newInstallmentsCount);

            // Crear las nuevas cuotas
            $nextNumber = $paidInstallments->count() + 1;
            for ($i = 0; $i < count($newAmounts); $i++) {
                Installment::create([
                    'installment_plan_id' => $plan->id,
                    'installment_number' => $nextNumber + $i,
                    'amount' => $newAmounts[$i],
                    'due_date' => $this->calculateDueDate($nextNumber + $i, $plan->start_date),
                    'status' => 'pending',
                    'notes' => "Cuota reestructurada: {$reason}",
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            // Actualizar el plan
            $plan->update([
                'total_installments' => $newTotalInstallments,
                'updated_at' => now()
            ]);

            // Registrar la restructuración en la base de datos
            $this->recordRestructure($plan, $reason, $pendingInstallments->count(), $newInstallmentsCount);

            // Log de la reestructuración
            Log::info('Cuotas reestructuradas exitosamente', [
                'installment_plan_id' => $plan->id,
                'old_total_installments' => $plan->getOriginal('total_installments'),
                'new_total_installments' => $newTotalInstallments,
                'reason' => $reason,
                'cuotas_eliminadas' => $pendingInstallments->count(),
                'cuotas_nuevas' => $newInstallmentsCount,
                'monto_restante' => $remainingBalance
            ]);

            DB::commit();

            return [
                'success' => true,
                'plan_id' => $plan->id,
                'old_total_installments' => $plan->getOriginal('total_installments'),
                'new_total_installments' => $newTotalInstallments,
                'cuotas_eliminadas' => $pendingInstallments->count(),
                'cuotas_nuevas' => $newInstallmentsCount,
                'monto_restante' => $remainingBalance
            ];

        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error al reestructurar cuotas: ' . $e->getMessage(), [
                'installment_plan_id' => $installmentPlanId,
                'new_total_installments' => $newTotalInstallments,
                'reason' => $reason,
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

    /**
     * Registrar la restructuración en la base de datos
     * 
     * @param InstallmentPlan $plan
     * @param string $reason
     * @param int $installmentsDeleted
     * @param int $installmentsCreated
     * @return void
     */
    private function recordRestructure(InstallmentPlan $plan, string $reason, int $installmentsDeleted, int $installmentsCreated): void
    {
        $user = Auth::user();
        $oldTotalInstallments = $plan->getOriginal('total_installments');
        $oldPaidInstallments = $plan->installments()->whereNotNull('payment_id')->count();
        $oldPendingInstallments = $oldTotalInstallments - $oldPaidInstallments;
        $oldPaidAmount = $plan->installments()->whereNotNull('payment_id')->sum('amount');
        $oldRemainingBalance = $plan->total_amount - $oldPaidAmount;

        // Obtener datos de las cuotas anteriores (solo las pendientes que se eliminaron)
        $oldInstallmentsData = $plan->installments()
            ->whereNull('payment_id')
            ->get(['installment_number', 'amount', 'due_date', 'status'])
            ->toArray();

        // Obtener datos de las nuevas cuotas
        $newInstallmentsData = $plan->installments()
            ->whereNull('payment_id')
            ->get(['installment_number', 'amount', 'due_date', 'status'])
            ->toArray();

        InstallmentRestructure::create([
            'installment_plan_id' => $plan->id,
            'user_id' => $user?->id,
            'user_name' => $user?->name,
            'user_email' => $user?->email,
            'participant_id' => $plan->participant_id,
            'program_id' => $plan->program_id,
            'old_total_installments' => $oldTotalInstallments,
            'old_total_amount' => $plan->total_amount,
            'old_paid_installments' => $oldPaidInstallments,
            'old_pending_installments' => $oldPendingInstallments,
            'old_paid_amount' => $oldPaidAmount,
            'old_remaining_balance' => $oldRemainingBalance,
            'new_total_installments' => $plan->total_installments,
            'new_total_amount' => $plan->total_amount,
            'new_paid_installments' => $oldPaidInstallments,
            'new_pending_installments' => $plan->total_installments - $oldPaidInstallments,
            'new_paid_amount' => $oldPaidAmount,
            'new_remaining_balance' => $oldRemainingBalance,
            'reason' => $reason,
            'installments_deleted' => $installmentsDeleted,
            'installments_created' => $installmentsCreated,
            'restructure_type' => 'manual',
            'old_installments_data' => $oldInstallmentsData,
            'new_installments_data' => $newInstallmentsData,
            'notes' => "Restructuración manual realizada por {$user?->name}",
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }
}
