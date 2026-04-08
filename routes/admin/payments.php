<?php

use App\Http\Controllers\Admin\PaymentController;
use App\Http\Controllers\Admin\CreateParticularPaymentController;
use App\Http\Controllers\Admin\CreateRefundController;
use App\Http\Controllers\Admin\ImportManualPaymentsController;
use App\Http\Controllers\Admin\AcConversionController;
use App\Http\Controllers\Admin\Reports\PaymentConfirmationLogsController;
use Illuminate\Support\Facades\Route;

Route::prefix('payments')->name('payments.')->group(function () {
    // Rutas principales de pagos (sin parámetros dinámicos primero)
    Route::get('/', [PaymentController::class, 'index'])->name('index');
    Route::get('/create', [PaymentController::class, 'create'])->name('create');
    Route::post('/store', [PaymentController::class, 'store'])->name('store');

    // Reportes y exportaciones
    Route::get('export', [PaymentController::class, 'export'])->name('export');
    Route::get('pending-report', [PaymentController::class, 'pendingReport'])->name('pending-report');
    Route::get('revenue-report', [PaymentController::class, 'revenueReport'])->name('revenue-report');

    // Estado de participantes
    Route::post('participant-status', [PaymentController::class, 'getParticipantPaymentStatus'])->name('participant-status');

    // Historial de Confirmaciones de Pago (ANTES de rutas con {payment})
    Route::get('confirmations', [PaymentConfirmationLogsController::class, 'index'])->name('confirmations.index');
    Route::get('confirmations/{payment}', [PaymentConfirmationLogsController::class, 'show'])->name('confirmations.show');
    Route::post('confirmations/{payment}/resend', [PaymentConfirmationLogsController::class, 'resend'])->name('confirmations.resend');

    // Rutas para pagos presenciales
    Route::prefix('presential')->name('presential.')->group(function () {
        Route::get('menu', [CreateParticularPaymentController::class, 'menu'])->name('menu');
        Route::get('create', [CreateParticularPaymentController::class, 'create'])->name('create');
        Route::post('store', [CreateParticularPaymentController::class, 'store'])->name('store');
        Route::get('import', [ImportManualPaymentsController::class, 'import'])->name('import');
        Route::post('import-preview', [ImportManualPaymentsController::class, 'preview'])->name('import-preview');
        Route::post('import-store', [ImportManualPaymentsController::class, 'importStore'])->name('import-store');
        Route::get('import-progress', [ImportManualPaymentsController::class, 'importProgress'])->name('import-progress');
        Route::post('import-export-section', [ImportManualPaymentsController::class, 'exportSection'])->name('import-export-section');
        Route::post('participant-status', [CreateParticularPaymentController::class, 'getParticipantPaymentStatus'])->name('participant-status');
        Route::get('payment-type-options', [CreateParticularPaymentController::class, 'getPaymentTypeOptions'])->name('payment-type-options');
        Route::get('search-participants', [CreateParticularPaymentController::class, 'searchEnrolledParticipants'])->name('search-participants');
    });

    // Rutas para conversión AC → Boleta
    Route::prefix('ac-conversion')->name('ac-conversion.')->group(function () {
        Route::get('/', [AcConversionController::class, 'index'])->name('index');
        Route::post('/execute', [AcConversionController::class, 'execute'])->name('execute');
    });

    // Rutas para reembolsos
    Route::prefix('refunds')->name('refunds.')->group(function () {
        Route::get('menu', [CreateRefundController::class, 'menu'])->name('menu');
        Route::get('create', [CreateRefundController::class, 'create'])->name('create');
        Route::post('store', [CreateRefundController::class, 'store'])->name('store');
        Route::get('import', [CreateRefundController::class, 'import'])->name('import');
        Route::post('import-preview', [CreateRefundController::class, 'preview'])->name('import-preview');
        Route::post('import-store', [CreateRefundController::class, 'importStore'])->name('import-store');
        Route::post('participant-status', [CreateRefundController::class, 'getParticipantStatus'])->name('participant-status');
    });

    // Rutas con parámetros dinámicos {payment} AL FINAL
    Route::get('/{payment}', [PaymentController::class, 'show'])->name('show');
    Route::get('/{payment}/edit', [PaymentController::class, 'edit'])->name('edit');
    Route::put('/{payment}', [PaymentController::class, 'update'])->name('update');
    Route::delete('/{payment}', [PaymentController::class, 'destroy'])->name('destroy');
    Route::patch('{payment}/confirm', [PaymentController::class, 'confirm'])->name('confirm');
    Route::patch('{payment}/cancel', [PaymentController::class, 'cancel'])->name('cancel');
    Route::post('{payment}/refund', [PaymentController::class, 'refund'])->name('refund');
    Route::post('{payment}/reconfirm', [PaymentController::class, 'reconfirm'])->name('reconfirm');
    Route::post('{payment}/retry-bsale', [PaymentController::class, 'retryBsale'])->name('retry-bsale');
});
