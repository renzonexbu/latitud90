<?php

namespace App\Http\Controllers;

use App\Models\OrderDetail;
use App\Models\Payment;
use App\Services\PDF\PaymentReceiptService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class PaymentReceiptController extends Controller
{
    protected $paymentReceiptService;

    public function __construct(PaymentReceiptService $paymentReceiptService)
    {
        $this->paymentReceiptService = $paymentReceiptService;
    }

    /**
     * Mostrar comprobante de pago en el navegador
     */
    public function download(Request $request)
    {
        try {
            // Validar parámetros requeridos
            $request->validate([
                'order_detail_id' => 'required|integer|exists:orders_detail,id',
                'payment_id' => 'required|integer|exists:payments,id',
            ]);

            // Obtener OrderDetail y Payment
            $orderDetail = OrderDetail::with(['order.program', 'order.participant.documentType'])->findOrFail($request->order_detail_id);
            $payment = Payment::findOrFail($request->payment_id);

            // Verificar que el pago esté completado
            if ($payment->status !== 'completed') {
                return response()->json([
                    'error' => 'El pago no está completado'
                ], 400);
            }

            // Verificar que el pago pertenezca al order detail
            if ($payment->order_detail_id !== $orderDetail->id) {
                return response()->json([
                    'error' => 'El pago no corresponde al detalle de orden'
                ], 400);
            }

            // Generar PDF
            $pdfPath = $this->paymentReceiptService->generatePaymentReceipt($orderDetail, $payment);

            // Leer el contenido del PDF
            $pdfContent = file_get_contents($pdfPath);

            // Limpiar archivo temporal
            $this->paymentReceiptService->cleanupTempFile($pdfPath);

            // Retornar PDF para visualización en el navegador
            return response($pdfContent, 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="comprobante_pago_' . $orderDetail->order->order_number . '.pdf"'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error al generar el comprobante: ' . $e->getMessage()
            ], 500);
        }
    }
}
