<?php

use App\Http\Controllers\Admin\Courses\CourseController;
use App\Http\Controllers\Admin\Courses\PaymentOptionsProgramController;
use Illuminate\Support\Facades\Route;

// Courses Management
Route::prefix('courses')->name('courses.')->group(function () {
    // Payment Options by Program - debe ir antes de las rutas con parámetros
    Route::get('/payment-options-programs', [PaymentOptionsProgramController::class, 'index'])
        ->name('payment-options-programs');
    Route::get('/payment-options-programs/export', [PaymentOptionsProgramController::class, 'export'])
        ->name('payment-options-programs.export');
    // Participant management routes (must be before wildcard routes)
    Route::get('/participants', [CourseController::class, 'getParticipants'])->name('participants.get');
    Route::delete('/participants/remove', [CourseController::class, 'removeParticipant'])->name('participants.remove');

    // Program deletion routes (must be before wildcard routes)
    Route::get('/program/can-delete', [CourseController::class, 'canDeleteProgram'])->name('program.can-delete');
    Route::delete('/program/delete', [CourseController::class, 'deleteProgram'])->name('program.delete');

    // Resource routes for courses
    Route::get('/', [CourseController::class, 'index'])->name('index');
    Route::get('/create', [CourseController::class, 'create'])->name('create');
    Route::post('/', [CourseController::class, 'store'])->name('store');
    Route::post('/participants/preview-import-new', [CourseController::class, 'previewParticipantsImportNew'])->name('participants.preview-import-new');
    Route::get('/{course}', [CourseController::class, 'show'])->name('show');
    Route::get('/{course}/edit', [CourseController::class, 'edit'])->name('edit');
    Route::post('/{course}/participants/preview-import', [CourseController::class, 'previewParticipantsImport'])->name('participants.preview-import');
    Route::put('/{course}', [CourseController::class, 'update'])->name('update');
    Route::delete('/{course}', [CourseController::class, 'destroy'])->name('destroy');

    // Additional course actions
    Route::patch('/{course}/toggle-status', [CourseController::class, 'toggleStatus'])
        ->name('toggle-status');

    // Debug/verification endpoint
    Route::get('/{course}/verify-payment-breakdown', [CourseController::class, 'verifyPaymentBreakdown'])
        ->name('verify-payment-breakdown');

    // Student management routes
    Route::prefix('{course}/students')->name('students.')->group(function () {
        Route::get('/', [CourseController::class, 'students'])->name('index');
        Route::get('/export', [CourseController::class, 'export'])->name('export');
    });
});

