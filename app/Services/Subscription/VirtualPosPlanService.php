<?php

namespace App\Services\Subscription;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Firebase\JWT\JWT;

class VirtualPosPlanService
{
    private $apiUrl;
    private $apiKey;
    private $secretKey;

    public function __construct()
    {
        $this->apiUrl = config('services.virtualpos.api_url');
        $this->apiKey = config('services.virtualpos.api_key');
        $this->secretKey = config('services.virtualpos.secret_key');
    }

    /**
     * Crear un plan en VirtualPos para un programa
     *
     * @param array $programData Datos del programa
     * @return array|null
     */
    public function createPlan(array $programData): ?array
    {
        try {
            // Preparar datos del plan
            $planData = $this->preparePlanData($programData);

            Log::info('Creando plan en VirtualPos', [
                'program_code' => $programData['code'],
                'plan_data' => $planData
            ]);

            // Hacer request a la API de VirtualPos
            $response = Http::withHeaders($this->getHeaders($planData))
                ->post($this->apiUrl . '/plan', $planData);

            if ($response->successful()) {
                $result = $response->json();

                // VirtualPos puede retornar 'id' o 'plan_id'
                $planId = $result['id'] ?? $result['plan_id'] ?? $planData['id'] ?? null;

                Log::info('Plan creado exitosamente en VirtualPos', [
                    'plan_id' => $planId,
                    'response' => $result
                ]);

                return [
                    'success' => true,
                    'plan_id' => $planId,
                    'data' => $result
                ];
            }

            Log::error('Error al crear plan en VirtualPos', [
                'status' => $response->status(),
                'response' => $response->body()
            ]);

            return [
                'success' => false,
                'error' => $response->body()
            ];

        } catch (\Exception $e) {
            Log::error('Excepción al crear plan en VirtualPos', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Actualizar un plan existente en VirtualPos
     *
     * @param string $planId ID del plan en VirtualPos
     * @param array $programData Datos actualizados del programa
     * @return array|null
     */
    public function updatePlan(string $planId, array $programData): ?array
    {
        try {
            // Preparar datos actualizados del plan
            $planData = $this->preparePlanData($programData);

            Log::info('Actualizando plan en VirtualPos', [
                'plan_id' => $planId,
                'plan_data' => $planData
            ]);

            // Hacer request a la API de VirtualPos
            $response = Http::withHeaders($this->getHeaders($planData))
                ->put($this->apiUrl . '/plan/' . $planId, $planData);

            if ($response->successful()) {
                $result = $response->json();

                Log::info('Plan actualizado exitosamente en VirtualPos', [
                    'plan_id' => $planId,
                    'response' => $result
                ]);

                return [
                    'success' => true,
                    'data' => $result
                ];
            }

            Log::error('Error al actualizar plan en VirtualPos', [
                'plan_id' => $planId,
                'status' => $response->status(),
                'response' => $response->body()
            ]);

            return [
                'success' => false,
                'error' => $response->body()
            ];

        } catch (\Exception $e) {
            Log::error('Excepción al actualizar plan en VirtualPos', [
                'plan_id' => $planId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Cancelar/Eliminar un plan en VirtualPos
     *
     * @param string $planId ID del plan en VirtualPos
     * @return array|null
     */
    public function deletePlan(string $planId): ?array
    {
        try {
            Log::info('Cancelando plan en VirtualPos', [
                'plan_id' => $planId
            ]);

            // Hacer request a la API de VirtualPos
            $response = Http::withHeaders($this->getHeaders())
                ->delete($this->apiUrl . '/plan/' . $planId);

            if ($response->successful()) {
                $result = $response->json();

                Log::info('Plan cancelado exitosamente en VirtualPos', [
                    'plan_id' => $planId,
                    'response' => $result
                ]);

                return [
                    'success' => true,
                    'data' => $result
                ];
            }

            Log::error('Error al cancelar plan en VirtualPos', [
                'plan_id' => $planId,
                'status' => $response->status(),
                'response' => $response->body()
            ]);

            return [
                'success' => false,
                'error' => $response->body()
            ];

        } catch (\Exception $e) {
            Log::error('Excepción al cancelar plan en VirtualPos', [
                'plan_id' => $planId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Preparar datos del plan para enviar a VirtualPos API v3
     *
     * @param array $programData
     * @return array
     */
    private function preparePlanData(array $programData): array
    {
        // Generar nombre descriptivo del plan
        $planName = $programData['name'] ?? 'Programa ' . ($programData['code'] ?? 'Sin código');

        // Calcular monto mensual (precio total dividido entre cuotas máximas)
        $maxInstallments = $programData['max_installments'] ?? 12;
        $monthlyAmount = round(($programData['trip_price'] ?? 0) / $maxInstallments);

        // Generar return_url
        $returnUrl = config('app.url') . '/admin/programs';

        return [
            'id' => 'PLAN_' . ($programData['code'] ?? uniqid()), // ID único del plan
            'name' => $planName,
            'description' => $programData['trip_description'] ?? 'Programa de viaje educativo Latitud90',
            'is_active' => 'T', // T = activo, F = inactivo
            'amount' => (int) $monthlyAmount, // Monto mensual
            'currency' => 'CLP',
            'trial_days' => 0, // Sin período de prueba
            'num_charges' => $maxInstallments, // Número de cobros (cuotas)
            'frequency_type' => 'Mensual', // Diario, Semanal, Mensual, Semestral, Anual
            'return_url' => base64_encode($returnUrl), // Codificar en base64 según docs VirtualPos
            'type' => 'MONTO_FIJO', // MONTO_FIJO, MONTO_VARIABLE, PROGRAMA_DE_PAGOS
            'fixed_amount_day_charge' => '01', // Día del mes para cobro (01-30)
            'show_in_terminal' => 'F', // No mostrar en SmartPOS
            'automatic_renewal' => 'F', // Sin renovación automática (termina después de num_charges)
        ];
    }

    /**
     * Obtener información de un plan
     *
     * @param string $planId
     * @return array|null
     */
    public function getPlan(string $planId): ?array
    {
        try {
            $response = Http::withHeaders($this->getHeaders())
                ->get($this->apiUrl . '/plan/' . $planId);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json()
                ];
            }

            return [
                'success' => false,
                'error' => $response->body()
            ];

        } catch (\Exception $e) {
            Log::error('Error al obtener plan de VirtualPos', [
                'plan_id' => $planId,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Generar headers de autenticación con JWT
     *
     * @param array $payload Datos adicionales para el JWT
     * @return array
     */
    private function getHeaders(array $payload = []): array
    {
        $jwt = $this->generateJWT($payload);

        return [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'Authorization' => $this->apiKey,
            'Signature' => $jwt,
        ];
    }

    /**
     * Generar JWT para autenticación
     *
     * @param array $additionalPayload Datos adicionales para el payload
     * @return string
     */
    private function generateJWT(array $additionalPayload = []): string
    {
        $payload = array_merge([
            'api_key' => $this->apiKey,
            'iat' => time(),
        ], $additionalPayload);

        return JWT::encode($payload, $this->secretKey, 'HS256');
    }

    /**
     * Verificar si un plan tiene suscripciones activas
     *
     * @param string $planId ID del plan en VirtualPos
     * @return bool
     */
    public function hasActiveSubscriptions(string $planId): bool
    {
        try {
            Log::info('Verificando suscripciones activas para plan', [
                'plan_id' => $planId
            ]);

            // Consultar el plan para obtener información de suscripciones
            $response = Http::withHeaders($this->getHeaders())
                ->get($this->apiUrl . '/plan/' . $planId);

            if (!$response->successful()) {
                Log::warning('Error al consultar plan en VirtualPos', [
                    'plan_id' => $planId,
                    'status' => $response->status(),
                    'response' => $response->body()
                ]);
                // Si hay error en la consulta, asumir que hay suscripciones por seguridad
                return true;
            }

            $result = $response->json();
            $plan = $result['plan'] ?? $result;

            // VirtualPos puede retornar un contador de suscripciones activas
            $activeSubscriptions = $plan['active_subscriptions'] ?? $plan['subscriptions_count'] ?? 0;

            Log::info('Resultado de verificación de suscripciones', [
                'plan_id' => $planId,
                'active_subscriptions' => $activeSubscriptions
            ]);

            return $activeSubscriptions > 0;

        } catch (\Exception $e) {
            Log::error('Error al verificar suscripciones activas', [
                'plan_id' => $planId,
                'error' => $e->getMessage()
            ]);
            // En caso de error, asumir que hay suscripciones por seguridad
            return true;
        }
    }
}
