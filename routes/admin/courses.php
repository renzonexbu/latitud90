<?php

use App\Http\Controllers\Admin\CoursesController;
use Illuminate\Support\Facades\Route;

// Gestión de cursos
Route::resource('courses', CoursesController::class);
Route::patch('courses/{course}/toggle-status', [CoursesController::class, 'toggleStatus'])->name('courses.toggle-status');
Route::post('courses/bulk-action', [CoursesController::class, 'bulkAction'])->name('courses.bulk-action');
Route::get('courses/{course}/students', [CoursesController::class, 'students'])->name('courses.students');
Route::get('courses/{course}/export', [CoursesController::class, 'export'])->name('courses.export');
