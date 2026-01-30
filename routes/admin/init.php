<?php

use App\Http\Controllers\Admin\Programs\ProgramController;
use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Admin\PaymentController;
use Illuminate\Support\Facades\Route;

// Dashboard principal (redirige al admin)
Route::get('/dashboard', function () {
    return redirect()->route('admin.dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');



// Rutas de administración
Route::middleware(['auth', 'verified', 'restrict.executive'])->prefix('admin')->name('admin.')->group(function () {

    // Dashboard de administración
    Route::get('/', [AdminController::class, 'dashboard'])->name('dashboard');

    // Gestión de programas
    include __DIR__ . '/program.php';

    // Gestión de cursos
    include __DIR__ . '/courses.php';

    // Gestión de participantes
    include __DIR__ . '/participants.php';

    // Gestión de cuotas
    include __DIR__ . '/installments.php';

    // Gestión de pagos
    include __DIR__ . '/payments.php';

    // Gestión de suscripciones
    include __DIR__ . '/subscriptions.php';

    // Gestión de reportes
    include __DIR__ . '/reports.php';

    // Gestión de usuarios
    include __DIR__ . '/users.php';

    // Gestión del mantenedor (solo super admin)
    include __DIR__ . '/maintainer.php';

    // Gestión de plantillas de documentos
    include __DIR__ . '/document-templates.php';

    // Gestión de contenido del sitio (tipo ACF)
    include __DIR__ . '/site-content.php';

    // Gestión de colegios
    include __DIR__ . '/schools.php';

    // Gestión de instituciones
    include __DIR__ . '/institutions.php';

    // Gestión de ejecutivos comerciales
    include __DIR__ . '/executives.php';

    // Gestión de documentos procedimientos
    Route::resource('procedure-documents', \App\Http\Controllers\Admin\ProcedureDocumentsController::class);
    Route::get('procedure-documents/{procedureDocument}/download', [\App\Http\Controllers\Admin\ProcedureDocumentsController::class, 'download'])->name('procedure-documents.download');

    Route::get('reports/programs', [ProgramController::class, 'reportsIndex'])->name('reports.programs');
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

    // Monitor BSale - Gestión de cola de boletas
    Route::prefix('bsale-monitor')->name('bsale-monitor.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Admin\BsaleMonitorController::class, 'index'])->name('index');
        Route::get('/list', [\App\Http\Controllers\Admin\BsaleMonitorController::class, 'list'])->name('list');
        Route::get('/stats', [\App\Http\Controllers\Admin\BsaleMonitorController::class, 'stats'])->name('stats');
        Route::get('/{bsaleRequest}', [\App\Http\Controllers\Admin\BsaleMonitorController::class, 'show'])->name('show');
        Route::post('/{bsaleRequest}/retry', [\App\Http\Controllers\Admin\BsaleMonitorController::class, 'retry'])->name('retry');
        Route::post('/{bsaleRequest}/force-reprocess', [\App\Http\Controllers\Admin\BsaleMonitorController::class, 'forceReprocess'])->name('force-reprocess');
        Route::post('/{bsaleRequest}/cancel', [\App\Http\Controllers\Admin\BsaleMonitorController::class, 'cancel'])->name('cancel');
        Route::post('/process-queue', [\App\Http\Controllers\Admin\BsaleMonitorController::class, 'processQueue'])->name('process-queue');
    });

    // Gestión de perfil
    include __DIR__ . '/profile.php';
});
