<?php

use App\Http\Controllers\Admin\CoursesController;
use App\Http\Controllers\Admin\InstitutionsController;
use Illuminate\Support\Facades\Route;

// Gestión de cursos
Route::resource('courses', CoursesController::class);
Route::patch('courses/{course}/toggle-status', [CoursesController::class, 'toggleStatus'])->name('courses.toggle-status');
Route::post('courses/bulk-action', [CoursesController::class, 'bulkAction'])->name('courses.bulk-action');
Route::get('courses/{course}/students', [CoursesController::class, 'students'])->name('courses.students');
Route::get('courses/{course}/export', [CoursesController::class, 'export'])->name('courses.export');

// Gestión de instituciones (dentro del contexto de cursos)
Route::get('institutions', [InstitutionsController::class, 'index'])->name('institutions.index');
Route::get('institutions/create', [InstitutionsController::class, 'create'])->name('institutions.create');
Route::post('institutions', [InstitutionsController::class, 'store'])->name('institutions.store');
