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
        // 1. Fechas y Filtros
        $from   = $request->input('from', now()->startOfMonth()->toDateString());
        $to     = $request->input('to', now()->toDateString());
        $sortBy = $request->input('sort', 'facturado'); // 'facturado' o 'cobrado'

        // 2. Extraer datos cacheados
        $prefix = "ventas:ranking:{$from}:{$to}";
        $rankingArray = CacheHelper::rememberArray(
            $prefix,
            config('cache_ttl.ranking_vendedores', 300),
            fn () => $this->erp->getRankingVendedores($from, $to)
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
            'ranking', 'podio', 'arena', 'totalFac', 'totalCob', 'totalPct',
            'reyEficiencia', 'from', 'to', 'sortBy'
        ));
    }
}
