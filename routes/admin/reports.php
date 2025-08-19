<?php

use App\Http\Controllers\Admin\ReportController;
use Illuminate\Support\Facades\Route;

// Reporte principal
Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');

// Reportes generales (que estaban en init.php)
Route::get('/reports/export', [ReportController::class, 'export'])->name('reports.export');
Route::get('/reports/sales-chart', [ReportController::class, 'salesChart'])->name('reports.sales-chart');

// Estado de Cuenta Parcial
Route::get('/reports/partial-account', [ReportController::class, 'partialAccount'])->name('reports.partial-account');

// Cronograma de Recuperación de Cuotas
Route::get('/reports/payment-schedule', [ReportController::class, 'paymentSchedule'])->name('reports.payment-schedule');

// Reporte de pagos diarios
Route::get('/reports/daily-payments', [ReportController::class, 'dailyPayments'])->name('reports.daily-payments');

// Reporte consolidado de pagos
Route::get('/reports/consolidated-payments', [ReportController::class, 'consolidatedPayments'])->name('reports.consolidated-payments');

// Cronograma de cuotas (mantener por compatibilidad)
Route::get('/reports/installment-schedule', [ReportController::class, 'installmentSchedule'])->name('reports.installment-schedule');

// Gráfico de ingresos
Route::get('/reports/revenue-chart', [ReportController::class, 'revenueChart'])->name('reports.revenue-chart');

// Exportar reportes específicos
Route::get('/reports/export/daily-payments', [ReportController::class, 'exportDailyPayments'])->name('reports.export.daily-payments');
Route::get('/reports/export/consolidated-payments', [ReportController::class, 'exportConsolidatedPayments'])->name('reports.export.consolidated-payments');
Route::get('/reports/export/partial-account', [ReportController::class, 'exportPartialAccount'])->name('reports.export.partial-account');
Route::get('/reports/export/payment-schedule', [ReportController::class, 'exportPaymentSchedule'])->name('reports.export.payment-schedule');
