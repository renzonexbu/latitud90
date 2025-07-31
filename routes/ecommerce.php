<?php

use App\Http\Controllers\EcommerceController;
use App\Http\Controllers\PaymentGatewayController;
use Illuminate\Support\Facades\Route;

// Rutas públicas de ecommerce
Route::get('/', [EcommerceController::class, 'index'])->name('ecommerce.index');
Route::get('/ecommerce', [EcommerceController::class, 'index'])->name('ecommerce.home');
Route::get('/terminos-y-condiciones', [EcommerceController::class, 'termsAndConditions'])->name('ecommerce.terms-and-conditions');
Route::get('/programa/{program}', [EcommerceController::class, 'show'])->name('ecommerce.show');
Route::get('/reserva/{program}', [EcommerceController::class, 'reservation'])->name('ecommerce.reservation');
Route::post('/reserva/{program}', [EcommerceController::class, 'storeReservation'])->name('ecommerce.store-reservation');
Route::get('/pago/{passenger}', [EcommerceController::class, 'payment'])->name('ecommerce.payment');
Route::post('/procesar-pago/{payment}', [EcommerceController::class, 'processPayment'])->name('ecommerce.process-payment');
Route::get('/confirmacion/{passenger}', [EcommerceController::class, 'confirmation'])->name('ecommerce.confirmation');
Route::get('/buscar-reserva', [EcommerceController::class, 'findReservation'])->name('ecommerce.find-reservation');
Route::post('/buscar-reserva', [EcommerceController::class, 'searchReservation'])->name('ecommerce.search-reservation');
Route::get('/buscar-viajes', [EcommerceController::class, 'searchByRut'])->name('ecommerce.search-by-rut');

// Rutas del nuevo flujo de pago por pasos
Route::get('/payment/method', [EcommerceController::class, 'paymentMethod'])->name('payment.method');
Route::post('/payment/setup', [EcommerceController::class, 'setupPayment'])->name('payment.setup');
Route::get('/payment/gateway/{passenger}', [EcommerceController::class, 'paymentGateway'])->name('payment.gateway');

// Rutas de pasarelas de pago
Route::get('/payment/return/{payment}', [PaymentGatewayController::class, 'return'])->name('payment.return');
Route::post('/payment/webhook/transbank', [PaymentGatewayController::class, 'transbankWebhook'])->name('payment.webhook.transbank');
Route::post('/payment/webhook/khipu', [PaymentGatewayController::class, 'khipuWebhook'])->name('payment.webhook.khipu');
Route::get('/payment/link/{paymentLink}', [PaymentGatewayController::class, 'paymentLink'])->name('payment.link');
Route::post('/payment/link/{paymentLink}/process', [PaymentGatewayController::class, 'processPaymentLink'])->name('payment.link.process');
