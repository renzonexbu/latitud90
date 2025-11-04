<?php

namespace App\Modules\Installments\Services;

use App\Models\Installment;
use App\Models\InstallmentPlan;
use App\Models\InstallmentRestructure;
use App\Models\Order;
use App\Models\Participant;
use App\Models\Program;
use App\Modules\Installments\Contracts\InstallmentServiceInterface;
use App\Modules\Installments\Contracts\InstallmentRepositoryInterface;
use App\Modules\Installments\Contracts\InstallmentPlanRepositoryInterface;
use App\Modules\Installments\Contracts\InstallmentCalculatorInterface;
use App\Helpers\ParticipantPriceHelper;
use App\Traits\SystemLogging;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;

class InstallmentManager implements InstallmentServiceInterface
{
    use SystemLogging;

    protected InstallmentRepositoryInterface $installmentRepo;
    protected InstallmentPlanRepositoryInterface $planRepo;
    protected InstallmentCalculatorInterface $calculator;

    public function __construct(
        InstallmentRepositoryInterface $installmentRepo,
        InstallmentPlanRepositoryInterface $planRepo,
        InstallmentCalculatorInterface $calculator
    ) {
        $this->installmentRepo = $installmentRepo;
        $this->planRepo = $planRepo;
        $this->calculator = $calculator;
    }

    /**
     * Verificar si el sistema de cuotas está habilitado
     */
    public function isEnabled(): bool
    {
        return config('installments.enabled', true);
    }

    /**
     * Crear un plan de cuotas simple
     */
    public function createPlan(array $data): ?InstallmentPlan
    {
        if (!$this->isEnabled()) {
            return null;
        }

        try {
            DB::beginTransaction();

            // Crear el plan
            $plan = $this->planRepo->create([
                'order_id' => $data['order_id'],
                'program_id' => $data['program_id'],
                'participant_id' => $data['participant_id'],
                'total_amount' => $data['total_amount'],
                'total_installments' => $data['installments'],
                'payment_type' => $data['payment_type'] ?? 'monthly',
                'status' => 'active',
                'start_date' => $data['start_date'] ?? now(),
                'notes' => $data['notes'] ?? null
            ]);

            // Crear cuotas
            $this->createInstallments($plan, $data['program'], $data['total_amount'], $data['installments']);

            DB::commit();

            $this->logInfo('InstallmentManager: Plan creado', [
                'plan_id' => $plan->id,
                'total_amount' => $data['total_amount'],
                'installments' => $data['installments']
            ]);

            return $plan;

        } catch (\Exception $e) {
            DB::rollBack();
            $this->logError('InstallmentManager: Error creando plan', [
                'error' => $e->getMessage(),
                'data' => $data
            ]);
            throw $e;
        }
    }

    /**
     * Crear o recuperar un plan de cuotas (lógica compleja del sistema LAT90)
     */
    public function createOrGetPlan(int $programId, string $rut, array $paymentData, array $formData): ?InstallmentPlan
    {
        if (!$this->isEnabled()) {
            return null;
        }

        try {
            DB::beginTransaction();

            // Buscar participante
            $participant = Participant::where('document_number', $rut)->first();
            if (!$participant) {
                throw new \Exception('Participante no encontrado');
            }

            // Buscar programa
            $program = Program::with(['course.participants'])->findOrFail($programId);

            // Calcular montos
            [$participantTotalAmount, $paidAmount, $participantBalance] = $this->computeParticipantAmounts($program, $participant);
            $finalAmount = $participantBalance;

            // Número de cuotas
            $totalInstallments = (int) ($paymentData['installments'] ?? ($program->lat90_max_installments ?? 1));
            if ($totalInstallments < 1) {
                $totalInstallments = 1;
            }

            $this->logInfo('InstallmentManager: createOrGetPlan - Montos calculados', [
                'program_id' => $programId,
                'participant_rut' => $rut,
                'participant_total_amount' => $participantTotalAmount,
                'paid_amount' => $paidAmount,
                'participant_balance' => $participantBalance,
                'final_amount' => $finalAmount,
                'total_installments' => $totalInstallments
            ]);

            // Buscar plan existente
            $existingPlan = $this->planRepo->findActiveByProgramAndParticipant($program->id, $participant->id);

            if ($existingPlan) {
                // Verificar si hay cuotas pagadas
                $paidCount = $this->installmentRepo->countByStatus($existingPlan->id, 'paid');

                if ($paidCount > 0) {
                    // Plan con cuotas pagadas - devolver siguiente cuota
                    $nextPending = $this->installmentRepo->getNextPending($existingPlan->id);

                    if ($nextPending) {
                        DB::commit();
                        return $existingPlan;
                    }
                } else {
                    // Sin cuotas pagadas - verificar si cambió el número
                    if ($existingPlan->total_installments !== $totalInstallments) {
                        $this->logInfo('InstallmentManager: Número de cuotas diferente, eliminando plan anterior', [
                            'old_installments' => $existingPlan->total_installments,
                            'new_installments' => $totalInstallments,
                            'plan_id' => $existingPlan->id
                        ]);

                        // Eliminar plan y orden asociada
                        $this->installmentRepo->deletePendingByPlan($existingPlan->id);
                        if ($existingPlan->order) {
                            $existingPlan->order->delete();
                        }
                        $this->planRepo->delete($existingPlan->id);
                    } else {
                        // Mismo número, devolver plan existente
                        DB::commit();
                        return $existingPlan;
                    }
                }
            }

            // Crear nueva orden
            $order = Order::create([
                'participant_id' => $participant->id,
                'program_id' => $program->id,
                'total_amount' => $participantTotalAmount,
                'discount' => 0,
                'final_amount' => $finalAmount,
                'total_installments' => $totalInstallments,
                'payment_type' => 'monthly',
                'status' => 'pending',
                'order_number' => app(\App\Services\Shared\OrderNumberGenerator::class)->generate(),
                'session_id' => $paymentData['session_id'] ?? null,
                'notes' => 'Orden creada para plan de cuotas mensuales'
            ]);

            // Crear plan de cuotas
            $installmentPlan = $this->planRepo->create([
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

            // Crear cuotas individuales
            $this->createInstallments($installmentPlan, $program, $finalAmount, $totalInstallments);

            DB::commit();

            $this->logInfo('InstallmentManager: Plan nuevo creado', [
                'plan_id' => $installmentPlan->id,
                'order_id' => $order->id
            ]);

            return $installmentPlan;

        } catch (\Exception $e) {
            DB::rollBack();
            $this->logError('InstallmentManager: Error en createOrGetPlan', [
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Marcar una cuota como pagada
     */
    public function markAsPaid(int $installmentId, array $paymentData): bool
    {
        if (!$this->isEnabled()) {
            return false;
        }

        try {
            $success = $this->installmentRepo->markAsPaid($installmentId, $paymentData);

            if ($success) {
                // Verificar si el plan se completó
                $installment = $this->installmentRepo->find($installmentId);
                if ($installment) {
                    $this->checkAndCompletePlan($installment->installment_plan_id);
                }

                // Disparar evento
                if (config('installments.enable_events', true)) {
                    Event::dispatch('installment.paid', [$installmentId, $paymentData]);
                }
            }

            return $success;

        } catch (\Exception $e) {
            $this->logError('InstallmentManager: Error marcando cuota como pagada', [
                'installment_id' => $installmentId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Reestructurar un plan de cuotas
     */
    public function restructurePlan(int $planId, int $newInstallments, string $reason): bool
    {
        if (!$this->isEnabled()) {
            return false;
        }

        try {
            DB::beginTransaction();

            $plan = $this->planRepo->find($planId);
            if (!$plan) {
                throw new \Exception('Plan no encontrado');
            }

            // Validaciones
            $paidCount = $this->installmentRepo->countByStatus($planId, 'paid');
            $maxInstallments = config('installments.max_installments_on_restructure', 24);

            if ($newInstallments < $paidCount) {
                throw new \Exception("No se puede reducir a menos de {$paidCount} cuotas (ya pagadas)");
            }

            if ($newInstallments > $maxInstallments) {
                throw new \Exception("Máximo {$maxInstallments} cuotas permitidas");
            }

            // Obtener cuotas pagadas para calcular saldo
            $paidInstallments = $this->installmentRepo->getPaidByPlan($planId);
            $paidTotal = $paidInstallments->sum('amount');
            $remainingBalance = $plan->total_amount - $paidTotal;

            // Guardar estado anterior para auditoría
            $oldInstallments = $this->installmentRepo->getByPlan($planId)->toArray();

            // Eliminar cuotas pendientes
            $this->installmentRepo->deletePendingByPlan($planId);

            // Recalcular y crear nuevas cuotas
            $pendingCount = $newInstallments - $paidCount;
            $amounts = $this->calculator->splitAmount($remainingBalance, $pendingCount);

            $program = Program::find($plan->program_id);
            $dueDates = $this->calculator->generateLat90DueDates($program, $pendingCount);

            for ($i = 0; $i < $pendingCount; $i++) {
                $installmentNumber = $paidCount + $i + 1;
                $this->installmentRepo->create([
                    'installment_plan_id' => $planId,
                    'installment_number' => $installmentNumber,
                    'amount' => $amounts[$i],
                    'due_date' => $dueDates[$i],
                    'status' => 'pending',
                    'notes' => "Cuota {$installmentNumber} de {$newInstallments} (reestructurado)"
                ]);
            }

            // Actualizar plan
            $this->planRepo->update($planId, [
                'total_installments' => $newInstallments
            ]);

            // Registrar reestructuración
            $newInstallmentsData = $this->installmentRepo->getByPlan($planId)->toArray();

            InstallmentRestructure::create([
                'installment_plan_id' => $planId,
                'user_id' => auth()->id(),
                'type' => 'manual',
                'reason' => $reason,
                'old_installments' => count($oldInstallments),
                'new_installments' => $newInstallments,
                'old_balance' => $remainingBalance,
                'new_balance' => $remainingBalance,
                'old_data' => json_encode($oldInstallments),
                'new_data' => json_encode($newInstallmentsData)
            ]);

            DB::commit();

            $this->logInfo('InstallmentManager: Plan reestructurado', [
                'plan_id' => $planId,
                'old_installments' => count($oldInstallments),
                'new_installments' => $newInstallments,
                'reason' => $reason
            ]);

            return true;

        } catch (\Exception $e) {
            DB::rollBack();
            $this->logError('InstallmentManager: Error reestructurando plan', [
                'plan_id' => $planId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Recalcular cuotas después de aplicar descuento
     */
    public function recalculateAfterDiscount(int $planId): bool
    {
        if (!$this->isEnabled()) {
            return false;
        }

        try {
            DB::beginTransaction();

            $plan = $this->planRepo->findWithRelations($planId);
            if (!$plan) {
                throw new \Exception('Plan no encontrado');
            }

            $program = $plan->program;
            $participant = $plan->participant;

            // Recalcular montos con descuentos actualizados
            [$participantTotalAmount, $paidAmount, $participantBalance] = $this->computeParticipantAmounts($program, $participant);

            // Obtener cuotas pagadas
            $paidInstallments = $this->installmentRepo->getPaidByPlan($planId);
            $alreadyPaidAmount = $paidInstallments->sum('amount');

            // Nuevo saldo pendiente
            $newRemainingBalance = $participantBalance - $alreadyPaidAmount;

            // Eliminar cuotas pendientes
            $this->installmentRepo->deletePendingByPlan($planId);

            // Recalcular cuotas pendientes
            $paidCount = $paidInstallments->count();
            $pendingCount = $plan->total_installments - $paidCount;

            if ($pendingCount > 0) {
                $amounts = $this->calculator->splitAmount($newRemainingBalance, $pendingCount);
                $dueDates = $this->calculator->generateLat90DueDates($program, $pendingCount);

                for ($i = 0; $i < $pendingCount; $i++) {
                    $installmentNumber = $paidCount + $i + 1;
                    $this->installmentRepo->create([
                        'installment_plan_id' => $planId,
                        'installment_number' => $installmentNumber,
                        'amount' => $amounts[$i],
                        'due_date' => $dueDates[$i],
                        'status' => 'pending',
                        'notes' => "Cuota {$installmentNumber} de {$plan->total_installments} (recalculado por descuento)"
                    ]);
                }
            }

            // Actualizar monto total del plan
            $this->planRepo->update($planId, [
                'total_amount' => $participantBalance
            ]);

            DB::commit();

            $this->logInfo('InstallmentManager: Plan recalculado por descuento', [
                'plan_id' => $planId,
                'new_total' => $participantBalance,
                'new_remaining' => $newRemainingBalance
            ]);

            return true;

        } catch (\Exception $e) {
            DB::rollBack();
            $this->logError('InstallmentManager: Error recalculando plan', [
                'plan_id' => $planId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Obtener la siguiente cuota pendiente
     */
    public function getNextPendingInstallment(int $planId): ?Installment
    {
        if (!$this->isEnabled()) {
            return null;
        }

        return $this->installmentRepo->getNextPending($planId);
    }

    /**
     * Obtener un plan por ID
     */
    public function getPlan(int $planId): ?InstallmentPlan
    {
        if (!$this->isEnabled()) {
            return null;
        }

        return $this->planRepo->find($planId);
    }

    /**
     * Calcular distribución de monto en cuotas
     */
    public function calculateInstallments(float $totalAmount, int $installmentCount): array
    {
        if (!$this->isEnabled()) {
            return [];
        }

        return $this->calculator->splitAmount($totalAmount, $installmentCount);
    }

    /**
     * Generar fechas de vencimiento
     */
    public function generateDueDates($program, int $installmentCount): array
    {
        if (!$this->isEnabled()) {
            return [];
        }

        return $this->calculator->generateLat90DueDates($program, $installmentCount);
    }

    /**
     * Verificar si un plan está completo
     */
    public function isPlanCompleted(int $planId): bool
    {
        if (!$this->isEnabled()) {
            return false;
        }

        $plan = $this->planRepo->find($planId);
        if (!$plan) {
            return false;
        }

        $paidCount = $this->installmentRepo->countByStatus($planId, 'paid');
        return $paidCount === $plan->total_installments;
    }

    // ==================== Métodos Privados ====================

    /**
     * Crear cuotas individuales para un plan
     */
    protected function createInstallments(InstallmentPlan $plan, $program, float $totalAmount, int $count): void
    {
        $amounts = $this->calculator->splitAmount($totalAmount, $count);
        $dueDates = $this->calculator->generateLat90DueDates($program, $count);

        for ($i = 0; $i < $count; $i++) {
            $this->installmentRepo->create([
                'installment_plan_id' => $plan->id,
                'installment_number' => $i + 1,
                'amount' => $amounts[$i],
                'due_date' => $dueDates[$i],
                'status' => 'pending',
                'notes' => "Cuota " . ($i + 1) . " de {$count}"
            ]);
        }
    }

    /**
     * Verificar y marcar plan como completado si todas las cuotas están pagadas
     */
    protected function checkAndCompletePlan(int $planId): void
    {
        if ($this->isPlanCompleted($planId)) {
            $this->planRepo->markAsCompleted($planId);

            $this->logInfo('InstallmentManager: Plan completado', [
                'plan_id' => $planId
            ]);

            if (config('installments.enable_events', true)) {
                Event::dispatch('installment.plan.completed', [$planId]);
            }
        }
    }

    /**
     * Calcular montos del participante
     */
    protected function computeParticipantAmounts($program, $participant): array
    {
        // Usar el helper para calcular el precio final con descuentos
        $priceData = ParticipantPriceHelper::calculateParticipantPrice($participant, $program);
        $participantTotalAmount = $priceData['final_price'];

        // Pagos aprobados y completados previos de este participante para este programa
        $paidAmount = (float) \App\Models\Payment::whereHas('order', function ($q) use ($participant, $program) {
                $q->where('participant_id', $participant->id)
                  ->where('program_id', $program->id);
            })
            ->whereIn('status', ['approved', 'completed'])
            ->sum('amount');

        $paidAmount = round($paidAmount, 2);
        $participantBalance = max(round($participantTotalAmount - $paidAmount, 2), 0);

        return [$participantTotalAmount, $paidAmount, $participantBalance];
    }
}
