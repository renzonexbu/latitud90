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

            // Generar UUID único para esta petición
            $uuid = \Illuminate\Support\Str::uuid()->toString();

            // Agregar UUID al cuerpo de la petición
            $planData['uuid'] = $uuid;

            Log::info('Creando plan en VirtualPos', [
                'program_code' => $programData['code'] ?? null,
                'uuid' => $uuid,
                'plan_data' => $planData
            ]);

            // JWT solo debe contener api_key y uuid (NO los datos del plan)
            $response = Http::withHeaders($this->getHeaders($uuid))
                ->post($this->apiUrl . '/plan', $planData);

            $result = $response->json();

            // Verificar si hay error en la respuesta (VirtualPos puede devolver 200 con error)
            if ($response->failed() || (isset($result['status']) && $result['status'] === 'NOK')) {
                $errorMessage = $result['error']['message'] ?? $result['message'] ?? $response->body();
                $errorCode = $result['error']['error_code'] ?? $result['code'] ?? 'UNKNOWN';

                Log::error('Error al crear plan en VirtualPos', [
                    'status' => $response->status(),
                    'error_code' => $errorCode,
                    'error_message' => $errorMessage,
                    'full_response' => $result
                ]);

                return [
                    'success' => false,
                    'error' => $errorMessage,
                    'error_code' => $errorCode,
                    'response' => $result
                ];
            }

            // Si llegamos aquí, fue exitoso
            // La respuesta de VirtualPos incluye el plan dentro de result['plan']
            $planId = $result['plan']['id'] ?? $result['id'] ?? $result['plan_id'] ?? $planData['id'] ?? null;

            Log::info('Plan creado exitosamente en VirtualPos', [
                'plan_id' => $planId,
                'full_response' => $result
            ]);

            return [
                'success' => true,
                'plan_id' => $planId,
                'data' => $result
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

            // Generar UUID único para esta petición
            $uuid = \Illuminate\Support\Str::uuid()->toString();

            // Agregar UUID al cuerpo de la petición
            $planData['uuid'] = $uuid;

            Log::info('Actualizando plan en VirtualPos', [
                'plan_id' => $planId,
                'uuid' => $uuid,
                'plan_data' => $planData
            ]);

            // JWT solo debe contener api_key y uuid (NO los datos del plan)
            $response = Http::withHeaders($this->getHeaders($uuid))
                ->put($this->apiUrl . '/plan/' . $planId, $planData);

            $result = $response->json();

            // Verificar si hay error en la respuesta (VirtualPos puede devolver 200 con error)
            if ($response->failed() || (isset($result['status']) && $result['status'] === 'NOK')) {
                $errorMessage = $result['error']['message'] ?? $result['message'] ?? $response->body();
                $errorCode = $result['error']['error_code'] ?? $result['code'] ?? 'UNKNOWN';

                Log::error('Error al actualizar plan en VirtualPos', [
                    'plan_id' => $planId,
                    'status' => $response->status(),
                    'error_code' => $errorCode,
                    'error_message' => $errorMessage,
                    'full_response' => $result
                ]);

                return [
                    'success' => false,
                    'error' => $errorMessage,
                    'error_code' => $errorCode,
                    'response' => $result
                ];
            }

            Log::info('Plan actualizado exitosamente en VirtualPos', [
                'plan_id' => $planId,
                'full_response' => $result
            ]);

            return [
                'success' => true,
                'data' => $result
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
        $monthlyAmount = ($programData['trip_price'] ?? 0) / $maxInstallments;

        // Generar return_url
        $returnUrl = config('app.url') . '/admin/programs';

        // Determinar configuración del primer cobro
        $immediateFirstCharge = $programData['immediate_first_charge'] ?? true;

        // Usar PROGRAMA_DE_PAGOS para tener control total sobre las fechas de cobro
        $now = now();
        $chargesProgram = [];

        // Generar programa de cobros
        for ($i = 0; $i < $maxInstallments; $i++) {
            if ($i === 0) {
                // Primera cuota
                if ($immediateFirstCharge) {
                    // Cobro inmediato: HOY
                    $chargeDate = $now->copy();
                } else {
                    // Cobro diferido: día 1 del próximo mes
                    $chargeDate = $now->copy()->addMonth()->startOfMonth();
                }
            } else {
                // Cuotas siguientes: sumar meses desde la primera cuota
                $chargeDate = ($i === 0 ? $now : $chargesProgram[0]['charge_date_obj'])->copy()->addMonths($i);
            }

            $chargesProgram[] = [
                'charge_date_obj' => $chargeDate, // Guardar objeto Carbon para siguiente iteración
                'charge_date' => $chargeDate->format('Y-m-d'),
                'amount' => $monthlyAmount,
                'description' => 'Cargo ' . ($i + 1) . ' de ' . $maxInstallments,
                'internal_code' => ($programData['code'] ?? 'PLAN') . '-CUOTA-' . ($i + 1)
            ];
        }

        // Remover el objeto Carbon antes de codificar
        $chargesProgramForApi = array_map(function($charge) {
            unset($charge['charge_date_obj']);
            return $charge;
        }, $chargesProgram);

        // Codificar charges_program en Base64 según documentación
        $chargesProgramBase64 = base64_encode(json_encode($chargesProgramForApi));

        return [
            'id' => $programData['code'] ?? 'PLAN_' . uniqid(), // ID único del plan (sin prefijo PLAN_)
            'name' => $planName,
            'description' => !empty($programData['trip_description']) ? $programData['trip_description'] : 'Programa de viaje educativo Latitud90',
            'is_active' => 'T', // T = activo, F = inactivo
            'amount' => $monthlyAmount, // Monto mensual (Float según docs)
            'currency' => 'CLP',
            'trial_days' => 0, // No se usa con PROGRAMA_DE_PAGOS
            'num_charges' => $maxInstallments, // Número de cobros (cuotas)
            'frequency_type' => 'Mensual', // Diario, Semanal, Mensual, Semestral, Anual
            'return_url' => base64_encode($returnUrl), // Codificar en base64 según docs VirtualPos
            'type' => 'PROGRAMA_DE_PAGOS', // Usar PROGRAMA_DE_PAGOS para control manual de fechas
            'charges_program' => $chargesProgramBase64, // Array de cobros codificado en Base64
            'show_in_terminal' => 'F', // No mostrar en SmartPOS
            'automatic_renewal' => 'F', // Sin renovación automática (ya definimos todas las cuotas)
            'shipping_address' => '', // Opcional: igual que buildPlanData
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
     * Obtener headers necesarios para la autenticación
     *
     * @param string|null $uuid UUID de la petición (requerido para POST)
     * @return array
     */
    protected function getHeaders(?string $uuid = null): array
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
    protected function generateJWT(?string $uuid = null): string
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
