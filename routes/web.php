
<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Pdf\ContractPreviewController;
use App\Http\Controllers\PaymentReceiptController;

// Preview de contrato en PDF (solo desarrollo)
Route::get('/contract/preview', [ContractPreviewController::class, 'show'])->name('contract.preview');
Route::get('/payment/preview', [ContractPreviewController::class, 'paymentShow'])->name('payment.preview');

// Ruta para descargar comprobante de pago
Route::get('/payment/receipt/download', [PaymentReceiptController::class, 'download'])->name('payment.receipt.download');

// Ruta temporal para preview del email de no payment
Route::get('/test/no-payment-email', [App\Http\Controllers\Controller::class, 'previewNoPaymentEmail'])->name('test.no-payment-email');

// Ruta temporal para preview del email de éxito de pago
Route::get('/test/success-payment-email', [App\Http\Controllers\Controller::class, 'previewSuccessPaymentEmail'])->name('test.success-payment-email');

// Rutas del Dashboard
Route::prefix('dashboard')->name('dashboard.')->middleware(['auth'])->group(function () {
    Route::get('/', [App\Http\Controllers\DashboardController::class, 'index'])->name('index');
    Route::get('/data', [App\Http\Controllers\DashboardController::class, 'getData'])->name('data');
    Route::post('/export', [App\Http\Controllers\DashboardController::class, 'exportReport'])->name('export');
});

// Rutas de Analytics
Route::prefix('analytics')->name('analytics.')->middleware(['auth'])->group(function () {
    Route::get('/funnel', [App\Http\Controllers\AnalyticsController::class, 'getFunnelAnalysis'])->name('funnel');
    Route::post('/program-list-view', [App\Http\Controllers\AnalyticsController::class, 'recordProgramListView'])->name('program-list-view');
    Route::post('/program-selection', [App\Http\Controllers\AnalyticsController::class, 'recordProgramSelection'])->name('program-selection');
    Route::post('/payment-selection', [App\Http\Controllers\AnalyticsController::class, 'recordPaymentSelection'])->name('payment-selection');
    Route::post('/program-detail-view', [App\Http\Controllers\AnalyticsController::class, 'recordProgramDetailView'])->name('program-detail-view');
    Route::post('/payment-details-view', [App\Http\Controllers\AnalyticsController::class, 'recordPaymentDetailsView'])->name('payment-details-view');
    Route::post('/confirmation-view', [App\Http\Controllers\AnalyticsController::class, 'recordConfirmationView'])->name('confirmation-view');
    Route::post('/payment-initiated', [App\Http\Controllers\AnalyticsController::class, 'recordPaymentInitiated'])->name('payment-initiated');
    Route::post('/payment-completed', [App\Http\Controllers\AnalyticsController::class, 'recordPaymentCompleted'])->name('payment-completed');
    Route::post('/payment-failed', [App\Http\Controllers\AnalyticsController::class, 'recordPaymentFailed'])->name('payment-failed');
});

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

include __DIR__ . '/client/init.php';
include __DIR__ . '/admin/init.php';

require __DIR__ . '/auth.php';
