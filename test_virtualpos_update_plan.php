<?php

require __DIR__.'/vendor/autoload.php';

use Illuminate\Support\Facades\Http;
use Firebase\JWT\JWT;

// Cargar configuración
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Obtener credenciales de VirtualPos
$apiUrl = config('services.virtualpos.api_url');
$apiKey = config('services.virtualpos.api_key');
$secretKey = config('services.virtualpos.secret_key');

echo "🔧 Probando API de VirtualPos\n";
echo "API URL: {$apiUrl}\n";
echo "API Key: {$apiKey}\n\n";

// Generar JWT
function generateJWT($apiKey, $secretKey, $payload = []) {
    $jwtPayload = array_merge([
        'api_key' => $apiKey,
        'iat' => time(),
    ], $payload);

    return JWT::encode($jwtPayload, $secretKey, 'HS256');
}

// Obtener headers
function getHeaders($apiKey, $secretKey, $payload = []) {
    return [
        'Content-Type' => 'application/json',
        'Accept' => 'application/json',
        'Authorization' => $apiKey,
        'Signature' => generateJWT($apiKey, $secretKey, $payload),
    ];
}

echo "📋 PASO 1: Listando planes existentes...\n";
echo str_repeat('-', 50) . "\n";

$response = Http::withHeaders(getHeaders($apiKey, $secretKey))
    ->get($apiUrl . '/plans');

if ($response->successful()) {
    $result = $response->json();
    echo "✅ Planes obtenidos:\n";
    echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n\n";

    // Si hay planes, intentar actualizar el primero
    $plans = $result['plans'] ?? [];
    if (!empty($plans) && is_array($plans)) {
        $firstPlan = $plans[0];
        $planId = $firstPlan['id'] ?? $firstPlan['plan_id'] ?? null;

        if ($planId) {
            echo "🔄 PASO 2: Intentando actualizar plan '{$planId}'...\n";
            echo str_repeat('-', 50) . "\n";

            // Intentar cambiar is_active a 'F' (inactivo)
            $updateData = [
                'is_active' => 'F'
            ];

            echo "Datos a enviar: " . json_encode($updateData) . "\n\n";

            $updateResponse = Http::withHeaders(getHeaders($apiKey, $secretKey, $updateData))
                ->put($apiUrl . '/plan/' . $planId, $updateData);

            echo "Status Code: " . $updateResponse->status() . "\n";
            echo "Response Body:\n";
            echo json_encode($updateResponse->json(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n\n";

            if ($updateResponse->successful()) {
                echo "✅ PUT exitoso! El plan se puede actualizar.\n";
            } else {
                echo "❌ PUT falló. VirtualPos no permite actualizar planes.\n";
                echo "Error: " . $updateResponse->body() . "\n";
            }
        } else {
            echo "⚠️ No se pudo obtener el ID del plan\n";
        }
    } else {
        echo "⚠️ No hay planes existentes. Creando uno de prueba primero...\n\n";

        echo "🆕 PASO 2: Creando plan de prueba...\n";
        echo str_repeat('-', 50) . "\n";

        $testPlan = [
            'id' => 'TEST_PLAN_' . time(),
            'name' => 'Plan de Prueba',
            'description' => 'Plan de prueba para verificar actualización',
            'is_active' => 'T',
            'amount' => 10000,
            'currency' => 'CLP',
            'trial_days' => 0,
            'num_charges' => 12,
            'frequency_type' => 'Mensual',
            'return_url' => base64_encode('https://test.com/return'),
            'type' => 'MONTO_FIJO',
            'fixed_amount_day_charge' => '01',
            'show_in_terminal' => 'F',
            'automatic_renewal' => 'F',
        ];

        $createResponse = Http::withHeaders(getHeaders($apiKey, $secretKey, $testPlan))
            ->post($apiUrl . '/plan', $testPlan);

        echo "Status Code: " . $createResponse->status() . "\n";
        echo "Response:\n";
        echo json_encode($createResponse->json(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n\n";

        if ($createResponse->successful()) {
            $result = $createResponse->json();
            $newPlanId = $result['id'] ?? $result['plan_id'] ?? $testPlan['id'];

            echo "✅ Plan creado: {$newPlanId}\n\n";

            echo "🔄 PASO 3: Intentando actualizar el plan recién creado...\n";
            echo str_repeat('-', 50) . "\n";

            $updateData = [
                'is_active' => 'F'
            ];

            $updateResponse = Http::withHeaders(getHeaders($apiKey, $secretKey, $updateData))
                ->put($apiUrl . '/plan/' . $newPlanId, $updateData);

            echo "Status Code: " . $updateResponse->status() . "\n";
            echo "Response Body:\n";
            echo json_encode($updateResponse->json(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n\n";

            if ($updateResponse->successful()) {
                echo "✅ PUT exitoso! El plan se puede actualizar.\n";
            } else {
                echo "❌ PUT falló. VirtualPos no permite actualizar planes.\n";
            }
        } else {
            echo "❌ Error al crear plan de prueba\n";
        }
    }
} else {
    echo "❌ Error al listar planes:\n";
    echo "Status: " . $response->status() . "\n";
    echo "Body: " . $response->body() . "\n";
}

echo "\n" . str_repeat('=', 50) . "\n";
echo "Prueba completada\n";
