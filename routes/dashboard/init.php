<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;

// Rutas del Dashboard
Route::prefix('dashboard')->name('dashboard.')->middleware(['auth'])->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('index');
    Route::get('/data', [DashboardController::class, 'getData'])->name('data');
    Route::post('/export', [DashboardController::class, 'exportReport'])->name('export');
});
