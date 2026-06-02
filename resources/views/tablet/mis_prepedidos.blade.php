{{--
    tablet/mis_prepedidos.blade.php
    Historial de pre-pedidos del vendedor autenticado.
    Estados mostrados: pendiente_caja, procesado, cancelado (NO borradores).
    Permite cancelar los que estén pendientes en caja.
--}}
@extends('layouts.app')
@section('title', 'Mis Pre-Pedidos')
@section('hide_daterange', true)

@push('styles')
<style>
/* ── Tabla ────────────────────────────────────── */
.pp-table { width: 100%; border-collapse: separate; border-spacing: 0; }
.pp-table thead th {
    background: #0f172a; color: rgba(255,255,255,.75);
    font-size: 10.5px; font-weight: 700; letter-spacing: .5px;
    text-transform: uppercase; padding: 11px 14px;
    border-bottom: 2px solid #1a56db;
    white-space: nowrap; position: sticky; top: 0; z-index: 2;
}
.pp-table tbody td {
    padding: 12px 14px; font-size: 13px;
    border-bottom: 1px solid #f1f5f9; vertical-align: middle;
}
.pp-table tbody tr:hover td { background: #f8fafc; cursor: pointer; }

/* ── Badge de estado ─────────────────────────── */
.est-badge {
    display: inline-flex; align-items: center; gap: 5px;
    padding: 4px 10px; border-radius: 20px;
    font-size: 11px; font-weight: 700; white-space: nowrap;
}

/* ── Fila de detalle ─────────────────────────── */
.pp-det-row { display: none; }
.pp-det-row.open { display: table-row; }
.pp-det-cell {
    padding: 0 14px 14px !important;
    background: #f8fafc !important;
    border-bottom: 2px solid #e2e8f0 !important;
}
.pp-det-inner {
    background: #fff; border-radius: 12px;
    border: 1px solid #e2e8f0; padding: 16px;
}

/* ── Ítem del detalle ────────────────────────── */
.det-item {
    display: flex; align-items: center; gap: 10px;
    padding: 8px 0; border-bottom: 1px solid #f8fafc; font-size: 12.5px;
}
.det-item:last-child { border-bottom: none; }
.det-ico {
    width: 32px; height: 32px; border-radius: 8px;
    background: #f1f5f9;
    display: flex; align-items: center; justify-content: center;
    font-size: 16px; flex-shrink: 0;
}
.det-desc { flex: 1; font-weight: 600; color: #0f172a; }
.det-meta { font-size: 11px; color: #94a3b8; display: block; }
.det-qty  { color: #64748b; font-size: 12px; white-space: nowrap; }
.det-sub  { font-weight: 800; color: #1a56db; min-width: 80px; text-align: right; white-space: nowrap; }

/* ── KPIs ────────────────────────────────────── */
.mis-kpis { display: flex; gap: 12px; flex-wrap: wrap; margin-bottom: 20px; }
.mk {
    background: #fff; border-radius: 12px;
    padding: 14px 18px; border: 1px solid #e2e8f0;
    display: flex; align-items: center; gap: 12px;
    flex: 1; min-width: 140px;
}
.mk-ico {
    width: 38px; height: 38px; border-radius: 10px;
    display: flex; align-items: center; justify-content: center;
    font-size: 16px; flex-shrink: 0;
}
.mk-val {
    font-family: var(--font-display,'Sora',sans-serif);
    font-size: 22px; font-weight: 800; line-height: 1;
}
.mk-lbl { font-size: 11px; color: #94a3b8; margin-top: 2px; }

/* ── Botón cancelar ──────────────────────────── */
.btn-cncl {
    padding: 5px 12px; border-radius: 8px;
    border: 1.5px solid #fca5a5; background: #fff;
    color: #dc2626; font-size: 12px; font-weight: 700;
    cursor: pointer; transition: all .12s; white-space: nowrap;
}
.btn-cncl:hover { background: #fee2e2; }

/* ── Filtros ─────────────────────────────────── */
.est-filter {
    display: inline-flex; align-items: center; gap: 5px;
    padding: 6px 14px; border-radius: 20px; border: 1.5px solid;
    font-size: 12px; font-weight: 600; cursor: pointer;
    text-decoration: none; transition: all .12s; white-space: nowrap;
}

/* ── Chevron ─────────────────────────────────── */
.chv { transition: transform .2s; display: inline-block; color: #94a3b8; }
.chv.open { transform: rotate(180deg); }

/* ── Empty ───────────────────────────────────── */
.pp-empty { text-align: center; padding: 70px 20px; color: #94a3b8; }

/* ── Toast ───────────────────────────────────── */
@keyframes toastUp {
    from { opacity:0; transform:translateX(-50%) translateY(10px); }
    to   { opacity:1; transform:translateX(-50%) translateY(0); }
}
</style>
@endpush

@section('breadcrumb')
    <a href="{{ route('tablet.catalogo') }}" style="color:var(--text-muted);text-decoration:none;">Tablet</a>
    <span style="color:#cbd5e1;margin:0 4px;">/</span>
    <span class="current">Mis Pre-Pedidos</span>
@endsection

@section('content')
@php
    $cur   = config('app_client.locale.currency_symbol', '$');
    $fmtM  = fn(float $v) => $cur.' '.number_format($v, 2, ',', '.');
    $icons = config('tablet.categoria_icons', []);

    $cPend  = $prePedidos->where('estado', 'pendiente_caja')->count();
    $cProc  = $prePedidos->where('estado', 'procesado')->count();
    $cCanc  = $prePedidos->where('estado', 'cancelado')->count();
    $monto  = $prePedidos->where('estado', 'procesado')->sum('total');

    $filtro = request('estado', 'todos');
@endphp

{{-- Header --}}
<div class="d-flex align-items-start justify-content-between flex-wrap gap-3 mb-4">
    <div>
        <h1 class="font-display mb-1" style="font-size:22px;font-weight:800;">
            Mis Pre-Pedidos
        </h1>
        <p class="mb-0" style="font-size:13px;color:#94a3b8;">
            Historial de pre-pedidos generados desde el catálogo tablet.
        </p>
    </div>
    <a href="{{ route('tablet.catalogo') }}"
       class="btn btn-primary btn-sm d-flex align-items-center gap-2"
       style="border-radius:9px;font-size:12.5px;">
        <i class="fas fa-plus"></i> Nuevo Pre-Pedido
    </a>
</div>

{{-- KPIs --}}
<div class="mis-kpis">
    <div class="mk">
        <div class="mk-ico" style="background:#fef3c7;">
            <i class="fas fa-clock" style="color:#d97706;"></i>
        </div>
        <div>
            <div class="mk-val" style="color:#d97706;">{{ $cPend }}</div>
            <div class="mk-lbl">En caja</div>
        </div>
    </div>
    <div class="mk">
        <div class="mk-ico" style="background:#dcfce7;">
            <i class="fas fa-check-circle" style="color:#059669;"></i>
        </div>
        <div>
            <div class="mk-val" style="color:#059669;">{{ $cProc }}</div>
            <div class="mk-lbl">Procesados</div>
        </div>
    </div>
    <div class="mk">
        <div class="mk-ico" style="background:#fee2e2;">
            <i class="fas fa-times-circle" style="color:#dc2626;"></i>
        </div>
        <div>
            <div class="mk-val" style="color:#dc2626;">{{ $cCanc }}</div>
            <div class="mk-lbl">Cancelados</div>
        </div>
    </div>
    <div class="mk">
        <div class="mk-ico" style="background:#eff6ff;">
            <i class="fas fa-chart-bar" style="color:#1a56db;"></i>
        </div>
        <div>
            <div class="mk-val" style="color:#1a56db;font-size:16px;">
                {{ $cur }} {{ number_format($monto, 0, ',', '.') }}
            </div>
            <div class="mk-lbl">Total procesado</div>
        </div>
    </div>
</div>

{{-- Panel tabla --}}
<div class="panel-card animate-in">

    <div class="panel-card-header flex-wrap gap-3">
        <div>
            <h3 class="section-title mb-0">Historial</h3>
            <p class="section-subtitle mb-0">{{ $prePedidos->total() }} registros</p>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            @foreach([
                ['todos',          'Todos',      '#64748b','#f8fafc'],
                ['pendiente_caja', 'En Caja',    '#d97706','#fef3c7'],
                ['procesado',      'Procesados', '#059669','#dcfce7'],
                ['cancelado',      'Cancelados', '#dc2626','#fee2e2'],
            ] as [$k,$l,$c,$bg])
            <a href="{{ request()->fullUrlWithQuery(['estado'=>$k]) }}"
               class="est-filter"
               style="border-color:{{ $filtro===$k?$c:'#e2e8f0' }};
                      background:{{ $filtro===$k?$bg:'#f8fafc' }};
                      color:{{ $filtro===$k?$c:'#64748b' }};">
                {{ $l }}
            </a>
            @endforeach
        </div>
    </div>

    @if($prePedidos->isEmpty())
    <div class="pp-empty">
        <i class="fas fa-clipboard-list fa-2x d-block mb-3 opacity-25"></i>
        <p style="font-size:15px;font-weight:700;">Sin pre-pedidos registrados</p>
        <p style="font-size:13px;">Crea uno desde el catálogo tablet.</p>
    </div>
    @else

    <div style="overflow-x:auto;">
        <table class="pp-table">
            <thead>
                <tr>
                    <th style="width:32px;"></th>
                    <th>Referencia</th>
                    <th>Cliente</th>
                    <th style="text-align:center;">Artículos</th>
                    <th style="text-align:right;">Total</th>
                    <th style="text-align:center;">Estado</th>
                    <th style="text-align:right;">Fecha</th>
                    <th style="text-align:center;"></th>
                </tr>
            </thead>
            <tbody>
                @foreach($prePedidos as $pp)
                @php $info = $pp->estadoInfo(); @endphp

                {{-- Fila principal --}}
                <tr onclick="toggleDet('{{ $pp->id }}')">
                    <td style="text-align:center;padding-left:14px;">
                        <span class="chv" id="chv-{{ $pp->id }}">
                            <i class="fas fa-chevron-down" style="font-size:11px;"></i>
                        </span>
                    </td>
                    <td>
                        <span style="font-family:var(--font-display,'Sora',sans-serif);
                                     font-size:13px;font-weight:800;color:#1a56db;">
                            {{ $pp->numero_referencia }}
                        </span>
                    </td>
                    <td>
                        @if($pp->cliente_nombre)
                            <span style="font-weight:600;color:#0f172a;">
                                {{ $pp->cliente_nombre }}
                            </span>
                            @if($pp->cliente_telefono)
                            <span style="display:block;font-size:11px;color:#94a3b8;">
                                {{ $pp->cliente_telefono }}
                            </span>
                            @endif
                        @else
                            <span style="color:#94a3b8;font-size:12px;">—</span>
                        @endif
                    </td>
                    <td style="text-align:center;">
                        <span style="font-family:var(--font-display,'Sora',sans-serif);
                                     font-size:16px;font-weight:800;">
                            {{ $pp->total_items }}
                        </span>
                    </td>
                    <td style="text-align:right;">
                        <span style="font-family:var(--font-display,'Sora',sans-serif);
                                     font-size:14px;font-weight:800;">
                            {{ $fmtM((float)$pp->total) }}
                        </span>
                    </td>
                    <td style="text-align:center;">
                        <span class="est-badge"
                              style="background:{{ $info['bg'] }};color:{{ $info['color'] }};">
                            <i class="fas {{ $info['icon'] }}" style="font-size:10px;"></i>
                            {{ $info['label'] }}
                        </span>
                    </td>
                    <td style="text-align:right;font-size:12px;color:#64748b;white-space:nowrap;">
                        {{ $pp->created_at->format(config('app_client.locale.date_format','d/m/Y')) }}
                        <span style="display:block;font-size:10.5px;color:#94a3b8;">
                            {{ $pp->created_at->format('H:i') }}
                        </span>
                    </td>
                    <td style="text-align:center;" onclick="event.stopPropagation()">
                        @if($pp->esPendienteCaja())
                        <button class="btn-cncl"
                                onclick="cancelarPP({{ $pp->id }},'{{ $pp->numero_referencia }}')">
                            <i class="fas fa-times me-1"></i>Cancelar
                        </button>
                        @else
                            <span style="font-size:11px;color:#94a3b8;">—</span>
                        @endif
                    </td>
                </tr>

                {{-- Detalle expandible --}}
                <tr class="pp-det-row" id="det-{{ $pp->id }}">
                    <td colspan="8" class="pp-det-cell">
                        <div class="pp-det-inner">
                            <p style="font-size:10.5px;font-weight:700;text-transform:uppercase;
                                      letter-spacing:.5px;color:#94a3b8;margin-bottom:10px;">
                                <i class="fas fa-list me-1"></i>
                                Artículos — {{ $pp->numero_referencia }}
                            </p>

                            @foreach($pp->items as $item)
                            @php $emj = $icons[$item->articulo_categoria??''] ?? ($icons['default']??'📦'); @endphp
                            <div class="det-item">
                                <div class="det-ico">{{ $emj }}</div>
                                <div style="flex:1;min-width:0;">
                                    <div class="det-desc"
                                         title="{{ $item->articulo_descripcion }}">
                                        {{ mb_strimwidth($item->articulo_descripcion,0,42,'…') }}
                                    </div>
                                    <span class="det-meta">
                                        {{ $item->articulo_linea }}
                                        @if($item->articulo_modelo) · {{ $item->articulo_modelo }} @endif
                                        · Precio {{ $item->precio_nivel }}
                                    </span>
                                </div>
                                <span class="det-qty">
                                    {{ number_format($item->cantidad,0) }} uds
                                    × {{ $fmtM((float)$item->precio_unitario) }}
                                </span>
                                <span class="det-sub">{{ $fmtM((float)$item->subtotal) }}</span>
                            </div>
                            @endforeach

                            <div style="display:flex;justify-content:flex-end;
                                        padding-top:10px;margin-top:6px;
                                        border-top:2px solid #f1f5f9;">
                                <div style="text-align:right;">
                                    <span style="font-size:11px;color:#94a3b8;">Total</span>
                                    <div style="font-family:var(--font-display,'Sora',sans-serif);
                                                font-size:20px;font-weight:900;color:#0f172a;">
                                        {{ $fmtM((float)$pp->total) }}
                                    </div>
                                </div>
                            </div>

                            @if($pp->notas)
                            <div style="margin-top:12px;padding:10px 12px;
                                        background:#fef9c3;border-radius:9px;
                                        font-size:12.5px;color:#713f12;">
                                <i class="fas fa-sticky-note me-1"></i>{{ $pp->notas }}
                            </div>
                            @endif

                        </div>
                    </td>
                </tr>

                @endforeach
            </tbody>
        </table>
    </div>

    {{-- Paginación simple --}}
    @if($prePedidos->hasPages())
    <div class="d-flex justify-content-center align-items-center gap-2 py-3"
         style="border-top:1px solid #f1f5f9;">
        @if(!$prePedidos->onFirstPage())
        <a href="{{ $prePedidos->previousPageUrl() }}"
           style="width:34px;height:34px;border-radius:8px;border:1.5px solid #e2e8f0;
                  display:flex;align-items:center;justify-content:center;
                  color:#64748b;text-decoration:none;transition:all .12s;">
            <i class="fas fa-chevron-left" style="font-size:11px;"></i>
        </a>
        @endif
        <span style="font-size:12.5px;color:#64748b;">
            Página {{ $prePedidos->currentPage() }} de {{ $prePedidos->lastPage() }}
        </span>
        @if($prePedidos->hasMorePages())
        <a href="{{ $prePedidos->nextPageUrl() }}"
           style="width:34px;height:34px;border-radius:8px;border:1.5px solid #e2e8f0;
                  display:flex;align-items:center;justify-content:center;
                  color:#64748b;text-decoration:none;transition:all .12s;">
            <i class="fas fa-chevron-right" style="font-size:11px;"></i>
        </a>
        @endif
    </div>
    @endif

    @endif
</div>

@push('scripts')
<script>
(function(){
'use strict';
const CSRF = document.querySelector('meta[name=csrf-token]')?.content ?? '';

/* ── Toggle detalle ── */
window.toggleDet = (id) => {
    const row  = document.getElementById(`det-${id}`);
    const chv  = document.getElementById(`chv-${id}`);
    const open = row.classList.contains('open');

    document.querySelectorAll('.pp-det-row').forEach(r => r.classList.remove('open'));
    document.querySelectorAll('.chv').forEach(c => c.classList.remove('open'));

    if (!open) {
        row.classList.add('open');
        chv.classList.add('open');
    }
};

/* ── Cancelar ── */
window.cancelarPP = async (id, ref) => {
    if (!confirm(`¿Cancelar el pre-pedido #${ref}?`)) return;

    const r = await fetch(`/caja/prepedido/${id}/cancelar`, {
        method:  'POST',
        headers: { 'Content-Type':'application/json', 'X-CSRF-TOKEN': CSRF },
        body:    JSON.stringify({ motivo: 'Cancelado por el vendedor' }),
    }).then(r => r.json());

    if (r.success) { toast(r.message); setTimeout(() => location.reload(), 1400); }
    else           { toast(r.message, 'err'); }
};

/* ── Toast ── */
function toast(msg, tipo = '') {
    const e = document.createElement('div');
    e.style.cssText = `
        position:fixed; bottom:24px; left:50%; transform:translateX(-50%);
        background:${ tipo==='err' ? '#dc2626' : '#059669' }; color:#fff;
        padding:11px 22px; border-radius:12px; font-size:13.5px; font-weight:700;
        z-index:9999; box-shadow:0 8px 30px rgba(0,0,0,.2);
        white-space:nowrap; pointer-events:none; animation:toastUp .2s ease both;
    `;
    e.textContent = msg;
    document.body.appendChild(e);
    setTimeout(() => e.remove(), 3200);
}
})();
</script>
@endpush

@endsection
