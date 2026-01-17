<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RestrictExecutiveCommercialAccess
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // Si el usuario es ejecutivo comercial
        if ($user && $user->hasRole('ejecutivo_comercial')) {
            $currentPath = $request->path();

            // Permitir acceso solo a rutas específicas
            $allowedPaths = [
                'admin/reports/executives',
                'admin/profile',
                'logout',
            ];

            $isAllowed = false;
            foreach ($allowedPaths as $allowedPath) {
                if (str_starts_with($currentPath, $allowedPath)) {
                    $isAllowed = true;
                    break;
                }
            }

            // Si intenta acceder a una ruta no permitida, redirigir a su página de reportes
            if (!$isAllowed) {
                return redirect()->route('admin.reports.executives.index')
                    ->with('error', 'No tienes permisos para acceder a esta sección.');
            }
        }

        return $next($request);
    }
}
