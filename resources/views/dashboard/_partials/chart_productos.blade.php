{{--
    dashboard/_partials/chart_productos.blade.php
    Gráfico interactivo de Top N Productos más vendidos.
    Toggle entre: Unidades Vendidas | Monto Facturado | Ambos (combo)

    Variables esperadas:
      $topProductos   Collection
      $chartLabels    string (JSON)
      $chartUnidades  string (JSON)
      $chartMontos    string (JSON)
--}}

<div class="panel-card h-100">
    <div class="panel-card-header">
        <div>
            <h3 class="section-title mb-0">
                <i class="fas fa-trophy me-2" style="color: var(--brand-warning); font-size: 13px;"></i>
                Top {{ $topProductos->count() }} Productos más Vendidos
            </h3>
            <p class="section-subtitle mb-0">por unidades vendidas en el período</p>
        </div>

        {{-- Toggle tipo de dato --}}
        <div class="btn-group chart-toggle" role="group" aria-label="Tipo de gráfico">
            <button type="button" class="btn btn-sm btn-primary active" id="btnUnidades"
                    onclick="switchChart('unidades')">
                <i class="fas fa-cubes me-1"></i> Unidades
            </button>
            <button type="button" class="btn btn-sm btn-outline-secondary" id="btnMontos"
                    onclick="switchChart('montos')">
                <i class="fas fa-dollar-sign me-1"></i> Monto
            </button>
        </div>
    </div>

    <div class="panel-card-body">
        @if($topProductos->isEmpty())
            <div class="text-center py-5" style="color: var(--text-muted);">
                <i class="fas fa-box-open fa-2x mb-3 d-block opacity-25"></i>
                <p class="mb-0" style="font-size: 13px;">Sin datos de ventas en el período seleccionado.</p>
            </div>
        @else
            {{-- Canvas del gráfico --}}
            <div style="position: relative; height: 280px;">
                <canvas id="chartProductos"></canvas>
            </div>

            {{-- Leyenda personalizada (tabla simplificada bajo el gráfico) --}}
            <div class="mt-3" style="overflow-x: auto;">
                <table style="width: 100%; font-size: 12px; border-collapse: collapse;">
                    <thead>
                        <tr style="border-bottom: 1px solid #f1f5f9;">
                            <th style="padding: 6px 8px; color: var(--text-muted); font-weight: 600; text-transform: uppercase; font-size: 10px; letter-spacing: .4px;">
                                Ref.
                            </th>
                            <th style="padding: 6px 8px; color: var(--text-muted); font-weight: 600; text-transform: uppercase; font-size: 10px; letter-spacing: .4px;">
                                Producto
                            </th>
                            <th style="padding: 6px 8px; color: var(--text-muted); font-weight: 600; text-transform: uppercase; font-size: 10px; letter-spacing: .4px; text-align: right;">
                                Unidades
                            </th>
                            <th style="padding: 6px 8px; color: var(--text-muted); font-weight: 600; text-transform: uppercase; font-size: 10px; letter-spacing: .4px; text-align: right;">
                                Monto
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($topProductos as $idx => $prod)
                        <tr style="border-bottom: 1px solid #f8fafc;">
                            <td style="padding: 7px 8px;">
                                <span class="rank-badge {{ $idx < 3 ? 'rank-'.($idx+1) : 'rank-n' }}">
                                    {{ chr(65 + $idx) }}
                                </span>
                            </td>
                            <td style="padding: 7px 8px; color: var(--text-primary); max-width: 220px;">
                                <span style="display: block; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"
                                      title="{{ $prod['descripcion'] }}">
                                    {{ $prod['descripcion'] }}
                                </span>
                                @if(!empty($prod['marca']))
                                    <span style="font-size: 10px; color: var(--text-muted);">
                                        {{ $prod['marca'] }}
                                    </span>
                                @endif
                            </td>
                            <td style="padding: 7px 8px; text-align: right; font-family: var(--font-display); font-weight: 600; color: var(--text-primary);">
                                {{ number_format($prod['unidades'], 0, '.', config('app_client.locale.thousands_sep')) }}
                            </td>
                            <td style="padding: 7px 8px; text-align: right; color: var(--brand-success); font-weight: 600;">
                                {{ config('app_client.locale.currency_symbol') }}
                                {{ number_format($prod['monto'], 2, config('app_client.locale.decimal_sep'), config('app_client.locale.thousands_sep')) }}
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>

@push('scripts')
<script>
(function () {
    'use strict';

    const labels   = {!! $chartLabels   !!};
    const unidades = {!! $chartUnidades !!};
    const montos   = {!! $chartMontos   !!};

    if (!labels.length) return;

    // Paleta coherente con el sistema
    const palette = [
        'rgba(26,  86, 219, .82)',
        'rgba(5,  150, 105, .82)',
        'rgba(217, 119, 6,  .82)',
        'rgba(220, 38,  38, .82)',
        'rgba(124, 58, 237, .82)',
        'rgba(14, 165, 233, .82)',
        'rgba(236, 72, 153, .82)',
        'rgba(20, 184, 166, .82)',
        'rgba(245, 158, 11, .82)',
        'rgba(99, 102, 241, .82)',
    ];

    const ctx = document.getElementById('chartProductos');
    if (!ctx) return;

    const chartInstance = new Chart(ctx, {
        type: 'bar',
        data: {
            labels,
            datasets: [{
                label:           'Unidades Vendidas',
                data:            unidades,
                backgroundColor: palette,
                borderRadius:    6,
                borderSkipped:   false,
                barPercentage:   0.72,
            }]
        },
        options: {
            responsive:          true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: '#1e293b',
                    titleFont:       { family: "'Sora', sans-serif", weight: '600', size: 12 },
                    bodyFont:        { family: "'DM Sans', sans-serif", size: 12 },
                    padding:         12,
                    cornerRadius:    8,
                    callbacks: {
                        label(ctx) {
                            const v = ctx.parsed.y;
                            if (window._chartMode === 'montos') {
                                return ` {{ config('app_client.locale.currency_symbol') }} ${v.toLocaleString('es-VE', {minimumFractionDigits: 2})}`;
                            }
                            return ` ${v.toLocaleString('es-VE')} unidades`;
                        }
                    }
                }
            },
            scales: {
                x: {
                    grid: { display: false },
                    ticks: {
                        font: { size: 11 },
                        maxRotation: 0,
                        callback(_, i) {
                            return String.fromCharCode(65 + i); // A, B, C...
                        }
                    }
                },
                y: {
                    grid: {
                        color: 'rgba(0,0,0,.04)',
                        drawBorder: false,
                    },
                    ticks: {
                        font: { size: 11 },
                        callback(v) {
                            if (window._chartMode === 'montos') {
                                return '{{ config('app_client.locale.currency_symbol') }}' +
                                    (v >= 1000 ? (v/1000).toFixed(1) + 'k' : v);
                            }
                            return v >= 1000 ? (v/1000).toFixed(1) + 'k' : v;
                        }
                    }
                }
            }
        }
    });

    // ── Toggle entre unidades y montos ────────────────────────────────────
    window._chartMode = 'unidades';

    window.switchChart = function (mode) {
        window._chartMode = mode;
        const btnU = document.getElementById('btnUnidades');
        const btnM = document.getElementById('btnMontos');

        if (mode === 'unidades') {
            chartInstance.data.datasets[0].label = 'Unidades Vendidas';
            chartInstance.data.datasets[0].data  = unidades;
            btnU.classList.replace('btn-outline-secondary', 'btn-primary');
            btnU.classList.add('active');
            btnM.classList.replace('btn-primary', 'btn-outline-secondary');
            btnM.classList.remove('active');
        } else {
            chartInstance.data.datasets[0].label = 'Monto Facturado';
            chartInstance.data.datasets[0].data  = montos;
            btnM.classList.replace('btn-outline-secondary', 'btn-primary');
            btnM.classList.add('active');
            btnU.classList.replace('btn-primary', 'btn-outline-secondary');
            btnU.classList.remove('active');
        }

        chartInstance.update('active');
    };

})();
</script>
@endpush
