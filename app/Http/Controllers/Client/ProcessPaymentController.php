<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Services\Client\CreateOrderService;
use App\Services\Client\PaymentGateway\TransbankService;
use App\Services\Client\PaymentGateway\KhipuService;
use App\Models\OrderDetail;
use App\Models\Payment;
use App\Models\Country;
use App\Models\Region;
use App\Models\Comune;
use App\Models\Document;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class ProcessPaymentController extends Controller
{
    protected $createOrderService;
    protected $transbankService;
    protected $khipuService;

    public function __construct(
        CreateOrderService $createOrderService,
        TransbankService $transbankService,
        KhipuService $khipuService
    ) {
        $this->createOrderService = $createOrderService;
        $this->transbankService = $transbankService;
        $this->khipuService = $khipuService;
    }

    private function updateBuyerDataOnOrderDetail(OrderDetail $orderDetail, array $formData, array $paymentData): void
    {
        try {
            // Aceptar buyerData anidado
            if (isset($formData['buyerData']) && is_array($formData['buyerData'])) {
                $formData = array_merge($formData, $formData['buyerData']);
            }

            $name = $formData['name'] ?? ($formData['fullName'] ?? null);
            $email = $formData['email'] ?? null;
            $codePhone = $formData['code_phone'] ?? null;
            $phone = $formData['phone'] ?? null;
            $documentType = $this->resolveDocumentTypeId($formData['documentType'] ?? null);
            $documentNumber = $formData['documentNumber'] ?? null;

            $country = $this->resolveCountryId($formData['countryId'] ?? ($formData['country'] ?? null));
            $region = $this->resolveRegionId($formData['regionId'] ?? ($formData['region'] ?? null));
            $city = $this->resolveCityId($formData['cityId'] ?? ($formData['city'] ?? null));

            $billingAddress = $formData['billing_address'] ?? null;
            $billingCity = $formData['cityName'] ?? ($formData['billing_city'] ?? null);
            $billingCountry = $formData['countryName'] ?? ($formData['billing_country'] ?? null);
            $billingPostalCode = $formData['billing_postal_code'] ?? null;

            $orderDetail->update([
                'name' => $name,
                'email' => $email,
                'code_phone' => $codePhone,
                'phone' => $phone,
                'document_type' => $documentType,
                'document_number' => $documentNumber,
                'country' => $country,
                'region' => $region,
                'city' => $city,
                'billing_address' => $billingAddress,
                'billing_city' => $billingCity,
                'billing_country' => $billingCountry,
                'billing_postal_code' => $billingPostalCode,
                // Asegurar modo y método si cambiaron para este intento
                'payment_method_id' => $orderDetail->payment_method_id ?: ($paymentData['paymentMethod'] === 'khipu' ? 3 : ($paymentData['paymentMethod'] === 'credit' ? 2 : 1)),
                'payment_mode_id' => $orderDetail->payment_mode_id ?: ($paymentData['paymentType'] === 'monthly' ? 2 : 1),
                'payment_gateway_id' => $orderDetail->payment_gateway_id ?: ($paymentData['paymentMethod'] === 'khipu' ? 2 : 1),
            ]);
        } catch (\Throwable $e) {
            Log::error('Error updating buyer data on OrderDetail', [
                'error' => $e->getMessage(),
                'order_detail_id' => $orderDetail->id,
            ]);
        }
    }

    private function resolveCountryId($value): ?int
    {
        if (empty($value)) { return null; }
        if (is_numeric($value)) { return (int) $value; }
        $string = trim((string) $value);
        if (strlen($string) <= 3) {
            $id = Country::where('code', $string)->value('id');
            if ($id) { return (int) $id; }
        }
        $id = Country::where('name', $string)->value('id');
        if ($id) { return (int) $id; }
        $id = Country::where('name', 'like', $string)->value('id');
        return $id ? (int) $id : null;
    }

    private function resolveRegionId($value): ?int
    {
        if (empty($value)) { return null; }
        if (is_numeric($value)) { return (int) $value; }
        $string = trim((string) $value);
        $id = Region::where('name', $string)->value('id');
        if ($id) { return (int) $id; }
        $id = Region::where('name', 'like', $string)->value('id');
        return $id ? (int) $id : null;
    }

    private function resolveCityId($value): ?int
    {
        if (empty($value)) { return null; }
        if (is_numeric($value)) { return (int) $value; }
        $string = trim((string) $value);
        $id = Comune::where('name', $string)->value('id');
        if ($id) { return (int) $id; }
        $id = Comune::where('name', 'like', $string)->value('id');
        return $id ? (int) $id : null;
    }

    private function resolveDocumentTypeId($value): ?int
    {
        if (empty($value)) { return null; }
        if (is_numeric($value)) { return (int) $value; }
        $string = trim((string) $value);
        $id = Document::where('name', $string)->value('id');
        if ($id) { return (int) $id; }
        $id = Document::where('name', 'like', $string)->value('id');
        return $id ? (int) $id : null;
    }
    public function processPayment(Request $request)
    {
        try {
            $request->validate([
                'programId' => 'required|integer',
                'rut' => 'required|string',
            ]);

            $programId = $request->input('programId');
            $rut = $request->input('rut');

            // Obtener datos del localStorage (enviados desde el frontend)
            $paymentData = $request->input('paymentData');
            $formData = $request->input('formData');

            if (!$paymentData || !$formData) {
                return response()->json([
                    'success' => false,
                    'error' => 'Datos de pago o formulario no encontrados'
                ], 400);
            }

            Log::info('Processing payment request', [
                'program_id' => $programId,
                'rut' => $rut,
                'payment_data' => $paymentData,
                'form_data' => $formData
            ]);

            // 1. Crear la orden y detalles
            $orderResult = $this->createOrderService->createOrder(
                $programId,
                $rut,
                $paymentData,
                $formData
            );

            if (!$orderResult['success']) {
                return response()->json([
                    'success' => false,
                    'error' => $orderResult['error']
                ], 400);
            }

            $order = $orderResult['order'];
            $orderDetail = $orderResult['order_detail'];

            // 2. Actualizar SIEMPRE datos del comprador y método/gateway del intento actual en el OrderDetail seleccionado
            $this->updateBuyerDataOnOrderDetail($orderDetail, $formData, $paymentData);

            // Si existe una orden mensual con cuotas previas impagas, mantener estado acorde
            if ($order) {
                $order->refreshStatus();
            }

            // 3. Determinar el gateway de pago y crear la transacción
            $gatewayResult = $this->createGatewayTransaction($orderDetail, $paymentData);

            if (!$gatewayResult['success']) {
                return response()->json([
                    'success' => false,
                    'error' => $gatewayResult['error']
                ], 400);
            }

            Log::info('Payment gateway transaction created', [
                'order_id' => $order->id,
                'order_detail_id' => $orderDetail->id,
                'gateway' => $paymentData['paymentMethod'],
                'gateway_response' => $gatewayResult
            ]);

            // Guardar RUT en sesión para preservar al regresar
            if (!empty($orderDetail->document_number)) {
                session(['current_rut' => preg_replace('/[.-]/', '', $orderDetail->document_number)]);
            }

            // Registrar pago pendiente en la tabla payments
            $this->recordPendingPayment($orderDetail, $paymentData['paymentMethod'], $gatewayResult);
            // Recalcular estado de la orden (al crear intento de pago, puede pasar de pending a processing si ya hay pagos previos)
            if ($orderDetail->order) {
                $orderDetail->order->refreshStatus();
            }

            return response()->json([
                'success' => true,
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'gateway_url' => $gatewayResult['url'],
                'gateway_token' => $gatewayResult['token'] ?? null,
                'gateway_type' => $paymentData['paymentMethod']
            ]);
        } catch (\Exception $e) {
            Log::error('Error processing payment', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Error interno del servidor: ' . $e->getMessage()
            ], 500);
        }
    }

    private function recordPendingPayment(OrderDetail $orderDetail, string $gatewayType, array $gatewayResult): void
    {
        try {
            $buyOrder = $orderDetail->order_id . '-' . $orderDetail->installment_number;
            $commonData = [
                'order_id' => $orderDetail->order_id,
                'order_detail_id' => $orderDetail->id,
                'payment_gateway_id' => $orderDetail->payment_gateway_id,
                'payment_method_id' => $orderDetail->payment_method_id,
                'payment_mode_id' => $orderDetail->payment_mode_id,
                'amount' => $orderDetail->amount,
                'currency' => 'CLP',
                'status' => 'pending',
                'buy_order' => $buyOrder,
                'gateway_response' => $gatewayResult,
            ];

            if ($gatewayType === 'khipu') {
                // Asegurar external_payment_id: usar payment_id; si no, extraer del payment_url/url
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
            } else {
                $data = array_merge($commonData, [
                    'token' => $gatewayResult['token'] ?? null,
                ]);
            }

            Payment::create($data);
        } catch (\Throwable $e) {
            Log::error('Error recording pending payment', [
                'error' => $e->getMessage(),
                'order_detail_id' => $orderDetail->id,
            ]);
        }
    }

    private function createGatewayTransaction($orderDetail, $paymentData)
    {
        $amount = $orderDetail->amount;
        $orderId = $orderDetail->order_id . '-' . $orderDetail->installment_number;

        // URLs de retorno
        // Callback intermedio (spinner) por gateway
        $transbankCallbackUrl = route('payment.callback', ['orderDetailId' => $orderDetail->id]);
        $khipuCallbackUrl = route('khipu.callback', ['orderDetailId' => $orderDetail->id]);
        $failureUrl = route('payment.failure', ['orderDetailId' => $orderDetail->id]);

        // URLs de notificación deshabilitadas (confirmación vía polling)
        $transbankNotificationUrl = null;
        $khipuNotificationUrl = null;

        switch ($paymentData['paymentMethod']) {
            case 'debit':
            case 'credit':
                // Determinar tipo de pago y cuotas para Transbank
                $paymentType = $paymentData['paymentType'] ?? null; // 'total' o 'monthly'
                $installments = $paymentData['installments'] ?? null;

                // Usar Transbank con URL de notificación y control de cuotas
                return $this->transbankService->createTransaction(
                    $orderId,
                    $amount,
                    $transbankCallbackUrl,
                    $transbankNotificationUrl,
                    $paymentType,
                    $installments
                );

            case 'khipu':
                // Usar Khipu con URL de retorno propia y URL de notificación
                return $this->khipuService->createTransaction($orderId, $amount, $khipuCallbackUrl, $khipuNotificationUrl);

            default:
                return [
                    'success' => false,
                    'error' => 'Método de pago no soportado'
                ];
        }
    }

    public function paymentSuccess($orderDetailId)
    {
        try {
            $orderDetail = OrderDetail::with(['order.program', 'order.participant'])->findOrFail($orderDetailId);

            Log::info('Payment success page accessed', [
                'order_detail_id' => $orderDetailId,
                'order_id' => $orderDetail->order_id,
                'amount' => $orderDetail->amount,
                'is_paid' => $orderDetail->is_paid,
                'status' => $orderDetail->status
            ]);

            // Verificar si el pago fue realmente procesado
            if (!$orderDetail->is_paid || $orderDetail->status !== 'paid') {
                Log::warning('Payment success page accessed but payment not confirmed', [
                    'order_detail_id' => $orderDetailId,
                    'is_paid' => $orderDetail->is_paid,
                    'status' => $orderDetail->status
                ]);

            // Redirigir al paso 4 con mensaje de error (incluyendo rut)
                $programId = $orderDetail->order->program_id;
                $rut = $orderDetail->document_number;

                return redirect()->route('payment.confirmation', [
                    'programId' => $programId,
                    'rut' => $rut
                ])->with('error', 'El pago aún no ha sido confirmado. Por favor, espera unos minutos o contacta soporte.');
            }

            // Preparar datos para la vista de éxito
            // Resolver tipo y número de documento con fallbacks robustos
            $documentTypeName = null;
            $documentNumber = null;

            if (!empty($orderDetail->document_type)) {
                if (is_numeric($orderDetail->document_type)) {
                    $documentTypeName = optional($orderDetail->documentType)->name;
                } else {
                    $documentTypeName = (string) $orderDetail->document_type;
                }
            }
            $documentNumber = $orderDetail->document_number;

            // Fallback a datos del participante si el detalle no los tiene
            if (!$documentTypeName && $orderDetail->order && $orderDetail->order->participant) {
                $documentTypeName = $orderDetail->order->participant->document_type;
            }
            if (!$documentNumber && $orderDetail->order && $orderDetail->order->participant) {
                $documentNumber = $orderDetail->order->participant->document_number;
            }

            $paymentData = [
                'order_number' => $orderDetail->order->order_number,
                'amount' => $orderDetail->amount,
                'payment_method' => $this->getPaymentMethodFromId($orderDetail->payment_method_id),
                'transaction_id' => $orderDetail->transaction_id,
                'paid_at' => $orderDetail->paid_at,
                'program' => [
                    'name' => $orderDetail->order->program->name,
                    'destination' => $orderDetail->order->program->destination,
                    'departure_date' => $orderDetail->order->program->departure_date,
                ],
                'participant_name' => $orderDetail->name,
                // Datos de documento con fallbacks
                'document_type_name' => $documentTypeName,
                'document_number' => $documentNumber,
                'participant_email' => $orderDetail->email,
                'participant_phone' => $orderDetail->code_phone . ' ' . $orderDetail->phone,
            ];

            // Persistir RUT en sesión
            if ($orderDetail->document_number) {
                session(['current_rut' => $orderDetail->document_number]);
            }
            return Inertia::render('Ecommerce/SuccessfulPayment', [
                'paymentData' => $paymentData
            ]);
        } catch (\Exception $e) {
            Log::error('Error in payment success page', [
                'error' => $e->getMessage(),
                'order_detail_id' => $orderDetailId
            ]);

            return redirect()->route('ecommerce.index')->with('error', 'Error al procesar el pago');
        }
    }

    public function paymentFailure($orderDetailId)
    {
        try {
            $orderDetail = OrderDetail::with(['order.program', 'order.participant'])->findOrFail($orderDetailId);

            Log::info('Payment failure page accessed', [
                'order_detail_id' => $orderDetailId,
                'order_id' => $orderDetail->order_id,
                'amount' => $orderDetail->amount,
                'is_paid' => $orderDetail->is_paid,
                'status' => $orderDetail->status
            ]);

            // Verificar si el pago fue realmente procesado (por si acaso llegó aquí por error)
            if ($orderDetail->is_paid && $orderDetail->status === 'paid') {
                Log::warning('Payment failure page accessed but payment was actually successful', [
                    'order_detail_id' => $orderDetailId,
                    'is_paid' => $orderDetail->is_paid,
                    'status' => $orderDetail->status
                ]);

                // Redirigir a la página de éxito
                return redirect()->route('payment.success', ['orderDetailId' => $orderDetailId]);
            }

            // Redirigir al paso 4 (confirmación) con mensaje de error usando RUT del participante
            $programId = $orderDetail->order->program_id;
            $participantRut = optional($orderDetail->order->participant)->document_number;
            if ($participantRut) {
                session(['current_rut' => preg_replace('/[.-]/', '', $participantRut)]);
            }
            return redirect()->route('payment.confirmation', [
                'programId' => $programId,
                'rut' => $participantRut
            ])->with('error', 'El pago no pudo ser procesado. Por favor, intenta nuevamente.');
        } catch (\Exception $e) {
            Log::error('Error in payment failure page', [
                'error' => $e->getMessage(),
                'order_detail_id' => $orderDetailId
            ]);

            return redirect()->route('ecommerce.index')->with('error', 'Error al procesar el pago');
        }
    }

    private function getPaymentMethodFromId($methodId)
    {
        $methods = [
            1 => 'debit',
            2 => 'credit',
            3 => 'khipu'
        ];

        return $methods[$methodId] ?? 'unknown';
    }



    /**
     * API endpoint para recibir notificaciones de Transbank
     * Esta ruta no tiene CORS y es llamada directamente por Transbank
     */
    public function transbankNotification(Request $request)
    {
        try {
            Log::info('Transbank notification received', [
                'data' => $request->all(),
                'headers' => $request->headers->all()
            ]);

            // Obtener el token de la transacción
            $token = $request->input('token_ws');

            if (!$token) {
                Log::error('Transbank notification: No token received');
                return response()->json(['error' => 'No token received'], 400);
            }

            // Buscar el OrderDetail por el token
            $orderDetail = OrderDetail::where('transaction_id', $token)->first();

            if (!$orderDetail) {
                Log::error('Transbank notification: OrderDetail not found', [
                    'token' => $token
                ]);
                return response()->json(['error' => 'OrderDetail not found'], 404);
            }

            // Confirmar la transacción con Transbank
            $confirmation = $this->transbankService->confirmTransaction($token);

            if ($confirmation['success']) {
                // Actualizar el estado del pago
                $orderDetail->update([
                    'is_paid' => true,
                    'paid_at' => now(),
                    'status' => 'paid',
                    'gateway_response' => $confirmation
                ]);

                Log::info('Transbank payment confirmed via notification', [
                    'order_detail_id' => $orderDetail->id,
                    'transaction_id' => $token,
                    'amount' => $confirmation['amount'] ?? 'unknown'
                ]);

                return response()->json(['success' => true, 'message' => 'Payment confirmed']);
            } else {
                // Marcar como fallido
                $orderDetail->update([
                    'status' => 'cancelled',
                    'gateway_response' => $confirmation
                ]);

                Log::error('Transbank payment failed via notification', [
                    'token' => $token,
                    'error' => $confirmation['error'] ?? 'unknown error',
                    'order_detail_id' => $orderDetail->id
                ]);

                return response()->json(['error' => 'Payment failed'], 400);
            }
        } catch (\Exception $e) {
            Log::error('Error processing Transbank notification', [
                'error' => $e->getMessage(),
                'data' => $request->all()
            ]);

            return response()->json(['error' => 'Internal server error'], 500);
        }
    }

    /**
     * API endpoint para recibir notificaciones de Khipu
     * Esta ruta no tiene CORS y es llamada directamente por Khipu
     */
    public function khipuNotification(Request $request)
    {
        try {
            Log::info('Khipu notification received', [
                'data' => $request->all(),
                'headers' => $request->headers->all()
            ]);

            // Obtener datos de la notificación
            $paymentId = $request->input('payment_id');

            if (!$paymentId) {
                Log::error('Khipu notification: No payment_id received');
                return response()->json(['error' => 'No payment_id received'], 400);
            }

            // Buscar el OrderDetail por el payment_id
            $orderDetail = OrderDetail::where('transaction_id', $paymentId)->first();

            if (!$orderDetail) {
                Log::error('Khipu notification: OrderDetail not found', [
                    'payment_id' => $paymentId
                ]);
                return response()->json(['error' => 'OrderDetail not found'], 404);
            }

            // Obtener datos del webhook de Khipu
            $data = $request->all();
            $status = $data['status'] ?? null;

            // Procesar según el estado del webhook
            if ($status === 'done' || $status === 'success') {
                // Pago exitoso
                $orderDetail->update([
                    'is_paid' => true,
                    'paid_at' => now(),
                    'status' => 'paid',
                    'gateway_response' => $data
                ]);

                Log::info('Khipu payment confirmed via notification', [
                    'order_detail_id' => $orderDetail->id,
                    'payment_id' => $paymentId,
                    'status' => $status
                ]);

                return response()->json(['success' => true, 'message' => 'Payment confirmed']);
            } else {
                // Pago fallido o cancelado
                $orderDetail->update([
                    'status' => 'cancelled',
                    'gateway_response' => $data
                ]);

                Log::error('Khipu payment failed via notification', [
                    'payment_id' => $paymentId,
                    'status' => $status,
                    'order_detail_id' => $orderDetail->id
                ]);

                return response()->json(['error' => 'Payment failed'], 400);
            }
        } catch (\Exception $e) {
            Log::error('Error processing Khipu notification', [
                'error' => $e->getMessage(),
                'data' => $request->all()
            ]);

            return response()->json(['error' => 'Internal server error'], 500);
        }
    }
}
