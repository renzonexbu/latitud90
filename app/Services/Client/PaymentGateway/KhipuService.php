<?php

namespace App\Services\Client\PaymentGateway;

use GuzzleHttp\Client;
use App\Traits\SystemLogging;

class KhipuService
{
    use SystemLogging;
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
                // notify_url opcional: sólo si se provee
            ];

            if (!empty($notificationUrl)) {
                $payload['notify_url'] = $notificationUrl;
            }

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
            $this->logInfo('Khipu createTransaction response', [
                'order_id' => $orderId,
                'amount' => $amount,
                'return_url' => $returnUrl,
                'notify_url' => $payload['notify_url'] ?? null,
                'http_status' => method_exists($response, 'getStatusCode') ? $response->getStatusCode() : 200,
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

            // Obtener la fecha de transacción de la respuesta de Khipu
            $transactionDate = null;
            if (isset($result['paid_at'])) {
                $transactionDate = $this->parseTransactionDate($result['paid_at']);
            } elseif (isset($result['updated_at'])) {
                $transactionDate = $this->parseTransactionDate($result['updated_at']);
            } elseif (isset($result['created_at'])) {
                $transactionDate = $this->parseTransactionDate($result['created_at']);
            } elseif ($approved) {
                // Si está aprobado pero no hay fecha específica, usar ahora
                $transactionDate = now('America/Santiago')->format('Y-m-d H:i:s');
            }

            // Log de confirmación/consulta de estado (siempre)
            $this->logInfo('Khipu getPaymentStatus response', [
                'payment_id' => $paymentId,
                'approved' => $approved,
                'status' => $result['status'] ?? 'unknown',
                'transaction_date' => $transactionDate,
                'http_status' => method_exists($response, 'getStatusCode') ? $response->getStatusCode() : null,
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

    /**
     * Parsear la fecha de transacción de Khipu para que sea compatible con MySQL
     */
    private function parseTransactionDate(string $dateString): string
    {
        // Khipu devuelve fechas en formato ISO 8601, por ejemplo: "2023-10-27T10:00:00Z"
        // MySQL espera un formato como "YYYY-MM-DD HH:MM:SS"
        // Para simplificar, podemos extraer la fecha y hora, y formatearla
        $date = \Carbon\Carbon::parse($dateString);
        return $date->format('Y-m-d H:i:s');
    }
}
