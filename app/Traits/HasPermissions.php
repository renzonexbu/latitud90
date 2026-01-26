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
            'is_contabilidad' => $this->userHasRole('contabilidad'),
            'is_marketing' => $this->userHasRole('marketing'),
            'is_ejecutivo_comercial' => $this->userHasRole('ejecutivo_comercial'),
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
     * Verificar si el usuario es de contabilidad
     */
    public function isContabilidad()
    {
        return $this->userHasRole('contabilidad');
    }

    /**
     * Verificar si el usuario es de marketing
     */
    public function isMarketing()
    {
        return $this->userHasRole('marketing');
    }

    /**
     * Verificar si el usuario es ejecutivo comercial
     */
    public function isEjecutivoComercial()
    {
        return $this->userHasRole('ejecutivo_comercial');
    }

    /**
     * Verificar si el usuario puede crear usuarios
     */
    public function canCreateUsers()
    {
        return $this->isSuperAdmin();
    }

    /**
     * Verificar si el usuario puede eliminar
     */
    public function canDelete()
    {
        return $this->userHasPermission('eliminar');
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
