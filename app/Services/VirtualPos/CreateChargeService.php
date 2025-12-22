<?php

namespace App\Services\VirtualPos;

use App\Models\ProgramSubscription;
use App\Models\Installment;
use App\Models\InstallmentPlan;
use App\Traits\SystemLogging;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Firebase\JWT\JWT;

class CreateChargeService
{
    use SystemLogging;

    private string $apiUrl;
    private string $apiKey;
    private string $secretKey;

    public function __construct()
    {
        $this->apiUrl = config('services.virtualpos.api_url');
        $this->apiKey = config('services.virtualpos.api_key');
        $this->secretKey = config('services.virtualpos.secret_key');
    }

    /**
     * Crear un cargo manual para una cuota específica
     */
    public function createCharge(ProgramSubscription $subscription, Installment $installment): array
    {
        try {
            // Verificar que la suscripción está activa
            if ($subscription->status !== 'ACTIVA') {
                return [
                    'success' => false,
                    'message' => 'Solo se pueden crear cargos para suscripciones activas.'
                ];
            }

            // Verificar que la cuota no esté pagada
            if ($installment->is_paid) {
                return [
                    'success' => false,
                    'message' => 'La cuota ya se encuentra pagada.'
                ];
            }

            // Verificar que tenga ID de suscripción
            if (empty($subscription->virtualpos_subscription_id)) {
                return [
                    'success' => false,
                    'message' => 'La suscripción no tiene ID de VirtualPos asociado.'
                ];
            }

            // Generar UUID para la petición
            $uuid = \Illuminate\Support\Str::uuid()->toString();

            $payload = [
                'uuid' => $uuid,
                'suscription_id' => $subscription->virtualpos_subscription_id,
                'amount' => (int) $installment->amount,
                'description' => "Cuota {$installment->installment_number} - Cobro manual",
            ];

            Log::info('VirtualPos: Creando cargo manual', [
                'subscription_id' => $subscription->id,
                'virtualpos_subscription_id' => $subscription->virtualpos_subscription_id,
                'installment_id' => $installment->id,
                'installment_number' => $installment->installment_number,
                'amount' => $payload['amount'],
                'uuid' => $uuid,
            ]);

            $headers = $this->getHeaders($uuid);
            $endpoint = "{$this->apiUrl}/charge";

            $response = Http::withHeaders($headers)
                ->post($endpoint, $payload);

            $result = $response->json();

            Log::info('VirtualPos: Respuesta de creación de cargo', [
                'status_code' => $response->status(),
                'response' => $result,
            ]);

            if ($response->successful() && (!isset($result['status']) || $result['status'] !== 'NOK')) {
                // Actualizar la cuota con el ID del cargo
                $chargeId = $result['charge']['id'] ?? $result['id'] ?? null;

                if ($chargeId) {
                    $installment->update([
                        'virtualpos_charge_id' => $chargeId,
                        'status' => 'processing',
                    ]);
                }

                $this->logOperationSuccess(
                    "Cargo manual creado para suscripción {$subscription->virtualpos_subscription_id}",
                    [
                        'subscription_id' => $subscription->id,
                        'installment_id' => $installment->id,
                        'charge_id' => $chargeId,
                        'amount' => $payload['amount'],
                    ]
                );

                return [
                    'success' => true,
                    'message' => 'Cargo creado exitosamente. El cobro se procesará automáticamente.',
                    'data' => $result
                ];
            }

            $errorMessage = $result['error']['message'] ?? $result['message'] ?? 'Error desconocido al crear el cargo';
            $errorCode = $result['error']['error_code'] ?? 'UNKNOWN';

            Log::error('VirtualPos: Error al crear cargo manual', [
                'subscription_id' => $subscription->id,
                'installment_id' => $installment->id,
                'error_code' => $errorCode,
                'error_message' => $errorMessage,
            ]);

            return [
                'success' => false,
                'message' => "Error al crear cargo: {$errorMessage}",
                'error_code' => $errorCode,
            ];

        } catch (\Exception $e) {
            Log::error('VirtualPos: Excepción al crear cargo manual', [
                'subscription_id' => $subscription->id,
                'installment_id' => $installment->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'message' => 'Error al procesar la solicitud: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Reintentar un cargo fallido
     */
    public function retryCharge(ProgramSubscription $subscription, Installment $installment): array
    {
        // Si ya tiene un charge_id, intentar obtener su estado primero
        if (!empty($installment->virtualpos_charge_id)) {
            Log::info('VirtualPos: Reintentando cargo existente', [
                'charge_id' => $installment->virtualpos_charge_id,
            ]);
        }

        // Crear un nuevo cargo
        return $this->createCharge($subscription, $installment);
    }

    /**
     * Obtener headers para autenticación con VirtualPos
     */
    private function getHeaders(?string $uuid = null): array
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
     * Eliminar/Cancelar un cargo de VirtualPos
     * Nota: Solo funciona para cargos en estado "pendiente" de cobro inmediato.
     * Si el cargo está programado para el futuro, usar cancelFutureCharges()
     */
    public function deleteCharge(ProgramSubscription $subscription, Installment $installment): array
    {
        try {
            // Verificar que la cuota tenga un charge_id
            if (empty($installment->virtualpos_charge_id)) {
                return [
                    'success' => false,
                    'message' => 'La cuota no tiene un cargo de VirtualPos asociado.'
                ];
            }

            // Verificar que la cuota no esté pagada
            if ($installment->is_paid) {
                return [
                    'success' => false,
                    'message' => 'No se puede eliminar un cargo que ya fue pagado.'
                ];
            }

            $chargeId = $installment->virtualpos_charge_id;

            Log::channel('daily')->info('VirtualPos: Intentando eliminar cargo', [
                'subscription_id' => $subscription->id,
                'installment_id' => $installment->id,
                'charge_id' => $chargeId,
            ]);

            $headers = $this->getHeaders();
            $endpoint = "{$this->apiUrl}/charge/{$chargeId}";

            Log::channel('daily')->info('VirtualPos: Llamando DELETE a endpoint', [
                'endpoint' => $endpoint,
            ]);

            $response = Http::withHeaders($headers)
                ->delete($endpoint);

            $result = $response->json();

            Log::channel('daily')->info('VirtualPos: Respuesta de eliminación de cargo', [
                'status_code' => $response->status(),
                'response' => $result,
            ]);

            // Verificar si hay error E-059 (cargo no pendiente)
            $errorCode = $result['error']['error_code'] ?? null;

            if ($errorCode === 'E-059') {
                Log::channel('daily')->warning('VirtualPos: Cargo no está pendiente en VirtualPos (E-059)', [
                    'charge_id' => $chargeId,
                    'message' => $result['error']['message'] ?? '',
                ]);

                // El cargo no está pendiente en VirtualPos, pero podemos limpiar el ID local
                // ya que el cargo no se puede cobrar de todas formas
                $installment->update([
                    'virtualpos_charge_id' => null,
                    'status' => 'pending',
                ]);

                return [
                    'success' => true,
                    'message' => 'El cargo no estaba pendiente en VirtualPos. Se ha limpiado el registro local.',
                    'data' => $result,
                    'warning' => 'E-059: El cargo ya no existe como pendiente en VirtualPos'
                ];
            }

            if ($response->successful() && (!isset($result['status']) || $result['status'] !== 'NOK')) {
                // Limpiar el charge_id de la cuota
                $installment->update([
                    'virtualpos_charge_id' => null,
                    'status' => 'pending',
                ]);

                $this->logOperationSuccess(
                    "Cargo eliminado de suscripción {$subscription->virtualpos_subscription_id}",
                    [
                        'subscription_id' => $subscription->id,
                        'installment_id' => $installment->id,
                        'deleted_charge_id' => $chargeId,
                    ]
                );

                return [
                    'success' => true,
                    'message' => 'Cargo eliminado exitosamente de VirtualPos.',
                    'data' => $result
                ];
            }

            $errorMessage = $result['error']['message'] ?? $result['message'] ?? 'Error desconocido al eliminar el cargo';

            Log::channel('daily')->error('VirtualPos: Error al eliminar cargo', [
                'subscription_id' => $subscription->id,
                'installment_id' => $installment->id,
                'charge_id' => $chargeId,
                'error_code' => $errorCode,
                'error_message' => $errorMessage,
            ]);

            return [
                'success' => false,
                'message' => "Error al eliminar cargo: {$errorMessage}",
                'error_code' => $errorCode,
            ];

        } catch (\Exception $e) {
            Log::channel('daily')->error('VirtualPos: Excepción al eliminar cargo', [
                'subscription_id' => $subscription->id,
                'installment_id' => $installment->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'message' => 'Error al procesar la solicitud: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Cancelar todos los cargos futuros de una suscripción
     * Endpoint: DELETE /charges/{subscription_id}
     */
    public function cancelFutureCharges(ProgramSubscription $subscription): array
    {
        try {
            if (empty($subscription->virtualpos_subscription_id)) {
                return [
                    'success' => false,
                    'message' => 'La suscripción no tiene ID de VirtualPos asociado.'
                ];
            }

            Log::channel('daily')->info('VirtualPos: Cancelando cargos futuros de suscripción', [
                'subscription_id' => $subscription->id,
                'virtualpos_subscription_id' => $subscription->virtualpos_subscription_id,
            ]);

            $headers = $this->getHeaders();
            $endpoint = "{$this->apiUrl}/charges/{$subscription->virtualpos_subscription_id}";

            Log::channel('daily')->info('VirtualPos: Llamando DELETE a endpoint de cargos futuros', [
                'endpoint' => $endpoint,
            ]);

            $response = Http::withHeaders($headers)
                ->delete($endpoint);

            $result = $response->json();

            Log::channel('daily')->info('VirtualPos: Respuesta de cancelación de cargos futuros', [
                'status_code' => $response->status(),
                'response' => $result,
            ]);

            if ($response->successful() && (!isset($result['status']) || $result['status'] !== 'NOK')) {
                $this->logOperationSuccess(
                    "Cargos futuros cancelados para suscripción {$subscription->virtualpos_subscription_id}",
                    [
                        'subscription_id' => $subscription->id,
                    ]
                );

                return [
                    'success' => true,
                    'message' => 'Cargos futuros cancelados exitosamente.',
                    'data' => $result
                ];
            }

            $errorMessage = $result['error']['message'] ?? $result['message'] ?? 'Error desconocido';

            Log::channel('daily')->error('VirtualPos: Error al cancelar cargos futuros', [
                'subscription_id' => $subscription->id,
                'error_message' => $errorMessage,
            ]);

            return [
                'success' => false,
                'message' => "Error al cancelar cargos futuros: {$errorMessage}",
            ];

        } catch (\Exception $e) {
            Log::channel('daily')->error('VirtualPos: Excepción al cancelar cargos futuros', [
                'subscription_id' => $subscription->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'message' => 'Error al procesar la solicitud: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Crear un nuevo cargo para la suscripción (sin cuota asociada)
     */
    public function createNewChargeForSubscription(ProgramSubscription $subscription, int $amount, string $description, ?string $dueDate = null): array
    {
        try {
            // Verificar que la suscripción está activa
            if ($subscription->status !== 'ACTIVA') {
                return [
                    'success' => false,
                    'message' => 'Solo se pueden crear cargos para suscripciones activas.'
                ];
            }

            // Verificar que tenga ID de suscripción
            if (empty($subscription->virtualpos_subscription_id)) {
                return [
                    'success' => false,
                    'message' => 'La suscripción no tiene ID de VirtualPos asociado.'
                ];
            }

            // Generar UUID para la petición
            $uuid = \Illuminate\Support\Str::uuid()->toString();

            $payload = [
                'uuid' => $uuid,
                'suscription_id' => $subscription->virtualpos_subscription_id,
                'amount' => $amount,
                'description' => $description,
            ];

            // Agregar fecha de cobro si se proporciona
            if ($dueDate) {
                $payload['charge_date'] = $dueDate;
            }

            $headers = $this->getHeaders($uuid);
            $endpoint = "{$this->apiUrl}/charge";

            Log::channel('daily')->info('VirtualPos: Creando nuevo cargo para suscripción', [
                'subscription_id' => $subscription->id,
                'virtualpos_subscription_id' => $subscription->virtualpos_subscription_id,
                'amount' => $amount,
                'description' => $description,
                'due_date' => $dueDate,
                'uuid' => $uuid,
                'endpoint' => $endpoint,
                'api_url_config' => $this->apiUrl,
                'api_key_length' => strlen($this->apiKey ?? ''),
                'secret_key_length' => strlen($this->secretKey ?? ''),
                'headers_authorization' => substr($headers['Authorization'] ?? '', 0, 20) . '...',
                'headers_signature_length' => strlen($headers['Signature'] ?? ''),
                'payload' => $payload,
            ]);

            $response = Http::withHeaders($headers)
                ->post($endpoint, $payload);

            Log::channel('daily')->info('VirtualPos: Respuesta RAW de creación de cargo', [
                'status_code' => $response->status(),
                'body_raw' => $response->body(),
            ]);

            $result = $response->json();

            Log::info('VirtualPos: Respuesta de creación de nuevo cargo', [
                'status_code' => $response->status(),
                'response' => $result,
            ]);

            if ($response->successful() && (!isset($result['status']) || $result['status'] !== 'NOK')) {
                $chargeId = $result['charge']['id'] ?? $result['id'] ?? null;

                $this->logOperationSuccess(
                    "Nuevo cargo creado para suscripción {$subscription->virtualpos_subscription_id}",
                    [
                        'subscription_id' => $subscription->id,
                        'charge_id' => $chargeId,
                        'amount' => $amount,
                        'description' => $description,
                    ]
                );

                return [
                    'success' => true,
                    'message' => 'Cargo creado exitosamente.',
                    'data' => $result,
                    'charge_id' => $chargeId,
                ];
            }

            $errorMessage = $result['error']['message'] ?? $result['message'] ?? 'Error desconocido al crear el cargo';
            $errorCode = $result['error']['error_code'] ?? 'UNKNOWN';

            Log::error('VirtualPos: Error al crear nuevo cargo', [
                'subscription_id' => $subscription->id,
                'error_code' => $errorCode,
                'error_message' => $errorMessage,
            ]);

            return [
                'success' => false,
                'message' => "Error al crear cargo: {$errorMessage}",
                'error_code' => $errorCode,
            ];

        } catch (\Exception $e) {
            Log::error('VirtualPos: Excepción al crear nuevo cargo', [
                'subscription_id' => $subscription->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'message' => 'Error al procesar la solicitud: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Generar JWT para autenticación con VirtualPos
     */
    private function generateJWT(?string $uuid = null): string
    {
        $payload = [
            'api_key' => $this->apiKey,
        ];

        if ($uuid) {
            $payload['uuid'] = $uuid;
        }

        return JWT::encode($payload, $this->secretKey, 'HS256');
    }
}
