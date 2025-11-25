<?php

use App\Http\Controllers\Admin\SiteContentController;
use Illuminate\Support\Facades\Route;

Route::prefix('site-content')->name('site-content.')->group(function () {
    Route::get('/', [SiteContentController::class, 'index'])->name('index');
    Route::post('/', [SiteContentController::class, 'store'])->name('store');
    Route::get('/{section}/edit', [SiteContentController::class, 'edit'])->name('edit');
    Route::put('/{section}', [SiteContentController::class, 'update'])->name('update');
    Route::post('/upload-image', [SiteContentController::class, 'uploadImage'])->name('upload-image');
    Route::delete('/{siteContent}', [SiteContentController::class, 'destroy'])->name('destroy');
});
