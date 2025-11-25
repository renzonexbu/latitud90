<?php

namespace App\Services\Client\Payment;

use App\Models\OrderDetail;
use App\Models\Payment;
use App\Services\Client\PaymentGateway\KhipuService;
use App\Helpers\PaymentDocumentTypeHelper;
use App\Traits\SystemLogging;
use Illuminate\Http\Request;
use Carbon\Carbon;

class ConfirmKhipuService
{
    use SystemLogging;
    public function __construct(
        private KhipuService $khipuService
    ) {}

    /**
     * Confirmar transacción de Khipu
     *
     * @param Request $request
     * @return array
     */
    public function execute(Request $request): array
    {
        $request->validate([
            'orderDetailId' => 'required|integer',
            'payment_id' => 'nullable|string',
        ]);

        $orderDetailId = (int) $request->input('orderDetailId');
        $paymentId = $request->input('payment_id');

        try {
            // Resolver payment_id si no viene en el request
            if (empty($paymentId)) {
                $last = Payment::where('order_detail_id', $orderDetailId)
                    ->whereNotNull('external_payment_id')
                    ->latest()->first();
                $paymentId = $last?->external_payment_id;
            }

            if (empty($paymentId)) {
                $this->logWarning('ConfirmKhipuService: confirmKhipu missing payment_id', [
                    'order_detail_id' => $orderDetailId,
                ]);
                return [
                    'success' => false,
                    'status' => 'missing_payment_id'
                ];
            }

            $this->logInfo('ConfirmKhipuService: confirmKhipu start', [
                'order_detail_id' => $orderDetailId,
                'payment_id' => $paymentId,
            ]);

            // Backend polling: hasta 5 intentos con espera de 2s
            $attempts = 0;
            $maxAttempts = 5;
            $status = null;
            $approved = false;

            while ($attempts < $maxAttempts) {
                $attempts++;
                $this->logInfo('ConfirmKhipuService: confirmKhipu attempt', [
                    'attempt' => $attempts,
                    'order_detail_id' => $orderDetailId,
                    'payment_id' => $paymentId,
                ]);
                
                $status = $this->khipuService->getPaymentStatus($paymentId);

                // Buscar pago existente por order_detail_id (sin importar external_payment_id)
                $payment = Payment::where('order_detail_id', $orderDetailId)
                    ->latest()
                    ->first();

                if (!$payment) {
                    $orderDetail = OrderDetail::findOrFail($orderDetailId);
                    $payment = Payment::create([
                        'order_id' => $orderDetail->order_id,
                        'order_detail_id' => $orderDetail->id,
                        'payment_gateway_id' => $orderDetail->payment_gateway_id,
                        'payment_option_id' => $orderDetail->payment_option_id,
                        'amount' => $orderDetail->amount,
                        'currency' => 'CLP',
                        'status' => 'pending',
                        'buy_order' => $orderDetail->order_id . '-' . $orderDetail->installment_number,
                        'external_payment_id' => $paymentId,
                        'transaction_date' => now('America/Santiago'),
                        'document_type' => PaymentDocumentTypeHelper::determineDocumentType($orderDetail->order->program_id),
                    ]);
                } else {
                    // Actualizar el external_payment_id y transaction_date si no los tienen
                    $updateFields = [];
                    if (!$payment->external_payment_id) {
                        $updateFields['external_payment_id'] = $paymentId;
                    }
                    if (!$payment->transaction_date) {
                        $updateFields['transaction_date'] = now('America/Santiago');
                    }
                    if (!empty($updateFields)) {
                        $payment->update($updateFields);
                    }
                }

                $approved = $status['success'] === true && in_array(($status['status'] ?? ''), ['done', 'paid', 'approved', 'completed']);
                $this->logInfo('ConfirmKhipuService: confirmKhipu attempt result', [
                    'attempt' => $attempts,
                    'approved' => $approved,
                    'status' => $status['status'] ?? null,
                ]);

                $updateData = [
                    'status' => $approved ? 'approved' : ($status['status'] ?? 'pending'),
                    'gateway_response' => $status['data'] ?? $status,
                    'raw_notification' => $status['data'] ?? $status,
                ];
                
                // Agregar transaction_date si el pago es aprobado y viene en la respuesta
                if ($approved && isset($status['transaction_date'])) {
                    $updateData['transaction_date'] = $this->parseTransactionDate($status['transaction_date']);
                } elseif ($approved && !$payment->transaction_date) {
                    // Si no hay transaction_date pero el pago es aprobado, usar la fecha actual
                    $updateData['transaction_date'] = now('America/Santiago');
                }
                
                $payment->update($updateData);

                $orderDetail = $payment->orderDetail;
                if ($orderDetail) {
                    $orderDetail->update([
                        'is_paid' => $approved,
                        'status' => $approved ? 'paid' : 'pending',
                        'paid_at' => $approved ? now() : $orderDetail->paid_at,
                        'transaction_id' => $paymentId,
                        'gateway_response' => $status['data'] ?? $status,
                    ]);
                    
                    // Si el pago fue aprobado y es una cuota, marcarla como pagada
                    if ($approved && $orderDetail->installment_number >= 1) {
                        $this->markInstallmentAsPaid($orderDetail);
                    }
                    
                    // Recalcular estado de la orden
                    if ($orderDetail->order) {
                        $orderDetail->order->refreshStatus();
                    }
                }

                if ($approved) {
                    $this->logInfo('ConfirmKhipuService: confirmKhipu approved', [
                        'order_detail_id' => $orderDetailId,
                        'payment_id' => $paymentId,
                        'attempt' => $attempts,
                    ]);
                    return [
                        'success' => true,
                        'redirect' => route('payment.success', $orderDetailId),
                        'status' => $status['status'] ?? 'done',
                    ];
                }

                // Esperar antes del siguiente intento
                sleep(2);
            }

            // No aprobado tras reintentos: redirigir a vista informativa (verificación)
            $this->logWarning('ConfirmKhipuService: confirmKhipu exhausted attempts without approval', [
                'order_detail_id' => $orderDetailId,
                'payment_id' => $paymentId,
                'attempts' => $attempts,
                'last_status' => $status['status'] ?? null,
            ]);
            
            return [
                'success' => false,
                'redirect' => route('khipu.callback', ['orderDetailId' => $orderDetailId]),
                'status' => $status['status'] ?? 'pending',
            ];
        } catch (\Throwable $e) {
            $this->logError('ConfirmKhipuService: Error confirming Khipu transaction', [
                'error' => $e->getMessage(),
                'order_detail_id' => $orderDetailId,
                'payment_id' => $paymentId,
            ], $e);
            
            return [
                'success' => false,
                'redirect' => route('payment.failure', $orderDetailId),
                'message' => 'No se pudo verificar la transacción en Khipu.'
            ];
        }
    }

    /**
     * Marcar una cuota como pagada cuando se confirma el pago
     *
     * @param OrderDetail $orderDetail
     * @return void
     */
    private function markInstallmentAsPaid(OrderDetail $orderDetail): void
    {
        try {
            // Buscar la cuota correspondiente usando el installment_number
            $installment = \App\Models\Installment::where('installment_number', $orderDetail->installment_number)
                ->whereHas('installmentPlan', function($query) use ($orderDetail) {
                    $query->where('participant_id', $orderDetail->order->participant_id)
                          ->where('program_id', $orderDetail->order->program_id);
                })
                ->first();

            if ($installment) {
                $installment->markAsPaid(
                    $orderDetail->order_id,
                    $orderDetail->id,
                    $orderDetail->payments()->latest()->first()->id
                );

                $this->logInfo('ConfirmKhipuService: Installment marked as paid after payment confirmation', [
                    'installment_id' => $installment->id,
                    'installment_number' => $installment->installment_number,
                    'order_id' => $orderDetail->order_id,
                    'order_detail_id' => $orderDetail->id,
                    'payment_id' => $orderDetail->payments()->latest()->first()->id
                ]);
            } else {
                $this->logWarning('ConfirmKhipuService: Installment not found for marking as paid', [
                    'installment_number' => $orderDetail->installment_number,
                    'participant_id' => $orderDetail->order->participant_id,
                    'program_id' => $orderDetail->order->program_id
                ]);
            }
        } catch (\Exception $e) {
            $this->logError('ConfirmKhipuService: Error marking installment as paid', [
                'error' => $e->getMessage(),
                'order_detail_id' => $orderDetail->id,
                'installment_number' => $orderDetail->installment_number
            ], $e);
        }
    }

    /**
     * Parsear la fecha de transacción de Khipu para que sea compatible con MySQL
     */
    private function parseTransactionDate(string $dateString): string
    {
        // Khipu devuelve fechas en formato ISO 8601, por ejemplo: "2023-10-27T10:00:00Z"
        // MySQL espera un formato como "YYYY-MM-DD HH:MM:SS"
        // Para simplificar, podemos extraer la fecha y hora, y formatearla
        $date = \Carbon\Carbon::parse($dateString);
        return $date->format('Y-m-d H:i:s');
    }
}
