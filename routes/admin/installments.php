<?php

use App\Http\Controllers\Admin\InstallmentController;
use Illuminate\Support\Facades\Route;

// Installment management routes
Route::prefix('installments')->name('installments.')->group(function () {
    // Reestructurar cuotas
    Route::post('restructure', [InstallmentController::class, 'restructure'])->name('restructure');
    
    // Recalcular cuotas después de aplicar descuento
    Route::post('recalculate-after-discount', [InstallmentController::class, 'recalculateAfterDiscount'])->name('recalculate-after-discount');
    
    // Ver detalles de un plan de cuotas
    Route::get('{installmentPlan}', [InstallmentController::class, 'show'])->name('show');
    
    // Listar cuotas de un plan
    Route::get('{installmentPlan}/installments', [InstallmentController::class, 'listInstallments'])->name('list');
});
