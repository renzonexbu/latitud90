<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Client\ProcessPaymentController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// Payment Gateway Notification Routes (sin CORS)
Route::post('/payment/notification/transbank', [ProcessPaymentController::class, 'transbankNotification']);
Route::post('/payment/notification/khipu', [ProcessPaymentController::class, 'khipuNotification']);