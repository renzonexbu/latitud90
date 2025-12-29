<?php

namespace App\Services\Admin\Payments;

use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\Payment;
use App\Models\PaymentGateway;
use App\Models\PaymentOption;
use App\Helpers\PaymentDocumentTypeHelper;
use App\Traits\AdminLogging;
use App\Helpers\RutHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class StorePaymentService
{
    use AdminLogging;
    /**
     * Crear un nuevo pago presencial
     *
     * @param Request $request
     * @return array
     * @throws \Exception
     */
    public function execute(Request $request): array
    {
        try {
            DB::beginTransaction();

            // VALIDACIÓN: Verificar si el participante tiene suscripción activa
            $this->validateSubscriptionStatus($request->participant_id, $request->program_id);

            // Buscar o crear la orden
            $order = $this->findOrCreateOrder($request);

            // Para pagos presenciales, usar gateway presencial y option según el tipo seleccionado
            $paymentGateway = PaymentGateway::where('code', 'presencial')->firstOrFail();
            
            // Mapear el tipo de pago presencial a la opción correspondiente
            $paymentOptionCode = $this->mapPresentialPaymentTypeToOption($request->presential_payment_type);
            $paymentOption = PaymentOption::where('code', $paymentOptionCode)->firstOrFail();
            

            // Crear el detalle de la orden
            $orderDetail = $this->createOrderDetail($request, $order, $paymentGateway, $paymentOption);

            // Crear el pago
            $payment = $this->createPayment($request, $order, $orderDetail, $paymentGateway, $paymentOption);

            // Actualizar estado de la orden
            $order->refreshStatus();

            // NOTA: Los pagos presenciales NO crean planes de cuotas automáticamente
            // Solo se crea la orden y el pago. Si se necesita un plan de cuotas,
            // debe crearse manualmente desde la interfaz de administración.
            // if ($request->status === 'completed') {
            //     $this->handleInstallmentPlan($order, $request->participant_id, $request->program_id, $request->amount);
            // }

            // Si se proporciona installment_id, marcar la cuota como pagada manualmente
            if ($request->has('installment_id') && $request->installment_id && $request->status === 'completed') {
                $installmentService = new \App\Services\Admin\Installments\MarkInstallmentAsPaidManually();

                // Mapear el tipo de pago presencial a payment_source
                $paymentSource = $this->mapPresentialPaymentTypeToSource($request->presential_payment_type);

                $result = $installmentService->markAsPaid(
                    $request->installment_id,
                    $payment->id,
                    $order->id,
                    $orderDetail->id,
                    $paymentSource
                );

                if ($result['success']) {
                    Log::info('✅ Cuota vinculada a pago presencial', [
                        'payment_id' => $payment->id,
                        'installment_id' => $request->installment_id,
                        'payment_source' => $paymentSource
                    ]);
                } else {
                    Log::warning('⚠️ No se pudo vincular cuota a pago presencial', [
                        'payment_id' => $payment->id,
                        'installment_id' => $request->installment_id,
                        'reason' => $result['message']
                    ]);
                }
            }

            DB::commit();

            // Log the payment creation
            $this->logCreate(
                'payments',
                'Payment',
                $payment->id,
                "Pago presencial creado: \${$request->amount} - Participante ID: {$request->participant_id}",
                $payment->toArray(),
                [
                    'order_id' => $order->id,
                    'payment_type' => 'presencial',
                    'payment_status' => $request->status,
                    'participant_id' => $request->participant_id,
                    'program_id' => $request->program_id,
                    'installment_id' => $request->installment_id ?? null,
                ]
            );

            Log::info('Pago presencial creado exitosamente', [
                'payment_id' => $payment->id,
                'order_id' => $order->id,
                'amount' => $request->amount,
                'participant_id' => $request->participant_id
            ]);

            return [
                'success' => true,
                'payment' => $payment,
                'order' => $order
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al crear pago presencial', [
                'error' => $e->getMessage(),
                'request_data' => $request->all()
            ]);
            throw $e;
        }
    }

    /**
     * Buscar o crear la orden
     *
     * @param Request $request
     * @return Order
     */
    private function findOrCreateOrder(Request $request): Order
    {
        // Siempre crear una nueva orden para pagos presenciales
        $order = Order::create([
            'participant_id' => $request->participant_id,
            'program_id' => $request->program_id,
            'total_amount' => $request->amount,
            'final_amount' => $request->amount,
            'total_installments' => 0, // Pago presencial no tiene cuotas por defecto
            'payment_type' => 'total',
            'status' => 'pending',
            'order_number' => app(\App\Services\Shared\OrderNumberGenerator::class)->generate(),
            'notes' => 'Orden creada desde pago presencial'
        ]);

        return $order;
    }

    /**
     * Generar número de orden único
     */
    // Eliminado: generación local de número de orden. Usar OrderNumberGenerator central.

    /**
     * Crear el detalle de la orden
     *
     * @param Request $request
     * @param Order $order
     * @param PaymentGateway $paymentGateway
     * @param PaymentOption $paymentOption
     * @return OrderDetail
     */
    private function createOrderDetail(Request $request, Order $order, PaymentGateway $paymentGateway, PaymentOption $paymentOption): OrderDetail
    {
        return OrderDetail::create([
            'order_id' => $order->id,
            'payment_option_id' => $paymentOption->id,
            'payment_gateway_id' => $paymentGateway->id,
            'name' => $request->buyer_full_name,
            'email' => $request->buyer_email,
            'country' => $request->buyer_country,
            'region' => $request->buyer_region,
            'city' => $request->buyer_city,
            'code_phone' => $request->buyer_code_phone,
            'phone' => $request->buyer_phone,
            'document_type' => $request->buyer_document_type,
            'document_number' => RutHelper::clean($request->buyer_document_number),
            'billing_address' => null,
            'billing_city' => $request->buyer_city,
            'billing_country' => $request->buyer_country,
            'billing_postal_code' => null,
            'terms_accepted' => true,
            'marketing_accepted' => false,
            'terms_accepted_confirmation' => true,
            'installment_number' => null, // Pago presencial no es una cuota
            'base_amount' => $request->amount,
            'discount_amount' => 0,
            'amount' => $request->amount,
            'due_date' => now(),
            'is_paid' => $request->status === 'completed',
            'status' => $request->status === 'completed' ? 'paid' : $request->status,
            'paid_at' => $request->status === 'completed' ? now() : null,
            'gateway_response' => [
                'notes' => $request->notes,
                'created_manually' => true,
                'payment_type' => 'presential'
            ]
        ]);
    }

    /**
     * Crear el pago
     *
     * @param Request $request
     * @param Order $order
     * @param OrderDetail $orderDetail
     * @param PaymentGateway $paymentGateway
     * @param PaymentOption $paymentOption
     * @return Payment
     */
    private function createPayment(Request $request, Order $order, OrderDetail $orderDetail, PaymentGateway $paymentGateway, PaymentOption $paymentOption): Payment
    {
        return Payment::create([
            'order_id' => $order->id,
            'order_detail_id' => $orderDetail->id,
            'payment_gateway_id' => $paymentGateway->id,
            'payment_option_id' => $paymentOption->id,
            'buy_order' => $order->order_number,
            'amount' => $request->amount,
            'status' => $request->status,
            'transaction_date' => $request->transaction_date ? \Carbon\Carbon::parse($request->transaction_date) : now(),
            'accounting_date' => now(),
            'authorization_code' => $request->authorization_code,
            'payment_code' => $request->payment_code,
            'gateway_response' => [
                'notes' => $request->notes,
                'created_manually' => true,
                'payment_type' => 'presential',
                'buyer_data' => [
                    'full_name' => $request->buyer_full_name,
                    'document_type' => $request->buyer_document_type,
                    'document_number' => RutHelper::clean($request->buyer_document_number),
                    'email' => $request->buyer_email,
                    'phone' => $request->buyer_phone,
                    'country' => $request->buyer_country,
                    'region' => $request->buyer_region,
                    'city' => $request->buyer_city,
                ]
            ],
            'currency' => 'CLP',
            'document_type' => PaymentDocumentTypeHelper::determineDocumentType($order->program_id),
        ]);
    }

    /**
     * Mapear el tipo de pago presencial a la opción de pago correspondiente
     *
     * @param string $presentialPaymentType
     * @return string
     */
    private function mapPresentialPaymentTypeToOption(string $presentialPaymentType): string
    {
        $mapping = [
            'BX' => 'presential_office_card',      // Pago con tarjeta en oficina
            'TE' => 'presential_bank_transfer',    // Transferencia bancaria
            'CH' => 'presential_check',            // Cheque
            'DP' => 'presential_deposit',          // Depósito
        ];

        $result = $mapping[$presentialPaymentType] ?? 'presential_office_card'; // Default fallback
        
        // Log temporal para diagnosticar
        Log::info('Mapeo presential_payment_type:', [
            'input' => $presentialPaymentType,
            'output' => $result,
            'mapping_exists' => isset($mapping[$presentialPaymentType])
        ]);
        
        return $result;
    }

    /**
     * Manejar plan de cuotas y reestructuración
     * NOTA: Este método NO se usa para pagos presenciales
     * Los pagos presenciales no crean cuotas automáticamente
     *
     * @param Order $order
     * @param int $participantId
     * @param int $programId
     * @param float $paymentAmount
     * @return void
     */
    private function handleInstallmentPlan(Order $order, int $participantId, int $programId, float $paymentAmount): void
    {
        try {
            // Calcular monto total del programa
            $program = \App\Models\Program::findOrFail($programId);
            $participant = \App\Models\Participant::findOrFail($participantId);
            
            $priceData = \App\Helpers\ParticipantPriceHelper::calculateParticipantPrice($participant, $program);
            $totalAmount = $priceData['final_price'];
            
            // Calcular monto ya pagado
            $paidAmount = \App\Models\Payment::whereHas('order', function ($q) use ($participantId, $programId) {
                    $q->where('participant_id', $participantId)
                      ->where('program_id', $programId);
                })
                ->whereIn('status', ['completed', 'approved'])
                ->sum('amount');
            
            $newPaidAmount = $paidAmount + $paymentAmount;
            
            // Buscar plan de cuotas existente
            $installmentPlan = \App\Models\InstallmentPlan::where('participant_id', $participantId)
                                                         ->where('program_id', $programId)
                                                         ->first();

            if (!$installmentPlan) {
                // Crear nuevo plan de cuotas si no existe
                $installmentPlan = \App\Models\InstallmentPlan::create([
                    'order_id' => $order->id,
                    'program_id' => $programId,
                    'participant_id' => $participantId,
                    'total_amount' => $totalAmount,
                    'total_installments' => 1,
                    'payment_type' => 'monthly',
                    'status' => 'active',
                    'start_date' => now(),
                    'notes' => 'Plan de cuotas creado desde pago presencial'
                ]);

                // Crear cuota inicial
                \App\Models\Installment::create([
                    'installment_plan_id' => $installmentPlan->id,
                    'installment_number' => 1,
                    'amount' => $totalAmount,
                    'due_date' => now(),
                    'status' => 'pending',
                    'notes' => 'Cuota inicial del programa'
                ]);
            }

            // Reestructurar cuotas pendientes
            $this->restructureInstallments($installmentPlan, $totalAmount, $newPaidAmount);
            
            // También actualizar OrderDetail para mantener consistencia
            $this->updateOrderDetailsFromInstallments($order, $installmentPlan);

        } catch (\Exception $e) {
            Log::error('Error manejando plan de cuotas', [
                'error' => $e->getMessage(),
                'participant_id' => $participantId,
                'program_id' => $programId
            ]);
        }
    }

    /**
     * Reestructurar cuotas pendientes
     *
     * @param \App\Models\InstallmentPlan $installmentPlan
     * @param float $totalAmount
     * @param float $paidAmount
     * @return void
     */
    private function restructureInstallments(\App\Models\InstallmentPlan $installmentPlan, float $totalAmount, float $paidAmount): void
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
     * Actualizar OrderDetail basado en Installments para mantener consistencia
     *
     * @param Order $order
     * @param \App\Models\InstallmentPlan $installmentPlan
     * @return void
     */
    private function updateOrderDetailsFromInstallments(Order $order, \App\Models\InstallmentPlan $installmentPlan): void
    {
        try {
            // Obtener todas las cuotas del plan
            $installments = $installmentPlan->installments()->orderBy('installment_number')->get();
            
            // Eliminar OrderDetails existentes
            $order->orderDetails()->delete();
            
            // Crear nuevos OrderDetails basados en las cuotas
            foreach ($installments as $installment) {
                $order->orderDetails()->create([
                    'payment_option_id' => $order->orderDetails->first()->payment_option_id ?? null,
                    'payment_gateway_id' => $order->orderDetails->first()->payment_gateway_id ?? null,
                    'name' => $order->orderDetails->first()->name ?? 'Participante',
                    'email' => $order->orderDetails->first()->email ?? '',
                    'country' => $order->orderDetails->first()->country ?? null,
                    'region' => $order->orderDetails->first()->region ?? null,
                    'city' => $order->orderDetails->first()->city ?? null,
                    'code_phone' => $order->orderDetails->first()->code_phone ?? null,
                    'phone' => $order->orderDetails->first()->phone ?? null,
                    'document_type' => $order->orderDetails->first()->document_type ?? null,
                    'document_number' => $order->orderDetails->first()->document_number ?? null,
                    'installment_number' => $installment->installment_number,
                    'base_amount' => $installment->amount,
                    'discount_amount' => 0,
                    'amount' => $installment->amount,
                    'due_date' => $installment->due_date,
                    'is_paid' => $installment->status === 'paid',
                    'status' => $installment->status === 'paid' ? 'paid' : 'pending',
                    'paid_at' => $installment->status === 'paid' ? $installment->paid_at : null,
                    'gateway_response' => [
                        'notes' => 'Actualizado desde InstallmentPlan',
                        'installment_id' => $installment->id
                    ]
                ]);
            }
            
            // Actualizar el total de cuotas en la orden
            $order->update([
                'total_installments' => $installments->count()
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error actualizando OrderDetails desde Installments', [
                'error' => $e->getMessage(),
                'order_id' => $order->id,
                'installment_plan_id' => $installmentPlan->id
            ]);
        }
    }

    /**
     * Mapear el tipo de pago presencial a payment_source
     *
     * @param string $presentialPaymentType
     * @return string
     */
    private function mapPresentialPaymentTypeToSource(string $presentialPaymentType): string
    {
        $mapping = [
            'BX' => 'manual_cash',       // Boleta/Efectivo
            'TE' => 'manual_transfer',   // Transferencia Electrónica
            'CH' => 'manual_check',      // Cheque
            'DP' => 'manual_other',      // Depósito u otro
        ];

        return $mapping[$presentialPaymentType] ?? 'manual_cash';
    }

    /**
     * Validar si el participante tiene suscripción activa
     * Solo permite pagos presenciales si hay cobros rechazados
     *
     * @param int $participantId
     * @param int $programId
     * @throws \Exception
     */
    private function validateSubscriptionStatus(int $participantId, int $programId): void
    {
        // Buscar suscripción activa
        $activeSubscription = \App\Models\ProgramSubscription::where('participant_id', $participantId)
            ->where('program_id', $programId)
            ->whereIn('status', ['ACTIVA', 'SUSCRIBIENDO'])
            ->first();

        if (!$activeSubscription) {
            // No hay suscripción activa, puede proceder con pago presencial normal
            return;
        }

        // Tiene suscripción activa, verificar si hay cobros rechazados
        $failedCharges = \App\Models\ChargeAttempt::where('program_subscription_id', $activeSubscription->id)
            ->where('status', 'failed')
            ->whereHas('installment', function ($query) {
                $query->whereIn('status', ['pending', 'overdue']);
            })
            ->count();

        if ($failedCharges === 0) {
            throw new \Exception(
                'Este participante tiene una suscripción activa sin cobros rechazados. ' .
                'Solo se pueden registrar pagos presenciales para cuotas que VirtualPos no pudo cobrar automáticamente.'
            );
        }

        Log::info('✅ Pago presencial permitido: Suscripción con cobros rechazados', [
            'participant_id' => $participantId,
            'program_id' => $programId,
            'subscription_id' => $activeSubscription->id,
            'failed_charges_count' => $failedCharges
        ]);
    }
}
