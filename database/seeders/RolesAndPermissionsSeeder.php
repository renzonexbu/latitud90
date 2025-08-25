<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Crear permisos básicos
        $permissions = [
            // Permisos generales
            'ver',
            'editar', 
            'eliminar',
            'crear',
            
            // Permisos de gestión de usuarios
            'crear_usuarios',
            'editar_usuarios',
            'eliminar_usuarios',
            'ver_usuarios',
            
            // Permisos específicos por módulo
            'ver_programas',
            'editar_programas',
            'eliminar_programas',
            'crear_programas',
            
            'ver_participantes',
            'editar_participantes',
            'eliminar_participantes',
            'crear_participantes',
            
            'ver_pagos',
            'editar_pagos',
            'eliminar_pagos',
            'crear_pagos',
            
            'ver_reportes',
            'exportar_reportes',
            
            'ver_cursos',
            'editar_cursos',
            'eliminar_cursos',
            'crear_cursos',
            
            'ver_instituciones',
            'editar_instituciones',
            'eliminar_instituciones',
            'crear_instituciones',
        ];

        foreach ($permissions as $permission) {
            Permission::create(['name' => $permission]);
        }

        // Crear roles
        $superAdmin = Role::create(['name' => 'super_admin']);
        $adminContabilidad = Role::create(['name' => 'admin_contabilidad']);
        $adminMarketing = Role::create(['name' => 'admin_marketing']);
        $contabilidad = Role::create(['name' => 'contabilidad']);
        $marketing = Role::create(['name' => 'marketing']);

        // Asignar permisos al Super Admin (acceso total)
        $superAdmin->givePermissionTo(Permission::all());

        // Permisos para Admin de Contabilidad
        $adminContabilidad->givePermissionTo([
            'ver', 'editar', 'eliminar', 'crear',
            'crear_usuarios', 'editar_usuarios', 'ver_usuarios',
            'ver_programas', 'editar_programas', 'ver_participantes', 'editar_participantes',
            'ver_pagos', 'editar_pagos', 'crear_pagos', 'eliminar_pagos',
            'ver_reportes', 'exportar_reportes',
            'ver_cursos', 'editar_cursos', 'ver_instituciones', 'editar_instituciones'
        ]);

        // Permisos para Admin de Marketing
        $adminMarketing->givePermissionTo([
            'ver', 'editar', 'eliminar', 'crear',
            'crear_usuarios', 'editar_usuarios', 'ver_usuarios',
            'ver_programas', 'editar_programas', 'crear_programas', 'eliminar_programas',
            'ver_participantes', 'editar_participantes', 'crear_participantes',
            'ver_pagos', 'editar_pagos', 'ver_reportes', 'exportar_reportes',
            'ver_cursos', 'editar_cursos', 'crear_cursos', 'eliminar_cursos',
            'ver_instituciones', 'editar_instituciones', 'crear_instituciones'
        ]);

        // Permisos para usuarios de Contabilidad
        $contabilidad->givePermissionTo([
            'ver_programas', 'ver_participantes', 'ver_pagos', 'editar_pagos',
            'ver_reportes', 'exportar_reportes', 'ver_cursos', 'ver_instituciones'
        ]);

        // Permisos para usuarios de Marketing
        $marketing->givePermissionTo([
            'ver_programas', 'editar_programas', 'ver_participantes', 'editar_participantes',
            'ver_pagos', 'ver_reportes', 'ver_cursos', 'editar_cursos',
            'ver_instituciones', 'editar_instituciones'
        ]);

        // Asignar rol Super Admin al primer usuario (tú)
        $firstUser = User::first();
        if ($firstUser) {
            $firstUser->assignRole('super_admin');
        }
    }
}
