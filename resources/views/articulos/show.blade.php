{{--
    articulos/show.blade.php
    Ficha 360° de un artículo. Layout de dos columnas:
    Izquierda: identidad visual + stock + almacén + fechas
    Derecha:   precios + margen + stats de ventas + gráfico mensual
--}}
@extends('layouts.app')
@section('title', $articulo['descripcion'])

@section('breadcrumb')
    <a href="{{ route('articulos.index') }}" style="color:var(--text-muted);text-decoration:none;">Artículos</a>
    <span style="color:#cbd5e1;margin:0 4px;">/</span>
    <span class="current" style="max-width:220px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
        {{ $articulo['codigo'] }}
    </span>
@endsection

@section('hide_daterange', true)

@push('styles')
<style>
    /* Hero del artículo */
    .art-hero {
        background: linear-gradient(145deg, #0f172a 0%, #1e3a5f 100%);
        border-radius: 16px;
        padding: 28px;
        color: #fff;
        position: relative;
        overflow: hidden;
        min-height: 260px;
    }
    .art-hero::after {
        content: attr(data-initials);
        position: absolute;
        right: -20px; bottom: -30px;
        font-family: var(--font-display);
        font-size: 120px; font-weight: 900;
        color: rgba(255,255,255,.04);
        line-height: 1; pointer-events: none;
        letter-spacing: -5px;
    }

    /* Badge de código */
    .codigo-badge {
        display: inline-block;
        font-family: var(--font-display);
        font-size: 13px; font-weight: 800;
        padding: 4px 12px; border-radius: 8px;
        background: rgba(255,255,255,.12);
        color: rgba(255,255,255,.9);
        letter-spacing: .5px;
        margin-bottom: 12px;
    }

    /* Info cards dentro de la ficha */
    .info-block {
        background: var(--card-bg);
        border-radius: 12px;
        padding: 18px 20px;
        border: 1px solid #e2e8f0;
    }
    .info-block-label {
        font-size: 10.5px; font-weight: 700; text-transform: uppercase;
        letter-spacing: .6px; color: var(--text-muted); margin-bottom: 10px;
        display: flex; align-items: center; gap: 6px;
    }

    /* Precios grid */
    .precio-item {
        padding: 10px 14px;
        border-radius: 10px;
        background: #f8fafc;
        border: 1.5px solid #e2e8f0;
        transition: all .15s;
        text-align: center;
    }
    .precio-item:hover {
        border-color: var(--brand-primary);
        background: #eff6ff;
    }
    .precio-item.compra {
        background: #fff7ed;
        border-color: #fed7aa;
    }
    .precio-label {
        font-size: 10px; color: var(--text-muted);
        font-weight: 700; text-transform: uppercase;
        letter-spacing: .4px; margin-bottom: 4px;
    }
    .precio-value {
        font-family: var(--font-display);
        font-size: 16px; font-weight: 800;
        color: var(--text-primary); line-height: 1;
    }

    /* Margen badge grande */
    .margen-hero {
        display: flex; align-items: center; justify-content: space-between;
        padding: 14px 18px; border-radius: 12px;
        background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%);
        border: 1.5px solid #86efac;
    }
    .margen-hero.warning { background: linear-gradient(135deg,#fffbeb,#fef3c7); border-color:#fcd34d; }
    .margen-hero.danger  { background: linear-gradient(135deg,#fff5f5,#fee2e2); border-color:#fca5a5; }

    /* Stock visual */
    .stock-ring {
        width: 72px; height: 72px;
        border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
        flex-direction: column;
        font-family: var(--font-display);
        flex-shrink: 0;
        position: relative;
    }

    /* Stat chips */
    .stat-chip {
        background: #f8fafc; border-radius: 10px;
        padding: 12px 14px; border: 1px solid #f1f5f9;
        text-align: center;
    }
    .stat-chip-val {
        font-family: var(--font-display);
        font-size: 20px; font-weight: 800; line-height: 1;
        color: var(--text-primary);
    }
    .stat-chip-label {
        font-size: 10px; color: var(--text-muted);
        text-transform: uppercase; letter-spacing: .5px;
        font-weight: 600; margin-top: 4px;
    }

    /* Navegación prev/next */
    .art-nav-btn {
        width: 36px; height: 36px; border-radius: 9px;
        border: 1px solid #e2e8f0; background: #fff;
        display: flex; align-items: center; justify-content: center;
        text-decoration: none; color: var(--text-secondary);
        transition: all .15s;
    }
    .art-nav-btn:hover { border-color:var(--brand-primary); color:var(--brand-primary); }
</style>
@endpush

@section('content')

@php
    $currency = config('app_client.locale.currency_symbol');
    $dec      = config('app_client.locale.decimal_sep');
    $thou     = config('app_client.locale.thousands_sep');
    $fmt      = fn(float $v) => $currency . ' ' . number_format($v, 2, $dec, $thou);
    $fmt0     = fn(float $v) => number_format($v, 0, '.', $thou);

    $stockActual  = (float) $articulo['stock_actual'];
    $stockMinimo  = (float) $articulo['stock_minimo'];
    $stockLibre   = max(0, $stockActual - (float)$articulo['stock_comprometido']);
    $stockPct     = $stockMinimo > 0 ? min(round($stockActual / $stockMinimo * 100), 100) : 100;
    $stockColor   = $stockPct <= 20 ? '#dc2626' : ($stockPct <= 80 ? '#d97706' : '#059669');

    $initials = strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $articulo['descripcion']), 0, 3));
    $colors   = ['#1a56db','#059669','#d97706','#7c3aed','#0891b2','#ea580c'];
    $accentColor = $colors[abs(crc32($articulo['codigo'])) % count($colors)];

    // Margen class
    $margenClass = $margenPct >= config('app_client.business.margin_alert_yellow',20) ? '' :
                  ($margenPct >= config('app_client.business.margin_alert_red',10)    ? 'warning' : 'danger');
@endphp

{{-- ── Header de página ──────────────────────────────────────────── --}}
<div class="d-flex align-items-center justify-content-between flex-wrap gap-3 mb-4">
    <div class="d-flex align-items-center gap-3">
        <a href="{{ route('articulos.index') }}" class="art-nav-btn">
            <i class="fas fa-arrow-left" style="font-size:12px;"></i>
        </a>
        <div>
            <h1 class="font-display mb-0" style="font-size:20px;font-weight:800;line-height:1.2;">
                {{ $articulo['descripcion'] }}
            </h1>
            <p class="mb-0 mt-1" style="font-size:12px;color:var(--text-muted);">
                Código <strong style="color:{{ $accentColor }};">{{ $articulo['codigo'] }}</strong>
                @if($articulo['marca']) &nbsp;·&nbsp; {{ $articulo['marca'] }} @endif
                @if($articulo['categoria']) &nbsp;·&nbsp; {{ $articulo['categoria'] }} @endif
            </p>
        </div>
    </div>
    <div class="d-flex align-items-center gap-2">
        <a href="{{ route('articulos.rendimiento') }}"
           style="font-size:12px;color:var(--brand-primary);text-decoration:none;font-weight:600;">
            <i class="fas fa-chart-line me-1"></i> Ver Rendimiento Global
        </a>
    </div>
</div>

{{-- ════════════════════════════════════════════════════════════════ --}}
{{-- LAYOUT: Izq 5 col / Der 7 col                                   --}}
{{-- ════════════════════════════════════════════════════════════════ --}}
<div class="row g-4">

    {{-- ── COLUMNA IZQUIERDA ────────────────────────────────────── --}}
    <div class="col-12 col-lg-5">

        {{-- Hero del artículo --}}
        <div class="art-hero animate-in" data-initials="{{ $initials }}"
             style="--accent: {{ $accentColor }};">

            <div class="codigo-badge">{{ $articulo['codigo'] }}</div>

            <div style="display:flex;align-items:flex-start;gap:12px;margin-bottom:16px;">
                {{-- Avatar del artículo --}}
                <div style="width:60px;height:60px;border-radius:14px;
                            background:{{ $accentColor }}22;border:2px solid {{ $accentColor }}44;
                            display:flex;align-items:center;justify-content:center;
                            font-family:var(--font-display);font-size:20px;font-weight:800;
                            color:{{ $accentColor }};flex-shrink:0;">
                    {{ substr($initials, 0, 2) }}
                </div>
                <div>
                    <h2 style="font-family:var(--font-display);font-size:18px;font-weight:800;
                               color:#fff;line-height:1.3;margin:0 0 6px;">
                        {{ $articulo['descripcion'] }}
                    </h2>
                    <div style="display:flex;gap:6px;flex-wrap:wrap;">
                        @if($articulo['marca'])
                        <span style="font-size:11px;padding:2px 8px;border-radius:6px;
                                     background:rgba(255,255,255,.12);color:rgba(255,255,255,.8);
                                     font-weight:600;">
                            {{ $articulo['marca'] }}
                        </span>
                        @endif
                        <span style="font-size:11px;padding:2px 8px;border-radius:6px;
                                     background:{{ $estadoBg }};color:{{ $estadoColor }};font-weight:700;">
                            {{ $estadoLabel }}
                        </span>
                    </div>
                </div>
            </div>

            {{-- Stock visual --}}
            <div style="background:rgba(255,255,255,.07);border-radius:12px;padding:14px 16px;
                        border:1px solid rgba(255,255,255,.1);">
                <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:8px;">
                    <span style="font-size:11px;color:rgba(255,255,255,.5);font-weight:600;
                                 text-transform:uppercase;letter-spacing:.5px;">
                        Stock Disponible
                    </span>
                    <span style="font-family:var(--font-display);font-size:22px;font-weight:800;
                                 color:{{ $stockColor === '#dc2626' ? '#f87171' : ($stockColor === '#d97706' ? '#fbbf24' : '#4ade80') }};">
                        {{ $fmt0($stockActual) }}
                        <span style="font-size:12px;font-weight:400;opacity:.6;">uds.</span>
                    </span>
                </div>
                {{-- Barra de stock --}}
                <div style="height:6px;background:rgba(255,255,255,.1);border-radius:99px;overflow:hidden;margin-bottom:6px;">
                    <div style="height:100%;border-radius:99px;
                                width:{{ $stockPct }}%;
                                background:{{ $stockColor === '#dc2626' ? '#f87171' : ($stockColor === '#d97706' ? '#fbbf24' : '#4ade80') }};
                                transition:width .8s ease;"></div>
                </div>
                <div style="display:flex;justify-content:space-between;font-size:11px;color:rgba(255,255,255,.4);">
                    <span>{{ $stockPct }}% del mínimo ({{ $fmt0($stockMinimo) }} uds.)</span>
                    <span>Comprometido: {{ $fmt0((float)$articulo['stock_comprometido']) }}</span>
                </div>
            </div>

            {{-- Tendencia --}}
            <div style="display:flex;align-items:center;gap:8px;margin-top:14px;">
                <i class="fas {{ $tendenciaIcon }}" style="color:{{ $tendenciaColor === '#059669' ? '#4ade80' : ($tendenciaColor === '#dc2626' ? '#f87171' : '#fbbf24') }};font-size:13px;"></i>
                <span style="font-size:12.5px;color:rgba(255,255,255,.7);">
                    Tendencia:
                    <strong style="color:{{ $tendenciaColor === '#059669' ? '#4ade80' : ($tendenciaColor === '#dc2626' ? '#f87171' : '#fbbf24') }};">
                        {{ $tendenciaLabel }}
                    </strong>
                    en {{ $year }}
                </span>
            </div>
        </div>

        {{-- Información de Almacén --}}
        <div class="info-block mt-4 animate-in">
            <div class="info-block-label">
                <i class="fas fa-warehouse" style="color:var(--brand-primary);font-size:11px;"></i>
                Información de Almacén
            </div>
            <div class="row g-2" style="font-size:12.5px;">
                @foreach([
                    ['Almacén',           $articulo['almacen']             ?: 'Principal'],
                    ['Ubicación',         $articulo['ubicacion']           ?: 'Sin datos'],
                    ['Proveedor Principal',$articulo['proveedor_principal'] ?: 'Sin datos'],
                    ['Código de Barras',  $articulo['codigo_barras']        ?: 'Sin datos'],
                ] as [$label, $value])
                <div class="col-6">
                    <div style="color:var(--text-muted);font-size:10.5px;font-weight:700;
                                text-transform:uppercase;letter-spacing:.4px;margin-bottom:2px;">
                        {{ $label }}
                    </div>
                    <div style="font-weight:500;color:var(--text-primary);">{{ $value }}</div>
                </div>
                @endforeach
            </div>
        </div>

        {{-- Fechas Importantes --}}
        <div class="info-block mt-3 animate-in">
            <div class="info-block-label">
                <i class="fas fa-calendar-check" style="color:var(--brand-primary);font-size:11px;"></i>
                Fechas Importantes
            </div>
            @php
                $fechas = [
                    ['Última Compra',        $articulo['fechas']['ultima_compra'],       'fa-cart-shopping',    '#7c3aed'],
                    ['Última Venta',         $articulo['fechas']['ultima_venta'],        'fa-receipt',          '#059669'],
                    ['Última Actualización', $articulo['fechas']['ultima_modificacion'], 'fa-pen-to-square',    '#0891b2'],
                ];
            @endphp
            <div style="display:flex;flex-direction:column;gap:10px;">
                @foreach($fechas as [$label, $fecha, $icon, $color])
                <div style="display:flex;align-items:center;gap:10px;">
                    <div style="width:30px;height:30px;border-radius:8px;
                                background:{{ $color }}12;
                                display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                        <i class="fas {{ $icon }}" style="color:{{ $color }};font-size:11px;"></i>
                    </div>
                    <div>
                        <div style="font-size:10.5px;color:var(--text-muted);font-weight:600;
                                    text-transform:uppercase;letter-spacing:.4px;">{{ $label }}</div>
                        <div style="font-size:12.5px;font-weight:600;color:var(--text-primary);">
                            @if($fecha)
                                {{ \Carbon\Carbon::parse($fecha)->format(config('app_client.locale.date_format')) }}
                            @else
                                <span style="color:var(--text-muted);font-weight:400;">Sin datos</span>
                            @endif
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>

    </div>

    {{-- ── COLUMNA DERECHA ──────────────────────────────────────── --}}
    <div class="col-12 col-lg-7">

        {{-- Precios --}}
        @include('articulos._partials.ficha_precios')

        {{-- Estadísticas de Ventas --}}
        @include('articulos._partials.ficha_stats')

        {{-- Mini-gráfico evolución mensual --}}
        <div class="info-block mt-4 animate-in">
            <div class="info-block-label">
                <i class="fas fa-chart-area" style="color:{{ $accentColor }};font-size:11px;"></i>
                Evolución Mensual — {{ $year }}
            </div>
            <div style="height:160px;position:relative;">
                <canvas id="chartMensual"></canvas>
            </div>
        </div>

        {{-- Costos desglosados (solo con permiso financiero) --}}
        @can('financiero.margenes.ver')
        <div class="info-block mt-3 animate-in">
            <div class="info-block-label">
                <i class="fas fa-coins" style="color:#d97706;font-size:11px;"></i>
                Costos (campo activo: <strong style="color:var(--brand-primary);">{{ $costField }}</strong>)
            </div>
            <div class="row g-2">
                @foreach([
                    ['COS_PRO_UN', 'Costo Prom. (local)'],
                    ['ULT_COS_UN', 'Último Costo (local)'],
                    ['COS_PRO_OM', 'Costo Prom. (USD)'],
                    ['ULT_COS_OM', 'Último Costo (USD)'],
                ] as [$campo, $label])
                <div class="col-6">
                    <div style="padding:10px 12px;border-radius:10px;
                                background:{{ $campo === $costField ? '#eff6ff' : '#f8fafc' }};
                                border:1.5px solid {{ $campo === $costField ? 'var(--brand-primary)' : '#e2e8f0' }};">
                        <div style="font-size:10px;color:var(--text-muted);font-weight:700;
                                    text-transform:uppercase;letter-spacing:.4px;margin-bottom:3px;">
                            {{ $label }}
                            @if($campo === $costField)
                                <span style="color:var(--brand-primary);">●</span>
                            @endif
                        </div>
                        <div style="font-family:var(--font-display);font-size:15px;font-weight:800;
                                    color:{{ $campo === $costField ? 'var(--brand-primary)' : 'var(--text-primary)' }};">
                            {{ $fmt((float)($articulo['costos_desglose'][$campo] ?? 0)) }}
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        @endcan

    </div>
</div>

@endsection

@push('scripts')
<script>
(function(){
    const ctx = document.getElementById('chartMensual');
    if (!ctx) return;

    const labels = {!! $chartLabels !!};
    const data   = {!! $chartData !!};
    const color  = '{{ $accentColor }}';

    new Chart(ctx, {
        type: 'line',
        data: {
            labels,
            datasets: [{
                label: 'Unidades vendidas',
                data,
                borderColor:     color,
                backgroundColor: color + '14',
                fill:            true,
                borderWidth:     2.5,
                tension:         0.4,
                pointRadius:     data.map(v => v > 0 ? 4 : 0),
                pointHoverRadius: 7,
                pointBackgroundColor: color,
            }]
        },
        options: {
            responsive: true, maintainAspectRatio: false,
            plugins: {
                legend: { display:false },
                tooltip: {
                    backgroundColor:'#1e293b', padding:10, cornerRadius:8,
                    callbacks: { label: c => ` ${c.parsed.y} unidades` }
                }
            },
            scales: {
                x: { grid:{display:false}, ticks:{font:{size:10}} },
                y: {
                    grid:{color:'rgba(0,0,0,.04)',drawBorder:false},
                    ticks:{font:{size:10}, callback: v => v >= 1000 ? (v/1000).toFixed(1)+'k' : v},
                    beginAtZero: true,
                }
            }
        }
    });

    // Animar barras
    requestAnimationFrame(()=>{
        document.querySelectorAll('[style*="transition:width"]').forEach(el => {
            const w = el.style.width; el.style.width='0';
            setTimeout(()=> el.style.width=w, 200);
        });
    });
})();
</script>
@endpush
