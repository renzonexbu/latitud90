<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Services\Client\InstallmentService;
use App\Services\Client\PaymentOrderService;
use App\Services\Client\PaymentGateway\TransbankService;
use App\Services\Client\PaymentGateway\KhipuService;
use App\Services\Client\FrequentClientService;
use App\Models\OrderDetail;
use App\Models\Payment;
use App\Models\Installment;
use App\Models\Country;
use App\Models\Region;
use App\Models\Comune;
use App\Models\Document;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use App\Services\Client\CreateOrderService;
use App\Services\Client\BsaleService;
use App\Services\EcommerceAnalyticsService;

class ProcessPaymentController extends Controller
{
    protected $installmentService;
    protected $paymentOrderService;
    protected $transbankService;
    protected $khipuService;
    protected $createOrderService;
    protected $bsaleService;
    protected $analyticsService;

    public function __construct(
        InstallmentService $installmentService,
        PaymentOrderService $paymentOrderService,
        TransbankService $transbankService,
        KhipuService $khipuService,
        CreateOrderService $createOrderService,
        BsaleService $bsaleService,
        EcommerceAnalyticsService $analyticsService
    ) {
        $this->installmentService = $installmentService;
        $this->paymentOrderService = $paymentOrderService;
        $this->transbankService = $transbankService;
        $this->khipuService = $khipuService;
        $this->createOrderService = $createOrderService;
        $this->bsaleService = $bsaleService;
        $this->analyticsService = $analyticsService;
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
            
            // Agregar session_id a los datos de pago
            $sessionId = $request->input('session_id');
            if ($sessionId) {
                $paymentData['session_id'] = $sessionId;
                Log::info('Session ID received from frontend', [
                    'session_id' => $sessionId,
                    'program_id' => $programId,
                    'rut' => $rut
                ]);
            } else {
                Log::warning('No session_id received from frontend', [
                    'program_id' => $programId,
                    'rut' => $rut
                ]);
            }

            if (!$paymentData || !$formData) {
                return response()->json([
                    'success' => false,
                    'error' => 'Datos de pago o formulario no encontrados'
                ], 400);
            }

            // Validar que el participante existe y tiene saldo pendiente
            $validationResult = $this->validatePaymentEligibility($programId, $rut, $paymentData);
            if (!$validationResult['success']) {
                return response()->json([
                    'success' => false,
                    'error' => $validationResult['error']
                ], 400);
            }

            Log::info('Processing payment', [
                'program_id' => $programId,
                'rut' => $rut,
                'payment_type' => $paymentData['paymentType'] ?? 'unknown',
                'payment_method' => $paymentData['paymentMethod'] ?? 'unknown'
            ]);

            // Log para verificar que session_id se está pasando correctamente
            Log::info('Processing payment with session_id', [
                'session_id' => $paymentData['session_id'] ?? 'NULL',
                'payment_type' => $paymentData['paymentType'] ?? 'unknown'
            ]);

            // Determinar el tipo de pago y crear la orden correspondiente
            if (($paymentData['paymentType'] ?? 'total') === 'monthly') {
                // PAGO DE CUOTAS MENSUALES
                $result = $this->processMonthlyPayment($programId, $rut, $paymentData, $formData);
            } else {
                // PAGO TOTAL
                $result = $this->processTotalPayment($programId, $rut, $paymentData, $formData);
            }

            if (!$result['success']) {
                return response()->json([
                    'success' => false,
                    'error' => $result['error']
                ], 400);
            }

            $order = $result['order'];
            $orderDetail = $result['order_detail'];
            $installment = $result['installment'] ?? null;

            // Actualizar datos del comprador y método de pago en el OrderDetail
            $this->updateBuyerDataOnOrderDetail($orderDetail, $formData, $paymentData);

            // Almacenar cliente frecuente para futuras compras
            $this->storeFrequentClient($formData);
            // Registrar inicio de pago en el servicio de analytics


            // Crear transacción en el gateway de pago
            $gatewayResult = $this->createGatewayTransaction($orderDetail, $paymentData);

            if (!$gatewayResult['success']) {
                return response()->json([
                    'success' => false,
                    'error' => $gatewayResult['error']
                ], 400);
            }

            // Registrar pago pendiente en la tabla payments
            $this->recordPendingPayment($orderDetail, $paymentData['paymentMethod'], $gatewayResult);

            // NO marcar la cuota como pagada aquí
            // Solo se marcará como pagada cuando se confirme el pago con la pasarela
            if ($installment) {
                Log::info('Installment payment initiated, will be marked as paid when confirmed', [
                    'installment_id' => $installment->id,
                    'installment_number' => $installment->installment_number,
                    'order_id' => $order->id,
                    'order_detail_id' => $orderDetail->id
                ]);
            }

            // Normalizar tipo para el frontend
            $frontendGatewayType = $this->normalizeGatewayType($paymentData['paymentMethod'] ?? '');

            // Fallbacks por compatibilidad entre gateways
            $gatewayUrl = $gatewayResult['url'] ?? ($gatewayResult['payment_url'] ?? null);
            $gatewayToken = $gatewayResult['token'] ?? null;

            // Guardar payment_id de Khipu en sesión como respaldo
            if ($frontendGatewayType === 'other' && isset($gatewayResult['payment_id'])) {
                session(['last_khipu_payment_id' => (string) $gatewayResult['payment_id']]);
                session(['last_khipu_order_detail_id' => (int) $orderDetail->id]);
            }

            return response()->json([
                'success' => true,
                'order_id' => $order->id,
                'order_detail_id' => $orderDetail->id,
                'order_number' => $order->order_number,
                'gateway_url' => $gatewayUrl,
                'gateway_token' => $gatewayToken,
                'gateway_type' => $frontendGatewayType
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

    /**
     * Procesar pago de cuotas mensuales
     */
    private function processMonthlyPayment(int $programId, string $rut, array $paymentData, array $formData): array
    {
        // Crear o recuperar plan de cuotas
        $installmentResult = $this->installmentService->createOrGetInstallmentPlan(
            $programId, $rut, $paymentData, $formData
        );

        if (!$installmentResult['success']) {
            return $installmentResult;
        }

        $installmentPlan = $installmentResult['installment_plan'];
        $nextInstallment = $installmentResult['next_installment'];

        // Crear nueva orden de pago para esta cuota específica
        $paymentResult = $this->paymentOrderService->createPaymentOrder(
            $nextInstallment, $paymentData, $formData
        );

        if (!$paymentResult['success']) {
            return $paymentResult;
        }

        return [
            'success' => true,
            'order' => $paymentResult['order'],
            'order_detail' => $paymentResult['order_detail'],
            'installment' => $nextInstallment
        ];
    }

    /**
     * Procesar pago total
     */
    private function processTotalPayment(int $programId, string $rut, array $paymentData, array $formData): array
    {
        // Crear nueva orden de pago total
        return $this->paymentOrderService->createTotalPaymentOrder(
            $programId, $rut, $paymentData, $formData
        );
    }

    /**
     * Validar el saldo pendiente del participante para el pago
     */
    private function validatePaymentEligibility(int $programId, string $rut, array $paymentData): array
    {
        // Buscar el participante
        $participant = \App\Models\Participant::where('document_number', $rut)->first();
        if (!$participant) {
            return [
                'success' => false,
                'error' => 'Participante no encontrado'
            ];
        }

        // Buscar el programa
        $program = \App\Models\Program::find($programId);
        if (!$program) {
            return [
                'success' => false,
                'error' => 'Programa no encontrado'
            ];
        }

        // Calcular el saldo pendiente del participante
        $priceData = \App\Helpers\ParticipantPriceHelper::calculateParticipantPrice($participant, $program);
        $participantTotalAmount = $priceData['final_price'];

        // Pagos aprobados previos
        $paidAmount = (float) \App\Models\Payment::whereHas('order', function ($q) use ($participant, $program) {
                $q->where('participant_id', $participant->id)
                  ->where('program_id', $program->id);
            })
            ->where('status', 'approved')
            ->sum('amount');
        $paidAmount = round($paidAmount, 2);
        $participantBalance = max(round($participantTotalAmount - $paidAmount, 2), 0);

        // Verificar si ya se pagó todo
        if ($participantBalance <= 0) {
            return [
                'success' => false,
                'error' => 'Ya has pagado el monto total del programa. No hay pagos pendientes.'
            ];
        }

        // Para pagos mensuales, verificar que el total de las cuotas no exceda el saldo
        if (($paymentData['paymentType'] ?? 'total') === 'monthly') {
            $installments = (int) ($paymentData['installments'] ?? 1);
            $amountPerInstallment = $participantBalance / $installments;
            $totalToPay = $amountPerInstallment * $installments;

            if ($totalToPay > $participantBalance) {
                return [
                    'success' => false,
                    'error' => 'El monto total de las cuotas excede el saldo pendiente. Por favor, selecciona un número menor de cuotas.'
                ];
            }
        }

        Log::info('Payment eligibility validated', [
            'participant_id' => $participant->id,
            'program_id' => $programId,
            'participant_total_amount' => $participantTotalAmount,
            'paid_amount' => $paidAmount,
            'participant_balance' => $participantBalance,
            'payment_type' => $paymentData['paymentType'] ?? 'total',
            'installments' => $paymentData['installments'] ?? 1
        ]);

        return [
            'success' => true,
            'participant' => $participant,
            'program' => $program,
            'participant_balance' => $participantBalance
        ];
    }

    /**
     * Actualizar datos del comprador en OrderDetail
     */
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

            $dataToUpdate = [
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
            ];
            
            // SIEMPRE actualizar el payment_gateway_id según el método de pago actual
            $dataToUpdate['payment_gateway_id'] = $paymentData['paymentMethod'] === 'khipu' ? 2 : 1;
            
            // SIEMPRE actualizar el payment_option_id según el método de pago y tipo de pago
            $dataToUpdate['payment_option_id'] = $this->resolvePaymentOptionIdForUpdate(
                $orderDetail->order->program_id, 
                $paymentData
            );
            
            $orderDetail->update($dataToUpdate);
            
            Log::info('OrderDetail buyer data updated successfully', [
                'order_detail_id' => $orderDetail->id,
                'installment_number' => $orderDetail->installment_number,
                'payment_gateway_id' => $dataToUpdate['payment_gateway_id'],
                'payment_option_id' => $dataToUpdate['payment_option_id'],
                'payment_method' => $paymentData['paymentMethod'],
                'buyer_name' => $name,
                'buyer_email' => $email
            ]);
        } catch (\Throwable $e) {
            Log::error('Error updating buyer data on OrderDetail', [
                'error' => $e->getMessage(),
                'order_detail_id' => $orderDetail->id,
            ]);
        }
    }

    /**
     * Almacenar cliente frecuente
     */
    private function storeFrequentClient(array $formData): void
    {
        try {
            $frequentClientData = [
                'full_name' => $formData['name'] ?? '',
                'document_id' => $this->resolveDocumentTypeId($formData['documentType'] ?? ''),
                'document' => $formData['documentNumber'] ?? '',
                'email' => $formData['email'] ?? '',
                'phone_code' => $formData['code_phone'] ?? '+56',
                'phone' => $formData['phone'] ?? '',
                'country_id' => $formData['countryId'] ?? '',
                'region_id' => $formData['regionId'] ?? '',
                'comune_id' => $formData['cityId'] ?? '',
                'terms_accepted' => $formData['termsAccepted'] ?? false,
                'marketing_accepted' => $formData['marketingAccepted'] ?? false,
            ];

            if (!empty($frequentClientData['full_name']) && 
                !empty($frequentClientData['document_id']) && 
                !empty($frequentClientData['document'])) {
                
                $storedClient = FrequentClientService::store($frequentClientData);
                
                Log::info('Frequent client stored successfully', [
                    'client_id' => $storedClient->id,
                    'document' => $storedClient->document,
                    'full_name' => $storedClient->full_name
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Error storing frequent client, continuing with payment', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Crear transacción en el gateway
     */
    private function createGatewayTransaction($orderDetail, $paymentData)
    {
        $amount = $orderDetail->amount;
        $orderId = $orderDetail->order_id . '-' . $orderDetail->installment_number;

        // Log para verificar el estado del OrderDetail antes de crear la transacción
        Log::info('Creating gateway transaction', [
            'order_detail_id' => $orderDetail->id,
            'installment_number' => $orderDetail->installment_number,
            'current_payment_gateway_id' => $orderDetail->payment_gateway_id,
            'payment_method' => $paymentData['paymentMethod'] ?? 'unknown',
            'amount' => $amount,
            'order_id' => $orderId
        ]);

        // URLs de retorno con parámetro gateway explícito
        $transbankCallbackUrl = route('payment.callback', ['orderDetailId' => $orderDetail->id, 'gateway' => 'transbank']);
        $khipuCallbackUrl = route('payment.callback', ['orderDetailId' => $orderDetail->id, 'gateway' => 'khipu']);
        $failureUrl = route('payment.failure', ['orderDetailId' => $orderDetail->id]);

        $method = (string) ($paymentData['paymentMethod'] ?? '');
        $normalizedMethod = $method;
        $installmentsOverride = null;
        
        if ($method !== '') {
            if (stripos($method, 'credit') !== false) {
                $normalizedMethod = 'credit';
                if (preg_match('/(\d+)/', $method, $m)) {
                    $n = (int) $m[1];
                    if ($n > 0) { $installmentsOverride = $n; }
                }
                if ($installmentsOverride === null) { $installmentsOverride = 1; }
            }
        }

        Log::info('createGatewayTransaction normalized method', [
            'original' => $method,
            'normalized' => $normalizedMethod,
            'installmentsOverride' => $installmentsOverride,
        ]);

        switch ($normalizedMethod) {
            case 'debit':
            case 'credit':
                $paymentType = $paymentData['paymentType'] ?? null;
                $installments = $installmentsOverride ?? ($paymentData['installments'] ?? null);

                return $this->transbankService->createTransaction(
                    $orderId,
                    $amount,
                    $transbankCallbackUrl,
                    null, // notification URL
                    $paymentType,
                    $installments
                );

            case 'khipu':
                // Para Khipu, necesitamos crear la transacción primero para obtener el payment_id
                $khipuResult = $this->khipuService->createTransaction($orderId, $amount, $khipuCallbackUrl, null);
                
                if ($khipuResult['success']) {
                    // Agregar el payment_id a la URL de retorno
                    $paymentId = $khipuResult['payment_id'] ?? null;
                    if ($paymentId) {
                        $khipuCallbackUrlWithPaymentId = $khipuCallbackUrl . '&payment_id=' . urlencode($paymentId);
                        // Actualizar el resultado con la URL corregida
                        $khipuResult['return_url'] = $khipuCallbackUrlWithPaymentId;
                    }
                }
                
                return $khipuResult;

            default:
                return [
                    'success' => false,
                    'error' => 'Método de pago no soportado'
                ];
        }
    }

    /**
     * Registrar pago pendiente en payments
     */
    private function recordPendingPayment(OrderDetail $orderDetail, string $gatewayType, array $gatewayResult): void
    {
        try {
            $buyOrder = $orderDetail->order_id . '-' . $orderDetail->installment_number;
            
            // Log para verificar que los datos del orderDetail estén correctos
            Log::info('Recording pending payment', [
                'order_detail_id' => $orderDetail->id,
                'installment_number' => $orderDetail->installment_number,
                'payment_gateway_id' => $orderDetail->payment_gateway_id,
                'payment_option_id' => $orderDetail->payment_option_id,
                'gateway_type' => $gatewayType,
                'amount' => $orderDetail->amount
            ]);
            
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
                } else {
                    $data = array_merge($commonData, [
                        'token' => $gatewayResult['token'] ?? null,
                    ]);
                }

                $payment = Payment::create($data);
            }
            
            Log::info('Payment record created successfully', [
                'payment_id' => $payment->id,
                'payment_gateway_id' => $payment->payment_gateway_id,
                'payment_option_id' => $payment->payment_option_id,
                'gateway_type' => $gatewayType
            ]);
        } catch (\Throwable $e) {
            Log::error('Error recording pending payment', [
                'error' => $e->getMessage(),
                'order_detail_id' => $orderDetail->id,
            ]);
        }
    }

    /**
     * Normalizar tipo de gateway para el frontend
     */
    private function normalizeGatewayType(string $method): string
    {
        $methodRaw = (string) $method;
        if (stripos($methodRaw, 'credit') !== false) { return 'credit'; }
        else if (stripos($methodRaw, 'debit') !== false) { return 'debit'; }
        return 'other';
    }

    /**
     * Resolver payment_option_id para actualización
     */
    private function resolvePaymentOptionIdForUpdate(int $programId, array $paymentData): ?int
    {
        $mode = ($paymentData['paymentType'] ?? 'total') === 'monthly' ? 'lat90' : 'full';
        $method = $paymentData['paymentMethod'] ?? 'debit';
        $code = null;
        
        if ($mode === 'full') {
            switch ($method) {
                case 'khipu': $code = 'full_transfer_khipu'; break;
                case 'debit': $code = 'full_debit_webpay'; break;
                case 'credit_0': $code = 'full_credit_webpay_0'; break;
                case 'credit_3': $code = 'full_credit_webpay_3'; break;
                case 'credit_6': $code = 'full_credit_webpay_6'; break;
                case 'credit_9': $code = 'full_credit_webpay_9'; break;
                case 'credit_12': $code = 'full_credit_webpay_12'; break;
                default:
                    if (strpos($method, 'credit') === 0) {
                        $suffix = trim(str_replace('credit', '', $method), '_');
                        $n = $suffix !== '' ? (int)$suffix : 0;
                        $code = 'full_credit_webpay_' . $n;
                    }
                    break;
            }
        } else {
            switch ($method) {
                case 'khipu': $code = 'lat90_transfer_khipu'; break;
                case 'debit': $code = 'lat90_debit_webpay'; break;
                case 'credit': $code = 'lat90_credit_0'; break;
            }
        }

        if (!$code) { return null; }

        $optionId = DB::table('payment_options')->where('code', $code)->value('id');
        if (!$optionId) { return null; }
        
        $enabled = DB::table('program_payment_option')
            ->where('program_id', $programId)
            ->where('payment_option_id', $optionId)
            ->where('enabled', true)
            ->exists();
            
        return $enabled ? (int)$optionId : null;
    }

    // Métodos de resolución de IDs (mantener los existentes)
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

    // Métodos deprecados - redirigir al nuevo controlador
    public function paymentSuccess($orderDetailId)
    {
        return redirect()->route('payment.success', $orderDetailId);
    }

    public function paymentFailure($orderDetailId)
    {
        return redirect()->route('payment.failure', $orderDetailId);
    }

    public function confirmKhipu(Request $request)
    {
        return redirect()->route('payment.confirm');
    }
}
