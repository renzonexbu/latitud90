<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class SetVisitorId
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        // Verificar si ya existe el visitor_id en las cookies
        $visitorId = $request->cookie('visitor_id');
        
        if (!$visitorId) {
            // Generar un nuevo visitor_id
            $visitorId = Str::uuid()->toString();
            
            // Establecer la cookie por 1 año
            $response = $next($request);
            $response->cookie('visitor_id', $visitorId, 60 * 24 * 365);
            
            return $response;
        }
        
        return $next($request);
    }
}
