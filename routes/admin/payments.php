<?php

use App\Http\Controllers\Admin\PaymentController;
use Illuminate\Support\Facades\Route;

Route::prefix('payments')->name('payments.')->group(function () {
    // Rutas principales de pagos
    Route::resource('/', PaymentController::class)->names([
        'index' => 'index',
        'create' => 'create',
        'store' => 'store',
        'show' => 'show',
        'edit' => 'edit',
        'update' => 'update',
        'destroy' => 'destroy',
    ]);
    
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
    

});