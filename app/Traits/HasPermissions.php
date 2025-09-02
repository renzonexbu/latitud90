<?php

namespace App\Traits;

use Illuminate\Support\Facades\Auth;

trait HasPermissions
{
    /**
     * Obtener todos los permisos del usuario autenticado
     */
    public function getUserPermissions()
    {
        $user = Auth::user();
        
        if (!$user) {
            return [];
        }

        return $user->getAllPermissions()->pluck('name')->toArray();
    }

    /**
     * Obtener todos los roles del usuario autenticado
     */
    public function getUserRoles()
    {
        $user = Auth::user();
        
        if (!$user) {
            return [];
        }

        return $user->getRoleNames()->toArray();
    }

    /**
     * Verificar si el usuario tiene un permiso específico
     */
    public function userHasPermission($permission)
    {
        $user = Auth::user();
        
        if (!$user) {
            return false;
        }

        return $user->hasPermissionTo($permission);
    }

    /**
     * Verificar si el usuario tiene un rol específico
     */
    public function userHasRole($role)
    {
        $user = Auth::user();
        
        if (!$user) {
            return false;
        }

        return $user->hasRole($role);
    }

    /**
     * Obtener datos de permisos para enviar al frontend
     */
    public function getPermissionsData()
    {
        return [
            'permissions' => $this->getUserPermissions(),
            'roles' => $this->getUserRoles(),
            'is_super_admin' => $this->userHasRole('super_admin'),
            'is_admin_contabilidad' => $this->userHasRole('admin_contabilidad'),
            'is_admin_marketing' => $this->userHasRole('admin_marketing'),
            'is_editor_contabilidad' => $this->userHasRole('editor_contabilidad'),
            'is_editor_marketing' => $this->userHasRole('editor_marketing'),
            'is_visualizador_contabilidad' => $this->userHasRole('visualizador_contabilidad'),
            'is_visualizador_marketing' => $this->userHasRole('visualizador_marketing'),
        ];
    }

    /**
     * Verificar si el usuario es super admin
     */
    public function isSuperAdmin()
    {
        return $this->userHasRole('super_admin');
    }

    /**
     * Verificar si el usuario es admin de contabilidad
     */
    public function isAdminContabilidad()
    {
        return $this->userHasRole('admin_contabilidad');
    }

    /**
     * Verificar si el usuario es admin de marketing
     */
    public function isAdminMarketing()
    {
        return $this->userHasRole('admin_marketing');
    }

    /**
     * Verificar si el usuario es editor de contabilidad
     */
    public function isEditorContabilidad()
    {
        return $this->userHasRole('editor_contabilidad');
    }

    /**
     * Verificar si el usuario es editor de marketing
     */
    public function isEditorMarketing()
    {
        return $this->userHasRole('editor_marketing');
    }

    /**
     * Verificar si el usuario es visualizador de contabilidad
     */
    public function isVisualizadorContabilidad()
    {
        return $this->userHasRole('visualizador_contabilidad');
    }

    /**
     * Verificar si el usuario es visualizador de marketing
     */
    public function isVisualizadorMarketing()
    {
        return $this->userHasRole('visualizador_marketing');
    }

    /**
     * Verificar si el usuario pertenece al grupo de contabilidad
     */
    public function belongsToContabilidadGroup()
    {
        return $this->isAdminContabilidad() || 
               $this->isEditorContabilidad() || 
               $this->isVisualizadorContabilidad();
    }

    /**
     * Verificar si el usuario pertenece al grupo de marketing
     */
    public function belongsToMarketingGroup()
    {
        return $this->isAdminMarketing() || 
               $this->isEditorMarketing() || 
               $this->isVisualizadorMarketing();
    }

    /**
     * Verificar si el usuario puede crear usuarios
     */
    public function canCreateUsers()
    {
        return $this->isSuperAdmin() || 
               $this->isAdminContabilidad() || 
               $this->isAdminMarketing();
    }

    /**
     * Verificar si el usuario puede eliminar
     */
    public function canDelete()
    {
        return $this->isSuperAdmin() || 
               $this->isAdminContabilidad() || 
               $this->isAdminMarketing();
    }

    /**
     * Verificar permisos para acciones específicas
     */
    public function canPerformAction($action)
    {
        $user = Auth::user();
        
        if (!$user) {
            return false;
        }

        // Super admin puede hacer todo
        if ($this->isSuperAdmin()) {
            return true;
        }

        // Verificar permisos específicos según la acción
        switch ($action) {
            case 'create_users':
                return $this->canCreateUsers();
            case 'delete':
                return $this->canDelete();
            case 'edit':
                return $this->userHasPermission('editar');
            case 'view':
                return $this->userHasPermission('ver');
            default:
                return $user->hasPermissionTo($action);
        }
    }
}
