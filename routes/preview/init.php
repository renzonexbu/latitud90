<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Pdf\ContractPreviewController;
use App\Http\Controllers\PaymentReceiptController;
use App\Http\Controllers\Controller;

// Preview de contrato en PDF (solo desarrollo)
Route::get('/contract/preview', [ContractPreviewController::class, 'show'])->name('contract.preview');
Route::get('/payment/preview', [ContractPreviewController::class, 'paymentShow'])->name('payment.preview');

// Ruta para descargar comprobante de pago
Route::get('/payment/receipt/download', [PaymentReceiptController::class, 'download'])->name('payment.receipt.download');

// Rutas temporales para preview de emails
Route::get('/test/no-payment-email', [Controller::class, 'previewNoPaymentEmail'])->name('test.no-payment-email');
Route::get('/test/success-payment-email', [Controller::class, 'previewSuccessPaymentEmail'])->name('test.success-payment-email');
