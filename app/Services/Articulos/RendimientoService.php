<?php

declare(strict_types=1);

namespace App\Services\Articulos;

use Illuminate\Support\Collection;

/**
 * RendimientoService
 *
 * Toda la lógica de análisis de rendimiento de artículos:
 *   1. Clasificación de estado: Alto / Medio / Bajo / Sin Rotación
 *   2. KPIs del módulo de rendimiento (totales, promedios, top, mes más activo)
 *   3. Enriquecimiento de la tabla de artículos con métricas calculadas
 *   4. Preparación de series para Chart.js (evolución mensual)
 *   5. Tendencia de un artículo individual (creciendo / estable / cayendo)
 *
 * No toca BD. Recibe colecciones de ErpConnectionInterface.
 */
class RendimientoService
{
    /** Nombres de meses en español para los ejes de Chart.js */
    private const MESES = [
        1=>'Ene', 2=>'Feb', 3=>'Mar', 4=>'Abr', 5=>'May', 6=>'Jun',
        7=>'Jul', 8=>'Ago', 9=>'Sep', 10=>'Oct', 11=>'Nov', 12=>'Dic',
    ];

    /*
    |--------------------------------------------------------------------------
    | Clasificación de estado de rendimiento
    |--------------------------------------------------------------------------
    */

    /**
     * Clasifica el estado de rendimiento de un artículo dado su promedio mensual
     * de ventas y el número de meses en que tuvo movimiento.
     *
     * Umbrales:
     *   Alto        → promedio ≥ 50 uds/mes Y meses_activo ≥ 3
     *   Medio       → promedio ≥ 10 uds/mes O meses_activo ≥ 2
     *   Bajo        → promedio > 0 pero por debajo de los umbrales anteriores
     *   Sin Rotación→ promedio = 0 o meses_activo = 0
     */
    public function clasificarEstado(float $promedioMensual, int $mesesActivo): string
    {
        if ($promedioMensual <= 0 || $mesesActivo === 0) {
            return 'sin_rotacion';
        }
        if ($promedioMensual >= 50 && $mesesActivo >= 3) {
            return 'alto';
        }
        if ($promedioMensual >= 10 || $mesesActivo >= 2) {
            return 'medio';
        }
        return 'bajo';
    }

    public function estadoLabel(string $estado): string
    {
        return match($estado) {
            'alto'         => 'Alto rendimiento',
            'medio'        => 'Rendimiento medio',
            'bajo'         => 'Bajo rendimiento',
            'sin_rotacion' => 'Sin Rotación',
            default        => 'Desconocido',
        };
    }

    public function estadoColor(string $estado): string
    {
        return match($estado) {
            'alto'         => '#059669',
            'medio'        => '#d97706',
            'bajo'         => '#0891b2',
            'sin_rotacion' => '#94a3b8',
            default        => '#94a3b8',
        };
    }

    public function estadoBg(string $estado): string
    {
        return match($estado) {
            'alto'         => '#dcfce7',
            'medio'        => '#fef3c7',
            'bajo'         => '#e0f2fe',
            'sin_rotacion' => '#f1f5f9',
            default        => '#f1f5f9',
        };
    }

    /*
    |--------------------------------------------------------------------------
    | Tendencia individual de artículo
    |--------------------------------------------------------------------------
    */

    /**
     * Determina la tendencia de ventas de un artículo comparando
     * el último mes con movimiento vs el anterior.
     * Retorna: 'subiendo' | 'estable' | 'cayendo' | 'sin_datos'
     */
    public function calcularTendencia(array $evolucionMensual): string
    {
        // Filtrar meses con ventas
        $mesesConVentas = array_filter($evolucionMensual, fn ($v) => $v > 0);

        if (count($mesesConVentas) < 2) {
            return count($mesesConVentas) === 0 ? 'sin_datos' : 'estable';
        }

        $valores = array_values($mesesConVentas);
        $ultimo  = end($valores);
        $anterior= $valores[count($valores) - 2];

        if ($anterior <= 0) {
            return 'subiendo';
        }

        $variacion = (($ultimo - $anterior) / $anterior) * 100;

        return match(true) {
            $variacion > 15  => 'subiendo',
            $variacion < -15 => 'cayendo',
            default          => 'estable',
        };
    }

    public function tendenciaLabel(string $tendencia): string
    {
        return match($tendencia) {
            'subiendo'  => 'Creciendo',
            'cayendo'   => 'Cayendo',
            'estable'   => 'Estable',
            'sin_datos' => 'Sin datos',
            default     => 'Sin datos',
        };
    }

    public function tendenciaColor(string $tendencia): string
    {
        return match($tendencia) {
            'subiendo'  => '#059669',
            'cayendo'   => '#dc2626',
            'estable'   => '#d97706',
            default     => '#94a3b8',
        };
    }

    public function tendenciaIcon(string $tendencia): string
    {
        return match($tendencia) {
            'subiendo'  => 'fa-arrow-trend-up',
            'cayendo'   => 'fa-arrow-trend-down',
            'estable'   => 'fa-minus',
            default     => 'fa-circle-question',
        };
    }

    /*
    |--------------------------------------------------------------------------
    | Enriquecimiento de la colección de artículos
    |--------------------------------------------------------------------------
    */

    /**
     * Enriquece la colección de artículos con:
     *   - promedio_mensual calculado
     *   - meses_activo contados
     *   - estado de rendimiento
     *   - etiquetas y colores listos para la vista
     *
     * @param  Collection<int, array<string,mixed>>  $articulos  Datos de ErpConnectionInterface::getArticulos()
     * @param  int   $year  Año de referencia para calcular promedios
     * @return Collection<int, array<string,mixed>>
     */
    public function enriquecerArticulos(Collection $articulos, int $year): Collection
    {
        $mesesDelAnio = (int) now()->year === $year
            ? (int) now()->month   // Solo meses transcurridos si es el año actual
            : 12;

        return $articulos->map(function (array $item) use ($mesesDelAnio) {
            // Si ya viene con total_unidades desde la query del ERP, usarlo
            $totalUnidades   = (float) ($item['total_unidades']   ?? 0);
            $mesesActivo     = (int)   ($item['meses_activo']     ?? 0);
            $promedioMensual = $mesesActivo > 0
                ? round($totalUnidades / $mesesActivo, 2)
                : 0.0;

            $estado = $this->clasificarEstado($promedioMensual, $mesesActivo);

            return array_merge($item, [
                'promedio_mensual' => $promedioMensual,
                'estado'           => $estado,
                'estado_label'     => $this->estadoLabel($estado),
                'estado_color'     => $this->estadoColor($estado),
                'estado_bg'        => $this->estadoBg($estado),
            ]);
        });
    }

    /*
    |--------------------------------------------------------------------------
    | KPIs del módulo de Rendimiento
    |--------------------------------------------------------------------------
    */

    /**
     * Calcula los 4 KPIs del encabezado del módulo de rendimiento.
     *
     * @param  Collection<int, array<string,mixed>>  $articulosEnriquecidos
     * @param  array<string, array<int,float>>       $evolucionPorCodigo  [codigo => [1..12 => uds]]
     * @return array<string,mixed>
     */
    public function calcularKpis(Collection $articulosEnriquecidos, array $evolucionPorCodigo): array
    {
        $totalVentas   = $articulosEnriquecidos->sum('total_unidades');
        $totalArticulos= $articulosEnriquecidos->count();

        // Promedio mensual global (suma de todos los promedios)
        $promedioMensual = $articulosEnriquecidos->sum('promedio_mensual');

        // Producto top (más unidades)
        $productoTop = $articulosEnriquecidos->sortByDesc('total_unidades')->first();

        // Mes más activo (sumando ventas de TODOS los artículos por mes)
        $ventasPorMes = array_fill(1, 12, 0.0);
        foreach ($evolucionPorCodigo as $serie) {
            foreach ($serie as $mes => $uds) {
                $ventasPorMes[$mes] = ($ventasPorMes[$mes] ?? 0) + $uds;
            }
        }

        $mesMasActivo = array_keys($ventasPorMes, max($ventasPorMes))[0] ?? 1;

        return [
            'total_ventas'         => $totalVentas,
            'promedio_mensual'     => round($promedioMensual, 1),
            'total_articulos'      => $totalArticulos,
            'producto_top'         => $productoTop ? [
                'codigo'      => $productoTop['codigo'],
                'descripcion' => $productoTop['descripcion'],
                'unidades'    => $productoTop['total_unidades'],
            ] : null,
            'mes_mas_activo'       => $mesMasActivo,
            'mes_mas_activo_label' => self::MESES[$mesMasActivo] ?? '—',
            'mes_mas_activo_uds'   => round($ventasPorMes[$mesMasActivo] ?? 0),
            'ventas_por_mes'       => $ventasPorMes,
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Series para Chart.js
    |--------------------------------------------------------------------------
    */

    /**
     * Genera el array de labels de meses para el eje X del gráfico.
     *
     * @return list<string>
     */
    public function labelesMeses(): array
    {
        return array_values(self::MESES);
    }

    /**
     * Convierte la evolución mensual de una lista de artículos en datasets
     * listos para Chart.js, con colores consistentes.
     *
     * @param  array<string, array<int,float>>  $evolucionPorCodigo
     * @param  Collection<int, array<string,mixed>>  $articulos  Para obtener descripción
     * @param  int  $maxSeries  Máximo de series a incluir
     * @return array<int, array<string,mixed>>  Array de datasets Chart.js
     */
    public function buildChartDatasets(
        array $evolucionPorCodigo,
        Collection $articulos,
        int $maxSeries = 6
    ): array {
        $palette = [
            ['line' => '#1a56db', 'fill' => 'rgba(26,86,219,.08)'],
            ['line' => '#059669', 'fill' => 'rgba(5,150,105,.08)'],
            ['line' => '#d97706', 'fill' => 'rgba(217,119,6,.08)'],
            ['line' => '#dc2626', 'fill' => 'rgba(220,38,38,.08)'],
            ['line' => '#7c3aed', 'fill' => 'rgba(124,58,237,.08)'],
            ['line' => '#0891b2', 'fill' => 'rgba(8,145,178,.08)'],
        ];

        $articulosMap = $articulos->keyBy('codigo');
        $datasets     = [];
        $idx          = 0;

        foreach ($evolucionPorCodigo as $codigo => $serie) {
            if ($idx >= $maxSeries) break;

            $descripcion = $articulosMap->get((string) $codigo)['descripcion'] ?? $codigo;
            $label = mb_strimwidth((string) $descripcion, 0, 35, '…');
            $color = $palette[$idx % count($palette)];

            $datasets[] = [
                'label'           => $label,
                'data'            => array_values($serie),
                'borderColor'     => $color['line'],
                'backgroundColor' => $color['fill'],
                'borderWidth'     => 2.5,
                'tension'         => 0.4,
                'fill'            => false,
                'pointRadius'     => 4,
                'pointHoverRadius'=> 7,
                'pointBackgroundColor' => $color['line'],
            ];
            $idx++;
        }

        return $datasets;
    }

    /**
     * Genera datos para el gráfico de distribución donut
     * (distribución de ventas totales entre los top artículos).
     *
     * @param  Collection<int, array<string,mixed>>  $articulosEnriquecidos
     * @param  int  $topN
     * @return array{labels: list<string>, data: list<float>, colors: list<string>}
     */
    public function buildDonutData(Collection $articulosEnriquecidos, int $topN = 8): array
    {
        $palette = [
            '#1a56db','#059669','#d97706','#dc2626',
            '#7c3aed','#0891b2','#ea580c','#64748b',
        ];

        $top    = $articulosEnriquecidos->sortByDesc('total_unidades')->take($topN);
        $resto  = $articulosEnriquecidos->sortByDesc('total_unidades')->slice($topN)->sum('total_unidades');


        $labels = $top->map(fn ($a) => mb_strimwidth((string) $a['descripcion'], 0, 28, '…'))->values()->toArray();
        $data   = $top->pluck('total_unidades')->values()->map(fn ($v) => (float) $v)->toArray();
        $colors = array_slice($palette, 0, $top->count());

        if ($resto > 0) {
            $labels[] = 'Otros';
            $data[]   = $resto;
            $colors[] = '#94a3b8';
        }

        return compact('labels', 'data', 'colors');
    }

    /**
     * Conteo de artículos por estado (para el filtro de la tabla).
     *
     * @return array{alto:int, medio:int, bajo:int, sin_rotacion:int, total:int}
     */
    public function conteoEstados(Collection $articulosEnriquecidos): array
    {
        return [
            'alto'         => $articulosEnriquecidos->where('estado','alto')->count(),
            'medio'        => $articulosEnriquecidos->where('estado','medio')->count(),
            'bajo'         => $articulosEnriquecidos->where('estado','bajo')->count(),
            'sin_rotacion' => $articulosEnriquecidos->where('estado','sin_rotacion')->count(),
            'total'        => $articulosEnriquecidos->count(),
        ];
    }
}
