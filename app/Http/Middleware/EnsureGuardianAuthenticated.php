<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureGuardianAuthenticated
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Verificar si el usuario está autenticado con el guard 'guardian'
        if (!auth('guardian')->check()) {
            // Guardar datos del programa/pago en sesión para después del login
            if ($request->has('program_id')) {
                session()->put('pending_subscription', [
                    'program_id' => $request->input('program_id'),
                    'document' => $request->input('document'),
                    'document_type' => $request->input('document_type'),
                    'payment_type' => 'monthly'
                ]);
            }

            // Si es una petición AJAX, retornar JSON
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'error' => 'Debes estar registrado como apoderado para usar mensualidades',
                    'redirect' => route('guardian.register')
                ], 401);
            }

            // Redirigir al registro de guardian
            return redirect()->route('guardian.register')
                ->with('message', 'Para usar el método de pago mensual, necesitas registrarte como apoderado.');
        }

        return $next($request);
    }
}
