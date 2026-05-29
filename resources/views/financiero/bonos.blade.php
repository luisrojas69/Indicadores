{{--
    financiero/bonos.blade.php

    Variables del controlador:
      $resumenBono, $margenes, $topPorMargen, $peorPorMargen,
      $chartTopLabels, $chartTopPct, $chartPeorLabels, $chartPeorPct,
      $costField, $excluirIva, $from, $to
--}}

@extends('layouts.app')

@section('title', 'Reporte de Bono Mensual')

@section('breadcrumb')
    <a href="{{ route('dashboard.index') }}"
       style="color: var(--text-muted); text-decoration: none;">Inicio</a>
    <span style="color: #cbd5e1; margin: 0 4px;">/</span>
    <a href="{{ route('financiero.margenes', ['from' => $from, 'to' => $to]) }}"
       style="color: var(--text-muted); text-decoration: none;">Financiero</a>
    <span style="color: #cbd5e1; margin: 0 4px;">/</span>
    <span class="current">Bono Mensual</span>
@endsection

@push('styles')
<style>
    /* ── Hero card del bono ─────────────────────────── */
    .bono-hero {
        background: linear-gradient(135deg, #0f172a 0%, #1e3a5f 100%);
        border-radius: 16px;
        padding: 32px 36px;
        position: relative;
        overflow: hidden;
        color: #fff;
    }

    .bono-hero::before {
        content: '';
        position: absolute;
        top: -60px; right: -60px;
        width: 220px; height: 220px;
        background: radial-gradient(circle, rgba(26,86,219,.4) 0%, transparent 70%);
        border-radius: 50%;
    }

    .bono-hero::after {
        content: '';
        position: absolute;
        bottom: -40px; left: 30%;
        width: 300px; height: 150px;
        background: radial-gradient(ellipse, rgba(5,150,105,.2) 0%, transparent 70%);
    }

    .bono-label {
        font-size: 11px; font-weight: 700;
        letter-spacing: 1.5px; text-transform: uppercase;
        color: rgba(255,255,255,.5);
        margin-bottom: 6px;
    }

    .bono-value-xl {
        font-family: var(--font-display);
        font-size: 48px; font-weight: 800;
        line-height: 1; letter-spacing: -2px;
        position: relative; z-index: 1;
    }

    .bono-value-xl.positivo { color: #4ade80; }
    .bono-value-xl.negativo { color: #f87171; }

    .bono-margen-badge {
        display: inline-flex; align-items: center; gap: 6px;
        padding: 5px 14px;
        border-radius: 24px;
        font-family: var(--font-display);
        font-size: 15px; font-weight: 700;
        margin-top: 10px;
    }

    .bono-margen-badge.verde    { background: rgba(74,222,128,.15); color: #4ade80; border: 1px solid rgba(74,222,128,.3); }
    .bono-margen-badge.amarillo { background: rgba(251,191,36,.15); color: #fbbf24; border: 1px solid rgba(251,191,36,.3); }
    .bono-margen-badge.rojo     { background: rgba(248,113,113,.15); color: #f87171; border: 1px solid rgba(248,113,113,.3); }

    /* ── Breakdown cards ─────────────────────────────── */
    .breakdown-card {
        background: rgba(255,255,255,.06);
        border: 1px solid rgba(255,255,255,.1);
        border-radius: 12px;
        padding: 16px 20px;
        backdrop-filter: blur(4px);
    }

    .breakdown-label {
        font-size: 11px; color: rgba(255,255,255,.45);
        text-transform: uppercase; letter-spacing: .8px; font-weight: 600;
        margin-bottom: 4px;
    }

    .breakdown-value {
        font-family: var(--font-display);
        font-size: 20px; font-weight: 700; color: #fff;
        letter-spacing: -.3px;
    }

    /* ── Ecuación visual ─────────────────────────────── */
    .ecuacion {
        display: flex; align-items: center; gap: 8px;
        flex-wrap: wrap;
        font-size: 13px; color: rgba(255,255,255,.6);
        margin-top: 20px;
        position: relative; z-index: 1;
    }
    .ecuacion .val { font-weight: 700; color: #fff; }
    .ecuacion .op  { font-size: 18px; opacity: .4; }
    .ecuacion .result { font-family: var(--font-display); font-size: 16px; font-weight: 800; }
    .ecuacion .result.pos { color: #4ade80; }
    .ecuacion .result.neg { color: #f87171; }

    /* ── Semáforo en bono --*/
    .sem-chip {
        display: inline-flex; align-items: center; gap: 4px;
        padding: 2px 8px; border-radius: 12px;
        font-size: 11px; font-weight: 700;
    }
    .sem-chip.verde    { background: #dcfce7; color: #15803d; }
    .sem-chip.amarillo { background: #fef3c7; color: #92400e; }
    .sem-chip.rojo     { background: #fee2e2; color: #b91c1c; }
</style>
@endpush

@section('content')

{{-- ── Page Header ─────────────────────────────────────────────────── --}}
<div class="d-flex align-items-start justify-content-between flex-wrap gap-3 mb-4">
    <div>
        <h1 class="font-display mb-1" style="font-size: 22px; font-weight: 800;">
            Reporte de Bono Mensual
        </h1>
        <p class="mb-0" style="font-size: 13px; color: var(--text-muted);">
            Período:
            <strong style="color: var(--text-secondary);">
                {{ \Carbon\Carbon::parse($from)->format(config('app_client.locale.date_format')) }}
                — {{ \Carbon\Carbon::parse($to)->format(config('app_client.locale.date_format')) }}
            </strong>
            &nbsp;·&nbsp; Campo costo:
            <strong style="color: var(--brand-primary);">{{ $costField }}</strong>
            @if($excluirIva)
                &nbsp;·&nbsp;
                <span class="sem-chip rojo" style="font-size: 10px;">IVA excluido</span>
            @endif
        </p>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('financiero.margenes', ['from' => $from, 'to' => $to]) }}"
           class="btn btn-sm btn-outline-secondary" style="border-radius: 9px; font-size: 12.5px;">
            <i class="fas fa-arrow-left me-1"></i> Ver Márgenes
        </a>
        @can('financiero.margenes.exportar')
        <a href="{{ route('financiero.margenes.exportar', ['from' => $from, 'to' => $to, 'cost_field' => $costField, 'excluir_iva' => (int)$excluirIva]) }}"
           class="btn btn-sm btn-success" style="border-radius: 9px; font-size: 12.5px;">
            <i class="fas fa-file-excel me-1"></i> Exportar Excel
        </a>
        @endcan
    </div>
</div>

{{-- ════════════════════════════════════════════════════════════════ --}}
{{-- BLOQUE 1 — Hero Card: Ganancia Neta                             --}}
{{-- ════════════════════════════════════════════════════════════════ --}}
@php
    $currency = config('app_client.locale.currency_symbol');
    $dec      = config('app_client.locale.decimal_sep');
    $thou     = config('app_client.locale.thousands_sep');
    $fmt      = fn(float $v) => $currency . ' ' . number_format(abs($v), 2, $dec, $thou);
    $fmtSig   = fn(float $v) => ($v < 0 ? '-' : '') . $currency . ' ' . number_format(abs($v), 2, $dec, $thou);

    $gn       = $resumenBono['ganancia_neta'];
    $gnClass  = $gn >= 0 ? 'positivo' : 'negativo';
    $semGlobal= $resumenBono['semaforo_global'];
@endphp

<div class="bono-hero mb-4 animate-in">
    <div class="row g-4 align-items-center">

        {{-- Ganancia neta —  principal --}}
        <div class="col-12 col-lg-5">
            <div class="bono-label">Ganancia Neta del Período</div>
            <div class="bono-value-xl {{ $gnClass }}">
                {{ $fmtSig($gn) }}
            </div>

            <div class="bono-margen-badge {{ $semGlobal }}">
                <i class="fas fa-{{ $semGlobal === 'verde' ? 'circle-check' : ($semGlobal === 'amarillo' ? 'circle-half-stroke' : 'triangle-exclamation') }}"></i>
                {{ number_format($resumenBono['margen_neto_pct'], 2, $dec, '') }}% Margen Neto
            </div>

            {{-- Ecuación visual --}}
            <div class="ecuacion">
                <span>
                    <div style="font-size: 10px; opacity:.5; margin-bottom:2px;">FACTURADO</div>
                    <span class="val">{{ $fmt($resumenBono['total_base']) }}</span>
                </span>
                <span class="op">−</span>
                <span>
                    <div style="font-size: 10px; opacity:.5; margin-bottom:2px;">COSTO</div>
                    <span class="val">{{ $fmt($resumenBono['costo_total']) }}</span>
                </span>
                <span class="op">=</span>
                <span class="result {{ $gn >= 0 ? 'pos' : 'neg' }}">
                    {{ $fmtSig($gn) }}
                </span>
            </div>

            @if($resumenBono['iva_excluido'])
            <div style="margin-top: 10px; font-size: 11px; color: rgba(255,255,255,.4);">
                <i class="fas fa-info-circle me-1"></i>
                IVA separado: {{ $fmt($resumenBono['iva_monto']) }}
                ({{ $resumenBono['iva_rate'] }}%)
            </div>
            @endif
        </div>

        {{-- Breakdown --}}
        <div class="col-12 col-lg-7">
            <div class="row g-3">
                <div class="col-6">
                    <div class="breakdown-card">
                        <div class="breakdown-label">Total Facturado</div>
                        <div class="breakdown-value">{{ $fmt($resumenBono['total_facturado']) }}</div>
                        @if($resumenBono['iva_excluido'])
                            <div style="font-size: 10px; color: rgba(255,255,255,.3); margin-top: 3px;">
                                Base s/IVA: {{ $fmt($resumenBono['total_base']) }}
                            </div>
                        @endif
                    </div>
                </div>
                <div class="col-6">
                    <div class="breakdown-card" style="border-color: rgba(248,113,113,.2);">
                        <div class="breakdown-label">Costo Total de Ventas</div>
                        <div class="breakdown-value" style="color: #fca5a5;">
                            {{ $fmt($resumenBono['costo_total']) }}
                        </div>
                        <div style="font-size: 10px; color: rgba(255,255,255,.3); margin-top: 3px;">
                            Campo: {{ $costField }}
                        </div>
                    </div>
                </div>

                {{-- Semáforo de artículos --}}
                @php $sem = $resumenBono['semaforo_conteo']; $total = $resumenBono['total_articulos'] ?: 1; @endphp
                <div class="col-12">
                    <div class="breakdown-card">
                        <div class="breakdown-label mb-2">Distribución de Artículos ({{ $resumenBono['total_articulos'] }} en total)</div>
                        <div class="d-flex gap-3 flex-wrap">
                            @foreach([['verde','#4ade80','Alto'], ['amarillo','#fbbf24','Medio'], ['rojo','#f87171','Bajo'], ['negativos','#c084fc','Neg.']] as [$k,$c,$l])
                            <div style="text-align: center;">
                                <div style="font-family: var(--font-display); font-size: 20px; font-weight: 800; color: {{$c}};">
                                    {{ $sem[$k] }}
                                </div>
                                <div style="font-size: 10px; color: rgba(255,255,255,.4); margin-top:2px;">
                                    {{ $l }}<br>
                                    <span style="color:{{$c}}; opacity:.7;">
                                        {{ number_format($sem[$k]/$total*100,0) }}%
                                    </span>
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                {{-- Mejor / Peor artículo --}}
                @if($resumenBono['mejor_articulo'] || $resumenBono['peor_articulo'])
                <div class="col-6">
                    @if($resumenBono['mejor_articulo'])
                    <div class="breakdown-card" style="border-color: rgba(74,222,128,.2);">
                        <div class="breakdown-label">⭐ Mayor Margen</div>
                        <div style="font-size: 12px; font-weight: 600; color: #fff; margin-top: 2px;"
                             title="{{ $resumenBono['mejor_articulo']['descripcion'] }}">
                            {{ mb_strimwidth($resumenBono['mejor_articulo']['descripcion'], 0, 30, '…') }}
                        </div>
                        <div style="font-size: 13px; color: #4ade80; font-weight: 800; margin-top: 4px;">
                            {{ number_format($resumenBono['mejor_articulo']['margen_pct'], 1, $dec, '') }}%
                        </div>
                    </div>
                    @endif
                </div>
                <div class="col-6">
                    @if($resumenBono['peor_articulo'])
                    <div class="breakdown-card" style="border-color: rgba(248,113,113,.2);">
                        <div class="breakdown-label">⚠️ Menor Margen</div>
                        <div style="font-size: 12px; font-weight: 600; color: #fff; margin-top: 2px;"
                             title="{{ $resumenBono['peor_articulo']['descripcion'] }}">
                            {{ mb_strimwidth($resumenBono['peor_articulo']['descripcion'], 0, 30, '…') }}
                        </div>
                        <div style="font-size: 13px; color: #f87171; font-weight: 800; margin-top: 4px;">
                            {{ number_format($resumenBono['peor_articulo']['margen_pct'], 1, $dec, '') }}%
                        </div>
                    </div>
                    @endif
                </div>
                @endif
            </div>
        </div>

    </div>
</div>

{{-- ════════════════════════════════════════════════════════════════ --}}
{{-- BLOQUE 2 — Gráficos: Top 10 mejor y peor margen                 --}}
{{-- ════════════════════════════════════════════════════════════════ --}}
<div class="row g-4 mb-4">
    <div class="col-12 col-xl-6 animate-in">
        <div class="panel-card h-100">
            <div class="panel-card-header">
                <div>
                    <h3 class="section-title mb-0">
                        <i class="fas fa-medal me-2" style="color: #d97706; font-size: 13px;"></i>
                        Top 10 — Mayor Margen %
                    </h3>
                    <p class="section-subtitle mb-0">artículos con mejor rentabilidad en el período</p>
                </div>
            </div>
            <div class="panel-card-body">
                <div style="height: 280px; position: relative;">
                    <canvas id="chartTopMargen"></canvas>
                </div>
            </div>
        </div>
    </div>

    <div class="col-12 col-xl-6 animate-in">
        <div class="panel-card h-100">
            <div class="panel-card-header">
                <div>
                    <h3 class="section-title mb-0">
                        <i class="fas fa-triangle-exclamation me-2" style="color: #dc2626; font-size: 13px;"></i>
                        Top 10 — Menor Margen %
                    </h3>
                    <p class="section-subtitle mb-0">artículos que requieren revisión urgente de precio o costo</p>
                </div>
            </div>
            <div class="panel-card-body">
                <div style="height: 280px; position: relative;">
                    <canvas id="chartPeorMargen"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ════════════════════════════════════════════════════════════════ --}}
{{-- BLOQUE 3 — Nota metodológica                                    --}}
{{-- ════════════════════════════════════════════════════════════════ --}}
<div class="panel-card animate-in" style="border-left: 4px solid var(--brand-primary);">
    <div class="panel-card-body py-3">
        <p class="mb-2" style="font-size: 12px; font-weight: 700; text-transform: uppercase;
                               letter-spacing: .5px; color: var(--text-muted);">
            <i class="fas fa-circle-info me-1"></i> Metodología de Cálculo
        </p>
        <div class="row g-2" style="font-size: 12.5px; color: var(--text-secondary);">
            <div class="col-12 col-md-4">
                <strong>Base de cálculo:</strong> Margen del mes completo ({{ \Carbon\Carbon::parse($from)->format('d/m/Y') }} — {{ \Carbon\Carbon::parse($to)->format('d/m/Y') }}).
                No se segmenta por vendedor individual.
            </div>
            <div class="col-12 col-md-4">
                <strong>Campo de costo:</strong> {{ $costField }}.
                @if(str_contains($costField, 'PRO')) Costo promedio ponderado histórico.
                @else Último costo registrado en el ERP.
                @endif
                @if(str_contains($costField, '_OM')) Valuado en otra moneda (USD).
                @endif
            </div>
            <div class="col-12 col-md-4">
                <strong>IVA:</strong>
                @if($excluirIva)
                    Excluido — la base de cálculo es el precio sin IVA.
                    IVA separado: {{ $fmt($resumenBono['iva_monto']) }} ({{ $resumenBono['iva_rate'] }}%).
                @else
                    Incluido en el precio de venta. El margen se calcula sobre el precio bruto.
                    Para excluirlo, usar el toggle en la vista de Márgenes.
                @endif
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
(function () {
    'use strict';

    const chartDefaults = {
        responsive:          true,
        maintainAspectRatio: false,
        indexAxis:           'y',
        plugins: {
            legend:  { display: false },
            tooltip: {
                backgroundColor: '#1e293b',
                padding: 10,
                cornerRadius: 8,
                callbacks: {
                    label: ctx => ` ${ctx.parsed.x.toFixed(1)}% margen`
                }
            }
        },
        scales: {
            x: {
                grid: { color: 'rgba(0,0,0,.04)', drawBorder: false },
                ticks: {
                    font: { size: 10 },
                    callback: v => v.toFixed(0) + '%'
                }
            },
            y: {
                grid: { display: false },
                ticks: { font: { size: 10 } }
            }
        }
    };

    // ── Top márgenes (verde) ──────────────────────────────────────────────
    const ctxTop = document.getElementById('chartTopMargen');
    if (ctxTop) {
        const pcts = {!! $chartTopPct !!};
        new Chart(ctxTop, {
            type: 'bar',
            data: {
                labels:   {!! $chartTopLabels !!},
                datasets: [{
                    data:            pcts,
                    backgroundColor: pcts.map(v =>
                        v >= {{ config('app_client.business.margin_alert_yellow', 20) }}
                            ? 'rgba(5,150,105,.8)'
                            : v >= {{ config('app_client.business.margin_alert_red', 10) }}
                                ? 'rgba(217,119,6,.8)'
                                : 'rgba(220,38,38,.8)'
                    ),
                    borderRadius:  5,
                    barPercentage: 0.75,
                }]
            },
            options: chartDefaults
        });
    }

    // ── Peor márgenes (rojo) ──────────────────────────────────────────────
    const ctxPeor = document.getElementById('chartPeorMargen');
    if (ctxPeor) {
        const pcts = {!! $chartPeorPct !!};
        new Chart(ctxPeor, {
            type: 'bar',
            data: {
                labels:   {!! $chartPeorLabels !!},
                datasets: [{
                    data:            pcts,
                    backgroundColor: pcts.map(v =>
                        v < 0
                            ? 'rgba(124,58,237,.8)'
                            : v >= {{ config('app_client.business.margin_alert_yellow', 20) }}
                                ? 'rgba(5,150,105,.8)'
                                : v >= {{ config('app_client.business.margin_alert_red', 10) }}
                                    ? 'rgba(217,119,6,.8)'
                                    : 'rgba(220,38,38,.8)'
                    ),
                    borderRadius:  5,
                    barPercentage: 0.75,
                }]
            },
            options: chartDefaults
        });
    }

})();
</script>
@endpush
