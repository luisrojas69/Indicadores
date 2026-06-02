{{-- tablet/caja.blade.php --}}
@extends('layouts.app')
@section('title', 'Panel de Caja')
@section('hide_daterange', true)

@push('styles')
<style>
.caja-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
    gap: 16px;
}
.pp-card {
    background: #fff; border-radius: 14px;
    border: 1.5px solid #e2e8f0;
    overflow: hidden; transition: all .18s;
    display: flex; flex-direction: column;
}
.pp-card:hover { box-shadow: 0 4px 20px rgba(0,0,0,.08); transform: translateY(-2px); }

.pp-card-head {
    padding: 14px 16px;
    display: flex; align-items: center;
    justify-content: space-between;
    border-bottom: 1px solid #f1f5f9;
    background: #fafbfc;
}
.pp-ref {
    font-family: var(--font-display,'Sora',sans-serif);
    font-size: 15px; font-weight: 800; color: #0f172a;
}
.pp-time { font-size: 11px; color: #94a3b8; margin-top: 2px; }

.estado-badge {
    display: inline-flex; align-items: center; gap: 5px;
    padding: 4px 10px; border-radius: 20px;
    font-size: 11px; font-weight: 700;
}

.pp-card-body { padding: 14px 16px; flex: 1; }

.pp-vendedor {
    display: flex; align-items: center; gap: 8px;
    margin-bottom: 10px;
}
.vend-avatar {
    width: 30px; height: 30px; border-radius: 50%;
    background: linear-gradient(135deg,#1a56db,#1347bf);
    color: #fff; font-size: 11px; font-weight: 800;
    display: flex; align-items: center; justify-content: center; flex-shrink: 0;
}
.vend-name { font-size: 12.5px; font-weight: 600; color: #374151; }
.vend-label { font-size: 10.5px; color: #94a3b8; }

.pp-cliente {
    background: #f8fafc; border-radius: 9px;
    padding: 9px 12px; margin-bottom: 10px;
}
.pp-cli-name { font-size: 13px; font-weight: 700; color: #0f172a; }
.pp-cli-tel  { font-size: 11.5px; color: #64748b; }

.pp-items-preview {
    margin-bottom: 10px;
}
.pp-item-row {
    display: flex; align-items: center; justify-content: space-between;
    padding: 5px 0; border-bottom: 1px solid #f8fafc; font-size: 12.5px;
}
.pp-item-row:last-child { border-bottom: none; }
.pp-item-desc { color: #374151; flex: 1; padding-right: 8px; }
.pp-item-qty  { color: #94a3b8; font-size: 11.5px; white-space: nowrap; }
.pp-item-sub  { font-weight: 700; color: #0f172a; white-space: nowrap; min-width: 70px; text-align: right; }

.pp-more { font-size: 11px; color: #1a56db; font-weight: 600; }

.pp-total-row {
    display: flex; justify-content: space-between; align-items: center;
    padding: 10px 0; border-top: 2px solid #f1f5f9; margin-top: 4px;
}
.pp-total-lbl { font-size: 12px; color: #94a3b8; }
.pp-total-val { font-family: var(--font-display,'Sora',sans-serif); font-size: 22px; font-weight: 900; color: #0f172a; }

.pp-card-foot {
    padding: 12px 16px; border-top: 1px solid #f1f5f9;
    display: flex; gap: 8px;
}

.btn-procesar {
    flex: 1; padding: 11px;
    background: linear-gradient(135deg,#059669,#047857);
    color: #fff; border: none; border-radius: 10px;
    font-size: 13.5px; font-weight: 700;
    display: flex; align-items: center; justify-content: center; gap: 7px;
    cursor: pointer; transition: all .15s;
}
.btn-procesar:hover { filter: brightness(1.08); }

.btn-cancelar-pp {
    width: 42px; height: 42px; border-radius: 10px;
    border: 1.5px solid #fca5a5; background: #fff;
    color: #dc2626; cursor: pointer;
    display: flex; align-items: center; justify-content: center;
    font-size: 15px; transition: all .15s;
}
.btn-cancelar-pp:hover { background: #fee2e2; }

/* KPI bar */
.caja-kpi-bar {
    display: flex; gap: 14px; flex-wrap: wrap;
    margin-bottom: 20px;
}
.caja-kpi {
    background: #fff; border-radius: 12px;
    padding: 14px 20px; border: 1px solid #e2e8f0;
    display: flex; align-items: center; gap: 12px;
    min-width: 170px; flex: 1;
}
.caja-kpi-icon {
    width: 40px; height: 40px; border-radius: 10px;
    display: flex; align-items: center; justify-content: center;
    font-size: 17px; flex-shrink: 0;
}
.caja-kpi-val { font-family: var(--font-display,'Sora',sans-serif); font-size: 24px; font-weight: 800; line-height: 1; }
.caja-kpi-lbl { font-size: 11px; color: #94a3b8; font-weight: 600; margin-top: 2px; }

/* Modal cancelar */
#modalCancelar .modal-content { border-radius: 16px; border: none; box-shadow: 0 20px 60px rgba(0,0,0,.2); }

/* Empty */
.caja-empty { text-align: center; padding: 80px 20px; color: #94a3b8; }
</style>
@endpush

@section('breadcrumb')
    <span class="current">Panel de Caja</span>
@endsection

@section('content')
@php
    $cur = config('app_client.locale.currency_symbol','$');
    $fmtM = fn(float $v) => $cur.' '.number_format($v,2,',','.');
@endphp

{{-- Header --}}
<div class="d-flex align-items-start justify-content-between flex-wrap gap-3 mb-4">
    <div>
        <h1 class="font-display mb-1" style="font-size:22px;font-weight:800;">
            <i class="fas fa-cash-register me-2" style="color:#1a56db;"></i>
            Panel de Caja
        </h1>
        <p class="mb-0" style="font-size:13px;color:#94a3b8;">
            Pre-pedidos enviados por los vendedores y pendientes de facturar en Profit.
        </p>
    </div>
    <a href="{{ route('tablet.catalogo') }}"
       class="btn btn-sm btn-outline-secondary" style="border-radius:9px;font-size:12.5px;">
        <i class="fas fa-tablet-screen-button me-1"></i> Ir al Catálogo
    </a>
</div>

{{-- KPIs rápidos --}}
<div class="caja-kpi-bar">
    <div class="caja-kpi">
        <div class="caja-kpi-icon" style="background:#fef3c7;">
            <i class="fas fa-clock" style="color:#d97706;"></i>
        </div>
        <div>
            <div class="caja-kpi-val" style="color:#d97706;">{{ $pendientes->count() }}</div>
            <div class="caja-kpi-lbl">Pendientes ahora</div>
        </div>
    </div>
    <div class="caja-kpi">
        <div class="caja-kpi-icon" style="background:#dcfce7;">
            <i class="fas fa-check-circle" style="color:#059669;"></i>
        </div>
        <div>
            <div class="caja-kpi-val" style="color:#059669;">{{ $procesadosHoy }}</div>
            <div class="caja-kpi-lbl">Procesados hoy</div>
        </div>
    </div>
    <div class="caja-kpi">
        <div class="caja-kpi-icon" style="background:#eff6ff;">
            <i class="fas fa-dollar-sign" style="color:#1a56db;"></i>
        </div>
        <div>
            <div class="caja-kpi-val" style="color:#1a56db;font-size:18px;">
                {{ $cur }} {{ number_format($pendientes->sum('total'),0,',','.') }}
            </div>
            <div class="caja-kpi-lbl">Total pendiente</div>
        </div>
    </div>
</div>

{{-- Grid de pre-pedidos --}}
@if($pendientes->isEmpty())
<div class="caja-empty">
    <i class="fas fa-inbox fa-3x d-block mb-4 opacity-20"></i>
    <p style="font-size:16px;font-weight:700;">No hay pre-pedidos pendientes</p>
    <p style="font-size:13px;">Los vendedores aún no han enviado nada a caja.</p>
</div>
@else
<div class="caja-grid">
    @foreach($pendientes as $pp)
    @php $estadoInfo = $pp->estadoInfo(); @endphp
    <div class="pp-card" id="pp-{{ $pp->id }}">

        {{-- Cabecera --}}
        <div class="pp-card-head">
            <div>
                <div class="pp-ref"># {{ $pp->numero_referencia }}</div>
                <div class="pp-time">
                    <i class="fas fa-clock" style="font-size:10px;"></i>
                    {{ $pp->enviado_a_caja_at?->diffForHumans() ?? $pp->updated_at->diffForHumans() }}
                </div>
            </div>
            <span class="estado-badge"
                  style="background:{{ $estadoInfo['bg'] }};color:{{ $estadoInfo['color'] }};">
                <i class="fas {{ $estadoInfo['icon'] }}" style="font-size:10px;"></i>
                {{ $estadoInfo['label'] }}
            </span>
        </div>

        {{-- Body --}}
        <div class="pp-card-body">

            {{-- Vendedor --}}
            <div class="pp-vendedor">
                <div class="vend-avatar">
                    {{ strtoupper(substr($pp->vendedor?->name ?? 'V', 0, 1)) }}
                </div>
                <div>
                    <div class="vend-name">{{ $pp->vendedor?->name ?? 'Vendedor' }}</div>
                    <div class="vend-label">Vendedor</div>
                </div>
            </div>

            {{-- Cliente --}}
            @if($pp->cliente_nombre)
            <div class="pp-cliente">
                <div class="pp-cli-name">
                    <i class="fas fa-user" style="font-size:10px;color:#94a3b8;margin-right:5px;"></i>
                    {{ $pp->cliente_nombre }}
                </div>
                @if($pp->cliente_telefono)
                <div class="pp-cli-tel">
                    <i class="fas fa-phone" style="font-size:9px;margin-right:4px;"></i>
                    {{ $pp->cliente_telefono }}
                </div>
                @endif
            </div>
            @endif

            {{-- Ítems (preview top 3) --}}
            <div class="pp-items-preview">
                @foreach($pp->items->take(3) as $item)
                <div class="pp-item-row">
                    <span class="pp-item-desc" title="{{ $item->articulo_descripcion }}">
                        {{ mb_strimwidth($item->articulo_descripcion, 0, 28, '…') }}
                    </span>
                    <span class="pp-item-qty">× {{ number_format($item->cantidad,0) }}</span>
                    <span class="pp-item-sub">
                        {{ $cur }} {{ number_format($item->subtotal,2,',','.') }}
                    </span>
                </div>
                @endforeach
                @if($pp->items->count() > 3)
                <p class="pp-more mt-1 mb-0">
                    + {{ $pp->items->count() - 3 }} artículos más
                </p>
                @endif
            </div>

            {{-- Total --}}
            <div class="pp-total-row">
                <span class="pp-total-lbl">
                    {{ $pp->total_items }} artículo{{ $pp->total_items !== 1 ? 's' : '' }}
                </span>
                <span class="pp-total-val">{{ $cur }} {{ number_format($pp->total,2,',','.') }}</span>
            </div>

        </div>

        {{-- Footer acciones --}}
        <div class="pp-card-foot">
            <button class="btn-procesar" onclick="procesar({{ $pp->id }}, '{{ $pp->numero_referencia }}')">
                <i class="fas fa-check"></i>
                Marcar Procesado
            </button>
            <button class="btn-cancelar-pp"
                    onclick="abrirCancelar({{ $pp->id }}, '{{ $pp->numero_referencia }}')"
                    title="Cancelar pre-pedido">
                <i class="fas fa-times"></i>
            </button>
        </div>

    </div>
    @endforeach
</div>
@endif

{{-- Modal Cancelar --}}
<div class="modal fade" id="modalCancelar" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered" style="max-width:420px;">
        <div class="modal-content">
            <div class="modal-header" style="border:none;padding:20px 24px 8px;">
                <h5 class="modal-title fw-bold" style="font-size:16px;">
                    <i class="fas fa-times-circle text-danger me-2"></i>
                    Cancelar Pre-Pedido
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" style="padding:12px 24px 20px;">
                <p style="font-size:13.5px;color:#374151;" id="cancelarMsg">¿Cancelar este pre-pedido?</p>
                <input type="text" id="motivoCancelacion"
                       class="form-control" placeholder="Motivo (opcional)"
                       style="border-radius:9px;font-size:13px;">
            </div>
            <div class="modal-footer" style="border:none;padding:0 24px 20px;gap:8px;">
                <button type="button" class="btn btn-outline-secondary btn-sm"
                        data-bs-dismiss="modal" style="border-radius:9px;">Cerrar</button>
                <button type="button" class="btn btn-danger btn-sm"
                        id="btnConfirmarCancelar"
                        style="border-radius:9px;font-weight:700;">
                    Confirmar Cancelación
                </button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
(function(){
'use strict';
const CSRF = document.querySelector('meta[name=csrf-token]')?.content ?? '';

async function jPost(url, body={}){
    const r = await fetch(url,{
        method:'POST',
        headers:{'Content-Type':'application/json','X-CSRF-TOKEN':CSRF},
        body:JSON.stringify(body)
    });
    return r.json();
}

/* ── Procesar ── */
window.procesar = async (id, ref) => {
    if(!confirm(`¿Confirmar que #${ref} fue facturado en Profit?`)) return;
    const r = await jPost(`/caja/prepedido/${id}/procesar`);
    if(r.success){
        const card = document.getElementById(`pp-${id}`);
        card.style.transition = 'opacity .3s, transform .3s';
        card.style.opacity = '0'; card.style.transform = 'scale(.95)';
        setTimeout(()=>card.remove(), 300);
        showToast(`✅ ${r.message}`);
        // Actualizar contador KPI
        const kv = document.querySelector('.caja-kpi-val');
        if(kv){ const n=parseInt(kv.textContent)-1; kv.textContent=n>=0?n:0; }
    } else {
        showToast(r.message, 'err');
    }
};

/* ── Cancelar ── */
let cancelId = null;
window.abrirCancelar = (id, ref) => {
    cancelId = id;
    document.getElementById('cancelarMsg').textContent = `¿Cancelar el pre-pedido #${ref}?`;
    document.getElementById('motivoCancelacion').value = '';
    new bootstrap.Modal(document.getElementById('modalCancelar')).show();
};

document.getElementById('btnConfirmarCancelar')?.addEventListener('click', async ()=>{
    if(!cancelId) return;
    const motivo = document.getElementById('motivoCancelacion').value;
    const r = await jPost(`/caja/prepedido/${cancelId}/cancelar`,{motivo});
    if(r.success){
        document.getElementById(`pp-${cancelId}`)?.remove();
        bootstrap.Modal.getInstance(document.getElementById('modalCancelar'))?.hide();
        showToast(r.message);
    } else {
        showToast(r.message,'err');
    }
});

/* ── Toast ── */
function showToast(msg, tipo=''){
    const e=document.createElement('div');
    e.style.cssText=`position:fixed;bottom:24px;left:50%;transform:translateX(-50%);
        background:${tipo==='err'?'#dc2626':'#059669'};color:#fff;
        padding:11px 22px;border-radius:12px;font-size:13.5px;font-weight:700;
        z-index:9999;box-shadow:0 8px 30px rgba(0,0,0,.2);white-space:nowrap;
        animation:toastUp .2s ease both;pointer-events:none;`;
    e.textContent=msg;
    document.body.appendChild(e);
    setTimeout(()=>e.remove(),3200);
}

/* ── Auto-refresh cada 30s ── */
setInterval(()=> location.reload(), 30000);

})();
</script>
<style>@keyframes toastUp{from{opacity:0;transform:translateX(-50%) translateY(10px);}to{opacity:1;transform:translateX(-50%) translateY(0);}}</style>
@endpush

@endsection
