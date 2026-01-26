<?php

use App\Http\Controllers\Admin\SiteContentController;
use Illuminate\Support\Facades\Route;

// Contenido del Sitio - Super Admin y Marketing
Route::prefix('site-content')->name('site-content.')
    ->middleware('role:super_admin,marketing')
    ->group(function () {
        Route::get('/', [SiteContentController::class, 'index'])->name('index');
        Route::get('/{section}/edit', [SiteContentController::class, 'edit'])->name('edit');

        // Solo Admin y Editor pueden crear/editar
        Route::post('/', [SiteContentController::class, 'store'])
            ->middleware('role:super_admin,marketing')
            ->name('store');
        Route::put('/{section}', [SiteContentController::class, 'update'])
            ->middleware('role:super_admin,marketing')
            ->name('update');
        Route::post('/upload-image', [SiteContentController::class, 'uploadImage'])
            ->middleware('role:super_admin,marketing')
            ->name('upload-image');

        // Solo Admin y Super Admin pueden eliminar
        Route::delete('/{siteContent}', [SiteContentController::class, 'destroy'])
            ->middleware('role:super_admin,marketing')
            ->name('destroy');
    });
