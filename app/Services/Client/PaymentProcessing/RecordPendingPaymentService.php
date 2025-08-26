<?php

namespace App\Services\Client\PaymentProcessing;

use App\Models\OrderDetail;
use App\Models\Payment;
use App\Traits\SystemLogging;

class RecordPendingPaymentService
{
    use SystemLogging;

    /**
     * Registrar pago pendiente en payments
     *
     * @param OrderDetail $orderDetail
     * @param string $gatewayType
     * @param array $gatewayResult
     * @return void
     */
    public function execute(OrderDetail $orderDetail, string $gatewayType, array $gatewayResult): void
    {
        try {
            // Obtener el número de orden correcto desde la orden
            $order = $orderDetail->order;
            $buyOrder = $order->order_number;



            $commonData = [
                'order_id' => $orderDetail->order_id,
                'order_detail_id' => $orderDetail->id,
                'payment_gateway_id' => $orderDetail->payment_gateway_id,
                'payment_option_id' => $orderDetail->payment_option_id,
                'amount' => $orderDetail->amount,
                'currency' => 'CLP',
                'status' => 'pending',
                'buy_order' => $buyOrder,
                'gateway_response' => $gatewayResult,
            ];

            // Buscar pago existente por order_detail_id
            $existingPayment = Payment::where('order_detail_id', $orderDetail->id)
                ->latest()
                ->first();

            if ($existingPayment) {
                // Actualizar el pago existente
                $updateData = [
                    'status' => 'pending',
                    'gateway_response' => $gatewayResult,
                ];

                if ($gatewayType === 'khipu') {
                    $paymentId = $gatewayResult['payment_id'] ?? null;
                    if (!$paymentId) {
                        $paymentUrl = $gatewayResult['payment_url'] ?? $gatewayResult['url'] ?? '';
                        if (is_string($paymentUrl) && $paymentUrl !== '') {
                            $parts = explode('/', rtrim($paymentUrl, '/'));
                            $paymentId = end($parts) ?: null;
                        }
                    }
                    $updateData['external_payment_id'] = $paymentId;
                } else if ($gatewayType === 'virtualpos') {
                    $paymentId = $gatewayResult['payment_id'] ?? null;
                    $updateData['external_payment_id'] = $paymentId;
                    $updateData['token'] = $gatewayResult['token'] ?? null;
                } else {
                    $updateData['token'] = $gatewayResult['token'] ?? null;
                }

                $existingPayment->update($updateData);
                $payment = $existingPayment;
            } else {
                // Crear nuevo pago solo si no existe
                if ($gatewayType === 'khipu') {
                    $paymentId = $gatewayResult['payment_id'] ?? null;
                    if (!$paymentId) {
                        $paymentUrl = $gatewayResult['payment_url'] ?? $gatewayResult['url'] ?? '';
                        if (is_string($paymentUrl) && $paymentUrl !== '') {
                            $parts = explode('/', rtrim($paymentUrl, '/'));
                            $paymentId = end($parts) ?: null;
                        }
                    }
                    $data = array_merge($commonData, [
                        'external_payment_id' => $paymentId,
                    ]);
                } else if ($gatewayType === 'virtualpos') {
                    $paymentId = $gatewayResult['payment_id'] ?? null;
                    $data = array_merge($commonData, [
                        'external_payment_id' => $paymentId,
                        'token' => $gatewayResult['token'] ?? null,
                    ]);
                } else {
                    $data = array_merge($commonData, [
                        'token' => $gatewayResult['token'] ?? null,
                    ]);
                }

                $payment = Payment::create($data);
            }

            $this->logInfo('Payment record created successfully', [
                'payment_id' => $payment->id,
                'payment_gateway_id' => $payment->payment_gateway_id,
                'payment_option_id' => $payment->payment_option_id,
                'gateway_type' => $gatewayType
            ]);
        } catch (\Throwable $e) {
            $this->logError('Error recording pending payment', [
                'error' => $e->getMessage(),
                'order_detail_id' => $orderDetail->id,
            ]);
        }
    }
}
