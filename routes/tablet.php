<?php

use App\Http\Controllers\CajaController;
use App\Http\Controllers\TabletController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Rutas — Módulo Tablet y Caja (Fase 5)
|--------------------------------------------------------------------------
| Solo activas si config('app_client.modules.tablet') === true
| Agregar dentro del grupo middleware(['auth', 'erp.connection']) en web.php
*/

Route::middleware(['auth', 'erp.connection'])->group(function () {

    // ── Solo si el módulo tablet está activo ──────────────────────────────
    if (config('app_client.modules.tablet', false)) {

        // ── Tablet / Catálogo (vendedores) ────────────────────────────────
        Route::prefix('tablet')->name('tablet.')->middleware('can:vendedor.catalogo.ver')->group(function () {

            Route::get('/',                  [TabletController::class, 'catalogo'])    ->name('catalogo');
            Route::get('/carrito',           [TabletController::class, 'carrito'])     ->name('carrito');
            Route::get('/mis-prepedidos',    [TabletController::class, 'misPrePedidos'])->name('mis_prepedidos');

            // AJAX — ficha de artículo
            Route::get('/articulo/{codigo}', [TabletController::class, 'fichaArticulo'])->name('articulo');

            // AJAX — gestión del carrito
            Route::post('/carrito/agregar',           [TabletController::class, 'agregarAlCarrito'])  ->name('carrito.agregar');
            Route::patch('/carrito/item/{itemId}',    [TabletController::class, 'actualizarCantidad'])->name('carrito.actualizar');
            Route::delete('/carrito/item/{itemId}',   [TabletController::class, 'eliminarItem'])      ->name('carrito.eliminar');
            Route::post('/carrito/enviar-a-caja',     [TabletController::class, 'enviarACaja'])       ->name('carrito.enviar');
        });

        // ── Caja ──────────────────────────────────────────────────────────
        Route::prefix('caja')->name('caja.')->middleware('can:caja.prepedidos.ver')->group(function () {

            Route::get('/',                       [CajaController::class, 'index'])    ->name('index');
            Route::get('/prepedido/{preOrder}',   [CajaController::class, 'detalle'])  ->name('detalle');
            Route::post('/prepedido/{preOrder}/procesar',  [CajaController::class, 'procesar']) ->name('procesar') ->middleware('can:caja.prepedidos.procesar');
            Route::post('/prepedido/{preOrder}/cancelar',  [CajaController::class, 'cancelar']) ->name('cancelar') ->middleware('can:caja.prepedidos.procesar');
        });
    }
});
