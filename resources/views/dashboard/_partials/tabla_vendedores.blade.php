{{--
    dashboard/_partials/tabla_vendedores.blade.php
    Tabla de ranking de vendedores con barra de progreso de cobranza.
    Variable esperada: $rankingVendedores (Collection)
--}}

<div class="panel-card h-100">
    <div class="panel-card-header">
        <div>
            <h3 class="section-title mb-0">
                <i class="fas fa-ranking-star me-2" style="color: var(--brand-primary); font-size: 13px;"></i>
                Ranking de Vendedores
            </h3>
            <p class="section-subtitle mb-0">facturación y cobranza en el período</p>
        </div>

        @if($rankingVendedores->isNotEmpty())
            <span class="badge" style="background: #ffffef6e; color: var(--brand-primary); font-size: 11px; border-radius: 6px; font-weight: 600;">
                {{ $rankingVendedores->count() }} vendedores
            </span>
        @endif

        <a href="{{ route('ventas.ranking') }}" class="btn btn-sm btn-outline-warning" style="border-radius:10px;" alt="Ver ranking completo">
            <i class="fas fa-star me-1"></i>
        </a>
    </div>

    <div style="overflow-x: auto;">
        @if($rankingVendedores->isEmpty())
            <div class="text-center py-5" style="color: var(--text-muted);">
                <i class="fas fa-users-slash fa-2x mb-3 d-block opacity-25"></i>
                <p class="mb-0" style="font-size: 13px;">Sin datos de vendedores en el período.</p>
            </div>
        @else
            <table class="vendedores-table">
                <thead>
                    <tr>
                        <th style="width: 36px;">#</th>
                        <th>Vendedor</th>
                        <th style="text-align: right;">Facturado</th>
                        <th style="text-align: right;">Cobrado</th>
                        <th style="min-width: 100px;">% Cobrado</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($rankingVendedores as $idx => $v)
                    @php
                        $rank = $idx + 1;
                        $rankClass = match(true) {
                            $rank === 1 => 'rank-1',
                            $rank === 2 => 'rank-2',
                            $rank === 3 => 'rank-3',
                            default     => 'rank-n',
                        };
                        $pct = (float) $v['porcentaje_cobranza'];
                        $barColor = match(true) {
                            $pct >= 70  => 'var(--brand-success)',
                            $pct >= 40  => 'var(--brand-warning)',
                            default     => 'var(--brand-danger)',
                        };
                        $currency   = config('app_client.locale.currency_symbol');
                        $dec        = config('app_client.locale.decimal_sep');
                        $thou       = config('app_client.locale.thousands_sep');
                    @endphp
                    <tr>
                        <td>
                            <span class="rank-badge {{ $rankClass }}">{{ $rank }}</span>
                        </td>
                        <td>
                            <div style="font-weight: 600; font-size: 13px; color: var(--text-primary);">
                                {{ $v['nombre'] }}
                            </div>
                            <div style="font-size: 11px; color: var(--text-muted);">
                                Cód. {{ $v['codigo'] }}
                            </div>
                        </td>
                        <td style="text-align: right;">
                            <span style="font-family: var(--font-display); font-weight: 600; font-size: 12.5px;">
                                {{ $currency }} {{ number_format($v['monto_facturado'], 0, $dec, $thou) }}
                            </span>
                        </td>
                        <td style="text-align: right;">
                            <span style="font-size: 12.5px; color: var(--brand-success); font-weight: 500;">
                                {{ $currency }} {{ number_format($v['cobranzas_mes'], 0, $dec, $thou) }}
                            </span>
                        </td>
                        <td>
                            <div class="cobranza-bar-wrap">
                                <div class="cobranza-bar">
                                    <div class="cobranza-bar-fill"
                                         style="width: {{ min($pct, 100) }}%; background: {{ $barColor }};">
                                    </div>
                                </div>
                                <span style="font-size: 11.5px; font-weight: 600; min-width: 36px; text-align: right; color: {{ $barColor }};">
                                    {{ number_format($pct, 1) }}%
                                </span>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>

    {{-- Totales al pie --}}
    @if($rankingVendedores->isNotEmpty())
    @php
        $totalFac  = $rankingVendedores->sum('monto_facturado');
        $totalCob  = $rankingVendedores->sum('cobranzas_mes');
        $totalPct  = $totalFac > 0 ? round($totalCob / $totalFac * 100, 1) : 0;
        $currency  = config('app_client.locale.currency_symbol');
        $dec       = config('app_client.locale.decimal_sep');
        $thou      = config('app_client.locale.thousands_sep');
    @endphp
    <div style="
        padding: 12px 14px;
        border-top: 1px solid #f1f5f9;
        display: flex;
        justify-content: space-between;
        align-items: center;
        background: #f8fafc;
    ">
        <span style="font-size: 11.5px; font-weight: 700; color: var(--text-secondary); text-transform: uppercase; letter-spacing: .4px;">
            Total Equipo
        </span>
        <div style="display: flex; gap: 20px; font-size: 12.5px;">
            <span>
                <span style="color: var(--text-muted);">Fact.</span>
                <strong style="font-family: var(--font-display); margin-left: 4px;">
                    {{ $currency }} {{ number_format($totalFac, 0, $dec, $thou) }}
                </strong>
            </span>
            <span>
                <span style="color: var(--text-muted);">Cobr.</span>
                <strong style="color: var(--brand-success); margin-left: 4px;">
                    {{ $currency }} {{ number_format($totalCob, 0, $dec, $thou) }}
                </strong>
            </span>
            <span style="
                background: {{ $totalPct >= 70 ? '#dcfce7' : ($totalPct >= 40 ? '#fef3c7' : '#fee2e2') }};
                color: {{ $totalPct >= 70 ? '#15803d' : ($totalPct >= 40 ? '#92400e' : '#b91c1c') }};
                padding: 2px 8px;
                border-radius: 6px;
                font-weight: 700;
                font-size: 12px;
            ">
                {{ number_format($totalPct, 1) }}%
            </span>
        </div>
    </div>
    @endif
</div>
