<?php

namespace App\Services\Admin\Installments;

use App\Models\Installment;
use App\Models\Payment;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Traits\AdminLogging;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

class RegisterManualPaymentService
{
    use AdminLogging;
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
            $installment = Installment::with(['installmentPlan.order.participant'])->find($installmentId);

            if (!$installment) {
                throw new Exception('Cuota no encontrada');
            }

            if ($installment->status === 'paid') {
                throw new Exception('Esta cuota ya está marcada como pagada');
            }

            // 2. Validar payment_source
            $allowedSources = [
                'manual_card',              // VP - Link TD/TC (Tarjeta Débito/Crédito con cuotas)
                'manual_transfer',          // TE, KP - Transferencia bancaria / Khipu
                'manual_card_office',       // TC, BX - POS Oficina / Tarjeta presencial
                'manual_international',     // VPI - Link Internacional
                'manual_deposit',           // DP - Depósito
                'manual_webpay',            // WP - Webpay
                'manual_subscription',      // PAT - Suscripción Cuotas
            ];
            $paymentSource = $paymentData['payment_source'] ?? 'manual_card';

            if (!in_array($paymentSource, $allowedSources)) {
                throw new Exception('Tipo de pago manual no válido. Debe ser uno de: ' . implode(', ', $allowedSources));
            }

            $amount = (float) ($paymentData['amount'] ?? $installment->amount);
            $paymentDate = $paymentData['payment_date'] ?? now();
            $notes = $paymentData['notes'] ?? null;
            $transactionReference = $paymentData['transaction_reference'] ?? null;
            $paymentGatewayId = $paymentData['payment_gateway_id'] ?? null;

            // Obtener el payment_option_id correspondiente al payment_source
            $paymentOptionId = $this->getPaymentOptionIdBySource($paymentSource);

            // 3. Obtener la orden y participante asociados
            $order = $installment->installmentPlan->order;

            if (!$order) {
                throw new Exception('No se encontró la orden asociada a esta cuota');
            }

            $participant = $order->participant;

            if (!$participant) {
                throw new Exception('No se encontró el participante asociado a esta orden');
            }

            // 4. Crear OrderDetail primero (Payment lo requiere)
            // Usar buyer_data si está disponible desde $paymentData
            // Si no hay datos de buyer, usar datos del participante como fallback
            $buyerName = $paymentData['buyer_name'] ?? ($participant->first_name . ' ' . $participant->last_name);

            // Para email, solo usar el del paymentData si es un email válido
            $buyerEmailFromData = $paymentData['buyer_email'] ?? null;
            $buyerEmail = ($buyerEmailFromData && filter_var($buyerEmailFromData, FILTER_VALIDATE_EMAIL))
                ? $buyerEmailFromData
                : $participant->email;

            // Para los demás campos, usar NULL si no están en paymentData
            // porque el participante puede tener datos en formato incorrecto (códigos en lugar de IDs)
            $buyerPhone = $paymentData['buyer_phone'] ?? null;
            $buyerCodePhone = $paymentData['buyer_code_phone'] ?? null;
            $buyerDocumentType = $paymentData['buyer_document_type'] ?? null;
            $buyerDocumentNumber = $paymentData['buyer_document_number'] ?? null;
            $buyerCountry = $paymentData['buyer_country'] ?? null;
            $buyerRegion = $paymentData['buyer_region'] ?? null;
            $buyerCity = $paymentData['buyer_city'] ?? null;

            $orderDetail = OrderDetail::create([
                'order_id' => $order->id,
                'installment_number' => $installment->installment_number,
                'amount' => $amount,
                'due_date' => $installment->due_date,
                'status' => 'paid',
                'is_paid' => true,
                'paid_at' => $paymentDate,
                'name' => $buyerName,
                'email' => $buyerEmail,
                'phone' => $buyerPhone,
                'code_phone' => $buyerCodePhone,
                'document_type' => $buyerDocumentType,
                'document_number' => $buyerDocumentNumber,
                'country' => $buyerCountry,
                'region' => $buyerRegion,
                'city' => $buyerCity,
            ]);

            // 5. Crear registro de Payment con el order_detail_id
            $payment = Payment::create([
                'order_id' => $order->id,
                'order_detail_id' => $orderDetail->id,
                'buy_order' => $order->order_number . '-I' . $installment->installment_number,
                'amount' => $amount,
                'status' => 'approved', // Pago manual se marca como aprobado inmediatamente
                'payment_gateway_id' => $paymentGatewayId,
                'payment_option_id' => $paymentOptionId, // Asociar con la opción de pago correcta
                'transaction_date' => $paymentDate,
                'installments_number' => 1,
                'document_type' => 'B2', // Boleta por defecto para pagos manuales
            ]);

            // 6. Actualizar la cuota
            $installment->update([
                'paid_at' => $paymentDate,
                'status' => 'paid',
                'is_paid' => true,
                'payment_id' => $payment->id,
                'payment_order_id' => $order->id,
                'payment_order_detail_id' => $orderDetail->id,
                'notes' => $notes ? ($installment->notes ? $installment->notes . "\n" . $notes : $notes) : $installment->notes,
            ]);

            // 7. Actualizar el estado de la orden basado en el progreso de pago
            $order->refreshStatus();

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

            // Admin logging
            $this->logCreate(
                'payments',
                'Payment',
                $payment->id,
                "Pago manual registrado para cuota #{$installment->installment_number} del participante {$participant->name}",
                [
                    'payment_source' => $paymentSource,
                    'amount' => $amount,
                    'payment_date' => $paymentDate,
                    'transaction_reference' => $transactionReference
                ],
                [
                    'installment_id' => $installment->id,
                    'installment_number' => $installment->installment_number,
                    'order_id' => $order->id,
                    'participant_id' => $participant->id,
                    'participant_name' => $participant->name,
                    'notes' => $notes
                ]
            );

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
     * Coincide con los códigos de reporte del PaymentOptionSeeder
     */
    private function getPaymentMethodLabel(string $paymentSource): string
    {
        $labels = [
            'manual_card' => 'Link TD/TC (VP)',
            'manual_transfer' => 'Transferencia Bancaria / Khipu (TE/KP)',
            'manual_card_office' => 'POS Oficina / Tarjeta Presencial (TC/BX)',
            'manual_international' => 'Link Internacional (VPI)',
            'manual_deposit' => 'Depósito (DP)',
            'manual_webpay' => 'Webpay (WP)',
            'manual_subscription' => 'Suscripción Cuotas (PAT)',
        ];

        return $labels[$paymentSource] ?? 'Manual';
    }

    /**
     * Obtener todas las opciones de pago manual disponibles
     * Útil para poblar selectores en el frontend
     * Incluye todos los códigos de forma de pago del sistema
     */
    public static function getPaymentSourceOptions(): array
    {
        return [
            ['value' => 'manual_card_office', 'label' => 'POS Oficina (TC)', 'report_code' => 'TC'],
            ['value' => 'manual_transfer', 'label' => 'Link KP (KP)', 'report_code' => 'KP'],
            ['value' => 'manual_subscription', 'label' => 'Suscripción Cuotas (PAT)', 'report_code' => 'PAT'],
            ['value' => 'manual_transfer', 'label' => 'Transferencia Banco (TE)', 'report_code' => 'TE'],
            ['value' => 'manual_card', 'label' => 'Link TD/TC (VP)', 'report_code' => 'VP'],
            ['value' => 'manual_international', 'label' => 'Link Internacional (VPI)', 'report_code' => 'VPI'],
            ['value' => 'manual_deposit', 'label' => 'Depósito (DP)', 'report_code' => 'DP'],
            ['value' => 'manual_webpay', 'label' => 'Webpay (WP)', 'report_code' => 'WP'],
            ['value' => 'manual_aporte', 'label' => 'Aporte (AP)', 'report_code' => 'AP'],
        ];
    }

    /**
     * Obtener el payment_option_id basándose en el payment_source
     * Mapea los códigos internos (manual_*) a las opciones de pago presenciales en la BD
     */
    private function getPaymentOptionIdBySource(string $paymentSource): ?int
    {
        // Mapeo de payment_source a código de payment_option
        $mapping = [
            'manual_card_office' => 'presential_pos_office',         // TC
            'manual_transfer' => 'presential_bank_transfer',         // TE/KP (por defecto TE)
            'manual_subscription' => 'presential_subscription',      // PAT
            'manual_card' => 'presential_debit_credit',              // VP
            'manual_international' => 'presential_international',    // VPI
            'manual_deposit' => 'presential_deposit',                // DP
            'manual_webpay' => 'presential_webpay',                  // WP
            'manual_aporte' => 'presential_aporte',                  // AP
        ];

        $paymentOptionCode = $mapping[$paymentSource] ?? null;

        if (!$paymentOptionCode) {
            return null;
        }

        // Buscar el payment_option_id en la base de datos
        $paymentOption = \App\Models\PaymentOption::where('code', $paymentOptionCode)->first();

        return $paymentOption?->id;
    }
}
