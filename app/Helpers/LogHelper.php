<?php

namespace App\Helpers;

use App\Models\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

class LogHelper
{
    /**
     * Registrar un log de error
     */
    public static function error(string $message, array $context = [], \Throwable $exception = null): void
    {
        self::createLog('error', $message, $context, $exception);
    }

    /**
     * Registrar un log de información
     */
    public static function info(string $message, array $context = []): void
    {
        self::createLog('info', $message, $context);
    }

    /**
     * Registrar un log de advertencia
     */
    public static function warning(string $message, array $context = []): void
    {
        self::createLog('warning', $message, $context);
    }

    /**
     * Registrar un log crítico
     */
    public static function critical(string $message, array $context = [], \Throwable $exception = null): void
    {
        self::createLog('critical', $message, $context, $exception);
    }

    /**
     * Registrar un log de debug
     */
    public static function debug(string $message, array $context = []): void
    {
        if (config('app.debug')) {
            self::createLog('debug', $message, $context);
        }
    }

    /**
     * Crear el log en la base de datos
     */
    private static function createLog(string $level, string $message, array $context = [], \Throwable $exception = null): void
    {
        try {
            $user = Auth::user();
            $request = Request::instance();

            $logData = [
                'level' => $level,
                'message' => $message,
                'context' => self::sanitizeContext($context),
                'user_id' => $user ? $user->id : null,
                'user_email' => $user ? $user->email : null,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'request_method' => $request->method(),
                'request_url' => $request->fullUrl(),
                'request_data' => self::sanitizeRequestData($request->all()),
            ];

            // Agregar información del error si existe
            if ($exception) {
                $logData['file'] = $exception->getFile();
                $logData['line'] = $exception->getLine();
                $logData['trace'] = $exception->getTraceAsString();
            }

            Log::create($logData);

        } catch (\Exception $e) {
            // Si falla el logging, usar el logger de Laravel como fallback
            \Illuminate\Support\Facades\Log::error('Error al crear log en base de datos: ' . $e->getMessage(), [
                'original_message' => $message,
                'original_context' => $context,
            ]);
        }
    }

    /**
     * Sanitizar el contexto para evitar datos sensibles
     */
    private static function sanitizeContext(array $context): array
    {
        $sensitiveKeys = ['password', 'password_confirmation', 'token', 'api_key', 'secret'];
        
        return self::removeSensitiveData($context, $sensitiveKeys);
    }

    /**
     * Sanitizar datos de la petición para evitar datos sensibles
     */
    private static function sanitizeRequestData(array $data): array
    {
        $sensitiveKeys = ['password', 'password_confirmation', 'token', 'api_key', 'secret', '_token'];
        
        return self::removeSensitiveData($data, $sensitiveKeys);
    }

    /**
     * Remover datos sensibles de un array recursivamente
     */
    private static function removeSensitiveData(array $data, array $sensitiveKeys): array
    {
        $sanitized = [];

        foreach ($data as $key => $value) {
            if (in_array(strtolower($key), array_map('strtolower', $sensitiveKeys))) {
                $sanitized[$key] = '***HIDDEN***';
            } elseif (is_array($value)) {
                $sanitized[$key] = self::removeSensitiveData($value, $sensitiveKeys);
            } else {
                $sanitized[$key] = $value;
            }
        }

        return $sanitized;
    }

    /**
     * Log de inicio de operación
     */
    public static function operationStart(string $operation, array $context = []): void
    {
        self::info("Iniciando operación: {$operation}", $context);
    }

    /**
     * Log de finalización exitosa de operación
     */
    public static function operationSuccess(string $operation, array $context = []): void
    {
        self::info("Operación completada exitosamente: {$operation}", $context);
    }

    /**
     * Log de fallo en operación
     */
    public static function operationFailure(string $operation, string $error, array $context = []): void
    {
        self::error("Operación falló: {$operation} - {$error}", $context);
    }

    /**
     * Log de validación fallida
     */
    public static function validationFailure(string $operation, array $errors, array $data = []): void
    {
        self::warning("Validación fallida en: {$operation}", [
            'errors' => $errors,
            'data' => self::sanitizeContext($data),
        ]);
    }

    /**
     * Log de acceso denegado
     */
    public static function accessDenied(string $operation, string $reason = ''): void
    {
        self::warning("Acceso denegado: {$operation}" . ($reason ? " - {$reason}" : ''));
    }

    /**
     * Log de exportación
     */
    public static function export(string $type, array $context = []): void
    {
        self::info("Exportación realizada: {$type}", $context);
    }

    /**
     * Log de importación
     */
    public static function import(string $type, array $context = []): void
    {
        self::info("Importación realizada: {$type}", $context);
    }
}
