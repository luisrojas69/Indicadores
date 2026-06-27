<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Configuración del Módulo Tablet / Catálogo
|--------------------------------------------------------------------------
| Centraliza el mapeo de campos de Profit Plus para el catálogo tablet.
| Profit NO tiene un campo "Marca" estándar — usa Línea, Sublínea, Categoría,
| Modelo, Color y campos libres (CAMPO1..CAMPO10).
|
| Adaptar según cómo el cliente específico usa los campos libres.
| Para JellCelulars (smartphones y accesorios):
|   CO_LIN    → Marca comercial (Samsung, Apple, Xiaomi...)
|   CO_SUB_LIN→ Gama (Alta, Media, Básica)
|   CO_CAT    → Categoría (Smartphone, Accesorio, Cargador, Forro)
|   CO_MOD    → Modelo (Galaxy S24, iPhone 15...)
|   CAMPO1    → RAM
|   CAMPO2    → Almacenamiento
|   CAMPO3    → Pantalla
|   CAMPO4    → Batería
|   CAMPO5    → Sistema Operativo
|   CAMPO6    → Color
*/

return [

    /*
    |--------------------------------------------------------------------------
    | Mapeo de Campos del Artículo en Profit
    |--------------------------------------------------------------------------
    | 'alias_sistema' => 'CAMPO_PROFIT'
    | Usado por TabletService para construir las queries dinámicamente.
    */
    'field_map' => [
        'codigo'       => env('TABLET_FIELD_CODIGO',       'CO_ART'),
        'descripcion'  => env('TABLET_FIELD_DESCRIPCION',  'ART_DES'),
        'marca'        => env('TABLET_FIELD_MARCA',        'CO_COLOR'),      // Línea = Marca
        'sublinea'     => env('TABLET_FIELD_SUBLINEA',     'CO_SUBL'),
        'categoria'    => env('TABLET_FIELD_CATEGORIA',    'CO_LIN'),
        'modelo'       => env('TABLET_FIELD_MODELO',       'MODELO'),
        'color'        => env('TABLET_FIELD_COLOR',        'CO_LIN'),
        'precio1'      => 'PREC_VTA1', // Precio de venta nivel 1 (puede ser el precio público o el precio para mayoristas, según la configuración del cliente)
        'precio2'      => 'PREC_VTA2',
        'precio3'      => 'PREC_VTA3',
        'precio4'      => 'PREC_VTA4',
        'stock_actual' => 'STOCK_ACT',
        'stock_min'    => 'STOCK_MIN',
        'stock_com'    => 'STOCK_COM',
        'barras'       => 'REF',
        'proveedor'    => 'CO_PROV',
    ],

    /*
    |--------------------------------------------------------------------------
    | Campos Libres del Artículo (Specs Técnicas)
    |--------------------------------------------------------------------------
    | Profit tiene CAMPO1..CAMPO10 como campos libres por artículo.
    | Aquí se define qué representa cada uno para ESTE cliente.
    | Se muestran en la ficha expandida del catálogo tablet.
    |
    | formato: ['campo_profit' => 'Etiqueta visible', ...]
    | Poner null para desactivar un campo.
    */
    'campos_libres' => [
        'CAMPO1'  => env('TABLET_CAMPO1_LABEL',  'RAM'),
        'CAMPO2'  => env('TABLET_CAMPO2_LABEL',  'Almacenamiento'),
        'CAMPO3'  => env('TABLET_CAMPO3_LABEL',  'Pantalla'),
        'CAMPO4'  => env('TABLET_CAMPO4_LABEL',  'Batería'),
        'CAMPO5'  => env('TABLET_CAMPO5_LABEL',  'Sistema Operativo'),
        'CAMPO6'  => env('TABLET_CAMPO6_LABEL',  'Color'),
        'CAMPO7'  => env('TABLET_CAMPO7_LABEL',  null),  // null = no mostrar
        'CAMPO8'  => env('TABLET_CAMPO8_LABEL',  null),
        'CAMPO9'  => env('TABLET_CAMPO9_LABEL',  null),
        'CAMPO10' => env('TABLET_CAMPO10_LABEL', null),
    ],

    /*
    |--------------------------------------------------------------------------
    | Nombre de la tabla de artículos
    |--------------------------------------------------------------------------
    | Tu query usa 'art' — Profit Plus 2K8 puede usar 'saArticulo' o 'art'
    | dependiendo del esquema. Centralizado aquí para no hardcodear.
    */
    'tabla_articulo' => env('PROFIT_TABLE_ARTICULO',           'art'),

    /*
    |--------------------------------------------------------------------------
    | Nivel de Precio por Defecto para Pre-Pedidos
    |--------------------------------------------------------------------------
    | 1 = PREC1, 2 = PREC2, etc.
    | El vendedor puede cambiarlo por artículo si tiene permiso.
    */
    'precio_nivel_default' => (int) env('TABLET_PRECIO_NIVEL', 1),

    /*
    |--------------------------------------------------------------------------
    | Paginación del Catálogo
    |--------------------------------------------------------------------------
    */
    'catalogo_per_page' => (int) env('TABLET_CATALOGO_PER_PAGE', 24),

    /*
    |--------------------------------------------------------------------------
    | Categorías del Negocio (para los filtros rápidos del catálogo)
    |--------------------------------------------------------------------------
    | Se muestran como tabs/chips en la UI del catálogo.
    | 'valor_en_profit' => 'Etiqueta visible'
    | Dejar vacío para que se carguen dinámicamente desde la BD.
    */
    'categorias' => [
        // Ejemplo JellCelulars — ajustar según los valores reales en CO_CAT de Profit
        //'' => 'Todos',
        '000001' => 'Accesorios Varios',
        '000002' => 'Celulares',
        '000003'  => 'Cargadores',
        '000004'  => 'Audifonos',
        '000005'  => 'Cornetas',
        '000006'  => 'Maquillaje',
        '000007'  => 'Gorras',
        '000008'  => 'Bolsos y Carteras',
        '000009'  => 'Comestics',
        '000010'  => 'Uñas',
        '000011'  => 'Globos',
        '000012'  => 'PERFUMES',
        '000013'  => 'RELOJ',
        '000014'  => 'COMPUTADORAS',
        '000015'  => 'MEMORIAS',
        '000016'  => 'PENDRIVE',
        '000017'  => 'CABLES',
        '000018'  => 'BATERIAS',
        '000019'  => 'CONTROLES',
        '000020'  => 'LENTES',
        '000021'  => 'ESTUCHES',
        '000022'  => 'BISUTERIA',
        '000023'  => 'SERVICIO TECNICO',
        '000024'  => 'TACTIL',
        '000025'  => 'TAPAS TRASERA',
        '000026'  => 'CORREAS',
        '000027'  => 'PANTALLAS',
        '000028'  => 'FLEX Y PINDE CARGA',
        '000029'  => 'MICAS',
        '000030'  => 'ANTI GOLPE',
        '000031'  => 'TELEFONOS',
        '000032'  => 'CAJAS DE REGALOS',
        '000033'  => 'PRUEBA',
        '000034'  => 'REPARACION',
        '000035'  => 'MODEN Y ROUTER',
        '000036'  => 'PRUEBA'
    ],

    /*
    |--------------------------------------------------------------------------
    | Imagen placeholder por categoría
    |--------------------------------------------------------------------------
    | Si un artículo no tiene imagen, se usa el emoji/icono de su categoría.
    */
    'categoria_icons' => [
        '000001'  => '🌏',
        '000002'  => '📱',
        '000003'  => '⚡',
        '000004'  => '🎧',
        '000005'  => '🔊',
        '000006'  => '💄',
        '000007'  => '🧢',
        '000008'  => '👜',
        '000009'  => '🍬',
        '000010'  => '💅',
        '000011'  => '🎈',
        '000012'  => '🌸',
        '000013'  => '⌚',
        '000017'  => '🔌',
        '000018'  => '🔋',
        '000019'  => '🕹️',
        '000020'  => '👓',
        '000021'  => '📱',
        '000022'  => '💍',
        '000024'  => '👆',
        '000025'  => '🧱',
        '000026'  => '🩲',
        '000027'  => '🖥️',
        '000028'  => '🔧',
        '000029'  => '🛡️',
        '000030'  => '💥',
        '000031'  => '📞',
        '000032'  => '🎁',
        '000033'  => '🔬',
        '000034'  => '🔧',
        '000035'  => '🔧',
        '000036'  => '🔬',
        'default'=> '📦',
    ],

];
