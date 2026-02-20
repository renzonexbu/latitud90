<?php

namespace App\Services\Admin\Payments;

use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\Payment;
use App\Models\InstallmentPlan;
use App\Models\Installment;
use App\Models\Participant;
use App\Models\Program;
use App\Models\ProgramCourse;
use App\Models\ProgramSubscription;
use App\Models\PaymentGateway;
use App\Models\PaymentOption;
use App\Helpers\ParticipantPriceHelper;
use App\Helpers\RutHelper;
use App\Helpers\PaymentDocumentTypeHelper;
use App\Traits\AdminLogging;
use App\Services\Subscription\SubscriptionRecalculationService;
use App\Services\Client\Integration\BsaleService;
use App\Services\Bsale\BsaleQueueService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;

class CreateParticularPaymentService
{
    use AdminLogging;
    /**
     * Crear un pago presencial completo con reestructuración de cuotas
     */
    public function execute(array $data): array
    {
        try {
            DB::beginTransaction();

            // Validar datos requeridos
            $this->validateData($data);

            // Buscar entidades
            // NOTA: program_id ahora es el ID de ProgramCourse, no de Program template
            $participant = Participant::findOrFail($data['participant_id']);
            $programCourse = ProgramCourse::with('program')->findOrFail($data['program_id']);

            // VALIDACIÓN CRÍTICA: No permitir pagos presenciales si hay suscripción activa
            $this->validateNoActiveSubscription($participant->id, $programCourse->id);

            // Para pagos presenciales, usar gateway presencial y mapear la opción según el tipo
            $paymentGateway = PaymentGateway::where('code', 'presencial')->firstOrFail();

            // Mapear el tipo de pago presencial a la opción correspondiente
            $paymentOptionCode = $this->mapPresentialPaymentTypeToOption($data['presential_payment_type'] ?? 'BX');
            $paymentOption = PaymentOption::where('code', $paymentOptionCode)->firstOrFail();

            // Log temporal para diagnosticar
            Log::info('Pago presencial - Mapeo:', [
                'presential_payment_type' => $data['presential_payment_type'] ?? 'BX',
                'payment_option_code' => $paymentOptionCode,
                'payment_option_id' => $paymentOption->id,
                'payment_option_gateway' => $paymentOption->gateway_code,
                'payment_option_label' => $paymentOption->label
            ]);

            // Calcular montos del participante
            // Usar el precio del ProgramCourse directamente
            $totalAmount = (float) $programCourse->trip_price;

            // Calcular monto ya pagado (program_id en orders es el ID de ProgramCourse)
            $paidAmount = $this->calculatePaidAmount($participant->id, $programCourse->id);
            $previousBalance = max($totalAmount - $paidAmount, 0);

            // Validar monto del pago considerando suscripciones activas
            $validationResult = $this->validatePaymentAmount($participant->id, $programCourse->id, $data['amount'], $previousBalance);
            if (!$validationResult['valid']) {
                throw new \Exception($validationResult['error']);
            }

            // Buscar o crear la orden
            $order = $this->findOrCreateOrder($participant, $programCourse, $totalAmount, $data['amount']);

            // Crear el detalle de la orden
            $orderDetail = $this->createOrderDetail($order, $paymentOption, $paymentGateway, $data);

            // Crear el pago
            $payment = $this->createPayment($order, $orderDetail, $paymentGateway, $paymentOption, $data);

            // Reestructurar cuotas existentes si hay un plan de cuotas activo
            $this->handleInstallmentRestructure($participant, $programCourse, $data['amount']);

            // Manejar suscripciones activas (cancelar y recrear si es necesario)
            $subscriptionResult = $this->handleSubscriptionAdjustment(
                $participant->id,
                $programCourse->id,
                $data['amount']
            );

            // Manejar APORTE (AP) - Actualizar campo contribution en participant_program
            $presentialPaymentType = strtoupper(trim($data['presential_payment_type'] ?? ''));
            if ($presentialPaymentType === 'AP') {
                $this->handleAportePayment($participant->id, $programCourse->id, $data['amount']);
            }

            // Actualizar estado de la orden
            $order->refreshStatus();

            DB::commit();

            // Generar boleta BSale (fuera de la transacción para que el pago quede registrado aunque BSale falle)
            $bsaleResult = $this->generateBsaleInvoiceForPayment($orderDetail, $payment);

            // Log the payment creation
            $isAporte = $presentialPaymentType === 'AP';
            $paymentTypeLabel = $isAporte ? 'Aporte' : 'Pago particular';

            $this->logCreate(
                'payments',
                'Payment',
                $payment->id,
                "{$paymentTypeLabel} creado: \${$data['amount']} - Participante: {$participant->first_name} {$participant->first_last_name}" .
                    ($bsaleResult ? " - Boleta BSale: {$bsaleResult['number']}" : ''),
                $payment->toArray(),
                [
                    'order_id' => $order->id,
                    'participant_id' => $participant->id,
                    'program_course_id' => $programCourse->id,
                    'program_id' => $programCourse->program->id ?? null,
                    'total_amount' => $totalAmount,
                    'paid_amount' => $paidAmount + $data['amount'],
                    'remaining_balance' => $previousBalance - $data['amount'],
                    'payment_code' => $data['payment_code'] ?? null,
                    'is_aporte' => $isAporte,
                    'bsale_generated' => $bsaleResult !== null,
                    'bsale_number' => $bsaleResult['number'] ?? null,
                ]
            );

            Log::info($isAporte ? 'Aporte presencial creado exitosamente' : 'Pago presencial creado exitosamente', [
                'payment_id' => $payment->id,
                'order_id' => $order->id,
                'participant_id' => $participant->id,
                'program_course_id' => $programCourse->id,
                'program_id' => $programCourse->program->id ?? null,
                'amount' => $data['amount'],
                'payment_code' => $data['payment_code'] ?? null,
                'is_aporte' => $isAporte,
                'bsale_generated' => $bsaleResult !== null,
                'bsale_number' => $bsaleResult['number'] ?? null,
            ]);

            return [
                'success' => true,
                'payment' => $payment,
                'order' => $order,
                'order_detail' => $orderDetail,
                'total_amount' => $totalAmount,
                'paid_amount' => $paidAmount + $data['amount'],
                'remaining_balance' => $previousBalance - $data['amount'],
                'bsale_generated' => $bsaleResult !== null,
                'bsale_number' => $bsaleResult['number'] ?? null,
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error creando pago presencial', [
                'error' => $e->getMessage(),
                'data' => $data
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Validar datos requeridos
     */
    private function validateData(array $data): void
    {
        $required = [
            'participant_id', 'program_id', 'amount', 'transaction_date', 'payment_code'
        ];

        foreach ($required as $field) {
            if (empty($data[$field])) {
                throw new \Exception("El campo {$field} es requerido");
            }
        }

        if ($data['amount'] <= 0) {
            throw new \Exception("El monto debe ser mayor a 0");
        }
    }

    /**
     * Validar suscripción activa
     * Si hay suscripción activa, solo permitir pagos presenciales cuando hay cobros rechazados
     */
    private function validateNoActiveSubscription(int $participantId, int $programId): void
    {
        $activeSubscription = ProgramSubscription::where('participant_id', $participantId)
            ->where('program_id', $programId)
            ->whereIn('status', ['ACTIVA', 'SUSCRIBIENDO'])
            ->first();

        if (!$activeSubscription) {
            // No hay suscripción activa, puede proceder
            Log::info('Validación de suscripción activa: OK (no existe suscripción)', [
                'participant_id' => $participantId,
                'program_id' => $programId,
                'operation' => 'presential_payment'
            ]);
            return;
        }

        // Verificar si hay cobros rechazados en VirtualPos
        $failedCharges = \App\Models\ChargeAttempt::where('program_subscription_id', $activeSubscription->id)
            ->where('status', 'failed')
            ->whereHas('installment', function ($query) {
                $query->whereIn('status', ['pending', 'overdue']);
            })
            ->count();

        Log::info('Validación de suscripción activa con cobros rechazados', [
            'participant_id' => $participantId,
            'program_id' => $programId,
            'subscription_id' => $activeSubscription->id,
            'failed_charges_count' => $failedCharges,
            'operation' => 'presential_payment'
        ]);

        if ($failedCharges === 0) {
            throw new \Exception(
                "No se puede registrar un pago presencial para este participante porque tiene una suscripción activa sin cobros rechazados. " .
                "Solo se pueden registrar pagos presenciales para cuotas que VirtualPos no pudo cobrar automáticamente."
            );
        }

        // Si hay cobros rechazados, permitir el pago presencial
        Log::info('✅ Pago presencial permitido: hay cobros rechazados pendientes de pago', [
            'participant_id' => $participantId,
            'program_id' => $programId,
            'subscription_id' => $activeSubscription->id,
            'failed_charges_count' => $failedCharges
        ]);
    }

    /**
     * Calcular monto ya pagado por el participante
     */
    private function calculatePaidAmount(int $participantId, int $programId): float
    {
        return (float) Payment::whereHas('order', function ($q) use ($participantId, $programId) {
                $q->where('participant_id', $participantId)
                  ->where('program_id', $programId);
            })
            ->whereIn('status', ['completed', 'approved'])
            ->sum('amount');
    }

    /**
     * Validar el monto del pago considerando suscripciones activas
     */
    private function validatePaymentAmount(int $participantId, int $programId, float $paymentAmount, float $orderBalance): array
    {
        // Verificar si existe una suscripción activa
        $subscription = \App\Models\ProgramSubscription::where('participant_id', $participantId)
            ->where('program_id', $programId)
            ->where('status', 'ACTIVA')
            ->whereNotNull('virtualpos_subscription_id')
            ->first();

        if ($subscription) {
            // Si hay suscripción activa, validar contra el monto pendiente de la suscripción
            try {
                $virtualPosService = app(\App\Services\Subscription\VirtualPosSubscriptionService::class);
                $virtualPosData = $virtualPosService->getSubscription($subscription->virtualpos_subscription_id);

                // Usar datos de VirtualPos, o como fallback los datos locales
                $chargeProgram = $virtualPosData['charge_program'] ?? $subscription->charge_program ?? [];

                $pendingAmount = 0;
                foreach ($chargeProgram as $charge) {
                    $status = strtolower($charge['status'] ?? '');
                    if (in_array($status, ['pendiente', 'procesando'])) {
                        $pendingAmount += $charge['amount'] ?? 0;
                    }
                }

                Log::info('Pago presencial: Validación contra suscripción activa', [
                    'participant_id' => $participantId,
                    'program_id' => $programId,
                    'subscription_id' => $subscription->id,
                    'subscription_pending_amount' => $pendingAmount,
                    'payment_amount' => $paymentAmount,
                    'using_local_data' => empty($virtualPosData['charge_program'])
                ]);

                // Si el pago excede el monto pendiente de la suscripción
                if ($paymentAmount > $pendingAmount) {
                    return [
                        'valid' => false,
                        'error' => "El monto del pago ({$paymentAmount}) excede el saldo pendiente de la suscripción ({$pendingAmount})"
                    ];
                }

                return ['valid' => true];

            } catch (\Exception $e) {
                Log::error('Pago presencial: Error validando contra suscripción', [
                    'participant_id' => $participantId,
                    'program_id' => $programId,
                    'error' => $e->getMessage()
                ]);
                // Continuar con validación normal si falla la consulta a VirtualPos
            }
        }

        // Si no hay suscripción activa o falló la validación de suscripción,
        // usar validación tradicional contra el balance de órdenes
        Log::info('Pago presencial: Validación contra balance de órdenes', [
            'participant_id' => $participantId,
            'program_id' => $programId,
            'order_balance' => $orderBalance,
            'payment_amount' => $paymentAmount
        ]);

        if ($paymentAmount > $orderBalance) {
            return [
                'valid' => false,
                'error' => "El monto del pago ({$paymentAmount}) excede el saldo pendiente ({$orderBalance})"
            ];
        }

        return ['valid' => true];
    }

    /**
     * Buscar o crear orden
     * NOTA: $programCourse->id se guarda en program_id, no el ID del template Program
     */
    private function findOrCreateOrder(Participant $participant, ProgramCourse $programCourse, float $totalAmount, float $paymentAmount): Order
    {
        // Siempre crear una nueva orden para pagos presenciales
        $order = Order::create([
            'participant_id' => $participant->id,
            'program_id' => $programCourse->id, // program_id guarda el ID de ProgramCourse
            'total_amount' => $totalAmount,
            'discount' => 0,
            'final_amount' => $totalAmount,
            'total_installments' => 1, // Se ajustará según el plan de cuotas
            'payment_type' => 'total',
            'status' => 'pending',
            'order_number' => app(\App\Services\Shared\OrderNumberGenerator::class)->generate(),
            'notes' => 'Orden creada desde pago presencial'
        ]);

        return $order;
    }

    /**
     * Crear el detalle de la orden
     */
    private function createOrderDetail(Order $order, PaymentOption $paymentOption, PaymentGateway $paymentGateway, array $data): OrderDetail
    {
        return OrderDetail::create([
            'order_id' => $order->id,
            'payment_option_id' => $paymentOption->id,
            'payment_gateway_id' => $paymentGateway->id,
            'name' => $data['buyer_full_name'] ?? trim(($data['buyer_first_name'] ?? '') . ' ' . ($data['buyer_last_name'] ?? '')) ?: 'Comprador',
            'email' => $data['buyer_email'] ?? '',
            'country' => $data['buyer_country'] ?? null,
            'region' => $data['buyer_region'] ?? null,
            'city' => $data['buyer_city'] ?? null,
            'code_phone' => $data['buyer_code_phone'] ?? null,
            'phone' => $data['buyer_phone'] ?? null,
            'document_type' => $data['buyer_document_type'] ?? null,
            'document_number' => RutHelper::clean($data['buyer_document_number'] ?? null),
            'installment_number' => null, // Pago presencial no es una cuota
            'installments_number' => null,
            'base_amount' => $data['amount'],
            'discount_amount' => 0,
            'amount' => $data['amount'],
            'due_date' => Carbon::parse($data['transaction_date']),
            'is_paid' => true,
            'status' => 'paid',
            'paid_at' => Carbon::parse($data['transaction_date']),
            'gateway_response' => [
                'notes' => $data['notes'] ?? null,
                'created_manually' => true,
                'payment_type' => 'presential',
                'buyer_data' => [
                    'first_name' => $data['buyer_first_name'] ?? '',
                    'last_name' => $data['buyer_last_name'] ?? '',
                ],
            ]
        ]);
    }

    /**
     * Crear el pago
     */
    private function createPayment(Order $order, OrderDetail $orderDetail, PaymentGateway $paymentGateway, PaymentOption $paymentOption, array $data): Payment
    {
        // CT (Crédito Temporal) usa document_type 'CT', no genera boleta
        $documentType = $paymentOption->code === 'presential_credit_temp'
            ? 'CT'
            : PaymentDocumentTypeHelper::determineDocumentType($order->program_id);

        return Payment::create([
            'order_id' => $order->id,
            'order_detail_id' => $orderDetail->id,
            'payment_gateway_id' => $paymentGateway->id,
            'payment_option_id' => $paymentOption->id,
            'buy_order' => $order->order_number,
            'amount' => $data['amount'],
            'status' => 'completed',
            'transaction_date' => Carbon::parse($data['transaction_date']),
            'authorization_code' => $data['authorization_code'] ?? null,
            // payment_code SIEMPRE guarda el código manual ingresado por el usuario
            // bsale_number se llena SOLO cuando BSale genera la boleta automáticamente
            'payment_code' => $data['payment_code'],
            'bsale_number' => null, // Se llenará al generar la boleta BSale
            'gateway_response' => [
                'notes' => $data['notes'] ?? null,
                'created_manually' => true,
                'payment_type' => 'presential',
                'buyer_data' => [
                    'first_name' => $data['buyer_first_name'] ?? null,
                    'last_name' => $data['buyer_last_name'] ?? null,
                    'full_name' => $data['buyer_full_name'] ?? null,
                    'document_type' => $data['buyer_document_type'] ?? null,
                    'document_number' => RutHelper::clean($data['buyer_document_number'] ?? null),
                    'email' => $data['buyer_email'] ?? null,
                    'phone' => $data['buyer_phone'] ?? null,
                    'country' => $data['buyer_country'] ?? null,
                    'region' => $data['buyer_region'] ?? null,
                    'city' => $data['buyer_city'] ?? null,
                ]
            ],
            'currency' => 'CLP',
            'document_type' => $documentType,
        ]);
    }

    /**
     * Manejar plan de cuotas y reestructurar
     * NOTA: Este método NO se usa para pagos presenciales
     * Los pagos presenciales no crean cuotas automáticamente
     */
    private function handleInstallmentPlan(Order $order, Participant $participant, ProgramCourse $programCourse, float $totalAmount, float $newPaidAmount): void
    {
        // Buscar plan de cuotas existente
        $installmentPlan = InstallmentPlan::where('participant_id', $participant->id)
                                         ->where('program_id', $programCourse->id)
                                         ->first();

        if (!$installmentPlan) {
            // Crear nuevo plan de cuotas si no existe
            $installmentPlan = $this->createInstallmentPlan($order, $participant, $programCourse, $totalAmount);
        }

        // Reestructurar cuotas pendientes
        $this->restructureInstallments($installmentPlan, $totalAmount, $newPaidAmount);
    }

    /**
     * Crear nuevo plan de cuotas
     */
    private function createInstallmentPlan(Order $order, Participant $participant, ProgramCourse $programCourse, float $totalAmount): InstallmentPlan
    {
        $installmentPlan = InstallmentPlan::create([
            'order_id' => $order->id,
            'program_id' => $programCourse->id,
            'participant_id' => $participant->id,
            'total_amount' => $totalAmount,
            'total_installments' => 1, // Se ajustará en la reestructuración
            'payment_type' => 'monthly',
            'status' => 'active',
            'start_date' => now(),
            'notes' => 'Plan de cuotas creado desde pago presencial'
        ]);

        // Crear cuota inicial
        Installment::create([
            'installment_plan_id' => $installmentPlan->id,
            'installment_number' => 1,
            'amount' => $totalAmount,
            'due_date' => now(),
            'status' => 'pending',
            'notes' => 'Cuota inicial del programa'
        ]);

        return $installmentPlan;
    }

    /**
     * Reestructurar cuotas pendientes
     */
    private function restructureInstallments(InstallmentPlan $installmentPlan, float $totalAmount, float $paidAmount): void
    {
        $remainingBalance = max($totalAmount - $paidAmount, 0);
        
        if ($remainingBalance <= 0) {
            // Si ya está completamente pagado, marcar todas las cuotas como pagadas
            $installmentPlan->installments()->update(['status' => 'paid', 'paid_at' => now()]);
            $installmentPlan->update(['status' => 'completed', 'end_date' => now()]);
            return;
        }

        // Obtener cuotas pendientes
        $pendingInstallments = $installmentPlan->installments()
            ->where('status', 'pending')
            ->orderBy('installment_number')
            ->get();

        if ($pendingInstallments->isEmpty()) {
            return;
        }

        // Calcular cuántas cuotas quedan por pagar
        $remainingInstallments = $pendingInstallments->count();
        
        // Distribuir el saldo restante entre las cuotas pendientes
        $amountPerInstallment = $remainingBalance / $remainingInstallments;
        $remainder = $remainingBalance - ($amountPerInstallment * $remainingInstallments);

        foreach ($pendingInstallments as $index => $installment) {
            $installmentAmount = $amountPerInstallment;
            
            // Distribuir centavos restantes en las primeras cuotas
            if ($remainder > 0) {
                $installmentAmount += 0.01;
                $remainder -= 0.01;
            }

            $installment->update([
                'amount' => round($installmentAmount, 2),
                'adjusted_at' => now(),
                'adjustment_reason' => 'Reestructuración por pago presencial'
            ]);
        }

        // Actualizar el plan
        $installmentPlan->update([
            'total_installments' => $pendingInstallments->count()
        ]);

        Log::info('Cuotas reestructuradas exitosamente', [
            'installment_plan_id' => $installmentPlan->id,
            'total_amount' => $totalAmount,
            'paid_amount' => $paidAmount,
            'remaining_balance' => $remainingBalance,
            'remaining_installments' => $remainingInstallments
        ]);
    }



    /**
     * Reestructurar cuotas existentes después de un pago presencial
     */
    private function handleInstallmentRestructure(Participant $participant, ProgramCourse $programCourse, float $paymentAmount): void
    {
        // Buscar plan de cuotas existente
        $installmentPlan = InstallmentPlan::where('participant_id', $participant->id)
                                         ->where('program_id', $programCourse->id)
                                         ->where('status', 'active')
                                         ->first();

        if (!$installmentPlan) {
            // No hay plan de cuotas existente, no hay nada que reestructurar
            Log::info('No se encontró plan de cuotas activo para reestructurar', [
                'participant_id' => $participant->id,
                'program_course_id' => $programCourse->id,
                'payment_amount' => $paymentAmount
            ]);
            return;
        }

        // Calcular montos actuales
        // Usar el precio del ProgramCourse directamente
        $totalAmount = (float) $programCourse->trip_price;

        // Calcular monto total ya pagado (incluyendo este pago)
        $paidAmount = $this->calculatePaidAmount($participant->id, $programCourse->id);
        
        // Calcular saldo restante
        $remainingBalance = max($totalAmount - $paidAmount, 0);
        
        if ($remainingBalance <= 0) {
            // Si ya está completamente pagado, marcar todas las cuotas como completadas
            $installmentPlan->installments()
                ->where('status', 'pending')
                ->update([
                    'status' => 'paid', 
                    'paid_at' => now(),
                    'notes' => 'Marcada como pagada por pago presencial completo'
                ]);
            
            $installmentPlan->update([
                'status' => 'completed', 
                'end_date' => now()
            ]);
            
            Log::info('Plan de cuotas completado por pago presencial', [
                'installment_plan_id' => $installmentPlan->id,
                'total_amount' => $totalAmount,
                'paid_amount' => $paidAmount
            ]);
            return;
        }

        // Obtener cuotas pendientes
        $pendingInstallments = $installmentPlan->installments()
            ->where('status', 'pending')
            ->orderBy('installment_number')
            ->get();

        if ($pendingInstallments->isEmpty()) {
            Log::info('No hay cuotas pendientes para reestructurar', [
                'installment_plan_id' => $installmentPlan->id
            ]);
            return;
        }

        // Reestructurar las cuotas pendientes
        $this->redistributeAmountInPendingInstallments($pendingInstallments, $remainingBalance);
        
        Log::info('Cuotas reestructuradas exitosamente después de pago presencial', [
            'installment_plan_id' => $installmentPlan->id,
            'total_amount' => $totalAmount,
            'paid_amount' => $paidAmount,
            'remaining_balance' => $remainingBalance,
            'pending_installments_count' => $pendingInstallments->count(),
            'payment_amount' => $paymentAmount
        ]);
    }

    /**
     * Redistribuir el saldo restante entre las cuotas pendientes
     */
    private function redistributeAmountInPendingInstallments($pendingInstallments, float $remainingBalance): void
    {
        $installmentCount = $pendingInstallments->count();
        
        // Calcular monto base por cuota: solo .6+ hacia arriba (1-5 abajo, 6-9 arriba)
        $remainingBalance = (int) round($remainingBalance);
        $baseAmount = (int) floor(($remainingBalance / $installmentCount) + 0.4);

        // Última cuota absorbe el residuo
        $allocated = $baseAmount * ($installmentCount - 1);
        $lastAmount = $remainingBalance - $allocated;

        foreach ($pendingInstallments as $index => $installment) {
            // Última cuota absorbe el residuo
            $newAmount = ($index === $installmentCount - 1) ? $lastAmount : $baseAmount;

            $installment->update([
                'amount' => $newAmount,
                'adjusted_at' => now(),
                'adjustment_reason' => 'Reestructuración automática por pago presencial adicional'
            ]);
        }
    }

    /**
     * Manejar ajuste de suscripción activa cuando se hace un pago presencial
     */
    private function handleSubscriptionAdjustment(int $participantId, int $programId, float $paymentAmount): ?array
    {
        try {
            $recalculationService = app(SubscriptionRecalculationService::class);

            // Procesar ajuste de suscripción
            $result = $recalculationService->processPaymentWithSubscriptionAdjustment(
                $participantId,
                $programId,
                $paymentAmount,
                'payment'
            );

            if (!$result['has_subscription']) {
                Log::info('Pago presencial: No hay suscripción activa para ajustar', [
                    'participant_id' => $participantId,
                    'program_id' => $programId
                ]);
                return null;
            }

            if ($result['subscription_cancelled']) {
                // Suscripción fue cancelada y recreada con nuevo plan
                Log::info('Pago presencial: Suscripción cancelada y recreada', [
                    'participant_id' => $participantId,
                    'program_id' => $programId,
                    'old_subscription_id' => $result['old_subscription_id'] ?? null,
                    'new_subscription_id' => $result['new_subscription_id'] ?? null,
                    'new_plan_id' => $result['new_plan_id'] ?? null,
                    'new_installments' => $result['new_installments'] ?? null,
                    'new_pending_amount' => $result['new_pending_amount'] ?? null
                ]);
            } else {
                // Suscripción se mantiene activa (no debería llegar aquí con la lógica actual)
                Log::info('Pago presencial: Suscripción mantenida activa', [
                    'participant_id' => $participantId,
                    'program_id' => $programId,
                    'subscription_id' => $result['subscription_id'] ?? null
                ]);
            }

            return $result;

        } catch (\Exception $e) {
            Log::error('Pago presencial: Error manejando ajuste de suscripción', [
                'participant_id' => $participantId,
                'program_id' => $programId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            // No lanzar excepción - el pago ya se procesó correctamente
            // Solo logear el error para revisión
            return null;
        }
    }

    /**
     * Mapear el tipo de pago presencial a la opción de pago correspondiente
     */
    private function mapPresentialPaymentTypeToOption(string $presentialType): string
    {
        $mapping = [
            'TC' => 'presential_pos_office',       // POS Oficina
            'KP' => 'presential_khipu_link',       // Link Khipu
            'PAT' => 'presential_subscription',    // Suscripción Cuotas
            'TE' => 'presential_bank_transfer',    // Transferencia bancaria
            'VP' => 'presential_debit_credit',     // Link TD/TC
            'VPI' => 'presential_international',   // Link Internacional
            'DP' => 'presential_deposit',          // Depósito
            'WP' => 'presential_webpay',           // Webpay
            'AP' => 'presential_aporte',           // Aporte
            'CT' => 'presential_credit_temp',      // Crédito Temporal
        ];

        return $mapping[$presentialType] ?? 'presential_pos_office'; // Default a POS Oficina
    }

    /**
     * Manejar pago de tipo APORTE (AP)
     * Los aportes actualizan el campo contribution en participant_program
     */
    private function handleAportePayment(int $participantId, int $programCourseId, float $paymentAmount): void
    {
        // Buscar el registro participant_program
        $participantProgram = \App\Models\ParticipantProgram::where('participant_id', $participantId)
            ->where('program_id', $programCourseId)
            ->first();

        if (!$participantProgram) {
            Log::warning('No se encontró participant_program para registrar aporte', [
                'participant_id' => $participantId,
                'program_course_id' => $programCourseId,
                'payment_amount' => $paymentAmount,
            ]);
            return;
        }

        // Actualizar el campo contribution sumando el nuevo aporte
        $currentContribution = (float) ($participantProgram->contribution ?? 0);
        $newContribution = $currentContribution + $paymentAmount;

        $participantProgram->update([
            'contribution' => $newContribution
        ]);

        Log::info('Aporte registrado y actualizado en participant_program', [
            'participant_id' => $participantId,
            'program_course_id' => $programCourseId,
            'participant_program_id' => $participantProgram->id,
            'previous_contribution' => $currentContribution,
            'payment_amount' => $paymentAmount,
            'new_contribution' => $newContribution,
        ]);
    }

    /**
     * Generar boleta BSale para el pago presencial
     * Usa BsaleQueueService para:
     * - Máximo 3 intentos antes de marcar como fallido
     * - Tracking completo en tabla bsale_requests
     * - Visibilidad en el Monitor de BSale
     */
    private function generateBsaleInvoiceForPayment(OrderDetail $orderDetail, Payment $payment): ?array
    {
        try {
            $bsaleQueueService = app(BsaleQueueService::class);

            // 1. Encolar la solicitud (esto valida si debe generar boleta)
            $bsaleRequest = $bsaleQueueService->queueBoleta($payment, 'presential_payment');

            if (!$bsaleRequest) {
                Log::info('Pago presencial: No se encoló boleta BSale (no aplica o ya existe)', [
                    'payment_id' => $payment->id,
                    'document_type' => $payment->document_type,
                ]);
                return null;
            }

            // 2. Procesar inmediatamente para obtener el resultado
            $success = $bsaleQueueService->processRequest($bsaleRequest);

            // 3. Recargar el pago para obtener los datos de BSale actualizados
            $payment->refresh();
            $bsaleRequest->refresh();

            if ($success && $bsaleRequest->status === 'completed') {
                $bsaleResult = [
                    'id' => $payment->bsale_document_id,
                    'number' => $payment->bsale_number,
                    'token' => $payment->bsale_token,
                ];

                // Descargar y guardar el PDF de la boleta en el servidor
                $this->downloadAndStoreBsalePdf($payment, $bsaleResult);

                Log::info('Pago presencial: Boleta BSale generada exitosamente', [
                    'payment_id' => $payment->id,
                    'order_detail_id' => $orderDetail->id,
                    'bsale_request_id' => $bsaleRequest->id,
                    'bsale_document_id' => $payment->bsale_document_id,
                    'bsale_number' => $payment->bsale_number,
                ]);

                return $bsaleResult;
            } else {
                Log::warning('Pago presencial: Boleta BSale no generada (encolada para reintento)', [
                    'payment_id' => $payment->id,
                    'bsale_request_id' => $bsaleRequest->id,
                    'bsale_request_status' => $bsaleRequest->status,
                    'attempts' => $bsaleRequest->attempts,
                    'error_message' => $bsaleRequest->error_message,
                ]);
                return null;
            }

        } catch (\Exception $e) {
            // No lanzar excepción - el pago ya fue registrado
            // Solo logear el error para revisión
            Log::error('Pago presencial: Error generando boleta BSale', [
                'payment_id' => $payment->id,
                'order_detail_id' => $orderDetail->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return null;
        }
    }

    /**
     * Descargar y almacenar el PDF de la boleta BSale en el servidor
     */
    private function downloadAndStoreBsalePdf(Payment $payment, array $bsaleResult): ?string
    {
        try {
            $bsaleToken = $bsaleResult['token'] ?? $payment->bsale_token;
            $bsaleDocumentId = $bsaleResult['id'] ?? $payment->bsale_document_id;

            if (!$bsaleToken) {
                Log::warning('Pago presencial: No se puede descargar PDF - falta token BSale', [
                    'payment_id' => $payment->id,
                ]);
                return null;
            }

            // Construir URL del PDF de BSale
            $bsaleUrl = "https://app2.bsale.cl/view/90370/{$bsaleToken}.pdf?sfd=99";

            // Crear directorio para almacenar boletas
            $year = $payment->created_at->format('Y');
            $month = $payment->created_at->format('m');
            $storageDir = storage_path("app/bsale_documents/{$year}/{$month}");

            if (!file_exists($storageDir)) {
                mkdir($storageDir, 0755, true);
            }

            // Nombre del archivo
            $filename = "bsale_{$bsaleDocumentId}_payment_{$payment->id}.pdf";
            $filePath = "{$storageDir}/{$filename}";

            // Descargar el PDF
            $response = Http::timeout(30)->get($bsaleUrl);

            if ($response->successful()) {
                file_put_contents($filePath, $response->body());

                Log::info('Pago presencial: PDF de boleta BSale descargado y almacenado', [
                    'payment_id' => $payment->id,
                    'bsale_document_id' => $bsaleDocumentId,
                    'bsale_number' => $payment->bsale_number,
                    'file_path' => $filePath,
                ]);

                return $filePath;
            } else {
                Log::warning('Pago presencial: No se pudo descargar PDF de BSale', [
                    'payment_id' => $payment->id,
                    'bsale_url' => $bsaleUrl,
                    'status' => $response->status(),
                ]);
                return null;
            }

        } catch (\Exception $e) {
            Log::error('Pago presencial: Error descargando PDF de BSale', [
                'payment_id' => $payment->id,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }
}
