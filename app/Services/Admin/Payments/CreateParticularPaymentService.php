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
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class CreateParticularPaymentService
{
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
            $participant = Participant::findOrFail($data['participant_id']);
            $program = Program::findOrFail($data['program_id']);
            
            // Para pagos presenciales, usar gateway y option fijos
            $paymentGateway = PaymentGateway::where('code', 'presencial')->firstOrFail();
            $paymentOption = PaymentOption::where('code', 'full_debit_credit_0')->firstOrFail();

            // Calcular montos del participante
            $priceData = ParticipantPriceHelper::calculateParticipantPrice($participant, $program);
            $totalAmount = $priceData['final_price'];
            
            // Calcular monto ya pagado
            $paidAmount = $this->calculatePaidAmount($participant->id, $program->id);
            $previousBalance = max($totalAmount - $paidAmount, 0);
            
            // Validar que el monto del pago no exceda el saldo pendiente
            if ($data['amount'] > $previousBalance) {
                throw new \Exception("El monto del pago ({$data['amount']}) excede el saldo pendiente ({$previousBalance})");
            }

            // Buscar o crear la orden
            $order = $this->findOrCreateOrder($participant, $program, $totalAmount, $data['amount']);

            // Crear el detalle de la orden
            $orderDetail = $this->createOrderDetail($order, $paymentOption, $paymentGateway, $data);

            // Crear el pago
            $payment = $this->createPayment($order, $orderDetail, $paymentGateway, $paymentOption, $data);

            // Crear o actualizar plan de cuotas y reestructurar
            $this->handleInstallmentPlan($order, $participant, $program, $totalAmount, $paidAmount + $data['amount']);

            // Actualizar estado de la orden
            $order->refreshStatus();

            DB::commit();

            Log::info('Pago presencial creado exitosamente', [
                'payment_id' => $payment->id,
                'order_id' => $order->id,
                'participant_id' => $participant->id,
                'program_id' => $program->id,
                'amount' => $data['amount'],
                'payment_code' => $data['payment_code'] ?? null
            ]);

            return [
                'success' => true,
                'payment' => $payment,
                'order' => $order,
                'order_detail' => $orderDetail,
                'total_amount' => $totalAmount,
                'paid_amount' => $paidAmount + $data['amount'],
                'remaining_balance' => $previousBalance - $data['amount']
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
            'participant_id', 'program_id', 'amount', 'payment_gateway_id', 
            'payment_option_id', 'transaction_date', 'payment_code'
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
     * Buscar o crear orden
     */
    private function findOrCreateOrder(Participant $participant, Program $program, float $totalAmount, float $paymentAmount): Order
    {
        // Siempre crear una nueva orden para pagos presenciales
        $order = Order::create([
            'participant_id' => $participant->id,
            'program_id' => $program->id,
            'total_amount' => $totalAmount,
            'discount' => 0,
            'final_amount' => $totalAmount,
            'total_installments' => 1, // Se ajustará según el plan de cuotas
            'payment_type' => 'total',
            'status' => 'pending',
            'order_number' => $this->generateOrderNumber(),
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
            'name' => $data['buyer_full_name'] ?? 'Comprador',
            'email' => $data['buyer_email'] ?? '',
            'country' => $data['buyer_country'] ?? null,
            'region' => $data['buyer_region'] ?? null,
            'city' => $data['buyer_city'] ?? null,
            'code_phone' => $data['buyer_code_phone'] ?? null,
            'phone' => $data['buyer_phone'] ?? null,
            'document_type' => $data['buyer_document_type'] ?? null,
            'document_number' => $data['buyer_document_number'] ?? null,
            'installment_number' => 1,
            'installments_number' => 1,
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
                'payment_type' => 'presential'
            ]
        ]);
    }

    /**
     * Crear el pago
     */
    private function createPayment(Order $order, OrderDetail $orderDetail, PaymentGateway $paymentGateway, PaymentOption $paymentOption, array $data): Payment
    {
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
            'payment_code' => $data['payment_code'],
            'gateway_response' => [
                'notes' => $data['notes'] ?? null,
                'created_manually' => true,
                'payment_type' => 'presential',
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
        ]);
    }

    /**
     * Manejar plan de cuotas y reestructurar
     */
    private function handleInstallmentPlan(Order $order, Participant $participant, Program $program, float $totalAmount, float $newPaidAmount): void
    {
        // Buscar plan de cuotas existente
        $installmentPlan = InstallmentPlan::where('participant_id', $participant->id)
                                         ->where('program_id', $program->id)
                                         ->first();

        if (!$installmentPlan) {
            // Crear nuevo plan de cuotas si no existe
            $installmentPlan = $this->createInstallmentPlan($order, $participant, $program, $totalAmount);
        }

        // Reestructurar cuotas pendientes
        $this->restructureInstallments($installmentPlan, $totalAmount, $newPaidAmount);
    }

    /**
     * Crear nuevo plan de cuotas
     */
    private function createInstallmentPlan(Order $order, Participant $participant, Program $program, float $totalAmount): InstallmentPlan
    {
        $installmentPlan = InstallmentPlan::create([
            'order_id' => $order->id,
            'program_id' => $program->id,
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
