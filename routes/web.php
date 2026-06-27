<?php

declare(strict_types=1);

use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ProfileController;

/*
|--------------------------------------------------------------------------
| Rutas Web — BI Bridge
|--------------------------------------------------------------------------
|
| Todos los grupos protegidos con:
|   - 'auth'           → usuario autenticado (Laravel Breeze/Jetstream/Fortify)
|   - 'erp.connection' → verifica que la conexión al ERP esté viva (Fase 0)
|   - 'verified'       → opcional, si usas verificación de email
|
| Los permisos granulares se verifican dentro de cada controlador
| o con ->middleware('can:permiso') a nivel de ruta.
*/

// Auth routes (Asume el scaffolding de autenticación de Laravel)
Auth::routes();
Route::get('/home', [HomeController::class, 'index'])->name('home');

// ── Ruta raíz → distribuidor de tráfico según permisos ────────────────────
Route::get('/', function () {
    if (!auth()->check()) {
        return redirect()->route('login');
    }

    $user = auth()->user();

    // 1. JERARQUÍA MÁXIMA: Super Admin y Gerencia al Dashboard
    if ($user->hasRole('SUPER_ADMIN') || $user->can('gerencia.dashboard.ver')) {
        return redirect()->route('dashboard.index');
    }

    // 2. OPERATIVO: Cajeros
    if ($user->can('caja.prepedidos.ver')) {
        return redirect()->route('caja.index');
    }

    // 3. OPERATIVO: Vendedores
    if ($user->can('vendedor.catalogo.ver')) {
        return redirect()->route('tablet.catalogo');
    }

    return redirect()->route('home');
});

// ── Grupo principal: autenticado + ERP disponible ─────────────────────────
Route::middleware(['auth', 'erp.connection'])->group(function () {

    //Acerca de:
    Route::get('about', function () {
        return view('about');
    })->name('about');

    // Rutas de Perfil/Configuración (ejemplo)
    Route::get('profile', function () {
        return view('profile');
    })->name('profile');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');

    // ── Dashboard Gerencial ───────────────────────────────────────────────
    Route::prefix('dashboard')->name('dashboard.')->group(function () {

        Route::get('/', [DashboardController::class, 'index'])
            ->name('index')
            ->middleware('can:gerencia.dashboard.ver');

        // Endpoint AJAX para refrescar solo el bloque de KPIs (sin recargar página completa)
        // Lo usaremos en Fase 1.5 para polling silencioso cada N minutos
        Route::get('/kpis', [DashboardController::class, 'kpisJson'])
            ->name('kpis.json')
            ->middleware('can:gerencia.dashboard.ver');

        // Exportar snapshot del dashboard a PDF/Excel (Fase futura)
        // Route::get('/exportar', [DashboardController::class, 'exportar'])
        //     ->name('exportar')
        //     ->middleware('can:gerencia.dashboard.exportar');
    });

    require __DIR__ . '/financiero.php';
    require __DIR__ . '/inventario.php';
    require __DIR__ . '/articulos.php';
    // CARGA DEL MÓDULO DE ADMINISTRACIÓN
    require __DIR__ . '/admin.php';
    // CARGA DEL MÓDULO DE TABLET (FASE 5)
    require __DIR__ . '/tablet.php';
    // CARGA DEL MÓDULO DE VENTAS (FASE 1.5)
    require __DIR__ . '/ventas.php';



    // ── Módulo Financiero (Fase 2) ────────────────────────────────────────
    // Route::prefix('financiero')->name('financiero.')->group(function () {
    //     Route::get('/margenes', [FinancieroController::class, 'margenes'])
    //         ->name('margenes')
    //         ->middleware('can:financiero.margenes.ver');
    // });

    // ── Módulo Inventario (Fase 3) ────────────────────────────────────────
    // Route::prefix('inventario')->name('inventario.')->group(function () {
    //     Route::get('/stock-critico', [InventarioController::class, 'stockCritico'])
    //         ->name('stock.critico')
    //         ->middleware('can:inventario.stock.critico');
    // });

    // ── Módulo Artículos (Fase 4) ─────────────────────────────────────────
    // Route::prefix('articulos')->name('articulos.')->group(function () {
    //     Route::get('/',          [ArticuloController::class, 'index'])->name('index');
    //     Route::get('/{codigo}',  [ArticuloController::class, 'show'])->name('show');
    //     Route::get('/rendimiento',[ArticuloController::class, 'rendimiento'])->name('rendimiento');
    // });

});

// ── Rutas de autenticación (Laravel Breeze o Fortify las genera) ──────────
// require __DIR__.'/auth.php';
