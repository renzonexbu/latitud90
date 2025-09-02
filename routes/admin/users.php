<?php

use App\Http\Controllers\Admin\UsersController;
use Illuminate\Support\Facades\Route;

// Gestión de usuarios
Route::resource('users', UsersController::class);
Route::patch('users/{user}/toggle-status', [UsersController::class, 'toggleStatus'])->name('users.toggle-status');
