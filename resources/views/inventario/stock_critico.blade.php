{{-- inventario/stock_critico.blade.php --}}
@extends('layouts.app')
@section('title', 'Stock Crítico')

@section('breadcrumb')
    <a href="{{ route('inventario.index') }}" style="color:var(--text-muted);text-decoration:none;">Inventario</a>
    <span style="color:#cbd5e1;margin:0 4px;">/</span>
    <span class="current">Stock Crítico</span>
@endsection

@section('hide_daterange', true)

@push('styles')
<style>
    .nivel-filter-btn {
        display: inline-flex; align-items: center; gap: 6px;
        padding: 6px 14px; border-radius: 20px; border: 1.5px solid;
        font-size: 12px; font-weight: 600; cursor: pointer;
        transition: all .15s; text-decoration: none;
    }
    .stock-table { width:100%; border-collapse:separate; border-spacing:0; }
    .stock-table thead th {
        background:#0f172a; color:rgba(255,255,255,.8);
        font-size:10.5px; font-weight:700; letter-spacing:.5px;
        text-transform:uppercase; padding:10px 12px;
        border-bottom:2px solid var(--brand-primary);
        white-space:nowrap; position:sticky; top:0; z-index:2;
    }
    .stock-table tbody td {
        padding:9px 12px; font-size:12.5px;
        border-bottom:1px solid #f1f5f9; vertical-align:middle;
    }
    .stock-table tbody tr:hover td { filter:brightness(.97); }

    .pct-bar-wrap { display:flex; align-items:center; gap:8px; min-width:100px; }
    .pct-bar      { flex:1; height:6px; background:#e2e8f0; border-radius:99px; overflow:hidden; }
    .pct-bar-fill { height:100%; border-radius:99px; transition:width .8s ease; }
</style>
@endpush

@section('content')

{{-- Header --}}
<div class="d-flex align-items-start justify-content-between flex-wrap gap-3 mb-4">
    <div>
        <h1 class="font-display mb-1" style="font-size:22px;font-weight:800;">Stock Crítico</h1>
        <p class="mb-0" style="font-size:13px;color:var(--text-muted);">
            Corte al <strong>{{ now()->format(config('app_client.locale.date_format')) }}</strong>
            &nbsp;·&nbsp; Artículos con stock ≤ mínimo configurado
        </p>
    </div>
    <a href="{{ route('inventario.index') }}" class="btn btn-sm btn-outline-secondary" style="border-radius:9px;font-size:12.5px;">
        <i class="fas fa-arrow-left me-1"></i> Hub
    </a>
</div>

{{-- KPI Cards de niveles --}}
<div class="row g-3 mb-4">
    @foreach([
        ['critico','#dc2626','#fee2e2','Crítico','fa-skull','Stock = 0 o ≤20% del mínimo'],
        ['bajo',   '#d97706','#fef3c7','Bajo',   'fa-triangle-exclamation','Stock entre 20% y 80% del mínimo'],
        ['alerta', '#0891b2','#e0f2fe','Alerta', 'fa-circle-exclamation','Stock entre 80% y 100% del mínimo'],
    ] as [$k,$c,$bg,$l,$ico,$tip])
    <div class="col-12 col-sm-4 animate-in">
        <a href="{{ request()->fullUrlWithQuery(['nivel' => $k]) }}" style="text-decoration:none;">
        <div style="background:{{$bg}};border-radius:14px;padding:18px 20px;
                    border:2px solid {{$filtroNivel===$k ? $c : 'transparent'}};
                    cursor:pointer;transition:all .2s;"
             title="{{ $tip }}">
            <div style="display:flex;align-items:center;gap:10px;margin-bottom:8px;">
                <i class="fas {{$ico}}" style="color:{{$c}};font-size:16px;"></i>
                <span style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:{{$c}};">{{$l}}</span>
                @if($filtroNivel===$k)
                    <span style="margin-left:auto;font-size:10px;background:{{$c}};color:#fff;padding:1px 6px;border-radius:8px;">Activo</span>
                @endif
            </div>
            <div style="font-family:var(--font-display);font-size:36px;font-weight:800;color:{{$c}};line-height:1;">
                {{ $niveles[$k] }}
            </div>
            <div style="font-size:11px;color:{{$c}};opacity:.7;margin-top:4px;">artículos</div>
        </div>
        </a>
    </div>
    @endforeach
</div>

{{-- Gráfico de déficit top críticos --}}
@if($stock->where('nivel','critico')->isNotEmpty())
<div class="panel-card mb-4 animate-in">
    <div class="panel-card-header">
        <div>
            <h3 class="section-title mb-0">
                <i class="fas fa-chart-bar me-2" style="color:#dc2626;font-size:13px;"></i>
                Top Artículos Críticos — Déficit de Stock
            </h3>
            <p class="section-subtitle mb-0">unidades faltantes para alcanzar el stock mínimo</p>
        </div>
        @if($filtroNivel !== 'todos')
        <a href="{{ request()->fullUrlWithQuery(['nivel'=>'todos']) }}"
           style="font-size:12px;color:var(--brand-primary);">
            <i class="fas fa-times-circle"></i> Quitar filtro
        </a>
        @endif
    </div>
    <div class="panel-card-body">
        <div style="height:220px;position:relative;">
            <canvas id="chartDeficit"></canvas>
        </div>
    </div>
</div>
@endif

{{-- Tabla --}}
<div class="panel-card animate-in">
    <div class="panel-card-header">
        <div>
            <h3 class="section-title mb-0">
                Artículos con Stock Bajo Mínimo
                @if($filtroNivel !== 'todos')
                <span style="font-size:11px;padding:2px 8px;border-radius:10px;
                             background:{{ $filtroNivel==='critico' ? '#fee2e2' : ($filtroNivel==='bajo' ? '#fef3c7' : '#e0f2fe') }};
                             color:{{ $filtroNivel==='critico' ? '#b91c1c' : ($filtroNivel==='bajo' ? '#92400e' : '#0c4a6e') }};
                             font-weight:700;margin-left:6px;">
                    {{ ucfirst($filtroNivel) }}
                </span>
                @endif
            </h3>
            <p class="section-subtitle mb-0">{{ $stockFiltrado->count() }} artículos mostrados</p>
        </div>
        <div style="position:relative;">
            <input type="text" id="stockSearch" placeholder="Buscar artículo..."
                   value="{{ $search }}"
                   class="form-control form-control-sm"
                   style="padding-left:32px;border-radius:9px;font-size:12.5px;width:210px;">
            <i class="fas fa-search" style="position:absolute;left:10px;top:50%;transform:translateY(-50%);font-size:11px;color:var(--text-muted);"></i>
        </div>
    </div>

    <div style="overflow:auto;max-height:55vh;">
        <table class="stock-table" id="stockTable">
            <thead>
                <tr>
                    <th>Código</th>
                    <th style="min-width:220px;">Descripción</th>
                    <th style="text-align:right;">Stock Actual</th>
                    <th style="text-align:right;">Stock Mínimo</th>
                    <th style="text-align:right;">Comprometido</th>
                    <th style="text-align:right;">Stock Libre</th>
                    <th style="min-width:120px;text-align:center;">Cobertura</th>
                    <th style="text-align:right;">Déficit</th>
                    <th style="text-align:center;">Nivel</th>
                </tr>
            </thead>
            <tbody>
                @forelse($stockFiltrado as $item)
                @php
                    $nc = $item['nivel_color'];
                    $nb = $item['nivel_bg'];
                @endphp
                <tr style="background:{{ $item['nivel']==='critico' ? '#fff5f5' : ($item['nivel']==='bajo' ? '#fffbeb' : '#f0f9ff') }}">
                    <td style="font-family:var(--font-display);font-size:11.5px;font-weight:700;color:{{$nc}};">
                        {{ $item['codigo'] }}
                    </td>
                    <td style="max-width:260px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"
                        title="{{ $item['descripcion'] }}">
                        {{ $item['descripcion'] }}
                    </td>
                    <td style="text-align:right;font-weight:700;color:{{$nc}};">
                        {{ number_format($item['stock_actual'], 0, '.', config('app_client.locale.thousands_sep')) }}
                    </td>
                    <td style="text-align:right;color:var(--text-secondary);">
                        {{ number_format($item['stock_minimo'], 0, '.', config('app_client.locale.thousands_sep')) }}
                    </td>
                    <td style="text-align:right;color:#7c3aed;font-size:12px;">
                        {{ number_format($item['stock_comprometido'], 0, '.', config('app_client.locale.thousands_sep')) }}
                    </td>
                    <td style="text-align:right;font-weight:600;">
                        {{ number_format($item['stock_libre'], 0, '.', config('app_client.locale.thousands_sep')) }}
                    </td>
                    <td>
                        <div class="pct-bar-wrap">
                            <div class="pct-bar">
                                <div class="pct-bar-fill"
                                     style="width:{{ $item['pct_cubierto'] }}%;background:{{$nc}};"></div>
                            </div>
                            <span style="font-size:11.5px;font-weight:700;color:{{$nc}};min-width:38px;text-align:right;">
                                {{ number_format($item['pct_cubierto'], 0) }}%
                            </span>
                        </div>
                    </td>
                    <td style="text-align:right;font-weight:700;color:#dc2626;">
                        {{ number_format($item['deficit'], 0, '.', config('app_client.locale.thousands_sep')) }}
                    </td>
                    <td style="text-align:center;">
                        <span style="display:inline-block;padding:3px 9px;border-radius:20px;
                                     background:{{$nb}};color:{{$nc}};
                                     font-size:11px;font-weight:700;">
                            {{ $item['nivel_label'] }}
                        </span>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="9" style="text-align:center;padding:48px;color:var(--text-muted);">
                        <i class="fas fa-check-circle fa-2x d-block mb-3" style="color:#059669;opacity:.4;"></i>
                        No hay artículos en este nivel de alerta.
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
(function(){
    // ── Gráfico de déficit ────────────────────────────────────────────────
    const ctx = document.getElementById('chartDeficit');
    if (ctx) {
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels:   {!! $chartLabels !!},
                datasets: [{
                    label: 'Déficit (unidades)',
                    data:  {!! $chartDeficit !!},
                    backgroundColor: 'rgba(220,38,38,.75)',
                    borderRadius: 6, barPercentage: .72,
                }]
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                plugins: { legend:{display:false}, tooltip:{
                    backgroundColor:'#1e293b', padding:10, cornerRadius:8,
                    callbacks:{ label: c => ` ${c.parsed.y} unidades de déficit` }
                }},
                scales: {
                    x:{ grid:{display:false}, ticks:{font:{size:10}} },
                    y:{ grid:{color:'rgba(0,0,0,.04)',drawBorder:false}, ticks:{font:{size:10}} }
                }
            }
        });
    }

    // ── Búsqueda client-side ──────────────────────────────────────────────
    document.getElementById('stockSearch')?.addEventListener('input', function(){
        const t = this.value.toLowerCase();
        document.querySelectorAll('#stockTable tbody tr').forEach(row => {
            row.style.display = !t || row.textContent.toLowerCase().includes(t) ? '' : 'none';
        });
    });

    // ── Animar barras ────────────────────────────────────────────────────
    requestAnimationFrame(() => {
        document.querySelectorAll('.pct-bar-fill').forEach(b => {
            const w = b.style.width; b.style.width='0';
            setTimeout(() => b.style.width=w, 120);
        });
    });
})();
</script>
@endpush
