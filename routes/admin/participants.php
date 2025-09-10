<?php

use App\Http\Controllers\Admin\Participants\ParticipantsController;
use App\Http\Controllers\Admin\Participants\ParticipantMedicalController;
use App\Http\Controllers\Admin\Participants\ParticipantEmergencyContactsController;
use App\Http\Controllers\Admin\Participants\ParticipantStatusController;
use App\Http\Controllers\Admin\Participants\ParticipantPaymentsController;
use Illuminate\Support\Facades\Route;

// Main participants CRUD routes
Route::resource('participants', ParticipantsController::class)->except(['destroy']);

// Status management routes
Route::delete('participants/{participant}', [ParticipantStatusController::class, 'destroy'])->name('participants.destroy');
Route::get('participants/inactive', [ParticipantStatusController::class, 'inactive'])->name('participants.inactive');
Route::post('participants/{participant}/toggle-status', [ParticipantStatusController::class, 'toggleStatus'])->name('participants.toggle-status');
Route::post('participants/bulk-action', [ParticipantStatusController::class, 'bulkAction'])->name('participants.bulk-action');

// Payments and exports routes
Route::get('participants/{participant}/payments', [ParticipantPaymentsController::class, 'show'])->name('participants.payments');
Route::get('participants/export', [ParticipantPaymentsController::class, 'export'])->name('participants.export');

// Medical conditions route
Route::put('participants/{participant}/medical-conditions', [ParticipantMedicalController::class, 'update'])->name('participants.update-medical-conditions');

// Emergency contacts routes
Route::put('participants/{participant}/emergency-contacts', [ParticipantEmergencyContactsController::class, 'store'])->name('participants.update-emergency-contacts');
Route::put('participants/{participant}/emergency-contact', [ParticipantEmergencyContactsController::class, 'update'])->name('participants.update-emergency-contact');
Route::delete('participants/{participant}/emergency-contact', [ParticipantEmergencyContactsController::class, 'destroy'])->name('participants.delete-emergency-contact');
