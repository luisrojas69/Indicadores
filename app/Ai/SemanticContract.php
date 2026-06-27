<?php

declare(strict_types=1);

namespace App\Ai;

/**
 * SemanticContract
 *
 * Define el vocabulario completo del fallback semántico.
 * Cuando la IA no puede resolver una consulta con un tool_call directo,
 * genera un JSON de intención estructurado. Este contrato define qué
 * combinaciones de {entity, filters, metrics} son válidas y seguras.
 *
 * También provee el prompt de sistema que instruye a la IA sobre cómo
 * generar ese JSON — de forma que Laravel pueda resolverlo sin SQL libre.
 *
 * Flujo:
 *   Usuario: "¿Cuánto vendimos en efectivo la semana pasada?"
 *   IA (tool_call): ninguna tool encaja perfectamente
 *   IA (fallback): genera JSON de intención
 *   SemanticContract::validate($json): verifica que sea seguro
 *   SemanticResolver::resolve($intent): construye la query segura
 */
class SemanticContract
{
    // ─────────────────────────────────────────────────────────────────────────
    // ENTIDADES VÁLIDAS
    // Representan las áreas de datos del ERP que el sistema conoce.
    // ─────────────────────────────────────────────────────────────────────────

    public const ENTITIES = [
        'sales'      => 'Ventas y facturación (tabla factura + reng_fac)',
        'inventory'  => 'Inventario y stock (tabla art + st_almac)',
        'collections'=> 'Cobranzas y CxC (tabla cobros + reng_cob + docum_cc)',
        'vendors'    => 'Vendedores y su desempeño (tabla vendedor)',
        'products'   => 'Artículos del catálogo (tabla art)',
        'purchases'  => 'Compras y órdenes (tabla ordenes + reng_ord)',
        'adjustments'=> 'Ajustes de inventario (tabla ajuste + reng_aju)',
        'margins'    => 'Márgenes y rentabilidad (cruce reng_fac + art)',
    ];

    // ─────────────────────────────────────────────────────────────────────────
    // FILTROS VÁLIDOS
    // Cada filtro tiene sus valores permitidos para evitar inyección.
    // ─────────────────────────────────────────────────────────────────────────

    public const FILTERS = [
        'date' => [
            'description' => 'Rango de fechas de la consulta.',
            'allowed_values' => [
                'today'        => 'Solo el día de hoy',
                'yesterday'    => 'Solo ayer',
                'this_week'    => 'Semana en curso (lunes a hoy)',
                'last_week'    => 'Semana anterior completa',
                'this_month'   => 'Mes en curso',
                'last_month'   => 'Mes anterior completo',
                'this_quarter' => 'Trimestre en curso',
                'this_year'    => 'Año en curso',
                'custom'       => 'Rango personalizado — requiere date_from y date_to',
            ],
        ],
        'date_from' => [
            'description'    => 'Fecha de inicio para filtro custom (YYYY-MM-DD).',
            'allowed_values' => [], // Validado por formato, no por enum
            'format'         => 'date',
        ],
        'date_to' => [
            'description'    => 'Fecha de fin para filtro custom (YYYY-MM-DD).',
            'allowed_values' => [],
            'format'         => 'date',
        ],
        'status' => [
            'description' => 'Estado del documento.',
            'allowed_values' => [
                'active'    => 'Documentos vigentes (no anulados)',
                'cancelled' => 'Documentos anulados',
                'all'       => 'Todos los documentos',
                'pending'   => 'Pendientes de completar (compras parciales)',
                'complete'  => 'Completados al 100%',
            ],
        ],
        'vendor_code' => [
            'description'    => 'Código de vendedor específico (CO_VEN de Profit).',
            'allowed_values' => [], // Validado como string alfanumérico
            'format'         => 'alphanumeric',
        ],
        'product_code' => [
            'description'    => 'Código de artículo específico (CO_ART de Profit).',
            'allowed_values' => [],
            'format'         => 'alphanumeric',
        ],
        'category' => [
            'description'    => 'Categoría del artículo (CO_CAT de Profit).',
            'allowed_values' => [],
            'format'         => 'alphanumeric',
        ],
        'line' => [
            'description'    => 'Línea/Marca del artículo (CO_LIN de Profit).',
            'allowed_values' => [],
            'format'         => 'alphanumeric',
        ],
        'limit' => [
            'description'    => 'Máximo de registros a retornar.',
            'allowed_values' => [],
            'format'         => 'integer',
            'min'            => 1,
            'max'            => 50,
        ],
    ];

    // ─────────────────────────────────────────────────────────────────────────
    // MÉTRICAS VÁLIDAS
    // Qué quiere calcular/ver el usuario sobre la entidad.
    // ─────────────────────────────────────────────────────────────────────────

    public const METRICS = [
        'total'       => 'Suma total en monto',
        'count'       => 'Conteo de registros',
        'average'     => 'Promedio',
        'percentage'  => 'Porcentaje o ratio',
        'ranking'     => 'Ordenado de mayor a menor',
        'trend'       => 'Evolución en el tiempo (mensual)',
        'deficit'     => 'Diferencia negativa (ej: stock vs mínimo)',
        'margin'      => 'Margen de rentabilidad',
        'pending'     => 'Lo que está pendiente o sin completar',
        'critical'    => 'Alertas y casos críticos',
    ];

    // ─────────────────────────────────────────────────────────────────────────
    // COMBINACIONES SOPORTADAS
    // Mapa de qué combinaciones {entity + metric} tiene resolución real.
    // Si no está aquí, la respuesta será "no soportado aún".
    // ─────────────────────────────────────────────────────────────────────────

    public const SUPPORTED_COMBINATIONS = [
        'sales' => [
            'total'      => 'get_dashboard_kpis',
            'count'      => 'get_dashboard_kpis',
            'ranking'    => 'get_ranking_vendedores',
            'trend'      => null,  // Futuro: evolución mensual de ventas
            'average'    => 'get_dashboard_kpis',
        ],
        'inventory' => [
            'critical'   => 'get_stock_critico',
            'deficit'    => 'get_stock_critico',
            'count'      => 'get_stock_critico',
            'ranking'    => 'get_top_productos',
            'total'      => null,  // Futuro: valor total del inventario
        ],
        'collections' => [
            'total'      => 'get_cxc_summary',
            'pending'    => 'get_cxc_summary',
            'percentage' => 'get_ranking_vendedores', // % cobranza por vendedor
            'ranking'    => 'get_ranking_vendedores',
        ],
        'vendors' => [
            'ranking'    => 'get_ranking_vendedores',
            'total'      => 'get_ranking_vendedores',
            'percentage' => 'get_ranking_vendedores',
        ],
        'products' => [
            'ranking'    => 'get_top_productos',
            'total'      => 'get_top_productos',
            'margin'     => 'get_margenes_por_articulo',
            'critical'   => 'get_stock_critico',
        ],
        'purchases' => [
            'pending'    => 'get_entradas_vs_compras',
            'total'      => 'get_entradas_vs_compras',
            'count'      => 'get_entradas_vs_compras',
        ],
        'adjustments' => [
            'total'      => 'get_salidas_no_comerciales',
            'count'      => 'get_salidas_no_comerciales',
            'ranking'    => 'get_salidas_no_comerciales',
        ],
        'margins' => [
            'total'      => 'get_resumen_financiero',
            'margin'     => 'get_margenes_por_articulo',
            'ranking'    => 'get_margenes_por_articulo',
            'percentage' => 'get_resumen_financiero',
        ],
    ];

    // ─────────────────────────────────────────────────────────────────────────
    // VALIDACIÓN DEL JSON DE INTENCIÓN
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Valida que un JSON de intención generado por la IA sea seguro y completo.
     *
     * @param  array<string, mixed>  $intent
     * @return array{valid: bool, errors: array<string>, tool: string|null}
     */
    public static function validate(array $intent): array
    {
        $errors = [];

        // 1. Campos obligatorios
        foreach (['intent', 'entity', 'metrics', 'confidence'] as $field) {
            if (empty($intent[$field])) {
                $errors[] = "Campo obligatorio ausente: {$field}";
            }
        }

        if (!empty($errors)) {
            return ['valid' => false, 'errors' => $errors, 'tool' => null];
        }

        // 2. El intent debe ser 'custom_query' para el fallback
        if ($intent['intent'] !== 'custom_query') {
            $errors[] = "El campo 'intent' debe ser 'custom_query'. Recibido: {$intent['intent']}";
        }

        // 3. Entidad válida
        if (!array_key_exists($intent['entity'], self::ENTITIES)) {
            $validEntities = implode(', ', array_keys(self::ENTITIES));
            $errors[] = "Entidad inválida: '{$intent['entity']}'. Válidas: {$validEntities}";
        }

        // 4. Métricas válidas (debe ser array)
        if (!is_array($intent['metrics'])) {
            $errors[] = "El campo 'metrics' debe ser un array.";
        } else {
            foreach ($intent['metrics'] as $metric) {
                if (!array_key_exists($metric, self::METRICS)) {
                    $validMetrics = implode(', ', array_keys(self::METRICS));
                    $errors[] = "Métrica inválida: '{$metric}'. Válidas: {$validMetrics}";
                }
            }
        }

        // 5. Filtros válidos (si existen)
        if (!empty($intent['filters']) && is_array($intent['filters'])) {
            foreach ($intent['filters'] as $filterKey => $filterVal) {
                if (!array_key_exists($filterKey, self::FILTERS)) {
                    $errors[] = "Filtro inválido: '{$filterKey}'.";
                    continue;
                }

                $filterDef = self::FILTERS[$filterKey];

                // Validar valores de enum si aplica
                if (!empty($filterDef['allowed_values']) && !array_key_exists($filterVal, $filterDef['allowed_values'])) {
                    $allowed = implode(', ', array_keys($filterDef['allowed_values']));
                    $errors[] = "Valor inválido para filtro '{$filterKey}': '{$filterVal}'. Válidos: {$allowed}";
                }

                // Validar formato de fecha
                if (($filterDef['format'] ?? '') === 'date') {
                    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', (string)$filterVal)) {
                        $errors[] = "Formato de fecha inválido para '{$filterKey}': debe ser YYYY-MM-DD.";
                    }
                }

                // Validar alfanumérico (prevención de inyección)
                if (($filterDef['format'] ?? '') === 'alphanumeric') {
                    if (!preg_match('/^[a-zA-Z0-9\s\-_\.]+$/', (string)$filterVal)) {
                        $errors[] = "Valor no permitido para '{$filterKey}': solo caracteres alfanuméricos.";
                    }
                }

                // Validar rango de entero
                if (($filterDef['format'] ?? '') === 'integer') {
                    $intVal = (int)$filterVal;
                    $min    = $filterDef['min'] ?? 1;
                    $max    = $filterDef['max'] ?? 50;
                    if ($intVal < $min || $intVal > $max) {
                        $errors[] = "Valor de '{$filterKey}' fuera de rango: debe estar entre {$min} y {$max}.";
                    }
                }
            }
        }

        // 6. Confianza mínima aceptable
        $confidence = (float)($intent['confidence'] ?? 0);
        if ($confidence < 0.6) {
            $errors[] = "Confianza insuficiente ({$confidence}). Mínimo requerido: 0.60.";
        }

        if (!empty($errors)) {
            return ['valid' => false, 'errors' => $errors, 'tool' => null];
        }

        // 7. Buscar tool que resuelva esta combinación
        $tool = self::resolveTool($intent['entity'], $intent['metrics']);

        return [
            'valid'  => true,
            'errors' => [],
            'tool'   => $tool, // null = soportado en estructura pero sin implementación aún
        ];
    }

    /**
     * Busca el tool que mejor resuelve la combinación entity + metrics.
     * Retorna el nombre del tool o null si no hay implementación.
     */
    public static function resolveTool(string $entity, array $metrics): ?string
    {
        $supported = self::SUPPORTED_COMBINATIONS[$entity] ?? [];
        if (empty($supported)) {
            return null;
        }

        // Buscar la primera métrica que tenga tool asignado
        foreach ($metrics as $metric) {
            if (!empty($supported[$metric])) {
                return $supported[$metric];
            }
        }

        return null;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // PROMPT DE SISTEMA PARA EL FALLBACK
    // Instrucciones que se incluyen en el system prompt de la IA para que
    // genere JSONs de intención compatibles con este contrato.
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Retorna el fragmento de system prompt que instruye a la IA sobre el fallback.
     * Se concatena al system prompt principal en AiChatService.
     */
    public static function fallbackSystemPrompt(): string
    {
        $entities = implode(', ', array_keys(self::ENTITIES));
        $filters  = implode(', ', array_keys(self::FILTERS));
        $metrics  = implode(', ', array_keys(self::METRICS));

        return <<<PROMPT
        Si la consulta del usuario no puede resolverse con ninguna de las herramientas disponibles,
        NO inventes datos ni generes SQL. En su lugar, responde ÚNICAMENTE con un JSON con esta estructura exacta:

        {
          "intent": "custom_query",
          "entity": "<entidad>",
          "filters": {
            "date": "<rango>",
            "date_from": "<YYYY-MM-DD>",
            "date_to": "<YYYY-MM-DD>"
          },
          "metrics": ["<metrica1>", "<metrica2>"],
          "confidence": <0.0-1.0>
        }

        Entidades válidas: {$entities}
        Filtros válidos: {$filters}
        Métricas válidas: {$metrics}

        Reglas estrictas:
        - Responde SOLO con el JSON, sin texto adicional, sin bloques de código markdown.
        - Si el usuario menciona fechas relativas ("ayer", "este mes"), conviértelas a date_from y date_to en formato YYYY-MM-DD.
        - Si no entiendes la consulta o no encaja en ninguna entidad válida, usa confidence menor a 0.6.
        - Nunca incluyas SQL, nombres de tablas de base de datos, ni campos técnicos del ERP en tu respuesta.
        PROMPT;
    }
}
