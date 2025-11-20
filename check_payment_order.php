<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Payment;

$payment = Payment::with('orderDetail.order.programCourse')->find(1);

if (!$payment) {
    echo "Payment not found\n";
    exit(1);
}

echo "Payment ID: {$payment->id}\n";
echo "OrderDetail ID: " . ($payment->orderDetail ? $payment->orderDetail->id : 'NULL') . "\n";

if ($payment->orderDetail) {
    echo "Order ID: " . ($payment->orderDetail->order ? $payment->orderDetail->order->id : 'NULL') . "\n";
    
    if ($payment->orderDetail->order) {
        echo "Order program_id: {$payment->orderDetail->order->program_id}\n";
        echo "ProgramCourse: " . ($payment->orderDetail->order->programCourse ? 'EXISTS' : 'NULL') . "\n";
        
        if ($payment->orderDetail->order->programCourse) {
            echo "ProgramCourse ID: {$payment->orderDetail->order->programCourse->id}\n";
            echo "ProgramCourse name: {$payment->orderDetail->order->programCourse->name}\n";
        }
    }
}
