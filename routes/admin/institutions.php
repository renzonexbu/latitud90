<?php

use App\Http\Controllers\Admin\InstitutionController;
use Illuminate\Support\Facades\Route;

Route::prefix('institutions')->name('institutions.')->group(function () {
    Route::get('/', [InstitutionController::class, 'index'])->name('index');
    Route::get('/create', [InstitutionController::class, 'create'])->name('create');
    Route::post('/', [InstitutionController::class, 'store'])->name('store');
    Route::get('/{institution}/edit', [InstitutionController::class, 'edit'])->name('edit');
    Route::put('/{institution}', [InstitutionController::class, 'update'])->name('update');
    Route::delete('/{institution}', [InstitutionController::class, 'destroy'])->name('destroy');
});
