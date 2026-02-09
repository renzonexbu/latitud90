<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\GuardianUserController;

Route::prefix('guardian-users')->name('guardian-users.')->group(function () {
    Route::get('/', [GuardianUserController::class, 'index'])->name('index');
    Route::post('/{guardianUser}/toggle-status', [GuardianUserController::class, 'toggleStatus'])->name('toggle-status');
    Route::post('/{guardianUser}/resend-password-reset', [GuardianUserController::class, 'resendPasswordReset'])->name('resend-password-reset');
    Route::post('/{guardianUser}/verify-email', [GuardianUserController::class, 'verifyEmail'])->name('verify-email');
});
