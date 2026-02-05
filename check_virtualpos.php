<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\Http;
use App\Models\Payment;

$orders = ['LT26020FTMQN', 'LT26023KOK8T', 'LT2602DIJMEM'];

$baseUrl = config('services.virtualpos.base_url', 'https://api.virtualpos.cl');
$secretKey = config('services.virtualpos.secret_key');

foreach ($orders as $buyOrder) {
    echo "═══════════════════════════════════════\n";
    echo "BUY ORDER: $buyOrder\n";

    $payment = Payment::where('buy_order', $buyOrder)->first();
    if (!$payment) {
        echo "❌ Payment not found\n\n";
        continue;
    }

    echo "Payment ID: {$payment->id}\n";
    $uuid = $payment->token;
    echo "UUID/Token: $uuid\n\n";

    // Consultar directamente la API de VirtualPos
    try {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $secretKey,
            'Content-Type' => 'application/json',
        ])->get("$baseUrl/api/v3/single-payment/$uuid");

        echo "HTTP Status: " . $response->status() . "\n";
        $data = $response->json();
        
        if (isset($data['data']['payment']['order'])) {
            $order = $data['data']['payment']['order'];
            echo "ESTADO: " . ($order['status'] ?? 'N/A') . "\n";
            echo "MONTO: $" . number_format($order['amount'] ?? 0, 0, ',', '.') . "\n";
            echo "AUTH CODE: " . ($order['auth_code'] ?? 'N/A') . "\n";
            echo "CREATED: " . ($order['created_at'] ?? 'N/A') . "\n";
            echo "AUTHORIZED: " . ($order['authorized_at'] ?? 'N/A') . "\n";
        }
        
        echo "\nRESPUESTA COMPLETA:\n";
        echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
    } catch (\Exception $e) {
        echo "❌ Error: " . $e->getMessage() . "\n";
    }
    echo "\n";
}

// Limpiar archivo temporal
