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
            'is_contabilidad' => $this->userHasRole('contabilidad'),
            'is_marketing' => $this->userHasRole('marketing'),
        ];
    }

    /**
     * Verificar permisos para acciones específicas
     */
    public function canPerformAction($action, $module = null)
    {
        $permission = $module ? "{$action}_{$module}" : $action;
        return $this->userHasPermission($permission);
    }
}
