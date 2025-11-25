<?php

use App\Http\Controllers\Admin\SchoolController;
use Illuminate\Support\Facades\Route;

Route::prefix('schools')->name('schools.')->group(function () {
    Route::get('/', [SchoolController::class, 'index'])->name('index');
    Route::post('/', [SchoolController::class, 'store'])->name('store');
    Route::put('/section-content', [SchoolController::class, 'updateSectionContent'])->name('update-section-content');
    Route::put('/{school}', [SchoolController::class, 'update'])->name('update');
    Route::delete('/{school}', [SchoolController::class, 'destroy'])->name('destroy');
    Route::post('/upload-logo', [SchoolController::class, 'uploadLogo'])->name('upload-logo');
    Route::post('/update-order', [SchoolController::class, 'updateOrder'])->name('update-order');
});
