<?php

use App\Http\Controllers\Client\ProgramController;
use App\Http\Controllers\Client\GeneratePaymentController;
use App\Http\Controllers\Client\ConfirmPaymentController;
use App\Http\Controllers\Client\ProcessPaymentController;
use App\Http\Controllers\Client\PaymentConfirmationController;
use Illuminate\Support\Facades\Route;

// Rutas de programas
Route::get('/programs', [ProgramController::class, 'index'])->name('ecommerce.programs');
Route::get('/programs/{programId}', [ProgramController::class, 'show'])->name('ecommerce.program-detail');

// Payment Routes
Route::get('/programs/{programId}/payment', [GeneratePaymentController::class, 'show'])->name('payment.details');
Route::post('/programs/{programId}/payment', [GeneratePaymentController::class, 'store'])->name('payment.generate');

// Confirmation Route
Route::get('/programs/{programId}/confirmation', [ConfirmPaymentController::class, 'show'])->name('payment.confirmation');

// Payment Processing Routes
Route::post('/process-payment', [ProcessPaymentController::class, 'processPayment'])->name('payment.process');

// Rutas unificadas de confirmación de pagos
Route::get('/payment/callback/{orderDetailId}', [PaymentConfirmationController::class, 'showSpinner'])->name('payment.callback');
Route::post('/payment/confirm', [PaymentConfirmationController::class, 'confirmPayment'])->name('payment.confirm');

// Rutas unificadas de resultado
Route::get('/payment/success/{orderDetailId}', [PaymentConfirmationController::class, 'showSuccess'])->name('payment.success');
Route::get('/payment/failure/{orderDetailId}', [PaymentConfirmationController::class, 'showFailure'])->name('payment.failure');

// Rutas de compatibilidad (redirigir a las nuevas con gateway explícito)
Route::get('/khipu/callback/{orderDetailId}', function($orderDetailId) {
    return redirect()->route('payment.callback', ['orderDetailId' => $orderDetailId, 'gateway' => 'khipu']);
})->name('khipu.callback');

Route::get('/khipu/view/{orderDetailId}', function($orderDetailId) {
    return redirect()->route('payment.callback', ['orderDetailId' => $orderDetailId, 'gateway' => 'khipu']);
})->name('khipu.view');