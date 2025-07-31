<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\EcommerceController;
use App\Http\Controllers\PaymentGatewayController;
use App\Http\Controllers\Admin\ProgramController;
use App\Http\Controllers\Admin\PassengerController;
use App\Http\Controllers\Admin\PaymentController;
use App\Http\Controllers\Admin\ReportController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

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

// Rutas públicas de ecommerce
Route::get('/', [EcommerceController::class, 'index'])->name('ecommerce.index');
Route::get('/ecommerce', [EcommerceController::class, 'index'])->name('ecommerce.home');
Route::get('/terminos-y-condiciones', [EcommerceController::class, 'termsAndConditions'])->name('ecommerce.terms-and-conditions');
Route::get('/programa/{program}', [EcommerceController::class, 'show'])->name('ecommerce.show');
Route::get('/reserva/{program}', [EcommerceController::class, 'reservation'])->name('ecommerce.reservation');
Route::post('/reserva/{program}', [EcommerceController::class, 'storeReservation'])->name('ecommerce.store-reservation');
Route::get('/pago/{passenger}', [EcommerceController::class, 'payment'])->name('ecommerce.payment');
Route::post('/procesar-pago/{payment}', [EcommerceController::class, 'processPayment'])->name('ecommerce.process-payment');
Route::get('/confirmacion/{passenger}', [EcommerceController::class, 'confirmation'])->name('ecommerce.confirmation');
Route::get('/buscar-reserva', [EcommerceController::class, 'findReservation'])->name('ecommerce.find-reservation');
Route::post('/buscar-reserva', [EcommerceController::class, 'searchReservation'])->name('ecommerce.search-reservation');
Route::get('/buscar-viajes', [EcommerceController::class, 'searchByRut'])->name('ecommerce.search-by-rut');

// Rutas del nuevo flujo de pago por pasos
Route::get('/payment/method', [EcommerceController::class, 'paymentMethod'])->name('payment.method');
Route::post('/payment/setup', [EcommerceController::class, 'setupPayment'])->name('payment.setup');
Route::get('/payment/gateway/{passenger}', [EcommerceController::class, 'paymentGateway'])->name('payment.gateway');

// Rutas de pasarelas de pago
Route::get('/payment/return/{payment}', [PaymentGatewayController::class, 'return'])->name('payment.return');
Route::post('/payment/webhook/transbank', [PaymentGatewayController::class, 'transbankWebhook'])->name('payment.webhook.transbank');
Route::post('/payment/webhook/khipu', [PaymentGatewayController::class, 'khipuWebhook'])->name('payment.webhook.khipu');
Route::get('/payment/link/{paymentLink}', [PaymentGatewayController::class, 'paymentLink'])->name('payment.link');
Route::post('/payment/link/{paymentLink}/process', [PaymentGatewayController::class, 'processPaymentLink'])->name('payment.link.process');

// Dashboard principal (redirige al admin)
Route::get('/dashboard', function () {
    return redirect()->route('admin.dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

// Rutas de perfil de usuario
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// Rutas de administración
Route::middleware(['auth', 'verified'])->prefix('admin')->name('admin.')->group(function () {

    // Dashboard de administración
    Route::get('/', function () {
        return Inertia::render('Admin/Dashboard');
    })->name('dashboard');

    // Gestión de programas
    Route::resource('programs', ProgramController::class);
    Route::patch('programs/{program}/toggle-status', [ProgramController::class, 'toggleStatus'])->name('programs.toggle-status');
    Route::post('programs/bulk-action', [ProgramController::class, 'bulkAction'])->name('programs.bulk-action');
    Route::post('programs/bulk-price-update', [ProgramController::class, 'bulkPriceUpdate'])->name('programs.bulk-price-update');
    Route::get('programs/{program}/passengers', [ProgramController::class, 'passengers'])->name('programs.passengers');
    Route::get('programs/{program}/payments', [ProgramController::class, 'payments'])->name('programs.payments');
    Route::get('programs/{program}/export', [ProgramController::class, 'export'])->name('programs.export');

    // Gestión de pasajeros
    Route::resource('passengers', PassengerController::class);
    Route::patch('passengers/{passenger}/update-status', [PassengerController::class, 'updateStatus'])->name('passengers.update-status');
    Route::patch('passengers/{passenger}/update-price', [PassengerController::class, 'updatePrice'])->name('passengers.update-price');
    Route::get('passengers/{passenger}/payments', [PassengerController::class, 'payments'])->name('passengers.payments');
    Route::get('passengers/{passenger}/contracts', [PassengerController::class, 'contracts'])->name('passengers.contracts');
    Route::post('passengers/{passenger}/send-payment-link', [PassengerController::class, 'sendPaymentLink'])->name('passengers.send-payment-link');
    Route::get('passengers/export', [PassengerController::class, 'export'])->name('passengers.export');

    // Gestión de pagos
    Route::resource('payments', PaymentController::class)->only(['index', 'show', 'update']);
    Route::patch('payments/{payment}/confirm', [PaymentController::class, 'confirm'])->name('payments.confirm');
    Route::patch('payments/{payment}/cancel', [PaymentController::class, 'cancel'])->name('payments.cancel');
    Route::post('payments/{payment}/refund', [PaymentController::class, 'refund'])->name('payments.refund');
    Route::get('payments/export', [PaymentController::class, 'export'])->name('payments.export');
    Route::get('payments/pending-report', [PaymentController::class, 'pendingReport'])->name('payments.pending-report');
    Route::get('payments/revenue-report', [PaymentController::class, 'revenueReport'])->name('payments.revenue-report');

    // Reportes generales
    Route::get('reports', [ReportController::class, 'index'])->name('reports.index');
    Route::get('reports/export', [ReportController::class, 'export'])->name('reports.export');
    Route::get('reports/sales-chart', [ReportController::class, 'salesChart'])->name('reports.sales-chart');

    Route::get('reports/programs', [ProgramController::class, 'reportsIndex'])->name('reports.programs');
    Route::get('reports/passengers', [PassengerController::class, 'reportsIndex'])->name('reports.passengers');
    Route::get('reports/payments', [PaymentController::class, 'reportsIndex'])->name('reports.payments');
    Route::get('reports/financial', [PaymentController::class, 'financialReport'])->name('reports.financial');
});

require __DIR__.'/auth.php';
