<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\MaintainerController;
use App\Http\Controllers\Admin\Maintainer\TermsConditionsController;
use App\Http\Controllers\Admin\Maintainer\FaqController;
use App\Http\Controllers\Admin\Maintainer\PaymentFormController;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::prefix('maintainer')->name('maintainer.')->group(function () {
        // Página principal del mantenedor - Super Admin y Marketing
        Route::get('/', [MaintainerController::class, 'index'])
            ->middleware('role:super_admin,admin_marketing,editor_marketing,visualizador_marketing')
            ->name('index');

        // Marketing Mails - Super Admin y Marketing
        Route::prefix('marketing-mails')->name('marketing.mails.')->middleware('role:super_admin,admin_marketing,editor_marketing,visualizador_marketing')->group(function () {
            Route::get('/', [MaintainerController::class, 'marketingMails'])->name('index');
            Route::post('/sync', [MaintainerController::class, 'syncMarketingMails'])->name('sync');
            Route::post('/{id}/toggle-status', [MaintainerController::class, 'toggleMarketingMailStatus'])->name('toggle-status');
            Route::delete('/{id}', [MaintainerController::class, 'destroyMarketingMail'])->name('destroy');
            Route::post('/export', [MaintainerController::class, 'exportMarketingMails'])->name('export');
        });

        // Newsletter - Super Admin y Marketing
        Route::prefix('newsletter')->name('newsletter.')->middleware('role:super_admin,admin_marketing,editor_marketing,visualizador_marketing')->group(function () {
            Route::get('/', [MaintainerController::class, 'newsletter'])->name('index');
            Route::get('/{id}/edit', [MaintainerController::class, 'editNewsletter'])->name('edit');
            Route::put('/{id}', [MaintainerController::class, 'updateNewsletter'])->name('update');
            Route::delete('/{id}', [MaintainerController::class, 'destroyNewsletter'])->name('destroy');
            Route::post('/export', [MaintainerController::class, 'exportNewsletter'])->name('export');
        });

        // Admin Logs - Solo Super Admin
        Route::prefix('admin-logs')->name('admin-logs.')->middleware('role:super_admin')->group(function () {
            Route::get('/', [MaintainerController::class, 'adminLogs'])->name('index');
            Route::get('/{id}', [MaintainerController::class, 'showAdminLog'])->name('show');
            Route::post('/export', [MaintainerController::class, 'exportAdminLogs'])->name('export');
        });

        // Términos y Condiciones - Solo Super Admin
        Route::prefix('terms-conditions')->name('terms-conditions.')->middleware('role:super_admin')->group(function () {
            Route::get('/', [TermsConditionsController::class, 'index'])->name('index');
            Route::post('/', [TermsConditionsController::class, 'store'])->name('store');
            Route::put('/{id}', [TermsConditionsController::class, 'update'])->name('update');
            Route::delete('/{id}', [TermsConditionsController::class, 'destroy'])->name('destroy');
            Route::post('/{id}/toggle-status', [TermsConditionsController::class, 'toggleStatus'])->name('toggle-status');
            Route::post('/reorder', [TermsConditionsController::class, 'reorder'])->name('reorder');
        });

        // Preguntas Frecuentes (FAQs) - Solo Super Admin
        Route::prefix('faqs')->name('faqs.')->middleware('role:super_admin')->group(function () {
            Route::get('/', [FaqController::class, 'index'])->name('index');
            Route::post('/', [FaqController::class, 'store'])->name('store');
            Route::put('/{id}', [FaqController::class, 'update'])->name('update');
            Route::delete('/{id}', [FaqController::class, 'destroy'])->name('destroy');
            Route::post('/update-title', [FaqController::class, 'updateTitle'])->name('update-title');
            Route::post('/reorder', [FaqController::class, 'reorder'])->name('reorder');
        });

        // Formulario de Pago - Solo Super Admin
        Route::prefix('payment-form')->name('payment-form.')->middleware('role:super_admin')->group(function () {
            Route::get('/', [PaymentFormController::class, 'index'])->name('index');
            Route::post('/', [PaymentFormController::class, 'update'])->name('update');
        });
    });
});
