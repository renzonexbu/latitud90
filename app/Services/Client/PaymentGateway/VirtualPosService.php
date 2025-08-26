<?php

namespace App\Services\Client\PaymentGateway;

use GuzzleHttp\Client;
use App\Traits\SystemLogging;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class VirtualPosService
{
    use SystemLogging;
    private $client;
    private $baseUrl;
    private $config;

    public function __construct()
    {
        $this->client = new Client();
        $this->baseUrl = 'https://api.virtualpos.cl/v3';
        $this->config = config('services.virtualpos');
    }

    /**
     * Crear transacción usando la API de VirtualPOS
     */
    public function createTransaction($orderId, $amount, $returnUrl, $notificationUrl = null, $paymentType = null, $installments = null, $customerEmail = null, $customerDocument = null, $customerName = null, $customerPhone = null)
    {
        try {
            // Determinar configuración según tipo de pago y cuotas
            $configKey = $this->getConfigKey($paymentType, $installments);
            $config = $this->config[$configKey] ?? $this->config['no_cuotes'];

            // Usar email del cliente si está disponible, sino usar email por defecto
            $email = $customerEmail ?: 'pagos@latitud90.cl';

            // Separar nombre completo en first_name y last_name
            $nameParts = $this->splitFullName($customerName ?: 'Cliente Latitud90');
            $firstName = $nameParts['first_name'];
            $lastName = $nameParts['last_name'];

            // Formatear teléfono según requerimiento (569NNNNNNNN)
            $formattedPhone = $this->formatPhone($customerPhone ?: '56912345678');

            // Codificar URLs en Base64 según documentación
            $encodedReturnUrl = base64_encode($returnUrl);
            $encodedCallbackUrl = $notificationUrl ? base64_encode($notificationUrl) : null;

            $payload = [
                'amount' => (int) $amount,
                'email' => $email,
                'social_id' => $customerDocument ?: '12345678-9',
                'first_name' => $firstName,
                'last_name' => $lastName,
                'phone' => $formattedPhone,
                'description' => "Pago de orden #{$orderId}",
                'merchant_internal_code' => $orderId,
                'merchant_internal_channel' => 'Portal Web',
                'return_url' => $encodedReturnUrl,
            ];

            // Agregar callback_url si se proporciona
            if ($encodedCallbackUrl) {
                $payload['callback_url'] = $encodedCallbackUrl;
            }

            // Agregar notify_url si se proporciona
            if (!empty($notificationUrl)) {
                $payload['notify_url'] = $notificationUrl;
            }

            // Preparar callback URL para la segunda llamada
            $callbackUrl = null;
            if ($encodedCallbackUrl) {
                $callbackUrl = base64_decode($encodedCallbackUrl);
            }

            // El número de cuotas ya está incluido en el payload base

            // Generar firma JWT según documentación de VirtualPOS
            $signature = $this->generateJWTSignature($config, $payload);

            $headers = [
                'Content-Type' => 'application/json',
                'Authorization' => $config['api_key'],
                'Signature' => $signature
            ];
            $this->logInfo('VirtualPOS createTransaction request', [
                'order_id' => $orderId,
                'amount' => $amount,
                'config_key' => $configKey,
                'commerce_code' => $config['commerce_code'],
                'installments' => $installments,
                'payload' => $payload
            ]);

            $response = $this->client->post($this->baseUrl . '/payment/', [
                'headers' => $headers,
                'json' => $payload
            ]);

            $result = json_decode($response->getBody()->getContents(), true);

            $this->logInfo('VirtualPOS createTransaction response', [
                'order_id' => $orderId,
                'http_status' => $response->getStatusCode(),
                'response' => $result,
            ]);

            if ($response->getStatusCode() === 200 || $response->getStatusCode() === 201) {
                // Verificar si la respuesta indica éxito
                if (isset($result['status']) && $result['status'] === 'OK') {
                    $paymentUuid = $result['payment']['order']['uuid'] ?? null;

                    // Si tenemos UUID, obtener la URL de checkout
                    if ($paymentUuid) {
                        $checkoutUrl = $this->getCheckoutUrl($paymentUuid, $config, $returnUrl, $callbackUrl);
                        return [
                            'success' => true,
                            'payment_id' => $paymentUuid,
                            'url' => $checkoutUrl,
                            'token' => $paymentUuid,
                            'full_response' => $result
                        ];
                    } else {
                        return [
                            'success' => false,
                            'error' => 'No se pudo obtener el UUID del pago',
                            'full_response' => $result
                        ];
                    }
                } else {
                    $errorMessage = 'Error en VirtualPOS';
                    if (isset($result['error']['message'])) {
                        $errorMessage = $result['error']['message'];
                    } elseif (isset($result['message'])) {
                        $errorMessage = $result['message'];
                    }

                    return [
                        'success' => false,
                        'error' => $errorMessage,
                        'full_response' => $result
                    ];
                }
            } else {
                return [
                    'success' => false,
                    'error' => $result['message'] ?? 'Error al crear transacción en VirtualPOS',
                    'full_response' => $result
                ];
            }
        } catch (\Exception $e) {
            $this->logError('VirtualPOS createTransaction error', [
                'order_id' => $orderId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ], $e);

            return [
                'success' => false,
                'error' => 'Error de conexión con VirtualPOS: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Confirmar transacción de VirtualPOS
     */
    public function confirmTransaction($paymentId)
    {
        try {
            // Determinar configuración (usar no_cuotes por defecto para consultas)
            $config = $this->config['no_cuotes'];

            // Generar firma JWT para consulta GET
            $signature = $this->generateJWTSignature($config, ['payment_id' => $paymentId]);

            $headers = [
                'Content-Type' => 'application/json',
                'Authorization' => $config['api_key'],
                'Signature' => $signature
            ];

            $this->logInfo('VirtualPOS confirmTransaction request', [
                'payment_id' => $paymentId,
                'commerce_code' => $config['commerce_code']
            ]);

            $response = $this->client->get($this->baseUrl . "/payment/{$paymentId}", [
                'headers' => $headers
            ]);

            $result = json_decode($response->getBody()->getContents(), true);

            $this->logInfo('VirtualPOS confirmTransaction response', [
                'payment_id' => $paymentId,
                'http_status' => $response->getStatusCode(),
                'response' => $result,
            ]);

            if ($response->getStatusCode() === 200) {
                $status = $result['status'] ?? 'unknown';
                $isApproved = in_array($status, ['approved', 'paid', 'success']);

                return [
                    'success' => $isApproved,
                    'status' => $status,
                    'authorization_code' => $result['authorization_code'] ?? null,
                    'transaction_id' => $result['transaction_id'] ?? $paymentId,
                    'amount' => $result['amount'] ?? null,
                    'currency' => $result['currency'] ?? 'CLP',
                    'payment_method' => $result['payment_method'] ?? null,
                    'installments' => $result['installments'] ?? null,
                    'full_response' => $result,
                    'error' => $isApproved ? null : ($result['message'] ?? 'Pago no aprobado')
                ];
            } else {
                return [
                    'success' => false,
                    'error' => $result['message'] ?? 'Error al consultar transacción en VirtualPOS',
                    'full_response' => $result
                ];
            }
        } catch (\Exception $e) {
            $this->logError('VirtualPOS confirmTransaction error', [
                'payment_id' => $paymentId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ], $e);

            return [
                'success' => false,
                'error' => 'Error de conexión con VirtualPOS: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Procesar notificación webhook de VirtualPOS
     */
    public function processNotification($notificationData)
    {
        try {
            $this->logInfo('VirtualPOS notification received', $notificationData);

            $paymentId = $notificationData['payment_id'] ?? $notificationData['id'] ?? null;
            $status = $notificationData['status'] ?? null;
            $amount = $notificationData['amount'] ?? null;
            $currency = $notificationData['currency'] ?? 'CLP';

            if (!$paymentId) {
                $this->logError('VirtualPOS notification: No payment_id provided', $notificationData);
                return [
                    'success' => false,
                    'error' => 'No payment_id provided'
                ];
            }

            // Verificar que la notificación sea válida
            if (!$this->validateNotification($notificationData)) {
                $this->logError('VirtualPOS notification: Invalid signature or data', $notificationData);
                return [
                    'success' => false,
                    'error' => 'Invalid notification signature'
                ];
            }

            $isApproved = in_array($status, ['approved', 'paid', 'success']);

            return [
                'success' => $isApproved,
                'payment_id' => $paymentId,
                'status' => $status,
                'amount' => $amount,
                'currency' => $currency,
                'authorization_code' => $notificationData['authorization_code'] ?? null,
                'transaction_id' => $notificationData['transaction_id'] ?? $paymentId,
                'payment_method' => $notificationData['payment_method'] ?? null,
                'installments' => $notificationData['installments'] ?? null,
                'full_response' => $notificationData,
                'error' => $isApproved ? null : ($notificationData['message'] ?? 'Pago no aprobado')
            ];
        } catch (\Exception $e) {
            $this->logError('VirtualPOS processNotification error', [
                'error' => $e->getMessage(),
                'notification_data' => $notificationData
            ], $e);

            return [
                'success' => false,
                'error' => 'Error procesando notificación de VirtualPOS: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Determinar la clave de configuración según tipo de pago y cuotas
     */
    private function getConfigKey($paymentType, $installments)
    {
        // Si es débito o sin cuotas
        if ($paymentType === 'debit' || !$installments || $installments <= 1) {
            return 'no_cuotes';
        }

        // Si es crédito con cuotas específicas
        switch ($installments) {
            case 3:
                return '3_cuotes';
            case 6:
                return '6_cuotes';
            case 9:
                return '9_cuotes'; // Configuración específica para 9 cuotas (usa las mismas credenciales que 6)
            case 12:
                return '12_cuotes';
            default:
                return 'no_cuotes';
        }
    }

    /**
     * Generar firma JWT según documentación de VirtualPOS
     */
    private function generateJWTSignature($config, $payload)
    {
        try {
            // Según la documentación, el payload del JWT debe contener la API KEY
            $jwtPayload = [
                'api_key' => $config['api_key'],
                'uuid' => uniqid(), // Generar UUID único para cada transacción
            ];

            $jwtSignature = JWT::encode($jwtPayload, $config['secret_key'], 'HS256');

            $this->logInfo('VirtualPOS JWT signature generated', [
                'api_key' => $config['api_key'],
                'uuid' => $jwtPayload['uuid']
            ]);

            return $jwtSignature;
        } catch (\Exception $e) {
            $this->logError('Error generating JWT signature for VirtualPOS', [
                'error' => $e->getMessage()
            ], $e);
            throw $e;
        }
    }

    /**
     * Obtener URL de checkout para VirtualPOS
     */
    private function getCheckoutUrl($paymentUuid, $config, $returnUrl, $callbackUrl = null)
    {
        try {
            // Generar firma JWT para la consulta
            $signature = $this->generateJWTSignature($config, ['uuid' => $paymentUuid]);
            $headers = [
                'Content-Type' => 'application/json',
                'Authorization' => $config['api_key'],
                'Signature' => $signature
            ];
            $payload = [
                'return_url' => base64_encode($returnUrl),
                'callback_url' => base64_encode($callbackUrl ?: $returnUrl),
                'payment_method' => 'webpay'
            ];

            $response = $this->client->post($this->baseUrl . "/payment/{$paymentUuid}/webcheckout", [
                'headers' => $headers,
                'json' => $payload
            ]);
            $result = json_decode($response->getBody()->getContents(), true);
            if ($response->getStatusCode() == 200 && isset($result['url_redirect'])) {
                return $result['url_redirect'];
            } else {
                $errorMessage = 'No se pudo obtener la URL de la pasarela de VirtualPOS';
                if (isset($result['error']['message'])) {
                    $errorMessage = $result['error']['message'];
                } elseif (isset($result['message'])) {
                    $errorMessage = $result['message'];
                }

                throw new \Exception($errorMessage);
            }
        } catch (\Exception $e) {
            $this->logError('Error getting checkout URL for VirtualPOS', [
                'payment_uuid' => $paymentUuid,
                'error' => $e->getMessage()
            ], $e);
            throw $e;
        }
    }



    /**
     * Separar nombre completo en first_name y last_name
     */
    private function splitFullName($fullName)
    {
        $nameParts = explode(' ', trim($fullName), 2);

        if (count($nameParts) >= 2) {
            return [
                'first_name' => $nameParts[0],
                'last_name' => $nameParts[1]
            ];
        } else {
            return [
                'first_name' => $fullName,
                'last_name' => 'Cliente'
            ];
        }
    }

    /**
     * Formatear teléfono según requerimiento de VirtualPOS (569NNNNNNNN)
     */
    private function formatPhone($phone)
    {
        // Remover todos los caracteres no numéricos
        $cleanPhone = preg_replace('/[^0-9]/', '', $phone);

        // Si empieza con +56, removerlo
        if (strpos($cleanPhone, '56') === 0 && strlen($cleanPhone) > 9) {
            $cleanPhone = substr($cleanPhone, 2);
        }

        // Si no empieza con 9, agregar 9 al inicio
        if (strpos($cleanPhone, '9') !== 0) {
            $cleanPhone = '9' . $cleanPhone;
        }

        // Asegurar que tenga el formato 569NNNNNNNN
        if (strlen($cleanPhone) === 9) {
            $cleanPhone = '56' . $cleanPhone;
        }

        return $cleanPhone;
    }

    /**
     * Validar la firma de la notificación (implementar según documentación de VirtualPOS)
     */
    private function validateNotification($notificationData)
    {
        // TODO: Implementar validación de firma según documentación de VirtualPOS
        // Por ahora retornamos true, pero deberías implementar la validación real
        $this->logInfo('VirtualPOS notification validation: Implementar validación de firma');
        return true;
    }
}
