<?php

namespace App\Traits;

use App\Models\AdminLog;

trait AdminLogging
{
    /**
     * Registrar una acción en los logs administrativos
     */
    protected function logAction($action, $module, $description = null, $resourceType = null, $resourceId = null, $oldValues = null, $newValues = null, $additionalData = null): void
    {
        AdminLog::logAction($action, $module, $description, $resourceType, $resourceId, $oldValues, $newValues, $additionalData);
    }

    /**
     * Registrar creación de un recurso
     */
    protected function logCreate($module, $resourceType, $resourceId, $description = null, $additionalData = null): void
    {
        $this->logAction('create', $module, $description, $resourceType, $resourceId, null, null, $additionalData);
    }

    /**
     * Registrar actualización de un recurso
     */
    protected function logUpdate($module, $resourceType, $resourceId, $description = null, $oldValues = null, $newValues = null, $additionalData = null): void
    {
        $this->logAction('update', $module, $description, $resourceType, $resourceId, $oldValues, $newValues, $additionalData);
    }

    /**
     * Registrar eliminación de un recurso
     */
    protected function logDelete($module, $resourceType, $resourceId, $description = null, $additionalData = null): void
    {
        $this->logAction('delete', $module, $description, $resourceType, $resourceId, null, null, $additionalData);
    }

    /**
     * Registrar vista de un recurso
     */
    protected function logView($module, $resourceType = null, $resourceId = null, $description = null, $additionalData = null): void
    {
        $this->logAction('view', $module, $description, $resourceType, $resourceId, null, null, $additionalData);
    }

    /**
     * Registrar exportación
     */
    protected function logExport($module, $description = null, $additionalData = null): void
    {
        $this->logAction('export', $module, $description, null, null, null, null, $additionalData);
    }

    /**
     * Registrar login
     */
    protected function logLogin($description = null, $additionalData = null): void
    {
        $this->logAction('login', 'auth', $description, null, null, null, null, $additionalData);
    }

    /**
     * Registrar logout
     */
    protected function logLogout($description = null, $additionalData = null): void
    {
        $this->logAction('logout', 'auth', $description, null, null, null, null, $additionalData);
    }
}
