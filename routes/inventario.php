<?php

use App\Http\Controllers\InventarioController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Rutas — Módulo Inventario (Fase 3)
|--------------------------------------------------------------------------
| Pegar dentro del grupo middleware(['auth', 'erp.connection']) en web.php
*/

Route::prefix('inventario')->name('inventario.')->group(function () {

    // Hub de navegación
    Route::get('/', [InventarioController::class, 'index'])
        ->name('index')
        ->middleware('can:inventario.stock.critico');

    // Stock crítico
    Route::get('/stock-critico', [InventarioController::class, 'stockCritico'])
        ->name('stock-critico')
        ->middleware('can:inventario.stock.critico');

    // Entradas vs Compras
    Route::get('/entradas', [InventarioController::class, 'entradas'])
        ->name('entradas')
        ->middleware('can:inventario.entradas.ver');

    // Salidas No Comerciales
    Route::get('/salidas', [InventarioController::class, 'salidas'])
        ->name('salidas')
        ->middleware('can:inventario.salidas.auditar');

    // Reporte consolidado — vista previa
    Route::get('/reporte', [InventarioController::class, 'reporte'])
        ->name('reporte')
        ->middleware('can:inventario.reporte.consolidado.ver');

    // Exportar reporte a Excel
    Route::get('/reporte/exportar', [InventarioController::class, 'exportarReporte'])
        ->name('reporte.exportar')
        ->middleware('can:inventario.reporte.consolidado.exportar');
});
