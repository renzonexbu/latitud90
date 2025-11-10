<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Test\Subscription\SubscriptionTestController;

/**
 * Rutas de prueba para módulo de suscripciones VirtualPos
 *
 * IMPORTANTE: Estas rutas son solo para testing y desarrollo
 * Deben ser deshabilitadas o protegidas en producción
 */

Route::prefix('test/subscriptions')->name('test.subscriptions.')->group(function () {

    // Página principal de pruebas
    Route::get('/', [SubscriptionTestController::class, 'index'])
        ->name('index');

    // Configuración
    Route::get('/config', [SubscriptionTestController::class, 'config'])
        ->name('config');

    // Gestión de Planes
    Route::get('/plans', [SubscriptionTestController::class, 'plansIndex'])
        ->name('plans');
    Route::post('/plans/create', [SubscriptionTestController::class, 'createPlan'])
        ->name('plans.create');
    Route::post('/plans/get', [SubscriptionTestController::class, 'getPlan'])
        ->name('plans.get');

    // Crear suscripción
    Route::get('/create', [SubscriptionTestController::class, 'createForm'])
        ->name('create');
    Route::post('/create', [SubscriptionTestController::class, 'create'])
        ->name('store');

    // Callbacks de VirtualPos
    Route::post('/callback', [SubscriptionTestController::class, 'callback'])
        ->name('callback');
    Route::match(['GET', 'POST'], '/return', [SubscriptionTestController::class, 'returnUrl'])
        ->name('return');

    // Ver detalles de suscripción
    Route::get('/{id}', [SubscriptionTestController::class, 'show'])
        ->name('show');

    // Sincronizar con VirtualPos
    Route::post('/{id}/sync', [SubscriptionTestController::class, 'sync'])
        ->name('sync');

    // Cancelar suscripción
    Route::post('/{id}/cancel', [SubscriptionTestController::class, 'cancel'])
        ->name('cancel');

    // Generar link de cambio de tarjeta
    Route::post('/{id}/card-change-link', [SubscriptionTestController::class, 'generateCardChangeLink'])
        ->name('card-change-link');

    // Listar todas las suscripciones de VirtualPos
    Route::get('/list/all', [SubscriptionTestController::class, 'listAll'])
        ->name('list-all');

    // Cancelar todas las suscripciones (útil para limpiar cache de pruebas)
    Route::post('/cancel-all', [SubscriptionTestController::class, 'cancelAll'])
        ->name('cancel-all');
});
