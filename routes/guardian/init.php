<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Client\GuardianPermissionController;

// Validación de permisos de Guardian (necesita sesión)
Route::post('/guardian/validate-payment-permission', [GuardianPermissionController::class, 'validatePaymentPermission']);
