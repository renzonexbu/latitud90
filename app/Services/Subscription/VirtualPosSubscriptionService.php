<?php

namespace App\Services\Subscription;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Firebase\JWT\JWT;
use Exception;

/**
 * Servicio para integración con VirtualPos Subscriptions API v3
 *
 * @see https://virtualpos.readme.io/reference/objeto-suscription-1
 */
class VirtualPosSubscriptionService
{
    protected string $apiUrl;
    protected string $apiKey;
    protected string $secretKey;
    protected string $merchantCode;
    protected bool $isV2;

    public function __construct()
    {
        $this->apiUrl = config('services.virtualpos.api_url', 'https://api.virtualpos.cl/v3');
        $this->apiKey = config('services.virtualpos.api_key');
        $this->secretKey = config('services.virtualpos.secret_key');
        $this->merchantCode = config('services.virtualpos.merchant_code');

        // Detectar si es v2 (sandbox) o v3 (producción)
        $this->isV2 = str_contains($this->apiUrl, '/v2');
    }

    /**
     * Crear una nueva suscripción
     *
     * @param array $data Datos de la suscripción
     * @return array
     * @throws Exception
     */
    public function createSubscription(array $data): array
    {
        try {
            $endpoint = '/suscription';

            // Generar UUID único para esta petición
            $uuid = \Illuminate\Support\Str::uuid()->toString();

            // Agregar UUID al cuerpo de la petición
            $data['uuid'] = $uuid;

            Log::info('VirtualPos: Creando suscripción', [
                'plan_id' => $data['plan_id'] ?? null,
                'service_id' => $data['service_id'] ?? null,
                'email' => $data['email'] ?? null,
                'uuid' => $uuid,
                'return_url_base64' => $data['return_url'] ?? null,
                'return_url_decoded' => isset($data['return_url']) ? base64_decode($data['return_url']) : null,
                'callback_url_base64' => $data['callback_url'] ?? null,
                'callback_url_decoded' => isset($data['callback_url']) ? base64_decode($data['callback_url']) : null,
                'full_data' => $data  // Ver todos los datos que se envían
            ]);

            // JWT solo debe contener api_key y uuid (NO los datos del body)
            $headers = $this->getHeaders($uuid);

            $response = Http::withHeaders($headers)
                ->post($this->apiUrl . $endpoint, $data);

            $result = $response->json();

            // Verificar si hay error en la respuesta (VirtualPos puede devolver 200 con error)
            if ($response->failed() || (isset($result['status']) && $result['status'] === 'NOK')) {
                $errorMessage = $result['error']['message'] ?? $response->body();
                $errorCode = $result['error']['error_code'] ?? 'UNKNOWN';

                Log::error('VirtualPos: Error al crear suscripción', [
                    'status' => $response->status(),
                    'error_code' => $errorCode,
                    'error_message' => $errorMessage,
                    'body' => $response->body(),
                    'headers_sent' => $headers
                ]);

                throw new Exception("Error al crear suscripción ({$errorCode}): {$errorMessage}");
            }

            Log::info('VirtualPos: Suscripción creada exitosamente', [
                'subscription_id' => $result['id'] ?? null
            ]);

            return $result;
        } catch (Exception $e) {
            Log::error('VirtualPos: Excepción al crear suscripción', [
                'message' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Recuperar información de una suscripción
     *
     * @param string $subscriptionId ID de la suscripción
     * @return array
     * @throws Exception
     */
    public function getSubscription(string $subscriptionId): ?array
    {
        try {
            $endpoint = "/suscription/{$subscriptionId}";

            Log::info('VirtualPos: Recuperando suscripción', [
                'subscription_id' => $subscriptionId
            ]);

            $response = Http::withHeaders($this->getHeaders())
                ->get($this->apiUrl . $endpoint);

            if ($response->failed()) {
                Log::error('VirtualPos: Error al recuperar suscripción', [
                    'subscription_id' => $subscriptionId,
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);
                throw new Exception('Error al recuperar suscripción: ' . $response->body());
            }

            $data = $response->json();

            // VirtualPOS devuelve los datos bajo la clave 'suscription'
            $subscriptionData = $data['suscription'] ?? $data;
            $chargeProgram = $subscriptionData['charge_program'] ?? [];

            Log::info('VirtualPos: Respuesta completa de getSubscription', [
                'subscription_id' => $subscriptionId,
                'has_charge_program' => !empty($chargeProgram),
                'charge_program_count' => count($chargeProgram),
                'status' => $subscriptionData['status'] ?? 'unknown'
            ]);

            return $data;
        } catch (Exception $e) {
            Log::error('VirtualPos: Excepción al recuperar suscripción', [
                'subscription_id' => $subscriptionId,
                'message' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Cancelar una suscripción
     *
     * @param string $subscriptionId ID de la suscripción
     * @return array
     * @throws Exception
     */
    public function cancelSubscription(string $subscriptionId): array
    {
        try {
            $endpoint = "/suscription/{$subscriptionId}";

            Log::info('VirtualPos: Cancelando suscripción', [
                'subscription_id' => $subscriptionId
            ]);

            $response = Http::withHeaders($this->getHeaders())
                ->delete($this->apiUrl . $endpoint);

            $result = $response->json();

            // Verificar si hay error en la respuesta (VirtualPos puede devolver 200 con error)
            if ($response->failed() || (isset($result['status']) && $result['status'] === 'NOK')) {
                $errorMessage = $result['error']['message'] ?? $response->body();
                $errorCode = $result['error']['error_code'] ?? 'UNKNOWN';

                Log::error('VirtualPos: Error al cancelar suscripción', [
                    'subscription_id' => $subscriptionId,
                    'status' => $response->status(),
                    'error_code' => $errorCode,
                    'error_message' => $errorMessage,
                    'body' => $response->body()
                ]);

                throw new Exception("Error al cancelar suscripción ({$errorCode}): {$errorMessage}");
            }

            Log::info('VirtualPos: Suscripción cancelada exitosamente', [
                'subscription_id' => $subscriptionId
            ]);

            return $result;
        } catch (Exception $e) {
            Log::error('VirtualPos: Excepción al cancelar suscripción', [
                'subscription_id' => $subscriptionId,
                'message' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Listar suscripciones con paginación
     *
     * @param array $filters Filtros opcionales
     * @return array
     * @throws Exception
     */
    public function listSubscriptions(array $filters = []): array
    {
        try {
            $endpoint = '/suscriptions';

            Log::info('VirtualPos: Listando suscripciones', [
                'filters' => $filters
            ]);

            $response = Http::withHeaders($this->getHeaders())
                ->get($this->apiUrl . $endpoint, $filters);

            if ($response->failed()) {
                Log::error('VirtualPos: Error al listar suscripciones', [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);
                throw new Exception('Error al listar suscripciones: ' . $response->body());
            }

            return $response->json();
        } catch (Exception $e) {
            Log::error('VirtualPos: Excepción al listar suscripciones', [
                'message' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Generar link de cambio de tarjeta
     *
     * @param string $subscriptionId ID de la suscripción
     * @return array
     * @throws Exception
     */
    public function generateCardChangeLink(string $subscriptionId): array
    {
        try {
            $endpoint = "/suscription/{$subscriptionId}/changecard";

            Log::info('VirtualPos: Generando link de cambio de tarjeta', [
                'subscription_id' => $subscriptionId
            ]);

            $response = Http::withHeaders($this->getHeaders())
                ->put($this->apiUrl . $endpoint);

            if ($response->failed()) {
                Log::error('VirtualPos: Error al generar link de cambio de tarjeta', [
                    'subscription_id' => $subscriptionId,
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);
                throw new Exception('Error al generar link de cambio de tarjeta: ' . $response->body());
            }

            Log::info('VirtualPos: Link de cambio de tarjeta generado', [
                'subscription_id' => $subscriptionId
            ]);

            return $response->json();
        } catch (Exception $e) {
            Log::error('VirtualPos: Excepción al generar link de cambio de tarjeta', [
                'subscription_id' => $subscriptionId,
                'message' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Obtener detalle de un cargo (charge) específico
     * Este endpoint retorna información adicional como el auth_code
     *
     * @param string $chargeId ID del cargo (ej: cid_xxx)
     * @return array
     * @throws Exception
     */
    public function getCharge(string $chargeId): array
    {
        try {
            $endpoint = "/charge/{$chargeId}";

            Log::info('VirtualPos: Recuperando detalle de cargo', [
                'charge_id' => $chargeId
            ]);

            $response = Http::withHeaders($this->getHeaders())
                ->get($this->apiUrl . $endpoint);

            if ($response->failed()) {
                Log::error('VirtualPos: Error al recuperar cargo', [
                    'charge_id' => $chargeId,
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);
                throw new Exception('Error al recuperar cargo: ' . $response->body());
            }

            $data = $response->json();

            Log::info('VirtualPos: Detalle de cargo obtenido', [
                'charge_id' => $chargeId,
                'status' => $data['charge']['status'] ?? 'unknown',
                'has_auth_code' => isset($data['charge']['payment']['order']['auth_code'])
            ]);

            return $data;
        } catch (Exception $e) {
            Log::error('VirtualPos: Excepción al recuperar cargo', [
                'charge_id' => $chargeId,
                'message' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Obtener headers necesarios para la autenticación
     *
     * @param string|null $uuid UUID de la petición (requerido para POST)
     * @return array
     */
    protected function getHeaders($uuid = null): array
    {
        $jwt = $this->generateJWT($uuid);

        return [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'Authorization' => $this->apiKey,
            'Signature' => $jwt,
        ];
    }

    /**
     * Generar JWT para autenticación
     * Según documentación VirtualPos, el JWT solo debe contener api_key y uuid (no los datos del body)
     *
     * @param string|null $uuid UUID de la petición (requerido para POST)
     * @return string
     */
    protected function generateJWT($uuid = null): string
    {
        $payload = [
            'api_key' => $this->apiKey,
        ];

        // Agregar UUID si se proporciona (requerido para POST requests)
        if ($uuid) {
            $payload['uuid'] = $uuid;
        }

        return JWT::encode($payload, $this->secretKey, 'HS256');
    }

    /**
     * Crear un plan de suscripción
     *
     * @param array $data Datos del plan
     * @return array
     * @throws Exception
     */
    public function createPlan(array $data): array
    {
        try {
            $endpoint = '/plan';

            // Generar UUID único para esta petición
            $uuid = \Illuminate\Support\Str::uuid()->toString();

            // Agregar UUID al cuerpo de la petición
            $data['uuid'] = $uuid;

            Log::info('VirtualPos: Creando plan', [
                'plan_id' => $data['plan_id'] ?? null,
                'uuid' => $uuid,
                'full_data' => $data
            ]);

            // JWT solo debe contener api_key y uuid (NO los datos del plan)
            $headers = $this->getHeaders($uuid);

            $response = Http::withHeaders($headers)
                ->post($this->apiUrl . $endpoint, $data);

            $result = $response->json();

            // Verificar si hay error en la respuesta (VirtualPos puede devolver 200 con error)
            if ($response->failed() || (isset($result['status']) && $result['status'] === 'NOK')) {
                $errorMessage = $result['error']['message'] ?? $response->body();
                $errorCode = $result['error']['error_code'] ?? 'UNKNOWN';

                Log::error('VirtualPos: Error al crear plan', [
                    'status' => $response->status(),
                    'error_code' => $errorCode,
                    'error_message' => $errorMessage,
                    'body' => $response->body(),
                    'headers_sent' => $headers
                ]);

                throw new Exception("Error al crear plan ({$errorCode}): {$errorMessage}");
            }

            Log::info('VirtualPos: Plan creado exitosamente', [
                'plan_id' => $result['id'] ?? $result['plan_id'] ?? null,
                'full_response' => $result
            ]);

            return $result;
        } catch (Exception $e) {
            Log::error('VirtualPos: Excepción al crear plan', [
                'message' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Obtener información de un plan
     *
     * @param string $planId ID del plan
     * @return array
     * @throws Exception
     */
    public function getPlan(string $planId): array
    {
        try {
            $endpoint = "/plan/{$planId}";

            Log::info('VirtualPos: Recuperando plan', [
                'plan_id' => $planId
            ]);

            $response = Http::withHeaders($this->getHeaders())
                ->get($this->apiUrl . $endpoint);

            if ($response->failed()) {
                Log::error('VirtualPos: Error al recuperar plan', [
                    'plan_id' => $planId,
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);
                throw new Exception('Error al recuperar plan: ' . $response->body());
            }

            return $response->json();
        } catch (Exception $e) {
            Log::error('VirtualPos: Excepción al recuperar plan', [
                'plan_id' => $planId,
                'message' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Listar todos los planes
     *
     * @param array $filters Filtros opcionales
     * @return array
     * @throws Exception
     */
    public function listPlans(array $filters = []): array
    {
        try {
            $endpoint = '/plans';

            Log::info('VirtualPos: Listando planes', [
                'filters' => $filters
            ]);

            $response = Http::withHeaders($this->getHeaders())
                ->get($this->apiUrl . $endpoint, $filters);

            if ($response->failed()) {
                Log::error('VirtualPos: Error al listar planes', [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);
                throw new Exception('Error al listar planes: ' . $response->body());
            }

            return $response->json();
        } catch (Exception $e) {
            Log::error('VirtualPos: Excepción al listar planes', [
                'message' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Construir datos para crear un plan
     *
     * @param array $params Parámetros del plan
     * @return array
     */
    public function buildPlanData(array $params): array
    {
        // Mapear periodicidad de inglés a español (valores de VirtualPos)
        $frequencyMap = [
            'DAILY' => 'Diario',
            'WEEKLY' => 'Semanal',
            'MONTHLY' => 'Mensual',
            'SEMESTRAL' => 'Semestral',
            'YEARLY' => 'Anual',
        ];

        $periodicity = $params['periodicity'] ?? 'MONTHLY';
        $frequencyType = $frequencyMap[$periodicity] ?? 'Mensual';

        // Preparar return_url y codificar en base64
        $returnUrl = $params['return_url'] ?? config('app.url') . '/test/subscriptions';

        return [
            'id' => $params['plan_id'], // ID único del plan
            'name' => $params['plan_name'],
            'description' => $params['description'],
            'is_active' => 'T', // T = activo, F = inactivo
            'amount' => $params['amount'],
            'currency' => $params['currency'] ?? 'CLP',
            'trial_days' => $params['trial_period_days'] ?? 0,
            'num_charges' => $params['charges_number'] ?? 0, // 0 = infinito según docs
            'frequency_type' => $frequencyType, // Diario, Semanal, Mensual, Semestral, Anual
            'return_url' => base64_encode($returnUrl), // Codificar en base64 según docs VirtualPos
            'type' => $params['type'] ?? 'MONTO_FIJO', // MONTO_FIJO, MONTO_VARIABLE, PROGRAMA_DE_PAGOS
            'fixed_amount_day_charge' => $params['fixed_amount_day_charge'] ?? '01', // 0,01,05,10,15,20,25,28,30
            'show_in_terminal' => $params['show_in_terminal'] ?? 'F', // T/F - mostrar en SmartPOS
            'automatic_renewal' => $params['automatic_renewal'] ?? 'T', // T/F - renovación automática
            'shipping_address' => $params['shipping_address'] ?? null, // Opcional: T/F
        ];
    }

    /**
     * Construir datos para crear una suscripción
     *
     * @param array $params Parámetros de la suscripción
     * @return array
     */
    public function buildSubscriptionData(array $params): array
    {
        // Según el cURL de la documentación, los campos van en el nivel raíz (no dentro de "client")
        $data = [
            'plan_id' => $params['plan_id'],
            'email' => $params['client']['email'],
            'first_name' => $params['client']['name'],
            'channel' => $params['channel'] ?? 'WEB',
            'automatic_renewal' => $params['automatic_renewal'] ?? 'T',
        ];

        // Agregar campos opcionales del cliente
        if (!empty($params['client']['surname'])) {
            $data['last_name'] = $params['client']['surname'];
        }
        if (!empty($params['client']['rut'])) {
            $data['social_id'] = $params['client']['rut'];
        }
        if (!empty($params['client']['phone'])) {
            $data['phone_number'] = $params['client']['phone'];  // IMPORTANTE: phone_number, no phone
        }

        // Agregar service_id solo si está presente
        if (!empty($params['service_id'])) {
            $data['service_id'] = $params['service_id'];
        }

        // Agregar URLs de callback y return
        $returnUrl = !empty($params['return_url'])
            ? $params['return_url']
            : config('app.url') . '/test/subscriptions/return';

        $callbackUrl = !empty($params['callback_url'])
            ? $params['callback_url']
            : config('app.url') . '/test/subscriptions/callback';

        // IMPORTANTE: URLs deben estar codificadas en base64 según documentación VirtualPos
        $data['return_url'] = base64_encode($returnUrl);
        $data['callback_url'] = base64_encode($callbackUrl);

        // Agregar charges_program si está presente (requerido para planes tipo PROGRAMA_DE_PAGOS)
        if (!empty($params['charges_program'])) {
            $data['charges_program'] = $params['charges_program']; // Ya debe venir codificado en Base64
        }

        return $data;
    }
}
