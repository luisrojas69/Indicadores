{{--
    dashboard/index.blade.php
    Vista principal del Dashboard Gerencial.
    Recibe del DashboardController:
      $kpis, $cxc, $topProductos, $rankingVendedores,
      $chartLabels, $chartUnidades, $chartMontos,
      $varPct, $pctCobranza, $from, $to
--}}

@extends('layouts.app')

@section('title', 'Dashboard Gerencial')

@section('breadcrumb')
    <span class="current">Dashboard Gerencial</span>
@endsection

@section('content')

{{-- ── Header de la página ──────────────────────────────────────── --}}
<div class="d-flex align-items-start justify-content-between mb-4">
    <div>
        <h1 class="font-display mb-1" style="font-size: 22px; font-weight: 800; color: var(--text-primary);">
            Dashboard Gerencial
        </h1>
        <p class="mb-0" style="font-size: 13px; color: var(--text-muted);">
            Período:
            <strong style="color: var(--text-secondary);">
                {{ \Carbon\Carbon::parse($from)->format(config('app_client.locale.date_format')) }}
                —
                {{ \Carbon\Carbon::parse($to)->format(config('app_client.locale.date_format')) }}
            </strong>
            &nbsp;·&nbsp;
            Datos actualizados hace
            <span id="lastUpdate" style="color: var(--brand-primary);">
                {{ now()->format('H:i') }}
            </span>
        </p>
    </div>

    @can('gerencia.dashboard.exportar')
        <button class="btn btn-sm btn-outline-secondary d-flex align-items-center gap-2"
                style="border-radius: 9px; font-size: 12.5px;">
            <i class="fas fa-download"></i>
            <span class="d-none d-md-inline">Exportar</span>
        </button>
    @endcan
</div>

{{-- ════════════════════════════════════════════════════════════════ --}}
{{-- BLOQUE 1 — KPI Cards principales                               --}}
{{-- ════════════════════════════════════════════════════════════════ --}}
<div class="row g-3 mb-4">

    {{-- Monto Facturado --}}
    <div class="col-12 col-sm-6 col-xl-3 animate-in">
        @include('dashboard._partials.kpi_card', [
            'label'      => 'Monto Facturado',
            'value'      => number_format($kpis['monto_facturado'], 2, config('app_client.locale.decimal_sep'), config('app_client.locale.thousands_sep')),
            'prefix'     => config('app_client.locale.currency_symbol'),
            'icon'       => 'fas fa-file-invoice-dollar',
            'accentClass'=> '',   // primary (default)
            'delta'      => $varPct,
            'deltaLabel' => 'vs mes anterior',
            'period'     => \Carbon\Carbon::parse($from)->translatedFormat('F Y'),
        ])
    </div>

    {{-- Cobranzas del Mes --}}
    <div class="col-12 col-sm-6 col-xl-3 animate-in">
        @include('dashboard._partials.kpi_card', [
            'label'      => 'Cobranzas del Mes',
            'value'      => number_format($kpis['cobranzas_mes'], 2, config('app_client.locale.decimal_sep'), config('app_client.locale.thousands_sep')),
            'prefix'     => config('app_client.locale.currency_symbol'),
            'icon'       => 'fas fa-coins',
            'accentClass'=> 'accent-success',
            'delta'      => $pctCobranza,
            'deltaLabel' => 'del facturado cobrado',
            'deltaType'  => 'pct_cobrado',
            'period'     => \Carbon\Carbon::parse($from)->translatedFormat('F Y'),
        ])
    </div>

    {{-- Clientes Activos --}}
    <div class="col-12 col-sm-6 col-xl-3 animate-in">
        @include('dashboard._partials.kpi_card', [
            'label'      => 'Clientes Activos',
            'value'      => number_format($kpis['clientes_activos'], 0, '.', config('app_client.locale.thousands_sep')),
            'prefix'     => '',
            'icon'       => 'fas fa-users',
            'accentClass'=> 'accent-info',
            'delta'      => null,
            'deltaLabel' => 'con facturación en el período',
            'period'     => 'en el período',
        ])
    </div>

    {{-- Clientes Nuevos --}}
    <div class="col-12 col-sm-6 col-xl-3 animate-in">
        @include('dashboard._partials.kpi_card', [
            'label'      => 'Clientes Nuevos',
            'value'      => number_format($kpis['clientes_nuevos'], 0, '.', config('app_client.locale.thousands_sep')),
            'prefix'     => '',
            'icon'       => 'fas fa-user-plus',
            'accentClass'=> 'accent-warning',
            'delta'      => null,
            'deltaLabel' => 'primera compra en el período',
            'period'     => 'primera compra',
        ])
    </div>

</div>

{{-- ════════════════════════════════════════════════════════════════ --}}
{{-- BLOQUE 2 — Cuentas por Cobrar (Aging)                          --}}
{{-- ════════════════════════════════════════════════════════════════ --}}
@can('gerencia.cxc.ver')
<div class="mb-4">
    <div class="section-header">
        <div>
            <h2 class="section-title">
                <i class="fas fa-clock me-2" style="color: var(--brand-warning); font-size: 14px;"></i>
                Cuentas por Cobrar
            </h2>
            <p class="section-subtitle">Antigüedad de cartera al {{ \Carbon\Carbon::parse($to)->format(config('app_client.locale.date_format')) }}</p>
        </div>
    </div>

    <div class="row g-3">
        @include('dashboard._partials.cxc_cards', ['cxc' => $cxc])
    </div>
</div>
@endcan

{{-- ════════════════════════════════════════════════════════════════ --}}
{{-- BLOQUE 3 — Gráfico Top Productos + Tabla Vendedores            --}}
{{-- ════════════════════════════════════════════════════════════════ --}}
<div class="row g-4 mb-4">

    {{-- Gráfico Top Productos --}}
    @can('gerencia.productos.ranking.ver')
    <div class="col-12 col-xl-7 animate-in">
        @include('dashboard._partials.chart_productos', [
            'topProductos'  => $topProductos,
            'chartLabels'   => $chartLabels,
            'chartUnidades' => $chartUnidades,
            'chartMontos'   => $chartMontos,
        ])
    </div>
    @endcan

    {{-- Tabla Ranking Vendedores --}}
    @can('gerencia.vendedores.ranking.ver')
    <div class="col-12 col-xl-5 animate-in">
        @include('dashboard._partials.tabla_vendedores', [
            'rankingVendedores' => $rankingVendedores,
        ])
    </div>
    @endcan

</div>

@endsection

@push('scripts')
<script>
// Chart.js ya está inicializado en layouts/app.blade.php
// Los gráficos concretos están en dashboard/_partials/chart_productos.blade.php
</script>
@endpush
