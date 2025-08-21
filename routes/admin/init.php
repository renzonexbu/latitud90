<?php

use App\Http\Controllers\Admin\ProgramController;
use App\Http\Controllers\Admin\AdminController;
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



// Rutas de administración
Route::middleware(['auth', 'verified'])->prefix('admin')->name('admin.')->group(function () {

    // Dashboard de administración
    Route::get('/', [AdminController::class, 'dashboard'])->name('dashboard');

    // Gestión de programas
    include __DIR__ . '/program.php';

    // Gestión de cursos
    include __DIR__ . '/courses.php';

    // Gestión de participantes
    include __DIR__ . '/participants.php';

    // Gestión de pagos
    Route::resource('payments', PaymentController::class);
    Route::patch('payments/{payment}/confirm', [PaymentController::class, 'confirm'])->name('payments.confirm');
    Route::patch('payments/{payment}/cancel', [PaymentController::class, 'cancel'])->name('payments.cancel');
    Route::post('payments/{payment}/refund', [PaymentController::class, 'refund'])->name('payments.refund');
    Route::get('payments/export', [PaymentController::class, 'export'])->name('payments.export');
    Route::get('payments/pending-report', [PaymentController::class, 'pendingReport'])->name('payments.pending-report');
    Route::get('payments/revenue-report', [PaymentController::class, 'revenueReport'])->name('payments.revenue-report');
    Route::post('payments/participant-status', [PaymentController::class, 'getParticipantPaymentStatus'])->name('payments.participant-status');

    // Gestión de reportes
    include __DIR__ . '/reports.php';

    Route::get('reports/programs', [ProgramController::class, 'reportsIndex'])->name('reports.programs');
    // Route::get('reports/passengers', [PassengerController::class, 'reportsIndex'])->name('reports.passengers');
    Route::get('reports/payments', [PaymentController::class, 'reportsIndex'])->name('reports.payments');
    Route::get('reports/financial', [PaymentController::class, 'financialReport'])->name('reports.financial');

    // Analytics del Ecommerce
    Route::prefix('analytics')->name('analytics.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Admin\AnalyticsController::class, 'index'])->name('index');
        Route::get('/funnel', [\App\Http\Controllers\Admin\AnalyticsController::class, 'funnel'])->name('funnel');
        Route::get('/export', [\App\Http\Controllers\Admin\AnalyticsController::class, 'export'])->name('export');
    });

    // Logs Administrativos
    Route::prefix('logs')->name('logs.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Admin\AdminLogsController::class, 'index'])->name('index');
        Route::get('/{log}', [\App\Http\Controllers\Admin\AdminLogsController::class, 'show'])->name('show');
        Route::get('/export', [\App\Http\Controllers\Admin\AdminLogsController::class, 'export'])->name('export');
    });

    // Gestión de perfil
    Route::get('profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});
