
<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Pdf\ContractPreviewController;

// Preview de contrato en PDF (solo desarrollo)
Route::get('/contract/preview', [ContractPreviewController::class, 'show'])->name('contract.preview');
Route::get('/payment/preview', [ContractPreviewController::class, 'paymentShow'])->name('payment.preview');
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
