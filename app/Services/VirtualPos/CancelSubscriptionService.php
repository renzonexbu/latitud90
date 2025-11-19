<?php

namespace App\Services\VirtualPos;

use App\Models\ProgramSubscription;
use App\Traits\SystemLogging;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Firebase\JWT\JWT;

class CancelSubscriptionService
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
     * Cancelar una suscripción en VirtualPos
     */
    public function cancel(ProgramSubscription $subscription): array
    {
        try {
            // Verificar que la suscripción está activa
            if (!$subscription->isActive() && $subscription->status !== 'SUSCRIBIENDO') {
                return [
                    'success' => false,
                    'message' => 'La suscripción no está activa y no puede ser cancelada.'
                ];
            }

            // Llamar a la API de VirtualPos
            $response = Http::withHeaders($this->getHeaders())
                ->delete("{$this->apiUrl}/suscription/{$subscription->virtualpos_subscription_id}");

            // Log de la respuesta
            Log::info('VirtualPos Cancel Subscription Response', [
                'subscription_id' => $subscription->id,
                'virtualpos_subscription_id' => $subscription->virtualpos_subscription_id,
                'status_code' => $response->status(),
                'response' => $response->json(),
            ]);

            if ($response->successful()) {
                $data = $response->json();

                // Actualizar la suscripción
                $subscription->update([
                    'status' => 'CANCELADA',
                    'cancelled_at' => now(),
                    'api_response' => $data,
                ]);

                // Cancelar el plan de cuotas (cargar manualmente)
                $installmentPlan = \App\Models\InstallmentPlan::where('participant_id', $subscription->participant_id)
                    ->where('program_id', $subscription->program_id)
                    ->first();

                if ($installmentPlan) {
                    $installmentPlan->update([
                        'status' => 'cancelled',
                    ]);

                    // Cancelar las cuotas pendientes
                    $installmentPlan->installments()
                        ->where('status', 'pending')
                        ->update(['status' => 'cancelled']);
                }

                $this->logOperationSuccess(
                    "Cancelación de suscripción {$subscription->virtualpos_subscription_id}",
                    [
                        'subscription_id' => $subscription->id,
                        'participant_id' => $subscription->participant_id,
                        'program_id' => $subscription->program_id,
                        'virtualpos_subscription_id' => $subscription->virtualpos_subscription_id,
                        'virtualpos_response' => $data,
                    ]
                );

                return [
                    'success' => true,
                    'message' => 'Suscripción cancelada exitosamente.',
                    'data' => $data
                ];
            }

            // Manejo de errores
            $errorData = $response->json();
            $errorMessage = $errorData['message'] ?? 'Error al cancelar la suscripción en VirtualPos';

            Log::error('VirtualPos Cancel Subscription Error', [
                'subscription_id' => $subscription->id,
                'virtualpos_subscription_id' => $subscription->virtualpos_subscription_id,
                'status_code' => $response->status(),
                'error' => $errorData,
            ]);

            return [
                'success' => false,
                'message' => $errorMessage,
                'error' => $errorData
            ];

        } catch (\Exception $e) {
            Log::error('Exception cancelling subscription', [
                'subscription_id' => $subscription->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'message' => 'Error al procesar la cancelación: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Obtener headers para autenticación con VirtualPos
     */
    private function getHeaders(): array
    {
        $jwt = $this->generateJWT();

        return [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'Authorization' => $this->apiKey,
            'Signature' => $jwt,
        ];
    }

    /**
     * Generar JWT para autenticación con VirtualPos
     */
    private function generateJWT(): string
    {
        $payload = [
            'api_key' => $this->apiKey,
        ];

        return JWT::encode($payload, $this->secretKey, 'HS256');
    }
}
