<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Http\Request;

class Authenticate extends Middleware
{
    /**
     * Get the path the user should be redirected to when they are not authenticated.
     */
    protected function redirectTo(Request $request): ?string
    {
        if ($request->expectsJson()) {
            return null;
        }

        // Detectar qué guard se está usando basado en la ruta
        if ($request->is('guardian/*') || $request->is('guardian')) {
            return route('guardian.login');
        }

        // Por defecto, redirigir al login del panel administrativo
        return route('admin.login');
    }
}
