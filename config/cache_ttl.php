<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Tiempos de Vida de Caché por Módulo — TTL en segundos
|--------------------------------------------------------------------------
| Centraliza todos los TTL para facilitar ajuste sin tocar código de negocio.
| Redis es el driver esperado. Todos los valores son enteros (segundos).
|
| Referencia rápida:
|   120  s =  2 minutos
|   300  s =  5 minutos
|   600  s = 10 minutos
|   900  s = 15 minutos
|   1800 s = 30 minutos
*/

return [

    // ── Dashboard Gerencial ────────────────────────────────────────────────
    'productos_mas_vendidos' => (int) env('TTL_PRODUCTOS_MAS_VENDIDOS', 300),
    'ranking_vendedores'     => (int) env('TTL_RANKING_VENDEDORES',     300),
    'cuentas_por_cobrar'     => (int) env('TTL_CUENTAS_POR_COBRAR',     900),
    'cuentas_por_pagar'      => (int) env('TTL_CUENTAS_POR_PAGAR',      900),

    // ── Módulo Financiero ──────────────────────────────────────────────────
    'margenes'               => (int) env('TTL_MARGENES',               600),

    // ── Módulo Inventario / Auditoría ──────────────────────────────────────
    'stock_critico'          => (int) env('TTL_STOCK_CRITICO',          120),
    'entradas_vs_compras'    => (int) env('TTL_ENTRADAS_VS_COMPRAS',    600),
    'salidas_no_comerciales' => (int) env('TTL_SALIDAS_NO_COMERCIALES', 600),

    // ── Módulo Tablet / Catálogo ───────────────────────────────────────────
    'catalogo_tablet'        => (int) env('TTL_CATALOGO_TABLET',       1800),

];
