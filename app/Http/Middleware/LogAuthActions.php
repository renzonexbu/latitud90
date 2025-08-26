<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\AdminLog;
use Symfony\Component\HttpFoundation\Response;

class LogAuthActions
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Log login actions
        if ($request->is('login') && $request->isMethod('POST')) {
            $this->logLogin($request);
        }

        // Log logout actions
        if ($request->is('logout') && $request->isMethod('POST')) {
            $this->logLogout($request);
        }

        return $response;
    }

    /**
     * Log login action
     */
    private function logLogin(Request $request): void
    {
        $user = auth()->user();
        
        if ($user) {
            AdminLog::create([
                'user_id' => $user->id,
                'user_name' => $user->name,
                'user_email' => $user->email,
                'action' => 'login',
                'module' => 'auth',
                'resource_type' => 'User',
                'resource_id' => $user->id,
                'description' => "Usuario inició sesión: {$user->name}",
                'old_values' => null,
                'new_values' => [
                    'login_time' => now()->toISOString(),
                    'user_agent' => $request->userAgent(),
                ],
                'additional_data' => [
                    'login_method' => 'credentials',
                    'remember_me' => $request->has('remember'),
                ],
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'session_id' => session()->getId(),
                'request_method' => $request->method(),
                'request_url' => $request->fullUrl(),
                'request_data' => $this->sanitizeRequestData($request->all()),
            ]);
        }
    }

    /**
     * Log logout action
     */
    private function logLogout(Request $request): void
    {
        $user = auth()->user();
        
        if ($user) {
            AdminLog::create([
                'user_id' => $user->id,
                'user_name' => $user->name,
                'user_email' => $user->email,
                'action' => 'logout',
                'module' => 'auth',
                'resource_type' => 'User',
                'resource_id' => $user->id,
                'description' => "Usuario cerró sesión: {$user->name}",
                'old_values' => [
                    'logout_time' => now()->toISOString(),
                    'user_agent' => $request->userAgent(),
                ],
                'new_values' => null,
                'additional_data' => [
                    'logout_method' => 'manual',
                    'session_duration' => session()->get('login_time') ? 
                        now()->diffInMinutes(session()->get('login_time')) : null,
                ],
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'session_id' => session()->getId(),
                'request_method' => $request->method(),
                'request_url' => $request->fullUrl(),
                'request_data' => $this->sanitizeRequestData($request->all()),
            ]);
        }
    }

    /**
     * Sanitize request data to remove sensitive information
     */
    private function sanitizeRequestData(array $data): array
    {
        $sensitiveFields = [
            'password',
            'password_confirmation',
            'current_password',
            'new_password',
            'token',
            '_token',
            'api_token',
            'remember_token',
        ];

        foreach ($sensitiveFields as $field) {
            if (isset($data[$field])) {
                $data[$field] = '***HIDDEN***';
            }
        }

        return $data;
    }
}
