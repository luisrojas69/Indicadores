{{--
    articulos/_partials/ficha_precios.blade.php
    Grid de precios y margen de rentabilidad en tiempo real.
    Variables heredadas de articulos/show.blade.php
--}}

<div class="info-block animate-in">
    <div class="info-block-label">
        <i class="fas fa-tag" style="color:var(--brand-primary);font-size:11px;"></i>
        Precios del Producto
        @can('financiero.config.costo.editar')
        <a href="{{ route('articulos.show', $articulo['codigo']) }}"
           style="margin-left:auto;font-size:11px;color:var(--brand-primary);
                  text-decoration:none;font-weight:600;">
            <i class="fas fa-sync-alt me-1" style="font-size:9px;"></i> Actualizar
        </a>
        @endcan
    </div>

    {{-- Grid precios 1-4 --}}
    <div class="row g-2 mb-3">
        @foreach([1,2,3,4] as $n)
        @php $pv = (float)($articulo['precios']["venta{$n}"] ?? 0); @endphp
        <div class="col-6 col-sm-3">
            <div class="precio-item {{ $pv <= 0 ? 'opacity-50' : '' }}">
                <div class="precio-label">Precio Venta {{ $n }}</div>
                <div class="precio-value" style="color:{{ $n === 1 ? 'var(--brand-primary)' : 'var(--text-primary)' }};">
                    @if($pv > 0)
                        <span style="font-size:11px;font-weight:400;opacity:.6;">{{ config('app_client.locale.currency_symbol') }}</span>
                        {{ number_format($pv, 2, config('app_client.locale.decimal_sep'), config('app_client.locale.thousands_sep')) }}
                    @else
                        <span style="font-size:13px;color:var(--text-muted);">—</span>
                    @endif
                </div>
            </div>
        </div>
        @endforeach
    </div>

    {{-- Precio de compra + Margen --}}
    @can('financiero.margenes.ver')
    <div style="display:flex;gap:12px;flex-wrap:wrap;">

        {{-- Precio de compra --}}
        <div class="precio-item compra" style="flex:1;min-width:120px;">
            <div class="precio-label" style="color:#c2410c;">
                <i class="fas fa-cart-shopping me-1" style="font-size:9px;"></i> Precio de Compra
            </div>
            <div class="precio-value" style="color:#ea580c;">
                {{ config('app_client.locale.currency_symbol') }}
                {{ number_format($costoActivo, 2, config('app_client.locale.decimal_sep'), config('app_client.locale.thousands_sep')) }}
            </div>
            <div style="font-size:10px;color:#c2410c;margin-top:3px;">
                {{ $costField }}
            </div>
        </div>

        {{-- Margen de rentabilidad --}}
        <div class="margen-hero {{ $margenClass }}" style="flex:2;min-width:180px;">
            <div>
                <div style="font-size:10px;font-weight:700;text-transform:uppercase;
                            letter-spacing:.5px;
                            color:{{ $margenClass === '' ? '#15803d' : ($margenClass === 'warning' ? '#92400e' : '#b91c1c') }};
                            margin-bottom:4px;">
                    <i class="fas fa-percent me-1" style="font-size:9px;"></i>
                    Margen de Rentabilidad
                </div>
                <div style="font-family:var(--font-display);font-size:28px;font-weight:900;
                            color:{{ $margenClass === '' ? '#15803d' : ($margenClass === 'warning' ? '#92400e' : '#b91c1c') }};
                            line-height:1;">
                    {{ number_format($margenPct, 2, config('app_client.locale.decimal_sep'), '') }}%
                </div>
            </div>
            <div style="text-align:right;">
                <div style="font-size:10px;color:var(--text-muted);margin-bottom:3px;">Margen bruto</div>
                <div style="font-family:var(--font-display);font-size:16px;font-weight:800;
                            color:{{ $margenClass === '' ? '#15803d' : ($margenClass === 'warning' ? '#92400e' : '#b91c1c') }};">
                    {{ config('app_client.locale.currency_symbol') }}
                    {{ number_format($precioVenta - $costoActivo, 2, config('app_client.locale.decimal_sep'), config('app_client.locale.thousands_sep')) }}
                </div>
                <div style="font-size:10px;color:var(--text-muted);">
                    P1 − {{ $costField }}
                </div>
            </div>
        </div>

    </div>
    @endcan

</div>
