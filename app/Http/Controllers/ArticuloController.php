<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Erp\Contracts\ErpConnectionInterface;
use App\Services\Articulos\RendimientoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;
use App\Support\CacheHelper;

/**
 * ArticuloController
 *
 * Módulo de Artículos — Ficha Técnica y Rendimiento.
 *
 * Métodos:
 *   index()       → Listado paginado con búsqueda y filtros
 *   show()        → Ficha 360° de un artículo
 *   rendimiento() → Análisis histórico de ventas con gráficos
 *   search()      → Endpoint JSON para autocomplete (Fase 5 tablet)
 */
class ArticuloController extends Controller
{
    public function __construct(
        private readonly ErpConnectionInterface $erp,
        private readonly RendimientoService     $rendimientoService,
    ) {}

    /*
    |--------------------------------------------------------------------------
    | Listado de artículos
    |--------------------------------------------------------------------------
    */

    public function index(Request $request): View
    {
        $perPage = (int) $request->input('per_page', 20);
        $page    = (int) $request->input('page', 1);
        $filters = [
            'search'    => $request->input('search', ''),
            'categoria' => $request->input('categoria', ''),
        ];

        // No cacheamos el listado paginado con búsqueda para que siempre refleje
        // el estado actual del stock (TTL corto de stock_critico)
        $resultado = $this->erp->getArticulos($filters, $perPage, $page);

        $articulos  = $resultado['data'];
        $total      = $resultado['total'];
        $totalPages = (int) ceil($total / $perPage);

        return view('articulos.index', compact(
            'articulos', 'total', 'totalPages',
            'perPage', 'page', 'filters'
        ));
    }

    /*
    |--------------------------------------------------------------------------
    | Ficha detalle de artículo
    |--------------------------------------------------------------------------
    */

    public function show(string $codigo): View
    {
        // Cache individual por artículo — TTL del catálogo tablet (30min)
        $cacheKey = "articulo:detalle:{$codigo}";

        $articulo = Cache::remember(
            $cacheKey,
            config('cache_ttl.catalogo_tablet', 1800),
            fn () => $this->erp->getArticuloDetalle($codigo)
        );

        if (! $articulo) {
            abort(404, "Artículo {$codigo} no encontrado en el ERP.");
        }

        // Evolución mensual del año actual para el mini-gráfico de la ficha
        $year = (int) now()->year;

        $evolucionArray = CacheHelper::rememberArray(
            "articulo:evolucion:{$codigo}:{$year}",
            config('cache_ttl.catalogo_tablet', 1800),
            fn () => $this->erp->getArticulosEvolucionMensual([$codigo], $year)
        );
        // 1. Buscamos primero por la llave exacta limpiando espacios
        $codigoLimpio = trim($codigo);

        // 2. Si existe la llave la usamos; si no, pero el array tiene datos, tomamos el primer elemento (índice 0)
        if (isset($evolucionArray[$codigoLimpio])) {
            $serieMensual = $evolucionArray[$codigoLimpio];
        } elseif (!empty($evolucionArray) && is_array($evolucionArray)) {
            $serieMensual = reset($evolucionArray); // Extrae el primer elemento (el array de los 12 meses)
        } else {
            $serieMensual = array_fill_keys(range(1, 12), 0.0);
        }

        // Tendencia calculada desde el servicio
        $tendencia     = $this->rendimientoService->calcularTendencia($serieMensual);
        $tendenciaLabel= $this->rendimientoService->tendenciaLabel($tendencia);
        $tendenciaColor= $this->rendimientoService->tendenciaColor($tendencia);
        $tendenciaIcon = $this->rendimientoService->tendenciaIcon($tendencia);

        // Estado de rendimiento para la ficha
        $totalAnio     = array_sum($serieMensual);
        $mesesActivo   = count(array_filter($serieMensual));
        $promedio      = $mesesActivo > 0 ? round($totalAnio / $mesesActivo, 1) : 0;
        $estado        = $this->rendimientoService->clasificarEstado($promedio, $mesesActivo);
        $estadoLabel   = $this->rendimientoService->estadoLabel($estado);
        $estadoColor   = $this->rendimientoService->estadoColor($estado);
        $estadoBg      = $this->rendimientoService->estadoBg($estado);

        // Margen calculado en tiempo real con el campo de costo activo
        $costField     = session('financiero_cost_field',
            config('app_client.business.cost_field', 'COS_PRO_UN'));
        $precioVenta   = (float) ($articulo['precios']['venta1'] ?? 0);
        $costoActivo   = (float) ($articulo['costos_desglose'][$costField] ?? $articulo['costo_activo'] ?? 0);
        $margenPct     = $precioVenta > 0
            ? round(($precioVenta - $costoActivo) / $precioVenta * 100, 2)
            : 0.0;

        // Series para Chart.js del mini-gráfico mensual
        $chartLabels   = json_encode(array_values(['Ene','Feb','Mar','Abr','May','Jun','Jul','Ago','Sep','Oct','Nov','Dic']));
        $chartData     = json_encode(array_values($serieMensual));
        $chartColor    = $this->rendimientoService->estadoColor($estado);

        return view('articulos.show', compact(
            'articulo',
            'serieMensual',
            'tendencia', 'tendenciaLabel', 'tendenciaColor', 'tendenciaIcon',
            'totalAnio', 'mesesActivo', 'promedio',
            'estado', 'estadoLabel', 'estadoColor', 'estadoBg',
            'costField', 'precioVenta', 'costoActivo', 'margenPct',
            'chartLabels', 'chartData', 'chartColor',
            'year',
        ));
    }

    /*
    |--------------------------------------------------------------------------
    | Vista de Rendimiento histórico
    |--------------------------------------------------------------------------
    */

public function rendimiento(Request $request): View
    {
        $year         = (int) $request->input('year', now()->year);
        $filtroEstado = $request->input('estado', 'todos');
        $search       = mb_strtolower($request->input('search', ''));

        // 1. Catálogo enriquecido con totales y meses activos reales
        $todosResultArrays = CacheHelper::rememberArray(
            "articulos:todos_rendimiento:{$year}",
            config('cache_ttl.catalogo_tablet', 1800),
            fn () => $this->erp->getRendimientoGlobal($year)
        );
        $todosResult = collect($todosResultArrays);

        $todosArticulos = $this->rendimientoService->enriquecerArticulos($todosResult, $year);

        // 2. Extraer el Top 6 dinámicamente desde la colección ya procesada
        $topCodigos = $todosArticulos->sortByDesc('total_unidades')->take(6)->pluck('codigo')->toArray();

        // 3. Evolución mensual exclusivamente para los Top 6 (Gráfico de Líneas)
        $evolucionTopArray = CacheHelper::rememberArray(
            "articulos:evolucion_top:{$year}",
            config('cache_ttl.catalogo_tablet', 1800),
            fn () => $this->erp->getArticulosEvolucionMensual($topCodigos, $year)
        );
        $evolucionTop = collect($evolucionTopArray);

        // 4. Cálculos para los Widgets y Gráficos
        $kpis   = $this->rendimientoService->calcularKpis($todosArticulos, $evolucionTop->toArray());
        $conteo = $this->rendimientoService->conteoEstados($todosArticulos);

        $chartLabeles  = $this->rendimientoService->labelesMeses();
        $chartDatasets = $this->rendimientoService->buildChartDatasets(
            $evolucionTop->toArray(),
            $todosArticulos // Pasamos la colección completa para buscar descripciones
        );
        $donutData     = $this->rendimientoService->buildDonutData($todosArticulos);

        // 5. Filtrado final para la Tabla UI
        $articulosFiltrados = $todosArticulos->when($filtroEstado !== 'todos', function ($collection) use ($filtroEstado) {
            return $collection->where('estado', $filtroEstado);
        })->when($search !== '', function ($collection) use ($search) {
            return $collection->filter(fn ($a) =>
                str_contains(mb_strtolower($a['descripcion']), $search) ||
                str_contains(mb_strtolower($a['codigo']), $search)
            );
        })->values();

        $yearsDisponibles = range((int) now()->year, (int) now()->year - 2);

        return view('articulos.rendimiento', compact(
            'kpis', 'conteo', 'articulosFiltrados', 'filtroEstado', 'search',
            'chartLabeles', 'chartDatasets', 'donutData', 'year', 'yearsDisponibles'
        ));
    }

    /*
    |--------------------------------------------------------------------------
    | Búsqueda AJAX para autocomplete (usado en Fase 5 — tablet)
    |--------------------------------------------------------------------------
    */

    public function search(Request $request): JsonResponse
    {
        $term     = $request->input('q', '');
        $resultado= $this->erp->getArticulos(['search' => $term], 15, 1);

        return response()->json([
            'items' => $resultado['data']->map(fn ($a) => [
                'codigo'      => $a['codigo'],
                'descripcion' => $a['descripcion'],
                'stock'       => $a['stock_actual'],
            ])->values(),
        ]);
    }
}
