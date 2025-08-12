<?php

use App\Http\Controllers\Client\ProgramController;
use App\Http\Controllers\Client\GeneratePaymentController;
use App\Http\Controllers\Client\ConfirmPaymentController;
use App\Http\Controllers\Client\ProcessPaymentController;
use App\Http\Controllers\Client\PaymentGatewayController;
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
Route::get('/payment/success/{orderDetailId}', [ProcessPaymentController::class, 'paymentSuccess'])->name('payment.success');
Route::get('/payment/failure/{orderDetailId}', [ProcessPaymentController::class, 'paymentFailure'])->name('payment.failure');

// Callback Transbank con spinner y confirmación
Route::get('/payment/callback/{orderDetailId}', [PaymentGatewayController::class, 'callbackSpinner'])->name('payment.callback');
Route::post('/payment/confirm', [PaymentGatewayController::class, 'confirmTransbank'])->name('payment.confirm');

// Payment Gateway Notification Routes (deshabilitadas: confirmación vía polling)
// Route::post('/webhook/transbank', [PaymentGatewayController::class, 'handleTransbankNotification'])->name('webhook.transbank');
// Route::post('/webhook/khipu', [PaymentGatewayController::class, 'handleKhipuNotification'])->name('webhook.khipu');

// Payment Result Processing
Route::get('/payment/result/{orderDetailId}', [PaymentGatewayController::class, 'processPaymentResult'])->name('payment.result');
Route::get('/payment/status/{orderDetailId}', [PaymentGatewayController::class, 'checkPaymentStatus'])->name('payment.status');

// Khipu callback con spinner y confirmación por consulta
Route::get('/khipu/callback/{orderDetailId}', [PaymentGatewayController::class, 'khipuCallbackSpinner'])->name('khipu.callback');
Route::post('/khipu/confirm', [PaymentGatewayController::class, 'confirmKhipu'])->name('khipu.confirm');
