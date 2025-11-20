<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== Estructura de orders_detail ===\n\n";

$columns = DB::select("SHOW COLUMNS FROM orders_detail");

echo "Columnas:\n";
foreach ($columns as $column) {
    echo "  - {$column->Field} ({$column->Type})\n";
}

echo "\n¿Existe installments_number? ";
$exists = false;
foreach ($columns as $column) {
    if ($column->Field === 'installments_number') {
        $exists = true;
        break;
    }
}
echo ($exists ? "✅ SÍ" : "❌ NO") . "\n";

if (!$exists) {
    echo "\n⚠️  El campo installments_number NO existe en la tabla\n";
    echo "   Necesitamos crear una migración para agregarlo\n";
}
