<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {

            // 1. Resetear el caché de permisos
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // -------------------------------------------------------------------
        // LIMPIEZA Y RESETEO DE TABLAS
        // -------------------------------------------------------------------

        // A. Limpiar la asignación de roles del usuario 1 (Super Administrador)
        $user = User::find(1);
        if ($user) {
          $user->syncRoles([]);
          $user->syncPermissions([]);
        }

        // B. Limpiar Tablas Pivot de Spatie (Usando DELETE)
        DB::table('model_has_roles')->delete();
        DB::table('role_has_permissions')->delete();
        DB::table('model_has_permissions')->delete();

        // C. Limpiar Tablas Principales de Spatie (Usando DELETE)
        Role::query()->delete();
        Permission::query()->delete();

        // 2. Definición del Diccionario de Permisos por Módulo
        $permissionsByModule = [
            'SEGURIDAD' => [
                'seguridad.dashboard',
                'seguridad.menu',
                'seguridad.reportes',
                //USUARIOS
                'seguridad.usuarios.gestionar',
                'seguridad.usuarios.crear', 'seguridad.usuarios.eliminar', 'seguridad.usuarios.ver', 'seguridad.usuarios.editar', 'seguridad.usuarios.reportes',    //ROLES
                //ROLES
                'seguridad.roles.gestionar',
                'seguridad.roles.crear', 'seguridad.roles.eliminar', 'seguridad.roles.ver', 'seguridad.roles.editar', 'seguridad.roles.reportes',
                //PERMISOS
                'seguridad.permisos.gestionar',
                'seguridad.permisos.crear', 'seguridad.permisos.eliminar', 'seguridad.permisos.ver', 'seguridad.permisos.editar', 'seguridad.permisos.reportes',
            ],

        ];

        // 3. Crear Permisos en la DB
        foreach ($permissionsByModule as $moduleName => $permissions) {
            foreach ($permissions as $permissionName) {
                Permission::create([
                    'name'   => $permissionName,
                    'module' => $moduleName, // Tu columna personalizada
                    'guard_name' => 'web'
                ]);
            }
        }

        // 4. Definición de Roles y Asignación de Permisos

        // --- ROLE: SUPER ADMINISTRADOR ---
        $superAdminRole = Role::create(['name' => 'super_admin']);
        // El Super Admin no necesita permisos específicos si usas Gate::before en AuthServiceProvider
        // pero por buena práctica se los asignamos todos.
        $superAdminRole->givePermissionTo(Permission::all());

        // --- ROLE: VISOR (Solo lectura de todo) ---
        //$visorRole = Role::create(['name' => 'gerente_general']);
        //$visorRole->givePermissionTo(Permission::where('name', 'like', '%.ver')->orWhere('name', 'like', '%.dashboard')->get());


        // 5. Asignación al Usuario ID 1
        $user = User::find(1);
        if ($user) {
            $user->assignRole($superAdminRole);
            $this->command->info('Usuario ID 1 ahora es Super Administrador.');
        } else {
            $this->command->error('No se encontró el usuario con ID 1.');
        }
    }
}
