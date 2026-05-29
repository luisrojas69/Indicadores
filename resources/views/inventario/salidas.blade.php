{{-- inventario/salidas.blade.php --}}
@extends('layouts.app')
@section('title', 'Salidas No Comerciales')

@section('breadcrumb')
    <a href="{{ route('inventario.index') }}" style="color:var(--text-muted);text-decoration:none;">Inventario</a>
    <span style="color:#cbd5e1;margin:0 4px;">/</span>
    <span class="current">Salidas No Comerciales</span>
@endsection

@push('styles')
<style>
    /* Timeline */
    .timeline-day { margin-bottom: 24px; }
    .timeline-date-badge {
        display: inline-flex; align-items: center; gap: 8px;
        padding: 5px 14px; border-radius: 20px;
        font-size: 12px; font-weight: 700;
        margin-bottom: 10px; border: 1.5px solid;
    }
    .timeline-day.sospechoso .timeline-date-badge {
        background: #fef3c7; color: #92400e; border-color: #f59e0b;
    }
    .timeline-day.normal .timeline-date-badge {
        background: #f8fafc; color: var(--text-secondary); border-color: #e2e8f0;
    }
    .timeline-item {
        display: flex; align-items: flex-start; gap: 12px;
        padding: 10px 14px; border-radius: 10px;
        margin-bottom: 6px; border: 1px solid #f1f5f9;
        background: #fff; transition: all .15s;
    }
    .timeline-item:hover { box-shadow: var(--card-shadow); border-color: #e2e8f0; }
    .timeline-icon {
        width: 34px; height: 34px; border-radius: 9px;
        display: flex; align-items: center; justify-content: center;
        font-size: 13px; flex-shrink: 0;
    }

    /* Ranking */
    .ranking-row {
        display: flex; align-items: center; gap: 12px;
        padding: 11px 16px; border-bottom: 1px solid #f1f5f9;
        transition: background .1s;
    }
    .ranking-row:hover { background: #f8fafc; }
    .ranking-row:last-child { border-bottom: none; }

    /* Tipo filter chips */
    .tipo-chip {
        display: inline-flex; align-items: center; gap: 5px;
        padding: 4px 10px; border-radius: 20px; border: 1.5px solid;
        font-size: 11.5px; font-weight: 600; cursor: pointer;
        transition: all .15s; text-decoration: none; white-space: nowrap;
    }
</style>
@endpush

@section('content')

<div class="d-flex align-items-start justify-content-between flex-wrap gap-3 mb-4">
    <div>
        <h1 class="font-display mb-1" style="font-size:22px;font-weight:800;">
            Salidas No Comerciales
            @if(!empty($diasSospechosos))
            <span style="font-size:13px;padding:3px 10px;background:#fef3c7;color:#92400e;
                         border-radius:8px;font-weight:700;margin-left:8px;vertical-align:middle;">
                <i class="fas fa-radiation me-1"></i> {{ count($diasSospechosos) }} días inusuales
            </span>
            @endif
        </h1>
        <p class="mb-0" style="font-size:13px;color:var(--text-muted);">
            Período: <strong>{{ \Carbon\Carbon::parse($from)->format(config('app_client.locale.date_format')) }}
            — {{ \Carbon\Carbon::parse($to)->format(config('app_client.locale.date_format')) }}</strong>
            &nbsp;·&nbsp; Movimientos que no corresponden a ventas
        </p>
    </div>
    <div class="d-flex gap-2">
        @can('inventario.salidas.exportar')
        <a href="{{ route('inventario.reporte.exportar', ['from'=>$from,'to'=>$to]) }}"
           class="btn btn-sm btn-success" style="border-radius:9px;font-size:12.5px;">
            <i class="fas fa-file-excel me-1"></i> Exportar
        </a>
        @endcan
        <a href="{{ route('inventario.index') }}" class="btn btn-sm btn-outline-secondary" style="border-radius:9px;font-size:12.5px;">
            <i class="fas fa-arrow-left me-1"></i> Hub
        </a>
    </div>
</div>

{{-- KPI Cards --}}
<div class="row g-3 mb-4">
    <div class="col-6 col-xl-3 animate-in">
        <div class="kpi-card accent-danger">
            <div class="kpi-label">Costo Total Estimado</div>
            <div class="kpi-value" style="font-size:22px;">
                {{ config('app_client.locale.currency_symbol') }}
                {{ number_format($costoTotalSalidas, 0, config('app_client.locale.decimal_sep'), config('app_client.locale.thousands_sep')) }}
            </div>
            <div class="kpi-period">impacto económico en el período</div>
        </div>
    </div>
    <div class="col-6 col-xl-3 animate-in">
        <div class="kpi-card accent-warning">
            <div class="kpi-label">Total Movimientos</div>
            <div class="kpi-value">{{ $salidas->count() }}</div>
            <div class="kpi-period">{{ $tiposUnicos->count() }} tipos distintos</div>
        </div>
    </div>
    <div class="col-6 col-xl-3 animate-in">
        <div class="kpi-card">
            <div class="kpi-label">Artículos Afectados</div>
            <div class="kpi-value">{{ $salidas->pluck('articulo_codigo')->unique()->count() }}</div>
            <div class="kpi-period">artículos únicos con salidas</div>
        </div>
    </div>
    <div class="col-6 col-xl-3 animate-in">
        <div class="kpi-card {{ !empty($diasSospechosos) ? 'accent-warning' : 'accent-success' }}">
            <div class="kpi-label">Días con Actividad Inusual</div>
            <div class="kpi-value">{{ count($diasSospechosos) }}</div>
            <div class="kpi-period">días con volumen ≥ 2× promedio</div>
        </div>
    </div>
</div>

{{-- Layout principal: Gráfico + Ranking | Timeline --}}
<div class="row g-4">

    {{-- Columna izquierda: gráfico por tipo + ranking --}}
    <div class="col-12 col-xl-5">

        {{-- Gráfico de tipos --}}
        <div class="panel-card mb-4 animate-in">
            <div class="panel-card-header">
                <h3 class="section-title mb-0">
                    <i class="fas fa-chart-pie me-2" style="color:#ea580c;font-size:13px;"></i>
                    Costo por Tipo de Movimiento
                </h3>
            </div>
            <div class="panel-card-body">
                <div style="height:200px;position:relative;">
                    <canvas id="chartTipos"></canvas>
                </div>
                {{-- Leyenda detallada --}}
                <div class="mt-3">
                    @foreach($conteoTipos as $tipo => $data)
                    <div style="display:flex;align-items:center;gap:8px;padding:5px 0;border-bottom:1px solid #f1f5f9;">
                        <i class="fas {{ $data['icon'] }}" style="color:{{ $data['color'] }};font-size:12px;width:16px;text-align:center;"></i>
                        <span style="flex:1;font-size:12px;color:var(--text-secondary);">{{ $tipo }}</span>
                        <span style="font-size:12px;font-weight:600;color:var(--text-primary);">{{ $data['count'] }}</span>
                        <span style="font-size:12px;color:{{ $data['color'] }};font-weight:700;min-width:80px;text-align:right;">
                            {{ config('app_client.locale.currency_symbol') }}
                            {{ number_format($data['costo'], 0, config('app_client.locale.decimal_sep'), config('app_client.locale.thousands_sep')) }}
                        </span>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- Ranking de artículos --}}
        <div class="panel-card animate-in">
            <div class="panel-card-header">
                <div>
                    <h3 class="section-title mb-0">
                        <i class="fas fa-ranking-star me-2" style="color:#dc2626;font-size:13px;"></i>
                        Artículos con Más Pérdidas
                    </h3>
                    <p class="section-subtitle mb-0">ranking por costo estimado total</p>
                </div>
            </div>
            <div style="max-height:340px;overflow-y:auto;">
                @foreach($ranking->take(15) as $idx => $art)
                @php
                    $rc = $art['riesgo_color'];
                    $rank = $idx + 1;
                @endphp
                <div class="ranking-row">
                    <span style="width:24px;text-align:center;font-family:var(--font-display);
                                 font-size:13px;font-weight:800;
                                 color:{{ $rank <= 3 ? '#dc2626' : 'var(--text-muted)' }};">
                        {{ $rank }}
                    </span>
                    <div style="flex:1;min-width:0;">
                        <div style="font-size:12.5px;font-weight:600;white-space:nowrap;
                                    overflow:hidden;text-overflow:ellipsis;"
                             title="{{ $art['descripcion'] }}">
                            {{ $art['descripcion'] }}
                        </div>
                        <div style="font-size:10.5px;color:var(--text-muted);">
                            {{ implode(' · ', array_slice($art['tipos'], 0, 2)) }}
                            @if(count($art['tipos']) > 2) +{{ count($art['tipos'])-2 }} más @endif
                        </div>
                    </div>
                    <div style="text-align:right;flex-shrink:0;">
                        <div style="font-size:12.5px;font-weight:700;color:{{ $rc }};">
                            {{ config('app_client.locale.currency_symbol') }}
                            {{ number_format($art['costo_total'], 0, config('app_client.locale.decimal_sep'), config('app_client.locale.thousands_sep')) }}
                        </div>
                        <div style="font-size:10px;color:var(--text-muted);">{{ $art['total_salidas'] }} mov.</div>
                    </div>
                </div>
                @endforeach
                @if($ranking->isEmpty())
                <div style="text-align:center;padding:32px;color:var(--text-muted);">
                    Sin salidas registradas en el período.
                </div>
                @endif
            </div>
        </div>

    </div>

    {{-- Columna derecha: Timeline --}}
    <div class="col-12 col-xl-7 animate-in">
        <div class="panel-card h-100">
            <div class="panel-card-header">
                <div>
                    <h3 class="section-title mb-0">
                        <i class="fas fa-timeline me-2" style="color:#7c3aed;font-size:13px;"></i>
                        Timeline de Movimientos
                    </h3>
                    <p class="section-subtitle mb-0">ordenados del más reciente al más antiguo</p>
                </div>

                {{-- Filtro por tipo --}}
                <div style="display:flex;gap:6px;flex-wrap:wrap;max-width:320px;justify-content:flex-end;">
                    <a href="{{ request()->fullUrlWithQuery(['tipo'=>'todos']) }}"
                       class="tipo-chip"
                       style="border-color:{{ $filtroTipo==='todos' ? 'var(--brand-primary)' : '#e2e8f0' }};
                              background:{{ $filtroTipo==='todos' ? '#eff6ff' : '#f8fafc' }};
                              color:{{ $filtroTipo==='todos' ? 'var(--brand-primary)' : 'var(--text-muted)' }};">
                        Todos
                    </a>
                    @foreach($tiposUnicos as $tipo)
                    @php $tipoDat = $conteoTipos[$tipo] ?? null; @endphp
                    <a href="{{ request()->fullUrlWithQuery(['tipo' => $tipo]) }}"
                       class="tipo-chip"
                       style="border-color:{{ $filtroTipo===$tipo ? ($tipoDat['color'] ?? '#64748b') : '#e2e8f0' }};
                              background:{{ $filtroTipo===$tipo ? 'rgba(0,0,0,.04)' : '#f8fafc' }};
                              color:{{ $filtroTipo===$tipo ? ($tipoDat['color'] ?? '#64748b') : 'var(--text-muted)' }};">
                        @if($tipoDat) <i class="fas {{ $tipoDat['icon'] }}" style="font-size:10px;"></i> @endif
                        {{ $tipo }}
                    </a>
                    @endforeach
                </div>
            </div>

            <div class="panel-card-body" style="max-height:700px;overflow-y:auto;">
                @if($salidasFiltradas->isEmpty())
                <div style="text-align:center;padding:48px;color:var(--text-muted);">
                    <i class="fas fa-check-circle fa-2x d-block mb-3" style="color:#059669;opacity:.4;"></i>
                    Sin movimientos en el filtro seleccionado.
                </div>
                @else
                @foreach($timeline as $fecha => $movimientos)
                @php
                    $esSospechoso = in_array($fecha, $diasSospechosos);
                    $costoDia = $movimientos->sum('costo_estimado');
                @endphp
                <div class="timeline-day {{ $esSospechoso ? 'sospechoso' : 'normal' }}">
                    <div class="timeline-date-badge">
                        @if($esSospechoso)
                            <i class="fas fa-radiation" style="font-size:11px;"></i>
                        @else
                            <i class="fas fa-calendar-day" style="font-size:11px;"></i>
                        @endif
                        {{ \Carbon\Carbon::parse($fecha)->format(config('app_client.locale.date_format')) }}
                        @if($esSospechoso)
                            <span style="font-size:10px;opacity:.8;">&mdash; Actividad inusual</span>
                        @endif
                        <span style="margin-left:auto;font-size:10.5px;opacity:.75;">
                            {{ $movimientos->count() }} mov. ·
                            {{ config('app_client.locale.currency_symbol') }}
                            {{ number_format($costoDia, 0, config('app_client.locale.decimal_sep'), config('app_client.locale.thousands_sep')) }}
                        </span>
                    </div>

                    @foreach($movimientos as $mov)
                    {{-- Solo mostrar si pasa el filtro de tipo --}}
                    @if($filtroTipo === 'todos' || $mov['tipo_label'] === $filtroTipo)
                    <div class="timeline-item">
                        <div class="timeline-icon"
                             style="background:{{ $mov['tipo_color'] }}1a;color:{{ $mov['tipo_color'] }};">
                            <i class="fas {{ $mov['tipo_icon'] }}"></i>
                        </div>
                        <div style="flex:1;min-width:0;">
                            <div style="font-size:12.5px;font-weight:600;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"
                                 title="{{ $mov['articulo_descripcion'] }}">
                                {{ $mov['articulo_descripcion'] }}
                            </div>
                            <div style="font-size:11px;color:var(--text-muted);margin-top:2px;">
                                <span style="background:{{ $mov['tipo_color'] }}1a;color:{{ $mov['tipo_color'] }};
                                             padding:1px 6px;border-radius:6px;font-weight:600;margin-right:6px;">
                                    {{ $mov['tipo_label'] }}
                                </span>
                                Ajuste #{{ $mov['numero_ajuste'] }}
                                &nbsp;·&nbsp; Cód. {{ $mov['articulo_codigo'] }}
                            </div>
                        </div>
                        <div style="text-align:right;flex-shrink:0;">
                            <div style="font-size:13px;font-weight:700;color:#dc2626;">
                                {{ number_format(abs($mov['cantidad']), 0, '.', '.') }} uds.
                            </div>
                            <div style="font-size:11px;color:var(--text-muted);">
                                ~{{ config('app_client.locale.currency_symbol') }}
                                {{ number_format($mov['costo_estimado'], 2, config('app_client.locale.decimal_sep'), config('app_client.locale.thousands_sep')) }}
                            </div>
                        </div>
                    </div>
                    @endif
                    @endforeach
                </div>
                @endforeach
                @endif
            </div>
        </div>
    </div>

</div>

@endsection

@push('scripts')
<script>
(function(){
    const ctx = document.getElementById('chartTipos');
    if (ctx) {
        new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels:   {!! $chartTiposLabels !!},
                datasets:[{
                    data: {!! $chartTiposCostos !!},
                    backgroundColor: {!! $chartTiposColors !!},
                    borderWidth: 2, borderColor: '#fff',
                }]
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                cutout: '62%',
                plugins:{
                    legend:{ display:false },
                    tooltip:{
                        backgroundColor:'#1e293b', padding:10, cornerRadius:8,
                        callbacks:{ label: c => ` ${c.label}: $${c.parsed.toLocaleString('es-VE',{minimumFractionDigits:0})}` }
                    }
                }
            }
        });
    }
})();
</script>
@endpush
