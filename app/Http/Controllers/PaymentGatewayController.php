<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\Passenger;
use App\Models\PaymentLink;
use App\Services\Client\PaymentGateway\TransbankService;
use App\Services\Client\PaymentGateway\KhipuService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Log;

class PaymentGatewayController extends Controller
{
    protected $transbankService;
    protected $khipuService;

    public function __construct(TransbankService $transbankService, KhipuService $khipuService)
    {
        $this->transbankService = $transbankService;
        $this->khipuService = $khipuService;
    }

    public function initiate(Request $request)
    {
        $validated = $request->validate([
            'passenger_id' => 'required|exists:passengers,id',
            'amount' => 'required|numeric|min:1',
            'payment_method' => 'required|in:transbank_webpay,khipu',
            'payment_type' => 'required|in:full,installment',
            'installments' => 'nullable|integer|min:1|max:12'
        ]);

        $passenger = Passenger::findOrFail($validated['passenger_id']);

        // Crear registro de pago
        $payment = Payment::create([
            'passenger_id' => $passenger->id,
            'amount' => $validated['amount'],
            'payment_method' => $validated['payment_method'],
            'payment_type' => $validated['payment_type'],
            'total_installments' => $validated['installments'] ?? 1,
            'installment_number' => 1,
            'status' => 'pending',
            'payer_name' => $passenger->full_name,
            'payer_email' => $passenger->email
        ]);

        if ($validated['payment_method'] === 'transbank_webpay') {
            return $this->initiateTransbank($payment);
        } elseif ($validated['payment_method'] === 'khipu') {
            return $this->initiateKhipu($payment);
        }

        return response()->json(['error' => 'Método de pago no válido'], 400);
    }

    private function initiateTransbank(Payment $payment)
    {
        $orderId = 'ORDER-' . $payment->id . '-' . time();
        $returnUrl = route('payment.transbank.return');

        $result = $this->transbankService->createTransaction(
            $orderId,
            (int) $payment->amount,
            $returnUrl
        );

        if ($result['success']) {
            $payment->update(['transaction_id' => $result['token']]);

            return response()->json([
                'success' => true,
                'redirect_url' => $result['url'] . '?token_ws=' . $result['token']
            ]);
        }

        return response()->json([
            'success' => false,
            'error' => $result['error']
        ], 400);
    }

    private function initiateKhipu(Payment $payment)
    {
        $orderId = 'PAYMENT-' . $payment->id;
        $amount = (int) $payment->amount;
        $returnUrl = route('payment.khipu.return');
        $notifyUrl = route('payment.khipu.webhook');

        $result = $this->khipuService->createTransaction(
            $orderId,
            $amount,
            $returnUrl,
            $notifyUrl
        );

        if ($result['success']) {
            $payment->update(['transaction_id' => $result['payment_id']]);

            return response()->json([
                'success' => true,
                'redirect_url' => $result['url']
            ]);
        }

        return response()->json([
            'success' => false,
            'error' => $result['error']
        ], 400);
    }

    public function transbankReturn(Request $request)
    {
        $token = $request->get('token_ws');
        $tbkToken = $request->get('TBK_TOKEN');

        if ($tbkToken) {
            // Usuario canceló la transacción
            return Inertia::render('Payment/Cancelled');
        }

        if (!$token) {
            return Inertia::render('Payment/Error', [
                'message' => 'Token no encontrado'
            ]);
        }

        $payment = Payment::where('transaction_id', $token)->first();

        if (!$payment) {
            return Inertia::render('Payment/Error', [
                'message' => 'Pago no encontrado'
            ]);
        }

        $result = $this->transbankService->processPayment($payment->id, $token);

        if ($result['success']) {
            return Inertia::render('Payment/Success', [
                'payment' => $result['payment'],
                'confirmation' => $result['confirmation']
            ]);
        } else {
            return Inertia::render('Payment/Failed', [
                'payment' => $result['payment'],
                'error' => $result['error']
            ]);
        }
    }

    public function khipuReturn(Request $request)
    {
        $paymentId = $request->get('payment_id');
        
        if (!$paymentId) {
            return Inertia::render('Payment/Error', [
                'message' => 'ID de pago no encontrado'
            ]);
        }

        $payment = Payment::where('transaction_id', $paymentId)->first();

        if (!$payment) {
            return Inertia::render('Payment/Error', [
                'message' => 'Pago no encontrado'
            ]);
        }

        // Verificar estado del pago con Khipu
        $status = $this->khipuService->getPaymentStatus($paymentId);

        if ($status['success'] && $status['status'] === 'done') {
            $payment->update([
                'status' => 'approved',
                'gateway_response' => $status['full_response']
            ]);

            return Inertia::render('Payment/Success', [
                'payment' => $payment
            ]);
        } else {
            return Inertia::render('Payment/Pending', [
                'payment' => $payment,
                'message' => 'El pago está siendo procesado'
            ]);
        }
    }

    public function khipuWebhook(Request $request)
    {
        Log::info('Khipu webhook received', $request->all());

        $result = $this->khipuService->processWebhook($request->all());

        if ($result['success']) {
            return response()->json(['status' => 'ok']);
        }

        return response()->json(['error' => $result['error']], 400);
    }

    public function paymentLink($token)
    {
        $paymentLink = PaymentLink::where('token', $token)->first();

        if (!$paymentLink || !$paymentLink->is_active) {
            return Inertia::render('Payment/LinkExpired');
        }

        $passenger = $paymentLink->passenger;
        $program = $passenger->program;

        return Inertia::render('Payment/LinkCheckout', [
            'paymentLink' => $paymentLink,
            'passenger' => $passenger,
            'program' => $program
        ]);
    }

    public function processPaymentLink(Request $request, $token)
    {
        $paymentLink = PaymentLink::where('token', $token)->first();

        if (!$paymentLink || !$paymentLink->is_active) {
            return response()->json(['error' => 'Link de pago inválido o expirado'], 400);
        }

        $validated = $request->validate([
            'payment_method' => 'required|in:transbank_webpay,khipu'
        ]);

        // Verificar que el método de pago esté permitido
        if ($paymentLink->payment_method !== 'both' && 
            $paymentLink->payment_method !== $validated['payment_method']) {
            return response()->json(['error' => 'Método de pago no permitido'], 400);
        }

        // Crear pago
        $payment = Payment::create([
            'passenger_id' => $paymentLink->passenger_id,
            'amount' => $paymentLink->amount,
            'payment_method' => $validated['payment_method'],
            'payment_type' => 'full',
            'status' => 'pending',
            'payer_name' => $paymentLink->passenger->full_name,
            'payer_email' => $paymentLink->passenger->email
        ]);

        // Marcar el link como usado
        $paymentLink->update(['status' => 'used']);

        // Procesar según el método de pago
        if ($validated['payment_method'] === 'transbank_webpay') {
            return $this->initiateTransbank($payment);
        } else {
            return $this->initiateKhipu($payment);
        }
    }
}
