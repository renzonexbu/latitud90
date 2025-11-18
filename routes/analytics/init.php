<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AnalyticsController;

// Rutas de Analytics
Route::prefix('analytics')->name('analytics.')->middleware(['auth'])->group(function () {
    Route::get('/funnel', [AnalyticsController::class, 'getFunnelAnalysis'])->name('funnel');
    Route::post('/program-list-view', [AnalyticsController::class, 'recordProgramListView'])->name('program-list-view');
    Route::post('/program-selection', [AnalyticsController::class, 'recordProgramSelection'])->name('program-selection');
    Route::post('/payment-selection', [AnalyticsController::class, 'recordPaymentSelection'])->name('payment-selection');
    Route::post('/program-detail-view', [AnalyticsController::class, 'recordProgramDetailView'])->name('program-detail-view');
    Route::post('/payment-details-view', [AnalyticsController::class, 'recordPaymentDetailsView'])->name('payment-details-view');
    Route::post('/confirmation-view', [AnalyticsController::class, 'recordConfirmationView'])->name('confirmation-view');
    Route::post('/payment-initiated', [AnalyticsController::class, 'recordPaymentInitiated'])->name('payment-initiated');
    Route::post('/payment-completed', [AnalyticsController::class, 'recordPaymentCompleted'])->name('payment-completed');
    Route::post('/payment-failed', [AnalyticsController::class, 'recordPaymentFailed'])->name('payment-failed');
});
