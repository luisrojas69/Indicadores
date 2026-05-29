{{--
    dashboard/_partials/kpi_card.blade.php
    Componente reutilizable de KPI card.

    Variables esperadas:
      $label       string  — Etiqueta de la métrica
      $value       string  — Valor ya formateado
      $prefix      string  — Símbolo antes del valor (ej: '$')
      $icon        string  — Clase Font Awesome (ej: 'fas fa-chart-bar')
      $accentClass string  — Clase CSS adicional: '' | 'accent-success' | 'accent-warning' | 'accent-danger' | 'accent-info'
      $delta       float|null — Variación porcentual. null = no mostrar badge
      $deltaLabel  string  — Texto descriptivo del delta (ej: 'vs mes anterior')
      $deltaType   string  — '' = porcentaje comparativo | 'pct_cobrado' = porcentaje de cobranza
      $period      string  — Texto de período (ej: 'Mayo 2026')
--}}

@php
    $accentClass = $accentClass ?? '';
    $delta       = $delta       ?? null;
    $deltaType   = $deltaType   ?? '';
    $prefix      = $prefix      ?? '';

    // Determinar clase del badge de delta
    $deltaClass = 'flat';
    $deltaIcon  = 'fa-minus';
    if ($delta !== null && $deltaType !== 'pct_cobrado') {
        if ($delta > 0)     { $deltaClass = 'up';   $deltaIcon = 'fa-arrow-trend-up';   }
        elseif ($delta < 0) { $deltaClass = 'down'; $deltaIcon = 'fa-arrow-trend-down'; }
    } elseif ($deltaType === 'pct_cobrado') {
        // Para % cobrado: verde si >70%, amarillo si 40-70%, rojo si <40%
        if ($delta >= 70)     { $deltaClass = 'up';   $deltaIcon = 'fa-check'; }
        elseif ($delta >= 40) { $deltaClass = 'flat'; $deltaIcon = 'fa-circle-half-stroke'; }
        else                  { $deltaClass = 'down'; $deltaIcon = 'fa-triangle-exclamation'; }
    }
@endphp

<div class="kpi-card {{ $accentClass }}">
    <div class="d-flex align-items-start justify-content-between mb-3">
        <div class="kpi-icon-wrap">
            <i class="{{ $icon }}"></i>
        </div>
        <div class="text-end" style="flex: 1; padding-left: 12px;">
            <div class="kpi-label">{{ $label }}</div>
            <div class="kpi-value">
                @if($prefix)
                    <span style="font-size: 16px; font-weight: 500; opacity: .6;">{{ $prefix }}</span>
                @endif
                {{ $value }}
            </div>
        </div>
    </div>

    <div class="d-flex align-items-center justify-content-between">
        @if($delta !== null)
            <span class="kpi-delta {{ $deltaClass }}">
                <i class="fas {{ $deltaIcon }}" style="font-size: 10px;"></i>
                @if($deltaType === 'pct_cobrado')
                    {{ number_format(abs($delta), 1) }}% {{ $deltaLabel }}
                @else
                    {{ $delta >= 0 ? '+' : '' }}{{ number_format($delta, 1) }}% {{ $deltaLabel }}
                @endif
            </span>
        @else
            <span class="kpi-delta flat" style="opacity: .6;">
                <i class="fas fa-calendar-alt" style="font-size: 10px;"></i>
                {{ $deltaLabel ?? $period }}
            </span>
        @endif

        <span class="kpi-period">{{ $period }}</span>
    </div>
</div>
