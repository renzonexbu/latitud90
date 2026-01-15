<?php

use App\Http\Controllers\Admin\ExecutiveController;
use App\Models\SalesExecutive;
use Illuminate\Support\Facades\Route;

Route::prefix('executives')->name('executives.')->group(function () {
    // Bind 'executive' parameter to SalesExecutive model
    Route::bind('executive', function ($value) {
        return SalesExecutive::findOrFail($value);
    });

    Route::get('/', [ExecutiveController::class, 'index'])->name('index');
    Route::get('/create', [ExecutiveController::class, 'create'])->name('create');
    Route::post('/', [ExecutiveController::class, 'store'])->name('store');
    Route::get('/{executive}/edit', [ExecutiveController::class, 'edit'])->name('edit');
    Route::put('/{executive}', [ExecutiveController::class, 'update'])->name('update');
    Route::delete('/{executive}', [ExecutiveController::class, 'destroy'])->name('destroy');
});
