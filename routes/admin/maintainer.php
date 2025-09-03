<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\MaintainerController;

Route::middleware(['auth', 'verified', 'role:super_admin'])->group(function () {
    Route::prefix('maintainer')->name('maintainer.')->group(function () {
        Route::get('/', [MaintainerController::class, 'index'])->name('index');
        
        // Marketing Mails
        Route::prefix('marketing-mails')->name('marketing.mails.')->group(function () {
            Route::get('/', [MaintainerController::class, 'marketingMails'])->name('index');
            Route::post('/{id}/toggle-status', [MaintainerController::class, 'toggleMarketingMailStatus'])->name('toggle-status');
            Route::delete('/{id}', [MaintainerController::class, 'destroyMarketingMail'])->name('destroy');
            Route::post('/export', [MaintainerController::class, 'exportMarketingMails'])->name('export');
        });
        
        // Newsletter
        Route::prefix('newsletter')->name('newsletter.')->group(function () {
            Route::get('/', [MaintainerController::class, 'newsletter'])->name('index');
            Route::get('/{id}/edit', [MaintainerController::class, 'editNewsletter'])->name('edit');
            Route::put('/{id}', [MaintainerController::class, 'updateNewsletter'])->name('update');
            Route::delete('/{id}', [MaintainerController::class, 'destroyNewsletter'])->name('destroy');
            Route::post('/export', [MaintainerController::class, 'exportNewsletter'])->name('export');
        });
        
        // Admin Logs
        Route::prefix('admin-logs')->name('admin-logs.')->group(function () {
            Route::get('/', [MaintainerController::class, 'adminLogs'])->name('index');
            Route::get('/{id}', [MaintainerController::class, 'showAdminLog'])->name('show');
            Route::post('/export', [MaintainerController::class, 'exportAdminLogs'])->name('export');
        });
    });
});
