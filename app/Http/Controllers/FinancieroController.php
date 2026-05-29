<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Erp\Contracts\ErpConnectionInterface;
use App\Services\Financiero\MargenService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Session;
use Illuminate\View\View;

/**
 * FinancieroController
 *
 * Maneja las vistas del módulo financiero:
 *   - margenes()      → tabla de márgenes por artículo con semáforo
 *   - bonos()         → resumen del bono mensual (totales del período)
 *   - setCostField()  → cambia el campo de costo activo en sesión (permiso: financiero.config.costo.editar)
 *
 * La lógica de IVA y cálculo de bono vive en MargenService — el controller
 * solo orquesta: recibe request → pide datos → pasa a servicio → retorna vista.
 */
class FinancieroController extends Controller
{
    public function __construct(
        private readonly ErpConnectionInterface $erp,
        private readonly MargenService          $margenService,
    ) {}

    /*
    |--------------------------------------------------------------------------
    | Vista de Márgenes
    |--------------------------------------------------------------------------
    */

    public function margenes(Request $request): View
    {
        // ── Fechas ────────────────────────────────────────────────────────
        [$from, $to] = $this->resolveRange($request);

        // ── Campo de costo activo ─────────────────────────────────────────
        // Prioridad: sesión del usuario → config/app_client.php → fallback
        $costField = $this->resolveCostField($request);

        // ── IVA ───────────────────────────────────────────────────────────
        $excluirIva = (bool) $request->input('excluir_iva',
            Session::get('financiero_excluir_iva', false)
        );
        Session::put('financiero_excluir_iva', $excluirIva);

        // ── Filtro de semáforo ────────────────────────────────────────────
        $filtroSemaforo = $request->input('semaforo', 'todos'); // todos | verde | amarillo | rojo

        // ── Datos del ERP (con caché) ─────────────────────────────────────
        $cacheKey = "financiero:margenes:{$from}:{$to}:{$costField}";

        $rawMargenes = Cache::remember(
            $cacheKey,
            config('cache_ttl.margenes', 600),
            fn () => $this->erp->getMargenesPorArticulo($from, $to, $costField)
        );

        // ── Enriquecimiento (IVA + semáforo) — no se cachea, depende de parámetros de UI ──
        $margenes = $this->margenService->enriquecerMargenes($rawMargenes, $excluirIva);

        // ── Filtro de semáforo aplicado ───────────────────────────────────
        $margenesFiltradas = $filtroSemaforo !== 'todos'
            ? $margenes->where('semaforo', $filtroSemaforo)->values()
            : $margenes;

        // ── Semáforo global (conteo) ──────────────────────────────────────
        $semaforos = $this->margenService->conteoSemaforo($margenes);

        // ── Datos para gráfico de distribución ───────────────────────────
        $chartDistribucion = [
            'labels' => ['Margen Alto', 'Margen Medio', 'Margen Bajo', 'Negativos'],
            'data'   => [$semaforos['verde'], $semaforos['amarillo'], $semaforos['rojo'], $semaforos['negativos']],
            'colors' => ['#059669', '#d97706', '#dc2626', '#7c3aed'],
        ];

        // ── Config de umbrales (para mostrar en la UI) ───────────────────
        $margenConfig = $this->margenService->getConfig();

        return view('financiero.margenes', compact(
            'margenes',
            'margenesFiltradas',
            'semaforos',
            'chartDistribucion',
            'margenConfig',
            'costField',
            'excluirIva',
            'filtroSemaforo',
            'from',
            'to',
        ));
    }

    /*
    |--------------------------------------------------------------------------
    | Vista de Bono Mensual
    |--------------------------------------------------------------------------
    */

    public function bonos(Request $request): View
    {
        [$from, $to] = $this->resolveRange($request);
        $costField   = $this->resolveCostField($request);
        $excluirIva  = (bool) Session::get('financiero_excluir_iva', false);

        // Datos del ERP
        $cacheKey = "financiero:margenes:{$from}:{$to}:{$costField}";

        $rawMargenes = Cache::remember(
            $cacheKey,
            config('cache_ttl.margenes', 600),
            fn () => $this->erp->getMargenesPorArticulo($from, $to, $costField)
        );

        $resumenErp = Cache::remember(
            "financiero:resumen:{$from}:{$to}:{$costField}",
            config('cache_ttl.margenes', 600),
            fn () => $this->erp->getResumenFinanciero($from, $to, $costField)
        );

        // Enriquecer y calcular bono
        $margenes    = $this->margenService->enriquecerMargenes($rawMargenes, $excluirIva);
        $resumenBono = $this->margenService->calcularResumenBono($margenes, $resumenErp, $excluirIva);

        // Top 10 artículos por margen pct (para el gráfico waterfall visual)
        $topPorMargen  = $margenes->sortByDesc('margen_pct')->take(10)->values();
        $peorPorMargen = $margenes->sortBy('margen_pct')->take(10)->values();

        // Series para Chart.js
        $chartTopLabels  = $topPorMargen->pluck('descripcion')
            ->map(fn ($d) => mb_strimwidth($d, 0, 25, '…'))->toJson();
        $chartTopPct     = $topPorMargen->pluck('margen_pct')->toJson();
        $chartPeorLabels = $peorPorMargen->pluck('descripcion')
            ->map(fn ($d) => mb_strimwidth($d, 0, 25, '…'))->toJson();
        $chartPeorPct    = $peorPorMargen->pluck('margen_pct')->toJson();

        return view('financiero.bonos', compact(
            'resumenBono',
            'margenes',
            'topPorMargen',
            'peorPorMargen',
            'chartTopLabels',
            'chartTopPct',
            'chartPeorLabels',
            'chartPeorPct',
            'costField',
            'excluirIva',
            'from',
            'to',
        ));
    }

    /*
    |--------------------------------------------------------------------------
    | Cambio de campo de costo (desde UI, solo con permiso)
    |--------------------------------------------------------------------------
    */

    public function setCostField(Request $request): RedirectResponse
    {
        $this->authorize('financiero.config.costo.editar');

        $allowed = ['COS_PRO_UN', 'ULT_COS_UN', 'COS_PRO_OM', 'ULT_COS_OM'];
        $field   = $request->input('cost_field', 'COS_PRO_UN');

        if (! in_array($field, $allowed, true)) {
            return back()->with('error', 'Campo de costo no válido.');
        }

        Session::put('financiero_cost_field', $field);

        // Limpiar caché de márgenes para forzar recalculo con el nuevo campo
        Cache::flush(); // En producción usar tags: Cache::tags('financiero')->flush()

        return back()->with('success', "Campo de costo cambiado a: {$field}");
    }

    /*
    |--------------------------------------------------------------------------
    | Exportar márgenes a Excel (delega al ExportController)
    |--------------------------------------------------------------------------
    */

    public function exportarMargenes(Request $request): \Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        $this->authorize('financiero.margenes.exportar');

        [$from, $to] = $this->resolveRange($request);
        $costField   = $this->resolveCostField($request);
        $excluirIva  = (bool) $request->input('excluir_iva', false);

        $rawMargenes = $this->erp->getMargenesPorArticulo($from, $to, $costField);
        $margenes    = $this->margenService->enriquecerMargenes($rawMargenes, $excluirIva);
        $resumenErp  = $this->erp->getResumenFinanciero($from, $to, $costField);
        $resumenBono = $this->margenService->calcularResumenBono($margenes, $resumenErp, $excluirIva);

        $exporter = new \App\Exports\MargenesExport($margenes, $resumenBono, $from, $to, $costField);

        return $exporter->download();
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers privados
    |--------------------------------------------------------------------------
    */

    /**
     * @return array{string, string}  [$from, $to]
     */
    private function resolveRange(Request $request): array
    {
        try {
            $from = Carbon::parse($request->input('from', now()->startOfMonth()))->toDateString();
            $to   = Carbon::parse($request->input('to',   now()))->toDateString();
            if ($from > $to) throw new \RuntimeException('Rango inválido');
        } catch (\Throwable) {
            $from = now()->startOfMonth()->toDateString();
            $to   = now()->toDateString();
        }

        return [$from, $to];
    }

    /**
     * Resuelve el campo de costo activo:
     * request > sesión > config > fallback
     */
    private function resolveCostField(Request $request): string
    {
        $allowed  = ['COS_PRO_UN', 'ULT_COS_UN', 'COS_PRO_OM', 'ULT_COS_OM'];
        $fromReq  = $request->input('cost_field');
        $fromSess = Session::get('financiero_cost_field');
        $fromConf = config('app_client.business.cost_field', 'COS_PRO_UN');

        $field = $fromReq ?? $fromSess ?? $fromConf;

        return in_array($field, $allowed, true) ? $field : 'COS_PRO_UN';
    }
}
