<?php

use App\Http\Controllers\Admin\Participants\ParticipantsController;
use App\Http\Controllers\Admin\Participants\ParticipantMedicalController;
use App\Http\Controllers\Admin\Participants\ParticipantEmergencyContactsController;
use App\Http\Controllers\Admin\Participants\ParticipantStatusController;
use App\Http\Controllers\Admin\Participants\ParticipantPaymentsController;
use App\Http\Controllers\Admin\Participants\ParticipantProgramController;
use Illuminate\Support\Facades\Route;

// Main participants CRUD routes
Route::resource('participants', ParticipantsController::class)->except(['destroy']);

// Status management routes
Route::delete('participants/{participant}', [ParticipantStatusController::class, 'destroy'])->name('participants.destroy');
Route::get('participants/inactive', [ParticipantStatusController::class, 'inactive'])->name('participants.inactive');
Route::post('participants/{participant}/toggle-status', [ParticipantStatusController::class, 'toggleStatus'])->name('participants.toggle-status');
Route::post('participants/bulk-action', [ParticipantStatusController::class, 'bulkAction'])->name('participants.bulk-action');

// Payments routes
Route::get('participants/{participant}/payments', [ParticipantPaymentsController::class, 'show'])->name('participants.payments');

// Export routes
Route::get('participants/export/excel', [ParticipantsController::class, 'exportExcel'])->name('participants.export.excel');
Route::get('participants/export/csv', [ParticipantsController::class, 'exportCsv'])->name('participants.export.csv');

// Medical conditions route
Route::put('participants/{participant}/medical-conditions', [ParticipantMedicalController::class, 'update'])->name('participants.update-medical-conditions');

// Emergency contacts routes
Route::put('participants/{participant}/emergency-contacts', [ParticipantEmergencyContactsController::class, 'store'])->name('participants.update-emergency-contacts');
Route::put('participants/{participant}/emergency-contact', [ParticipantEmergencyContactsController::class, 'update'])->name('participants.update-emergency-contact');
Route::delete('participants/{participant}/emergency-contact', [ParticipantEmergencyContactsController::class, 'destroy'])->name('participants.delete-emergency-contact');

// Program status toggle route
Route::post('participants/{participant}/programs/{program}/toggle-status', [ParticipantProgramController::class, 'toggleProgramStatus'])->name('participants.toggle-program-status');

// Program active status toggle route (dar de baja/reactivar por programa individual)
Route::post('participants/{participant}/programs/{program}/toggle-active', [ParticipantProgramController::class, 'toggleProgramActiveStatus'])->name('participants.toggle-program-active');
