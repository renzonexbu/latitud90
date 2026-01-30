<?php

use App\Http\Controllers\Client\SubscriptionController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Subscription Routes
|--------------------------------------------------------------------------
|
| Rutas para el sistema de suscripciones mensuales con guardian
| Todas estas rutas requieren que el guardian esté autenticado
|
*/

Route::prefix('subscription')->name('subscription.')->group(function () {

    // ========================================
    // RUTAS PÚBLICAS (sin autenticación)
    // ========================================

    // Crear suscripción desde confirmación (sin requerir guardian auth)
    Route::post('/create-from-confirmation', [SubscriptionController::class, 'createFromConfirmation'])
        ->name('create-from-confirmation');

    // Páginas de resultado
    Route::get('/success/{subscriptionId}', [SubscriptionController::class, 'success'])
        ->name('success');

    Route::get('/failure/{subscriptionId}', [SubscriptionController::class, 'failure'])
        ->name('failure');

    // Página de verificación del primer cargo (intermedia)
    Route::get('/verifying/{subscriptionId}', [SubscriptionController::class, 'verifying'])
        ->name('verifying');

    // ========================================
    // RUTAS PROTEGIDAS (requieren autenticación)
    // ========================================
    Route::middleware('guardian.auth')->group(function () {

        // Iniciar proceso de suscripción
        Route::post('/initiate', [SubscriptionController::class, 'initiate'])
            ->name('initiate');

        // Ver detalles de suscripción antes de confirmar
        Route::get('/details/{programId}', [SubscriptionController::class, 'showDetails'])
            ->name('details');

        // Confirmar y procesar suscripción
        Route::post('/process', [SubscriptionController::class, 'process'])
            ->name('process');

        // Gestión de suscripciones del guardian
        Route::get('/my-subscriptions', [SubscriptionController::class, 'mySubscriptions'])
            ->name('my-subscriptions');

        // Cancelar suscripción
        Route::post('/cancel/{subscriptionId}', [SubscriptionController::class, 'cancel'])
            ->name('cancel');

        // Ver detalles de una suscripción específica (DEBE IR AL FINAL)
        Route::get('/{subscriptionId}', [SubscriptionController::class, 'show'])
            ->name('show');
    });
});
