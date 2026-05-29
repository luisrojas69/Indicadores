<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HomeController;
// Nota: Falta el controlador de Baja/Mortalidad (se agregará después)

// Auth routes (Asume el scaffolding de autenticación de Laravel)
Auth::routes();

// Home/Dashboard route
Route::get('/', [HomeController::class, 'index']);
Route::get('/home', [HomeController::class, 'index'])->name('home');

// Rutas protegidas por autenticación
Route::middleware(['auth'])->group(function () {

     /*
    |--------------------------------------------------------------------------
    | Ruta FallBack personalizada para error 404 con AuthUser
    |--------------------------------------------------------------------------*/
    Route::fallback(function () {
        return response()->view('errors.404', [], 404);
    })->middleware('web'); // Apply the 'web' middleware

    Route::fallback(function () {
        return response()->view('errors.403', [], 403);
    })->middleware('web'); // Apply the 'web' middleware

    
    // CARGA DEL MÓDULO DE PRODUCCION
    require __DIR__.'/produccion.php';   

     // CARGA DEL MÓDULO DE areas
    require __DIR__.'/areas.php';

    // CARGA DEL MÓDULO DE ADMINISTRACIÓN
    require __DIR__.'/admin.php';

    // CARGA DEL MÓDULO DE TALLER
    require __DIR__.'/taller.php';

    // CARGA DEL MÓDULO DE agro
    require __DIR__.'/agro.php';

    // CARGA DEL MÓDULO DE liquidacion
    require __DIR__.'/liquidacion.php';

    // CARGA DEL MÓDULO DE costo
    require __DIR__.'/costo.php';

    // CARGA DEL MÓDULO DE pozo
    require __DIR__.'/pozo.php';

      // CARGA DEL MÓDULO DE pozo
    require __DIR__.'/comedor.php';

     // CARGA DEL MÓDULO DE pozo
    require __DIR__.'/medicina.php';

         // CARGA DEL MÓDULO DE pozo
    require __DIR__.'/sistemas.php';
    
    // CARGA DEL MÓDULO DE PLUVIOMETRIA
    require __DIR__.'/pluviometria.php';

    // CARGA DEL MÓDULO DE PLUVIOMETRIA
    require __DIR__.'/labores.php';

    // CARGA DEL MÓDULO DE ARRIMES
    require __DIR__.'/arrimes.php';




    // Rutas de Perfil/Configuración (ejemplo)
    Route::get('profile', function () { return view('profile'); })->name('profile');
    Route::put('/profile', 'ProfileController@update')->name('profile.update');
    Route::get('about', function () { return view('about'); })->name('about');

});