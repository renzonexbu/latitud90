<?php

namespace App\Services\Admin\Installments;

use App\Models\Installment;
use App\Models\Payment;
use App\Models\Order;
use App\Models\OrderDetail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

class RegisterManualPaymentService
{
    /**
     * Registrar un pago manual para una cuota específica
     *
     * @param int $installmentId ID de la cuota a pagar
     * @param array $paymentData Datos del pago manual:
     *   - amount: Monto pagado
     *   - payment_source: Tipo de pago manual (manual_cash, manual_transfer, etc.)
     *   - payment_date: Fecha del pago (opcional, default: hoy)
     *   - notes: Notas adicionales (opcional)
     *   - payment_gateway_id: ID del gateway si aplica (opcional)
     *   - transaction_reference: Referencia de transacción (opcional)
     *
     * @return array
     * @throws Exception
     */
    public function registerManualPayment(int $installmentId, array $paymentData): array
    {
        try {
            DB::beginTransaction();

            // 1. Validar cuota
            $installment = Installment::with(['installmentPlan.order'])->find($installmentId);

            if (!$installment) {
                throw new Exception('Cuota no encontrada');
            }

            if ($installment->is_paid) {
                throw new Exception('Esta cuota ya está marcada como pagada');
            }

            // 2. Validar payment_source
            $allowedSources = ['manual_cash', 'manual_transfer', 'manual_online', 'manual_check', 'manual_other'];
            $paymentSource = $paymentData['payment_source'] ?? 'manual_cash';

            if (!in_array($paymentSource, $allowedSources)) {
                throw new Exception('Tipo de pago manual no válido. Debe ser: ' . implode(', ', $allowedSources));
            }

            $amount = (float) ($paymentData['amount'] ?? $installment->amount);
            $paymentDate = $paymentData['payment_date'] ?? now();
            $notes = $paymentData['notes'] ?? null;
            $transactionReference = $paymentData['transaction_reference'] ?? null;
            $paymentGatewayId = $paymentData['payment_gateway_id'] ?? null;

            // 3. Obtener la orden asociada
            $order = $installment->installmentPlan->order;

            if (!$order) {
                throw new Exception('No se encontró la orden asociada a esta cuota');
            }

            // 4. Crear registro de Payment
            $payment = Payment::create([
                'order_id' => $order->id,
                'buy_order' => $order->order_number . '-I' . $installment->installment_number,
                'amount' => $amount,
                'status' => 'approved', // Pago manual se marca como aprobado inmediatamente
                'payment_method_id' => 1, // Puede ser ajustado según el tipo
                'payment_gateway_id' => $paymentGatewayId,
                'transaction_reference' => $transactionReference,
                'transaction_date' => $paymentDate,
                'paid_at' => $paymentDate,
                'installments_number' => 1,
                'notes' => $notes,
            ]);

            // 5. Crear OrderDetail
            $orderDetail = OrderDetail::create([
                'order_id' => $order->id,
                'payment_id' => $payment->id,
                'installment_id' => $installment->id,
                'description' => 'Pago manual de cuota ' . $installment->installment_number,
                'quantity' => 1,
                'unit_price' => $amount,
                'total_price' => $amount,
                'payment_method' => $this->getPaymentMethodLabel($paymentSource),
                'notes' => $notes,
            ]);

            // 6. Actualizar la cuota
            $installment->update([
                'is_paid' => true,
                'paid_at' => $paymentDate,
                'status' => 'paid',
                'payment_id' => $payment->id,
                'payment_order_id' => $order->id,
                'payment_order_detail_id' => $orderDetail->id,
                'payment_source' => $paymentSource,
                'notes' => $notes ? ($installment->notes ? $installment->notes . "\n" . $notes : $notes) : $installment->notes,
            ]);

            DB::commit();

            Log::info('✅ Pago manual registrado exitosamente', [
                'installment_id' => $installment->id,
                'installment_number' => $installment->installment_number,
                'payment_id' => $payment->id,
                'payment_source' => $paymentSource,
                'amount' => $amount,
                'order_id' => $order->id,
                'participant_id' => $order->participant_id,
            ]);

            return [
                'success' => true,
                'message' => 'Pago manual registrado exitosamente',
                'data' => [
                    'installment_id' => $installment->id,
                    'payment_id' => $payment->id,
                    'order_detail_id' => $orderDetail->id,
                    'amount' => $amount,
                    'payment_source' => $paymentSource,
                    'payment_date' => $paymentDate,
                ]
            ];

        } catch (Exception $e) {
            DB::rollBack();

            Log::error('❌ Error registrando pago manual', [
                'installment_id' => $installmentId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'message' => 'Error al registrar el pago manual: ' . $e->getMessage(),
                'data' => null
            ];
        }
    }

    /**
     * Obtener etiqueta de método de pago según payment_source
     */
    private function getPaymentMethodLabel(string $paymentSource): string
    {
        $labels = [
            'manual_cash' => 'Efectivo',
            'manual_transfer' => 'Transferencia Bancaria',
            'manual_online' => 'Pago Online Manual',
            'manual_check' => 'Cheque',
            'manual_other' => 'Otro',
        ];

        return $labels[$paymentSource] ?? 'Manual';
    }
}
