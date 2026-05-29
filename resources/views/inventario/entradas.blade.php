{{-- inventario/entradas.blade.php --}}
@extends('layouts.app')
@section('title', 'Entradas vs Compras')

@section('breadcrumb')
    <a href="{{ route('inventario.index') }}" style="color:var(--text-muted);text-decoration:none;">Inventario</a>
    <span style="color:#cbd5e1;margin:0 4px;">/</span>
    <span class="current">Entradas vs Compras</span>
@endsection

@push('styles')
<style>
    .entradas-table { width:100%; border-collapse:separate; border-spacing:0; }
    .entradas-table thead th {
        background:#0f172a; color:rgba(255,255,255,.8);
        font-size:10.5px; font-weight:700; letter-spacing:.5px;
        text-transform:uppercase; padding:10px 12px; white-space:nowrap;
        border-bottom:2px solid #7c3aed; position:sticky; top:0; z-index:2;
    }
    .entradas-table tbody td {
        padding:9px 12px; font-size:12.5px; border-bottom:1px solid #f1f5f9; vertical-align:middle;
    }
    .entradas-table tbody tr:hover td { filter:brightness(.97); }
    .alerta-pill {
        display:inline-flex; align-items:center; gap:5px;
        padding:3px 9px; border-radius:20px;
        font-size:11px; font-weight:700; white-space:nowrap;
    }
    .rcv-bar-wrap { display:flex; align-items:center; gap:7px; min-width:100px; }
    .rcv-bar { flex:1; height:5px; background:#e2e8f0; border-radius:99px; overflow:hidden; }
    .rcv-bar-fill { height:100%; border-radius:99px; transition:width .8s ease; }
</style>
@endpush

@section('content')
<div class="d-flex align-items-start justify-content-between flex-wrap gap-3 mb-4">
    <div>
        <h1 class="font-display mb-1" style="font-size:22px;font-weight:800;">Entradas vs Compras</h1>
        <p class="mb-0" style="font-size:13px;color:var(--text-muted);">
            Período: <strong>{{ \Carbon\Carbon::parse($from)->format(config('app_client.locale.date_format')) }}
            — {{ \Carbon\Carbon::parse($to)->format(config('app_client.locale.date_format')) }}</strong>
        </p>
    </div>
    <a href="{{ route('inventario.index') }}" class="btn btn-sm btn-outline-secondary" style="border-radius:9px;font-size:12.5px;">
        <i class="fas fa-arrow-left me-1"></i> Hub
    </a>
</div>

{{-- KPIs de cumplimiento --}}
<div class="row g-3 mb-4">
    <div class="col-6 col-xl-3 animate-in">
        <div class="kpi-card">
            <div class="kpi-label">Total Órdenes Cruzadas</div>
            <div class="kpi-value">{{ $conteo['total'] }}</div>
        </div>
    </div>
    <div class="col-6 col-xl-3 animate-in">
        <div class="kpi-card accent-success">
            <div class="kpi-label">% Cumplimiento Global</div>
            <div class="kpi-value">{{ number_format($pctCumplimiento, 1) }}%</div>
            <div class="kpi-delta {{ $pctCumplimiento >= 90 ? 'up' : ($pctCumplimiento >= 70 ? 'flat' : 'down') }}">
                <i class="fas {{ $pctCumplimiento >= 90 ? 'fa-check' : 'fa-triangle-exclamation' }}" style="font-size:10px;"></i>
                {{ number_format($totalRecibido, 0, '.', '.') }} / {{ number_format($totalOrdenado, 0, '.', '.') }} uds.
            </div>
        </div>
    </div>
    <div class="col-6 col-xl-3 animate-in">
        <div class="kpi-card accent-danger">
            <div class="kpi-label">Discrepancias Críticas</div>
            <div class="kpi-value">{{ $conteo['critico'] + $conteo['sin_entrada'] }}</div>
            <div class="kpi-period">Sin entrada: {{ $conteo['sin_entrada'] }} · Crítico: {{ $conteo['critico'] }}</div>
        </div>
    </div>
    <div class="col-6 col-xl-3 animate-in">
        <div class="kpi-card accent-warning">
            <div class="kpi-label">Diferencia Total (uds.)</div>
            <div class="kpi-value">{{ number_format($totalDiferencia, 0, '.', config('app_client.locale.thousands_sep')) }}</div>
            <div class="kpi-period">unidades no recibidas</div>
        </div>
    </div>
</div>

{{-- Filtros de alerta --}}
<div class="d-flex gap-2 flex-wrap mb-3">
    @foreach([
        ['todos',       'Todas',       '#64748b','#f8fafc'],
        ['ok',          'Completo',    '#059669','#dcfce7'],
        ['parcial',     'Parcial',     '#d97706','#fef3c7'],
        ['critico',     'Crítico',     '#dc2626','#fee2e2'],
        ['sin_entrada', 'Sin entrada', '#7c3aed','#ede9fe'],
        ['leve',        'Leve',        '#0891b2','#e0f2fe'],
    ] as [$k,$l,$c,$bg])
    <a href="{{ request()->fullUrlWithQuery(['alerta' => $k]) }}"
       class="nivel-filter-btn"
       style="border-color:{{$filtroAlerta===$k ? $c : '#e2e8f0'}};
              background:{{$filtroAlerta===$k ? $bg : '#f8fafc'}};
              color:{{$filtroAlerta===$k ? $c : 'var(--text-muted)'}};">
        {{ $l }}
        @if($k !== 'todos')
            <span style="background:{{$c}};color:#fff;border-radius:99px;padding:1px 6px;font-size:10px;">
                {{ $conteo[$k] ?? 0 }}
            </span>
        @endif
    </a>
    @endforeach

    <div style="position:relative;margin-left:auto;">
        <input type="text" id="entradasSearch" placeholder="Buscar orden / artículo / proveedor..."
               value="{{ $search }}"
               class="form-control form-control-sm"
               style="padding-left:32px;border-radius:9px;font-size:12.5px;width:250px;">
        <i class="fas fa-search" style="position:absolute;left:10px;top:50%;transform:translateY(-50%);font-size:11px;color:var(--text-muted);"></i>
    </div>
</div>

{{-- Tabla --}}
<div class="panel-card animate-in">
    <div style="overflow:auto;max-height:62vh;">
        <table class="entradas-table" id="entradasTable">
            <thead>
                <tr>
                    <th>N° Orden</th>
                    <th>Fecha</th>
                    <th style="min-width:160px;">Proveedor</th>
                    <th>Artículo</th>
                    <th style="min-width:220px;">Descripción</th>
                    <th style="text-align:right;">Ordenado</th>
                    <th style="text-align:right;">Recibido</th>
                    <th style="text-align:right;">Diferencia</th>
                    <th style="min-width:120px;text-align:center;">% Recibido</th>
                    <th style="text-align:center;">Estado</th>
                </tr>
            </thead>
            <tbody>
                @forelse($entradasFiltradas as $item)
                @php
                    $alertaBg = match($item['alerta']) {
                        'critico','sin_entrada' => $item['alerta_bg'],
                        default => 'transparent',
                    };
                @endphp
                <tr style="background:{{$alertaBg}}">
                    <td style="font-family:var(--font-display);font-size:11.5px;font-weight:600;">
                        {{ $item['numero_orden'] }}
                    </td>
                    <td style="color:var(--text-secondary);font-size:12px;">{{ $item['fecha'] }}</td>
                    <td style="max-width:180px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"
                        title="{{ $item['proveedor'] }}">
                        {{ $item['proveedor'] }}
                    </td>
                    <td style="font-weight:700;font-size:11.5px;color:var(--brand-primary);">
                        {{ $item['articulo_codigo'] }}
                    </td>
                    <td style="max-width:240px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"
                        title="{{ $item['articulo_descripcion'] }}">
                        {{ $item['articulo_descripcion'] }}
                    </td>
                    <td style="text-align:right;">{{ number_format($item['cantidad_ordenada'], 0, '.', '.') }}</td>
                    <td style="text-align:right;color:#059669;font-weight:600;">
                        {{ number_format($item['cantidad_recibida'], 0, '.', '.') }}
                    </td>
                    <td style="text-align:right;font-weight:700;
                               color:{{ $item['diferencia'] > 0 ? '#dc2626' : '#059669' }};">
                        {{ $item['diferencia'] > 0 ? '-' : '' }}{{ number_format(abs($item['diferencia']), 0, '.', '.') }}
                    </td>
                    <td>
                        <div class="rcv-bar-wrap">
                            <div class="rcv-bar">
                                <div class="rcv-bar-fill"
                                     style="width:{{ $item['pct_recibido'] }}%;background:{{ $item['alerta_color'] }};"></div>
                            </div>
                            <span style="font-size:11.5px;font-weight:700;min-width:38px;text-align:right;
                                         color:{{ $item['alerta_color'] }};">
                                {{ number_format($item['pct_recibido'], 0) }}%
                            </span>
                        </div>
                    </td>
                    <td style="text-align:center;">
                        <span class="alerta-pill"
                              style="background:{{ $item['alerta_bg'] }};color:{{ $item['alerta_color'] }};">
                            {{ $item['alerta_label'] }}
                        </span>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="10" style="text-align:center;padding:48px;color:var(--text-muted);">
                        <i class="fas fa-box-open fa-2x d-block mb-3 opacity-25"></i>
                        Sin órdenes de compra en el período seleccionado.
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
    document.getElementById('entradasSearch')?.addEventListener('input', function(){
        const t = this.value.toLowerCase();
        document.querySelectorAll('#entradasTable tbody tr').forEach(r => {
            r.style.display = !t || r.textContent.toLowerCase().includes(t) ? '' : 'none';
        });
    });
    requestAnimationFrame(()=>{
        document.querySelectorAll('.rcv-bar-fill').forEach(b=>{
            const w=b.style.width; b.style.width='0'; setTimeout(()=>b.style.width=w, 150);
        });
    });
})();
</script>
@endpush
