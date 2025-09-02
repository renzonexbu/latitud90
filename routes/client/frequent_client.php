<?php

use App\Http\Controllers\Client\FrequentClientController;
use Illuminate\Support\Facades\Route;

// Rutas para clientes frecuentes
Route::prefix('frequent-clients')->group(function () {
    Route::post('/find-by-document', [FrequentClientController::class, 'findByDocument']);
    Route::post('/store', [FrequentClientController::class, 'store']);
    Route::get('/stats', [FrequentClientController::class, 'getStats']);
});