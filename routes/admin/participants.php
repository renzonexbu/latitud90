<?php

use App\Http\Controllers\Admin\ParticipantsController;
use Illuminate\Support\Facades\Route;

// Participants management routes
Route::resource('participants', ParticipantsController::class);

// Additional participants routes
Route::post('participants/{participant}/toggle-status', [ParticipantsController::class, 'toggleStatus'])->name('participants.toggle-status');
Route::post('participants/bulk-action', [ParticipantsController::class, 'bulkAction'])->name('participants.bulk-action');
Route::get('participants/{participant}/payments', [ParticipantsController::class, 'payments'])->name('participants.payments');
Route::get('participants/export', [ParticipantsController::class, 'export'])->name('participants.export');
