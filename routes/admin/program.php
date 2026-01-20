<?php

use App\Http\Controllers\Admin\Programs\ProgramController;   
use App\Http\Controllers\Admin\Programs\SalesExecutivesController;
use Illuminate\Support\Facades\Route;

// Gestión de plantillas (templates)
Route::resource('templates', ProgramController::class)
    ->names('programs')
    ->parameters(['templates' => 'program']);
Route::post('sales-executives', [SalesExecutivesController::class, 'store'])->name('sales-executives.store');
Route::patch('templates/{program}/toggle-status', [ProgramController::class, 'toggleStatus'])->name('programs.toggle-status');
Route::post('templates/bulk-action', [ProgramController::class, 'bulkAction'])->name('programs.bulk-action');
Route::post('templates/bulk-price-update', [ProgramController::class, 'bulkPriceUpdate'])->name('programs.bulk-price-update');

Route::get('templates/{program}/payments', [ProgramController::class, 'payments'])->name('programs.payments');
Route::get('templates/{program}/export', [ProgramController::class, 'export'])->name('programs.export');
Route::get('templates/{program}/files', [ProgramController::class, 'files'])->name('programs.files');
Route::get('templates/{program}/can-delete', [ProgramController::class, 'canDelete'])->name('programs.can-delete');
