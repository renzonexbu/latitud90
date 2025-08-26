<?php

namespace App\Traits;

use App\Models\AdminLog;

trait AdminLogging
{
    /**
     * Log an admin action
     */
    protected function logAction(
        string $action,
        string $module,
        ?string $description = null,
        ?string $resourceType = null,
        ?int $resourceId = null,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?array $additionalData = null
    ): void {
        $user = auth()->user();
        
        AdminLog::create([
            'user_id' => $user?->id,
            'user_name' => $user?->name,
            'user_email' => $user?->email,
            'action' => $action,
            'module' => $module,
            'resource_type' => $resourceType,
            'resource_id' => $resourceId,
            'description' => $description,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'additional_data' => $additionalData,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'session_id' => session()->getId(),
            'request_method' => request()->method(),
            'request_url' => request()->fullUrl(),
            'request_data' => $this->sanitizeRequestData(request()->all()),
        ]);
    }

    /**
     * Log a creation action
     */
    protected function logCreate(
        string $module,
        string $resourceType,
        int $resourceId,
        ?string $description = null,
        ?array $newValues = null,
        ?array $additionalData = null
    ): void {
        $this->logAction(
            'create',
            $module,
            $description ?? "Creación de {$resourceType}",
            $resourceType,
            $resourceId,
            null,
            $newValues,
            $additionalData
        );
    }

    /**
     * Log an update action
     */
    protected function logUpdate(
        string $module,
        string $resourceType,
        int $resourceId,
        ?string $description = null,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?array $additionalData = null
    ): void {
        $this->logAction(
            'update',
            $module,
            $description ?? "Actualización de {$resourceType}",
            $resourceType,
            $resourceId,
            $oldValues,
            $newValues,
            $additionalData
        );
    }

    /**
     * Log a delete action
     */
    protected function logDelete(
        string $module,
        string $resourceType,
        int $resourceId,
        ?string $description = null,
        ?array $oldValues = null,
        ?array $additionalData = null
    ): void {
        $this->logAction(
            'delete',
            $module,
            $description ?? "Eliminación de {$resourceType}",
            $resourceType,
            $resourceId,
            $oldValues,
            null,
            $additionalData
        );
    }

    /**
     * Log a view action
     */
    protected function logView(
        string $module,
        string $resourceType,
        int $resourceId,
        ?string $description = null,
        ?array $additionalData = null
    ): void {
        $this->logAction(
            'view',
            $module,
            $description ?? "Visualización de {$resourceType}",
            $resourceType,
            $resourceId,
            null,
            null,
            $additionalData
        );
    }

    /**
     * Log an export action
     */
    protected function logExport(
        string $module,
        ?string $description = null,
        ?array $additionalData = null
    ): void {
        $this->logAction(
            'export',
            $module,
            $description ?? "Exportación de datos",
            null,
            null,
            null,
            null,
            $additionalData
        );
    }

    /**
     * Log a status change action
     */
    protected function logStatusChange(
        string $module,
        string $resourceType,
        int $resourceId,
        string $oldStatus,
        string $newStatus,
        ?string $description = null,
        ?array $additionalData = null
    ): void {
        $this->logAction(
            'update',
            $module,
            $description ?? "Cambio de estado de {$resourceType}",
            $resourceType,
            $resourceId,
            ['status' => $oldStatus],
            ['status' => $newStatus],
            $additionalData
        );
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
