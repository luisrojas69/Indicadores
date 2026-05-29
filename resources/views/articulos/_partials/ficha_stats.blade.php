{{--
    articulos/_partials/ficha_stats.blade.php
    Estadísticas de ventas del artículo individual.
    Variables heredadas de articulos/show.blade.php:
      $articulo, $totalAnio, $mesesActivo, $promedio,
      $tendencia, $tendenciaLabel, $tendenciaColor, $tendenciaIcon,
      $estadoLabel, $estadoColor, $estadoBg, $year
--}}

<div class="info-block mt-4 animate-in">
    <div class="info-block-label">
        <i class="fas fa-chart-bar" style="color:var(--brand-primary);font-size:11px;"></i>
        Estadísticas de Ventas — {{ $year }}
    </div>

    {{-- 4 stat chips principales --}}
    <div class="row g-2 mb-3">

        {{-- Ventas del Mes --}}
        <div class="col-6 col-sm-3">
            <div class="stat-chip">
                <div class="stat-chip-val"
                     style="color:{{ (float)$articulo['ventas_mes'] > 0 ? 'var(--brand-primary)' : 'var(--text-muted)' }};">
                    {{ number_format((float)$articulo['ventas_mes'], 0, '.', '.') }}
                </div>
                <div class="stat-chip-label">Ventas del mes</div>
                @if((float)$articulo['ventas_mes'] <= 0)
                    <div style="font-size:10px;color:var(--text-muted);margin-top:2px;">Sin movimientos</div>
                @endif
            </div>
        </div>

        {{-- Ventas del Año --}}
        <div class="col-6 col-sm-3">
            <div class="stat-chip">
                <div class="stat-chip-val" style="color:var(--text-primary);">
                    {{ number_format((float)$articulo['ventas_anio'], 0, '.', '.') }}
                </div>
                <div class="stat-chip-label">Ventas del año</div>
            </div>
        </div>

        {{-- Ventas Mes Anterior --}}
        <div class="col-6 col-sm-3">
            @php
                $vMesAnt   = (float)$articulo['ventas_mes_anterior'];
                $vMesAct   = (float)$articulo['ventas_mes'];
                $varMes    = $vMesAnt > 0 ? round(($vMesAct - $vMesAnt) / $vMesAnt * 100, 1) : null;
                $varColor  = $varMes === null ? 'var(--text-muted)'
                           : ($varMes > 0 ? '#059669' : ($varMes < 0 ? '#dc2626' : 'var(--text-muted)'));
                $varIcon   = $varMes === null ? '' : ($varMes > 0 ? '↑' : ($varMes < 0 ? '↓' : '–'));
            @endphp
            <div class="stat-chip">
                <div class="stat-chip-val" style="color:var(--text-secondary);">
                    {{ number_format($vMesAnt, 0, '.', '.') }}
                </div>
                <div class="stat-chip-label">Mes anterior</div>
                @if($varMes !== null)
                    <div style="font-size:10.5px;font-weight:700;color:{{ $varColor }};margin-top:3px;">
                        {{ $varIcon }} {{ abs($varMes) }}%
                    </div>
                @endif
            </div>
        </div>

        {{-- Promedio Mensual --}}
        <div class="col-6 col-sm-3">
            <div class="stat-chip">
                <div class="stat-chip-val" style="color:var(--text-primary);">
                    {{ number_format($promedio, 1, ',', '.') }}
                </div>
                <div class="stat-chip-label">Prom. mensual</div>
                <div style="font-size:10px;color:var(--text-muted);margin-top:2px;">
                    {{ $mesesActivo }} mes{{ $mesesActivo !== 1 ? 'es' : '' }} activo{{ $mesesActivo !== 1 ? 's' : '' }}
                </div>
            </div>
        </div>

    </div>

    {{-- Barra inferior: Estado + Tendencia --}}
    <div style="display:flex;align-items:center;justify-content:space-between;
                padding:10px 14px;border-radius:10px;background:#f8fafc;gap:16px;flex-wrap:wrap;">

        {{-- Estado de rendimiento --}}
        <div style="display:flex;align-items:center;gap:8px;">
            <div style="width:8px;height:8px;border-radius:50%;background:{{ $estadoColor }};
                        box-shadow:0 0 0 3px {{ $estadoBg }};"></div>
            <span style="font-size:12.5px;font-weight:600;color:var(--text-primary);">
                {{ $estadoLabel }}
            </span>
        </div>

        {{-- Separador --}}
        <div style="width:1px;height:18px;background:#e2e8f0;"></div>

        {{-- Tendencia --}}
        <div style="display:flex;align-items:center;gap:6px;">
            <i class="fas {{ $tendenciaIcon }}"
               style="color:{{ $tendenciaColor }};font-size:12px;"></i>
            <span style="font-size:12.5px;color:var(--text-secondary);">
                Tendencia: <strong style="color:{{ $tendenciaColor }};">{{ $tendenciaLabel }}</strong>
            </span>
        </div>

        {{-- Separador --}}
        <div style="width:1px;height:18px;background:#e2e8f0;"></div>

        {{-- Link a rendimiento global --}}
        <a href="{{ route('articulos.rendimiento') }}"
           style="font-size:11.5px;color:var(--brand-primary);text-decoration:none;
                  font-weight:600;display:flex;align-items:center;gap:5px;margin-left:auto;">
            <i class="fas fa-chart-line" style="font-size:10px;"></i>
            Ver en Rendimiento Global
        </a>

    </div>

</div>
