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
Route::get('/reports/search-participants', [ReportController::class, 'searchParticipants'])->name('reports.search-participants');

// Cronograma de Recuperación de Cuotas
Route::get('/reports/payment-schedule', [ReportController::class, 'paymentSchedule'])->name('reports.payment-schedule');
Route::get('/reports/payment-schedule/details', [ReportController::class, 'paymentScheduleDetails'])->name('reports.payment-schedule.details');

// Participantes sin pagos iniciados
Route::get('/reports/no-payment', [ReportController::class, 'noPayment'])->name('reports.no-payment');
Route::get('/reports/export/no-payment', [ReportController::class, 'exportNoPayment'])->name('reports.export.no-payment');

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
Route::get('/reports/softland', [ReportController::class, 'softland'])->name('reports.softland');
Route::get('/reports/export/softland-template', [ReportController::class, 'exportSoftlandTemplate'])->name('reports.export.softland-template');
Route::get('/reports/export/softland-auxiliares', [ReportController::class, 'exportSoftlandAuxiliares'])->name('reports.export.softland-auxiliares');
Route::get('/reports/preview/softland-auxiliares', [ReportController::class, 'previewSoftlandAuxiliares'])->name('reports.preview.softland-auxiliares');
Route::get('/reports/export/softland-zip', [ReportController::class, 'exportSoftlandZip'])->name('reports.export.softland-zip');

// Generated Documents (Comprobantes, Contratos, BSale)
Route::get('/reports/bsale-documents', [ReportController::class, 'bsaleDocuments'])->name('reports.bsale-documents');
Route::get('/reports/bsale-documents/list', [ReportController::class, 'bsaleDocumentsList'])->name('reports.bsale-documents.list');
Route::get('/reports/bsale-documents/download/{documentId}', [ReportController::class, 'downloadBsaleDocument'])->name('reports.bsale-documents.download');
Route::post('/reports/bsale-documents/resend/{documentId}', [ReportController::class, 'resendDocument'])->name('reports.bsale-documents.resend');
Route::get('/reports/bsale-documents/download-zip', [ReportController::class, 'downloadBsaleDocumentsZip'])->name('reports.bsale-documents.download-zip');

// Document Downloads for Partial Account Report
Route::get('/reports/download-payment-receipt/{paymentId}', [ReportController::class, 'downloadPaymentReceipt'])->name('reports.download-payment-receipt');
Route::get('/reports/download-reservation-contract/{participantId}/{programId}', [ReportController::class, 'downloadReservationContract'])->name('reports.download-reservation-contract');
Route::get('/reports/download-all-payment-receipts/{participantId}/{programId}', [ReportController::class, 'downloadAllPaymentReceipts'])->name('reports.download-all-payment-receipts');
Route::get('/debug-zip/{participantId}/{programId}', [ReportController::class, 'debugZipCreation'])->name('admin.reports.debug-zip');
Route::get('/reports/download-all-bsale-documents/{participantId}/{programId}', [ReportController::class, 'downloadAllBsaleDocuments'])->name('reports.download-all-bsale-documents');

// Reporte de aceptación de términos y condiciones
Route::get('/reports/terms-acceptance', [ReportController::class, 'termsAcceptance'])->name('reports.terms-acceptance');
Route::get('/reports/export/terms-acceptance', [ReportController::class, 'exportTermsAcceptance'])->name('reports.export.terms-acceptance');
Route::get('/reports/download/terms-acceptance-pdf/{orderDetailId}', [ReportController::class, 'downloadTermsAcceptancePdf'])->name('reports.download.terms-acceptance-pdf');
Route::get('/reports/download/terms-acceptance-pdfs-zip', [ReportController::class, 'downloadTermsAcceptancePdfsZip'])->name('reports.download.terms-acceptance-pdfs-zip');

// Reporte de cuotas pagadas
Route::get('/reports/paid-installments', [ReportController::class, 'paidInstallments'])->name('reports.paid-installments');
Route::get('/reports/export/paid-installments', [ReportController::class, 'exportPaidInstallments'])->name('reports.export.paid-installments');

// Reporte simple para TI (Número de Negocio y Monto Recaudado)
Route::get('/reports/it-simple', [ReportController::class, 'itSimpleReport'])->name('reports.it-simple');
Route::get('/reports/export/it-simple', [ReportController::class, 'exportItSimpleReport'])->name('reports.export.it-simple');

// Documentos Procedimientos para todos los usuarios
Route::get('/reports/procedure-documents', function () {
    $documents = \App\Models\ProcedureDocument::active()
        ->orderBy('created_at', 'desc')
        ->get();

    return \Inertia\Inertia::render('Admin/Reports/ProcedureDocuments', [
        'documents' => $documents
    ]);
})->name('reports.procedure-documents');

Route::get('/reports/procedure-documents/download/{procedureDocument}', function (\App\Models\ProcedureDocument $procedureDocument) {
    if (!\Storage::disk('public')->exists($procedureDocument->file_path)) {
        return back()->withErrors(['error' => 'El archivo no existe']);
    }

    return \Storage::disk('public')->download(
        $procedureDocument->file_path,
        $procedureDocument->file_name
    );
})->name('reports.procedure-documents.download');

// Ejecutivos/Apoderados: Consolidado de Área Ingresos y Estado de Cuenta Parcial
// Estos reportes son accesibles por ejecutivos comerciales y roles con permiso especial
use App\Http\Controllers\Admin\ExecutivesReportsController;
Route::prefix('/reports/executives')->name('reports.executives.')
    ->middleware('permission:ver_reportes_executives|ver_contacto_pagador')
    ->group(function () {
        // Vista index de reportes de apoderados
        Route::get('/', [ExecutivesReportsController::class, 'index'])->name('index');

        Route::get('/consolidated', [ExecutivesReportsController::class, 'consolidated'])->name('consolidated');
        Route::get('/partial-account', [ExecutivesReportsController::class, 'partialAccount'])->name('partial-account');

        // Exportaciones
        Route::get('/export/consolidated', [ExecutivesReportsController::class, 'exportConsolidated'])->name('export.consolidated');
        Route::get('/export/partial-account', [ExecutivesReportsController::class, 'exportPartialAccount'])->name('export.partial-account');
    });