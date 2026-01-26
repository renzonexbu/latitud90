<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Mapeo de roles antiguos a nuevos
        $roleMapping = [
            'admin_contabilidad' => 'contabilidad',
            'editor_contabilidad' => 'contabilidad',
            'visualizador_contabilidad' => 'contabilidad',
            'admin_marketing' => 'marketing',
            'editor_marketing' => 'marketing',
            'visualizador_marketing' => 'marketing',
            'ejecutivo_comercial' => 'marketing',
        ];

        // Crear los nuevos roles si no existen
        foreach (['contabilidad', 'marketing'] as $newRole) {
            Role::firstOrCreate(['name' => $newRole, 'guard_name' => 'web']);
        }

        // Obtener los IDs de los roles antiguos
        $oldRoleIds = Role::whereIn('name', array_keys($roleMapping))->pluck('id', 'name');

        // Obtener los IDs de los roles nuevos
        $newRoleIds = Role::whereIn('name', array_values($roleMapping))->pluck('id', 'name');

        // Obtener todas las asignaciones de roles antiguos agrupadas por usuario
        $userRoles = DB::table('model_has_roles')
            ->whereIn('role_id', $oldRoleIds->values())
            ->get()
            ->groupBy(function ($item) {
                return $item->model_id . '-' . $item->model_type;
            });

        // Para cada usuario, asignar el nuevo rol correspondiente
        foreach ($userRoles as $userKey => $roles) {
            $modelId = $roles->first()->model_id;
            $modelType = $roles->first()->model_type;

            // Determinar qué nuevos roles necesita este usuario
            $newRolesNeeded = [];
            foreach ($roles as $role) {
                $oldRoleName = $oldRoleIds->search($role->role_id);
                if ($oldRoleName && isset($roleMapping[$oldRoleName])) {
                    $newRoleName = $roleMapping[$oldRoleName];
                    if (isset($newRoleIds[$newRoleName])) {
                        $newRolesNeeded[$newRoleName] = $newRoleIds[$newRoleName];
                    }
                }
            }

            // Insertar los nuevos roles (sin duplicados)
            foreach ($newRolesNeeded as $roleName => $roleId) {
                // Verificar si ya existe antes de insertar
                $exists = DB::table('model_has_roles')
                    ->where('role_id', $roleId)
                    ->where('model_id', $modelId)
                    ->where('model_type', $modelType)
                    ->exists();

                if (!$exists) {
                    DB::table('model_has_roles')->insert([
                        'role_id' => $roleId,
                        'model_type' => $modelType,
                        'model_id' => $modelId,
                    ]);
                }
            }
        }

        // Eliminar todas las asignaciones de roles antiguos
        DB::table('model_has_roles')
            ->whereIn('role_id', $oldRoleIds->values())
            ->delete();

        // Eliminar los roles antiguos
        Role::whereIn('name', array_keys($roleMapping))->delete();

        // Limpiar caché de permisos
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No se implementa rollback ya que la estructura antigua será eliminada
        // Si necesitas volver atrás, deberás restaurar desde un backup
    }
};
