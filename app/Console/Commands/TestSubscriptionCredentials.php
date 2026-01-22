<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Firebase\JWT\JWT;
use Illuminate\Support\Str;

class TestSubscriptionCredentials extends Command
{
    protected $signature = 'subscription:test-credentials';
    protected $description = 'Verificar que las credenciales de suscripción VirtualPOS funcionan correctamente';

    public function handle()
    {
        $this->info('=== Test de Credenciales VirtualPOS Suscripciones ===');
        $this->newLine();

        // Mostrar configuración actual
        $env = config('services.virtualpos.api_url');
        $apiKey = config('services.virtualpos.api_key');
        $secretKey = config('services.virtualpos.secret_key');
        $merchantCode = config('services.virtualpos.merchant_code');

        $this->info('Configuración actual:');
        $this->line("  API URL: {$env}");
        $this->line("  API Key: " . ($apiKey ? substr($apiKey, 0, 10) . '...' : 'NO CONFIGURADA'));
        $this->line("  Secret Key: " . ($secretKey ? substr($secretKey, 0, 10) . '...' : 'NO CONFIGURADA'));
        $this->line("  Merchant Code: " . ($merchantCode ?: 'NO CONFIGURADO'));
        $this->newLine();

        if (!$apiKey || !$secretKey) {
            $this->error('❌ Faltan credenciales. Verifica las variables de entorno.');
            return 1;
        }

        // Intentar listar planes (endpoint que debería funcionar si las credenciales son válidas)
        $this->info('Probando conexión con endpoint /plan...');

        try {
            // Generar JWT (para GET no necesita UUID)
            $payload = [
                'api_key' => $apiKey,
            ];
            $jwt = JWT::encode($payload, $secretKey, 'HS256');

            // VirtualPOS v3 usa Authorization para API Key y Signature para JWT
            $headers = [
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
                'Authorization' => $apiKey,
                'Signature' => $jwt,
            ];

            $response = Http::withHeaders($headers)
                ->timeout(30)
                ->get($env . '/plan');

            $statusCode = $response->status();
            $body = $response->json();

            $this->line("  Status HTTP: {$statusCode}");

            if ($response->successful()) {
                $this->info('✅ Conexión exitosa!');

                if (isset($body['items'])) {
                    $this->info("  Planes encontrados: " . count($body['items']));

                    if (count($body['items']) > 0) {
                        $this->newLine();
                        $this->info('Planes disponibles:');
                        foreach ($body['items'] as $plan) {
                            $this->line("  - ID: {$plan['id']} | Nombre: {$plan['name']} | Monto: \${$plan['amount']}");
                        }
                    }
                } else {
                    $this->line("  Respuesta: " . json_encode($body, JSON_PRETTY_PRINT));
                }

                return 0;
            } else {
                $this->error('❌ Error en la conexión');
                $this->line("  Respuesta: " . json_encode($body, JSON_PRETTY_PRINT));

                if (isset($body['error'])) {
                    $this->error("  Código: " . ($body['error']['error_code'] ?? 'N/A'));
                    $this->error("  Mensaje: " . ($body['error']['message'] ?? 'N/A'));
                }

                return 1;
            }
        } catch (\Exception $e) {
            $this->error('❌ Excepción: ' . $e->getMessage());
            return 1;
        }
    }
}
