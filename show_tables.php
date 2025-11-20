<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== Tablas en la base de datos ===\n\n";

$database = DB::connection()->getDatabaseName();
echo "Database: {$database}\n\n";

$tables = DB::select('SHOW TABLES');

echo "Tablas disponibles:\n";
foreach ($tables as $table) {
    $tableName = array_values((array)$table)[0];
    echo "  - {$tableName}\n";
}
