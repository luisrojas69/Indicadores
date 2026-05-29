<?php

declare(strict_types=1);

namespace App\Services\Inventario;

use Illuminate\Support\Collection;

/**
 * AuditoriaService
 *
 * Encapsula toda la lógica de auditoría anti-fugas de inventario:
 *
 *   1. Clasificación semántica de ajustes por tipo de movimiento
 *   2. Scoring de riesgo por artículo (frecuencia + monto)
 *   3. Detección de patrones temporales sospechosos
 *   4. Semáforo de stock crítico con tres niveles
 *   5. Enriquecimiento de entradas vs compras con estatus
 *
 * Esta clase NO accede a la BD directamente.
 * Recibe colecciones de ErpConnectionInterface y devuelve datos enriquecidos.
 */
class AuditoriaService
{
    /**
     * Mapa de palabras clave → categoría de movimiento.
     * Las claves son patrones (strtolower) que se buscan en CONCEP_AJ del ERP.
     */
    private const TIPO_MAP = [
        // Pérdidas / daños
        'desperfecto'  => ['label' => 'Desperfecto',    'color' => '#dc2626', 'icon' => 'fa-box-open',            'riesgo' => 3],
        'dañado'       => ['label' => 'Dañado',         'color' => '#dc2626', 'icon' => 'fa-box-open',            'riesgo' => 3],
        'daño'         => ['label' => 'Dañado',         'color' => '#dc2626', 'icon' => 'fa-box-open',            'riesgo' => 3],
        'merma'        => ['label' => 'Merma',           'color' => '#ea580c', 'icon' => 'fa-droplet-slash',       'riesgo' => 3],
        'perdida'      => ['label' => 'Pérdida',         'color' => '#dc2626', 'icon' => 'fa-circle-xmark',        'riesgo' => 4],
        'pérdida'      => ['label' => 'Pérdida',         'color' => '#dc2626', 'icon' => 'fa-circle-xmark',        'riesgo' => 4],
        'robo'         => ['label' => 'Robo',            'color' => '#7f1d1d', 'icon' => 'fa-skull',               'riesgo' => 5],
        'hurto'        => ['label' => 'Hurto',           'color' => '#7f1d1d', 'icon' => 'fa-skull',               'riesgo' => 5],

        // Uso interno
        'uso interno'  => ['label' => 'Uso Interno',    'color' => '#7c3aed', 'icon' => 'fa-screwdriver-wrench',  'riesgo' => 2],
        'consumo'      => ['label' => 'Uso Interno',    'color' => '#7c3aed', 'icon' => 'fa-screwdriver-wrench',  'riesgo' => 2],
        'muestra'      => ['label' => 'Muestra',         'color' => '#0891b2', 'icon' => 'fa-vial',                'riesgo' => 2],
        'exhibición'   => ['label' => 'Exhibición',      'color' => '#0891b2', 'icon' => 'fa-store',               'riesgo' => 1],
        'exhibicion'   => ['label' => 'Exhibición',      'color' => '#0891b2', 'icon' => 'fa-store',               'riesgo' => 1],

        // Garantías / devoluciones
        'garantia'     => ['label' => 'Garantía',        'color' => '#d97706', 'icon' => 'fa-shield-halved',       'riesgo' => 2],
        'garantía'     => ['label' => 'Garantía',        'color' => '#d97706', 'icon' => 'fa-shield-halved',       'riesgo' => 2],
        'devolucion'   => ['label' => 'Devolución',      'color' => '#d97706', 'icon' => 'fa-rotate-left',         'riesgo' => 1],
        'devolución'   => ['label' => 'Devolución',      'color' => '#d97706', 'icon' => 'fa-rotate-left',         'riesgo' => 1],
        'cambio'       => ['label' => 'Cambio/Canje',   'color' => '#d97706', 'icon' => 'fa-arrows-rotate',       'riesgo' => 1],

        // Ajustes manuales
        'ajuste'       => ['label' => 'Ajuste Manual',  'color' => '#64748b', 'icon' => 'fa-sliders',             'riesgo' => 3],
        'corrección'   => ['label' => 'Corrección',     'color' => '#64748b', 'icon' => 'fa-pen-to-square',       'riesgo' => 2],
        'correccion'   => ['label' => 'Corrección',     'color' => '#64748b', 'icon' => 'fa-pen-to-square',       'riesgo' => 2],
        'inventario'   => ['label' => 'Toma Física',    'color' => '#0f172a', 'icon' => 'fa-clipboard-list',      'riesgo' => 2],
        'conteo'       => ['label' => 'Toma Física',    'color' => '#0f172a', 'icon' => 'fa-clipboard-list',      'riesgo' => 2],
    ];

    private const TIPO_DEFAULT = [
        'label'  => 'Sin Clasificar',
        'color'  => '#94a3b8',
        'icon'   => 'fa-circle-question',
        'riesgo' => 2,
    ];

    /*
    |--------------------------------------------------------------------------
    | Clasificación de Movimientos
    |--------------------------------------------------------------------------
    */

    /**
     * Enriquece salidas no comerciales con clasificación semántica y score de riesgo.
     *
     * @param  Collection<int, array<string, mixed>>  $salidas  Datos de ErpConnectionInterface::getSalidasNoComerciales()
     * @return Collection<int, array<string, mixed>>
     */
    public function clasificarSalidas(Collection $salidas): Collection
    {
        return $salidas->map(function (array $item) {
            $tipo = $this->clasificarTipo($item['tipo_movimiento'] ?? '');

            return array_merge($item, [
                'tipo_label'  => $tipo['label'],
                'tipo_color'  => $tipo['color'],
                'tipo_icon'   => $tipo['icon'],
                'riesgo'      => $tipo['riesgo'],
                'riesgo_label'=> $this->riesgoLabel($tipo['riesgo']),
                'riesgo_color'=> $this->riesgoColor($tipo['riesgo']),
                // Costo estimado ya viene del ERP — lo redondeamos
                'costo_estimado' => round((float) ($item['costo_estimado'] ?? 0), 2),
            ]);
        });
    }

    /**
     * Genera el ranking de artículos con más salidas no comerciales.
     * Ordena por costo total estimado (impacto económico real).
     *
     * @param  Collection<int, array<string, mixed>>  $salidasClasificadas
     * @return Collection<int, array<string, mixed>>
     */
    public function rankingArticulosSalidas(Collection $salidasClasificadas): Collection
    {
        return $salidasClasificadas
            ->groupBy('articulo_codigo')
            ->map(function (Collection $grupo) {
                $primero = $grupo->first();
                return [
                    'codigo'          => $primero['articulo_codigo'],
                    'descripcion'     => $primero['articulo_descripcion'],
                    'total_salidas'   => $grupo->count(),
                    'costo_total'     => $grupo->sum('costo_estimado'),
                    'tipos'           => $grupo->pluck('tipo_label')->unique()->values()->toArray(),
                    'riesgo_max'      => $grupo->max('riesgo'),
                    'riesgo_label'    => $this->riesgoLabel($grupo->max('riesgo')),
                    'riesgo_color'    => $this->riesgoColor($grupo->max('riesgo')),
                    'ultimo_movimiento'=> $grupo->sortByDesc('fecha')->first()['fecha'] ?? null,
                ];
            })
            ->sortByDesc('costo_total')
            ->values();
    }

    /**
     * Genera timeline de movimientos agrupados por fecha para la vista de audit trail.
     *
     * @param  Collection<int, array<string, mixed>>  $salidasClasificadas
     * @return Collection<string, Collection>  [fecha => Collection de movimientos]
     */
    public function timelinePorFecha(Collection $salidasClasificadas): Collection
    {
        return $salidasClasificadas
            ->sortByDesc('fecha')
            ->groupBy('fecha');
    }

    /**
     * Detecta días con actividad inusualmente alta (outliers).
     * Un día es "sospechoso" si su costo supera 2x el promedio diario.
     *
     * @param  Collection<int, array<string, mixed>>  $salidasClasificadas
     * @return array<string>  Lista de fechas marcadas como sospechosas
     */
    public function detectarDiasSospechosos(Collection $salidasClasificadas): array
    {
        if ($salidasClasificadas->isEmpty()) {
            return [];
        }

        $porFecha = $salidasClasificadas
            ->groupBy('fecha')
            ->map(fn ($g) => $g->sum('costo_estimado'));

        $promedio = $porFecha->avg();

        if ($promedio <= 0) {
            return [];
        }

        return $porFecha
            ->filter(fn ($total) => $total > ($promedio * 2))
            ->keys()
            ->toArray();
    }

    /*
    |--------------------------------------------------------------------------
    | Stock Crítico
    |--------------------------------------------------------------------------
    */

    /**
     * Enriquece el stock crítico con nivel de urgencia y porcentaje de cobertura.
     *
     * Niveles:
     *   'critico'  → stock = 0 o stock ≤ 20% del mínimo
     *   'bajo'     → stock entre 20% y 80% del mínimo
     *   'alerta'   → stock entre 80% y 100% del mínimo (justo en el umbral)
     *
     * @param  Collection<int, array<string, mixed>>  $stock
     * @return Collection<int, array<string, mixed>>
     */
    public function enriquecerStockCritico(Collection $stock): Collection
    {
        return $stock->map(function (array $item) {
            $actual  = (float) $item['stock_actual'];
            $minimo  = (float) $item['stock_minimo'];
            $comprom = (float) $item['stock_comprometido'];
            $libre   = max(0, $actual - $comprom);

            $pctCubierto = $minimo > 0
                ? min(round($actual / $minimo * 100, 1), 100)
                : 100;

            $nivel = match(true) {
                $actual <= 0              => 'critico',
                $pctCubierto <= 20        => 'critico',
                $pctCubierto <= 80        => 'bajo',
                default                   => 'alerta',
            };

            return array_merge($item, [
                'stock_libre'   => $libre,
                'pct_cubierto'  => $pctCubierto,
                'nivel'         => $nivel,
                'nivel_label'   => match($nivel) {
                    'critico' => 'Crítico',
                    'bajo'    => 'Bajo',
                    default   => 'Alerta',
                },
                'nivel_color'   => match($nivel) {
                    'critico' => '#dc2626',
                    'bajo'    => '#d97706',
                    default   => '#0891b2',
                },
                'nivel_bg'      => match($nivel) {
                    'critico' => '#fee2e2',
                    'bajo'    => '#fef3c7',
                    default   => '#e0f2fe',
                },
            ]);
        })->sortBy('pct_cubierto')->values();
    }

    /**
     * Resumen de conteo por nivel de urgencia.
     *
     * @param  Collection<int, array<string, mixed>>  $stockEnriquecido
     * @return array{critico: int, bajo: int, alerta: int, total: int}
     */
    public function conteoStockNiveles(Collection $stockEnriquecido): array
    {
        return [
            'critico' => $stockEnriquecido->where('nivel', 'critico')->count(),
            'bajo'    => $stockEnriquecido->where('nivel', 'bajo')->count(),
            'alerta'  => $stockEnriquecido->where('nivel', 'alerta')->count(),
            'total'   => $stockEnriquecido->count(),
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Entradas vs Compras
    |--------------------------------------------------------------------------
    */

    /**
     * Enriquece el cruce de entradas vs compras con nivel de alerta.
     *
     * @param  Collection<int, array<string, mixed>>  $entradas
     * @return Collection<int, array<string, mixed>>
     */
    public function enriquecerEntradas(Collection $entradas): Collection
    {
        return $entradas->map(function (array $item) {
            $ordenada  = (float) $item['cantidad_ordenada'];
            $recibida  = (float) $item['cantidad_recibida'];
            $diferencia= (float) $item['diferencia'];
            $pctRecibido = $ordenada > 0 ? round($recibida / $ordenada * 100, 1) : 0;

            $alerta = match(true) {
                $diferencia <= 0        => 'ok',          // Recibió todo o más
                $pctRecibido <= 0       => 'sin_entrada',  // No entró nada
                $pctRecibido < 50       => 'critico',      // Menos de la mitad
                $pctRecibido < 90       => 'parcial',      // Entre 50% y 90%
                default                 => 'leve',         // Entre 90% y 100%
            };

            return array_merge($item, [
                'pct_recibido'  => $pctRecibido,
                'alerta'        => $alerta,
                'alerta_label'  => match($alerta) {
                    'ok'          => 'Completo',
                    'sin_entrada' => 'Sin entrada',
                    'critico'     => 'Crítico',
                    'parcial'     => 'Parcial',
                    default       => 'Leve',
                },
                'alerta_color'  => match($alerta) {
                    'ok'          => '#059669',
                    'sin_entrada' => '#7c3aed',
                    'critico'     => '#dc2626',
                    'parcial'     => '#d97706',
                    default       => '#0891b2',
                },
                'alerta_bg'     => match($alerta) {
                    'ok'          => '#dcfce7',
                    'sin_entrada' => '#ede9fe',
                    'critico'     => '#fee2e2',
                    'parcial'     => '#fef3c7',
                    default       => '#e0f2fe',
                },
            ]);
        });
    }

    /**
     * Resumen del cruce entradas vs compras.
     *
     * @return array{ok: int, sin_entrada: int, critico: int, parcial: int, leve: int, total: int}
     */
    public function conteoEntradas(Collection $entradasEnriquecidas): array
    {
        return [
            'ok'          => $entradasEnriquecidas->where('alerta', 'ok')->count(),
            'sin_entrada' => $entradasEnriquecidas->where('alerta', 'sin_entrada')->count(),
            'critico'     => $entradasEnriquecidas->where('alerta', 'critico')->count(),
            'parcial'     => $entradasEnriquecidas->where('alerta', 'parcial')->count(),
            'leve'        => $entradasEnriquecidas->where('alerta', 'leve')->count(),
            'total'       => $entradasEnriquecidas->count(),
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers privados
    |--------------------------------------------------------------------------
    */

    private function clasificarTipo(string $concepto): array
    {
        $lower = mb_strtolower(trim($concepto));

        foreach (self::TIPO_MAP as $patron => $tipo) {
            if (str_contains($lower, $patron)) {
                return $tipo;
            }
        }

        return self::TIPO_DEFAULT;
    }

    private function riesgoLabel(int $riesgo): string
    {
        return match(true) {
            $riesgo >= 5 => 'Muy Alto',
            $riesgo >= 4 => 'Alto',
            $riesgo >= 3 => 'Medio',
            $riesgo >= 2 => 'Bajo',
            default      => 'Mínimo',
        };
    }

    private function riesgoColor(int $riesgo): string
    {
        return match(true) {
            $riesgo >= 5 => '#7f1d1d',
            $riesgo >= 4 => '#dc2626',
            $riesgo >= 3 => '#d97706',
            $riesgo >= 2 => '#0891b2',
            default      => '#059669',
        };
    }
}
