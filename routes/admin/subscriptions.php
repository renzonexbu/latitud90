<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\SubscriptionController;

Route::prefix('subscriptions')->name('subscriptions.')->group(function () {
    Route::get('/', [SubscriptionController::class, 'index'])->name('index');
    Route::get('/{subscription}', [SubscriptionController::class, 'show'])->name('show');
    Route::post('/{subscription}/sync', [SubscriptionController::class, 'syncWithVirtualPos'])->name('sync');
    Route::delete('/{subscription}', [SubscriptionController::class, 'cancel'])->name('cancel');
});
