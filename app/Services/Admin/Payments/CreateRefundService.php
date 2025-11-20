<?php

namespace App\Services\Admin\Payments;

use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\Payment;
use App\Models\InstallmentPlan;
use App\Models\Installment;
use App\Models\Participant;
use App\Models\Program;
use App\Models\PaymentGateway;
use App\Models\PaymentOption;
use App\Helpers\ParticipantPriceHelper;
use App\Traits\AdminLogging;
use App\Services\Subscription\SubscriptionRecalculationService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class CreateRefundService
{
    use AdminLogging;
    /**
     * Crear un reembolso completo con reestructuración de cuotas
     * La diferencia principal con los pagos es que SUMA el monto a la deuda en lugar de restarlo
     */
    public function execute(array $data): array
    {
        try {
            DB::beginTransaction();

            // Validar datos requeridos
            $this->validateData($data);

            // Buscar entidades
            $participant = Participant::findOrFail($data['participant_id']);
            $program = Program::findOrFail($data['program_id']);
            
            // Para reembolsos, buscar por código en lugar de ID hardcodeado
            $paymentGateway = PaymentGateway::where('code', 'refund')->firstOrFail();
            $paymentOption = PaymentOption::where('code', 'refund_credit_note')->firstOrFail();

            // Calcular montos del participante
            $priceData = ParticipantPriceHelper::calculateParticipantPrice($participant, $program);
            $totalAmount = $priceData['final_price'];

            // Calcular monto ya pagado
            $paidAmount = $this->calculatePaidAmount($participant->id, $program->id);
            $previousBalance = max($totalAmount - $paidAmount, 0);

            // Validar monto del reembolso
            $validationResult = $this->validateRefundAmount($participant->id, $program->id, $data['amount'], $paidAmount);
            if (!$validationResult['valid']) {
                throw new \Exception($validationResult['error']);
            }

            // Buscar o crear la orden
            $order = $this->findOrCreateOrder($participant, $program, $totalAmount, $data['amount'], $data);

            // Crear el detalle de la orden
            $orderDetail = $this->createOrderDetail($order, $paymentOption, $paymentGateway, $data);

            // Crear el reembolso (como un pago negativo)
            $refund = $this->createRefund($order, $orderDetail, $paymentGateway, $paymentOption, $data);

            // LÓGICA CLAVE: Para reembolsos, RESTAMOS el monto pagado (equivale a SUMAR a la deuda)
            // Si antes había pagado 100.000 y reembolsamos 50.000, ahora efectivamente ha pagado 50.000
            $newPaidAmount = $paidAmount - $data['amount'];
            
            // NOTA: Los reembolsos NO crean cuotas automáticamente
            // Solo se registra el reembolso en la orden. Si se necesita un plan de cuotas,
            // debe crearse manualmente desde la interfaz de administración.

            // Manejar suscripciones activas (cancelar y recrear con monto ajustado)
            $subscriptionResult = $this->handleSubscriptionAdjustmentForRefund(
                $participant->id,
                $program->id,
                $data['amount']
            );

            // Actualizar estado de la orden
            $order->refreshStatus();

            DB::commit();

            // Log the refund creation
            $this->logCreate(
                'payments',
                'Refund',
                $refund->id,
                "Reembolso creado: \${$data['amount']} - Participante: {$participant->first_name} {$participant->first_last_name}",
                $refund->toArray(),
                [
                    'order_id' => $order->id,
                    'participant_id' => $participant->id,
                    'program_id' => $program->id,
                    'total_amount' => $totalAmount,
                    'previous_paid_amount' => $paidAmount,
                    'new_paid_amount' => $newPaidAmount,
                    'refund_amount' => $data['amount'],
                    'new_balance' => $totalAmount - $newPaidAmount,
                    'payment_code' => $data['payment_code'] ?? null,
                ]
            );

            Log::info('Reembolso procesado exitosamente', [
                'refund_id' => $refund->id,
                'order_id' => $order->id,
                'participant_id' => $participant->id,
                'program_id' => $program->id,
                'refund_amount' => $data['amount'],
                'payment_code' => $data['payment_code'] ?? null,
                'previous_paid_amount' => $paidAmount,
                'new_paid_amount' => $newPaidAmount,
                'new_balance' => $totalAmount - $newPaidAmount
            ]);

            return [
                'success' => true,
                'refund' => $refund,
                'order' => $order,
                'order_detail' => $orderDetail,
                'total_amount' => $totalAmount,
                'previous_paid_amount' => $paidAmount,
                'new_paid_amount' => $newPaidAmount,
                'refund_amount' => $data['amount'],
                'new_balance' => $totalAmount - $newPaidAmount
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error procesando reembolso', [
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
        $required = ['participant_id', 'program_id', 'amount', 'payment_code', 'transaction_date', 'sii_code', 'document_number', 'total_amount'];
        
        foreach ($required as $field) {
            if (!isset($data[$field]) || empty($data[$field])) {
                throw new \Exception("El campo {$field} es requerido");
            }
        }

        if (!is_numeric($data['amount']) || $data['amount'] <= 0) {
            throw new \Exception("El monto del reembolso debe ser un número mayor a 0");
        }
    }

    /**
     * Calcular monto pagado
     */
    private function calculatePaidAmount($participantId, $programId): float
    {
        return Payment::whereHas('order', function ($query) use ($participantId, $programId) {
            $query->where('participant_id', $participantId)
                  ->where('program_id', $programId);
        })
        ->where('status', 'completed')
        ->sum('amount');
    }

    /**
     * Validar el monto del reembolso considerando suscripciones activas
     */
    private function validateRefundAmount(int $participantId, int $programId, float $refundAmount, float $paidAmount): array
    {
        // Validación básica: monto debe ser mayor a 0
        if ($refundAmount <= 0) {
            return [
                'valid' => false,
                'error' => "El monto del reembolso debe ser mayor a 0"
            ];
        }

        // Verificar si existe una suscripción activa
        $subscription = \App\Models\ProgramSubscription::where('participant_id', $participantId)
            ->where('program_id', $programId)
            ->where('status', 'ACTIVA')
            ->whereNotNull('virtualpos_subscription_id')
            ->first();

        if ($subscription) {
            // Si hay suscripción activa, validar que el reembolso no exceda el monto pagado de la suscripción
            try {
                $virtualPosService = app(\App\Services\Subscription\VirtualPosSubscriptionService::class);
                $virtualPosData = $virtualPosService->getSubscription($subscription->virtualpos_subscription_id);

                // Usar datos de VirtualPos, o como fallback los datos locales
                $chargeProgram = $virtualPosData['charge_program'] ?? $subscription->charge_program ?? [];

                $paidAmountFromSubscription = 0;
                foreach ($chargeProgram as $charge) {
                    $status = strtolower($charge['status'] ?? '');
                    if ($status === 'pagado') {
                        $paidAmountFromSubscription += $charge['amount'] ?? 0;
                    }
                }

                Log::info('Reembolso: Validación contra suscripción activa', [
                    'participant_id' => $participantId,
                    'program_id' => $programId,
                    'subscription_id' => $subscription->id,
                    'subscription_paid_amount' => $paidAmountFromSubscription,
                    'refund_amount' => $refundAmount,
                    'using_local_data' => empty($virtualPosData['charge_program'])
                ]);

                // Validar que el reembolso no exceda lo pagado en la suscripción
                if ($refundAmount > $paidAmountFromSubscription) {
                    return [
                        'valid' => false,
                        'error' => "El monto del reembolso ({$refundAmount}) excede el monto pagado de la suscripción ({$paidAmountFromSubscription})"
                    ];
                }

                return ['valid' => true];

            } catch (\Exception $e) {
                Log::error('Reembolso: Error validando contra suscripción', [
                    'participant_id' => $participantId,
                    'program_id' => $programId,
                    'error' => $e->getMessage()
                ]);
                // Continuar con validación normal si falla la consulta a VirtualPos
            }
        }

        // Si no hay suscripción activa, validar contra el monto total pagado
        Log::info('Reembolso: Validación contra monto total pagado', [
            'participant_id' => $participantId,
            'program_id' => $programId,
            'paid_amount' => $paidAmount,
            'refund_amount' => $refundAmount
        ]);

        // Para reembolsos sin suscripción, validar que no se reembolse más de lo pagado
        if ($refundAmount > $paidAmount) {
            return [
                'valid' => false,
                'error' => "El monto del reembolso ({$refundAmount}) excede el monto total pagado ({$paidAmount})"
            ];
        }

        return ['valid' => true];
    }

    /**
     * Buscar o crear orden
     */
    private function findOrCreateOrder(Participant $participant, Program $program, float $totalAmount, float $refundAmount, array $data): Order
    {
        // Siempre crear una nueva orden para reembolsos
        $order = Order::create([
            'participant_id' => $participant->id,
            'program_id' => $program->id,
            'order_number' => app(\App\Services\Shared\OrderNumberGenerator::class)->generate(),
            'total_amount' => $totalAmount,
            'final_amount' => $totalAmount,
            'discount' => 0,
            'total_installments' => 1, // Se ajustará si corresponde al reestructurar
            'payment_type' => 'total',
            'status' => 'pending',
            'notes' => 'Orden creada desde reembolso',
            
            // Campos fiscales
            'sii_code' => $data['sii_code'],
            'document_number' => $data['document_number'],
            'total_amount_fiscal' => $data['total_amount'],
            
            'created_at' => now(),
            'updated_at' => now()
        ]);

        return $order;
    }

    /**
     * Generar número de orden único para reembolsos
     */
    // Eliminado: generación local de número de orden. Usar OrderNumberGenerator central.

    /**
     * Crear detalle de la orden
     */
    private function createOrderDetail(Order $order, PaymentOption $paymentOption, PaymentGateway $paymentGateway, array $data): OrderDetail
    {
        return OrderDetail::create([
            'order_id' => $order->id,
            'payment_option_id' => $paymentOption->id,
            'payment_gateway_id' => $paymentGateway->id,
            
            // Campos obligatorios del cliente
            'name' => $data['client_name'] ?? 'Reembolso',
            'email' => 'refund@latitud90.cl',
            'country' => null,
            'region' => null,
            'city' => null,
            'code_phone' => '+56',
            'phone' => null,
            // Tipo de documento del cliente
            'document_type' => null,
            'document_number' => $data['client_rut'] ?? null,
            
            // Información de la cuota (para reembolsos, usamos valores por defecto)
            'installment_number' => null, // Reembolso no es una cuota
            'base_amount' => $data['amount'],
            'discount_amount' => 0,
            'amount' => $data['amount'],
            'due_date' => now()->format('Y-m-d'),
            'is_paid' => true,
            'paid_at' => now(),
            'status' => 'paid',
            
            // Información de transacción
            'transaction_id' => 'REFUND-' . time() . '-' . rand(1000, 9999),
            'gateway_response' => [
                'created_manually' => true,
                'payment_type' => 'refund',
                'refund_reason' => 'Nota de crédito procesada manualmente',
                'fiscal_data' => [
                    'sii_code' => $data['sii_code'] ?? null,
                    'document_number' => $data['document_number'] ?? null,
                    'total_amount' => $data['total_amount'] ?? null,
                ],
                'client_data' => [
                    'name' => $data['client_name'] ?? null,
                    'rut' => $data['client_rut'] ?? null,
                ]
            ],
        ]);
    }

    /**
     * Crear el reembolso (como un pago con monto negativo para la lógica interna)
     */
    private function createRefund(Order $order, OrderDetail $orderDetail, PaymentGateway $paymentGateway, PaymentOption $paymentOption, array $data): Payment
    {
        return Payment::create([
            'order_id' => $order->id,
            'order_detail_id' => $orderDetail->id,
            'payment_gateway_id' => $paymentGateway->id,
            'payment_option_id' => $paymentOption->id,
            'buy_order' => $order->order_number,
            // IMPORTANTE: Almacenamos el monto como negativo para diferenciarlo de pagos normales
            'amount' => -abs($data['amount']),
            'status' => 'completed',
            'transaction_date' => Carbon::parse($data['transaction_date']),
            'authorization_code' => $data['authorization_code'] ?? null,
            'payment_code' => $data['payment_code'],
            'gateway_response' => [
                'created_manually' => true,
                'payment_type' => 'refund',
                'refund_reason' => 'Nota de crédito procesada manualmente',
                'fiscal_data' => [
                    'sii_code' => $data['sii_code'] ?? null,
                    'document_number' => $data['document_number'] ?? null,
                    'total_amount' => $data['total_amount'] ?? null,
                ],
                'client_data' => [
                    'name' => $data['client_name'] ?? null,
                    'rut' => $data['client_rut'] ?? null,
                ]
            ],
            'currency' => 'CLP',
            'document_type' => 'BC',
        ]);
    }

    /**
     * Manejar plan de cuotas y reestructurar
     * NOTA: Este método NO se usa para reembolsos
     * Los reembolsos no crean cuotas automáticamente
     */
    private function handleInstallmentPlan(Order $order, Participant $participant, Program $program, float $totalAmount, float $newPaidAmount): void
    {
        // Buscar plan de cuotas existente del participante/programa
        $installmentPlan = InstallmentPlan::where('participant_id', $participant->id)
            ->where('program_id', $program->id)
            ->first();

        if (!$installmentPlan) {
            // Crear nuevo plan de cuotas si no existe
            $installmentPlan = InstallmentPlan::create([
                'order_id' => $order->id,
                'program_id' => $program->id,
                'participant_id' => $participant->id,
                'total_amount' => $totalAmount,
                'total_installments' => 1, // Se ajustará en la redistribución
                'payment_type' => 'monthly',
                'status' => 'active',
                'start_date' => now(),
                'notes' => 'Plan de cuotas creado desde reembolso'
            ]);

            // Crear cuota inicial
            Installment::create([
                'installment_plan_id' => $installmentPlan->id,
                'installment_number' => 1,
                'amount' => $totalAmount,
                'due_date' => now(),
                'status' => 'pending',
                'notes' => 'Cuota inicial creada por reembolso'
            ]);
        }

        // Calcular nuevo saldo pendiente
        $newBalance = $totalAmount - $newPaidAmount;
        
        // Si el nuevo monto pagado es negativo (más reembolsos que pagos), ajustar el total
        if ($newPaidAmount < 0) {
            $adjustedTotal = $totalAmount + abs($newPaidAmount);
            $installmentPlan->update(['total_amount' => $adjustedTotal]);
            $newBalance = $adjustedTotal;
        }

        // Obtener cuotas pendientes
        $pendingInstallments = Installment::where('installment_plan_id', $installmentPlan->id)
                                         ->where('status', 'pending')
                                         ->orderBy('due_date')
                                         ->get();

        if ($pendingInstallments->count() > 0) {
            // Redistribuir el saldo pendiente entre las cuotas
            $amountPerInstallment = round($newBalance / $pendingInstallments->count(), 0);
            $remainder = $newBalance - ($amountPerInstallment * $pendingInstallments->count());
            
            foreach ($pendingInstallments as $index => $installment) {
                $amount = $amountPerInstallment;
                
                // Agregar el residuo a la última cuota
                if ($index === $pendingInstallments->count() - 1) {
                    $amount += $remainder;
                }
                
                $installment->update([
                    'amount' => max($amount, 0), // Evitar montos negativos
                    'updated_at' => now()
                ]);
            }
        }

        Log::info('Plan de cuotas reestructurado después del reembolso', [
            'installment_plan_id' => $installmentPlan->id,
            'order_id' => $order->id,
            'total_amount' => $totalAmount,
            'new_paid_amount' => $newPaidAmount,
            'new_balance' => $newBalance,
            'pending_installments_count' => $pendingInstallments->count()
        ]);
    }

    /**
     * Manejar ajuste de suscripción activa cuando se hace un reembolso
     * Los reembolsos AUMENTAN la deuda pendiente
     */
    private function handleSubscriptionAdjustmentForRefund(int $participantId, int $programId, float $refundAmount): ?array
    {
        try {
            $recalculationService = app(SubscriptionRecalculationService::class);

            // Procesar ajuste de suscripción (tipo 'refund' aumenta la deuda)
            $result = $recalculationService->processPaymentWithSubscriptionAdjustment(
                $participantId,
                $programId,
                $refundAmount,
                'refund'
            );

            if (!$result['has_subscription']) {
                Log::info('Reembolso: No hay suscripción activa para ajustar', [
                    'participant_id' => $participantId,
                    'program_id' => $programId
                ]);
                return null;
            }

            if ($result['subscription_cancelled']) {
                Log::info('Reembolso: Suscripción cancelada por ajuste', [
                    'participant_id' => $participantId,
                    'program_id' => $programId,
                    'old_subscription_id' => $result['old_subscription_id'],
                    'new_amount' => $result['new_amount'],
                    'new_installments' => $result['new_installments']
                ]);

                // Crear nueva suscripción con el monto ajustado (aumentado por el reembolso)
                $newSubscriptionResult = $recalculationService->createNewSubscription(
                    $participantId,
                    $programId,
                    $result['new_amount'],
                    $result['new_installments'],
                    $result['old_plan_id'] ?? null // Usar el mismo plan de la suscripción original
                );

                if ($newSubscriptionResult['success']) {
                    Log::info('Reembolso: Nueva suscripción creada exitosamente', [
                        'new_subscription_id' => $newSubscriptionResult['subscription']->id,
                        'virtualpos_id' => $newSubscriptionResult['subscription']->virtualpos_subscription_id
                    ]);
                }

                return $newSubscriptionResult;
            }

            return $result;

        } catch (\Exception $e) {
            Log::error('Reembolso: Error manejando ajuste de suscripción', [
                'participant_id' => $participantId,
                'program_id' => $programId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            // No lanzar excepción - el reembolso ya se procesó correctamente
            // Solo logear el error para revisión
            return null;
        }
    }
}
