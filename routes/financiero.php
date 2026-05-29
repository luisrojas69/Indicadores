<?php

/*
|--------------------------------------------------------------------------
| Rutas — Módulo Financiero (Fase 2)
|--------------------------------------------------------------------------
| Agregar dentro del grupo middleware(['auth', 'erp.connection']) del web.php
| generado en Fase 1.
|
| Pegar este bloque dentro del closure del grupo principal:
|
|   Route::middleware(['auth', 'erp.connection'])->group(function () {
|       // ... rutas dashboard Fase 1 ...
|
|       // ▼ Pegar aquí ▼
|       require __DIR__.'/financiero.php'; // o incluir este bloque directamente
|   });
*/

use App\Http\Controllers\FinancieroController;
use Illuminate\Support\Facades\Route;

Route::prefix('financiero')->name('financiero.')->group(function () {

    // ── Vista de Márgenes ─────────────────────────────────────────────────
    Route::get('/margenes', [FinancieroController::class, 'margenes'])
        ->name('margenes')
        ->middleware('can:financiero.margenes.ver');

    // ── Vista de Bono Mensual ─────────────────────────────────────────────
    Route::get('/bonos', [FinancieroController::class, 'bonos'])
        ->name('bonos')
        ->middleware('can:financiero.reporte.bonos');

    // ── Cambiar campo de costo activo ─────────────────────────────────────
    Route::post('/set-cost-field', [FinancieroController::class, 'setCostField'])
        ->name('set-cost-field')
        ->middleware('can:financiero.config.costo.editar');

    // ── Exportar márgenes a Excel ─────────────────────────────────────────
    Route::get('/margenes/exportar', [FinancieroController::class, 'exportarMargenes'])
        ->name('margenes.exportar')
        ->middleware('can:financiero.margenes.exportar');

});
