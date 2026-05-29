<?php

use App\Http\Controllers\ArticuloController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Rutas — Módulo Artículos (Fase 4)
|--------------------------------------------------------------------------
| IMPORTANTE: La ruta /rendimiento debe ir ANTES de /{codigo}
| para que Laravel no confunda "rendimiento" con un código de artículo.
|--------------------------------------------------------------------------
*/

Route::prefix('articulos')->name('articulos.')->group(function () {

    // Listado con búsqueda y paginación
    Route::get('/', [ArticuloController::class, 'index'])
        ->name('index');

    // Vista de rendimiento histórico — DEBE ir antes de /{codigo}
    Route::get('/rendimiento', [ArticuloController::class, 'rendimiento'])
        ->name('rendimiento');

    // Búsqueda AJAX para autocomplete (Fase 5)
    Route::get('/search', [ArticuloController::class, 'search'])
        ->name('search');

    // Ficha detalle de artículo
    Route::get('/{codigo}', [ArticuloController::class, 'show'])
        ->name('show')
        ->where('codigo', '[A-Za-z0-9\-\_\.]+');
});
