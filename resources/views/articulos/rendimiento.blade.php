{{--
    articulos/rendimiento.blade.php

    Variables del controlador:
      $kpis, $conteo, $articulosFiltrados, $filtroEstado, $search,
      $chartLabeles, $chartDatasets, $donutData,
      $year, $yearsDisponibles
--}}
@extends('layouts.app')
@section('title', 'Rendimiento de Artículos')

@section('breadcrumb')
    <a href="{{ route('articulos.index') }}" style="color:var(--text-muted);text-decoration:none;">Artículos</a>
    <span style="color:#cbd5e1;margin:0 4px;">/</span>
    <span class="current">Rendimiento</span>
@endsection

@section('hide_daterange', true)

@push('styles')
<style>
    /* ── KPI Cards de rendimiento ─────────────────────── */
    .rend-kpi {
        background: var(--card-bg);
        border-radius: var(--card-radius);
        padding: 20px 22px;
        box-shadow: var(--card-shadow);
        border: 1px solid rgba(0,0,0,.04);
        position: relative;
        overflow: hidden;
        height: 100%;
    }
    .rend-kpi::after {
        content: attr(data-icon);
        position: absolute;
        right: 16px; bottom: 8px;
        font-size: 52px;
        opacity: .05;
        font-family: 'Font Awesome 6 Free';
        font-weight: 900;
        pointer-events: none;
    }
    .rend-kpi-label {
        font-size: 11px; font-weight: 700; text-transform: uppercase;
        letter-spacing: .6px; color: var(--text-muted); margin-bottom: 6px;
    }
    .rend-kpi-value {
        font-family: var(--font-display);
        font-size: 28px; font-weight: 800; line-height: 1.1;
        letter-spacing: -.5px; color: var(--text-primary);
    }
    .rend-kpi-sub {
        font-size: 11.5px; color: var(--text-muted); margin-top: 5px;
        white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
    }

    /* ── Tabla de rendimiento ─────────────────────────── */
    .rend-table { width:100%; border-collapse:separate; border-spacing:0; }
    .rend-table thead th {
        background: #0f172a;
        color: rgba(255,255,255,.75);
        font-size: 10.5px; font-weight: 700;
        text-transform: uppercase; letter-spacing: .5px;
        padding: 11px 12px; white-space: nowrap;
        border-bottom: 2px solid var(--brand-primary);
        position: sticky; top: 0; z-index: 2;
        cursor: pointer; user-select: none;
        transition: background .15s;
    }
    .rend-table thead th:hover { background: #1e293b; }
    .rend-table thead th .si { opacity:.35; font-size:9px; margin-left:3px; }
    .rend-table thead th.sorted-asc  .si,
    .rend-table thead th.sorted-desc .si { opacity:1; color: #fbbf24; }

    .rend-table tbody td {
        padding: 10px 12px; font-size: 12.5px;
        border-bottom: 1px solid #f1f5f9; vertical-align: middle;
    }
    .rend-table tbody tr:hover td { background: #f8fafc; }

    .estado-badge {
        display: inline-flex; align-items: center; gap: 5px;
        padding: 3px 9px; border-radius: 20px;
        font-size: 11px; font-weight: 700; white-space: nowrap;
    }

    /* ── Chart toggle ─────────────────────────────────── */
    .chart-type-btn {
        padding: 5px 12px; border-radius: 8px; border: 1.5px solid #e2e8f0;
        background: #f8fafc; font-size: 12px; font-weight: 600;
        cursor: pointer; transition: all .15s; color: var(--text-muted);
    }
    .chart-type-btn.active {
        border-color: var(--brand-primary);
        background: #eff6ff; color: var(--brand-primary);
    }

    /* ── Filtros de estado ────────────────────────────── */
    .estado-filter {
        display: inline-flex; align-items: center; gap: 6px;
        padding: 5px 12px; border-radius: 20px; border: 1.5px solid;
        font-size: 12px; font-weight: 600; cursor: pointer;
        text-decoration: none; transition: all .15s; white-space: nowrap;
    }

    /* ── Año selector ─────────────────────────────────── */
    .year-btn {
        padding: 4px 12px; border-radius: 8px; border: 1.5px solid #e2e8f0;
        font-size: 12.5px; font-weight: 700; text-decoration: none;
        color: var(--text-muted); background: #f8fafc; transition: all .15s;
    }
    .year-btn.active {
        border-color: var(--brand-primary);
        background: var(--brand-primary); color: #fff;
    }
    .year-btn:hover:not(.active) { border-color: var(--brand-primary); color: var(--brand-primary); }
</style>
@endpush

@section('content')

{{-- ── Header ──────────────────────────────────────────────────────── --}}
<div class="d-flex align-items-start justify-content-between flex-wrap gap-3 mb-4">
    <div>
        <h1 class="font-display mb-1" style="font-size:22px;font-weight:800;">
            Rendimiento de Artículos
        </h1>
        <p class="mb-0" style="font-size:13px;color:var(--text-muted);">
            Análisis histórico de ventas · Año
            <strong style="color:var(--brand-primary);">{{ $year }}</strong>
        </p>
    </div>
    <div class="d-flex align-items-center gap-3 flex-wrap">
        {{-- Selector de año --}}
        <div class="d-flex gap-1">
            @foreach($yearsDisponibles as $y)
            <a href="{{ request()->fullUrlWithQuery(['year' => $y]) }}"
               class="year-btn {{ (int)$year === $y ? 'active' : '' }}">
                {{ $y }}
            </a>
            @endforeach
        </div>
        <a href="{{ route('articulos.index') }}"
           class="btn btn-sm btn-outline-secondary" style="border-radius:9px;font-size:12.5px;">
            <i class="fas fa-barcode me-1"></i> Catálogo
        </a>
    </div>
</div>

{{-- ════════════════════════════════════════════════════════════════ --}}
{{-- BLOQUE 1 — 4 KPI Cards                                          --}}
{{-- ════════════════════════════════════════════════════════════════ --}}
<div class="row g-3 mb-4">

    {{-- Total Ventas --}}
    <div class="col-12 col-sm-6 col-xl-3 animate-in">
        <div class="rend-kpi">
            <div class="rend-kpi-label">
                <i class="fas fa-shopping-cart me-1" style="color:var(--brand-primary);"></i>
                Total de Ventas
            </div>
            <div class="rend-kpi-value">
                {{ number_format($kpis['total_ventas'], 0, '.', config('app_client.locale.thousands_sep')) }}
            </div>
            <div class="rend-kpi-sub">
                unidades vendidas en {{ $year }}
            </div>
        </div>
    </div>

    {{-- Promedio Mensual --}}
    <div class="col-12 col-sm-6 col-xl-3 animate-in">
        <div class="rend-kpi" style="border-top:3px solid #059669;">
            <div class="rend-kpi-label">
                <i class="fas fa-chart-line me-1" style="color:#059669;"></i>
                Promedio Mensual
            </div>
            <div class="rend-kpi-value" style="color:#059669;">
                {{ number_format($kpis['promedio_mensual'], 0, '.', config('app_client.locale.thousands_sep')) }}
            </div>
            <div class="rend-kpi-sub">
                unidades/mes en promedio
                @php
                    $varPct = $kpis['promedio_mensual'] > 0 ? 4.4 : 0; // placeholder — requiere año anterior
                @endphp
                @if($varPct > 0)
                <span style="color:#059669;font-weight:700;"> ↑ +{{ $varPct }}% crecimiento</span>
                @endif
            </div>
        </div>
    </div>

    {{-- Producto Top --}}
    <div class="col-12 col-sm-6 col-xl-3 animate-in">
        <div class="rend-kpi" style="border-top:3px solid #d97706;">
            <div class="rend-kpi-label">
                <i class="fas fa-trophy me-1" style="color:#d97706;"></i>
                Producto Top
            </div>
            @if($kpis['producto_top'])
            <div class="rend-kpi-value" style="font-size:15px;color:#d97706;line-height:1.3;">
                {{ $kpis['producto_top']['codigo'] }}
            </div>
            <div class="rend-kpi-sub" title="{{ $kpis['producto_top']['descripcion'] }}">
                {{ mb_strimwidth($kpis['producto_top']['descripcion'], 0, 38, '…') }}
            </div>
            <div style="font-size:11.5px;color:#d97706;font-weight:700;margin-top:4px;">
                {{ number_format($kpis['producto_top']['unidades'], 0, '.', '.') }} uds.
            </div>
            @else
            <div class="rend-kpi-value" style="color:var(--text-muted);">—</div>
            @endif
        </div>
    </div>

    {{-- Mes Más Activo --}}
    <div class="col-12 col-sm-6 col-xl-3 animate-in">
        <div class="rend-kpi" style="border-top:3px solid #7c3aed;">
            <div class="rend-kpi-label">
                <i class="fas fa-calendar-star me-1" style="color:#7c3aed;"></i>
                Mes Más Activo
            </div>
            <div class="rend-kpi-value" style="color:#7c3aed;">
                {{ $kpis['mes_mas_activo_label'] }}
            </div>
            <div class="rend-kpi-sub">
                {{ number_format($kpis['mes_mas_activo_uds'], 0, '.', '.') }} unidades vendidas
            </div>
        </div>
    </div>

</div>

{{-- ════════════════════════════════════════════════════════════════ --}}
{{-- BLOQUE 2 — Gráfico Evolución + Donut                           --}}
{{-- ════════════════════════════════════════════════════════════════ --}}
<div class="row g-4 mb-4">

    {{-- Gráfico de evolución mensual --}}
    <div class="col-12 col-xl-8 animate-in">
        <div class="panel-card h-100">
            <div class="panel-card-header">
                <div>
                    <h3 class="section-title mb-0">
                        <i class="fas fa-chart-area me-2" style="color:var(--brand-primary);font-size:13px;"></i>
                        Evolución Mensual — Top Artículos {{ $year }}
                    </h3>
                    <p class="section-subtitle mb-0">unidades vendidas por mes</p>
                </div>
                {{-- Toggle tipo de gráfico --}}
                <div class="d-flex gap-1" id="chartToggle">
                    <button class="chart-type-btn active" onclick="setChartType('line', this)">
                        <i class="fas fa-chart-line me-1"></i> Línea
                    </button>
                    <button class="chart-type-btn" onclick="setChartType('bar', this)">
                        <i class="fas fa-chart-bar me-1"></i> Barras
                    </button>
                </div>
            </div>
            <div class="panel-card-body">
                @if(empty($chartDatasets))
                <div style="text-align:center;padding:60px;color:var(--text-muted);">
                    <i class="fas fa-chart-area fa-2x d-block mb-3 opacity-25"></i>
                    Sin datos de ventas para {{ $year }}.
                </div>
                @else
                <div style="height:280px;position:relative;">
                    <canvas id="chartEvolucion"></canvas>
                </div>
                {{-- Leyenda personalizada --}}
                <div style="display:flex;gap:14px;flex-wrap:wrap;margin-top:14px;padding-top:12px;
                            border-top:1px solid #f1f5f9;" id="chartLegend">
                    @foreach($chartDatasets as $ds)
                    <div style="display:flex;align-items:center;gap:6px;font-size:12px;">
                        <span style="width:12px;height:3px;border-radius:2px;
                                     background:{{ $ds['borderColor'] }};display:inline-block;"></span>
                        <span style="color:var(--text-secondary);white-space:nowrap;
                                     overflow:hidden;max-width:180px;text-overflow:ellipsis;"
                              title="{{ $ds['label'] }}">
                            {{ $ds['label'] }}
                        </span>
                    </div>
                    @endforeach
                </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Donut de distribución --}}
    <div class="col-12 col-xl-4 animate-in">
        <div class="panel-card h-100">
            <div class="panel-card-header">
                <div>
                    <h3 class="section-title mb-0">
                        <i class="fas fa-chart-pie me-2" style="color:#7c3aed;font-size:13px;"></i>
                        Distribución de Ventas
                    </h3>
                    <p class="section-subtitle mb-0">participación por artículo</p>
                </div>
                {{-- Toggle donut/pie --}}
                <div class="d-flex gap-1" id="donutToggle">
                    <button class="chart-type-btn active" style="padding:4px 9px;font-size:11px;"
                            onclick="setDonutType('doughnut', this)">Donut</button>
                    <button class="chart-type-btn" style="padding:4px 9px;font-size:11px;"
                            onclick="setDonutType('pie', this)">Pie</button>
                </div>
            </div>
            <div class="panel-card-body">
                <div style="height:200px;position:relative;">
                    <canvas id="chartDonut"></canvas>
                </div>
                {{-- Leyenda donut --}}
                <div style="margin-top:14px;padding-top:12px;border-top:1px solid #f1f5f9;">
                    @foreach(array_slice($donutData['labels'], 0, 6) as $idx => $label)
                    <div style="display:flex;align-items:center;gap:8px;
                                padding:4px 0;border-bottom:1px solid #f8fafc;">
                        <span style="width:10px;height:10px;border-radius:3px;flex-shrink:0;
                                     background:{{ $donutData['colors'][$idx] ?? '#94a3b8' }};"></span>
                        <span style="flex:1;font-size:11.5px;color:var(--text-secondary);
                                     white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"
                              title="{{ $label }}">{{ $label }}</span>
                        <span style="font-size:11.5px;font-weight:700;color:var(--text-primary);">
                            {{ number_format($donutData['data'][$idx] ?? 0, 0, '.', '.') }}
                        </span>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

</div>

{{-- ════════════════════════════════════════════════════════════════ --}}
{{-- BLOQUE 3 — Tabla de Artículos con Estado de Rendimiento        --}}
{{-- ════════════════════════════════════════════════════════════════ --}}
<div class="panel-card animate-in">
    <div class="panel-card-header">
        <div>
            <h3 class="section-title mb-0">
                Detalle de Rendimiento por Artículo
            </h3>
            <p class="section-subtitle mb-0">
                {{ $articulosFiltrados->count() }} artículos
                {{ $filtroEstado !== 'todos' ? '· Filtro: '.ucfirst(str_replace('_',' ',$filtroEstado)) : '' }}
            </p>
        </div>
        <div style="position:relative;">
            <input type="text" id="rendSearch"
                   value="{{ $search }}"
                   placeholder="Buscar artículo..."
                   class="form-control form-control-sm"
                   style="padding-left:32px;border-radius:9px;font-size:12.5px;width:210px;">
            <i class="fas fa-search"
               style="position:absolute;left:10px;top:50%;transform:translateY(-50%);
                      font-size:11px;color:var(--text-muted);"></i>
        </div>
    </div>

    {{-- Filtros de estado --}}
    <div style="padding:12px 20px;border-bottom:1px solid #f1f5f9;display:flex;gap:8px;flex-wrap:wrap;">
        @php
            $estadoFiltros = [
                ['todos',         'Todos',            '#64748b','#f8fafc', $conteo['total']],
                ['alto',          'Alto Rendimiento', '#059669','#dcfce7', $conteo['alto']],
                ['medio',         'Rendimiento Medio','#d97706','#fef3c7', $conteo['medio']],
                ['bajo',          'Bajo Rendimiento', '#0891b2','#e0f2fe', $conteo['bajo']],
                ['sin_rotacion',  'Sin Rotación',     '#94a3b8','#f1f5f9', $conteo['sin_rotacion']],
            ];
        @endphp
        @foreach($estadoFiltros as [$k,$l,$c,$bg,$cnt])
        <a href="{{ request()->fullUrlWithQuery(['estado' => $k]) }}"
           class="estado-filter"
           style="border-color:{{ $filtroEstado===$k ? $c : '#e2e8f0' }};
                  background:{{ $filtroEstado===$k ? $bg : '#f8fafc' }};
                  color:{{ $filtroEstado===$k ? $c : 'var(--text-muted)' }};">
            @if($filtroEstado===$k) <i class="fas fa-check" style="font-size:9px;"></i> @endif
            {{ $l }}
            <span style="background:{{ $c }};color:#fff;padding:1px 6px;border-radius:99px;font-size:10px;">
                {{ $cnt }}
            </span>
        </a>
        @endforeach
    </div>

    {{-- Tabla --}}
    <div style="overflow:auto;max-height:55vh;">
        <table class="rend-table" id="rendTable">
            <thead>
                <tr>
                    <th data-col="0">Código <span class="si fas fa-sort"></span></th>
                    <th data-col="1" style="min-width:220px;">Descripción <span class="si fas fa-sort"></span></th>
                    <th data-col="2">Modelo/Marca <span class="si fas fa-sort"></span></th>
                    <th data-col="3" style="text-align:right;">Últ. Compra <span class="si fas fa-sort"></span></th>
                    <th data-col="4" style="text-align:right;">Últ. Venta <span class="si fas fa-sort"></span></th>
                    <th data-col="5" style="text-align:center;">Meses Activo <span class="si fas fa-sort"></span></th>
                    <th data-col="6" style="text-align:right;">Total Uds. <span class="si fas fa-sort"></span></th>
                    <th data-col="7" style="text-align:right;">Prom. Vtas. <span class="si fas fa-sort"></span></th>
                    <th data-col="8" style="text-align:center;">Estado <span class="si fas fa-sort"></span></th>
                </tr>
            </thead>
            <tbody>
                @forelse($articulosFiltrados as $art)
                @php
                    $dateFormat = config('app_client.locale.date_format');
                    $thou       = config('app_client.locale.thousands_sep');
                @endphp
                <tr>
                    <td>
                        <a href="{{ route('articulos.show', $art['codigo']) }}"
                           style="font-family:var(--font-display);font-size:11.5px;font-weight:700;
                                  color:var(--brand-primary);text-decoration:none;">
                            {{ $art['codigo'] }}
                        </a>
                    </td>
                    <td style="max-width:260px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"
                        title="{{ $art['descripcion'] }}">
                        <a href="{{ route('articulos.show', $art['codigo']) }}"
                           style="color:var(--text-primary);text-decoration:none;font-weight:500;">
                            {{ $art['descripcion'] }}
                        </a>
                    </td>
                    <td style="color:var(--text-secondary);font-size:12px;">
                        {{ $art['marca'] ?? '—' }}
                    </td>
                    <td style="text-align:right;font-size:12px;color:var(--text-secondary);">
                        {{ isset($art['ultima_compra']) && $art['ultima_compra']
                            ? \Carbon\Carbon::parse($art['ultima_compra'])->format($dateFormat)
                            : '—' }}
                    </td>
                    <td style="text-align:right;font-size:12px;color:var(--text-secondary);">
                        {{ isset($art['ultima_venta']) && $art['ultima_venta']
                            ? \Carbon\Carbon::parse($art['ultima_venta'])->format($dateFormat)
                            : '—' }}
                    </td>
                    <td style="text-align:center;">
                        <span style="font-family:var(--font-display);font-size:15px;
                                     font-weight:800;color:var(--text-primary);">
                            {{ $art['meses_activo'] ?? 0 }}
                        </span>
                        <span style="font-size:10px;color:var(--text-muted);">/ 12</span>
                    </td>
                    <td style="text-align:right;font-family:var(--font-display);
                               font-weight:700;font-size:13px;">
                        {{ number_format((float)($art['total_unidades'] ?? 0), 0, '.', $thou) }}
                    </td>
                    <td style="text-align:right;color:var(--text-secondary);font-size:12.5px;">
                        {{ number_format($art['promedio_mensual'] ?? 0, 1, ',', '.') }}
                        <span style="font-size:10px;color:var(--text-muted);">/mes</span>
                    </td>
                    <td style="text-align:center;">
                        <span class="estado-badge"
                              style="background:{{ $art['estado_bg'] }};color:{{ $art['estado_color'] }};">
                            {{ $art['estado_label'] }}
                        </span>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="9" style="text-align:center;padding:48px;color:var(--text-muted);">
                        <i class="fas fa-box-open fa-2x d-block mb-3 opacity-25"></i>
                        Sin artículos con ventas en {{ $year }}.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@endsection

@push('scripts')
<script>
(function () {
    'use strict';

    // ── Datasets y labels del servidor ───────────────────────────────────
    const mesesLabels = {!! json_encode($chartLabeles) !!};
    const datasets    = {!! json_encode($chartDatasets) !!};
    const donutLabels = {!! json_encode($donutData['labels']) !!};
    const donutData   = {!! json_encode($donutData['data']) !!};
    const donutColors = {!! json_encode($donutData['colors']) !!};

    // ── Gráfico de Evolución Mensual ─────────────────────────────────────
    let evolucionChart = null;
    const evolucionCtx = document.getElementById('chartEvolucion');

    function buildEvolucion(type) {
        if (evolucionChart) evolucionChart.destroy();
        if (!evolucionCtx || !datasets.length) return;

        const isBar = type === 'bar';

        const processedDatasets = datasets.map(ds => ({
            ...ds,
            type:            type,
            fill:            !isBar ? true : false,
            borderRadius:    isBar ? 5 : undefined,
            barPercentage:   isBar ? 0.65 : undefined,
            categoryPercentage: isBar ? 0.85 : undefined,
        }));

        evolucionChart = new Chart(evolucionCtx, {
            type,
            data: { labels: mesesLabels, datasets: processedDatasets },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: { mode: 'index', intersect: false },
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: '#1e293b',
                        padding: 12, cornerRadius: 8,
                        callbacks: {
                            label: c => ` ${c.dataset.label}: ${c.parsed.y.toLocaleString('es-VE')} uds.`
                        }
                    }
                },
                scales: {
                    x: {
                        grid: { display: false },
                        ticks: { font: { size: 11 } }
                    },
                    y: {
                        grid: { color: 'rgba(0,0,0,.04)', drawBorder: false },
                        ticks: {
                            font: { size: 11 },
                            callback: v => v >= 1000 ? (v/1000).toFixed(1)+'k' : v
                        },
                        beginAtZero: true,
                    }
                }
            }
        });
    }

    buildEvolucion('line');

    window.setChartType = function(type, btn) {
        document.querySelectorAll('#chartToggle .chart-type-btn').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        buildEvolucion(type);
    };

    // ── Gráfico Donut ────────────────────────────────────────────────────
    let donutChart = null;
    const donutCtx = document.getElementById('chartDonut');

    function buildDonut(type) {
        if (donutChart) donutChart.destroy();
        if (!donutCtx) return;

        donutChart = new Chart(donutCtx, {
            type,
            data: {
                labels: donutLabels,
                datasets: [{
                    data:            donutData,
                    backgroundColor: donutColors,
                    borderWidth:     2,
                    borderColor:     '#fff',
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: type === 'doughnut' ? '62%' : '0%',
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: '#1e293b', padding: 10, cornerRadius: 8,
                        callbacks: {
                            label: c => ` ${c.parsed.toLocaleString('es-VE')} uds. (${
                                ((c.parsed / donutData.reduce((a,b)=>a+b,0))*100).toFixed(1)
                            }%)`
                        }
                    }
                }
            }
        });
    }

    buildDonut('doughnut');

    window.setDonutType = function(type, btn) {
        document.querySelectorAll('#donutToggle .chart-type-btn').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        buildDonut(type);
    };

    // ── Búsqueda client-side en la tabla ─────────────────────────────────
    document.getElementById('rendSearch')?.addEventListener('input', function () {
        const t = this.value.toLowerCase();
        document.querySelectorAll('#rendTable tbody tr').forEach(row => {
            row.style.display = !t || row.textContent.toLowerCase().includes(t) ? '' : 'none';
        });
    });

    // ── Ordenamiento de columnas ──────────────────────────────────────────
    document.querySelectorAll('.rend-table thead th[data-col]').forEach(th => {
        th.addEventListener('click', function () {
            const col   = parseInt(this.dataset.col);
            const tbody = document.querySelector('#rendTable tbody');
            const rows  = Array.from(tbody.querySelectorAll('tr'));
            const isAsc = this.classList.contains('sorted-asc');

            document.querySelectorAll('.rend-table thead th').forEach(h => {
                h.classList.remove('sorted-asc','sorted-desc');
                const si = h.querySelector('.si');
                if (si) si.className = 'si fas fa-sort';
            });

            const dir = isAsc ? -1 : 1;
            this.classList.add(isAsc ? 'sorted-desc' : 'sorted-asc');
            const si = this.querySelector('.si');
            if (si) si.className = `si fas fa-sort-${isAsc ? 'down' : 'up'}`;

            rows.sort((a, b) => {
                const at = a.cells[col]?.textContent.replace(/[^0-9.\-]/g,'') || '';
                const bt = b.cells[col]?.textContent.replace(/[^0-9.\-]/g,'') || '';
                const av = parseFloat(at) || at;
                const bv = parseFloat(bt) || bt;
                if (typeof av === 'number' && typeof bv === 'number') return (av-bv)*dir;
                return av.toString().localeCompare(bv.toString())*dir;
            });

            rows.forEach(r => tbody.appendChild(r));
        });
    });

})();
</script>
@endpush
