@extends('layouts.app')

@section('title-page', 'Leaderboard de Ventas')

@section('breadcrumb')
    <a href="{{ route('ventas.index') }}" style="color:var(--text-muted);text-decoration:none;">Ventas</a>
    <span style="color:#cbd5e1;margin:0 4px;">/</span>
    <span class="current">Ranking de Vendedores</span>
@endsection

@push('styles')
<style>
    /* ── Estilos Exclusivos del Leaderboard ── */
    .podium-container {
        display: flex;
        align-items: flex-end;
        justify-content: center;
        gap: 16px;
        min-height: 280px;
        margin-top: 20px;
    }

    .podium-card {
        background: var(--card-bg);
        border-radius: 16px 16px 8px 8px;
        padding: 24px 20px;
        text-align: center;
        box-shadow: var(--card-shadow);
        position: relative;
        transition: transform 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        border: 1px solid rgba(0,0,0,.04);
        width: 30%;
        max-width: 260px;
    }

    .podium-card:hover { transform: translateY(-8px); }

    /* Alturas escalonadas */
    .podium-rank-1 { padding-bottom: 60px; z-index: 3; border-top: 4px solid #fbbf24; }
    .podium-rank-2 { padding-bottom: 40px; z-index: 2; border-top: 4px solid #94a3b8; }
    .podium-rank-3 { padding-bottom: 20px; z-index: 1; border-top: 4px solid #b45309; }

    /* Medallas y Avatares */
    .medal-icon {
        font-size: 32px;
        position: absolute;
        top: -18px;
        left: 50%;
        transform: translateX(-50%);
        filter: drop-shadow(0 4px 6px rgba(0,0,0,0.15));
    }

    .podium-rank-1 .medal-icon { color: #fbbf24; font-size: 42px; top: -24px; } /* Oro */
    .podium-rank-2 .medal-icon { color: #cbd5e1; } /* Plata */
    .podium-rank-3 .medal-icon { color: #d97706; } /* Bronce */

    .avatar-circle {
        width: 64px; height: 64px;
        border-radius: 50%;
        background: var(--brand-sidebar-bg);
        color: #fff;
        display: flex; align-items: center; justify-content: center;
        font-family: var(--font-display); font-size: 20px; font-weight: 700;
        margin: 12px auto;
        box-shadow: 0 4px 10px rgba(0,0,0,0.1);
    }
    .podium-rank-1 .avatar-circle { width: 80px; height: 80px; font-size: 28px; background: linear-gradient(135deg, #fbbf24 0%, #d97706 100%); }

    .podium-name { font-family: var(--font-display); font-weight: 700; font-size: 15px; color: var(--text-primary); margin-bottom: 4px; }
    .podium-amount { font-family: var(--font-display); font-weight: 800; font-size: 20px; color: var(--brand-primary); letter-spacing: -0.5px; }
    .podium-rank-1 .podium-amount { font-size: 26px; }

    /* Fila estilo Arena para el resto */
    .arena-row {
        background: var(--card-bg);
        border-radius: 12px;
        padding: 16px 20px;
        margin-bottom: 12px;
        display: flex; align-items: center; justify-content: space-between;
        box-shadow: 0 2px 4px rgba(0,0,0,.02);
        border: 1px solid #f1f5f9;
        transition: all 0.2s;
    }
    .arena-row:hover { border-color: #e2e8f0; transform: translateX(4px); box-shadow: var(--card-shadow); }

    .status-badge {
        font-size: 11px; font-weight: 700; padding: 4px 10px; border-radius: 20px;
    }

    @media (max-width: 768px) {
        .podium-container { flex-direction: column; align-items: center; gap: 32px; }
        .podium-card { width: 100%; max-width: 100%; padding-bottom: 24px !important; }
        .podium-rank-1 { order: -1; } /* El 1ro siempre arriba en mobile */
    }
</style>
@endpush

@section('content')
@php
    $currency = config('app_client.locale.currency_symbol');
    $dec      = config('app_client.locale.decimal_sep');
    $thou     = config('app_client.locale.thousands_sep');
@endphp

<div class="d-flex align-items-start justify-content-between flex-wrap gap-3 mb-4">
    <div>
        <h1 class="font-display mb-1" style="font-size:22px;font-weight:800;">
            <i class="fas fa-trophy text-warning me-2"></i>
                Leaderboard de Ventas</h1>
        <p class="section-subtitle mb-0">Rendimiento, facturación y cobranza del equipo.
            | Período: <strong>{{ \Carbon\Carbon::parse($from)->format(config('app_client.locale.date_format')) }}
            — {{ \Carbon\Carbon::parse($to)->format(config('app_client.locale.date_format')) }}</strong></p>
    </div>
    <form method="GET" id="rankingForm" class="d-flex gap-3 bg-white p-2 rounded-3 shadow-sm border">
        <!--div class="input-group input-group-sm">
            <span class="input-group-text bg-light border-end-0"><i class="fas fa-calendar-alt text-muted"></i></span>
            <input type="text" name="_range" class="form-control border-start-0 date-picker" value="{{ request('_range') }}" placeholder="Seleccione período">
            <input type="hidden" name="from" id="filterFrom" value="{{ $from }}">
            <input type="hidden" name="to" id="filterTo" value="{{ $to }}">
        </div-->

        <select name="sort" class="form-select form-select-sm" style="width: auto; font-weight: 600; color: var(--brand-primary);" onchange="document.getElementById('rankingForm').submit()">
            <option value="facturado" {{ $sortBy === 'facturado' ? 'selected' : '' }}>🏆 Competir por Facturación</option>
            <option value="cobrado" {{ $sortBy === 'cobrado' ? 'selected' : '' }}>💰 Competir por Cobranza</option>
        </select>
    </form>
</div>

@if($ranking->isEmpty())
    <div class="text-center py-5 panel-card animate-in">
        <i class="fas fa-flag-checkered fa-3x mb-3 text-muted opacity-25"></i>
        <h4 class="font-display">La pista está vacía</h4>
        <p class="text-muted">No hay registros de ventas ni cobranzas en este período.</p>
    </div>
@else

    <div class="podium-container animate-in">
        @if(isset($podio[1]))
        <div class="podium-card podium-rank-2">
            <i class="fas fa-medal medal-icon"></i>
            <div class="avatar-circle">{{ substr($podio[1]['nombre'], 0, 2) }}</div>
            <div class="podium-name">{{ mb_strimwidth($podio[1]['nombre'], 0, 20, '...') }}</div>
            <div class="text-muted" style="font-size: 11px;">#{{ $podio[1]['codigo'] }}</div>
            <div class="podium-amount mt-3">{{ $currency }} {{ number_format($sortBy === 'cobrado' ? $podio[1]['cobranzas_mes'] : $podio[1]['monto_facturado'], 0, $dec, $thou) }}</div>
            <div class="mt-2 status-badge" style="background: #f8fafc; color: var(--text-secondary);">
                {{ number_format($podio[1]['porcentaje_cobranza'], 1) }}% Eficiencia
            </div>
        </div>
        @endif

        @if(isset($podio[0]))
        <div class="podium-card podium-rank-1">
            <i class="fas fa-trophy medal-icon"></i>
            <div class="avatar-circle">{{ substr($podio[0]['nombre'], 0, 2) }}</div>
            <div class="podium-name" style="font-size: 18px;">{{ mb_strimwidth($podio[0]['nombre'], 0, 20, '...') }}</div>
            <div class="text-muted" style="font-size: 12px; font-weight:600;">#{{ $podio[0]['codigo'] }} • MVP</div>
            <div class="podium-amount mt-3" style="color: #d97706;">{{ $currency }} {{ number_format($sortBy === 'cobrado' ? $podio[0]['cobranzas_mes'] : $podio[0]['monto_facturado'], 0, $dec, $thou) }}</div>
            <div class="mt-2 status-badge" style="background: #fef3c7; color: #b45309;">
                <i class="fas fa-fire me-1"></i> {{ number_format($podio[0]['porcentaje_cobranza'], 1) }}% Eficiencia
            </div>
        </div>
        @endif

        @if(isset($podio[2]))
        <div class="podium-card podium-rank-3">
            <i class="fas fa-medal medal-icon"></i>
            <div class="avatar-circle">{{ substr($podio[2]['nombre'], 0, 2) }}</div>
            <div class="podium-name">{{ mb_strimwidth($podio[2]['nombre'], 0, 20, '...') }}</div>
            <div class="text-muted" style="font-size: 11px;">#{{ $podio[2]['codigo'] }}</div>
            <div class="podium-amount mt-3">{{ $currency }} {{ number_format($sortBy === 'cobrado' ? $podio[2]['cobranzas_mes'] : $podio[2]['monto_facturado'], 0, $dec, $thou) }}</div>
            <div class="mt-2 status-badge" style="background: #f8fafc; color: var(--text-secondary);">
                {{ number_format($podio[2]['porcentaje_cobranza'], 1) }}% Eficiencia
            </div>
        </div>
        @endif
    </div>

    <div class="row mt-5">
        <div class="col-lg-8 animate-in" style="animation-delay: 0.1s;">
            <h4 class="font-display mb-3" style="font-size: 16px; font-weight:700;"><i class="fas fa-users text-muted me-2"></i> El Resto del Batallón</h4>

            @if($arena->isEmpty())
                <p class="text-muted">Todos los vendedores con actividad están en el podio.</p>
            @else
                @foreach($arena as $idx => $v)
                @php
                    $rank = $idx + 4; // Porque empieza después del top 3
                    $pct = $v['porcentaje_cobranza'];
                    // Lógica de gamificación para etiquetas
                    $etiqueta = ''; $badgeCls = '';
                    if ($pct >= 85) { $etiqueta = 'Cerrador Estrella 🌟'; $badgeCls = 'bg-success-subtle text-success'; }
                    elseif ($pct >= 50 && $v['monto_facturado'] > ($totalFac / $ranking->count())) { $etiqueta = 'Cazador en Racha 🏹'; $badgeCls = 'bg-primary-subtle text-primary'; }
                    else { $etiqueta = 'En Batalla ⚔️'; $badgeCls = 'bg-secondary-subtle text-secondary'; }
                @endphp
                <div class="arena-row">
                    <div class="d-flex align-items-center gap-3">
                        <div style="font-family: var(--font-display); font-weight:800; font-size:18px; color: #cbd5e1; width: 24px; text-align:right;">
                            {{ $rank }}
                        </div>
                        <div>
                            <div style="font-weight: 700; color: var(--text-primary);">{{ $v['nombre'] }}</div>
                            <span class="badge {{ $badgeCls }}">{{ $etiqueta }}</span>
                        </div>
                    </div>

                    <div class="text-end">
                        <div style="font-size: 11px; color: var(--text-muted); text-transform: uppercase; font-weight:600;">
                            {{ $sortBy === 'cobrado' ? 'Cobrado' : 'Facturado' }}
                        </div>
                        <div style="font-family: var(--font-display); font-weight:700; font-size:15px; color: var(--brand-primary);">
                            {{ $currency }} {{ number_format($sortBy === 'cobrado' ? $v['cobranzas_mes'] : $v['monto_facturado'], 0, $dec, $thou) }}
                        </div>
                    </div>

                    <div style="width: 120px; text-align:right;">
                        <div style="font-size: 12px; font-weight:600; color: {{ $pct >= 70 ? 'var(--brand-success)' : ($pct >= 40 ? 'var(--brand-warning)' : 'var(--brand-danger)') }}; mb-1">
                            {{ number_format($pct, 1) }}% Eficiencia
                        </div>
                        <div class="progress" style="height: 6px;">
                            <div class="progress-bar" style="background: {{ $pct >= 70 ? 'var(--brand-success)' : ($pct >= 40 ? 'var(--brand-warning)' : 'var(--brand-danger)') }}; width: {{ min($pct, 100) }}%"></div>
                        </div>
                    </div>
                </div>
                @endforeach
            @endif
        </div>

        <div class="col-lg-4 animate-in" style="animation-delay: 0.2s;">
            @php
                $teamColor = $totalPct >= 70 ? '#15803d' : ($totalPct >= 40 ? '#92400e' : '#b91c1c');
                $teamBg    = $totalPct >= 70 ? '#dcfce7' : ($totalPct >= 40 ? '#fef3c7' : '#fee2e2');
                $teamMsg   = $totalPct >= 70
                    ? "¡Ritmo imparable! El equipo no solo está cerrando negocios, está asegurando la liquidez. ¡Sigamos cazando juntos!"
                    : ($totalPct >= 40 ? "Buen volumen, pero la calle está dura. Enfoquemos el cierre en recuperar cartera." : "¡Alerta Roja! Ninguno de nosotros es tan bueno como todos juntos. Urge plan de choque de cobranzas.");
            @endphp
            <div class="panel-card mb-4" style="background: {{ $teamBg }}; border: 1px solid color-mix(in srgb, {{ $teamColor }} 30%, transparent);">
                <div class="panel-card-body">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <i class="fas fa-users" style="color: {{ $teamColor }};"></i>
                        <h5 class="mb-0 font-display" style="color: {{ $teamColor }}; font-weight:700;">Meta Colectiva</h5>
                    </div>
                    <p style="font-size: 13px; color: {{ $teamColor }}; opacity: 0.9; margin-bottom: 16px;">
                        {{ $teamMsg }}
                    </p>

                    <div class="p-3 rounded-3" style="background: rgba(255,255,255,0.6);">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span style="font-size: 12px; font-weight: 700; color: var(--text-secondary);">Facturado Equipo</span>
                            <span class="font-display" style="font-weight: 800;">{{ $currency }} {{ number_format($totalFac, 0, $dec, $thou) }}</span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center">
                            <span style="font-size: 12px; font-weight: 700; color: var(--text-secondary);">Cobrado Equipo</span>
                            <span class="font-display" style="color: {{ $teamColor }}; font-weight: 800;">{{ $currency }} {{ number_format($totalCob, 0, $dec, $thou) }}</span>
                        </div>
                        <div class="progress mt-3" style="height: 8px;">
                            <div class="progress-bar" style="background-color: {{ $teamColor }}; width: {{ min($totalPct, 100) }}%"></div>
                        </div>
                        <div class="text-center mt-1" style="font-size: 11px; font-weight:700; color: {{ $teamColor }};">
                            Liquidación Global: {{ number_format($totalPct, 1) }}%
                        </div>
                    </div>
                </div>
            </div>

            <div class="panel-card">
                <div class="panel-card-header pb-0 border-0">
                    <h5 class="font-display" style="font-size: 14px; font-weight:700;"><i class="fas fa-star text-warning me-2"></i> Menciones Honoríficas</h5>
                </div>
                <div class="panel-card-body">
                    @if($reyEficiencia)
                    <div class="d-flex align-items-start gap-3 mb-3">
                        <div class="kpi-icon-wrap" style="--accent-color: var(--brand-success); width: 36px; height: 36px; font-size: 14px;">
                            <i class="fas fa-bullseye"></i>
                        </div>
                        <div>
                            <div style="font-size: 11px; font-weight:700; text-transform:uppercase; color: var(--text-muted);">Francotirador (Eficiencia)</div>
                            <div style="font-weight:700; color: var(--text-primary); font-size: 13px;">{{ $reyEficiencia['nombre'] }}</div>
                            <div style="font-size: 12px; color: var(--brand-success); font-weight:600;">{{ number_format($reyEficiencia['porcentaje_cobranza'], 1) }}% de retorno real</div>
                        </div>
                    </div>
                    @endif

                    @if($podio->isNotEmpty())
                    <div class="d-flex align-items-start gap-3">
                        <div class="kpi-icon-wrap" style="--accent-color: var(--brand-primary); width: 36px; height: 36px; font-size: 14px;">
                            <i class="fas fa-rocket"></i>
                        </div>
                        <div>
                            <div style="font-size: 11px; font-weight:700; text-transform:uppercase; color: var(--text-muted);">Motor Comercial (Volumen)</div>
                            <div style="font-weight:700; color: var(--text-primary); font-size: 13px;">{{ $podio[0]['nombre'] }}</div>
                            <div style="font-size: 12px; color: var(--text-secondary); font-weight:600;">Generó el {{ $totalFac > 0 ? number_format(($podio[0]['monto_facturado'] / $totalFac) * 100, 1) : 0 }}% de las ventas</div>
                        </div>
                    </div>
                    @endif
                </div>
            </div>

        </div>
    </div>
@endif

@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Inicializar el Flatpickr conectándolo a tu formulario de filtros
        if(typeof flatpickr !== 'undefined') {
            flatpickr(".date-picker", {
                mode: "range",
                dateFormat: "Y-m-d",
                locale: "es",
                defaultDate: [document.getElementById('filterFrom').value, document.getElementById('filterTo').value],
                onChange: function(selectedDates, dateStr, instance) {
                    if (selectedDates.length === 2) {
                        // Formatear manualmente a Y-m-d para evitar errores de zona horaria
                        const f = selectedDates[0];
                        const t = selectedDates[1];

                        const fromStr = f.getFullYear() + "-" + String(f.getMonth() + 1).padStart(2, '0') + "-" + String(f.getDate()).padStart(2, '0');
                        const toStr = t.getFullYear() + "-" + String(t.getMonth() + 1).padStart(2, '0') + "-" + String(t.getDate()).padStart(2, '0');

                        document.getElementById('filterFrom').value = fromStr;
                        document.getElementById('filterTo').value = toStr;
                        document.getElementById('rankingForm').submit();
                    }
                }
            });
        }
    });
</script>
@endpush
