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
        'articulo'       => env('PROFIT_TABLE_ARTICULO',       'saArticulo'),
        'factura_enc'    => env('PROFIT_TABLE_FACTURA_ENC',    'saFactura'),
        'factura_det'    => env('PROFIT_TABLE_FACTURA_DET',    'saItemFac'),
        'compra_enc'     => env('PROFIT_TABLE_COMPRA_ENC',     'saOrdenCompra'),
        'compra_det'     => env('PROFIT_TABLE_COMPRA_DET',     'saItemOCompra'),
        'cliente'        => env('PROFIT_TABLE_CLIENTE',        'saCliente'),
        'vendedor'       => env('PROFIT_TABLE_VENDEDOR',       'saVendedor'),
        'proveedor'      => env('PROFIT_TABLE_PROVEEDOR',      'saProveedor'),
        'ajuste_inv_enc' => env('PROFIT_TABLE_AJUSTE_INV_ENC', 'saAj_Inv'),
        'ajuste_inv_det' => env('PROFIT_TABLE_AJUSTE_INV_DET', 'saItem_Aj'),
        'stock_almacen'  => env('PROFIT_TABLE_STOCK_ALMACEN',  'saStockAlmacen'),
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
            'stock_minimo'        => 'sto_min',   // Configurable por negocio
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
