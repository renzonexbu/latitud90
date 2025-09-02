<?php

use App\Http\Controllers\Admin\MarketingMailsController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    
    // Rutas para emails de marketing
    Route::prefix('marketing')->name('marketing.')->group(function () {
        
        // Lista de emails de marketing
        Route::get('/emails', [MarketingMailsController::class, 'index'])
            ->name('emails.index');
        
        // Cambiar estado de un email
        Route::patch('/emails/{marketingMail}/toggle-status', [MarketingMailsController::class, 'toggleStatus'])
            ->name('emails.toggle-status');
        
        // Eliminar un email
        Route::delete('/emails/{marketingMail}', [MarketingMailsController::class, 'destroy'])
            ->name('emails.destroy');
        
        // Procesar emails manualmente
        Route::post('/emails/process', [MarketingMailsController::class, 'processEmails'])
            ->name('emails.process');
        
        // Obtener estadísticas
        Route::get('/emails/stats', [MarketingMailsController::class, 'getStats'])
            ->name('emails.stats');
    });
});
