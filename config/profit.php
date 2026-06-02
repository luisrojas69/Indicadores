<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Conexión ERP Profit Plus 2K8
    |--------------------------------------------------------------------------
    | Nombre de la conexión secundaria definida en config/database.php.
    | Todos los modelos y repositorios de Profit usarán esta clave.
    */
    'connection' => env('PROFIT_DB_CONNECTION', 'profit'),

    /*
    |--------------------------------------------------------------------------
    | Mapa de Tablas Físicas del ERP
    |--------------------------------------------------------------------------
    | Centraliza los nombres reales de las tablas SQL de Profit Plus 2K8.
    | Actualizar aquí si el cliente trabaja con prefijos o esquemas distintos.
    */
    'tables' => [
        // Maestros
        'articulo'           => env('PROFIT_TABLE_ARTICULO',           'art'),
        'cliente'            => env('PROFIT_TABLE_CLIENTE',            'clientes'),
        'proveedor'          => env('PROFIT_TABLE_PROVEEDOR',          'prov'),
        'vendedor'           => env('PROFIT_TABLE_VENDEDOR',           'vendedor'), // Corregido para 2K8

        // Clasificadores de Inventario
        'categoria_articulo' => env('PROFIT_TABLE_CAT_ARTICULO',       'cat_art'),
        'linea_articulo'     => env('PROFIT_TABLE_LIN_ARTICULO',       'lin_art'),
        'sublinea_articulo'  => env('PROFIT_TABLE_SUBLIN_ARTICULO',    'sub_lin'),
        'color_articulo'     => env('PROFIT_TABLE_COLOR_ARTICULO',     'colores'),

        // Ventas
        'factura_enc'        => env('PROFIT_TABLE_FACTURA_ENC',        'factura'),
        'factura_det'        => env('PROFIT_TABLE_FACTURA_DET',        'reng_fac'), // Corregido a reng_fac

        // Cobranzas y CxC
        'cobro_enc'          => env('PROFIT_TABLE_COBRO_ENC',          'cobros'),
        'cobro_det'          => env('PROFIT_TABLE_COBRO_DET',          'reng_cob'),
        'cxc_docum'          => env('PROFIT_TABLE_CXC_DOCUM',          'docum_cc'),

        // Compras (Órdenes de Compra vs Compras Definitivas)
        'orden_compra_enc'   => env('PROFIT_TABLE_ORDEN_COMPRA_ENC',   'ordenes'),
        'orden_compra_det'   => env('PROFIT_TABLE_ORDEN_COMPRA_DET',   'reng_ord'),
        'factura_compra_enc' => env('PROFIT_TABLE_FACT_COMPRA_ENC',    'compras'),
        'factura_compra_det' => env('PROFIT_TABLE_FACT_COMPRA_DET',    'reng_com'),

        // Inventario y Almacén
        'ajuste_inv_enc'     => env('PROFIT_TABLE_AJUSTE_INV_ENC',     'ajuste'),
        'ajuste_inv_det'     => env('PROFIT_TABLE_AJUSTE_INV_DET',     'reng_aju'),
        'tipo_aju'           => env('PROFIT_TABLE_TIPO_AJU',           'tipo_aju'),
        'stock_almacen'      => env('PROFIT_TABLE_STOCK_ALMACEN',      'st_almac'), // Corregido para 2K8
    ],
    /*
    |--------------------------------------------------------------------------
    | Campos Clave — Diccionario Real Profit Plus 2K8
    |--------------------------------------------------------------------------
    | Nomenclatura extraída del diccionario de datos oficial del cliente.
    | NO modificar sin validar contra la BD de producción de Profit.
    */
    'fields' => [

        // ── Artículo ──────────────────────────────────────────────────────
        'articulo' => [
            'codigo'              => 'CO_ART',    // Character(30) — PK del maestro
            'descripcion'         => 'ART_DES',   // Character(120)
            'stock_actual'        => 'STOCK_ACT',
            'stock_comprometido'  => 'STOCK_COM',
            'stock_minimo'        => 'stock_min',   // Configurable por negocio
        ],

        // ── Costos — Moneda Principal ─────────────────────────────────────
        'costos' => [
            'costo_promedio' => 'COS_PRO_UN',     // Costo Promedio Unitario
            'ultimo_costo'   => 'ULT_COS_UN',     // Último Costo Unitario
        ],

        // ── Costos — Otra Moneda (USD / divisas) ──────────────────────────
        'costos_om' => [
            'costo_promedio_om' => 'COS_PRO_OM',  // Costo Promedio Otra Moneda
            'ultimo_costo_om'   => 'ULT_COS_OM',  // Último Costo Otra Moneda
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Campo de Costo Activo para Cálculo de Márgenes
    |--------------------------------------------------------------------------
    | Define cuál campo de costo usa el módulo financiero por defecto.
    | Puede ser sobreescrito por el usuario desde el panel de configuración.
    |
    | Opciones válidas: 'COS_PRO_UN' | 'ULT_COS_UN' | 'COS_PRO_OM' | 'ULT_COS_OM'
    */
    'cost_field'         => env('PROFIT_COST_FIELD',         'COS_PRO_UN'),
    'foreign_cost_field' => env('PROFIT_FOREIGN_COST_FIELD', 'COS_PRO_OM'),

    /*
    |--------------------------------------------------------------------------
    | Timeouts de Conexión SQL Server
    |--------------------------------------------------------------------------
    | Evita que una consulta lenta bloquee la UI completa.
    | Valores en segundos.
    */
/*
    |--------------------------------------------------------------------------
    | Timeouts de Conexión SQL Server
    |--------------------------------------------------------------------------
    */
    'timeouts' => [
        'connect' => (int) env('PROFIT_CONNECT_TIMEOUT', 5),
        'query'   => (int) env('PROFIT_QUERY_TIMEOUT',   30),
    ],

    /*
    |--------------------------------------------------------------------------
    | Query Logging
    |--------------------------------------------------------------------------
    | En entorno local registra cada consulta ejecutada contra Profit.
    | Se desactiva automáticamente en producción por rendimiento.
    */
    //'query_log_enabled' => (bool) env('PROFIT_QUERY_LOG', app()->environment('local')),

];
