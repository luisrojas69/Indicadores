{{--
    dashboard/_partials/cxc_cards.blade.php
    5 tarjetas de antigüedad de cartera (CxC Aging).
    Variable esperada: $cxc (array con keys del ErpConnectionInterface)
--}}

@php
    $currency = config('app_client.locale.currency_symbol', '$');
    $dec      = config('app_client.locale.decimal_sep',    ',');
    $thou     = config('app_client.locale.thousands_sep',  '.');
    $fmt = fn(float $v) => $currency . ' ' . number_format($v, 2, $dec, $thou);

    $cards = [
        [
            'class'  => 'total',
            'label'  => 'Total CxC',
            'value'  => $fmt($cxc['total_cxc']     ?? 0),
            'icon'   => 'fas fa-layer-group',
            'tip'    => 'Saldo pendiente total de todas las facturas activas',
        ],
        [
            'class'  => 'por-vencer',
            'label'  => 'Por Vencer',
            'value'  => $fmt($cxc['por_vencer']     ?? 0),
            'icon'   => 'fas fa-hourglass-start',
            'tip'    => 'Facturas cuya fecha de vencimiento aún no ha llegado',
        ],
        [
            'class'  => 'v0-15',
            'label'  => 'Vencidas 0–15 días',
            'value'  => $fmt($cxc['vencidas_0_15']  ?? 0),
            'icon'   => 'fas fa-triangle-exclamation',
            'tip'    => 'Facturas vencidas hace 1 a 15 días',
        ],
        [
            'class'  => 'v16-30',
            'label'  => 'Vencidas 16–30 días',
            'value'  => $fmt($cxc['vencidas_16_30'] ?? 0),
            'icon'   => 'fas fa-circle-exclamation',
            'tip'    => 'Facturas vencidas hace 16 a 30 días — requieren seguimiento',
        ],
        [
            'class'  => 'v31-mas',
            'label'  => 'Vencidas 31+ días',
            'value'  => $fmt($cxc['vencidas_31_mas'] ?? 0),
            'icon'   => 'fas fa-skull-crossbones',
            'tip'    => 'Cartera crítica — más de 30 días sin cobrar',
        ],
    ];
@endphp

{{-- 5 cards en una fila responsiva: 2 col en móvil, 3 en tablet, 5 en desktop --}}
@foreach($cards as $card)
    <div class="col-6 col-md-4 col-xl animate-in" title="{{ $card['tip'] }}">
        <div class="cxc-card {{ $card['class'] }}">
            <div class="d-flex align-items-center justify-content-between mb-2">
                <i class="{{ $card['icon'] }}"
                   style="font-size: 15px; color: var(--accent, var(--brand-primary));"></i>
                @if(($cxc[$card['class'] === 'v31-mas' ? 'vencidas_31_mas' : ''] ?? 0) > 0 && $card['class'] === 'v31-mas')
                    <span class="badge" style="background: var(--brand-danger); font-size: 9px; border-radius: 5px;">
                        CRÍTICO
                    </span>
                @endif
            </div>
            <div class="cxc-amount">{{ $card['value'] }}</div>
            <div class="cxc-label">{{ $card['label'] }}</div>
        </div>
    </div>
@endforeach
