<?php

namespace App\Services\Client\PaymentProcessing;

use App\Services\Client\Payment\InstallmentService;
use App\Services\Client\Payment\PaymentOrderService;
use App\Services\Client\PaymentGateway\VirtualPosService;
use App\Services\Client\PaymentGateway\KhipuService;
use App\Services\Client\Orders\CreateOrderService;
use App\Services\Client\Integration\BsaleService;
use App\Services\EcommerceAnalyticsService;
use App\Services\Client\PaymentProcessing\ValidatePaymentEligibilityService;
use App\Services\Client\PaymentProcessing\UpdateBuyerDataService;
use App\Services\Client\PaymentProcessing\StoreFrequentClientService;
use App\Services\Client\PaymentProcessing\CreateGatewayTransactionService;
use App\Services\Client\PaymentProcessing\RecordPendingPaymentService;
use App\Modules\Installments\Contracts\InstallmentServiceInterface;
use App\Traits\SystemLogging;
use Illuminate\Http\Request;

class ProcessPaymentService
{
    use SystemLogging;

    public function __construct(
        private InstallmentService $installmentService,
        private InstallmentServiceInterface $installmentManager,
        private PaymentOrderService $paymentOrderService,
        private VirtualPosService $virtualPosService,
        private KhipuService $khipuService,
        private CreateOrderService $createOrderService,
        private BsaleService $bsaleService,
        private EcommerceAnalyticsService $analyticsService,
        private ValidatePaymentEligibilityService $validatePaymentEligibilityService,
        private UpdateBuyerDataService $updateBuyerDataService,
        private StoreFrequentClientService $storeFrequentClientService,
        private CreateGatewayTransactionService $createGatewayTransactionService,
        private RecordPendingPaymentService $recordPendingPaymentService
    ) {}

    /**
     * Procesar el pago completo
     *
     * @param Request $request
     * @return array
     */
    public function execute(Request $request): array
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
                $this->logInfo('Session ID received from frontend', [
                    'session_id' => $sessionId,
                    'program_id' => $programId,
                    'rut' => $rut
                ]);
            } else {
                $this->logWarning('No session_id received from frontend', [
                    'program_id' => $programId,
                    'rut' => $rut
                ]);
            }

            if (!$paymentData || !$formData) {
                return [
                    'success' => false,
                    'error' => 'Datos de pago o formulario no encontrados'
                ];
            }

            // Validar que el participante existe y tiene saldo pendiente
            $validationResult = $this->validatePaymentEligibilityService->execute($programId, $rut, $paymentData);
            if (!$validationResult['success']) {
                return [
                    'success' => false,
                    'error' => $validationResult['error']
                ];
            }

            $this->logInfo('Processing payment', [
                'program_id' => $programId,
                'rut' => $rut,
                'payment_type' => $paymentData['paymentType'] ?? 'unknown',
                'payment_method' => $paymentData['paymentMethod'] ?? 'unknown'
            ]);

            // Log para verificar que session_id se está pasando correctamente
            $this->logInfo('Processing payment with session_id', [
                'session_id' => $paymentData['session_id'] ?? 'NULL',
                'payment_type' => $paymentData['paymentType'] ?? 'unknown'
            ]);

            \Log::info('=== PROCESS PAYMENT: Before order creation ===', [
                'payment_type' => $paymentData['paymentType'] ?? 'total',
                'payment_method' => $paymentData['paymentMethod'] ?? 'unknown',
            ]);

            // Determinar el tipo de pago y crear la orden correspondiente
            if (($paymentData['paymentType'] ?? 'total') === 'monthly') {
                // PAGO DE CUOTAS MENSUALES
                $result = $this->processMonthlyPayment($programId, $rut, $paymentData, $formData);
            } else {
                // PAGO TOTAL
                $result = $this->processTotalPayment($programId, $rut, $paymentData, $formData);
            }

            \Log::info('=== PROCESS PAYMENT: Order creation result ===', [
                'success' => $result['success'] ?? false,
                'error' => $result['error'] ?? null,
            ]);

            if (!$result['success']) {
                return [
                    'success' => false,
                    'error' => $result['error']
                ];
            }

            $order = $result['order'];
            $orderDetail = $result['order_detail'];
            $installment = $result['installment'] ?? null;

            \Log::info('=== PROCESS PAYMENT: Order created successfully ===', [
                'order_id' => $order->id,
                'order_detail_id' => $orderDetail->id,
            ]);

            $this->logInfo('=== PAYMENT FLOW STEP 1: Order created ===', [
                'order_id' => $order->id,
                'order_detail_id' => $orderDetail->id,
            ]);

            // Actualizar datos del comprador y método de pago en el OrderDetail
            $this->updateBuyerDataService->execute($orderDetail, $formData, $paymentData);

            $this->logInfo('=== PAYMENT FLOW STEP 2: Buyer data updated ===');

            // Almacenar cliente frecuente para futuras compras
            $this->storeFrequentClientService->execute($formData);

            $this->logInfo('=== PAYMENT FLOW STEP 3: Frequent client stored ===');
            $this->logInfo('=== PAYMENT FLOW STEP 4: About to create gateway transaction ===', [
                'payment_method' => $paymentData['paymentMethod'] ?? 'unknown',
                'use_virtualpos' => config('lat90.payment.use_virtualpos'),
            ]);

            \Log::info('=== PROCESS PAYMENT: About to call gateway ===', [
                'payment_method' => $paymentData['paymentMethod'] ?? 'unknown',
                'use_virtualpos' => config('lat90.payment.use_virtualpos'),
            ]);

            // Crear transacción en el gateway de pago
            $gatewayResult = $this->createGatewayTransactionService->execute($orderDetail, $paymentData, $formData);

            \Log::info('=== PROCESS PAYMENT: Gateway result ===', [
                'success' => $gatewayResult['success'] ?? false,
                'error' => $gatewayResult['error'] ?? null,
                'has_url' => isset($gatewayResult['url']),
            ]);

            $this->logInfo('=== PAYMENT FLOW STEP 5: Gateway transaction completed ===', [
                'success' => $gatewayResult['success'] ?? false,
                'result' => $gatewayResult,
            ]);

            if (!$gatewayResult['success']) {
                return [
                    'success' => false,
                    'error' => $gatewayResult['error']
                ];
            }

            // Cargar la relación order para asegurar que esté disponible
            $orderDetail->load('order');
            
            // Registrar pago pendiente en la tabla payments
            $this->recordPendingPaymentService->execute($orderDetail, $paymentData['paymentMethod'], $gatewayResult);

            // NO marcar la cuota como pagada aquí
            // Solo se marcará como pagada cuando se confirme el pago con la pasarela
            if ($installment) {
                $this->logInfo('Installment payment initiated, will be marked as paid when confirmed', [
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

            return [
                'success' => true,
                'order_id' => $order->id,
                'order_detail_id' => $orderDetail->id,
                'order_number' => $order->order_number,
                'gateway_url' => $gatewayUrl,
                'gateway_token' => $gatewayToken,
                'gateway_type' => $frontendGatewayType
            ];
        } catch (\Exception $e) {
            $this->logError('Error processing payment', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'error' => 'Error interno del servidor: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Procesar pago de cuotas mensuales
     */
    private function processMonthlyPayment(int $programId, string $rut, array $paymentData, array $formData): array
    {
        // Usar el nuevo módulo de Installments si está habilitado
        if ($this->installmentManager->isEnabled()) {
            // Crear o recuperar plan de cuotas usando el nuevo módulo
            $installmentPlan = $this->installmentManager->createOrGetPlan(
                $programId,
                $rut,
                $paymentData,
                $formData
            );

            if (!$installmentPlan) {
                return [
                    'success' => false,
                    'error' => 'No se pudo crear o recuperar el plan de cuotas'
                ];
            }

            // Obtener siguiente cuota pendiente
            $nextInstallment = $this->installmentManager->getNextPendingInstallment($installmentPlan->id);

            if (!$nextInstallment) {
                return [
                    'success' => false,
                    'error' => 'No hay cuotas pendientes'
                ];
            }
        } else {
            // Fallback al servicio antiguo
            $installmentResult = $this->installmentService->createOrGetInstallmentPlan(
                $programId,
                $rut,
                $paymentData,
                $formData
            );

            if (!$installmentResult['success']) {
                return $installmentResult;
            }

            $installmentPlan = $installmentResult['installment_plan'];
            $nextInstallment = $installmentResult['next_installment'];
        }

        // Crear nueva orden de pago para esta cuota específica
        $paymentResult = $this->paymentOrderService->createPaymentOrder(
            $nextInstallment,
            $paymentData,
            $formData
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
            $programId,
            $rut,
            $paymentData,
            $formData
        );
    }

    /**
     * Normalizar tipo de gateway para el frontend
     */
    private function normalizeGatewayType(string $method): string
    {
        $methodRaw = (string) $method;
        // VirtualPOS: debit_credit_0, debit_credit_3, etc.
        if (stripos($methodRaw, 'debit_credit') !== false) {
            return 'virtualpos';
        }
        // Legacy Transbank
        else if (stripos($methodRaw, 'credit') !== false) {
            return 'credit';
        } else if (stripos($methodRaw, 'debit') !== false) {
            return 'debit';
        }
        return 'other';
    }
}
