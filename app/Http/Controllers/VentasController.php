<?php

namespace App\Http\Controllers;

use App\Support\CacheHelper;
use App\Erp\Contracts\ErpConnectionInterface;

use Illuminate\Http\Request;

class VentasController extends Controller
{
    public function __construct(
        private readonly ErpConnectionInterface $erp,
    ) {}

    public function index()
    {
        return view('ventas.index');
    }

    public function rankingVendedores(Request $request)
    {
        // ── 1. Parámetros de entrada ──────────────────────────────────────────
        $from   = $request->input('from', now()->startOfMonth()->toDateString());
        $to     = $request->input('to',   now()->toDateString());
        $sortBy = $request->input('sort', 'facturado'); // 'facturado' | 'cobrado'

        // Validación mínima de fechas
        try {
            $from = \Carbon\Carbon::parse($from)->toDateString();
            $to   = \Carbon\Carbon::parse($to)->toDateString();
            if ($from > $to) {
                $from = now()->startOfMonth()->toDateString();
            }
        } catch (\Throwable) {
            $from = now()->startOfMonth()->toDateString();
            $to   = now()->toDateString();
        }

        // ── 2. Datos cacheados ────────────────────────────────────────────────
        $rankingArray = CacheHelper::rememberArray(
            "ventas:ranking:{$from}:{$to}",
            config('cache_ttl.ranking_vendedores', 300),
            fn() => $this->erp->getRankingVendedores($from, $to)
        );

        $ranking = collect($rankingArray);

        // ── 3. Reordenar según criterio de competencia ────────────────────────
        $ranking = $sortBy === 'cobrado'
            ? $ranking->sortByDesc('cobranzas_mes')->values()
            : $ranking->sortByDesc('monto_facturado')->values();

        // ── 4. Totales globales del equipo ────────────────────────────────────
        $totalFac = $ranking->sum('monto_facturado');
        $totalCob = $ranking->sum('cobranzas_mes');
        $totalPct = $totalFac > 0 ? round($totalCob / $totalFac * 100, 1) : 0.0;

        // ── 5. Menciones especiales ───────────────────────────────────────────
        $promedio = $ranking->count() > 0 ? $totalFac / $ranking->count() : 0;

        // Francotirador: mayor % de cobranza entre quienes facturen al menos 30% del promedio
        $reyEficiencia = $ranking
            ->where('monto_facturado', '>=', $promedio * 0.3)
            ->sortByDesc('porcentaje_cobranza')
            ->first();

        // Motor comercial: mayor volumen (siempre el primero si sort=facturado)
        $motorComercial = $ranking->sortByDesc('monto_facturado')->first();

        // ── 6. Podio y arena ─────────────────────────────────────────────────
        // Convertimos a array indexado para acceso seguro en Blade sin isset() quirks
        $todos = $ranking->values()->all();   // array PHP plano [0, 1, 2, 3...]
        $podio = array_slice($todos, 0, 3);  // top 3 — puede tener 0, 1, 2 o 3 elementos
        $arena = array_slice($todos, 3);     // el resto

        // ── 7. Porcentajes de participación por vendedor (para barras de progreso) ─
        // Se calcula aquí para no tener lógica en la vista
        $campo = $sortBy === 'cobrado' ? 'cobranzas_mes' : 'monto_facturado';
        $maxValor = $ranking->max($campo) ?: 1;

        $todosConPct = array_map(function ($v) use ($campo, $maxValor) {
            $v['pct_barra'] = round(($v[$campo] / $maxValor) * 100, 1);
            return $v;
        }, $todos);

        $podio = array_slice($todosConPct, 0, 3);
        $arena = array_slice($todosConPct, 3);

        return view('ventas.ranking', compact(
            'ranking',
            'podio',
            'arena',
            'totalFac',
            'totalCob',
            'totalPct',
            'reyEficiencia',
            'motorComercial',
            'from',
            'to',
            'sortBy',
        ));
    }

    public function rankingVendedoresOLD(Request $request)
    {
        // 1. Fechas y Filtros
        $from   = $request->input('from', now()->startOfMonth()->toDateString());
        $to     = $request->input('to', now()->toDateString());
        $sortBy = $request->input('sort', 'facturado'); // 'facturado' o 'cobrado'

        // 2. Extraer datos cacheados
        $prefix = "ventas:ranking:{$from}:{$to}";
        $rankingArray = CacheHelper::rememberArray(
            $prefix,
            config('cache_ttl.ranking_vendedores', 300),
            fn() => $this->erp->getRankingVendedores($from, $to)
        );

        $ranking = collect($rankingArray);

        // 3. Reordenar dinámicamente según lo que el usuario quiera ver
        if ($sortBy === 'cobrado') {
            $ranking = $ranking->sortByDesc('cobranzas_mes')->values();
        } else {
            $ranking = $ranking->sortByDesc('monto_facturado')->values();
        }

        // 4. Cálculos globales (El "Pulso del Equipo")
        $totalFac = $ranking->sum('monto_facturado');
        $totalCob = $ranking->sum('cobranzas_mes');
        $totalPct = $totalFac > 0 ? round($totalCob / $totalFac * 100, 1) : 0;

        // 5. Hall de la Fama (Widgets extra)
        // El más eficiente (El que cobró mayor porcentaje teniendo al menos un mínimo de facturación)
        $minFacturacion = $totalFac > 0 ? ($totalFac / $ranking->count()) * 0.3 : 0; // Al menos 30% del promedio
        $reyEficiencia  = $ranking->where('monto_facturado', '>=', $minFacturacion)
            ->sortByDesc('porcentaje_cobranza')
            ->first();

        // 6. Dividir la colección para el Podio y la Arena
        $podio = $ranking->take(3);
        $arena = $ranking->slice(3);

        return view('ventas.ranking', compact(
            'ranking',
            'podio',
            'arena',
            'totalFac',
            'totalCob',
            'totalPct',
            'reyEficiencia',
            'from',
            'to',
            'sortBy'
        ));
    }
}
