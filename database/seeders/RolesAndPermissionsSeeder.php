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
            'ver_reportes_executives', // Permiso específico para ver reportes de ejecutivos
            'ver_contacto_pagador', // Permiso para ver información sensible del contacto pagador
            
            'ver_cursos',
            'editar_cursos',
            'eliminar_cursos',
            'crear_cursos',
            
            'ver_instituciones',
            'editar_instituciones',
            'eliminar_instituciones',
            'crear_instituciones',

            // Permisos de contenido
            'ver_contenido',
            'editar_contenido',
            'crear_contenido',
            'eliminar_contenido',

            // Permisos del mantenedor - Marketing Mails
            'ver_marketing_mails',
            'editar_marketing_mails',
            'crear_marketing_mails',
            'eliminar_marketing_mails',

            // Permisos del mantenedor - Newsletter
            'ver_newsletter',
            'editar_newsletter',
            'crear_newsletter',
            'eliminar_newsletter',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Crear roles
        $superAdmin = Role::firstOrCreate(['name' => 'super_admin']);
        $adminContabilidad = Role::firstOrCreate(['name' => 'admin_contabilidad']);
        $adminMarketing = Role::firstOrCreate(['name' => 'admin_marketing']);
        $editorContabilidad = Role::firstOrCreate(['name' => 'editor_contabilidad']);
        $editorMarketing = Role::firstOrCreate(['name' => 'editor_marketing']);
        $visualizadorContabilidad = Role::firstOrCreate(['name' => 'visualizador_contabilidad']);
        $visualizadorMarketing = Role::firstOrCreate(['name' => 'visualizador_marketing']);
        $ejecutivoComercial = Role::firstOrCreate(['name' => 'ejecutivo_comercial']);

        // Asignar permisos al Super Admin (acceso total)
        $superAdmin->syncPermissions(Permission::all());

        // Permisos para Admin de Contabilidad (control total sobre su grupo + contacto pagador)
        $adminContabilidad->syncPermissions([
            'ver', 'editar', 'eliminar', 'crear',
            'crear_usuarios', 'editar_usuarios', 'eliminar_usuarios', 'ver_usuarios',
            'ver_programas', 'editar_programas', 'eliminar_programas', 'crear_programas',
            'ver_participantes', 'editar_participantes', 'eliminar_participantes', 'crear_participantes',
            'ver_pagos', 'editar_pagos', 'crear_pagos', 'eliminar_pagos',
            'ver_reportes', 'exportar_reportes', 'ver_reportes_executives', 'ver_contacto_pagador',
            'ver_cursos', 'editar_cursos', 'eliminar_cursos', 'crear_cursos',
            'ver_instituciones', 'editar_instituciones', 'eliminar_instituciones', 'crear_instituciones'
        ]);

        // Permisos para Admin de Marketing (solo plantillas, contenido, marketing mails y newsletter)
        $adminMarketing->syncPermissions([
            'ver_programas', // Solo visualización de plantillas
            'ver_contenido', 'editar_contenido', 'crear_contenido', 'eliminar_contenido',
            'ver_marketing_mails', 'editar_marketing_mails', 'crear_marketing_mails', 'eliminar_marketing_mails',
            'ver_newsletter', 'editar_newsletter', 'crear_newsletter', 'eliminar_newsletter',
        ]);

        // Permisos para Editor de Contabilidad (casi todo excepto crear usuarios y eliminar)
        $editorContabilidad->syncPermissions([
            'ver', 'editar', 'crear',
            'editar_usuarios', 'ver_usuarios',
            'ver_programas', 'editar_programas', 'crear_programas',
            'ver_participantes', 'editar_participantes', 'crear_participantes',
            'ver_pagos', 'editar_pagos', 'crear_pagos',
            'ver_reportes', 'exportar_reportes',
            'ver_cursos', 'editar_cursos', 'crear_cursos',
            'ver_instituciones', 'editar_instituciones', 'crear_instituciones'
        ]);

        // Permisos para Editor de Marketing (solo plantillas, contenido, marketing mails y newsletter - sin eliminar)
        $editorMarketing->syncPermissions([
            'ver_programas', // Solo visualización de plantillas
            'ver_contenido', 'editar_contenido', 'crear_contenido',
            'ver_marketing_mails', 'editar_marketing_mails', 'crear_marketing_mails',
            'ver_newsletter', 'editar_newsletter', 'crear_newsletter',
        ]);

        // Permisos para Visualizador de Contabilidad (solo ver)
        $visualizadorContabilidad->syncPermissions([
            'ver',
            'ver_usuarios',
            'ver_programas',
            'ver_participantes',
            'ver_pagos',
            'ver_reportes', 'exportar_reportes',
            'ver_cursos',
            'ver_instituciones'
        ]);

        // Permisos para Visualizador de Marketing (solo ver plantillas, contenido, marketing mails y newsletter)
        $visualizadorMarketing->syncPermissions([
            'ver_programas', // Solo visualización de plantillas
            'ver_contenido',
            'ver_marketing_mails',
            'ver_newsletter',
        ]);

        // Permisos para Ejecutivo Comercial (solo reportes de executives)
        $ejecutivoComercial->syncPermissions([
            'ver_reportes_executives',
            'exportar_reportes'
        ]);
    }
}
