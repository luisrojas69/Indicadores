{{--
    financiero/margenes.blade.php

    Variables del controlador:
      $margenes, $margenesFiltradas, $semaforos, $chartDistribucion,
      $margenConfig, $costField, $excluirIva, $filtroSemaforo, $from, $to
--}}

@extends('layouts.app')

@section('title', 'Márgenes de Rentabilidad')

@section('breadcrumb')
    <a href="{{ route('dashboard.index') }}"
       style="color: var(--text-muted); text-decoration: none;">Inicio</a>
    <span style="color: #cbd5e1; font-size: 11px; margin: 0 4px;">/</span>
    <span class="current">Márgenes</span>
@endsection

@push('styles')
<style>
    /* ── Semáforo badges ─────────────────────────────── */
    .sem-badge {
        display: inline-flex; align-items: center; gap: 5px;
        padding: 3px 9px; border-radius: 20px;
        font-size: 11px; font-weight: 700; letter-spacing: .3px;
        white-space: nowrap;
    }
    .sem-verde    { background: #dcfce7; color: #15803d; }
    .sem-amarillo { background: #fef3c7; color: #92400e; }
    .sem-rojo     { background: #fee2e2; color: #b91c1c; }
    .sem-negativo { background: #ede9fe; color: #5b21b6; }

    /* ── Tabla márgenes ──────────────────────────────── */
    .margenes-table { width: 100%; border-collapse: separate; border-spacing: 0; }

    .margenes-table thead th {
        position: sticky; top: 0; z-index: 2;
        background: #0f172a;
        color: rgba(255,255,255,.8);
        font-size: 10.5px; font-weight: 700;
        letter-spacing: .5px; text-transform: uppercase;
        padding: 11px 12px;
        border-bottom: 2px solid var(--brand-primary);
        white-space: nowrap;
        cursor: pointer;
        user-select: none;
        transition: background .15s;
    }
    .margenes-table thead th:hover { background: #1e293b; }
    .margenes-table thead th .sort-icon { opacity: .4; font-size: 9px; margin-left: 4px; }
    .margenes-table thead th.sorted-asc  .sort-icon,
    .margenes-table thead th.sorted-desc .sort-icon { opacity: 1; color: var(--brand-warning); }

    .margenes-table tbody tr { transition: background .1s; }
    .margenes-table tbody tr:hover td { filter: brightness(.96); }

    .margenes-table tbody td {
        padding: 9px 12px;
        font-size: 12.5px;
        color: var(--text-primary);
        border-bottom: 1px solid #f1f5f9;
        vertical-align: middle;
        white-space: nowrap;
    }

    /* Filas con color de semáforo tenue */
    .row-verde    td { background: #f0fdf4; }
    .row-amarillo td { background: #fffbeb; }
    .row-rojo     td { background: #fff5f5; }
    .row-negativo td { background: #f5f3ff; }

    /* Barra de margen inline */
    .margin-bar-wrap { display: flex; align-items: center; gap: 8px; min-width: 110px; }
    .margin-bar      { flex: 1; height: 5px; background: #e2e8f0; border-radius: 99px; overflow: hidden; }
    .margin-bar-fill { height: 100%; border-radius: 99px; transition: width .8s ease; }

    /* Sticky total row */
    .margenes-table tfoot td {
        position: sticky; bottom: 0;
        background: #f1f5f9;
        font-weight: 700;
        font-size: 12px;
        padding: 10px 12px;
        border-top: 2px solid var(--brand-primary);
        color: var(--text-primary);
    }

    /* Selector de costo */
    .cost-pill {
        display: inline-flex; align-items: center; gap: 6px;
        padding: 4px 10px 4px 8px;
        border-radius: 20px; border: 1.5px solid;
        font-size: 11.5px; font-weight: 600;
        cursor: pointer; transition: all .15s;
    }
    .cost-pill.active  { border-color: var(--brand-primary); background: #eff6ff; color: var(--brand-primary); }
    .cost-pill.inactive{ border-color: #e2e8f0; background: #f8fafc; color: var(--text-muted); }
    .cost-pill.inactive:hover { border-color: var(--brand-primary); color: var(--brand-primary); }
</style>
@endpush

@section('content')

{{-- ── Page Header ─────────────────────────────────────────────────── --}}
<div class="d-flex align-items-start justify-content-between flex-wrap gap-3 mb-4">
    <div>
        <h1 class="font-display mb-1" style="font-size: 22px; font-weight: 800;">
            Márgenes de Rentabilidad
        </h1>
        <p class="mb-0" style="font-size: 13px; color: var(--text-muted);">
            Período:
            <strong style="color: var(--text-secondary);">
                {{ \Carbon\Carbon::parse($from)->format(config('app_client.locale.date_format')) }}
                — {{ \Carbon\Carbon::parse($to)->format(config('app_client.locale.date_format')) }}
            </strong>
            &nbsp;·&nbsp; Campo de costo activo:
            <strong style="color: var(--brand-primary);">{{ $costField }}</strong>
            @if($excluirIva)
                &nbsp;·&nbsp;
                <span class="sem-badge sem-negativo" style="font-size: 10px;">IVA excluido</span>
            @endif
        </p>
    </div>

    <div class="d-flex align-items-center gap-2 flex-wrap">
        {{-- Enlace al bono --}}
        @can('financiero.reporte.bonos')
        <a href="{{ route('financiero.bonos', ['from' => $from, 'to' => $to]) }}"
           class="btn btn-sm btn-outline-primary d-flex align-items-center gap-2"
           style="border-radius: 9px; font-size: 12.5px;">
            <i class="fas fa-award"></i> Ver Bono
        </a>
        @endcan

        {{-- Exportar --}}
        @can('financiero.margenes.exportar')
        <a href="{{ route('financiero.margenes.exportar', ['from' => $from, 'to' => $to, 'cost_field' => $costField, 'excluir_iva' => (int)$excluirIva]) }}"
           class="btn btn-sm btn-success d-flex align-items-center gap-2"
           style="border-radius: 9px; font-size: 12.5px;">
            <i class="fas fa-file-excel"></i> Exportar Excel
        </a>
        @endcan
    </div>
</div>

{{-- ════════════════════════════════════════════════════════════════ --}}
{{-- BLOQUE 1 — Configuración de Costo + Toggle IVA                  --}}
{{-- ════════════════════════════════════════════════════════════════ --}}
<div class="panel-card mb-4 p-3 d-flex align-items-center justify-content-between flex-wrap gap-3">

    {{-- Selector de campo de costo --}}
    <div>
        <p class="mb-2" style="font-size: 11px; font-weight: 700; text-transform: uppercase;
                               letter-spacing: .5px; color: var(--text-muted);">
            Campo de Costo para Márgenes
        </p>
        <div class="d-flex gap-2 flex-wrap" id="costFieldSelector">
            @php
                $costFields = [
                    'COS_PRO_UN' => ['label' => 'Costo Promedio',   'sub' => 'Moneda local'],
                    'ULT_COS_UN' => ['label' => 'Último Costo',     'sub' => 'Moneda local'],
                    'COS_PRO_OM' => ['label' => 'Costo Promedio',   'sub' => 'Otra moneda'],
                    'ULT_COS_OM' => ['label' => 'Último Costo',     'sub' => 'Otra moneda'],
                ];
            @endphp

            @foreach($costFields as $field => $meta)
                @can('financiero.config.costo.editar')
                <form method="POST" action="{{ route('financiero.set-cost-field') }}" style="display:inline;">
                    @csrf
                    <input type="hidden" name="cost_field" value="{{ $field }}">
                    <input type="hidden" name="from" value="{{ $from }}">
                    <input type="hidden" name="to"   value="{{ $to }}">
                    <button type="submit"
                            class="cost-pill {{ $costField === $field ? 'active' : 'inactive' }}">
                        <i class="fas fa-{{ $costField === $field ? 'circle-check' : 'circle' }}"
                           style="font-size: 11px;"></i>
                        <span>
                            {{ $meta['label'] }}
                            <small style="display:block; font-size: 9px; font-weight: 400; opacity:.7;">
                                {{ $meta['sub'] }}
                            </small>
                        </span>
                    </button>
                </form>
                @else
                <div class="cost-pill {{ $costField === $field ? 'active' : 'inactive' }}"
                     title="{{ $costField === $field ? 'Campo activo' : 'Sin permiso para cambiar' }}">
                    <i class="fas fa-{{ $costField === $field ? 'circle-check' : 'circle' }}"
                       style="font-size: 11px;"></i>
                    {{ $meta['label'] }}
                    <small style="font-size: 9px; opacity:.6;">({{ $meta['sub'] }})</small>
                </div>
                @endcan
            @endforeach
        </div>
    </div>

    {{-- Toggle IVA --}}
    <div>
        <p class="mb-2" style="font-size: 11px; font-weight: 700; text-transform: uppercase;
                               letter-spacing: .5px; color: var(--text-muted);">
            Tratamiento IVA ({{ $margenConfig['iva_rate'] }}%)
        </p>
        <div class="d-flex gap-2">
            <a href="{{ request()->fullUrlWithQuery(['excluir_iva' => 0]) }}"
               class="cost-pill {{ !$excluirIva ? 'active' : 'inactive' }}">
                <i class="fas fa-circle-check" style="font-size: 11px;"></i>
                Precio bruto
            </a>
            <a href="{{ request()->fullUrlWithQuery(['excluir_iva' => 1]) }}"
               class="cost-pill {{ $excluirIva ? 'active' : 'inactive' }}">
                <i class="fas fa-circle-minus" style="font-size: 11px;"></i>
                Sin IVA
            </a>
        </div>
    </div>

</div>

{{-- ════════════════════════════════════════════════════════════════ --}}
{{-- BLOQUE 2 — KPIs de Semáforo + Gráfico de Distribución          --}}
{{-- ════════════════════════════════════════════════════════════════ --}}
<div class="row g-3 mb-4">

    {{-- Cards de semáforo --}}
    @php
        $semCards = [
            ['label' => 'Margen Alto',   'count' => $semaforos['verde'],    'class' => 'sem-verde',    'icon' => 'fa-circle-check',     'tip' => "≥ {$margenConfig['yellow']}%"],
            ['label' => 'Margen Medio',  'count' => $semaforos['amarillo'], 'class' => 'sem-amarillo', 'icon' => 'fa-circle-half-stroke','tip' => "{$margenConfig['red']}% – {$margenConfig['yellow']}%"],
            ['label' => 'Margen Bajo',   'count' => $semaforos['rojo'],     'class' => 'sem-rojo',     'icon' => 'fa-triangle-exclamation','tip' => "< {$margenConfig['red']}%"],
            ['label' => 'Negativos',     'count' => $semaforos['negativos'],'class' => 'sem-negativo', 'icon' => 'fa-skull-crossbones',  'tip' => 'Costo > Precio'],
        ];
        $totalArts = array_sum($semaforos);
    @endphp

    @foreach($semCards as $card)
    <div class="col-6 col-xl-2 animate-in">
        <a href="{{ request()->fullUrlWithQuery(['semaforo' => str_replace('sem-', '', $card['class'])]) }}"
           style="text-decoration: none;">
        <div class="kpi-card {{ str_replace('sem-', 'accent-', $card['class']) === 'accent-verde' ? '' : str_replace('sem-', 'accent-', $card['class']) }}"
             style="padding: 16px 18px; cursor: pointer;
                    {{ $filtroSemaforo === str_replace('sem-', '', $card['class']) ? 'box-shadow: 0 0 0 2px var(--brand-primary);' : '' }}"
             title="Filtrar: {{ $card['tip'] }}">
            <div class="d-flex align-items-center gap-2 mb-2">
                <span class="sem-badge {{ $card['class'] }}" style="padding: 2px 7px;">
                    <i class="fas {{ $card['icon'] }}" style="font-size: 10px;"></i>
                </span>
            </div>
            <div class="kpi-value" style="font-size: 28px;">{{ $card['count'] }}</div>
            <div class="kpi-label">{{ $card['label'] }}</div>
            @if($totalArts > 0)
                <div style="font-size: 11px; color: var(--text-muted); margin-top: 4px;">
                    {{ number_format($card['count'] / $totalArts * 100, 1) }}% del total
                </div>
            @endif
        </div>
        </a>
    </div>
    @endforeach

    {{-- Mini gráfico donut de distribución --}}
    <div class="col-12 col-xl-4 animate-in">
        <div class="panel-card h-100" style="padding: 16px 20px;">
            <p style="font-size: 11px; font-weight: 700; text-transform: uppercase;
                      letter-spacing: .5px; color: var(--text-muted); margin-bottom: 10px;">
                Distribución de Artículos
            </p>
            <div style="display: flex; align-items: center; gap: 16px;">
                <div style="flex-shrink: 0; width: 90px; height: 90px; position: relative;">
                    <canvas id="chartDonutSem"></canvas>
                </div>
                <div style="flex: 1; font-size: 12px;">
                    @foreach([['verde','#059669','Alto'],['amarillo','#d97706','Medio'],['rojo','#dc2626','Bajo'],['negativos','#7c3aed','Neg.']] as [$k,$c,$l])
                    <div style="display:flex; align-items:center; gap:6px; margin-bottom:5px;">
                        <span style="width:8px;height:8px;border-radius:50%;background:{{$c}};flex-shrink:0;"></span>
                        <span style="flex:1; color: var(--text-secondary);">{{ $l }}</span>
                        <strong>{{ $semaforos[$k] }}</strong>
                    </div>
                    @endforeach
                </div>
            </div>
            @if($filtroSemaforo !== 'todos')
            <div class="mt-2">
                <a href="{{ request()->fullUrlWithQuery(['semaforo' => 'todos']) }}"
                   style="font-size: 11px; color: var(--brand-primary);">
                    <i class="fas fa-times-circle"></i> Quitar filtro
                </a>
            </div>
            @endif
        </div>
    </div>

</div>

{{-- ════════════════════════════════════════════════════════════════ --}}
{{-- BLOQUE 3 — Tabla de Márgenes                                     --}}
{{-- ════════════════════════════════════════════════════════════════ --}}
<div class="panel-card animate-in">
    <div class="panel-card-header">
        <div>
            <h3 class="section-title mb-0">
                Detalle de Márgenes por Artículo
                @if($filtroSemaforo !== 'todos')
                    &nbsp;<span class="sem-badge sem-{{ $filtroSemaforo }}" style="font-size: 10px;">
                        Filtro: {{ ucfirst($filtroSemaforo) }}
                    </span>
                @endif
            </h3>
            <p class="section-subtitle mb-0">
                {{ $margenesFiltradas->count() }} artículos
                @if($excluirIva) · <span style="color: var(--brand-danger);">Precio sin IVA ({{ $margenConfig['iva_rate'] }}%)</span> @endif
            </p>
        </div>

        {{-- Búsqueda en tabla (client-side) --}}
        <div style="position: relative;">
            <input type="text" id="tableSearch"
                   placeholder="Buscar artículo..."
                   class="form-control form-control-sm"
                   style="padding-left: 32px; border-radius: 9px; font-size: 12.5px; width: 210px; border-color: #e2e8f0;">
            <i class="fas fa-search" style="position: absolute; left: 10px; top: 50%;
               transform: translateY(-50%); font-size: 11px; color: var(--text-muted);"></i>
        </div>
    </div>

    <div style="overflow: auto; max-height: 60vh;">
        <table class="margenes-table" id="margenesTable">
            <thead>
                <tr>
                    <th data-col="0">Código <span class="sort-icon fas fa-sort"></span></th>
                    <th data-col="1" style="min-width: 220px;">Descripción <span class="sort-icon fas fa-sort"></span></th>
                    <th data-col="2" style="text-align:right;">Precio {{ $excluirIva ? 's/IVA' : 'Venta' }} <span class="sort-icon fas fa-sort"></span></th>
                    <th data-col="3" style="text-align:right;">Costo <span class="sort-icon fas fa-sort"></span></th>
                    <th data-col="4" style="text-align:right;">Margen $ <span class="sort-icon fas fa-sort"></span></th>
                    <th data-col="5" style="text-align:center; min-width: 130px;">Margen % <span class="sort-icon fas fa-sort"></span></th>
                    <th data-col="6" style="text-align:right;">Uds. Vendidas <span class="sort-icon fas fa-sort"></span></th>
                    <th data-col="7" style="text-align: center;">Alerta</th>
                </tr>
            </thead>
            <tbody>
                @forelse($margenesFiltradas as $item)
                @php
                    $rowClass = $item['es_negativo'] ? 'row-negativo' : 'row-' . $item['semaforo'];
                    $currency = config('app_client.locale.currency_symbol');
                    $dec      = config('app_client.locale.decimal_sep');
                    $thou     = config('app_client.locale.thousands_sep');
                    $fmt = fn(float $v) => $currency . ' ' . number_format($v, 2, $dec, $thou);

                    $barPct   = min(abs($item['margen_pct']), 100);
                    $barColor = match($item['semaforo']) {
                        'verde'    => '#059669',
                        'amarillo' => '#d97706',
                        default    => '#dc2626',
                    };
                    if($item['es_negativo']) $barColor = '#7c3aed';
                @endphp
                <tr class="{{ $rowClass }}">
                    <td style="font-family: var(--font-display); font-size: 11.5px; font-weight: 600;">
                        {{ $item['codigo'] }}
                    </td>
                    <td style="max-width: 280px; overflow: hidden; text-overflow: ellipsis;"
                        title="{{ $item['descripcion'] }}">
                        {{ $item['descripcion'] }}
                    </td>
                    <td style="text-align: right; font-weight: 500;">
                        {{ $fmt($item['precio_calculo']) }}
                    </td>
                    <td style="text-align: right; color: var(--text-secondary);">
                        {{ $fmt($item['costo']) }}
                    </td>
                    <td style="text-align: right; font-weight: 700;
                               color: {{ $item['es_negativo'] ? '#7c3aed' : ($item['margen_monto'] >= 0 ? '#059669' : '#dc2626') }};">
                        {{ $fmt($item['margen_monto']) }}
                    </td>
                    <td>
                        <div class="margin-bar-wrap">
                            <div class="margin-bar">
                                <div class="margin-bar-fill"
                                     style="width: {{ $barPct }}%; background: {{ $barColor }};"></div>
                            </div>
                            <span style="font-size: 12px; font-weight: 700; min-width: 46px;
                                         text-align: right; color: {{ $barColor }};">
                                {{ number_format($item['margen_pct'], 1, $dec, '') }}%
                            </span>
                        </div>
                    </td>
                    <td style="text-align: right;">
                        {{ number_format($item['unidades_vendidas'], 0, '.', $thou) }}
                    </td>
                    <td style="text-align: center;">
                        @if($item['es_negativo'])
                            <span class="sem-badge sem-negativo">⬛ Neg.</span>
                        @else
                            <span class="sem-badge sem-{{ $item['semaforo'] }}">
                                {{ match($item['semaforo']) {
                                    'verde'    => '▲ Alto',
                                    'amarillo' => '◆ Medio',
                                    default    => '▼ Bajo'
                                } }}
                            </span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" style="text-align: center; padding: 40px; color: var(--text-muted);">
                        <i class="fas fa-box-open fa-2x d-block mb-3 opacity-25"></i>
                        Sin artículos con ventas en el período o filtro aplicado.
                    </td>
                </tr>
                @endforelse
            </tbody>

            @if($margenesFiltradas->isNotEmpty())
            @php
                $totFac  = $margenesFiltradas->sum(fn($i) => $i['precio_calculo'] * $i['unidades_vendidas']);
                $totCos  = $margenesFiltradas->sum(fn($i) => $i['costo']          * $i['unidades_vendidas']);
                $totGan  = $totFac - $totCos;
                $totPct  = $totFac > 0 ? round($totGan / $totFac * 100, 2) : 0;
            @endphp
            <tfoot>
                <tr>
                    <td colspan="2">TOTALES ({{ $margenesFiltradas->count() }} artículos)</td>
                    <td style="text-align:right;">{{ $fmt($totFac) }}</td>
                    <td style="text-align:right;">{{ $fmt($totCos) }}</td>
                    <td style="text-align:right; color: {{ $totGan >= 0 ? '#059669' : '#dc2626' }};">
                        {{ $fmt($totGan) }}
                    </td>
                    <td style="text-align:center; font-weight:700;
                               color: {{ $margenService->semaforo($totPct) === 'verde' ? '#059669' : ($margenService->semaforo($totPct) === 'amarillo' ? '#d97706' : '#dc2626') }};">
                        {{ number_format($totPct, 1) }}%
                    </td>
                    <td colspan="2"></td>
                </tr>
            </tfoot>
            @endif
        </table>
    </div>
</div>

@endsection

@push('scripts')
<script>
(function () {
    'use strict';

    // ── Gráfico donut de distribución ─────────────────────────────────────
    const donutCtx = document.getElementById('chartDonutSem');
    if (donutCtx) {
        new Chart(donutCtx, {
            type: 'doughnut',
            data: {
                labels: {!! json_encode($chartDistribucion['labels']) !!},
                datasets: [{
                    data:            {!! json_encode($chartDistribucion['data'])   !!},
                    backgroundColor: {!! json_encode($chartDistribucion['colors']) !!},
                    borderWidth:     2,
                    borderColor:     '#fff',
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '68%',
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: '#1e293b',
                        padding: 10,
                        cornerRadius: 8,
                        callbacks: {
                            label: ctx => ` ${ctx.raw} artículos`
                        }
                    }
                }
            }
        });
    }

    // ── Búsqueda client-side ──────────────────────────────────────────────
    const searchInput = document.getElementById('tableSearch');
    const tableBody   = document.querySelector('#margenesTable tbody');

    if (searchInput && tableBody) {
        searchInput.addEventListener('input', function () {
            const term = this.value.toLowerCase().trim();
            tableBody.querySelectorAll('tr').forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = !term || text.includes(term) ? '' : 'none';
            });
        });
    }

    // ── Ordenamiento de columnas ──────────────────────────────────────────
    document.querySelectorAll('.margenes-table thead th[data-col]').forEach(th => {
        th.addEventListener('click', function () {
            const col     = parseInt(this.dataset.col);
            const tbody   = document.querySelector('#margenesTable tbody');
            const rows    = Array.from(tbody.querySelectorAll('tr'));
            const isAsc   = this.classList.contains('sorted-asc');
            const icon    = this.querySelector('.sort-icon');

            // Reset all
            document.querySelectorAll('.margenes-table thead th').forEach(h => {
                h.classList.remove('sorted-asc', 'sorted-desc');
                const ico = h.querySelector('.sort-icon');
                if (ico) { ico.className = 'sort-icon fas fa-sort'; }
            });

            // Sort direction
            const dir = isAsc ? -1 : 1;
            this.classList.add(isAsc ? 'sorted-desc' : 'sorted-asc');
            if (icon) icon.className = `sort-icon fas fa-sort-${isAsc ? 'down' : 'up'}`;

            rows.sort((a, b) => {
                const aText = a.cells[col]?.textContent.replace(/[^0-9.\-,]/g, '').replace(',', '.') || '';
                const bText = b.cells[col]?.textContent.replace(/[^0-9.\-,]/g, '').replace(',', '.') || '';
                const aVal  = parseFloat(aText) || aText;
                const bVal  = parseFloat(bText) || bText;

                if (typeof aVal === 'number' && typeof bVal === 'number') {
                    return (aVal - bVal) * dir;
                }
                return aVal.toString().localeCompare(bVal.toString()) * dir;
            });

            rows.forEach(row => tbody.appendChild(row));
        });
    });

    // ── Animar barras de margen al cargar ────────────────────────────────
    requestAnimationFrame(() => {
        document.querySelectorAll('.margin-bar-fill').forEach(bar => {
            const w = bar.style.width;
            bar.style.width = '0';
            setTimeout(() => { bar.style.width = w; }, 100);
        });
    });

})();
</script>
@endpush
