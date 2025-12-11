<?php

namespace App\Services\Client\PaymentGateway;

use GuzzleHttp\Client;
use App\Traits\SystemLogging;
use Firebase\JWT\JWT;
use Illuminate\Support\Str;

class KhipuService
{
    use SystemLogging;

    private $client;
    private $apiKey;
    private $secretKey;
    private $isProduction;

    // URLs para cada ambiente
    private const KHIPU_BASE_URL = 'https://payment-api.khipu.com';
    private const VIRTUALPOS_PROD_URL = 'https://api.virtualpos.cl/v3';  // API v3 como VirtualPosService
    private const VIRTUALPOS_SANDBOX_URL = 'https://api.virtualpos-sandbox.com/v3';

    public function __construct()
    {
        $this->client = new Client();
        // Usar el flag global de pagos para determinar ambiente
        $this->isProduction = config('lat90.payment.use_virtualpos', false);

        // Seleccionar credenciales según el ambiente
        if ($this->isProduction) {
            // Producción: usar VirtualPos
            $this->apiKey = config('services.khipu.production.api_key');
            $this->secretKey = config('services.khipu.production.secret_key');
        } else {
            // Test/QA: usar API nativa de Khipu
            $this->apiKey = config('services.khipu.test.api_key', 'ae940262-4f1a-4a0d-aef3-e5406b533b44');
            $this->secretKey = config('services.khipu.test.secret_key');
        }

        $this->logInfo('=== KhipuService: CONSTRUCTOR CALLED ===', [
            'is_production' => $this->isProduction,
            'mode' => $this->isProduction ? 'VirtualPos' : 'Khipu nativo',
            'api_key_preview' => !empty($this->apiKey) ? substr($this->apiKey, 0, 10) . '...' : 'EMPTY',
            'secret_key_length' => strlen($this->secretKey ?? ''),
            'config_use_virtualpos' => config('lat90.payment.use_virtualpos'),
        ]);
    }

    /**
     * Verificar si está en modo producción
     */
    public function isProductionMode(): bool
    {
        return $this->isProduction;
    }

    /**
     * Crear transacción - delega al método apropiado según el ambiente
     */
    public function createTransaction($orderId, $amount, $returnUrl, $notificationUrl = null, $customerData = [])
    {
        \Log::info('=== KHIPU SERVICE: createTransaction called ===', [
            'order_id' => $orderId,
            'amount' => $amount,
            'is_production' => $this->isProduction,
            'will_use' => $this->isProduction ? 'VirtualPos' : 'Khipu nativo',
        ]);

        if ($this->isProduction) {
            return $this->createVirtualPosTransaction($orderId, $amount, $returnUrl, $notificationUrl, $customerData);
        }

        return $this->createKhipuTransaction($orderId, $amount, $returnUrl, $notificationUrl);
    }

    /**
     * Consultar estado de pago - delega al método apropiado según el ambiente
     */
    public function getPaymentStatus(string $paymentId): array
    {
        if ($this->isProduction) {
            return $this->getVirtualPosPaymentStatus($paymentId);
        }

        return $this->getKhipuPaymentStatus($paymentId);
    }

    // =========================================================================
    // KHIPU NATIVO (Test/QA)
    // =========================================================================

    /**
     * Crear transacción usando la API v3.0 de Khipu (nativa)
     */
    private function createKhipuTransaction($orderId, $amount, $returnUrl, $notificationUrl = null)
    {
        try {
            $payload = [
                'amount' => (int) $amount,
                'currency' => 'CLP',
                'subject' => "Pago de orden #{$orderId}",
                'return_url' => $returnUrl,
            ];

            if (!empty($notificationUrl)) {
                $payload['notify_url'] = $notificationUrl;
            }

            $headers = [
                'Content-Type' => 'application/json',
                'x-api-key' => $this->apiKey
            ];

            $response = $this->client->post(self::KHIPU_BASE_URL . '/v3/payments', [
                'headers' => $headers,
                'json' => $payload
            ]);

            $result = json_decode($response->getBody()->getContents(), true);

            $this->logInfo('Khipu createTransaction response', [
                'order_id' => $orderId,
                'amount' => $amount,
                'return_url' => $returnUrl,
                'response' => $result,
            ]);

            return [
                'success' => true,
                'payment_id' => $result['payment_id'] ?? null,
                'payment_url' => $result['payment_url'] ?? null,
                'url' => $result['payment_url'] ?? null,
            ];
        } catch (\GuzzleHttp\Exception\ClientException $e) {
            $body = (string) ($e->getResponse() ? $e->getResponse()->getBody() : '');
            $this->logError('Error creating Khipu payment (ClientException)', [
                'error' => $e->getMessage(),
                'amount' => $amount,
                'order_id' => $orderId,
                'response' => $body,
            ], $e);

            return [
                'success' => false,
                'error' => $body ?: $e->getMessage(),
            ];
        } catch (\Exception $e) {
            $this->logError('Error creating Khipu payment', [
                'error' => $e->getMessage(),
                'amount' => $amount,
                'order_id' => $orderId
            ], $e);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Consultar el estado de un pago Khipu nativo
     */
    private function getKhipuPaymentStatus(string $paymentId): array
    {
        try {
            $headers = [
                'x-api-key' => $this->apiKey
            ];

            $response = $this->client->get(self::KHIPU_BASE_URL . '/v3/payments/' . $paymentId, [
                'headers' => $headers
            ]);

            $result = json_decode($response->getBody()->getContents(), true);
            $approved = isset($result['status']) && in_array($result['status'], ['done', 'paid', 'approved', 'completed']);

            $transactionDate = $this->extractTransactionDate($result, $approved);

            $this->logInfo('Khipu getPaymentStatus response', [
                'payment_id' => $paymentId,
                'approved' => $approved,
                'status' => $result['status'] ?? 'unknown',
                'transaction_date' => $transactionDate,
                'response' => $result,
            ]);

            return [
                'success' => $approved,
                'status' => $result['status'] ?? 'unknown',
                'transaction_date' => $transactionDate,
                'data' => $result,
            ];
        } catch (\Exception $e) {
            $this->logError('Error getting Khipu payment status', [
                'error' => $e->getMessage(),
                'payment_id' => $paymentId
            ], $e);

            return [
                'success' => false,
                'status' => 'error',
                'error' => $e->getMessage()
            ];
        }
    }

    // =========================================================================
    // VIRTUALPOS (Producción)
    // =========================================================================

    /**
     * Crear transacción usando VirtualPos API v2
     */
    private function createVirtualPosTransaction($orderId, $amount, $returnUrl, $notificationUrl = null, $customerData = [])
    {
        \Log::info('=== VIRTUALPOS: createVirtualPosTransaction START (API v3) ===', [
            'order_id' => $orderId,
            'amount' => $amount,
            'api_key_preview' => substr($this->apiKey ?? '', 0, 10) . '...',
        ]);

        try {
            $baseUrl = self::VIRTUALPOS_PROD_URL;

            // Formatear teléfono según requerimiento de VirtualPOS
            $phone = $this->formatPhone($customerData['phone'] ?? '56912345678');

            // Codificar URLs en Base64 según documentación
            $encodedReturnUrl = base64_encode($returnUrl);
            $encodedCallbackUrl = $notificationUrl ? base64_encode($notificationUrl) : null;

            // Payload según API v3 (mismo formato que VirtualPosService)
            $payload = [
                'amount' => (int) $amount,
                'email' => $customerData['email'] ?? 'cliente@latitud90.cl',
                'social_id' => str_replace(['.', '-'], '', $customerData['rut'] ?? '111111111'),
                'first_name' => $customerData['first_name'] ?? 'Cliente',
                'last_name' => $customerData['last_name'] ?? 'Latitud90',
                'phone' => $phone,
                'description' => $customerData['description'] ?? "Pago de orden #{$orderId}",
                'merchant_internal_code' => (string) $orderId,
                'merchant_internal_channel' => 'Portal Web',
                'return_url' => $encodedReturnUrl,
            ];

            // Agregar callback_url si se proporciona
            if ($encodedCallbackUrl) {
                $payload['callback_url'] = $encodedCallbackUrl;
            }

            // Generar firma JWT según documentación de VirtualPOS v3
            $signature = $this->generateVirtualPosSignature([]);

            $headers = [
                'Content-Type' => 'application/json',
                'Authorization' => $this->apiKey,
                'Signature' => $signature
            ];

            // LOG DETALLADO PARA DEBUG
            \Log::info('=== VIRTUALPOS DEBUG (API v3) ===', [
                'url' => $baseUrl . '/payment/',
                'api_key_preview' => substr($this->apiKey, 0, 10) . '...',
                'secret_key_length' => strlen($this->secretKey ?? ''),
                'payload' => $payload,
            ]);

            $response = $this->client->post($baseUrl . '/payment/', [
                'headers' => $headers,
                'json' => $payload
            ]);

            $responseBody = $response->getBody()->getContents();
            $result = json_decode($responseBody, true);

            \Log::info('=== VIRTUALPOS: Response received ===', [
                'http_status' => $response->getStatusCode(),
                'response' => $result,
            ]);

            // VirtualPos v3 devuelve status "OK" para éxito
            if (isset($result['status']) && $result['status'] === 'OK') {
                $paymentUuid = $result['payment']['order']['uuid'] ?? null;

                // Si tenemos UUID, obtener la URL de checkout
                if ($paymentUuid) {
                    $checkoutUrl = $this->getCheckoutUrl($paymentUuid, $returnUrl, $notificationUrl);

                    return [
                        'success' => true,
                        'payment_id' => $paymentUuid,
                        'payment_url' => $checkoutUrl,
                        'url' => $checkoutUrl,
                        'uuid' => $paymentUuid,
                    ];
                }
            }

            return [
                'success' => false,
                'error' => $result['message'] ?? $result['error']['message'] ?? json_encode($result) ?? 'Error desconocido en VirtualPos',
            ];

        } catch (\GuzzleHttp\Exception\ClientException $e) {
            $body = (string) ($e->getResponse() ? $e->getResponse()->getBody() : '');
            $statusCode = $e->getResponse() ? $e->getResponse()->getStatusCode() : 'unknown';

            \Log::error('=== VIRTUALPOS ERROR (ClientException) ===', [
                'http_status' => $statusCode,
                'error_message' => $e->getMessage(),
                'response_body' => $body,
                'order_id' => $orderId,
            ]);

            return [
                'success' => false,
                'error' => $body ?: $e->getMessage(),
            ];
        } catch (\Exception $e) {
            \Log::error('=== VIRTUALPOS ERROR (Exception) ===', [
                'error_class' => get_class($e),
                'error_message' => $e->getMessage(),
                'order_id' => $orderId,
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Obtener URL de checkout para VirtualPOS con Khipu
     */
    private function getCheckoutUrl($paymentUuid, $returnUrl, $callbackUrl = null)
    {
        try {
            $baseUrl = self::VIRTUALPOS_PROD_URL;
            $signature = $this->generateVirtualPosSignature([]);

            $headers = [
                'Content-Type' => 'application/json',
                'Authorization' => $this->apiKey,
                'Signature' => $signature
            ];

            // Usar 'khipu' como método de pago para transferencia bancaria
            $payload = [
                'return_url' => base64_encode($returnUrl),
                'callback_url' => base64_encode($callbackUrl ?: $returnUrl),
                'payment_method' => 'khipu'  // Khipu para transferencia bancaria
            ];

            \Log::info('=== VIRTUALPOS: Getting checkout URL for Khipu ===', [
                'payment_uuid' => $paymentUuid,
                'payment_method' => 'khipu',
                'return_url' => $returnUrl,
            ]);

            $response = $this->client->post($baseUrl . "/payment/{$paymentUuid}/webcheckout", [
                'headers' => $headers,
                'json' => $payload
            ]);

            $result = json_decode($response->getBody()->getContents(), true);

            \Log::info('=== VIRTUALPOS: Checkout URL response ===', [
                'status_code' => $response->getStatusCode(),
                'result' => $result,
            ]);

            if ($response->getStatusCode() == 200 && isset($result['url_redirect'])) {
                return $result['url_redirect'];
            }

            throw new \Exception($result['message'] ?? $result['error']['message'] ?? 'No se pudo obtener la URL de checkout');

        } catch (\Exception $e) {
            \Log::error('Error getting VirtualPos checkout URL for Khipu', [
                'payment_uuid' => $paymentUuid,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Formatear teléfono según requerimiento de VirtualPOS (569NNNNNNNN)
     */
    private function formatPhone($phone)
    {
        $cleanPhone = preg_replace('/[^0-9]/', '', $phone);

        if (strpos($cleanPhone, '56') === 0 && strlen($cleanPhone) > 9) {
            $cleanPhone = substr($cleanPhone, 2);
        }

        if (strpos($cleanPhone, '9') !== 0) {
            $cleanPhone = '9' . $cleanPhone;
        }

        if (strlen($cleanPhone) === 9) {
            $cleanPhone = '56' . $cleanPhone;
        }

        return $cleanPhone;
    }

    /**
     * Consultar el estado de un pago VirtualPos (API v3)
     */
    private function getVirtualPosPaymentStatus(string $uuid): array
    {
        try {
            $baseUrl = self::VIRTUALPOS_PROD_URL;

            // Generar firma JWT para la consulta
            $signature = $this->generateVirtualPosSignature([]);

            $headers = [
                'Content-Type' => 'application/json',
                'Authorization' => $this->apiKey,
                'Signature' => $signature
            ];

            $response = $this->client->get($baseUrl . "/payment/{$uuid}", [
                'headers' => $headers
            ]);

            $result = json_decode($response->getBody()->getContents(), true);

            $this->logInfo('VirtualPos getPaymentStatus response (API v3)', [
                'uuid' => $uuid,
                'response' => $result,
            ]);

            // Extraer datos según estructura de respuesta v3
            $status = $result['status'] ?? 'unknown';
            $paymentData = $result['payment'] ?? [];
            $orderData = $paymentData['order'] ?? [];

            $approved = in_array($status, ['approved', 'paid', 'success', 'aprobado']);

            // Extraer fecha de transacción
            $transactionDate = null;
            if (isset($orderData['payment_date'])) {
                $transactionDate = $this->parseTransactionDate($orderData['payment_date']);
            } elseif ($approved) {
                $transactionDate = now('America/Santiago')->format('Y-m-d H:i:s');
            }

            return [
                'success' => $approved,
                'status' => $this->mapVirtualPosStatus($status),
                'transaction_date' => $transactionDate,
                'data' => $result,
                'auth_code' => $paymentData['auth_code'] ?? $orderData['auth_code'] ?? null,
                'card_number' => $orderData['card_number'] ?? null,
                'payment_type' => $orderData['payment_type_code'] ?? null,
            ];
        } catch (\Exception $e) {
            $this->logError('Error getting VirtualPos payment status', [
                'error' => $e->getMessage(),
                'uuid' => $uuid
            ], $e);

            return [
                'success' => false,
                'status' => 'error',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Generar firma JWT para VirtualPos v3
     * Según la documentación, el payload del JWT debe contener la API KEY y un UUID único
     */
    private function generateVirtualPosSignature(array $params): string
    {
        $jwtPayload = [
            'api_key' => $this->apiKey,
            'uuid' => Str::uuid()->toString(),
        ];

        return JWT::encode($jwtPayload, $this->secretKey, 'HS256');
    }

    /**
     * Mapear estados de VirtualPos a estados normalizados
     */
    private function mapVirtualPosStatus(string $vpStatus): string
    {
        $statusMap = [
            'pendiente' => 'pending',
            'pagado' => 'done',
            'paid' => 'done',
            'approved' => 'done',
            'completed' => 'done',
            'rechazado' => 'rejected',
            'rejected' => 'rejected',
            'anulado' => 'cancelled',
            'cancelled' => 'cancelled',
        ];

        return $statusMap[strtolower($vpStatus)] ?? $vpStatus;
    }

    // =========================================================================
    // UTILIDADES COMUNES
    // =========================================================================

    /**
     * Extraer fecha de transacción de la respuesta
     */
    private function extractTransactionDate(array $result, bool $approved): ?string
    {
        if (isset($result['paid_at'])) {
            return $this->parseTransactionDate($result['paid_at']);
        } elseif (isset($result['updated_at'])) {
            return $this->parseTransactionDate($result['updated_at']);
        } elseif (isset($result['created_at'])) {
            return $this->parseTransactionDate($result['created_at']);
        } elseif ($approved) {
            return now('America/Santiago')->format('Y-m-d H:i:s');
        }

        return null;
    }

    /**
     * Parsear la fecha de transacción para que sea compatible con MySQL
     */
    private function parseTransactionDate(string $dateString): string
    {
        $date = \Carbon\Carbon::parse($dateString);
        return $date->format('Y-m-d H:i:s');
    }
}
