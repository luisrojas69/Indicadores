<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\AuthenticatesUsers;

class LoginController extends Controller
{
    use AuthenticatesUsers;

    public function __construct()
    {
        $this->middleware('guest')->except('logout');
    }

    /**
     * Determina a dónde redirigir al usuario después de un login exitoso.
     */
    protected function redirectTo()
    {
        session()->flash('success', '¡Bienvenido al sistema de indicadores!');

        $user = auth()->user();

    // 1. JERARQUÍA MÁXIMA: Si es Super Admin o Gerente, va al Dashboard
    // Nota: Puedes usar hasRole o evaluar el permiso clave de gerencia primero
    if ($user->hasRole('SUPER_ADMIN') || $user->can('gerencia.dashboard.ver')) {
        return route('dashboard.index');
    }

    // 2. OPERATIVO: Cajeros van a la caja
    if ($user->can('caja.prepedidos.ver')) {
        return route('caja.index');
    }

    // 3. OPERATIVO: Vendedores van a su catálogo
    if ($user->can('vendedor.catalogo.ver')) {
        return route('tablet.catalogo');
    }

        // Fallback: Si es un usuario sin esos permisos específicos, va a home/about
        return route('home');
    }
}