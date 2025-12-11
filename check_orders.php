<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\OrderDetail;

// Ver las últimas 5 órdenes
$orders = OrderDetail::latest()->take(5)->get();

echo "ÚLTIMAS 5 ÓRDENES:\n";
echo "==================\n\n";

foreach($orders as $o) {
    echo "ID: {$o->id} | Order: {$o->order_id}\n";
    echo "Comprador: {$o->name}\n";
    echo "Creado: {$o->created_at}\n";
    echo "IP: " . ($o->ip_address ?? 'NULL') . "\n";
    echo "Device: " . ($o->device_type ?? 'NULL') . "\n";
    echo "Browser: " . ($o->browser ?? 'NULL') . "\n";
    echo "OS: " . ($o->operating_system ?? 'NULL') . "\n";
    echo "Terms At: " . ($o->terms_accepted_at ?? 'NULL') . "\n";
    echo "---\n";
}
