<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Services\Client\PaymentGateway\HandleNotificationService;
use App\Models\OrderDetail;
use App\Models\Payment;
use App\Models\Installment;
use App\Services\Client\PaymentGateway\TransbankService;
use App\Services\Client\PaymentGateway\KhipuService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PaymentGatewayController extends Controller
{
    private $handleNotificationService;

    public function __construct()
    {
        $this->handleNotificationService = new HandleNotificationService();
    }

    /**
     * Vista con spinner: recibe token_ws y orden, luego el frontend hará POST a confirm.
     */
    public function callbackSpinner(Request $request, $orderDetailId)
    {
        // Pasar RUT desde sesión para preservarlo en redirecciones
        $rut = session('current_rut');
        Log::info('CallbackSpinner render', [
            'order_detail_id' => (int) $orderDetailId,
            'token_ws' => $request->query('token_ws'),
            'rut_in_session' => $rut,
            'full_url' => $request->fullUrl()
        ]);
        return inertia('Payment/CallbackSpinner', [
            'orderDetailId' => (int) $orderDetailId,
            'token' => $request->query('token_ws') ?? null,
            'rut' => $rut,
        ]);
    }

    /**
     * Confirmar transacción de Transbank vía POST (commit con token_ws)
     */
    public function confirmTransbank(Request $request, TransbankService $transbankService)
    {
        $request->validate([
            'orderDetailId' => 'required|integer',
            'token_ws' => 'required|string',
        ]);

        $orderDetailId = (int) $request->input('orderDetailId');
        $token = $request->input('token_ws');

        try {
            $confirmation = $transbankService->confirmTransaction($token);

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

            Log::info('confirmTransbank redirect being built', [
                'approved' => $approved,
                'order_detail_id' => $orderDetailId,
                'redirect' => $redirectUrl,
                'rut_in_session' => session('current_rut')
            ]);

            return response()->json([
                'success' => $approved,
                'redirect' => $redirectUrl,
            ]);
        } catch (\Throwable $e) {
            Log::error('Error confirming Transbank transaction', [
                'error' => $e->getMessage(),
                'order_detail_id' => $orderDetailId,
            ]);
            return response()->json([
                'success' => false,
                'redirect' => route('payment.failure', $orderDetailId),
                'message' => 'No se pudo confirmar la transacción.'
            ], 500);
        }
    }

    /**
     * Khipu: vista de spinner que recibe orderDetailId y paymentId
     */
    public function khipuCallbackSpinner(Request $request, $orderDetailId)
    {
        // Resolver paymentId desde el último registro de payments de este orderDetail
        $payment = Payment::where('order_detail_id', (int) $orderDetailId)
            ->whereNotNull('external_payment_id')
            ->latest()
            ->first();

        // Cargar datos para mostrar en la vista final (similar a success)
        $orderDetail = OrderDetail::with(['order.program', 'documentType'])->find((int) $orderDetailId);
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

        // Render a la vista en carpeta Payment para evitar conflictos de resolución
        return inertia('Payment/KhipuView', [
            'orderDetailId' => (int) $orderDetailId,
            'paymentId' => (string) ($payment->external_payment_id ?? session('last_khipu_payment_id', '')),
            'paymentData' => $paymentData,
            'rut' => session('current_rut'),
        ]);
    }

    /**
     * Khipu: confirmar consultando estado por payment_id
     */
    public function confirmKhipu(Request $request, KhipuService $khipuService)
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
                Log::warning('confirmKhipu missing payment_id', [
                    'order_detail_id' => $orderDetailId,
                ]);
                return response()->json([
                    'success' => false,
                    'status' => 'missing_payment_id'
                ], 422);
            }

            Log::info('confirmKhipu start', [
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
                Log::info('confirmKhipu attempt', [
                    'attempt' => $attempts,
                    'order_detail_id' => $orderDetailId,
                    'payment_id' => $paymentId,
                ]);
                $status = $khipuService->getPaymentStatus($paymentId);

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
                    ]);
                } else {
                    // Actualizar el external_payment_id si no lo tiene
                    if (!$payment->external_payment_id) {
                        $payment->update(['external_payment_id' => $paymentId]);
                    }
                }

                $approved = $status['success'] === true && in_array(($status['status'] ?? ''), ['done', 'paid', 'approved', 'completed']);
                Log::info('confirmKhipu attempt result', [
                    'attempt' => $attempts,
                    'approved' => $approved,
                    'status' => $status['status'] ?? null,
                ]);

                $payment->update([
                    'status' => $approved ? 'approved' : ($status['status'] ?? 'pending'),
                    'gateway_response' => $status['data'] ?? $status,
                    'raw_notification' => $status['data'] ?? $status,
                ]);

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
                    Log::info('confirmKhipu approved', [
                        'order_detail_id' => $orderDetailId,
                        'payment_id' => $paymentId,
                        'attempt' => $attempts,
                    ]);
                    return response()->json([
                        'success' => true,
                        'redirect' => route('payment.success', $orderDetailId),
                        'status' => $status['status'] ?? 'done',
                    ]);
                }

                // Esperar antes del siguiente intento
                sleep(2);
            }

            // No aprobado tras reintentos: redirigir a vista informativa (verificación)
            Log::warning('confirmKhipu exhausted attempts without approval', [
                'order_detail_id' => $orderDetailId,
                'payment_id' => $paymentId,
                'attempts' => $attempts,
                'last_status' => $status['status'] ?? null,
            ]);
            return response()->json([
                'success' => false,
                'redirect' => route('khipu.callback', ['orderDetailId' => $orderDetailId]),
                'status' => $status['status'] ?? 'pending',
            ]);
        } catch (\Throwable $e) {
            Log::error('Error confirming Khipu transaction', [
                'error' => $e->getMessage(),
                'order_detail_id' => $orderDetailId,
                'payment_id' => $paymentId,
            ]);
            return response()->json([
                'success' => false,
                'redirect' => route('payment.failure', $orderDetailId),
                'message' => 'No se pudo verificar la transacción en Khipu.'
            ], 500);
        }
    }

    /**
     * Manejar notificación de Transbank
     */
    public function handleTransbankNotification(Request $request)
    {
        Log::info('Transbank notification received', $request->all());

        try {
            $notification = $request->all();
            $result = $this->handleNotificationService->handleNotification('transbank', $notification);

            if ($result['success']) {
                return response('OK', 200);
            } else {
                Log::error('Transbank notification processing failed', $result);
                return response($result['error'], 400);
            }
        } catch (\Exception $e) {
            Log::error('Error processing Transbank notification', [
                'error' => $e->getMessage(),
                'request_data' => $request->all()
            ]);
            return response('Internal server error', 500);
        }
    }

    /**
     * Manejar notificación de Khipu
     */
    public function handleKhipuNotification(Request $request)
    {
        Log::info('Khipu notification received', $request->all());

        try {
            $notification = $request->all();
            $result = $this->handleNotificationService->handleNotification('khipu', $notification);

            if ($result['success']) {
                return response('OK', 200);
            } else {
                Log::error('Khipu notification processing failed', $result);
                return response($result['error'], 400);
            }
        } catch (\Exception $e) {
            Log::error('Error processing Khipu notification', [
                'error' => $e->getMessage(),
                'request_data' => $request->all()
            ]);
            return response('Internal server error', 500);
        }
    }

    /**
     * Procesar resultado del pago y redirigir según el estado
     */
    public function processPaymentResult(Request $request, $orderDetailId)
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
            Log::error('Error processing payment result', [
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

    /**
     * Verificar estado del pago (para polling desde frontend)
     */
    public function checkPaymentStatus($orderDetailId)
    {
        try {
            $orderDetail = OrderDetail::findOrFail($orderDetailId);

            return response()->json([
                'success' => true,
                'is_paid' => $orderDetail->is_paid,
                'order_detail_id' => $orderDetail->id,
                'amount' => $orderDetail->amount,
                'order_id' => $orderDetail->order_id
            ]);
        } catch (\Exception $e) {
            Log::error('Error checking payment status', [
                'error' => $e->getMessage(),
                'order_detail_id' => $orderDetailId
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al verificar el estado del pago'
            ], 500);
        }
    }

    private function getPaymentMethodFromId($methodId)
    {
        $methods = [
            1 => 'debit',
            2 => 'credit',
            3 => 'khipu',
        ];

        return $methods[$methodId] ?? 'unknown';
    }

    /**
     * Marcar una cuota como pagada cuando se confirma el pago
     */
    private function markInstallmentAsPaid(OrderDetail $orderDetail)
    {
        try {
            // Buscar la cuota correspondiente usando el installment_number
            $installment = Installment::where('installment_number', $orderDetail->installment_number)
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

                Log::info('Installment marked as paid after payment confirmation', [
                    'installment_id' => $installment->id,
                    'installment_number' => $installment->installment_number,
                    'order_id' => $orderDetail->order_id,
                    'order_detail_id' => $orderDetail->id,
                    'payment_id' => $orderDetail->payments()->latest()->first()->id
                ]);
            } else {
                Log::warning('Installment not found for marking as paid', [
                    'installment_number' => $orderDetail->installment_number,
                    'participant_id' => $orderDetail->order->participant_id,
                    'program_id' => $orderDetail->order->program_id
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Error marking installment as paid', [
                'error' => $e->getMessage(),
                'order_detail_id' => $orderDetail->id,
                'installment_number' => $orderDetail->installment_number
            ]);
        }
    }
}
