<?php

namespace App\Services\Client\PaymentGateway;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;

class KhipuService
{
    private $client;
    private $baseUrl;
    private $apiKey;

    public function __construct()
    {
        $this->client = new Client();
        $this->baseUrl = 'https://payment-api.khipu.com';
        $this->apiKey = config('services.khipu.api_key', 'ae940262-4f1a-4a0d-aef3-e5406b533b44');
    }

    /**
     * Crear transacción usando la API v3.0 de Khipu
     */
    public function createTransaction($orderId, $amount, $returnUrl, $notificationUrl = null)
    {
        try {
            $payload = [
                'amount' => (int) $amount,
                'currency' => 'CLP',
                'subject' => "Pago de orden #{$orderId}",
                'return_url' => $returnUrl,
                // Usar webhook nativo del proyecto (si no viene explícito)
                'notify_url' => $notificationUrl ?: route('webhook.khipu')
            ];

            $headers = [
                'Content-Type' => 'application/json',
                'x-api-key' => $this->apiKey
            ];

            $response = $this->client->post($this->baseUrl . '/v3/payments', [
                'headers' => $headers,
                'json' => $payload
            ]);

            $result = json_decode($response->getBody()->getContents(), true);

            // Log de creación de pago (siempre)
            Log::info('Khipu createTransaction response', [
                'order_id' => $orderId,
                'amount' => $amount,
                'return_url' => $returnUrl,
                'notify_url' => $payload['notify_url'] ?? null,
                'http_status' => method_exists($response, 'getStatusCode') ? $response->getStatusCode() : 200,
                'response' => $result,
            ]);

            return [
                'success' => true,
                'payment_id' => $result['payment_id'],
                'url' => $result['payment_url']
            ];
        } catch (\Exception $e) {
            Log::error('Error creating Khipu payment', [
                'error' => $e->getMessage(),
                'amount' => $amount,
                'order_id' => $orderId
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Consultar el estado de un pago Khipu por payment_id
     */
    public function getPaymentStatus(string $paymentId): array
    {
        try {
            $headers = [
                'x-api-key' => $this->apiKey
            ];

            $response = $this->client->get($this->baseUrl . '/v3/payments/' . $paymentId, [
                'headers' => $headers
            ]);

            $result = json_decode($response->getBody()->getContents(), true);
            $approved = isset($result['status']) && in_array($result['status'], ['done', 'paid', 'approved', 'completed']);

            // Log de confirmación/consulta de estado (siempre)
            Log::info('Khipu getPaymentStatus response', [
                'payment_id' => $paymentId,
                'approved' => $approved,
                'status' => $result['status'] ?? 'unknown',
                'http_status' => method_exists($response, 'getStatusCode') ? $response->getStatusCode() : null,
                'response' => $result,
            ]);

            return [
                'success' => $approved,
                'status' => $result['status'] ?? 'unknown',
                'data' => $result,
            ];
        } catch (\Exception $e) {
            Log::error('Error getting Khipu payment status', [
                'error' => $e->getMessage(),
                'payment_id' => $paymentId
            ]);

            return [
                'success' => false,
                'status' => 'error',
                'error' => $e->getMessage()
            ];
        }
    }
}
