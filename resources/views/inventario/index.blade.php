{{-- inventario/index.blade.php --}}
@extends('layouts.app')
@section('title', 'Inventario & Auditoría')

@section('breadcrumb')
    <span class="current">Inventario & Auditoría</span>
@endsection

@section('hide_daterange', true)

@section('content')

<div class="d-flex align-items-start justify-content-between flex-wrap gap-3 mb-4">
    <div>
        <h1 class="font-display mb-1" style="font-size:22px; font-weight:800;">
            Inventario & Auditoría
        </h1>
        <p class="mb-0" style="font-size:13px; color:var(--text-muted);">
            Centro de control de stock y auditoría anti-fugas.
            Corte al <strong>{{ now()->format(config('app_client.locale.date_format')) }}</strong>
        </p>
    </div>
</div>

{{-- ── 4 tarjetas de acceso con KPI rápido ─────────────────────────────── --}}
<div class="row g-4">

    {{-- Stock Crítico --}}
    @can('inventario.stock-critico')
    <div class="col-12 col-md-6 col-xl-3 animate-in">
        <a href="{{ route('inventario.stock.critico') }}" style="text-decoration:none;">
        <div class="panel-card h-100" style="
            border-top: 4px solid #dc2626;
            transition: all .25s ease;
            cursor: pointer;
        " onmouseover="this.style.transform='translateY(-4px)';this.style.boxShadow='var(--card-shadow-hover)'"
           onmouseout="this.style.transform='';this.style.boxShadow=''">
            <div class="panel-card-body">
                <div style="display:flex; align-items:center; gap:12px; margin-bottom:16px;">
                    <div style="width:46px;height:46px;border-radius:12px;background:#fee2e2;
                                display:flex;align-items:center;justify-content:center;">
                        <i class="fas fa-triangle-exclamation" style="color:#dc2626;font-size:18px;"></i>
                    </div>
                    <div>
                        <div style="font-size:11px;font-weight:700;text-transform:uppercase;
                                    letter-spacing:.5px;color:var(--text-muted);">Stock Crítico</div>
                        <div style="font-size:11px;color:var(--text-muted);">artículos bajo mínimo</div>
                    </div>
                </div>

                <div style="display:flex; gap:16px; margin-bottom:14px;">
                    @foreach([['critico','#dc2626','Crítico'],['bajo','#d97706','Bajo'],['alerta','#0891b2','Alerta']] as [$k,$c,$l])
                    <div style="text-align:center;">
                        <div style="font-family:var(--font-display);font-size:24px;font-weight:800;color:{{$c}};">
                            {{ $nivelesStock[$k] }}
                        </div>
                        <div style="font-size:10px;color:var(--text-muted);font-weight:600;">{{ $l }}</div>
                    </div>
                    @endforeach
                </div>

                <div style="display:flex;align-items:center;justify-content:space-between;">
                    <span style="font-size:12px;color:var(--text-secondary);">
                        {{ $nivelesStock['total'] }} artículos en total
                    </span>
                    <span style="color:#dc2626;font-size:12px;font-weight:600;">
                        Ver detalle <i class="fas fa-arrow-right ms-1" style="font-size:10px;"></i>
                    </span>
                </div>
            </div>
        </div>
        </a>
    </div>
    @endcan

    {{-- Entradas vs Compras --}}
    @can('inventario.entradas.ver')
    <div class="col-12 col-md-6 col-xl-3 animate-in">
        <a href="{{ route('inventario.entradas', ['from' => $mesDesde, 'to' => $hoy]) }}" style="text-decoration:none;">
        <div class="panel-card h-100" style="border-top:4px solid #7c3aed;cursor:pointer;transition:all .25s"
             onmouseover="this.style.transform='translateY(-4px)';this.style.boxShadow='var(--card-shadow-hover)'"
             onmouseout="this.style.transform='';this.style.boxShadow=''">
            <div class="panel-card-body">
                <div style="display:flex;align-items:center;gap:12px;margin-bottom:16px;">
                    <div style="width:46px;height:46px;border-radius:12px;background:#ede9fe;
                                display:flex;align-items:center;justify-content:center;">
                        <i class="fas fa-boxes-stacked" style="color:#7c3aed;font-size:18px;"></i>
                    </div>
                    <div>
                        <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:var(--text-muted);">
                            Entradas vs Compras
                        </div>
                        <div style="font-size:11px;color:var(--text-muted);">mes en curso</div>
                    </div>
                </div>

                <div style="display:flex;gap:16px;margin-bottom:14px;">
                    @foreach([['ok','#059669','Completo'],['parcial','#d97706','Parcial'],['sin_entrada','#7c3aed','Sin entrada']] as [$k,$c,$l])
                    <div style="text-align:center;">
                        <div style="font-family:var(--font-display);font-size:24px;font-weight:800;color:{{$c}};">
                            {{ $nivelesEntradas[$k] }}
                        </div>
                        <div style="font-size:10px;color:var(--text-muted);font-weight:600;">{{ $l }}</div>
                    </div>
                    @endforeach
                </div>

                <div style="display:flex;align-items:center;justify-content:space-between;">
                    <span style="font-size:12px;color:var(--text-secondary);">
                        {{ $nivelesEntradas['total'] }} líneas de OC
                    </span>
                    <span style="color:#7c3aed;font-size:12px;font-weight:600;">
                        Ver detalle <i class="fas fa-arrow-right ms-1" style="font-size:10px;"></i>
                    </span>
                </div>
            </div>
        </div>
        </a>
    </div>
    @endcan

    {{-- Salidas No Comerciales --}}
    @can('inventario.salidas.auditar')
    <div class="col-12 col-md-6 col-xl-3 animate-in">
        <a href="{{ route('inventario.salidas', ['from' => $mesDesde, 'to' => $hoy]) }}" style="text-decoration:none;">
        <div class="panel-card h-100" style="border-top:4px solid #ea580c;cursor:pointer;transition:all .25s"
             onmouseover="this.style.transform='translateY(-4px)';this.style.boxShadow='var(--card-shadow-hover)'"
             onmouseout="this.style.transform='';this.style.boxShadow=''">
            <div class="panel-card-body">
                <div style="display:flex;align-items:center;gap:12px;margin-bottom:16px;">
                    <div style="width:46px;height:46px;border-radius:12px;background:#fff7ed;
                                display:flex;align-items:center;justify-content:center;">
                        <i class="fas fa-arrow-right-from-bracket" style="color:#ea580c;font-size:18px;"></i>
                    </div>
                    <div>
                        <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:var(--text-muted);">
                            Salidas No Comerciales
                        </div>
                        <div style="font-size:11px;color:var(--text-muted);">mes en curso</div>
                    </div>
                </div>

                <div style="margin-bottom:14px;">
                    <div style="font-family:var(--font-display);font-size:28px;font-weight:800;color:#ea580c;">
                        {{ config('app_client.locale.currency_symbol') }}
                        {{ number_format($costoTotalSalidas, 0, config('app_client.locale.decimal_sep'), config('app_client.locale.thousands_sep')) }}
                    </div>
                    <div style="font-size:11px;color:var(--text-muted);">costo estimado de pérdidas</div>
                </div>

                <div style="display:flex;align-items:center;justify-content:space-between;">
                    <span style="font-size:11px;padding:3px 8px;background:#fff7ed;color:#ea580c;
                                 border-radius:6px;font-weight:600;">
                        <i class="fas fa-shield-halved me-1"></i> Auditoría activa
                    </span>
                    <span style="color:#ea580c;font-size:12px;font-weight:600;">
                        Ver detalle <i class="fas fa-arrow-right ms-1" style="font-size:10px;"></i>
                    </span>
                </div>
            </div>
        </div>
        </a>
    </div>
    @endcan

    {{-- Reporte Consolidado --}}
    @can('inventario.reporte.consolidado.ver')
    <div class="col-12 col-md-6 col-xl-3 animate-in">
        <a href="{{ route('inventario.reporte', ['from' => $mesDesde, 'to' => $hoy]) }}" style="text-decoration:none;">
        <div class="panel-card h-100" style="border-top:4px solid #059669;cursor:pointer;transition:all .25s"
             onmouseover="this.style.transform='translateY(-4px)';this.style.boxShadow='var(--card-shadow-hover)'"
             onmouseout="this.style.transform='';this.style.boxShadow=''">
            <div class="panel-card-body">
                <div style="display:flex;align-items:center;gap:12px;margin-bottom:16px;">
                    <div style="width:46px;height:46px;border-radius:12px;background:#dcfce7;
                                display:flex;align-items:center;justify-content:center;">
                        <i class="fas fa-clipboard-list" style="color:#059669;font-size:18px;"></i>
                    </div>
                    <div>
                        <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:var(--text-muted);">
                            Reporte Consolidado
                        </div>
                        <div style="font-size:11px;color:var(--text-muted);">exportable a Excel</div>
                    </div>
                </div>

                <p style="font-size:12.5px;color:var(--text-secondary);margin-bottom:14px;line-height:1.5;">
                    Vista unificada de stock crítico, entradas y salidas con corte de fecha seleccionable.
                </p>

                <div style="display:flex;align-items:center;justify-content:space-between;">
                    <span style="font-size:11px;padding:3px 8px;background:#dcfce7;color:#059669;
                                 border-radius:6px;font-weight:600;">
                        <i class="fas fa-file-excel me-1"></i> Exportar disponible
                    </span>
                    <span style="color:#059669;font-size:12px;font-weight:600;">
                        Abrir <i class="fas fa-arrow-right ms-1" style="font-size:10px;"></i>
                    </span>
                </div>
            </div>
        </div>
        </a>
    </div>
    @endcan

</div>

@endsection
