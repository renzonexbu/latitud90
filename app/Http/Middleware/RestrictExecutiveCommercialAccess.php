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
        // Este middleware ya no es necesario ya que el rol ejecutivo_comercial
        // ha sido integrado en el rol de marketing con permisos específicos.
        // Se mantiene por compatibilidad pero no aplica restricciones.

        return $next($request);
    }
}
