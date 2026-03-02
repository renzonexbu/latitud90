<?php

use App\Http\Controllers\Client\authentication\GuardianAuthController;
use App\Http\Controllers\Client\Guardian\GuardianDashboardController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Guardian Authentication Routes
|--------------------------------------------------------------------------
|
| Rutas para el sistema de autenticación de apoderados (guardian_users)
|
*/

Route::prefix('guardian')->name('guardian.')->group(function () {

    // Redirección raíz a dashboard (si está autenticado) o login (si no lo está)
    Route::get('/', function () {
        if (auth('guardian')->check()) {
            return redirect()->route('guardian.dashboard');
        }
        return redirect()->route('guardian.login');
    });

    // Rutas para invitados (no autenticados)
    Route::middleware('guest:guardian')->group(function () {

        // Registro
        Route::get('/register', [GuardianAuthController::class, 'showRegisterForm'])
            ->name('register');

        Route::post('/register', [GuardianAuthController::class, 'register'])
            ->name('register.post');

        Route::get('/register/success', [GuardianAuthController::class, 'registerSuccess'])
            ->name('register.success');

        // Verificación de email
        Route::get('/verify-email/{userId}/{token}', [GuardianAuthController::class, 'verifyEmail'])
            ->name('verify-email');

        Route::post('/resend-verification', [GuardianAuthController::class, 'resendVerification'])
            ->name('resend-verification');

        // Login
        Route::get('/login', [GuardianAuthController::class, 'showLoginForm'])
            ->name('login');

        Route::post('/login', [GuardianAuthController::class, 'login'])
            ->name('login.post');

        // Reseteo de contraseña
        Route::get('/forgot-password', [GuardianAuthController::class, 'showForgotPasswordForm'])
            ->name('forgot-password');

        Route::post('/forgot-password', [GuardianAuthController::class, 'forgotPassword'])
            ->name('forgot-password.post');

        Route::get('/account-access/{userId}/{token}', [GuardianAuthController::class, 'showResetPasswordForm'])
            ->name('reset-password');

        Route::post('/account-access', [GuardianAuthController::class, 'resetPassword'])
            ->name('reset-password.post');
    });

    // Rutas para usuarios autenticados
    Route::middleware('auth:guardian')->group(function () {

        // Página de verificación de email pendiente
        Route::get('/verification-required', [GuardianAuthController::class, 'showVerificationNotice'])
            ->name('verification.notice');

        Route::post('/verification-resend', [GuardianAuthController::class, 'resendVerificationAuthenticated'])
            ->name('verification.resend');

        // Logout
        Route::post('/logout', [GuardianAuthController::class, 'logout'])
            ->name('logout');

        // Cambiar contraseña (usuario autenticado)
        Route::get('/change-password', [GuardianAuthController::class, 'showChangePasswordForm'])
            ->name('change-password');

        Route::post('/change-password', [GuardianAuthController::class, 'changePassword'])
            ->name('change-password.post');

        // Dashboard
        Route::get('/dashboard', [GuardianDashboardController::class, 'index'])
            ->name('dashboard');

        // Participantes
        Route::get('/participants', [GuardianDashboardController::class, 'participants'])
            ->name('participants');

        // Programas de un participante
        Route::get('/participant/{participant}/programs', [GuardianDashboardController::class, 'participantPrograms'])
            ->name('participant.programs');

        // Detalle de un programa específico
        Route::get('/participant/{participant}/program/{programCourse}', [GuardianDashboardController::class, 'programDetail'])
            ->name('participant.program.detail');

        // Vista de pruebas (solo para testing)
        Route::get('/test', function () {
            return inertia('Guardian/TestView', [
                'user' => auth('guardian')->user()->load([
                    'documentType',
                    'country',
                    'region',
                    'comune',
                    'guardianLinks.emergencyContact.participant'
                ])
            ]);
        })->name('test');
    });
});
