<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Cookie;
use Symfony\Component\HttpFoundation\Response;

class VisitorTrackingMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);
        
        // Establecer visitor_id si no existe
        if (!$request->cookie('visitor_id')) {
            $visitorId = Str::uuid()->toString();
            Cookie::queue('visitor_id', $visitorId, 60 * 24 * 365); // 1 año
        }
        
        return $response;
    }
}
