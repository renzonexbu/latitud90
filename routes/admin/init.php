<?php

use App\Http\Controllers\Admin\ProgramController;
use App\Http\Controllers\Admin\PassengerController;
use App\Http\Controllers\Admin\PaymentController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\ProfileController;
use Inertia\Inertia;
use Illuminate\Support\Facades\Route;

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
    include __DIR__ . '/program.php';

    // Gestión de cursos
    include __DIR__ . '/courses.php';

    // Gestión de participantes
    include __DIR__ . '/participants.php';
    
    include __DIR__ . '/payments.php';

    // Gestión de pasajeros - Comentado temporalmente
    // Route::resource('passengers', PassengerController::class);
    // Route::patch('passengers/{passenger}/update-status', [PassengerController::class, 'updateStatus'])->name('passengers.update-status');
    // Route::patch('passengers/{passenger}/update-price', [PassengerController::class, 'updatePrice'])->name('passengers.update-price');
    // Route::get('passengers/{passenger}/payments', [PassengerController::class, 'payments'])->name('passengers.payments');
    // Route::get('passengers/{passenger}/contracts', [PassengerController::class, 'contracts'])->name('passengers.contracts');
    // Route::post('passengers/{passenger}/send-payment-link', [PassengerController::class, 'sendPaymentLink'])->name('passengers.send-payment-link');
    // Route::get('passengers/export', [PassengerController::class, 'export'])->name('passengers.export');

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
    // Route::get('reports/passengers', [PassengerController::class, 'reportsIndex'])->name('reports.passengers');
    Route::get('reports/payments', [PaymentController::class, 'reportsIndex'])->name('reports.payments');
    Route::get('reports/financial', [PaymentController::class, 'financialReport'])->name('reports.financial');
});
