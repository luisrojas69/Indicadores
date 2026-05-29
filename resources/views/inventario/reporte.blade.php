{{--
    inventario/reporte.blade.php
    Reporte consolidado de inventario — vista previa antes de exportar.
    Variables: $resumen, $stock, $salidas, $entradas, $from, $to
--}}
@extends('layouts.app')
@section('title', 'Reporte Consolidado de Inventario')

@section('breadcrumb')
    <a href="{{ route('inventario.index') }}" style="color:var(--text-muted);text-decoration:none;">Inventario</a>
    <span style="color:#cbd5e1;margin:0 4px;">/</span>
    <span class="current">Reporte Consolidado</span>
@endsection

@push('styles')
<style>
    .report-section {
        background: var(--card-bg);
        border-radius: var(--card-radius);
        box-shadow: var(--card-shadow);
        border: 1px solid rgba(0,0,0,.04);
        overflow: hidden;
        margin-bottom: 24px;
    }
    .report-section-header {
        padding: 14px 22px;
        border-bottom: 1px solid #f1f5f9;
        display: flex; align-items: center; justify-content: space-between;
        background: #f8fafc;
    }
    .report-section-body { padding: 20px 22px; }

    .summary-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 16px;
    }
    .summary-stat {
        padding: 16px 18px;
        border-radius: 12px;
        border: 1.5px solid;
    }
    .summary-stat-label {
        font-size: 11px; font-weight: 700; text-transform: uppercase;
        letter-spacing: .5px; color: var(--text-muted); margin-bottom: 6px;
    }
    .summary-stat-value {
        font-family: var(--font-display); font-size: 28px;
        font-weight: 800; line-height: 1; letter-spacing: -.5px;
    }
    .summary-stat-sub {
        font-size: 11px; margin-top: 5px; opacity: .7;
    }

    /* Mini tabla de preview */
    .preview-table { width:100%; border-collapse:collapse; font-size:12px; }
    .preview-table thead th {
        background: #f1f5f9; color: var(--text-muted);
        font-size:10px; font-weight:700; text-transform:uppercase;
        letter-spacing:.5px; padding:8px 10px;
        border-bottom:1px solid #e2e8f0;
    }
    .preview-table tbody td {
        padding:8px 10px; border-bottom:1px solid #f8fafc;
        color: var(--text-primary); vertical-align:middle;
    }
    .preview-table tbody tr:last-child td { border-bottom:none; }
    .preview-table tbody tr:hover td { background:#f8fafc; }
</style>
@endpush

@section('content')

{{-- ── Header ────────────────────────────────────────────────────────── --}}
<div class="d-flex align-items-start justify-content-between flex-wrap gap-3 mb-4">
    <div>
        <h1 class="font-display mb-1" style="font-size:22px;font-weight:800;">
            Reporte Consolidado de Inventario
        </h1>
        <p class="mb-0" style="font-size:13px;color:var(--text-muted);">
            Período:
            <strong>{{ \Carbon\Carbon::parse($from)->format(config('app_client.locale.date_format')) }}
            — {{ \Carbon\Carbon::parse($to)->format(config('app_client.locale.date_format')) }}</strong>
            &nbsp;·&nbsp; Generado: <strong>{{ $resumen['generado_en'] }}</strong>
        </p>
    </div>

    <div class="d-flex gap-2 align-items-center">
        <a href="{{ route('inventario.index') }}"
           class="btn btn-sm btn-outline-secondary" style="border-radius:9px;font-size:12.5px;">
            <i class="fas fa-arrow-left me-1"></i> Hub
        </a>
        @can('inventario.reporte.consolidado.exportar')
        <a href="{{ route('inventario.reporte.exportar', ['from'=>$from,'to'=>$to]) }}"
           class="btn btn-sm btn-success d-flex align-items-center gap-2"
           style="border-radius:9px;font-size:12.5px;">
            <i class="fas fa-file-excel"></i> Descargar Excel (3 hojas)
        </a>
        @endcan
    </div>
</div>

{{-- ════════════════════════════════════════════════════════════════ --}}
{{-- BLOQUE 1 — Resumen Ejecutivo                                    --}}
{{-- ════════════════════════════════════════════════════════════════ --}}
<div class="report-section animate-in">
    <div class="report-section-header">
        <div>
            <h3 class="section-title mb-0">
                <i class="fas fa-clipboard-check me-2" style="color:var(--brand-primary);font-size:13px;"></i>
                Resumen Ejecutivo
            </h3>
            <p class="section-subtitle mb-0">Vista rápida del estado del inventario</p>
        </div>
    </div>
    <div class="report-section-body">
        <div class="summary-grid">

            {{-- Stock Crítico --}}
            <div class="summary-stat"
                 style="border-color:#dc2626;background:#fff5f5;">
                <div class="summary-stat-label">
                    <i class="fas fa-triangle-exclamation me-1" style="color:#dc2626;"></i>
                    Stock Crítico
                </div>
                <div class="summary-stat-value" style="color:#dc2626;">
                    {{ $resumen['stock']['critico'] + $resumen['stock']['bajo'] + $resumen['stock']['alerta'] }}
                </div>
                <div class="summary-stat-sub" style="color:#dc2626;">
                    artículos bajo mínimo
                </div>
                <div style="margin-top:8px;display:flex;gap:10px;font-size:11px;">
                    <span style="color:#dc2626;font-weight:700;">{{ $resumen['stock']['critico'] }} críticos</span>
                    <span style="color:#d97706;font-weight:700;">{{ $resumen['stock']['bajo'] }} bajos</span>
                    <span style="color:#0891b2;font-weight:700;">{{ $resumen['stock']['alerta'] }} alertas</span>
                </div>
            </div>

            {{-- Entradas --}}
            @php
                $pctOk = $resumen['entradas']['total'] > 0
                    ? round($resumen['entradas']['ok'] / $resumen['entradas']['total'] * 100, 1)
                    : 100;
                $entBg = $pctOk >= 90 ? '#f0fdf4' : ($pctOk >= 70 ? '#fffbeb' : '#fff5f5');
                $entC  = $pctOk >= 90 ? '#15803d' : ($pctOk >= 70 ? '#92400e' : '#b91c1c');
            @endphp
            <div class="summary-stat" style="border-color:{{ $entC }};background:{{ $entBg }};">
                <div class="summary-stat-label">
                    <i class="fas fa-boxes-stacked me-1" style="color:{{ $entC }};"></i>
                    Cumplimiento de Compras
                </div>
                <div class="summary-stat-value" style="color:{{ $entC }};">{{ number_format($pctOk, 1) }}%</div>
                <div class="summary-stat-sub" style="color:{{ $entC }};">
                    órdenes completamente recibidas
                </div>
                <div style="margin-top:8px;display:flex;gap:10px;font-size:11px;">
                    <span style="color:#7c3aed;font-weight:700;">
                        {{ $resumen['entradas']['sin_entrada'] }} sin entrada
                    </span>
                    <span style="color:#dc2626;font-weight:700;">
                        {{ $resumen['entradas']['critico'] }} críticas
                    </span>
                </div>
            </div>

            {{-- Salidas No Comerciales --}}
            @php
                $currency = config('app_client.locale.currency_symbol');
                $dec = config('app_client.locale.decimal_sep');
                $thou = config('app_client.locale.thousands_sep');
            @endphp
            <div class="summary-stat" style="border-color:#ea580c;background:#fff7ed;">
                <div class="summary-stat-label">
                    <i class="fas fa-arrow-right-from-bracket me-1" style="color:#ea580c;"></i>
                    Salidas No Comerciales
                </div>
                <div class="summary-stat-value" style="color:#ea580c;font-size:22px;">
                    {{ $currency }}
                    {{ number_format($resumen['salidas']['costo_total'], 0, $dec, $thou) }}
                </div>
                <div class="summary-stat-sub" style="color:#ea580c;">costo estimado de pérdidas</div>
                <div style="margin-top:8px;display:flex;gap:10px;font-size:11px;color:#ea580c;">
                    <span>{{ $resumen['salidas']['total'] }} movimientos</span>
                    <span>·</span>
                    <span>{{ $resumen['salidas']['articulos'] }} artículos</span>
                    <span>·</span>
                    <span>{{ $resumen['salidas']['tipos'] }} tipos</span>
                </div>
            </div>

        </div>
    </div>
</div>

{{-- ════════════════════════════════════════════════════════════════ --}}
{{-- BLOQUE 2 — Preview Stock Crítico (top 10)                       --}}
{{-- ════════════════════════════════════════════════════════════════ --}}
<div class="report-section animate-in">
    <div class="report-section-header">
        <div>
            <h3 class="section-title mb-0">
                <i class="fas fa-triangle-exclamation me-2" style="color:#dc2626;font-size:13px;"></i>
                Stock Crítico — Top 10
            </h3>
            <p class="section-subtitle mb-0">artículos con mayor déficit de stock</p>
        </div>
        <a href="{{ route('inventario.stock-critico') }}"
           style="font-size:12px;color:var(--brand-primary);text-decoration:none;font-weight:600;">
            Ver todos ({{ $stock->count() }}) <i class="fas fa-arrow-right ms-1" style="font-size:10px;"></i>
        </a>
    </div>
    <div style="overflow-x:auto;">
        <table class="preview-table">
            <thead>
                <tr>
                    <th>Código</th>
                    <th>Descripción</th>
                    <th style="text-align:right;">Stock Actual</th>
                    <th style="text-align:right;">Stock Mínimo</th>
                    <th style="text-align:right;">Déficit</th>
                    <th style="text-align:center;">Nivel</th>
                </tr>
            </thead>
            <tbody>
                @foreach($stock->take(10) as $item)
                <tr>
                    <td style="font-weight:700;font-size:11.5px;color:{{ $item['nivel_color'] }};">
                        {{ $item['codigo'] }}
                    </td>
                    <td style="max-width:260px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"
                        title="{{ $item['descripcion'] }}">
                        {{ $item['descripcion'] }}
                    </td>
                    <td style="text-align:right;font-weight:700;color:{{ $item['nivel_color'] }};">
                        {{ number_format($item['stock_actual'], 0, '.', $thou) }}
                    </td>
                    <td style="text-align:right;color:var(--text-secondary);">
                        {{ number_format($item['stock_minimo'], 0, '.', $thou) }}
                    </td>
                    <td style="text-align:right;color:#dc2626;font-weight:700;">
                        {{ number_format($item['deficit'], 0, '.', $thou) }}
                    </td>
                    <td style="text-align:center;">
                        <span style="padding:2px 8px;border-radius:12px;font-size:10.5px;font-weight:700;
                                     background:{{ $item['nivel_bg'] }};color:{{ $item['nivel_color'] }};">
                            {{ $item['nivel_label'] }}
                        </span>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

{{-- ════════════════════════════════════════════════════════════════ --}}
{{-- BLOQUE 3 — Preview Salidas + Entradas (lado a lado)             --}}
{{-- ════════════════════════════════════════════════════════════════ --}}
<div class="row g-4">

    <div class="col-12 col-xl-6 animate-in">
        <div class="report-section" style="margin-bottom:0;">
            <div class="report-section-header">
                <div>
                    <h3 class="section-title mb-0">
                        <i class="fas fa-arrow-right-from-bracket me-2" style="color:#ea580c;font-size:13px;"></i>
                        Salidas No Comerciales — Top 8
                    </h3>
                    <p class="section-subtitle mb-0">por costo estimado</p>
                </div>
                <a href="{{ route('inventario.salidas', ['from'=>$from,'to'=>$to]) }}"
                   style="font-size:12px;color:var(--brand-primary);text-decoration:none;font-weight:600;">
                    Ver todas <i class="fas fa-arrow-right ms-1" style="font-size:10px;"></i>
                </a>
            </div>
            <div style="overflow-x:auto;">
                <table class="preview-table">
                    <thead>
                        <tr>
                            <th>Artículo</th>
                            <th>Tipo</th>
                            <th style="text-align:right;">Cantidad</th>
                            <th style="text-align:right;">Costo Est.</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($salidas->sortByDesc('costo_estimado')->take(8) as $s)
                        <tr>
                            <td style="max-width:180px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"
                                title="{{ $s['articulo_descripcion'] }}">
                                <span style="font-size:11px;color:var(--text-muted);">{{ $s['articulo_codigo'] }}</span><br>
                                {{ $s['articulo_descripcion'] }}
                            </td>
                            <td>
                                <span style="font-size:11px;padding:2px 7px;border-radius:8px;
                                             background:{{ $s['tipo_color'] }}1a;color:{{ $s['tipo_color'] }};
                                             font-weight:600;white-space:nowrap;">
                                    <i class="fas {{ $s['tipo_icon'] }}" style="font-size:9px;"></i>
                                    {{ $s['tipo_label'] }}
                                </span>
                            </td>
                            <td style="text-align:right;color:#dc2626;font-weight:600;">
                                {{ number_format(abs($s['cantidad']), 0, '.', $thou) }}
                            </td>
                            <td style="text-align:right;font-weight:700;color:#ea580c;">
                                {{ $currency }} {{ number_format($s['costo_estimado'], 2, $dec, $thou) }}
                            </td>
                        </tr>
                        @endforeach
                        @if($salidas->isEmpty())
                        <tr>
                            <td colspan="4" style="text-align:center;padding:24px;color:var(--text-muted);">
                                Sin salidas en el período.
                            </td>
                        </tr>
                        @endif
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="col-12 col-xl-6 animate-in">
        <div class="report-section" style="margin-bottom:0;">
            <div class="report-section-header">
                <div>
                    <h3 class="section-title mb-0">
                        <i class="fas fa-boxes-stacked me-2" style="color:#7c3aed;font-size:13px;"></i>
                        Entradas con Discrepancia — Top 8
                    </h3>
                    <p class="section-subtitle mb-0">órdenes críticas o sin recibir</p>
                </div>
                <a href="{{ route('inventario.entradas', ['from'=>$from,'to'=>$to]) }}"
                   style="font-size:12px;color:var(--brand-primary);text-decoration:none;font-weight:600;">
                    Ver todas <i class="fas fa-arrow-right ms-1" style="font-size:10px;"></i>
                </a>
            </div>
            <div style="overflow-x:auto;">
                <table class="preview-table">
                    <thead>
                        <tr>
                            <th>Orden</th>
                            <th>Artículo</th>
                            <th style="text-align:right;">Diferencia</th>
                            <th style="text-align:center;">Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($entradas->whereIn('alerta',['critico','sin_entrada','parcial'])->take(8) as $e)
                        <tr>
                            <td style="font-weight:700;font-size:11.5px;">{{ $e['numero_orden'] }}</td>
                            <td style="max-width:160px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"
                                title="{{ $e['articulo_descripcion'] }}">
                                {{ $e['articulo_descripcion'] }}
                            </td>
                            <td style="text-align:right;font-weight:700;color:#dc2626;">
                                -{{ number_format($e['diferencia'], 0, '.', $thou) }}
                            </td>
                            <td style="text-align:center;">
                                <span style="padding:2px 8px;border-radius:12px;font-size:10.5px;font-weight:700;
                                             background:{{ $e['alerta_bg'] }};color:{{ $e['alerta_color'] }};">
                                    {{ $e['alerta_label'] }}
                                </span>
                            </td>
                        </tr>
                        @endforeach
                        @if($entradas->whereIn('alerta',['critico','sin_entrada','parcial'])->isEmpty())
                        <tr>
                            <td colspan="4" style="text-align:center;padding:24px;color:var(--text-muted);">
                                <i class="fas fa-check-circle me-1" style="color:#059669;"></i>
                                Sin discrepancias críticas en el período.
                            </td>
                        </tr>
                        @endif
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>

{{-- Nota informativa sobre el Excel --}}
@can('inventario.reporte.consolidado.exportar')
<div class="panel-card mt-4 animate-in" style="border-left:4px solid #059669;">
    <div class="panel-card-body py-3">
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
            <div>
                <p class="mb-1" style="font-size:13px;font-weight:700;color:var(--text-primary);">
                    <i class="fas fa-file-excel me-2" style="color:#059669;"></i>
                    El Excel incluye 3 hojas completas
                </p>
                <p class="mb-0" style="font-size:12px;color:var(--text-secondary);">
                    <strong>Hoja 1:</strong> Stock Crítico con semáforo de colores ·
                    <strong>Hoja 2:</strong> Salidas No Comerciales con tipo de movimiento ·
                    <strong>Hoja 3:</strong> Entradas vs Compras con % de cumplimiento y fórmulas.
                    Totales calculados con fórmulas Excel reales.
                </p>
            </div>
            <a href="{{ route('inventario.reporte.exportar', ['from'=>$from,'to'=>$to]) }}"
               class="btn btn-success d-flex align-items-center gap-2"
               style="border-radius:10px;padding:10px 20px;font-size:13px;font-weight:600;white-space:nowrap;">
                <i class="fas fa-download"></i>
                Descargar Reporte Excel
            </a>
        </div>
    </div>
</div>
@endcan

@endsection
