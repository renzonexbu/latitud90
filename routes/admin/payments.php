<?php

use App\Http\Controllers\Admin\PaymentsController;
use Illuminate\Support\Facades\Route;

Route::prefix('payments')->name('payments.')->group(function () {
    Route::get('/', [PaymentsController::class, 'index'])->name('index');
    Route::get('/{payment}', [PaymentsController::class, 'show'])->name('show');
    Route::put('/{payment}', [PaymentsController::class, 'update'])->name('update');
    Route::post('/bulk-action', [PaymentsController::class, 'bulkAction'])->name('bulk-action');
    Route::get('/export', [PaymentsController::class, 'export'])->name('export');
    Route::post('/{payment}/refund', [PaymentsController::class, 'refund'])->name('refund');
});