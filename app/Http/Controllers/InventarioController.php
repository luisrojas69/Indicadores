<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Erp\Contracts\ErpConnectionInterface;
use App\Exports\InventarioExport;
use App\Services\Inventario\AuditoriaService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;
use App\Support\CacheHelper;

/**
 * InventarioController
 *
 * Módulo de Inventario y Auditoría Anti-Fugas.
 *
 * Vistas:
 *   index()       → Hub de navegación con KPIs rápidos de todos los sub-módulos
 *   stockCritico()→ Tabla de artículos bajo stock mínimo, con nivel de urgencia
 *   entradas()    → Cruce órdenes de compra vs entradas recibidas
 *   salidas()     → Ajustes de inventario no comerciales + timeline + ranking
 *   reporte()     → Reporte consolidado exportable (Excel)
 */
class InventarioController extends Controller
{
    public function __construct(
        private readonly ErpConnectionInterface $erp,
        private readonly AuditoriaService       $auditoria,
    ) {}

    /*
    |--------------------------------------------------------------------------
    | Hub de Inventario
    |--------------------------------------------------------------------------
    */

    public function index(): View
    {
        $hoy = now()->toDateString();
        $mesDesde = now()->startOfMonth()->toDateString();

        // KPIs rápidos para las cards del hub — todos desde caché
        $stockArray = CacheHelper::rememberArray(
            'inventario:stock_critico',
            config('cache_ttl.stock_critico', 120),
            fn () => $this->erp->getStockCritico()
        );
        $stockData = collect($stockArray);

        $salidasArray = CacheHelper::rememberArray(
            "inventario:salidas:{$mesDesde}:{$hoy}",
            config('cache_ttl.salidas_no_comerciales', 600),
            fn () => $this->erp->getSalidasNoComerciales($mesDesde, $hoy)
        );
        $salidasData = collect($salidasArray);

        $entradasArray = CacheHelper::rememberArray(
            "inventario:entradas:{$mesDesde}:{$hoy}",
            config('cache_ttl.entradas_vs_compras', 600),
            fn () => $this->erp->getEntradasVsCompras($mesDesde, $hoy)
        );
        $entradasData = collect($entradasArray);

        $stockEnriquecido    = $this->auditoria->enriquecerStockCritico($stockData);
        $nivelesStock        = $this->auditoria->conteoStockNiveles($stockEnriquecido);

        $salidasClasificadas = $this->auditoria->clasificarSalidas($salidasData);
        $costoTotalSalidas   = $salidasClasificadas->sum('costo_estimado');

        $entradasEnriquecidas= $this->auditoria->enriquecerEntradas($entradasData);
        $nivelesEntradas     = $this->auditoria->conteoEntradas($entradasEnriquecidas);

        return view('inventario.index', compact(
            'nivelesStock',
            'costoTotalSalidas',
            'nivelesEntradas',
            'mesDesde',
            'hoy',
        ));
    }

    /*
    |--------------------------------------------------------------------------
    | Stock Crítico
    |--------------------------------------------------------------------------
    */

    public function stockCritico(Request $request): View
    {
        $filtroNivel = $request->input('nivel', 'todos');
        $search      = $request->input('search', '');

        $rawArray = CacheHelper::rememberArray(
            'inventario:stock_critico',
            config('cache_ttl.stock_critico', 120),
            fn () => $this->erp->getStockCritico()
        );
        $raw = collect($rawArray);

        $stock   = $this->auditoria->enriquecerStockCritico($raw);
        $niveles = $this->auditoria->conteoStockNiveles($stock);

        // Filtrado
        $stockFiltrado = $stock;
        if ($filtroNivel !== 'todos') {
            $stockFiltrado = $stock->where('nivel', $filtroNivel)->values();
        }
        if ($search) {
            $s = mb_strtolower($search);
            $stockFiltrado = $stockFiltrado->filter(
                fn ($i) => str_contains(mb_strtolower($i['descripcion']), $s)
                        || str_contains(mb_strtolower($i['codigo']),      $s)
            )->values();
        }

        // Chart: artículos críticos top 10 por déficit
        $chartLabels = $stock->where('nivel', 'critico')
            ->take(10)
            ->map(fn ($i) => mb_strimwidth($i['descripcion'], 0, 22, '…'))
            ->values()->toJson();

        $chartDeficit = $stock->where('nivel', 'critico')
            ->take(10)
            ->pluck('deficit')
            ->values()->toJson();

        return view('inventario.stock_critico', compact(
            'stock',
            'stockFiltrado',
            'niveles',
            'filtroNivel',
            'search',
            'chartLabels',
            'chartDeficit',
        ));
    }

    /*
    |--------------------------------------------------------------------------
    | Entradas vs Compras
    |--------------------------------------------------------------------------
    */

    public function entradas(Request $request): View
    {
        [$from, $to] = $this->resolveRange($request);
        $filtroAlerta = $request->input('alerta', 'todos');
        $search       = $request->input('search', '');

        $rawArray = CacheHelper::rememberArray(
            "inventario:entradas:{$from}:{$to}",
            config('cache_ttl.entradas_vs_compras', 600),
            fn () => $this->erp->getEntradasVsCompras($from, $to)
        );
        $raw = collect($rawArray);

        $entradas  = $this->auditoria->enriquecerEntradas($raw);
        $conteo    = $this->auditoria->conteoEntradas($entradas);

        // Filtrado
        $entradasFiltradas = $entradas;
        if ($filtroAlerta !== 'todos') {
            $entradasFiltradas = $entradas->where('alerta', $filtroAlerta)->values();
        }
        if ($search) {
            $s = mb_strtolower($search);
            $entradasFiltradas = $entradasFiltradas->filter(
                fn ($i) => str_contains(mb_strtolower($i['articulo_descripcion']), $s)
                        || str_contains(mb_strtolower($i['numero_orden']),          $s)
                        || str_contains(mb_strtolower($i['proveedor']),             $s)
            )->values();
        }

        // KPIs de discrepancia
        $totalOrdenado  = $entradas->sum('cantidad_ordenada');
        $totalRecibido  = $entradas->sum('cantidad_recibida');
        $totalDiferencia= $entradas->sum('diferencia');
        $pctCumplimiento= $totalOrdenado > 0
            ? round($totalRecibido / $totalOrdenado * 100, 1)
            : 100.0;

        return view('inventario.entradas', compact(
            'entradas',
            'entradasFiltradas',
            'conteo',
            'filtroAlerta',
            'search',
            'totalOrdenado',
            'totalRecibido',
            'totalDiferencia',
            'pctCumplimiento',
            'from',
            'to',
        ));
    }

    /*
    |--------------------------------------------------------------------------
    | Salidas No Comerciales
    |--------------------------------------------------------------------------
    */

    public function salidas(Request $request): View
    {
        [$from, $to] = $this->resolveRange($request);
        $filtroTipo  = $request->input('tipo', 'todos');
        $search      = $request->input('search', '');

        $rawArray = CacheHelper::rememberArray(
            "inventario:salidas:{$from}:{$to}",
            config('cache_ttl.salidas_no_comerciales', 600),
            fn () => $this->erp->getSalidasNoComerciales($from, $to)
        );
        $raw = collect($rawArray);

        $salidas   = $this->auditoria->clasificarSalidas($raw);
        $ranking   = $this->auditoria->rankingArticulosSalidas($salidas);
        $timeline  = $this->auditoria->timelinePorFecha($salidas);
        $diasSospechosos = $this->auditoria->detectarDiasSospechosos($salidas);

        // Filtrado
        $salidasFiltradas = $salidas;
        if ($filtroTipo !== 'todos') {
            $salidasFiltradas = $salidas->where('tipo_label', $filtroTipo)->values();
        }
        if ($search) {
            $s = mb_strtolower($search);
            $salidasFiltradas = $salidasFiltradas->filter(
                fn ($i) => str_contains(mb_strtolower($i['articulo_descripcion']), $s)
                        || str_contains(mb_strtolower($i['numero_ajuste']),         $s)
            )->values();
        }

        // KPIs globales
        $costoTotalSalidas = $salidas->sum('costo_estimado');
        $tiposUnicos       = $salidas->pluck('tipo_label')->unique()->sort()->values();
        $conteoTipos       = $salidas->groupBy('tipo_label')
            ->map(fn ($g) => [
                'count' => $g->count(),
                'costo' => $g->sum('costo_estimado'),
                'color' => $g->first()['tipo_color'],
                'icon'  => $g->first()['tipo_icon'],
            ]);

        // Series Chart.js para gráfico de tipos
        $chartTiposLabels = $conteoTipos->keys()->toJson();
        $chartTiposCostos = $conteoTipos->pluck('costo')->values()->toJson();
        $chartTiposColors = $conteoTipos->pluck('color')->values()->toJson();

        return view('inventario.salidas', compact(
            'salidas',
            'salidasFiltradas',
            'ranking',
            'timeline',
            'diasSospechosos',
            'costoTotalSalidas',
            'tiposUnicos',
            'conteoTipos',
            'filtroTipo',
            'search',
            'chartTiposLabels',
            'chartTiposCostos',
            'chartTiposColors',
            'from',
            'to',
        ));
    }

    /*
    |--------------------------------------------------------------------------
    | Reporte Consolidado (exportable)
    |--------------------------------------------------------------------------
    */

    public function reporte(Request $request): View
    {
        [$from, $to] = $this->resolveRange($request);

        // Todos los datos del módulo para el resumen
        $stockRaw    = Cache::remember('inventario:stock_critico',
            config('cache_ttl.stock_critico', 120),
            fn () => $this->erp->getStockCritico());

        $salidasRaw  = Cache::remember("inventario:salidas:{$from}:{$to}",
            config('cache_ttl.salidas_no_comerciales', 600),
            fn () => $this->erp->getSalidasNoComerciales($from, $to));

        $entradasRaw = Cache::remember("inventario:entradas:{$from}:{$to}",
            config('cache_ttl.entradas_vs_compras', 600),
            fn () => $this->erp->getEntradasVsCompras($from, $to));

        $stock    = $this->auditoria->enriquecerStockCritico($stockRaw);
        $salidas  = $this->auditoria->clasificarSalidas($salidasRaw);
        $entradas = $this->auditoria->enriquecerEntradas($entradasRaw);

        $resumen = [
            'stock'    => $this->auditoria->conteoStockNiveles($stock),
            'entradas' => $this->auditoria->conteoEntradas($entradas),
            'salidas'  => [
                'total'       => $salidas->count(),
                'costo_total' => $salidas->sum('costo_estimado'),
                'tipos'       => $salidas->pluck('tipo_label')->unique()->count(),
                'articulos'   => $salidas->pluck('articulo_codigo')->unique()->count(),
            ],
            'generado_en' => now()->format('d/m/Y H:i:s'),
            'periodo'     => ['from' => $from, 'to' => $to],
        ];

        return view('inventario.reporte', compact(
            'resumen', 'stock', 'salidas', 'entradas', 'from', 'to'
        ));
    }

    /**
     * Descarga el reporte consolidado como Excel.
     */
    public function exportarReporte(Request $request): \Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        $this->authorize('inventario.reporte.consolidado.exportar');

        [$from, $to] = $this->resolveRange($request);

        $stock    = $this->auditoria->enriquecerStockCritico($this->erp->getStockCritico());
        $salidas  = $this->auditoria->clasificarSalidas($this->erp->getSalidasNoComerciales($from, $to));
        $entradas = $this->auditoria->enriquecerEntradas($this->erp->getEntradasVsCompras($from, $to));

        $exporter = new InventarioExport($stock, $salidas, $entradas, $from, $to);
        return $exporter->download();
    }

    /*
    |--------------------------------------------------------------------------
    | Helper privado
    |--------------------------------------------------------------------------
    */

    /** @return array{string, string} */
    private function resolveRange(Request $request): array
    {
        try {
            $from = Carbon::parse($request->input('from', now()->startOfMonth()))->toDateString();
            $to   = Carbon::parse($request->input('to',   now()))->toDateString();
            if ($from > $to) throw new \RuntimeException();
        } catch (\Throwable) {
            $from = now()->startOfMonth()->toDateString();
            $to   = now()->toDateString();
        }
        return [$from, $to];
    }
}
