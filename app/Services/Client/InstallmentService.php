<?php

namespace App\Services\Client;

use App\Models\InstallmentPlan;
use App\Models\Installment;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\Participant;
use App\Models\Program;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class InstallmentService
{
    /**
     * Crear o recuperar un plan de cuotas para un programa/participante
     */
    public function createOrGetInstallmentPlan(int $programId, string $rut, array $paymentData, array $formData): array
    {
        try {
            DB::beginTransaction();

            // Buscar el participante por RUT
            $participant = Participant::where('document_number', $rut)->first();
            if (!$participant) {
                throw new \Exception('Participante no encontrado');
            }

            // Buscar el programa
            $program = Program::with(['course.participants'])->findOrFail($programId);

            // Calcular montos por participante desde pivote y pagos previos
            [$participantTotalAmount, $paidAmount, $participantBalance] = $this->computeParticipantAmounts($program, $participant);
            $finalAmount = $participantBalance;

            // Determinar número total de cuotas
            $totalInstallments = (int) ($paymentData['installments'] ?? ($program->lat90_max_installments ?? 1));
            if ($totalInstallments < 1) { $totalInstallments = 1; }

            // Buscar si ya existe un plan de cuotas activo
            $existingPlan = InstallmentPlan::where('participant_id', $participant->id)
                ->where('program_id', $program->id)
                ->where('status', 'active')
                ->first();

            if ($existingPlan) {
                // Verificar si hay cuotas PAGADAS (no solo pendientes)
                $paidInstallments = $existingPlan->installments()->where('status', 'paid')->count();
                
                if ($paidInstallments > 0) {
                    // Si ya hay cuotas pagadas, usar el plan existente
                    $nextPendingInstallment = $existingPlan->getNextPendingInstallmentAttribute();
                    
                    if ($nextPendingInstallment) {
                        Log::info('Existing installment plan found with paid installments, continuing with next pending', [
                            'plan_id' => $existingPlan->id,
                            'paid_installments' => $paidInstallments,
                            'next_installment' => $nextPendingInstallment->installment_number,
                            'amount' => $nextPendingInstallment->amount
                        ]);

                        DB::commit();
                        return [
                            'success' => true,
                            'installment_plan' => $existingPlan,
                            'next_installment' => $nextPendingInstallment,
                            'is_new_plan' => false
                        ];
                    }
                } else {
                    // Si NO hay cuotas pagadas, verificar si el número de cuotas es diferente
                    $currentTotalInstallments = $existingPlan->total_installments;
                    $requestedInstallments = $totalInstallments;
                    
                    if ($currentTotalInstallments !== $requestedInstallments) {
                        // Si el número de cuotas es diferente, eliminar el plan anterior y crear uno nuevo
                        Log::info('Different number of installments requested, deleting old plan and creating new one', [
                            'old_installments' => $currentTotalInstallments,
                            'new_installments' => $requestedInstallments,
                            'plan_id' => $existingPlan->id
                        ]);
                        
                        // Guardar información del plan anterior antes de eliminarlo
                        $oldPlanId = $existingPlan->id;
                        $oldOrderId = $existingPlan->order_id;
                        
                        // Eliminar el plan anterior y sus cuotas
                        $existingPlan->installments()->delete();
                        
                        // Eliminar la orden asociada al plan anterior (si existe)
                        if ($existingPlan->order) {
                            $existingPlan->order->delete();
                        }
                        
                        $existingPlan->delete();
                        
                        Log::info('Old installment plan deleted due to different number of installments', [
                            'old_plan_id' => $oldPlanId,
                            'old_order_id' => $oldOrderId,
                            'old_installments' => $currentTotalInstallments,
                            'new_installments' => $requestedInstallments
                        ]);
                        
                        // Continuar con la creación del nuevo plan
                    } else {
                        // Si el número de cuotas es el mismo, usar la primera cuota pendiente
                        $firstPendingInstallment = $existingPlan->installments()
                            ->where('installment_number', 1)
                            ->where('status', 'pending')
                            ->first();
                        
                        if ($firstPendingInstallment) {
                            Log::info('Using existing plan with same number of installments, first installment still pending', [
                                'plan_id' => $existingPlan->id,
                                'installment_number' => $firstPendingInstallment->installment_number,
                                'amount' => $firstPendingInstallment->amount
                            ]);

                            DB::commit();
                            return [
                                'success' => true,
                                'installment_plan' => $existingPlan,
                                'next_installment' => $firstPendingInstallment,
                                'is_new_plan' => false
                            ];
                        }
                    }
                }
            }

            // Crear nueva orden para el plan de cuotas
            $order = Order::create([
                'participant_id' => $participant->id,
                'program_id' => $program->id,
                'total_amount' => $participantTotalAmount,
                'discount' => 0,
                'final_amount' => $finalAmount,
                'total_installments' => $totalInstallments,
                'payment_type' => 'monthly',
                'status' => 'pending',
                'order_number' => $this->generateOrderNumber(),
                'notes' => 'Orden creada para plan de cuotas mensuales'
            ]);

            // Crear el plan de cuotas
            $installmentPlan = InstallmentPlan::create([
                'order_id' => $order->id,
                'program_id' => $program->id,
                'participant_id' => $participant->id,
                'total_amount' => $finalAmount,
                'total_installments' => $totalInstallments,
                'payment_type' => 'monthly',
                'status' => 'active',
                'start_date' => now(),
                'notes' => 'Plan de cuotas mensuales creado desde el flujo de pago'
            ]);

            // Crear las cuotas individuales
            $amounts = $this->splitAmountInInstallments($finalAmount, $totalInstallments);
            $dueDates = $this->generateMonthlyDueDates($program, $totalInstallments);
            
            for ($i = 1; $i <= $totalInstallments; $i++) {
                Installment::create([
                    'installment_plan_id' => $installmentPlan->id,
                    'installment_number' => $i,
                    'amount' => $amounts[$i - 1],
                    'due_date' => $dueDates[$i - 1],
                    'status' => 'pending',
                    'notes' => "Cuota {$i} de {$totalInstallments}"
                ]);
            }

            // Obtener la primera cuota para el pago inicial
            $firstInstallment = $installmentPlan->installments()->where('installment_number', 1)->first();

            Log::info('New installment plan created successfully', [
                'plan_id' => $installmentPlan->id,
                'order_id' => $order->id,
                'total_installments' => $totalInstallments,
                'total_amount' => $finalAmount,
                'is_replacement' => $existingPlan ? 'yes' : 'no'
            ]);

            DB::commit();

            return [
                'success' => true,
                'installment_plan' => $installmentPlan,
                'next_installment' => $firstInstallment,
                'is_new_plan' => true,
                'was_replacement' => isset($oldPlanId)
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error creating installment plan', [
                'error' => $e->getMessage(),
                'program_id' => $programId,
                'rut' => $rut
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Marcar una cuota como pagada y vincularla con la orden de pago
     */
    public function markInstallmentAsPaid(Installment $installment, int $orderId, int $orderDetailId, int $paymentId): bool
    {
        try {
            $installment->markAsPaid($orderId, $orderDetailId, $paymentId);
            
            // Verificar si el plan está completo
            $installmentPlan = $installment->installmentPlan;
            if ($installmentPlan->isCompleted()) {
                $installmentPlan->update([
                    'status' => 'completed',
                    'end_date' => now()
                ]);
                
                Log::info('Installment plan completed', [
                    'plan_id' => $installmentPlan->id,
                    'total_installments' => $installmentPlan->total_installments
                ]);
            }

            return true;
        } catch (\Exception $e) {
            Log::error('Error marking installment as paid', [
                'error' => $e->getMessage(),
                'installment_id' => $installment->id
            ]);
            return false;
        }
    }

    /**
     * Obtener la siguiente cuota pendiente de un plan
     */
    public function getNextPendingInstallment(int $installmentPlanId)
    {
        return Installment::where('installment_plan_id', $installmentPlanId)
            ->where('status', 'pending')
            ->orderBy('due_date')
            ->first();
    }

    /**
     * Calcular montos por participante
     */
    private function computeParticipantAmounts(Program $program, Participant $participant): array
    {
        // Implementar lógica de cálculo de montos
        // Por ahora retornar valores básicos
        $totalAmount = 550000; // Monto del programa
        $paidAmount = 0; // Monto ya pagado
        $balance = $totalAmount - $paidAmount;
        
        return [$totalAmount, $paidAmount, $balance];
    }

    /**
     * Dividir monto en cuotas
     */
    private function splitAmountInInstallments(float $total, int $installments): array
    {
        $base = floor(($total / $installments) * 100) / 100;
        $amounts = array_fill(0, $installments, $base);
        $allocated = $base * $installments;
        $remainder = round($total - $allocated, 2);

        $i = 0;
        while ($remainder > 0 && $i < $installments) {
            $amounts[$i] = round($amounts[$i] + 0.01, 2);
            $remainder = round($remainder - 0.01, 2);
            $i++;
        }
        return $amounts;
    }

    /**
     * Generar fechas de vencimiento mensuales
     */
    private function generateMonthlyDueDates(Program $program, int $installments): array
    {
        $dates = [];
        $base = now();
        $baseDay = $base->day;
        
        for ($i = 0; $i < $installments; $i++) {
            $month = $base->copy()->addMonthsNoOverflow($i);
            $dates[] = $month->copy()->day(min($baseDay, $month->daysInMonth));
        }
        return $dates;
    }

    /**
     * Generar número de orden único
     */
    private function generateOrderNumber(): string
    {
        $prefix = 'ORD';
        $year = date('Y');
        $month = date('m');
        
        // Obtener el último número de secuencia usado en este mes
        $lastOrder = Order::where('order_number', 'like', "{$prefix}-{$year}{$month}-%")
                         ->orderBy('order_number', 'desc')
                         ->first();
        
        if ($lastOrder) {
            // Extraer el número de secuencia del último order_number
            $parts = explode('-', $lastOrder->order_number);
            $lastSequence = (int) end($parts);
            $sequence = $lastSequence + 1;
        } else {
            $sequence = 1;
        }
        
        return sprintf('%s-%s%s-%06d', $prefix, $year, $month, $sequence);
    }
}
