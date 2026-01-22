<?php

use App\Http\Controllers\Admin\Courses\CourseController;
use App\Http\Controllers\Admin\InstitutionsController;
use Illuminate\Support\Facades\Route;

// Courses Management
Route::prefix('courses')->name('courses.')->group(function () {
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
    Route::get('/{course}', [CourseController::class, 'show'])->name('show');
    Route::get('/{course}/edit', [CourseController::class, 'edit'])->name('edit');
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

// Institutions Management
Route::prefix('institutions')->name('institutions.')->group(function () {
    Route::get('/', [InstitutionsController::class, 'index'])->name('index');
    Route::get('/create', [InstitutionsController::class, 'create'])->name('create');
    Route::post('/', [InstitutionsController::class, 'store'])->name('store');
});
