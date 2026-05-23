<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role; // Importar el modelo Role de Spatie
use Illuminate\Support\Facades\Gate;

class UserController extends Controller
{
   
    /**
     * Muestra la lista de todos los usuarios del sistema.
     */
    public function index()
    {
        Gate::authorize('seguridad.usuarios.ver');
        $users = User::with('roles')->get(); // Cargar los roles de cada usuario
        $stats = [
           'total' => User::count(),
           'admins' => User::role('super_admin')->count(), // Ajusta según el nombre de tu rol administrador
            'sin_rol' => User::doesntHave('roles')->count(),
            'roles_activos' => Role::count(),
        ];
        return view('admin.users.index', compact('users', 'stats'));
        
    }

    /**
     * Muestra el formulario para editar los roles de un usuario específico.
     */
    public function editRoles(User $user)
    {
        Gate::authorize('seguridad.usuarios.editar');
        // Obtener todos los roles disponibles en el sistema
        $roles = Role::all();
        
        // Obtener los nombres de los roles que el usuario ya tiene
        $userRoles = $user->roles->pluck('name')->toArray(); 

        return view('admin.users.edit_roles', compact('user', 'roles', 'userRoles'));
    }

    /**
     * Actualiza y sincroniza los roles asignados al usuario.
     */
    public function updateRoles(Request $request, User $user)
    {
        Gate::authorize('seguridad.usuarios.editar');
        $request->validate([
            'roles' => ['nullable', 'array'], // Esperamos un array de nombres de roles
        ]);

        // Spatie: Sincroniza los roles. Los quita si no están seleccionados y los añade si lo están.
        $user->syncRoles($request->roles ?? []); 

        return redirect()->route('admin.users.index')->with('success', 
            "Roles del usuario {$user->email} actualizados correctamente.");
    }
}