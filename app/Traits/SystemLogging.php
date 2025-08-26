<?php

namespace App\Traits;

use App\Models\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Log as LaravelLog;

trait SystemLogging
{
    /**
     * Registrar un log de error
     */
    protected function logError(string $message, array $context = [], \Throwable $exception = null): void
    {
        $this->createLog('error', $message, $context, $exception);
    }

    /**
     * Registrar un log de información
     */
    protected function logInfo(string $message, array $context = []): void
    {
        $this->createLog('info', $message, $context);
    }

    /**
     * Registrar un log de advertencia
     */
    protected function logWarning(string $message, array $context = []): void
    {
        $this->createLog('warning', $message, $context);
    }

    /**
     * Registrar un log crítico
     */
    protected function logCritical(string $message, array $context = [], \Throwable $exception = null): void
    {
        $this->createLog('critical', $message, $context, $exception);
    }

    /**
     * Registrar un log de debug
     */
    protected function logDebug(string $message, array $context = []): void
    {
        if (config('app.debug')) {
            $this->createLog('debug', $message, $context);
        }
    }

    /**
     * Crear el log en la base de datos
     */
    private function createLog(string $level, string $message, array $context = [], \Throwable $exception = null): void
    {
        try {
            $user = Auth::user();
            $request = Request::instance();

            $logData = [
                'level' => $level,
                'message' => $message,
                'context' => $this->sanitizeContext($context),
                'user_id' => $user ? $user->id : null,
                'user_email' => $user ? $user->email : null,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'request_method' => $request->method(),
                'request_url' => $request->fullUrl(),
                'request_data' => $this->sanitizeRequestData($request->all()),
            ];

            // Agregar información del error si existe
            if ($exception) {
                $logData['file'] = $exception->getFile();
                $logData['line'] = $exception->getLine();
                $logData['trace'] = $exception->getTraceAsString();
            }

            \App\Models\Log::create($logData);

        } catch (\Exception $e) {
            // Si falla el logging, usar el logger de Laravel como fallback
            LaravelLog::error('Error al crear log en base de datos: ' . $e->getMessage(), [
                'original_message' => $message,
                'original_context' => $context,
            ]);
        }
    }

    /**
     * Sanitizar el contexto para evitar datos sensibles
     */
    private function sanitizeContext(array $context): array
    {
        $sensitiveKeys = ['password', 'password_confirmation', 'token', 'api_key', 'secret'];
        
        return $this->removeSensitiveData($context, $sensitiveKeys);
    }

    /**
     * Sanitizar datos de la petición para evitar datos sensibles
     */
    private function sanitizeRequestData(array $data): array
    {
        $sensitiveKeys = ['password', 'password_confirmation', 'token', 'api_key', 'secret', '_token'];
        
        return $this->removeSensitiveData($data, $sensitiveKeys);
    }

    /**
     * Remover datos sensibles de un array recursivamente
     */
    private function removeSensitiveData(array $data, array $sensitiveKeys): array
    {
        $sanitized = [];

        foreach ($data as $key => $value) {
            if (in_array(strtolower($key), array_map('strtolower', $sensitiveKeys))) {
                $sanitized[$key] = '***HIDDEN***';
            } elseif (is_array($value)) {
                $sanitized[$key] = $this->removeSensitiveData($value, $sensitiveKeys);
            } else {
                $sanitized[$key] = $value;
            }
        }

        return $sanitized;
    }

    /**
     * Log de inicio de operación
     */
    protected function logOperationStart(string $operation, array $context = []): void
    {
        $this->logInfo("Iniciando operación: {$operation}", $context);
    }

    /**
     * Log de finalización exitosa de operación
     */
    protected function logOperationSuccess(string $operation, array $context = []): void
    {
        $this->logInfo("Operación completada exitosamente: {$operation}", $context);
    }

    /**
     * Log de fallo en operación
     */
    protected function logOperationFailure(string $operation, string $error, array $context = []): void
    {
        $this->logError("Operación falló: {$operation} - {$error}", $context);
    }

    /**
     * Log de validación fallida
     */
    protected function logValidationFailure(string $operation, array $errors, array $data = []): void
    {
        $this->logWarning("Validación fallida en: {$operation}", [
            'errors' => $errors,
            'data' => $this->sanitizeContext($data),
        ]);
    }

    /**
     * Log de acceso denegado
     */
    protected function logAccessDenied(string $operation, string $reason = ''): void
    {
        $this->logWarning("Acceso denegado: {$operation}" . ($reason ? " - {$reason}" : ''));
    }

    /**
     * Log de exportación
     */
    protected function logExport(string $type, array $context = []): void
    {
        $this->logInfo("Exportación realizada: {$type}", $context);
    }

    /**
     * Log de importación
     */
    protected function logImport(string $type, array $context = []): void
    {
        $this->logInfo("Importación realizada: {$type}", $context);
    }
}
