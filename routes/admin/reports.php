<?php

use App\Http\Controllers\Admin\ReportController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    // Reporte principal
    Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
    
    // Reporte de pagos diarios
    Route::get('/reports/daily-payments', [ReportController::class, 'dailyPayments'])->name('reports.daily-payments');
    
    // Reporte consolidado de pagos
    Route::get('/reports/consolidated-payments', [ReportController::class, 'consolidatedPayments'])->name('reports.consolidated-payments');
    
    // Cronograma de cuotas
    Route::get('/reports/installment-schedule', [ReportController::class, 'installmentSchedule'])->name('reports.installment-schedule');
    
    // Gráfico de ingresos
    Route::get('/reports/revenue-chart', [ReportController::class, 'revenueChart'])->name('reports.revenue-chart');
    
    // Exportar reportes
    Route::get('/reports/export/daily-payments', [ReportController::class, 'exportDailyPayments'])->name('reports.export.daily-payments');
    Route::get('/reports/export/consolidated-payments', [ReportController::class, 'exportConsolidatedPayments'])->name('reports.export.consolidated-payments');
});
