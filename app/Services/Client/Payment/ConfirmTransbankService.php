<?php

namespace App\Services\Client\Payment;

use App\Models\OrderDetail;
use App\Models\Payment;
use App\Services\Client\PaymentGateway\TransbankService;
use App\Traits\SystemLogging;
use Illuminate\Http\Request;

class ConfirmTransbankService
{
    use SystemLogging;
    public function __construct(
        private TransbankService $transbankService
    ) {}

    /**
     * Confirmar transacción de Transbank
     *
     * @param Request $request
     * @return array
     */
    public function execute(Request $request): array
    {
        $request->validate([
            'orderDetailId' => 'required|integer',
            'token_ws' => 'required|string',
        ]);

        $orderDetailId = (int) $request->input('orderDetailId');
        $token = $request->input('token_ws');

        try {
            $confirmation = $this->transbankService->confirmTransaction($token);

            // Buscar registro de Payment por token o buy_order
            $payment = Payment::where('token', $token)->latest()->first();
            if (!$payment) {
                $payment = Payment::where('order_detail_id', $orderDetailId)->latest()->first();
            }

            $approved = $confirmation['success'] ?? false;

            if ($payment) {
                $payment->update([
                    'status' => $approved ? 'approved' : 'rejected',
                    'authorization_code' => $confirmation['authorization_code'] ?? $payment->authorization_code,
                    'response_code' => (string) ($confirmation['response_code'] ?? ''),
                    'gateway_response' => $confirmation,
                ]);
            }

            $orderDetail = OrderDetail::findOrFail($orderDetailId);
            $orderDetail->update([
                'is_paid' => $approved,
                'status' => $approved ? 'paid' : 'pending',
                'paid_at' => $approved ? now() : $orderDetail->paid_at,
                'transaction_id' => $token,
                'gateway_response' => $confirmation,
            ]);
            
            // Si el pago fue aprobado y es una cuota, marcarla como pagada
            if ($approved && $orderDetail->installment_number >= 1) {
                $this->markInstallmentAsPaid($orderDetail);
            }
            
            // Recalcular estado de la orden
            if ($orderDetail->order) {
                $orderDetail->order->refreshStatus();
            }

            $redirectUrl = $approved
                ? route('payment.success', $orderDetailId)
                : route('payment.failure', $orderDetailId);

            $this->logInfo('ConfirmTransbankService: confirmTransbank redirect being built', [
                'approved' => $approved,
                'order_detail_id' => $orderDetailId,
                'redirect' => $redirectUrl,
                'rut_in_session' => session('current_rut')
            ]);

            return [
                'success' => $approved,
                'redirect' => $redirectUrl,
            ];
        } catch (\Throwable $e) {
            $this->logError('ConfirmTransbankService: Error confirming Transbank transaction', [
                'error' => $e->getMessage(),
                'order_detail_id' => $orderDetailId,
            ], $e);
            
            return [
                'success' => false,
                'redirect' => route('payment.failure', $orderDetailId),
                'message' => 'No se pudo confirmar la transacción.'
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

                $this->logInfo('ConfirmTransbankService: Installment marked as paid after payment confirmation', [
                    'installment_id' => $installment->id,
                    'installment_number' => $installment->installment_number,
                    'order_id' => $orderDetail->order_id,
                    'order_detail_id' => $orderDetail->id,
                    'payment_id' => $orderDetail->payments()->latest()->first()->id
                ]);
            } else {
                $this->logWarning('ConfirmTransbankService: Installment not found for marking as paid', [
                    'installment_number' => $orderDetail->installment_number,
                    'participant_id' => $orderDetail->order->participant_id,
                    'program_id' => $orderDetail->order->program_id
                ]);
            }
        } catch (\Exception $e) {
            $this->logError('ConfirmTransbankService: Error marking installment as paid', [
                'error' => $e->getMessage(),
                'order_detail_id' => $orderDetail->id,
                'installment_number' => $orderDetail->installment_number
            ], $e);
        }
    }
}
