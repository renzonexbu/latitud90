<?php

namespace App\Services\Client\Data;

use App\Models\OrderDetail;
use App\Models\Payment;
use App\Traits\SystemLogging;
use Illuminate\Http\Request;

class GetKhipuCallbackDataService
{
    use SystemLogging;
    /**
     * Obtener datos para la vista de callback de Khipu
     *
     * @param Request $request
     * @param int $orderDetailId
     * @return array
     */
    public function execute(Request $request, int $orderDetailId): array
    {
        // Resolver paymentId desde el último registro de payments de este orderDetail
        $payment = Payment::where('order_detail_id', $orderDetailId)
            ->whereNotNull('external_payment_id')
            ->latest()
            ->first();

        // Cargar datos para mostrar en la vista final (similar a success)
        $orderDetail = OrderDetail::with(['order.program', 'documentType'])->find($orderDetailId);
        $documentTypeName = null;
        
        if ($orderDetail) {
            if (!empty($orderDetail->document_type)) {
                if (is_numeric($orderDetail->document_type)) {
                    $documentTypeName = optional($orderDetail->documentType)->name;
                } else {
                    $documentTypeName = (string) $orderDetail->document_type;
                }
            }
        }

        $paymentData = $orderDetail ? [
            'order_number' => $orderDetail->order->order_number,
            'amount' => $orderDetail->amount,
            'payment_method' => $this->getPaymentMethodFromId($orderDetail->payment_method_id ?? null),
            'transaction_id' => $payment->external_payment_id ?? $orderDetail->transaction_id,
            'paid_at' => $orderDetail->paid_at,
            'program' => [
                'name' => $orderDetail->order->program->name,
                'destination' => $orderDetail->order->program->destination,
                'departure_date' => $orderDetail->order->program->departure_date,
            ],
            'participant_name' => $orderDetail->name,
            'document_type_name' => $documentTypeName,
            'document_number' => $orderDetail->document_number,
            'participant_email' => $orderDetail->email,
            'participant_phone' => $orderDetail->code_phone . ' ' . $orderDetail->phone,
        ] : null;

        return [
            'orderDetailId' => $orderDetailId,
            'paymentId' => (string) ($payment->external_payment_id ?? session('last_khipu_payment_id', '')),
            'paymentData' => $paymentData,
            'rut' => session('current_rut'),
        ];
    }

    /**
     * Obtener nombre del método de pago desde el ID
     *
     * @param int|null $methodId
     * @return string
     */
    private function getPaymentMethodFromId(?int $methodId): string
    {
        $methods = [
            1 => 'debit',
            2 => 'credit',
            3 => 'khipu',
        ];

        return $methods[$methodId] ?? 'unknown';
    }
}
