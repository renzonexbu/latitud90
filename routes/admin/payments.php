<?php

use App\Http\Controllers\Admin\PaymentController;
use App\Http\Controllers\Admin\CreateParticularPaymentController;
use App\Http\Controllers\Admin\CreateRefundController;
use App\Http\Controllers\Admin\ImportManualPaymentsController;
use Illuminate\Support\Facades\Route;

Route::prefix('payments')->name('payments.')->group(function () {
    // Rutas principales de pagos
    Route::get('/', [PaymentController::class, 'index'])->name('index');
    Route::get('/create', [PaymentController::class, 'create'])->name('create');
    Route::post('/store', [PaymentController::class, 'store'])->name('store');
    Route::get('/{payment}', [PaymentController::class, 'show'])->name('show');
    Route::get('/{payment}/edit', [PaymentController::class, 'edit'])->name('edit');
    Route::put('/{payment}', [PaymentController::class, 'update'])->name('update');
    Route::delete('/{payment}', [PaymentController::class, 'destroy'])->name('destroy');
    
    // Acciones específicas de pagos
    Route::patch('{payment}/confirm', [PaymentController::class, 'confirm'])->name('confirm');
    Route::patch('{payment}/cancel', [PaymentController::class, 'cancel'])->name('cancel');
    Route::post('{payment}/refund', [PaymentController::class, 'refund'])->name('refund');
    
    // Reportes y exportaciones
    Route::get('export', [PaymentController::class, 'export'])->name('export');
    Route::get('pending-report', [PaymentController::class, 'pendingReport'])->name('pending-report');
    Route::get('revenue-report', [PaymentController::class, 'revenueReport'])->name('revenue-report');
    
    // Estado de participantes
    Route::post('participant-status', [PaymentController::class, 'getParticipantPaymentStatus'])->name('participant-status');
    
    // Rutas para pagos presenciales
    Route::prefix('presential')->name('presential.')->group(function () {
        Route::get('menu', [CreateParticularPaymentController::class, 'menu'])->name('menu');
        Route::get('create', [CreateParticularPaymentController::class, 'create'])->name('create');
        Route::post('store', [CreateParticularPaymentController::class, 'store'])->name('store');
        Route::get('import', [ImportManualPaymentsController::class, 'import'])->name('import');
        Route::post('import-store', [ImportManualPaymentsController::class, 'importStore'])->name('import-store');
        Route::post('participant-status', [CreateParticularPaymentController::class, 'getParticipantPaymentStatus'])->name('participant-status');
        Route::get('payment-type-options', [CreateParticularPaymentController::class, 'getPaymentTypeOptions'])->name('payment-type-options');
    });
    
    // Rutas para reembolsos
    Route::prefix('refunds')->name('refunds.')->group(function () {
        Route::get('menu', [CreateRefundController::class, 'menu'])->name('menu');
        Route::get('create', [CreateRefundController::class, 'create'])->name('create');
        Route::post('store', [CreateRefundController::class, 'store'])->name('store');
        Route::get('import', [CreateRefundController::class, 'import'])->name('import');
        Route::post('import-store', [CreateRefundController::class, 'importStore'])->name('import-store');
        Route::post('participant-status', [CreateRefundController::class, 'getParticipantStatus'])->name('participant-status');
    });

});