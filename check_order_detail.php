<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\OrderDetail;

$orderDetail = OrderDetail::find(7);

echo "OrderDetail #7:\n";
echo "  installment_number: " . ($orderDetail->installment_number ?? 'NULL') . "\n";
echo "  installments_number: " . ($orderDetail->installments_number ?? 'NULL') . "\n";
echo "\nProblem: installments_number should be 12, not " . ($orderDetail->installments_number ?? 'NULL') . "\n";
