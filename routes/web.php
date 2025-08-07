<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Log;

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
include __DIR__.'/client/init.php';
include __DIR__.'/admin/init.php';

require __DIR__.'/auth.php';

// Webhook de Khipu
Route::post('/webhook/khipu', [App\Http\Controllers\KhipuWebhookController::class, 'handle'])->name('webhook.khipu');
