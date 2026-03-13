<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\SubscriptionController;

Route::prefix('subscriptions')->name('subscriptions.')->group(function () {
    Route::get('/', [SubscriptionController::class, 'index'])->name('index');
    Route::get('/charges', [SubscriptionController::class, 'charges'])->name('charges');
    Route::get('/charge-attempts', [SubscriptionController::class, 'chargeAttempts'])->name('charge-attempts');
    Route::get('/charge-attempts/export', [SubscriptionController::class, 'exportChargeAttempts'])->name('charge-attempts.export');
    Route::get('/{subscription}', [SubscriptionController::class, 'show'])->name('show');
    Route::post('/{subscription}/sync', [SubscriptionController::class, 'syncWithVirtualPos'])->name('sync');
    Route::post('/{subscription}/resend-subscription-email', [SubscriptionController::class, 'resendSubscriptionEmail'])->name('resend-subscription-email');
    Route::post('/{subscription}/resend-payment-email', [SubscriptionController::class, 'resendPaymentEmail'])->name('resend-payment-email');
    Route::post('/{subscription}/charge', [SubscriptionController::class, 'createCharge'])->name('charge');
    Route::post('/{subscription}/retry-charge', [SubscriptionController::class, 'retryCharge'])->name('retry-charge');
    Route::post('/{subscription}/new-charge', [SubscriptionController::class, 'createNewCharge'])->name('new-charge');
    Route::delete('/{subscription}/charge', [SubscriptionController::class, 'deleteCharge'])->name('delete-charge');
    Route::delete('/{subscription}', [SubscriptionController::class, 'cancel'])->name('cancel');
});
