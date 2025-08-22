<?php

use App\Http\Controllers\Admin\ParticipantsController;
use Illuminate\Support\Facades\Route;

// Participants management routes
Route::resource('participants', ParticipantsController::class);

// Additional participants routes
Route::get('participants/inactive', [ParticipantsController::class, 'inactive'])->name('participants.inactive');
Route::post('participants/{participant}/toggle-status', [ParticipantsController::class, 'toggleStatus'])->name('participants.toggle-status');
Route::post('participants/bulk-action', [ParticipantsController::class, 'bulkAction'])->name('participants.bulk-action');
Route::get('participants/{participant}/payments', [ParticipantsController::class, 'payments'])->name('participants.payments');
Route::get('participants/export', [ParticipantsController::class, 'export'])->name('participants.export');

// Medical conditions route
Route::put('participants/{participant}/medical-conditions', [ParticipantsController::class, 'updateMedicalConditions'])->name('participants.update-medical-conditions');

// Emergency contacts routes
Route::put('participants/{participant}/emergency-contacts', [ParticipantsController::class, 'updateEmergencyContacts'])->name('participants.update-emergency-contacts');
Route::put('participants/{participant}/emergency-contact', [ParticipantsController::class, 'updateEmergencyContact'])->name('participants.update-emergency-contact');
Route::delete('participants/{participant}/emergency-contact', [ParticipantsController::class, 'deleteEmergencyContact'])->name('participants.delete-emergency-contact');
