<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Client\ProcessPaymentController;
use App\Http\Controllers\AnalyticsController;

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

// Analytics Routes
Route::post('/analytics/program-list-view', [AnalyticsController::class, 'recordProgramListView']);
Route::post('/analytics/program-selection', [AnalyticsController::class, 'recordProgramSelection']);
Route::post('/analytics/payment-selection', [AnalyticsController::class, 'recordPaymentSelection']);
Route::post('/analytics/program-detail-view', [AnalyticsController::class, 'recordProgramDetailView']);
Route::post('/analytics/payment-details-view', [AnalyticsController::class, 'recordPaymentDetailsView']);

Route::post('/analytics/confirmation-view', [AnalyticsController::class, 'recordConfirmationView']);
Route::post('/analytics/payment-initiated', [AnalyticsController::class, 'recordPaymentInitiated']);
Route::post('/analytics/payment-completed', [AnalyticsController::class, 'recordPaymentCompleted']);
Route::post('/analytics/payment-failed', [AnalyticsController::class, 'recordPaymentFailed']);
Route::post('/analytics/confirmation-changes', [AnalyticsController::class, 'recordConfirmationChanges']);