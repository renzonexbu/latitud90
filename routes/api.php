<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Client\ProcessPaymentController;
use App\Http\Controllers\Client\PaymentConfirmationController;
use App\Http\Controllers\Client\GuardianPermissionController;
use App\Http\Controllers\AnalyticsController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// Payment Gateway Notification Routes (sin CORS)
Route::post('/payment/notification/transbank', [ProcessPaymentController::class, 'transbankNotification'])->name('api.payment.notification.transbank');
Route::post('/payment/notification/virtualpos', [PaymentConfirmationController::class, 'handleVirtualPosWebhook'])->name('api.payment.notification.virtualpos');
Route::post('/payment/notification/khipu', [ProcessPaymentController::class, 'khipuNotification'])->name('api.payment.notification.khipu');

// Subscription VirtualPos Callbacks (sin CSRF, sin sesión)
Route::match(['get', 'post'], '/subscription/return', [\App\Http\Controllers\Client\SubscriptionController::class, 'returnUrl'])->name('api.subscription.return');
Route::post('/subscription/callback', [\App\Http\Controllers\Client\SubscriptionController::class, 'callback'])->name('api.subscription.callback');
Route::post('/subscription/webhook', [\App\Http\Controllers\Client\SubscriptionController::class, 'webhook'])->name('api.subscription.webhook');

// Verificar estado de suscripción (necesita sesión para detectar guardian logueado, sin CSRF)
Route::post('/subscription/check-status', [\App\Http\Controllers\Client\SubscriptionController::class, 'checkSubscriptionStatus'])
    ->middleware([
        \App\Http\Middleware\EncryptCookies::class,
        \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
        \Illuminate\Session\Middleware\StartSession::class,
    ])
    ->name('api.subscription.check-status');

// Verificar estado del primer cargo (para polling desde vista de verificación)
Route::get('/subscription/check-first-charge/{subscriptionId}', [\App\Http\Controllers\Client\SubscriptionController::class, 'checkFirstChargeStatus'])->name('api.subscription.check-first-charge');

// Analytics Routes
Route::post('/analytics/program-list-view', [AnalyticsController::class, 'recordProgramListView']);
Route::post('/analytics/program-selection', [AnalyticsController::class, 'recordProgramSelection']);
Route::post('/analytics/payment-selection', [AnalyticsController::class, 'recordPaymentSelection']);
Route::post('/analytics/program-detail-view', [AnalyticsController::class, 'recordProgramDetailView']);
Route::post('/analytics/payment-details-view', [AnalyticsController::class, 'recordPaymentDetailsView']);

Route::post('/analytics/confirmation-view', [AnalyticsController::class, 'recordConfirmationView']);
Route::post('/analytics/payment-initiated', [AnalyticsController::class, 'recordPaymentInitiated']);
Route::post('/analytics/payment-completed', [AnalyticsController::class, 'recordPaymentCompleted']);
Route::post('/analytics/payment-failed', [AnalyticsController::class, 'recordPaymentFailed']);
Route::post('/analytics/confirmation-changes', [AnalyticsController::class, 'recordConfirmationChanges']);
