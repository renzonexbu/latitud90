<?php

use App\Http\Controllers\Client\ProgramController;
use Illuminate\Support\Facades\Route;

// Rutas de programas
Route::get('/programs', [ProgramController::class, 'index'])->name('ecommerce.programs');
Route::get('/programs/{programId}', [ProgramController::class, 'show'])->name('ecommerce.program-detail');

