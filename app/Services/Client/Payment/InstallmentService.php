<?php

namespace App\Services\Client\Payment;

use App\Models\InstallmentPlan;
use App\Models\Installment;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\Participant;
use App\Models\Program;
use App\Helpers\ParticipantPriceHelper;
use App\Traits\SystemLogging;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class InstallmentService
{
    use SystemLogging;
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

            // Log para verificar los montos antes de crear el plan
            $this->logInfo('InstallmentService: createOrGetInstallmentPlan - Montos calculados', [
                'program_id' => $programId,
                'participant_rut' => $rut,
                'participant_total_amount' => $participantTotalAmount,
                'paid_amount' => $paidAmount,
                'participant_balance' => $participantBalance,
                'final_amount' => $finalAmount,
                'total_installments' => $totalInstallments,
                'payment_data' => $paymentData
            ]);

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
                                            $this->logInfo('Existing installment plan found with paid installments, continuing with next pending', [
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
                        $this->logInfo('Different number of installments requested, deleting old plan and creating new one', [
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
                        
                        $this->logInfo('Old installment plan deleted due to different number of installments', [
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
                                                    $this->logInfo('Using existing plan with same number of installments, first installment still pending', [
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
                'session_id' => $paymentData['session_id'] ?? null,
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
            
            $this->logInfo('InstallmentService: Creando cuotas individuales', [
                'plan_id' => $installmentPlan->id,
                'final_amount' => $finalAmount,
                'total_installments' => $totalInstallments,
                'amounts' => $amounts,
                'due_dates' => $dueDates
            ]);
            
            for ($i = 1; $i <= $totalInstallments; $i++) {
                $installment = Installment::create([
                    'installment_plan_id' => $installmentPlan->id,
                    'installment_number' => $i,
                    'amount' => $amounts[$i - 1],
                    'due_date' => $dueDates[$i - 1],
                    'status' => 'pending',
                    'notes' => "Cuota {$i} de {$totalInstallments}"
                ]);

                $this->logInfo('InstallmentService: Cuota creada', [
                    'installment_id' => $installment->id,
                    'installment_number' => $i,
                    'amount' => $amounts[$i - 1],
                    'due_date' => $dueDates[$i - 1]
                ]);
            }

            // Obtener la primera cuota para el pago inicial
            $firstInstallment = $installmentPlan->installments()->where('installment_number', 1)->first();

            $this->logInfo('New installment plan created successfully', [
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
            $this->logError('Error creating installment plan', [
                'error' => $e->getMessage(),
                'program_id' => $programId,
                'rut' => $rut
            ], $e);

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
                
                $this->logInfo('Installment plan completed', [
                    'plan_id' => $installmentPlan->id,
                    'total_installments' => $installmentPlan->total_installments
                ]);
            }

            return true;
        } catch (\Exception $e) {
            $this->logError('Error marking installment as paid', [
                'error' => $e->getMessage(),
                'installment_id' => $installment->id
            ], $e);
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
        // Usar el helper para calcular el precio final con descuentos
        $priceData = ParticipantPriceHelper::calculateParticipantPrice($participant, $program);
        $participantTotalAmount = $priceData['final_price'];

        // Pagos aprobados previos de este participante para este programa
        $paidAmount = (float) \App\Models\Payment::whereHas('order', function ($q) use ($participant, $program) {
                $q->where('participant_id', $participant->id)
                  ->where('program_id', $program->id);
            })
            ->where('status', 'approved')
            ->sum('amount');
        $paidAmount = round($paidAmount, 2);
        $participantBalance = max(round($participantTotalAmount - $paidAmount, 2), 0);

        return [$participantTotalAmount, $paidAmount, $participantBalance];
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

        // Log detallado para debugging
        $this->logInfo('InstallmentService: splitAmountInInstallments', [
            'total_amount' => $total,
            'installments' => $installments,
            'base_amount' => $base,
            'allocated' => $allocated,
            'remainder' => $remainder,
            'final_amounts' => $amounts,
            'sum_of_amounts' => array_sum($amounts),
            'difference' => $total - array_sum($amounts)
        ]);

        return $amounts;
    }

    /**
     * Generar fechas de vencimiento mensuales
     * - Si el programa tiene final_payment_date, la última cuota vence ese día y las anteriores se van restando meses.
     * - Si no, usa el día actual como día base y genera hacia adelante.
     */
    private function generateMonthlyDueDates(Program $program, int $installments): array
    {
        $dates = [];
        if ($program->final_payment_date) {
            $last = Carbon::parse($program->final_payment_date);
            for ($i = $installments - 1; $i >= 0; $i--) {
                $dates[$i] = $last->copy()->subMonthsNoOverflow(($installments - 1) - $i);
            }
            ksort($dates);
            return array_values($dates);
        }

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
        
        do {
            // Generar un número aleatorio de 6 dígitos
            $randomSequence = str_pad(rand(100000, 999999), 6, '0', STR_PAD_LEFT);
            $orderNumber = sprintf('%s-%s%s-%s', $prefix, $year, $month, $randomSequence);
            
            // Verificar que no exista ya en la base de datos
            $exists = Order::where('order_number', $orderNumber)->exists();
        } while ($exists);
        
        return $orderNumber;
    }
}
