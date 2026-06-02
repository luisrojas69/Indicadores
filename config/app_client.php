<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Configuración de Instancia del Cliente
|--------------------------------------------------------------------------
| Este archivo define la identidad y comportamiento de UNA instalación
| específica del sistema. No hay tenants en BD. Para un nuevo cliente,
| se despliega una nueva instancia y se editan estas variables de entorno.
|
| Filosofía: toda adaptación por .env o config/, nunca por registros en BD.
|--------------------------------------------------------------------------
*/

return [

    /*
    |--------------------------------------------------------------------------
    | Identidad del Cliente
    |--------------------------------------------------------------------------
    */
    'name'         => env('CLIENT_NAME',         'JellCelulars'),
    'short_name'   => env('CLIENT_SHORT_NAME',   'JellCell'),
    'rif'          => env('CLIENT_RIF',           'J-12345678-0'),
    'logo'         => env('CLIENT_LOGO',          'images/logo.png'),      // relativo a public/
    'logo_dark'    => env('CLIENT_LOGO_DARK',     'images/logo-dark.png'), // variante para sidebar oscuro
    'favicon'      => env('CLIENT_FAVICON',       'favicon.ico'),
    'address'      => env('CLIENT_ADDRESS',       ''),
    'phone'        => env('CLIENT_PHONE',         ''),
    'email'        => env('CLIENT_EMAIL',         ''),

    /*
    |--------------------------------------------------------------------------
    | Branding / Paleta de Colores
    |--------------------------------------------------------------------------
    | Inyectados como CSS variables en el layout base (--brand-primary, etc.)
    | El cliente puede cambiar colores sin tocar una sola línea de Blade/CSS.
    */
    'brand' => [
        'primary'        => env('CLIENT_COLOR_PRIMARY',   '#4e73df'), // Azul SBAdmin2 por defecto
        'primary_dark'   => env('CLIENT_COLOR_PRIMARY_DARK',   '#3a57c5'),
        'secondary'      => env('CLIENT_COLOR_SECONDARY', '#858796'),
        'success'        => env('CLIENT_COLOR_SUCCESS',   '#1cc88a'),
        'warning'        => env('CLIENT_COLOR_WARNING',   '#f6c23e'),
        'danger'         => env('CLIENT_COLOR_DANGER',    '#e74a3b'),
        'sidebar_bg'     => env('CLIENT_SIDEBAR_BG',      '#4e73df'), // Color de fondo del sidebar
    ],

    /*
    |--------------------------------------------------------------------------
    | Localización y Moneda
    |--------------------------------------------------------------------------
    */
    'locale' => [
        'timezone'         => env('CLIENT_TIMEZONE',          'America/Caracas'),
        'language'         => env('CLIENT_LANGUAGE',          'es'),
        'currency_symbol'  => env('CLIENT_CURRENCY_SYMBOL',   '$'),
        'locale_symbol'    => env('CLIENT_LOCALE_SYMBOL',     '$'),
        'currency_code'    => env('CLIENT_CURRENCY_CODE',     'USD'),
        'decimal_places'   => (int) env('CLIENT_DECIMAL_PLACES', 2),
        'decimal_sep'      => env('CLIENT_DECIMAL_SEP',       ','),
        'thousands_sep'    => env('CLIENT_THOUSANDS_SEP',     '.'),
        'date_format'      => env('CLIENT_DATE_FORMAT',       'd/m/Y'),
        'date_format_js'   => env('CLIENT_DATE_FORMAT_JS',    'dd/mm/yyyy'), // Para datepickers JS
    ],

    /*
    |--------------------------------------------------------------------------
    | Módulos Activos
    |--------------------------------------------------------------------------
    | Activa o desactiva módulos enteros sin tocar rutas ni controladores.
    | Un módulo desactivado oculta su menú y devuelve 403 en sus rutas.
    */
    'modules' => [
        'dashboard'  => (bool) env('MODULE_DASHBOARD',  true),
        'financiero' => (bool) env('MODULE_FINANCIERO',  true),
        'inventario' => (bool) env('MODULE_INVENTARIO',  true),
        'articulos'  => (bool) env('MODULE_ARTICULOS',   true),
        'vendedores' => (bool) env('MODULE_VENDEDORES',  true),
        'tablet'     => (bool) env('MODULE_TABLET',      true), // Fase 5 — desactivado por defecto
    ],

    /*
    |--------------------------------------------------------------------------
    | Configuración del Negocio (Reglas Financieras)
    |--------------------------------------------------------------------------
    */
    'business' => [
        // Campo de costo activo para cálculo de márgenes.
        // Opciones: COS_PRO_UN | ULT_COS_UN | COS_PRO_OM | ULT_COS_OM
        'cost_field'        => env('BUSINESS_COST_FIELD', 'COS_PRO_UN'),

        // Tasa de IVA vigente (porcentaje entero: 16 = 16%)
        'iva_rate'          => (float) env('BUSINESS_IVA_RATE', 16),

        // ¿Los precios en Profit ya incluyen IVA?
        'prices_include_iva' => (bool) env('BUSINESS_PRICES_INCLUDE_IVA', false),

        // Umbral de margen mínimo aceptable (%) — por debajo dispara alerta roja
        'margin_alert_red'   => (float) env('BUSINESS_MARGIN_ALERT_RED',    10.0),
        // Umbral de margen moderado (%) — por debajo dispara alerta amarilla
        'margin_alert_yellow' => (float) env('BUSINESS_MARGIN_ALERT_YELLOW', 20.0),

        // Días de antigüedad para clasificar CxC como "crítica"
        'cxc_critical_days'  => (int) env('BUSINESS_CXC_CRITICAL_DAYS', 30),

        // Top N productos en rankings del dashboard
        'dashboard_top_n'    => (int) env('BUSINESS_DASHBOARD_TOP_N', 10),
    ],

    /*
    |--------------------------------------------------------------------------
    | Tipo de ERP Conectado
    |--------------------------------------------------------------------------
    | Determina qué implementación de ErpConnectionInterface usa el sistema.
    | El ErpServiceProvider lee esta clave para resolver el binding correcto.
    |
    | Opciones registradas: 'profit_plus_2k8' | (futuros: 'sap_b1', 'siigo', etc.)
    */
    'erp_driver' => env('ERP_DRIVER', 'profit_plus_2k8'),

    /*
    |--------------------------------------------------------------------------
    | Footer y Metadata del Sistema
    |--------------------------------------------------------------------------
    */
    'system' => [
        'name'        => env('SYSTEM_NAME',    'BI Bridge'),
        'version'     => env('SYSTEM_VERSION', '0.1.0-beta1'),
        'built_by'    => env('SYSTEM_BUILT_BY', 'Ing. Luis Rojas'),
        'support_url' => env('SYSTEM_SUPPORT_URL', 'https://github.com/luisrojas69/'),
    ],

];
