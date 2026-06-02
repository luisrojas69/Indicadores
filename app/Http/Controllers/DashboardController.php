<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Erp\Contracts\ErpConnectionInterface;
use App\Support\CacheHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

/**
 * DashboardController — VERSIÓN CORREGIDA (Fix #1: caché)
 *
 * CAMBIO CLAVE respecto a la versión anterior:
 *   ❌ ANTES: Cache::remember(..., fn() => $this->erp->getTopProductos(...))
 *            → guardaba Collection serializada → __PHP_Incomplete_Class en 2da request
 *
 *   ✅ AHORA: CacheHelper::rememberArray(..., fn() => $this->erp->getTopProductos(...))
 *            → guarda JSON plano → collect($array) después de leer caché
 *
 * PATRÓN A SEGUIR EN TODOS LOS CONTROLLERS:
 *   1. Usar CacheHelper::rememberArray() para listas  → retorna array
 *   2. Usar CacheHelper::rememberAssoc() para KPIs    → retorna array asociativo
 *   3. Convertir a Collection con collect() DESPUÉS de leer, nunca antes
 */
class DashboardController extends Controller
{
    public function __construct(
        private readonly ErpConnectionInterface $erp
    ) {}

    public function index(Request $request): View
    {
        // ── 1. Resolución del rango de fechas ─────────────────────────────
        $dateFrom = $request->input('from', now()->startOfMonth()->toDateString());
        $dateTo   = $request->input('to',   now()->toDateString());

        try {
            $from = Carbon::parse($dateFrom)->toDateString();
            $to   = Carbon::parse($dateTo)->toDateString();
            if ($from > $to) throw new \RuntimeException();
        } catch (\Throwable) {
            $from = now()->startOfMonth()->toDateString();
            $to   = now()->toDateString();
        }

        $prefix = "dashboard:{$from}:{$to}";
        $topN   = config('app_client.business.dashboard_top_n', 10);

        // ── 2. KPIs principales ───────────────────────────────────────────
        // rememberAssoc → retorna array asociativo, nunca Collection
        $kpis = CacheHelper::rememberAssoc(
            "{$prefix}:kpis",
            config('cache_ttl.productos_mas_vendidos', 300),
            fn () => $this->erp->getDashboardKpis($from, $to)
        );

        // ── 3. CxC ────────────────────────────────────────────────────────
        $cxc = CacheHelper::rememberAssoc(
            "{$prefix}:cxc",
            config('cache_ttl.cuentas_por_cobrar', 900),
            fn () => $this->erp->getCuentasPorCobrarSummary($from, $to)
        );

        // ── 4. Top Productos ──────────────────────────────────────────────
        // rememberArray → retorna array plano
        // collect() se llama AQUÍ, después de leer caché — nunca dentro del callback
        $topProductosArray = CacheHelper::rememberArray(
            "{$prefix}:top_productos:{$topN}",
            config('cache_ttl.productos_mas_vendidos', 300),
            fn () => $this->erp->getTopProductos($from, $to, $topN)
        );
        $topProductos = collect($topProductosArray); // ← conversión DESPUÉS de caché

        // ── 5. Ranking Vendedores ─────────────────────────────────────────
        $rankingArray = CacheHelper::rememberArray(
            "{$prefix}:ranking_vendedores",
            config('cache_ttl.ranking_vendedores', 300),
            fn () => $this->erp->getRankingVendedores($from, $to)
        );
        $rankingVendedores = collect($rankingArray); // ← conversión DESPUÉS de caché

        // ── 6. Series para Chart.js ───────────────────────────────────────
        $chartLabels   = $topProductos->pluck('descripcion')
                            ->map(fn ($d) => mb_strimwidth($d, 0, 28, '…'))
                            ->values()->toJson();
        $chartUnidades = $topProductos->pluck('unidades')->values()->toJson();
        $chartMontos   = $topProductos->pluck('monto')->values()->toJson();

        // ── 7. Variaciones ────────────────────────────────────────────────
        $varPct = 0.0;
        if (($kpis['monto_facturado_anterior'] ?? 0) > 0) {
            $varPct = round(
                (($kpis['monto_facturado'] - $kpis['monto_facturado_anterior'])
                    / $kpis['monto_facturado_anterior']) * 100,
                1
            );
        }

        $pctCobranza = 0.0;
        if (($kpis['monto_facturado'] ?? 0) > 0) {
            $pctCobranza = round(
                ($kpis['cobranzas_mes'] / $kpis['monto_facturado']) * 100, 1
            );
        }

        return view('dashboard.index', compact(
            'kpis', 'cxc', 'topProductos', 'rankingVendedores',
            'chartLabels', 'chartUnidades', 'chartMontos',
            'varPct', 'pctCobranza', 'from', 'to',
        ));
    }
}
