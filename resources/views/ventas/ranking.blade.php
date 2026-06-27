{{--
    ventas/ranking.blade.php
    Ranking / Leaderboard de Vendedores
    Compatible con layouts/app.blade.php del proyecto BI Bridge.

    Fixes aplicados vs versión anterior:
    1. Podio usa array PHP plano ($podio[0], $podio[1], $podio[2]) — sin quirks de Collection
    2. Select de competencia preserva from/to como hidden inputs
    3. Flatpickr conectado al mismo form — no reinicia el período
    4. Layout 2 columnas estable — sin floats ni flex roto
    5. Cada fila de la arena muestra facturado + cobrado + barra de progreso
--}}
@extends('layouts.app')

@section('title-page', 'Leaderboard de Ventas')

@section('breadcrumb')
    <a href="{{ route('ventas.index') }}" style="color:var(--text-muted);text-decoration:none;">Ventas</a>
    <span style="color:#cbd5e1;margin:0 4px;">/</span>
    <span class="current">Ranking de Vendedores</span>
@endsection

@section('hide_daterange', true)

@push('styles')
<style>
/* ── Podio ───────────────────────────────────────────────────────── */
.podium-wrap {
    display: grid;
    grid-template-columns: 1fr 1fr 1fr;
    align-items: flex-end;
    gap: 12px;
    padding: 24px 0 0;
}

/* Orden visual: 2 — 1 — 3 */
.podium-slot-1 { order: 2; }
.podium-slot-2 { order: 1; }
.podium-slot-3 { order: 3; }

.podium-card {
    background: var(--card-bg, #fff);
    border-radius: 14px 14px 0 0;
    padding: 20px 14px 24px;
    text-align: center;
    border: 1px solid rgba(0,0,0,.05);
    position: relative;
    transition: transform .2s ease;
}
.podium-card:hover { transform: translateY(-4px); }

/* Alturas escalonadas — el primero es más alto */
.podium-slot-1 .podium-card { border-top: 4px solid #fbbf24; padding-bottom: 40px; }
.podium-slot-2 .podium-card { border-top: 4px solid #94a3b8; padding-bottom: 24px; }
.podium-slot-3 .podium-card { border-top: 4px solid #b45309; padding-bottom: 8px;  }

/* Escalón bajo el podio */
.podium-step {
    background: linear-gradient(to bottom, #e2e8f0, #f1f5f9);
    border-radius: 0 0 8px 8px;
    text-align: center;
    font-family: var(--font-display, 'Sora', sans-serif);
    font-weight: 900;
    color: #94a3b8;
    padding: 6px 0;
    font-size: 13px;
    letter-spacing: .5px;
}
.podium-slot-1 .podium-step { padding: 10px 0; color: #fbbf24; font-size: 15px; }

/* Ícono de medalla flotante */
.podium-medal {
    position: absolute;
    top: -16px; left: 50%;
    transform: translateX(-50%);
    font-size: 28px;
    filter: drop-shadow(0 3px 4px rgba(0,0,0,.15));
}
.podium-slot-1 .podium-medal { font-size: 36px; top: -20px; }

/* Avatar */
.p-avatar {
    width: 56px; height: 56px; border-radius: 50%;
    background: var(--brand-sidebar-bg, #0f172a);
    color: #fff; margin: 12px auto 8px;
    display: flex; align-items: center; justify-content: center;
    font-family: var(--font-display, 'Sora', sans-serif);
    font-weight: 800; font-size: 18px;
    box-shadow: 0 4px 10px rgba(0,0,0,.12);
}
.podium-slot-1 .p-avatar {
    width: 68px; height: 68px; font-size: 22px;
    background: linear-gradient(135deg, #fbbf24, #d97706);
}

.p-name {
    font-family: var(--font-display, 'Sora', sans-serif);
    font-weight: 700; font-size: 13px;
    color: var(--text-primary, #0f172a);
    margin-bottom: 2px;
    white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
}
.podium-slot-1 .p-name { font-size: 15px; }

.p-amount {
    font-family: var(--font-display, 'Sora', sans-serif);
    font-weight: 800; font-size: 16px;
    color: var(--brand-primary, #1a56db);
    margin-top: 8px;
}
.podium-slot-1 .p-amount { font-size: 20px; color: #d97706; }

.p-eff {
    font-size: 11px; font-weight: 700;
    padding: 3px 8px; border-radius: 20px;
    margin-top: 6px; display: inline-block;
    background: #f8fafc; color: var(--text-secondary, #64748b);
}
.podium-slot-1 .p-eff { background: #fef3c7; color: #92400e; }

/* ── Tabla Arena ──────────────────────────────────────────────────── */
.arena-table { width: 100%; border-collapse: separate; border-spacing: 0; }

.arena-table thead th {
    background: #0f172a; color: rgba(255,255,255,.7);
    font-size: 10.5px; font-weight: 700; text-transform: uppercase;
    letter-spacing: .5px; padding: 10px 14px;
    border-bottom: 2px solid var(--brand-primary, #1a56db);
    white-space: nowrap;
}

.arena-table tbody td {
    padding: 12px 14px; font-size: 13px;
    border-bottom: 1px solid #f1f5f9;
    vertical-align: middle;
}
.arena-table tbody tr:last-child td { border-bottom: none; }
.arena-table tbody tr:hover td { background: #f8fafc; }

/* Barra de participación */
.part-bar-wrap { display: flex; align-items: center; gap: 8px; min-width: 110px; }
.part-bar { flex: 1; height: 5px; background: #e2e8f0; border-radius: 99px; overflow: hidden; }
.part-bar-fill { height: 100%; border-radius: 99px; transition: width .8s ease; }

/* ── Panel de Equipo ──────────────────────────────────────────────── */
.equipo-panel {
    border-radius: 14px; padding: 20px;
    border: 1.5px solid;
}

/* ── Menciones ────────────────────────────────────────────────────── */
.mencion-item {
    display: flex; align-items: flex-start; gap: 10px;
    padding: 12px 0; border-bottom: 1px solid #f1f5f9;
}
.mencion-item:last-child { border-bottom: none; }
.mencion-ico {
    width: 36px; height: 36px; border-radius: 10px; flex-shrink: 0;
    display: flex; align-items: center; justify-content: center;
    font-size: 14px;
}

/* ── Selector de competencia ──────────────────────────────────────── */
.sort-btn {
    padding: 7px 16px; border-radius: 9px; border: 1.5px solid #e2e8f0;
    background: #f8fafc; color: var(--text-secondary, #64748b);
    font-size: 13px; font-weight: 600; cursor: pointer;
    transition: all .15s; text-decoration: none; white-space: nowrap;
}
.sort-btn.active {
    background: var(--brand-primary, #1a56db);
    border-color: var(--brand-primary, #1a56db);
    color: #fff;
}
.sort-btn:hover:not(.active) {
    border-color: var(--brand-primary, #1a56db);
    color: var(--brand-primary, #1a56db);
}

/* ── Responsive ────────────────────────────────────────────────────── */
@media (max-width: 768px) {
    .podium-wrap {
        grid-template-columns: 1fr;
        padding: 8px 0 0;
    }
    .podium-slot-1 { order: 1; }
    .podium-slot-2 { order: 2; }
    .podium-slot-3 { order: 3; }
    .podium-card { border-radius: 14px; padding-bottom: 20px !important; }
    .podium-step { border-radius: 0 0 8px 8px; }
    .arena-table thead { display: none; }
    .arena-table tbody td { display: block; padding: 4px 14px; }
    .arena-table tbody td:first-child { padding-top: 12px; }
    .arena-table tbody td:last-child  { padding-bottom: 12px; }
}
</style>
@endpush

@section('content')
@php
    $cur  = config('app_client.locale.currency_symbol', '$');
    $dec  = config('app_client.locale.decimal_sep', ',');
    $thou = config('app_client.locale.thousands_sep', '.');
    $fmt0 = fn(float $v) => $cur . ' ' . number_format($v, 0, $dec, $thou);
    $fmt2 = fn(float $v) => $cur . ' ' . number_format($v, 2, $dec, $thou);

    // Colores del equipo según % global
    $teamColor = $totalPct >= 70 ? '#15803d' : ($totalPct >= 40 ? '#92400e' : '#b91c1c');
    $teamBg    = $totalPct >= 70 ? '#f0fdf4' : ($totalPct >= 40 ? '#fffbeb' : '#fff5f5');
    $teamBdr   = $totalPct >= 70 ? '#86efac' : ($totalPct >= 40 ? '#fde68a' : '#fca5a5');
    $teamMsg   = match(true) {
        $totalPct >= 70 => '¡Equipo en ritmo imparable! Alto volumen y buena liquidez. Mantener la presión.',
        $totalPct >= 40 => 'Buen volumen de ventas, pero la cobranza necesita atención. Activar seguimiento de cartera.',
        default         => 'Alerta: cobranza crítica. Priorizar recuperación antes de cerrar más ventas.',
    };
@endphp

{{-- ── HEADER ──────────────────────────────────────────────────────── --}}
<div class="d-flex align-items-start justify-content-between flex-wrap gap-3 mb-4">
    <div>
        <h1 class="font-display mb-1" style="font-size:22px;font-weight:800;">
            <i class="fas fa-trophy text-warning me-2"></i> Ranking de Vendedores
        </h1>
        <p class="mb-0" style="font-size:13px;color:var(--text-muted);">
            Período:
            <strong style="color:var(--text-secondary);">
                {{ \Carbon\Carbon::parse($from)->format(config('app_client.locale.date_format','d/m/Y')) }}
                —
                {{ \Carbon\Carbon::parse($to)->format(config('app_client.locale.date_format','d/m/Y')) }}
            </strong>
            &nbsp;·&nbsp;
            Compitiendo por:
            <strong style="color:var(--brand-primary);">
                {{ $sortBy === 'cobrado' ? 'Cobranza' : 'Facturación' }}
            </strong>
        </p>
    </div>

    {{-- ── Controles: Flatpickr + Selector de competencia ── --}}
    {{-- Un solo form que preserva TODOS los parámetros al cambiar cualquier cosa --}}
    <form method="GET" action="{{ url()->current() }}" id="rankingForm"
          class="d-flex align-items-center gap-2 flex-wrap">

        {{-- Rango de fechas — mismo patrón que el topbar --}}
        <div class="date-range-badge" id="rankingDateTrigger"
             style="cursor:pointer;background:#f8fafc;border:1px solid #e2e8f0;
                    border-radius:9px;padding:7px 12px;font-size:12.5px;
                    display:flex;align-items:center;gap:7px;color:#64748b;">
            <i class="fas fa-calendar-alt" style="color:var(--brand-primary);font-size:12px;"></i>
            <span id="rankingDateDisplay">
                {{ \Carbon\Carbon::parse($from)->format('d/m/Y') }}
                &nbsp;—&nbsp;
                {{ \Carbon\Carbon::parse($to)->format('d/m/Y') }}
            </span>
            <i class="fas fa-chevron-down" style="font-size:9px;"></i>
        </div>

        {{-- Input oculto que Flatpickr maneja --}}
        <input type="text"   id="rankingFlatpickr" style="display:none"
               value="{{ $from }} to {{ $to }}">
        <input type="hidden" id="rankingFrom" name="from" value="{{ $from }}">
        <input type="hidden" id="rankingTo"   name="to"   value="{{ $to }}">

        {{-- Selector de competencia — como botones, no un <select> --}}
        <input type="hidden" name="sort" id="sortInput" value="{{ $sortBy }}">
        <div class="d-flex gap-1">
            <button type="button"
                    class="sort-btn {{ $sortBy === 'facturado' ? 'active' : '' }}"
                    onclick="setSort('facturado')">
                <i class="fas fa-chart-bar me-1"></i> Facturación
            </button>
            <button type="button"
                    class="sort-btn {{ $sortBy === 'cobrado' ? 'active' : '' }}"
                    onclick="setSort('cobrado')">
                <i class="fas fa-coins me-1"></i> Cobranza
            </button>
        </div>

    </form>
</div>

@if($ranking->isEmpty())

{{-- ── Estado vacío ──────────────────────────────────────────────── --}}
<div class="panel-card animate-in" style="text-align:center;padding:80px 20px;">
    <i class="fas fa-flag-checkered fa-3x d-block mb-4 opacity-20"></i>
    <h4 class="font-display mb-2">La pista está vacía</h4>
    <p style="color:var(--text-muted);font-size:13px;">
        No hay registros de ventas ni cobranzas en este período.
    </p>
</div>

@else

{{-- ════════════════════════════════════════════════════════════════ --}}
{{-- BLOQUE 1 — PODIO                                               --}}
{{-- ════════════════════════════════════════════════════════════════ --}}
<div class="panel-card mb-4 animate-in" style="padding:20px 24px 0;">
    <div class="section-header" style="margin-bottom:0;">
        <div>
            <h3 class="section-title mb-0">
                <i class="fas fa-podium me-2" style="color:#fbbf24;font-size:13px;"></i>
                Top 3 del Período
            </h3>
            <p class="section-subtitle mb-0">
                {{ $sortBy === 'cobrado' ? 'Ordenado por cobranza' : 'Ordenado por facturación' }}
            </p>
        </div>
    </div>

    <div class="podium-wrap">

        {{-- Puesto 2 (izquierda) --}}
        <div class="podium-slot-2">
            @if(isset($podio[1]))
            @php $v = $podio[1]; @endphp
            <div class="podium-card">
                <span class="podium-medal">🥈</span>
                <div class="p-avatar">
                    {{ strtoupper(substr($v['nombre'], 0, 2)) }}
                </div>
                <div class="p-name" title="{{ $v['nombre'] }}">
                    {{ mb_strimwidth($v['nombre'], 0, 18, '…') }}
                </div>
                <div style="font-size:10.5px;color:var(--text-muted);">{{ $v['codigo'] }}</div>
                <div class="p-amount">
                    {{ $fmt0((float)($sortBy === 'cobrado' ? $v['cobranzas_mes'] : $v['monto_facturado'])) }}
                </div>
                <span class="p-eff">
                    {{ number_format($v['porcentaje_cobranza'], 1) }}% eficiencia
                </span>
            </div>
            <div class="podium-step">2°</div>
            @else
            <div class="podium-card" style="opacity:.25;">
                <span class="podium-medal">🥈</span>
                <div class="p-avatar" style="background:#e2e8f0;"></div>
                <div class="p-name">—</div>
            </div>
            <div class="podium-step">2°</div>
            @endif
        </div>

        {{-- Puesto 1 (centro, más alto) --}}
        <div class="podium-slot-1">
            @if(isset($podio[0]))
            @php $v = $podio[0]; @endphp
            <div class="podium-card">
                <span class="podium-medal">🏆</span>
                <div class="p-avatar">
                    {{ strtoupper(substr($v['nombre'], 0, 2)) }}
                </div>
                <div class="p-name" title="{{ $v['nombre'] }}">
                    {{ mb_strimwidth($v['nombre'], 0, 18, '…') }}
                </div>
                <div style="font-size:11px;font-weight:600;color:#d97706;">
                    {{ $v['codigo'] }} &nbsp;·&nbsp; MVP
                </div>
                <div class="p-amount">
                    {{ $fmt0((float)($sortBy === 'cobrado' ? $v['cobranzas_mes'] : $v['monto_facturado'])) }}
                </div>
                <span class="p-eff">
                    <i class="fas fa-fire me-1"></i>
                    {{ number_format($v['porcentaje_cobranza'], 1) }}% eficiencia
                </span>
            </div>
            <div class="podium-step">1° LUGAR</div>
            @endif
        </div>

        {{-- Puesto 3 (derecha) --}}
        <div class="podium-slot-3">
            @if(isset($podio[2]))
            @php $v = $podio[2]; @endphp
            <div class="podium-card">
                <span class="podium-medal">🥉</span>
                <div class="p-avatar">
                    {{ strtoupper(substr($v['nombre'], 0, 2)) }}
                </div>
                <div class="p-name" title="{{ $v['nombre'] }}">
                    {{ mb_strimwidth($v['nombre'], 0, 18, '…') }}
                </div>
                <div style="font-size:10.5px;color:var(--text-muted);">{{ $v['codigo'] }}</div>
                <div class="p-amount">
                    {{ $fmt0((float)($sortBy === 'cobrado' ? $v['cobranzas_mes'] : $v['monto_facturado'])) }}
                </div>
                <span class="p-eff">
                    {{ number_format($v['porcentaje_cobranza'], 1) }}% eficiencia
                </span>
            </div>
            <div class="podium-step">3°</div>
            @else
            <div class="podium-card" style="opacity:.25;">
                <span class="podium-medal">🥉</span>
                <div class="p-avatar" style="background:#e2e8f0;"></div>
                <div class="p-name">—</div>
            </div>
            <div class="podium-step">3°</div>
            @endif
        </div>

    </div>
</div>

{{-- ════════════════════════════════════════════════════════════════ --}}
{{-- BLOQUE 2 — TABLA ARENA + PANEL LATERAL                        --}}
{{-- ════════════════════════════════════════════════════════════════ --}}
<div class="row g-4">

    {{-- ── Tabla completa de vendedores ── --}}
    <div class="col-12 col-xl-8 animate-in">
        <div class="panel-card h-100">
            <div class="panel-card-header">
                <div>
                    <h3 class="section-title mb-0">
                        <i class="fas fa-users me-2" style="color:var(--brand-primary);font-size:13px;"></i>
                        Tabla de Posiciones Completa
                    </h3>
                    <p class="section-subtitle mb-0">
                        {{ $ranking->count() }} vendedores con actividad en el período
                    </p>
                </div>
            </div>
            <div style="overflow-x:auto;">
                <table class="arena-table">
                    <thead>
                        <tr>
                            <th style="width:40px;">#</th>
                            <th>Vendedor</th>
                            <th style="text-align:right;">Facturado</th>
                            <th style="text-align:right;">Cobrado</th>
                            <th style="text-align:center;min-width:120px;">Eficiencia</th>
                            <th style="text-align:center;min-width:130px;">Participación</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach(array_merge($podio, $arena) as $idx => $v)
                        @php
                            $rank    = $idx + 1;
                            $pct     = (float)$v['porcentaje_cobranza'];
                            $pctColor= $pct >= 70 ? '#059669' : ($pct >= 40 ? '#d97706' : '#dc2626');
                            $pctBg   = $pct >= 70 ? '#dcfce7' : ($pct >= 40 ? '#fef3c7' : '#fee2e2');
                            $rankBg  = match($rank) { 1=>'#fef9c3', 2=>'#f8fafc', 3=>'#fff7ed', default=>'transparent' };
                            $rankColor=match($rank) { 1=>'#d97706', 2=>'#94a3b8', 3=>'#b45309', default=>'#cbd5e1' };
                        @endphp
                        <tr style="background:{{ $rankBg }};">
                            <td>
                                <span style="
                                    font-family:var(--font-display,'Sora',sans-serif);
                                    font-size:15px;font-weight:900;
                                    color:{{ $rankColor }};
                                ">{{ $rank }}</span>
                            </td>
                            <td>
                                <div style="display:flex;align-items:center;gap:10px;">
                                    <div style="
                                        width:32px;height:32px;border-radius:50%;
                                        background:{{ $rankColor }}22;
                                        display:flex;align-items:center;justify-content:center;
                                        font-family:var(--font-display,'Sora',sans-serif);
                                        font-weight:800;font-size:12px;
                                        color:{{ $rankColor }};
                                        flex-shrink:0;
                                    ">{{ strtoupper(substr($v['nombre'],0,2)) }}</div>
                                    <div>
                                        <div style="font-weight:700;font-size:13px;color:var(--text-primary);">
                                            {{ $v['nombre'] }}
                                        </div>
                                        <div style="font-size:11px;color:var(--text-muted);">
                                            Cód. {{ $v['codigo'] }}
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td style="text-align:right;font-family:var(--font-display,'Sora',sans-serif);font-weight:700;font-size:13px;">
                                {{ $fmt0((float)$v['monto_facturado']) }}
                            </td>
                            <td style="text-align:right;font-weight:600;font-size:13px;color:#059669;">
                                {{ $fmt0((float)$v['cobranzas_mes']) }}
                            </td>
                            <td style="text-align:center;">
                                <span style="
                                    display:inline-block;
                                    padding:3px 10px;border-radius:20px;
                                    background:{{ $pctBg }};color:{{ $pctColor }};
                                    font-size:12px;font-weight:700;
                                ">{{ number_format($pct,1) }}%</span>
                            </td>
                            <td>
                                <div class="part-bar-wrap">
                                    <div class="part-bar">
                                        <div class="part-bar-fill"
                                             style="width:{{ $v['pct_barra'] ?? 0 }}%;
                                                    background:{{ $sortBy === 'cobrado' ? '#059669' : 'var(--brand-primary,#1a56db)' }};"></div>
                                    </div>
                                    <span style="font-size:11.5px;font-weight:700;color:var(--text-secondary);min-width:36px;text-align:right;">
                                        {{ $v['pct_barra'] ?? 0 }}%
                                    </span>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                    {{-- Fila de totales del equipo --}}
                    <tfoot>
                        <tr>
                            <td colspan="2"
                                style="padding:12px 14px;font-size:12px;font-weight:800;
                                       color:var(--text-secondary);text-transform:uppercase;
                                       letter-spacing:.5px;background:#f8fafc;
                                       border-top:2px solid var(--brand-primary);">
                                TOTAL EQUIPO
                            </td>
                            <td style="text-align:right;font-family:var(--font-display,'Sora',sans-serif);
                                       font-weight:800;font-size:14px;background:#f8fafc;
                                       border-top:2px solid var(--brand-primary);">
                                {{ $fmt0($totalFac) }}
                            </td>
                            <td style="text-align:right;font-weight:800;font-size:14px;
                                       color:#059669;background:#f8fafc;
                                       border-top:2px solid var(--brand-primary);">
                                {{ $fmt0($totalCob) }}
                            </td>
                            <td style="text-align:center;background:#f8fafc;
                                       border-top:2px solid var(--brand-primary);">
                                <span style="
                                    display:inline-block;
                                    padding:3px 10px;border-radius:20px;font-size:12px;font-weight:700;
                                    background:{{ $totalPct>=70?'#dcfce7':($totalPct>=40?'#fef3c7':'#fee2e2') }};
                                    color:{{ $totalPct>=70?'#15803d':($totalPct>=40?'#92400e':'#b91c1c') }};
                                ">{{ number_format($totalPct,1) }}%</span>
                            </td>
                            <td style="background:#f8fafc;border-top:2px solid var(--brand-primary);">
                                <div class="part-bar-wrap">
                                    <div class="part-bar">
                                        <div class="part-bar-fill"
                                             style="width:{{ min($totalPct,100) }}%;
                                                    background:{{ $totalPct>=70?'#059669':($totalPct>=40?'#d97706':'#dc2626') }};"></div>
                                    </div>
                                    <span style="font-size:11.5px;font-weight:700;color:var(--text-secondary);min-width:36px;text-align:right;">
                                        {{ number_format($totalPct,1) }}%
                                    </span>
                                </div>
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>

    {{-- ── Panel lateral: Pulso + Menciones ── --}}
    <div class="col-12 col-xl-4 animate-in">

        {{-- Pulso del equipo --}}
        <div class="equipo-panel mb-4"
             style="background:{{ $teamBg }};border-color:{{ $teamBdr }};">
            <div style="display:flex;align-items:center;gap:8px;margin-bottom:10px;">
                <i class="fas fa-heartbeat" style="color:{{ $teamColor }};font-size:16px;"></i>
                <span style="font-family:var(--font-display,'Sora',sans-serif);
                             font-size:14px;font-weight:800;color:{{ $teamColor }};">
                    Pulso del Equipo
                </span>
            </div>
            <p style="font-size:12.5px;color:{{ $teamColor }};opacity:.85;
                      margin-bottom:16px;line-height:1.5;">
                {{ $teamMsg }}
            </p>

            <div style="background:rgba(255,255,255,.65);border-radius:10px;padding:14px;">
                @foreach([
                    ['Facturado total', $fmt0($totalFac), 'var(--text-primary)'],
                    ['Cobrado total',   $fmt0($totalCob), $teamColor],
                ] as [$label, $val, $color])
                <div style="display:flex;justify-content:space-between;
                            margin-bottom:8px;font-size:12.5px;">
                    <span style="color:var(--text-secondary);font-weight:600;">{{ $label }}</span>
                    <span style="font-family:var(--font-display,'Sora',sans-serif);
                                 font-weight:800;color:{{ $color }};">{{ $val }}</span>
                </div>
                @endforeach

                {{-- Barra global --}}
                <div style="height:8px;background:#e2e8f0;border-radius:99px;
                            overflow:hidden;margin-top:10px;margin-bottom:5px;">
                    <div style="height:100%;border-radius:99px;
                                width:{{ min($totalPct,100) }}%;
                                background:{{ $teamColor }};
                                transition:width 1s ease;"></div>
                </div>
                <div style="text-align:center;font-size:11.5px;
                            font-weight:800;color:{{ $teamColor }};">
                    Liquidación global: {{ number_format($totalPct,1) }}%
                </div>
            </div>
        </div>

        {{-- Menciones honoríficas --}}
        <div class="panel-card">
            <div class="panel-card-header">
                <h3 class="section-title mb-0">
                    <i class="fas fa-star text-warning me-2" style="font-size:13px;"></i>
                    Menciones Especiales
                </h3>
            </div>
            <div class="panel-card-body" style="padding-top:8px;">

                {{-- Francotirador: máxima eficiencia --}}
                @if($reyEficiencia)
                <div class="mencion-item">
                    <div class="mencion-ico" style="background:#dcfce7;color:#15803d;">
                        <i class="fas fa-bullseye"></i>
                    </div>
                    <div>
                        <div style="font-size:10.5px;font-weight:800;text-transform:uppercase;
                                    letter-spacing:.5px;color:var(--text-muted);margin-bottom:2px;">
                            🎯 Francotirador (Mayor Eficiencia)
                        </div>
                        <div style="font-weight:700;font-size:13px;color:var(--text-primary);">
                            {{ $reyEficiencia['nombre'] }}
                        </div>
                        <div style="font-size:12px;color:#059669;font-weight:600;">
                            {{ number_format($reyEficiencia['porcentaje_cobranza'],1) }}% de retorno real
                        </div>
                    </div>
                </div>
                @endif

                {{-- Motor comercial: mayor facturación --}}
                @if($motorComercial)
                @php
                    $pctMotor = $totalFac > 0
                        ? round(($motorComercial['monto_facturado'] / $totalFac) * 100, 1)
                        : 0;
                @endphp
                <div class="mencion-item">
                    <div class="mencion-ico" style="background:#eff6ff;color:#1a56db;">
                        <i class="fas fa-rocket"></i>
                    </div>
                    <div>
                        <div style="font-size:10.5px;font-weight:800;text-transform:uppercase;
                                    letter-spacing:.5px;color:var(--text-muted);margin-bottom:2px;">
                            🚀 Motor Comercial (Mayor Volumen)
                        </div>
                        <div style="font-weight:700;font-size:13px;color:var(--text-primary);">
                            {{ $motorComercial['nombre'] }}
                        </div>
                        <div style="font-size:12px;color:var(--brand-primary);font-weight:600;">
                            Generó el {{ $pctMotor }}% de las ventas del equipo
                        </div>
                    </div>
                </div>
                @endif

                {{-- Nota de período --}}
                <div style="margin-top:12px;padding:10px 12px;
                            background:#f8fafc;border-radius:9px;
                            font-size:11.5px;color:var(--text-muted);line-height:1.5;">
                    <i class="fas fa-info-circle me-1"></i>
                    Los títulos se calculan sobre el período seleccionado.
                    Cambia el rango de fechas para ver diferentes resultados.
                </div>

            </div>
        </div>

    </div>
</div>

@endif

@endsection

@push('scripts')
<script>
(function(){
    'use strict';

    // ── Flatpickr conectado al form —————————————————————————————
    // Mismo patrón que el topbar del proyecto
    if (typeof flatpickr !== 'undefined') {
        flatpickr('#rankingFlatpickr', {
            mode:          'range',
            dateFormat:    'Y-m-d',
            locale:        'es',
            defaultDate:   [
                '{{ $from }}',
                '{{ $to }}'
            ],
            maxDate: 'today',
            disableMobile: true,

            onReady(_, __, instance) {
                document.getElementById('rankingDateTrigger')
                    ?.addEventListener('click', () => instance.open());
            },

            onChange(selectedDates) {
                if (selectedDates.length !== 2) return;
                const fmt = d => d.toISOString().split('T')[0];
                const from = fmt(selectedDates[0]);
                const to   = fmt(selectedDates[1]);

                document.getElementById('rankingFrom').value = from;
                document.getElementById('rankingTo').value   = to;

                // Actualizar el texto del badge visual
                const fmtDisplay = d => {
                    const [y,m,day] = d.split('-');
                    return `${day}/${m}/${y}`;
                };
                document.getElementById('rankingDateDisplay').textContent =
                    fmtDisplay(from) + ' — ' + fmtDisplay(to);

                // Submit automático con pequeño delay
                setTimeout(() => document.getElementById('rankingForm').submit(), 300);
            },
        });
    }

    // ── Selector de competencia — botones en vez de <select> ────
    window.setSort = function(valor) {
        document.getElementById('sortInput').value = valor;
        document.getElementById('rankingForm').submit();
    };

    // ── Animar barras de progreso al cargar ─────────────────────
    requestAnimationFrame(() => {
        document.querySelectorAll('.part-bar-fill').forEach(bar => {
            const w = bar.style.width;
            bar.style.width = '0';
            setTimeout(() => { bar.style.width = w; }, 150);
        });
    });

})();
</script>
@endpush