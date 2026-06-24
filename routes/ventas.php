<?php

/*
|--------------------------------------------------------------------------
| Rutas — Módulo Ventas (Fase 1.5)
|--------------------------------------------------------------------------
| Aquí definimos las rutas relacionadas con el módulo de Ventas, que en su primera fase se centrará en el "Ranking de Vendedores". Esta ruta es accesible
| para usuarios con el permiso 'ventas.ranking.ver'.
| La lógica de negocio se encuentra en VentasController@rankingVendedores, que maneja la extracción de datos, ordenamiento dinámico y cálculos adicionales para enriquecer la vista.
| Desarrollaremos esta sección en fases futuras para incluir más funcionalidades como análisis de clientes, tendencias de ventas, etc.
| Desarrolado por: Ing. Luis Rojas - 2026-06-02
*/

use App\Http\Controllers\VentasController;
use Illuminate\Support\Facades\Route;

Route::prefix('ventas')->name('ventas.')->group(function () {

    Route::get('/', [VentasController::class, 'index'])
        ->name('index')
        ->middleware('can:gerencia.vendedores.ranking.ver');

    // ── Vista de Ranking de Vendedores ───────────────────────────────────────
    Route::get('/ranking', [VentasController::class, 'rankingVendedores'])
        ->name('ranking')
        ->middleware('can:gerencia.vendedores.ranking.ver');

});
