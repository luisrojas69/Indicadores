{{--
    financiero/_partials/resumen_bono.blade.php

    Componente reutilizable del resumen ejecutivo del bono.
    Se usa en: financiero/bonos.blade.php
    También puede incluirse en el dashboard gerencial como widget.

    Variables esperadas (todas desde FinancieroController::bonos()):
      $resumenBono  — array calculado por MargenService::calcularResumenBono()
      $from, $to    — período
      $costField    — campo de costo activo
      $excluirIva   — bool
--}}

@php
    $currency = config('app_client.locale.currency_symbol');
    $dec      = config('app_client.locale.decimal_sep');
    $thou     = config('app_client.locale.thousands_sep');
    $fmt      = fn(float $v) => $currency . ' ' . number_format(abs($v), 2, $dec, $thou);
    $fmtSig   = fn(float $v) => ($v < 0 ? '-' : '') . $currency . ' ' . number_format(abs($v), 2, $dec, $thou);

    $gn        = $resumenBono['ganancia_neta'];
    $semGlobal = $resumenBono['semaforo_global'];
    $gnClass   = $gn >= 0 ? 'positivo' : 'negativo';

    $semColor  = match($semGlobal) {
        'verde'    => ['bg'   => '#4ade80', 'text' => '#14532d', 'badge_bg' => 'rgba(74,222,128,.15)', 'border' => 'rgba(74,222,128,.3)'],
        'amarillo' => ['bg'   => '#fbbf24', 'text' => '#78350f', 'badge_bg' => 'rgba(251,191,36,.15)',  'border' => 'rgba(251,191,36,.3)'],
        default    => ['bg'   => '#f87171', 'text' => '#7f1d1d', 'badge_bg' => 'rgba(248,113,113,.15)','border' => 'rgba(248,113,113,.3)'],
    };

    $sem = $resumenBono['semaforo_conteo'];
    $total = $resumenBono['total_articulos'] ?: 1;
@endphp

<div style="
    background: linear-gradient(135deg, #0f172a 0%, #1e3a5f 100%);
    border-radius: 16px;
    padding: 28px 32px;
    color: #fff;
    position: relative;
    overflow: hidden;
">
    {{-- Decoración de fondo --}}
    <div style="position:absolute;top:-60px;right:-60px;width:200px;height:200px;
                background:radial-gradient(circle,rgba(26,86,219,.35) 0%,transparent 70%);
                border-radius:50%;pointer-events:none;"></div>
    <div style="position:absolute;bottom:-40px;left:25%;width:280px;height:120px;
                background:radial-gradient(ellipse,rgba(5,150,105,.2) 0%,transparent 70%);
                pointer-events:none;"></div>

    <div class="row g-4 align-items-center position-relative">

        {{-- Ganancia neta principal --}}
        <div class="col-12 col-md-5">
            <div style="font-size:11px;font-weight:700;text-transform:uppercase;
                        letter-spacing:1.5px;color:rgba(255,255,255,.45);margin-bottom:8px;">
                Ganancia Neta del Período
            </div>

            <div style="font-family:var(--font-display);font-size:42px;font-weight:900;
                        line-height:1;letter-spacing:-2px;
                        color:{{ $gn >= 0 ? '#4ade80' : '#f87171' }};">
                {{ $fmtSig($gn) }}
            </div>

            {{-- Badge de margen --}}
            <div style="
                display:inline-flex;align-items:center;gap:7px;
                padding:5px 14px;border-radius:24px;margin-top:10px;
                font-family:var(--font-display);font-size:14px;font-weight:700;
                background:{{ $semColor['badge_bg'] }};
                color:{{ $semColor['bg'] }};
                border:1px solid {{ $semColor['border'] }};
            ">
                <i class="fas fa-{{ $semGlobal === 'verde' ? 'circle-check' : ($semGlobal === 'amarillo' ? 'circle-half-stroke' : 'triangle-exclamation') }}"></i>
                {{ number_format($resumenBono['margen_neto_pct'], 2, $dec, '') }}% Margen Neto
            </div>

            {{-- Ecuación --}}
            <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;
                        margin-top:16px;font-size:12px;color:rgba(255,255,255,.5);">
                <span>
                    <div style="font-size:9px;opacity:.5;margin-bottom:2px;">FACTURADO</div>
                    <strong style="color:#fff;font-size:13px;">{{ $fmt($resumenBono['total_base']) }}</strong>
                </span>
                <span style="font-size:18px;opacity:.3;">−</span>
                <span>
                    <div style="font-size:9px;opacity:.5;margin-bottom:2px;">COSTO</div>
                    <strong style="color:#fff;font-size:13px;">{{ $fmt($resumenBono['costo_total']) }}</strong>
                </span>
                <span style="font-size:18px;opacity:.3;">=</span>
                <strong style="font-family:var(--font-display);font-size:15px;
                               color:{{ $gn >= 0 ? '#4ade80' : '#f87171' }};">
                    {{ $fmtSig($gn) }}
                </strong>
            </div>

            @if($resumenBono['iva_excluido'])
            <div style="margin-top:10px;font-size:11px;color:rgba(255,255,255,.35);">
                <i class="fas fa-info-circle me-1"></i>
                IVA separado: {{ $fmt($resumenBono['iva_monto']) }}
                ({{ $resumenBono['iva_rate'] }}%)
            </div>
            @endif
        </div>

        {{-- Breakdown derecho --}}
        <div class="col-12 col-md-7">
            <div class="row g-3">

                {{-- Total Facturado --}}
                <div class="col-6">
                    <div style="background:rgba(255,255,255,.06);border:1px solid rgba(255,255,255,.1);
                                border-radius:12px;padding:14px 16px;">
                        <div style="font-size:10px;color:rgba(255,255,255,.4);text-transform:uppercase;
                                    letter-spacing:.7px;font-weight:600;margin-bottom:4px;">
                            Total Facturado
                        </div>
                        <div style="font-family:var(--font-display);font-size:18px;
                                    font-weight:800;color:#fff;">
                            {{ $fmt($resumenBono['total_facturado']) }}
                        </div>
                        @if($resumenBono['iva_excluido'])
                        <div style="font-size:10px;color:rgba(255,255,255,.3);margin-top:3px;">
                            Base s/IVA: {{ $fmt($resumenBono['total_base']) }}
                        </div>
                        @endif
                    </div>
                </div>

                {{-- Costo Total --}}
                <div class="col-6">
                    <div style="background:rgba(248,113,113,.08);border:1px solid rgba(248,113,113,.2);
                                border-radius:12px;padding:14px 16px;">
                        <div style="font-size:10px;color:rgba(255,255,255,.4);text-transform:uppercase;
                                    letter-spacing:.7px;font-weight:600;margin-bottom:4px;">
                            Costo Total
                        </div>
                        <div style="font-family:var(--font-display);font-size:18px;
                                    font-weight:800;color:#fca5a5;">
                            {{ $fmt($resumenBono['costo_total']) }}
                        </div>
                        <div style="font-size:10px;color:rgba(255,255,255,.3);margin-top:3px;">
                            Campo: {{ $costField }}
                        </div>
                    </div>
                </div>

                {{-- Distribución semáforo --}}
                <div class="col-12">
                    <div style="background:rgba(255,255,255,.05);border:1px solid rgba(255,255,255,.08);
                                border-radius:12px;padding:12px 16px;">
                        <div style="font-size:10px;color:rgba(255,255,255,.4);text-transform:uppercase;
                                    letter-spacing:.7px;font-weight:600;margin-bottom:10px;">
                            Distribución — {{ $resumenBono['total_articulos'] }} artículos
                        </div>
                        <div style="display:flex;gap:20px;flex-wrap:wrap;">
                            @foreach([
                                ['verde',    '#4ade80', 'Alto'],
                                ['amarillo', '#fbbf24', 'Medio'],
                                ['rojo',     '#f87171', 'Bajo'],
                                ['negativos','#c084fc', 'Negativo'],
                            ] as [$k,$c,$l])
                            <div style="text-align:center;">
                                <div style="font-family:var(--font-display);font-size:22px;
                                            font-weight:800;color:{{$c}};">
                                    {{ $sem[$k] }}
                                </div>
                                <div style="font-size:10px;color:rgba(255,255,255,.4);">
                                    {{ $l }}<br>
                                    <span style="color:{{$c}};opacity:.7;">
                                        {{ $total > 0 ? number_format($sem[$k]/$total*100, 0) : 0 }}%
                                    </span>
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </div>
                </div>

            </div>
        </div>

    </div>
</div>
