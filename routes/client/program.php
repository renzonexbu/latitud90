<?php

use App\Http\Controllers\Client\ProgramController;
use App\Http\Controllers\Client\GeneratePaymentController;
use App\Http\Controllers\Client\ConfirmPaymentController;
use App\Http\Controllers\Client\ProcessPaymentController;
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
