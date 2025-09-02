<?php

use App\Http\Controllers\Admin\MaintainerController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'role:super_admin'])->group(function () {
    Route::get('/maintainer', [MaintainerController::class, 'index'])->name('maintainer.index');
    
    // Newsletter routes
    Route::prefix('maintainer/newsletter')->name('maintainer.newsletter.')->group(function () {
        Route::get('/', [MaintainerController::class, 'newsletter'])->name('index');
        Route::get('/export', [MaintainerController::class, 'exportNewsletter'])->name('export');
        Route::get('/{newsletter}/edit', [MaintainerController::class, 'editNewsletter'])->name('edit');
        Route::put('/{newsletter}', [MaintainerController::class, 'updateNewsletter'])->name('update');
        Route::delete('/{newsletter}', [MaintainerController::class, 'destroyNewsletter'])->name('destroy');
    });

    // Admin Logs routes
    Route::prefix('maintainer/admin-logs')->name('maintainer.admin-logs.')->group(function () {
        Route::get('/', [MaintainerController::class, 'adminLogs'])->name('index');
        Route::get('/export', [MaintainerController::class, 'exportAdminLogs'])->name('export');
        Route::get('/{log}', [MaintainerController::class, 'showAdminLog'])->name('show');
    });
});
