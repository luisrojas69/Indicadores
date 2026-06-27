<?php

declare(strict_types=1);

namespace App\Ai;

/**
 * ToolCatalog
 *
 * Define el catálogo completo de herramientas (tools/functions) que se
 * envían a la API de DeepSeek V3 en cada request.
 *
 * Cada herramienta mapea 1:1 a un método de ErpConnectionInterface.
 * La IA decide qué tool llamar — Laravel ejecuta la consulta de forma
 * segura sin exponer SQL ni lógica de negocio al modelo.
 *
 * Formato: compatible con OpenAI Function Calling spec (que DeepSeek V3 respeta).
 */
class ToolCatalog
{
    /**
     * Retorna el array de tools listo para enviarse a la API.
     * Se puede filtrar por permisos del usuario antes de enviarlo.
     *
     * @param  array<string>  $allowedTools  Si vacío, retorna todos.
     * @return array<int, array<string, mixed>>
     */
    public static function get(array $allowedTools = []): array
    {
        $tools = self::definitions();

        if (empty($allowedTools)) {
            return array_values($tools);
        }

        return array_values(
            array_filter($tools, fn ($t) => in_array($t['function']['name'], $allowedTools, true))
        );
    }

    /**
     * Retorna solo los nombres de todas las herramientas registradas.
     *
     * @return array<string>
     */
    public static function names(): array
    {
        return array_map(fn ($t) => $t['function']['name'], self::definitions());
    }

    /**
     * Verifica si un nombre de tool existe en el catálogo.
     * Usado por el ToolDispatcher como whitelist de seguridad.
     */
    public static function exists(string $name): bool
    {
        return in_array($name, self::names(), true);
    }

    /**
     * Retorna el subconjunto de tools permitidas según los permisos Spatie del usuario.
     *
     * @param  \App\Models\User  $user
     * @return array<int, array<string, mixed>>
     */
    public static function forUser(\App\Models\User $user): array
    {
        $allowed = [];

        // Herramientas gerenciales — disponibles para gerencia y financiero
        if ($user->canAny(['gerencia.dashboard.ver', 'financiero.margenes.ver'])) {
            $allowed[] = 'get_dashboard_kpis';
            $allowed[] = 'get_ranking_vendedores';
            $allowed[] = 'get_top_productos';
            $allowed[] = 'get_cxc_summary';
        }

        // Herramientas financieras
        if ($user->can('financiero.margenes.ver')) {
            $allowed[] = 'get_margenes_por_articulo';
            $allowed[] = 'get_resumen_financiero';
        }

        // Herramientas de inventario
        if ($user->canAny(['inventario.stock.critico', 'inventario.entradas.ver', 'inventario.salidas.auditar'])) {
            $allowed[] = 'get_stock_critico';
        }
        if ($user->can('inventario.entradas.ver')) {
            $allowed[] = 'get_entradas_vs_compras';
        }
        if ($user->can('inventario.salidas.auditar')) {
            $allowed[] = 'get_salidas_no_comerciales';
        }

        return self::get($allowed);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // DEFINICIONES INTERNAS
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * @return array<string, array<string, mixed>>  [tool_name => tool_definition]
     */
    private static function definitions(): array
    {
        return [

            // ── Dashboard / KPIs Generales ────────────────────────────────
            'get_dashboard_kpis' => [
                'type' => 'function',
                'function' => [
                    'name'        => 'get_dashboard_kpis',
                    'description' =>
                        'Obtiene los KPIs principales del dashboard gerencial: monto facturado, ' .
                        'cobranzas del mes, clientes activos y clientes nuevos para un período dado. ' .
                        'Usar cuando el usuario pregunte por ventas generales, facturación total, ' .
                        'resumen del mes o desempeño general del negocio.',
                    'parameters'  => [
                        'type'       => 'object',
                        'properties' => [
                            'date_from' => [
                                'type'        => 'string',
                                'format'      => 'date',
                                'description' => 'Fecha de inicio del período en formato YYYY-MM-DD. ' .
                                                 'Si el usuario dice "este mes", usar el primer día del mes actual.',
                            ],
                            'date_to' => [
                                'type'        => 'string',
                                'format'      => 'date',
                                'description' => 'Fecha de fin del período en formato YYYY-MM-DD. ' .
                                                 'Si el usuario dice "hoy" o "este mes", usar la fecha actual.',
                            ],
                        ],
                        'required' => ['date_from', 'date_to'],
                    ],
                ],
            ],

            // ── Ranking de Vendedores ─────────────────────────────────────
            'get_ranking_vendedores' => [
                'type' => 'function',
                'function' => [
                    'name'        => 'get_ranking_vendedores',
                    'description' =>
                        'Obtiene el ranking de vendedores ordenado por monto facturado y cobranzas. ' .
                        'Incluye nombre, monto facturado, monto cobrado y porcentaje de cobranza. ' .
                        'Usar cuando el usuario pregunte quién vendió más, ranking de vendedores, ' .
                        'desempeño del equipo de ventas o comparativa entre vendedores.',
                    'parameters'  => [
                        'type'       => 'object',
                        'properties' => [
                            'date_from' => [
                                'type'        => 'string',
                                'format'      => 'date',
                                'description' => 'Fecha de inicio del período (YYYY-MM-DD).',
                            ],
                            'date_to' => [
                                'type'        => 'string',
                                'format'      => 'date',
                                'description' => 'Fecha de fin del período (YYYY-MM-DD).',
                            ],
                        ],
                        'required' => ['date_from', 'date_to'],
                    ],
                ],
            ],

            // ── Top Productos ─────────────────────────────────────────────
            'get_top_productos' => [
                'type' => 'function',
                'function' => [
                    'name'        => 'get_top_productos',
                    'description' =>
                        'Obtiene los productos más vendidos por unidades y monto facturado. ' .
                        'Usar cuando el usuario pregunte por productos estrella, qué se vende más, ' .
                        'artículos más populares o top de ventas por producto.',
                    'parameters'  => [
                        'type'       => 'object',
                        'properties' => [
                            'date_from' => [
                                'type'        => 'string',
                                'format'      => 'date',
                                'description' => 'Fecha de inicio del período (YYYY-MM-DD).',
                            ],
                            'date_to' => [
                                'type'        => 'string',
                                'format'      => 'date',
                                'description' => 'Fecha de fin del período (YYYY-MM-DD).',
                            ],
                            'limit' => [
                                'type'        => 'integer',
                                'description' => 'Cantidad de productos a retornar. Por defecto 10. Máximo 20.',
                                'default'     => 10,
                                'minimum'     => 1,
                                'maximum'     => 20,
                            ],
                        ],
                        'required' => ['date_from', 'date_to'],
                    ],
                ],
            ],

            // ── Cuentas por Cobrar ────────────────────────────────────────
            'get_cxc_summary' => [
                'type' => 'function',
                'function' => [
                    'name'        => 'get_cxc_summary',
                    'description' =>
                        'Obtiene el resumen de cuentas por cobrar segmentado por antigüedad: ' .
                        'total CxC, facturas por vencer, vencidas 0-15 días, 16-30 días y 31+ días. ' .
                        'Usar cuando el usuario pregunte por cartera, deudas pendientes, ' .
                        'cuentas por cobrar, facturas vencidas o liquidez.',
                    'parameters'  => [
                        'type'       => 'object',
                        'properties' => [
                            'date_from' => [
                                'type'        => 'string',
                                'format'      => 'date',
                                'description' => 'Fecha de inicio del período (YYYY-MM-DD).',
                            ],
                            'date_to' => [
                                'type'        => 'string',
                                'format'      => 'date',
                                'description' => 'Fecha de fin del período (YYYY-MM-DD).',
                            ],
                        ],
                        'required' => ['date_from', 'date_to'],
                    ],
                ],
            ],

            // ── Márgenes por Artículo ─────────────────────────────────────
            'get_margenes_por_articulo' => [
                'type' => 'function',
                'function' => [
                    'name'        => 'get_margenes_por_articulo',
                    'description' =>
                        'Calcula el margen de rentabilidad por artículo vendido: precio de venta, ' .
                        'costo, margen en monto y porcentaje. ' .
                        'Usar cuando el usuario pregunte por rentabilidad, márgenes de ganancia, ' .
                        'productos más rentables o menos rentables.',
                    'parameters'  => [
                        'type'       => 'object',
                        'properties' => [
                            'date_from' => [
                                'type'        => 'string',
                                'format'      => 'date',
                                'description' => 'Fecha de inicio del período (YYYY-MM-DD).',
                            ],
                            'date_to' => [
                                'type'        => 'string',
                                'format'      => 'date',
                                'description' => 'Fecha de fin del período (YYYY-MM-DD).',
                            ],
                            'cost_field' => [
                                'type'        => 'string',
                                'description' => 'Campo de costo a usar. Valores válidos: ' .
                                                 'COS_PRO_UN (costo promedio local), ' .
                                                 'ULT_COS_UN (último costo local), ' .
                                                 'COS_PRO_OM (costo promedio otra moneda), ' .
                                                 'ULT_COS_OM (último costo otra moneda). ' .
                                                 'Por defecto usar COS_PRO_UN.',
                                'enum'        => ['COS_PRO_UN', 'ULT_COS_UN', 'COS_PRO_OM', 'ULT_COS_OM'],
                                'default'     => 'COS_PRO_UN',
                            ],
                        ],
                        'required' => ['date_from', 'date_to'],
                    ],
                ],
            ],

            // ── Resumen Financiero (Bono) ─────────────────────────────────
            'get_resumen_financiero' => [
                'type' => 'function',
                'function' => [
                    'name'        => 'get_resumen_financiero',
                    'description' =>
                        'Calcula el resumen financiero del período: total facturado, costo total ' .
                        'de lo vendido, ganancia neta y margen neto porcentual. ' .
                        'Usar cuando el usuario pregunte por la ganancia del mes, el bono, ' .
                        'utilidad neta o rentabilidad global del negocio.',
                    'parameters'  => [
                        'type'       => 'object',
                        'properties' => [
                            'date_from' => [
                                'type'        => 'string',
                                'format'      => 'date',
                                'description' => 'Fecha de inicio del período (YYYY-MM-DD).',
                            ],
                            'date_to' => [
                                'type'        => 'string',
                                'format'      => 'date',
                                'description' => 'Fecha de fin del período (YYYY-MM-DD).',
                            ],
                            'cost_field' => [
                                'type'        => 'string',
                                'enum'        => ['COS_PRO_UN', 'ULT_COS_UN', 'COS_PRO_OM', 'ULT_COS_OM'],
                                'default'     => 'COS_PRO_UN',
                                'description' => 'Campo de costo a usar para el cálculo de ganancia.',
                            ],
                        ],
                        'required' => ['date_from', 'date_to'],
                    ],
                ],
            ],

            // ── Stock Crítico ─────────────────────────────────────────────
            'get_stock_critico' => [
                'type' => 'function',
                'function' => [
                    'name'        => 'get_stock_critico',
                    'description' =>
                        'Lista los artículos cuyo stock actual es menor o igual al stock mínimo ' .
                        'configurado. Incluye déficit y stock comprometido. ' .
                        'Usar cuando el usuario pregunte por productos agotados, stock bajo, ' .
                        'qué necesita reposición o alertas de inventario.',
                    'parameters'  => [
                        'type'       => 'object',
                        'properties' => [],
                        'required'   => [],
                    ],
                ],
            ],

            // ── Entradas vs Compras ───────────────────────────────────────
            'get_entradas_vs_compras' => [
                'type' => 'function',
                'function' => [
                    'name'        => 'get_entradas_vs_compras',
                    'description' =>
                        'Cruza las órdenes de compra con las entradas reales al inventario. ' .
                        'Muestra discrepancias: órdenes completas, parciales o sin entrada. ' .
                        'Usar cuando el usuario pregunte por recepciones pendientes, ' .
                        'órdenes de compra incompletas o auditoría de compras.',
                    'parameters'  => [
                        'type'       => 'object',
                        'properties' => [
                            'date_from' => [
                                'type'        => 'string',
                                'format'      => 'date',
                                'description' => 'Fecha de inicio del período (YYYY-MM-DD).',
                            ],
                            'date_to' => [
                                'type'        => 'string',
                                'format'      => 'date',
                                'description' => 'Fecha de fin del período (YYYY-MM-DD).',
                            ],
                        ],
                        'required' => ['date_from', 'date_to'],
                    ],
                ],
            ],

            // ── Salidas No Comerciales ────────────────────────────────────
            'get_salidas_no_comerciales' => [
                'type' => 'function',
                'function' => [
                    'name'        => 'get_salidas_no_comerciales',
                    'description' =>
                        'Lista los ajustes de inventario negativos que no corresponden a ventas: ' .
                        'desperfectos, uso interno, garantías, mermas. ' .
                        'Usar cuando el usuario pregunte por pérdidas de inventario, ' .
                        'ajustes, mermas, desperfectos o auditoría anti-fugas.',
                    'parameters'  => [
                        'type'       => 'object',
                        'properties' => [
                            'date_from' => [
                                'type'        => 'string',
                                'format'      => 'date',
                                'description' => 'Fecha de inicio del período (YYYY-MM-DD).',
                            ],
                            'date_to' => [
                                'type'        => 'string',
                                'format'      => 'date',
                                'description' => 'Fecha de fin del período (YYYY-MM-DD).',
                            ],
                        ],
                        'required' => ['date_from', 'date_to'],
                    ],
                ],
            ],

        ];
    }
}
