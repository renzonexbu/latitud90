<?php

use App\Http\Controllers\Admin\ProgramController;
use Illuminate\Support\Facades\Route;

// Gestión de programas
Route::resource('programs', ProgramController::class);
Route::patch('programs/{program}/toggle-status', [ProgramController::class, 'toggleStatus'])->name('programs.toggle-status');
Route::post('programs/bulk-action', [ProgramController::class, 'bulkAction'])->name('programs.bulk-action');
Route::post('programs/bulk-price-update', [ProgramController::class, 'bulkPriceUpdate'])->name('programs.bulk-price-update');
// Route::get('programs/{program}/passengers', [ProgramController::class, 'passengers'])->name('programs.passengers');
Route::get('programs/{program}/payments', [ProgramController::class, 'payments'])->name('programs.payments');
Route::get('programs/{program}/export', [ProgramController::class, 'export'])->name('programs.export');
Route::get('programs/{program}/files', [ProgramController::class, 'files'])->name('programs.files');
