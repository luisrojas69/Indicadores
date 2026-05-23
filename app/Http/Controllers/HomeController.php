<?php

namespace App\Http\Controllers;

use App\Models\User;
use Spatie\Permission\Models\Role;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index()
    {
       // Gate::authorize('seguridad.usuarios.ver');
        $users = User::with('roles')->get(); // Cargar los roles de cada usuario
        $stats = [
           'total' => User::count(),
           'admins' => User::role('super_admin')->count(), // Ajusta según el nombre de tu rol administrador
            'sin_rol' => User::doesntHave('roles')->count(),
            'roles_activos' => Role::count(),
        ];
        return view('dashboard.index', compact('users', 'stats'));
    }
}
