<?php

namespace App\Services\Client;

use App\Models\OrderDetail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ProcessPaymentResultService
{
    /**
     * Procesar resultado del pago y determinar redirección
     *
     * @param Request $request
     * @param int $orderDetailId
     * @return \Illuminate\Http\RedirectResponse
     */
    public function execute(Request $request, int $orderDetailId)
    {
        try {
            $orderDetail = OrderDetail::findOrFail($orderDetailId);

            if ($orderDetail->is_paid) {
                // Pago exitoso - redirigir a success
                return redirect()->route('payment.success', $orderDetailId);
            } else {
                // Pago fallido - redirigir a failure con mensaje
                $errorMessage = 'El pago no pudo ser procesado correctamente. Si se le descontó dinero, contacte a atención al cliente para verificar el estado de su transacción.';

                // Guardar RUT en sesión para confirmación
                if ($orderDetail->document_number) {
                    session(['current_rut' => preg_replace('/[.-]/', '', $orderDetail->document_number)]);
                }
                
                return redirect()->route('payment.failure', $orderDetailId)
                    ->with('error', $errorMessage)
                    ->with('payment_data', [
                        'amount' => $orderDetail->amount,
                        'order_id' => $orderDetail->order_id
                    ]);
            }
        } catch (\Exception $e) {
            Log::error('ProcessPaymentResultService: Error processing payment result', [
                'error' => $e->getMessage(),
                'order_detail_id' => $orderDetailId
            ]);

            // Persistir RUT si es posible
            if (isset($orderDetail) && $orderDetail && $orderDetail->document_number) {
                session(['current_rut' => $orderDetail->document_number]);
            }
            
            return redirect()->route('payment.failure', $orderDetailId)
                ->with('error', 'Error al procesar el resultado del pago. Si se le descontó dinero, contacte a atención al cliente.');
        }
    }
}
