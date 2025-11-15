<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Support\Facades\Http;

class TestVirtualPosJWT extends Command
{
    protected $signature = 'virtualpos:test-jwt';
    protected $description = 'Test VirtualPos JWT signature generation';

    public function handle()
    {
        $apiKey = config('services.virtualpos.api_key');
        $secretKey = config('services.virtualpos.secret_key');
        $apiUrl = config('services.virtualpos.api_url');

        $this->info('=== VirtualPos JWT Test ===');
        $this->info('API URL: ' . $apiUrl);
        $this->info('API Key: ' . $apiKey);
        $this->info('Secret Key: ' . substr($secretKey, 0, 10) . '...' . substr($secretKey, -10));
        $this->newLine();

        // Test 1: Correct JWT structure (api_key + uuid only)
        $this->info('Test 1: Correct JWT structure according to VirtualPos docs (api_key + uuid only)');
        $uuid = \Illuminate\Support\Str::uuid()->toString();
        $correctPayload = [
            'api_key' => $apiKey,
            'uuid' => $uuid,
        ];
        $correctJwt = JWT::encode($correctPayload, $secretKey, 'HS256');
        $this->info('JWT: ' . $correctJwt);
        $this->info('Payload: ' . json_encode($correctPayload, JSON_PRETTY_PRINT));
        $this->newLine();

        // Test 2: Decoding JWT to verify signature
        $this->info('Test 2: Decoding JWT to verify signature');
        try {
            $decoded = JWT::decode($correctJwt, new Key($secretKey, 'HS256'));
            $this->info('✓ JWT decodes successfully');
            $this->info('Decoded payload: ' . json_encode($decoded, JSON_PRETTY_PRINT));
        } catch (\Exception $e) {
            $this->error('✗ JWT decode failed: ' . $e->getMessage());
        }
        $this->newLine();

        // Test 3: Prepare plan data with UUID in body
        $this->info('Test 3: Prepare plan data (UUID also goes in body)');
        $planData = [
            'uuid' => $uuid, // UUID must be in BOTH JWT and body
            'id' => 'TEST_PLAN_' . time(),
            'name' => 'Test Plan',
            'description' => 'Plan de prueba',
            'is_active' => 'T',
            'amount' => 10000.0,
            'currency' => 'CLP',
            'trial_days' => 0,
            'num_charges' => 12,
            'frequency_type' => 'Mensual',
            'return_url' => base64_encode('http://latitud90.test/admin/programs'),
            'type' => 'MONTO_FIJO',
            'fixed_amount_day_charge' => '01',
            'show_in_terminal' => 'F',
            'automatic_renewal' => 'T',
            'shipping_address' => '',
        ];

        $headers = [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'Authorization' => $apiKey,
            'Signature' => $correctJwt,
        ];

        $this->info('Headers: ' . json_encode($headers, JSON_PRETTY_PRINT));
        $this->info('Plan Data: ' . json_encode($planData, JSON_PRETTY_PRINT));
        $this->newLine();

        // Test 4: Make API call with correct structure
        $this->info('Test 4: Making API call to VirtualPos with correct JWT structure');
        try {
            $response = Http::withHeaders($headers)
                ->post($apiUrl . '/plan', $planData);

            $this->info('Response Status: ' . $response->status());
            $this->info('Response Body: ' . $response->body());

            $result = $response->json();
            if (isset($result['status']) && $result['status'] === 'NOK') {
                $this->error('API Error: ' . ($result['error']['message'] ?? 'Unknown'));
                $this->error('Error Code: ' . ($result['error']['error_code'] ?? 'Unknown'));
            } else {
                $this->info('✓ Success! Plan created.');
                $planId = $result['plan']['id'] ?? $result['id'] ?? $result['plan_id'] ?? 'Unknown';
                $this->info('Plan ID: ' . $planId);
                if (isset($result['plan']['suscription_url'])) {
                    $this->info('Subscription URL: ' . $result['plan']['suscription_url']);
                }
            }
        } catch (\Exception $e) {
            $this->error('Exception: ' . $e->getMessage());
        }

        return 0;
    }
}
