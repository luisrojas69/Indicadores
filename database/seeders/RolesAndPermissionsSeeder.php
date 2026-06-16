<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        // ── 1. Resetear caché de permisos Spatie ──────────────────────────
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // ── 2. Limpieza de tablas pivot (orden crítico por FK constraints) ─
        $firstUser = User::find(1);
        if ($firstUser) {
            $firstUser->syncRoles([]);
            $firstUser->syncPermissions([]);
        }

        DB::table('model_has_roles')->delete();
        DB::table('role_has_permissions')->delete();
        DB::table('model_has_permissions')->delete();

        Role::query()->delete();
        Permission::query()->delete();

        // ── 3. Diccionario de Permisos por Módulo ─────────────────────────
        /**
         * La columna personalizada 'module' se inserta en cada Permission::create()
         * para mantener compatibilidad con el esquema extendido de Spatie del proyecto.
         *
         * Nomenclatura: dominio.recurso.accion
         */
        $permissionsByModule = [

            // ── Seguridad / Administración del sistema ────────────────────
            'SEGURIDAD' => [
                'seguridad.dashboard',
                'seguridad.menu',
                'seguridad.reportes',
                // Usuarios
                'seguridad.usuarios.gestionar',
                'seguridad.usuarios.crear',
                'seguridad.usuarios.editar',
                'seguridad.usuarios.eliminar',
                'seguridad.usuarios.ver',
                'seguridad.usuarios.reportes',
                // Roles
                'seguridad.roles.gestionar',
                'seguridad.roles.crear',
                'seguridad.roles.editar',
                'seguridad.roles.eliminar',
                'seguridad.roles.ver',
                'seguridad.roles.reportes',
                // Permisos
                'seguridad.permisos.gestionar',
                'seguridad.permisos.crear',
                'seguridad.permisos.editar',
                'seguridad.permisos.eliminar',
                'seguridad.permisos.ver',
                'seguridad.permisos.reportes',
            ],

            // ── Gerencia / Dashboard ejecutivo ────────────────────────────
            'GERENCIA' => [
                'gerencia.dashboard.ver',
                'gerencia.dashboard.exportar',
                'gerencia.cxc.ver',
                'gerencia.cxp.ver',
                'gerencia.vendedores.ranking.ver',
                'gerencia.productos.ranking.ver',
            ],

            // ── Financiero / Márgenes y bonos ─────────────────────────────
            'FINANCIERO' => [
                'financiero.margenes.ver',
                'financiero.margenes.exportar',
                'financiero.reporte.bonos',
                'financiero.alertas.margen.ver',
                'financiero.config.costo.editar',
            ],

            // ── Inventario / Auditoría anti-fugas ─────────────────────────
            'INVENTARIO' => [
                'inventario.entradas.ver',
                'inventario.entradas.exportar',
                'inventario.salidas.auditar',
                'inventario.salidas.exportar',
                'inventario.stock.critico',
                'inventario.stock.configurar',
                'inventario.reporte.consolidado.ver',
                'inventario.reporte.consolidado.exportar',
                'inventario.articulos.ver',
                'inventario.articulos.rendimiento',
            ],

            // ── Ventas / Módulo tablet y caja ─────────────────────────────
            'VENTAS' => [
                'vendedor.catalogo.ver',
                'vendedor.prepedido.crear',
                'vendedor.prepedido.gestionar',
                'vendedor.prepedido.cancelar',
                'caja.prepedidos.ver',
                'caja.prepedidos.procesar',
            ],
        ];

        // ── 4. Crear permisos con columna 'module' personalizada ──────────
        foreach ($permissionsByModule as $moduleName => $permissions) {
            foreach ($permissions as $permissionName) {
                Permission::create([
                    'name'       => $permissionName,
                    'module'     => $moduleName,
                    'guard_name' => 'web',
                ]);
            }
        }

        // ── 5. Crear roles ────────────────────────────────────────────────
        $superAdmin = Role::create(['name' => 'super_admin',  'guard_name' => 'web']);
        $gerente    = Role::create(['name' => 'gerente',      'guard_name' => 'web']);
        $financiero = Role::create(['name' => 'financiero',   'guard_name' => 'web']);
        $auditor    = Role::create(['name' => 'auditor_inv',  'guard_name' => 'web']);
        $vendedor   = Role::create(['name' => 'vendedor',     'guard_name' => 'web']);
        $cajero     = Role::create(['name' => 'cajero',       'guard_name' => 'web']);

        // ── 6. Asignar permisos por rol ───────────────────────────────────

        // Super Admin: acceso total sin restricción.
        $superAdmin->givePermissionTo(Permission::all());

        // Gerente: ve todo lo de su dashboard + puede exportar.
        $gerente->givePermissionTo(
            Permission::where('module', 'GERENCIA')->pluck('name')->toArray()
        );

        // Financiero: dashboard gerencial + módulo financiero completo.
        $financiero->givePermissionTo(
            Permission::whereIn('module', ['GERENCIA', 'FINANCIERO'])->pluck('name')->toArray()
        );

        // Auditor de inventario: módulo inventario completo.
        $auditor->givePermissionTo(
            Permission::where('module', 'INVENTARIO')->pluck('name')->toArray()
        );

        // Vendedor: solo puede ver catálogo y gestionar sus pre-pedidos.
        $vendedor->givePermissionTo([
            'vendedor.catalogo.ver',
            'vendedor.prepedido.crear',
            'vendedor.prepedido.gestionar',
            'vendedor.prepedido.cancelar',
        ]);

        // Cajero: recibe y procesa pre-pedidos en caja.
        $cajero->givePermissionTo([
            'caja.prepedidos.ver',
            'caja.prepedidos.procesar',
        ]);

        // ── 7. Asignar super_admin al Usuario ID 1 ────────────────────────
        $user = User::find(1);

        if ($user) {
            $user->assignRole($superAdmin);
            $this->command->info('✅ Usuario ID 1 asignado como Super Administrador.');
        } else {
            $this->command->error('⚠️  No se encontró el usuario con ID 1. Crea un usuario primero.');
        }

        $this->command->info(sprintf(
            '✅ Seeder completado: %d permisos · %d roles creados.',
            Permission::count(),
            Role::count()
        ));
    }
}
