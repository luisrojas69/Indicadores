<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Erp\Contracts\ErpConnectionInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

/**
 * DashboardController
 *
 * Orquesta todos los datos del dashboard gerencial.
 * No contiene lógica de negocio ni queries — delega todo a ErpConnectionInterface.
 * Solo es responsable de: recibir fechas, gestionar caché y pasar datos a la vista.
 */
class DashboardController extends Controller
{
    public function __construct(
        private readonly ErpConnectionInterface $erp
    ) {}

    /**
     * Vista principal del dashboard gerencial.
     */
    public function index(Request $request): View
    {
        // ── 1. Resolución del rango de fechas ─────────────────────────────
        // Por defecto: mes en curso. El selector global del topbar envía ?from= y ?to=
        $dateFrom = $request->input('from', now()->startOfMonth()->toDateString());
        $dateTo   = $request->input('to',   now()->toDateString());

        // Validación básica de fechas — si llegan mal formateadas, usamos el mes actual
        try {
            $from = Carbon::parse($dateFrom)->toDateString();
            $to   = Carbon::parse($dateTo)->toDateString();
            if ($from > $to) {
                $from = now()->startOfMonth()->toDateString();
                $to   = now()->toDateString();
            }
        } catch (\Throwable) {
            $from = now()->startOfMonth()->toDateString();
            $to   = now()->toDateString();
        }

        // ── 2. Clave de caché por período (distintos rangos = distintas keys) ──
        $cachePrefix = "dashboard:{$from}:{$to}";

        // ── 3. KPIs principales ───────────────────────────────────────────
        $kpis = Cache::remember(
            "{$cachePrefix}:kpis",
            config('cache_ttl.productos_mas_vendidos', 300),
            fn () => $this->erp->getDashboardKpis($from, $to)
        );

        // ── 4. Cuentas por Cobrar ─────────────────────────────────────────
        $cxc = Cache::remember(
            "{$cachePrefix}:cxc",
            config('cache_ttl.cuentas_por_cobrar', 900),
            fn () => $this->erp->getCuentasPorCobrarSummary($from, $to)
        );

        // ── 5. Top productos ──────────────────────────────────────────────
        $topN = config('app_client.business.dashboard_top_n', 10);

        $topProductos = Cache::remember(
            "{$cachePrefix}:top_productos:{$topN}",
            config('cache_ttl.productos_mas_vendidos', 300),
            fn () => $this->erp->getTopProductos($from, $to, $topN)
        );

        // ── 6. Ranking de vendedores ──────────────────────────────────────
        $rankingVendedores = Cache::remember(
            "{$cachePrefix}:ranking_vendedores",
            config('cache_ttl.ranking_vendedores', 300),
            fn () => $this->erp->getRankingVendedores($from, $to)
        );

        // ── 7. Datos para el gráfico de barras (Chart.js) ─────────────────
        // Preparamos las series aquí para no poner lógica en Blade
        $chartLabels    = $topProductos->pluck('descripcion')
                            ->map(fn ($d) => mb_strimwidth($d, 0, 28, '…'))
                            ->values()
                            ->toJson();

        $chartUnidades  = $topProductos->pluck('unidades')->values()->toJson();
        $chartMontos    = $topProductos->pluck('monto')->values()->toJson();

        // ── 8. Variación porcentual MoM para las KPI cards ────────────────
        $varPct = 0.0;
        if (($kpis['monto_facturado_anterior'] ?? 0) > 0) {
            $varPct = round(
                (($kpis['monto_facturado'] - $kpis['monto_facturado_anterior'])
                    / $kpis['monto_facturado_anterior']) * 100,
                1
            );
        }

        // ── 9. Calcular porcentaje implícito del CxC ──────────────────────
        // % cobrado = cobranzas / monto facturado
        $pctCobranza = 0.0;
        if (($kpis['monto_facturado'] ?? 0) > 0) {
            $pctCobranza = round(
                ($kpis['cobranzas_mes'] / $kpis['monto_facturado']) * 100, 1
            );
        }

        return view('dashboard.index', compact(
            'kpis',
            'cxc',
            'topProductos',
            'rankingVendedores',
            'chartLabels',
            'chartUnidades',
            'chartMontos',
            'varPct',
            'pctCobranza',
            'from',
            'to',
        ));
    }
}
