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
            
            // Para reembolsos, usar gateway ID 4 (refund) y opción de pago ID 19 (refund_credit_note)
            $paymentGateway = PaymentGateway::findOrFail(4);
            $paymentOption = PaymentOption::findOrFail(19);

            // Calcular montos del participante
            $priceData = ParticipantPriceHelper::calculateParticipantPrice($participant, $program);
            $totalAmount = $priceData['final_price'];
            
            // Calcular monto ya pagado
            $paidAmount = $this->calculatePaidAmount($participant->id, $program->id);
            $previousBalance = max($totalAmount - $paidAmount, 0);
            
            // Para reembolsos, no validamos límites superiores ya que se SUMA a la deuda
            if ($data['amount'] <= 0) {
                throw new \Exception("El monto del reembolso debe ser mayor a 0");
            }

            // Buscar o crear la orden
            $order = $this->findOrCreateOrder($participant, $program, $totalAmount, $data['amount']);

            // Crear el detalle de la orden
            $orderDetail = $this->createOrderDetail($order, $paymentOption, $paymentGateway, $data);

            // Crear el reembolso (como un pago negativo)
            $refund = $this->createRefund($order, $orderDetail, $paymentGateway, $paymentOption, $data);

            // LÓGICA CLAVE: Para reembolsos, RESTAMOS el monto pagado (equivale a SUMAR a la deuda)
            // Si antes había pagado 100.000 y reembolsamos 50.000, ahora efectivamente ha pagado 50.000
            $newPaidAmount = $paidAmount - $data['amount'];
            
            // Crear o actualizar plan de cuotas y reestructurar
            $this->handleInstallmentPlan($order, $participant, $program, $totalAmount, $newPaidAmount);

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
        $required = ['participant_id', 'program_id', 'amount', 'payment_code', 'transaction_date'];
        
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
     * Buscar o crear orden
     */
    private function findOrCreateOrder(Participant $participant, Program $program, float $totalAmount, float $refundAmount): Order
    {
        // Siempre crear una nueva orden para reembolsos
        $order = Order::create([
            'participant_id' => $participant->id,
            'program_id' => $program->id,
            'order_number' => $this->generateOrderNumber(),
            'total_amount' => $totalAmount,
            'final_amount' => $totalAmount,
            'discount' => 0,
            'total_installments' => 1, // Se ajustará si corresponde al reestructurar
            'payment_type' => 'total',
            'status' => 'pending',
            'notes' => 'Orden creada desde reembolso',
            'created_at' => now(),
            'updated_at' => now()
        ]);

        return $order;
    }

    /**
     * Generar número de orden único para reembolsos
     */
    private function generateOrderNumber(): string
    {
        $prefix = 'REF';
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

    /**
     * Crear detalle de la orden
     */
    private function createOrderDetail(Order $order, PaymentOption $paymentOption, PaymentGateway $paymentGateway, array $data): OrderDetail
    {
        return OrderDetail::create([
            'order_id' => $order->id,
            'payment_option_id' => $paymentOption->id,
            'payment_gateway_id' => $paymentGateway->id,
            
            // Campos obligatorios del comprador
            'name' => $data['buyer_full_name'] ?? 'Reembolso',
            'email' => $data['buyer_email'] ?? 'refund@latitud90.cl',
            'country' => $data['buyer_country'] ?? null,
            'region' => $data['buyer_region'] ?? null,
            'city' => $data['buyer_city'] ?? null,
            'code_phone' => $data['buyer_code_phone'] ?? '+56',
            'phone' => $data['buyer_phone'] ?? null,
            // Tipo de documento del comprador (FK numérica a tabla document)
            'document_type' => $data['buyer_document_type'] ?? null,
            'document_number' => $data['buyer_document_number'] ?? null,
            
            // Información de la cuota (para reembolsos, usamos valores por defecto)
            'installment_number' => 1,
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
                'notes' => $data['notes'] ?? null,
                'created_manually' => true,
                'payment_type' => 'refund',
                'refund_reason' => $data['notes'] ?? 'Reembolso procesado manualmente',
                'buyer_data' => [
                    'full_name' => $data['buyer_full_name'] ?? null,
                    'document_type' => $data['buyer_document_type'] ?? null,
                    'document_number' => $data['buyer_document_number'] ?? null,
                    'email' => $data['buyer_email'] ?? null,
                    'phone' => $data['buyer_phone'] ?? null,
                    'country' => $data['buyer_country'] ?? null,
                    'region' => $data['buyer_region'] ?? null,
                    'city' => $data['buyer_city'] ?? null,
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
                'notes' => $data['notes'] ?? null,
                'created_manually' => true,
                'payment_type' => 'refund',
                'refund_reason' => $data['notes'] ?? 'Reembolso procesado manualmente',
                'buyer_data' => [
                    'full_name' => $data['buyer_full_name'] ?? null,
                    'document_type' => $data['buyer_document_type'] ?? null,
                    'document_number' => $data['buyer_document_number'] ?? null,
                    'email' => $data['buyer_email'] ?? null,
                    'phone' => $data['buyer_phone'] ?? null,
                    'country' => $data['buyer_country'] ?? null,
                    'region' => $data['buyer_region'] ?? null,
                    'city' => $data['buyer_city'] ?? null,
                ]
            ],
            'currency' => 'CLP',
            'document_type' => 'BC',
        ]);
    }

    /**
     * Manejar plan de cuotas y reestructurar
     * Para reembolsos, necesitamos recalcular las cuotas considerando que ahora debe más
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
}
