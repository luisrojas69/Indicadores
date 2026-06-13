{{-- tablet/catalogo.blade.php --}}
@extends('layouts.app')
@section('title', 'Catálogo Tablet')
@section('hide_daterange', true)

@push('styles')
<style>
/* ── Override padding solo para este módulo ─ */
.app-content { padding: 0 !important; overflow: hidden; }

/* ── Variables ─────────────────────────── */
:root { --sb-w: 200px; --bar-h: 62px; }

/* ══ SHELL ══════════════════════════════ */
.t-shell {
    display: flex;
    height: calc(100vh - 64px);
    overflow: hidden;
    position: relative;
    background: #f1f5f9;
}

/* ══ SIDEBAR FILTROS ════════════════════ */
.t-sb {
    width: var(--sb-w); flex-shrink: 0;
    background: #fff;
    border-right: 1px solid #e2e8f0;
    overflow-y: auto;
    padding: 14px 10px;
    transition: transform .25s ease;
    z-index: 20;
}
.t-sb-section { margin-bottom: 18px; }
.t-sb-title {
    font-size: 9.5px; font-weight: 800;
    text-transform: uppercase; letter-spacing: .8px;
    color: #94a3b8; padding: 0 4px;
    margin-bottom: 6px; display: block;
}
.t-chip {
    display: block; padding: 8px 11px;
    border-radius: 9px; font-size: 12.5px; font-weight: 500;
    cursor: pointer; border: 1.5px solid transparent;
    background: transparent; color: #64748b;
    transition: all .12s; text-align: left;
    margin-bottom: 2px; text-decoration: none;
    width: 100%;
}
.t-chip:hover  { background: #eff6ff; color: #1a56db; }
.t-chip.on     { background: #1a56db; color: #fff; font-weight: 700; }

/* ══ MAIN ════════════════════════════════ */
.t-main { flex: 1; display: flex; flex-direction: column; overflow: hidden; min-width: 0; }

/* ── Barra búsqueda ── */
.t-bar {
    height: var(--bar-h); flex-shrink: 0;
    background: #fff; border-bottom: 1px solid #e2e8f0;
    display: flex; align-items: center;
    padding: 0 14px; gap: 10px;
}
.t-search-wrap { flex: 1; position: relative; }
.t-search {
    width: 100%; padding: 10px 14px 10px 38px;
    border-radius: 10px; border: 1.5px solid #e2e8f0;
    font-size: 14px; background: #f8fafc;
    transition: all .15s; font-family: inherit;
}
.t-search:focus {
    outline: none; border-color: #1a56db;
    background: #fff; box-shadow: 0 0 0 3px rgba(26,86,219,.08);
}
.t-search-ico {
    position: absolute; left: 11px; top: 50%;
    transform: translateY(-50%);
    color: #94a3b8; font-size: 13px; pointer-events: none;
}
.t-info { font-size: 12px; color: #94a3b8; white-space: nowrap; flex-shrink: 0; }

/* Botón carrito */
.t-cart-btn {
    background: #1a56db; color: #fff; border: none;
    border-radius: 10px; padding: 9px 16px;
    font-size: 13.5px; font-weight: 700;
    display: flex; align-items: center; gap: 7px;
    cursor: pointer; white-space: nowrap; flex-shrink: 0;
    transition: filter .12s;
}
.t-cart-btn:hover { filter: brightness(1.1); }
.t-cbadge {
    background: #fff; color: #1a56db;
    font-size: 10px; font-weight: 800;
    min-width: 20px; height: 20px; border-radius: 20px;
    padding: 0 5px;
    display: flex; align-items: center; justify-content: center;
}

/* ── Toggle sidebar móvil ── */
.t-sb-toggle {
    width: 40px; height: 40px; border-radius: 10px;
    border: 1.5px solid #e2e8f0; background: #f8fafc;
    display: flex; align-items: center; justify-content: center;
    font-size: 16px; color: #64748b; cursor: pointer; flex-shrink: 0;
    transition: all .12s;
}
.t-sb-toggle:hover { border-color: #1a56db; color: #1a56db; }

/* ══ GRID PRODUCTOS ══════════════════════ */
.t-grid-wrap { flex: 1; overflow-y: auto; padding: 14px; }
.t-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(175px, 1fr));
    gap: 12px;
}

/* ── Tarjeta ── */
.p-card {
    background: #fff; border-radius: 14px;
    border: 1.5px solid #e2e8f0;
    display: flex; flex-direction: column;
    overflow: hidden; transition: all .18s;
    cursor: pointer;
    -webkit-tap-highlight-color: transparent;
}
.p-card:hover {
    border-color: #1a56db;
    box-shadow: 0 4px 20px rgba(26,86,219,.1);
    transform: translateY(-2px);
}
.p-card:active { transform: scale(.97); }

.p-img {
    height: 100px;
    display: flex; align-items: center; justify-content: center;
    font-size: 46px;
    background: linear-gradient(135deg, #f8fafc, #eef2ff);
    flex-shrink: 0;
}
.p-body { padding: 10px 11px; flex: 1; display: flex; flex-direction: column; gap: 3px; }
.p-linea { font-size: 9.5px; font-weight: 800; text-transform: uppercase; letter-spacing: .5px; color: #1a56db; opacity: .7; }
.p-nombre {
    font-size: 12px; font-weight: 700; color: #0f172a; line-height: 1.3;
    display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;
}
.p-modelo { font-size: 10.5px; color: #94a3b8; }
.p-precio { font-family: var(--font-display,'Sora',sans-serif); font-size: 17px; font-weight: 800; color: #0f172a; margin-top: 3px; }
.p-stock { font-size: 10.5px; font-weight: 600; display: flex; align-items: center; gap: 3px; }
.p-stock.ok     { color: #059669; }
.p-stock.bajo   { color: #d97706; }
.p-stock.agotado{ color: #dc2626; }

.p-foot { padding: 8px 10px; border-top: 1px solid #f1f5f9; display: flex; gap: 6px; }
.btn-add {
    flex: 1; padding: 9px 0;
    background: #1a56db; color: #fff; border: none;
    border-radius: 9px; font-size: 13px; font-weight: 700;
    display: flex; align-items: center; justify-content: center; gap: 5px;
    cursor: pointer; transition: filter .12s;
}
.btn-add:hover  { filter: brightness(1.1); }
.btn-add:active { transform: scale(.95); }
.btn-add:disabled { background: #e2e8f0; color: #94a3b8; cursor: default; filter: none; transform: none; }
.btn-inf {
    width: 36px; height: 36px; flex-shrink: 0;
    background: #f8fafc; color: #64748b;
    border: 1.5px solid #e2e8f0; border-radius: 9px;
    display: flex; align-items: center; justify-content: center;
    cursor: pointer; font-size: 13px; transition: all .12s;
}
.btn-inf:hover { border-color: #1a56db; color: #1a56db; }

/* ══ CARRITO PANEL ═══════════════════════ */
.cart-overlay {
    display: none; position: fixed; inset: 0;
    background: rgba(0,0,0,.38); z-index: 200; cursor: pointer;
}
.cart-overlay.open { display: block; }
.cart-panel {
    position: fixed; top: 0; right: 0; bottom: 0;
    width: 360px; max-width: 96vw;
    background: #fff; z-index: 201;
    display: flex; flex-direction: column;
    transform: translateX(110%);
    transition: transform .28s cubic-bezier(.4,0,.2,1);
    box-shadow: -8px 0 40px rgba(0,0,0,.14);
}
.cart-panel.open { transform: translateX(0); }
.cart-hd {
    padding: 16px 18px; background: #1a56db; color: #fff;
    display: flex; align-items: center; justify-content: space-between; flex-shrink: 0;
}
.cart-hd h3 { font-family: var(--font-display,'Sora',sans-serif); font-size: 16px; font-weight: 800; margin: 0; }
.cart-cls {
    width: 32px; height: 32px; border-radius: 8px;
    background: rgba(255,255,255,.18); border: none; color: #fff;
    cursor: pointer; display: flex; align-items: center; justify-content: center;
    font-size: 15px; transition: background .12s;
}
.cart-cls:hover { background: rgba(255,255,255,.3); }
.cart-items { flex: 1; overflow-y: auto; padding: 10px 14px; }

/* ítem del carrito */
.ci {
    display: flex; align-items: center; gap: 9px;
    padding: 9px 0; border-bottom: 1px solid #f8fafc;
    animation: fadeIn .15s ease;
}
@keyframes fadeIn { from { opacity:0; transform:translateY(4px); } to { opacity:1; } }
.ci:last-child { border-bottom: none; }
.ci-emo { width: 38px; height: 38px; border-radius: 9px; background: #f1f5f9; display: flex; align-items: center; justify-content: center; font-size: 20px; flex-shrink: 0; }
.ci-inf { flex: 1; min-width: 0; }
.ci-nom { font-size: 12px; font-weight: 700; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.ci-met { font-size: 11px; color: #94a3b8; }
.ci-qty { display: flex; align-items: center; gap: 3px; flex-shrink: 0; }
.ci-qb {
    width: 26px; height: 26px; border-radius: 7px;
    border: 1px solid #e2e8f0; background: #f8fafc;
    display: flex; align-items: center; justify-content: center;
    cursor: pointer; font-size: 15px; font-weight: 700;
    color: #64748b; transition: all .1s; line-height: 1;
}
.ci-qb:hover { border-color: #1a56db; color: #1a56db; }
.ci-qn { width: 30px; text-align: center; font-size: 13px; font-weight: 700; }
.ci-sub { font-family: var(--font-display,'Sora',sans-serif); font-size: 13px; font-weight: 800; color: #1a56db; flex-shrink: 0; min-width: 58px; text-align: right; }
.ci-del {
    width: 26px; height: 26px; border-radius: 6px; border: none;
    background: transparent; color: #cbd5e1; cursor: pointer;
    display: flex; align-items: center; justify-content: center; font-size: 12px; transition: all .1s; flex-shrink: 0;
}
.ci-del:hover { color: #dc2626; background: #fee2e2; }

/* Footer carrito */
.cart-ft { padding: 14px 18px; border-top: 2px solid #f1f5f9; flex-shrink: 0; }
.cart-tot { display: flex; justify-content: space-between; align-items: baseline; margin-bottom: 12px; }
.cart-tot-lbl { font-size: 13px; color: #94a3b8; }
.cart-tot-val { font-family: var(--font-display,'Sora',sans-serif); font-size: 24px; font-weight: 900; color: #0f172a; }
.cart-cli { display: flex; flex-direction: column; gap: 7px; margin-bottom: 12px; }
.cart-inp { border: 1.5px solid #e2e8f0; border-radius: 9px; padding: 9px 12px; font-size: 13px; font-family: inherit; transition: border-color .12s; }
.cart-inp:focus { outline: none; border-color: #1a56db; }
.btn-caja {
    width: 100%; padding: 14px;
    background: linear-gradient(135deg,#059669,#047857);
    color: #fff; border: none; border-radius: 12px;
    font-family: var(--font-display,'Sora',sans-serif);
    font-size: 15px; font-weight: 800;
    cursor: pointer; transition: all .18s;
    display: flex; align-items: center; justify-content: center; gap: 10px;
}
.btn-caja:hover  { filter: brightness(1.08); transform: translateY(-1px); }
.btn-caja:disabled { background: #e2e8f0; color: #94a3b8; transform: none; cursor: default; filter: none; }

/* ══ MODAL FICHA ═════════════════════════ */
#modalFicha .modal-content { border-radius: 18px; border: none; box-shadow: 0 20px 60px rgba(0,0,0,.2); overflow: hidden; }
.spec-chip { display: inline-flex; align-items: center; gap: 5px; background: #f1f5f9; border-radius: 8px; padding: 5px 11px; font-size: 12px; font-weight: 600; margin: 3px; }
.spec-chip em { font-style: normal; font-weight: 400; color: #64748b; }

/* ── Toast ── */
.t-toast {
    position: fixed; bottom: 24px; left: 50%;
    transform: translateX(-50%);
    background: #059669; color: #fff;
    padding: 11px 22px; border-radius: 12px;
    font-size: 13.5px; font-weight: 700; z-index: 9999;
    box-shadow: 0 8px 30px rgba(0,0,0,.2);
    animation: toastUp .2s ease both; white-space: nowrap; pointer-events: none;
}
.t-toast.err { background: #dc2626; }
@keyframes toastUp { from { opacity:0; transform:translateX(-50%) translateY(10px); } to { opacity:1; transform:translateX(-50%) translateY(0); } }

/* ══ RESPONSIVE ══════════════════════════ */
@media (max-width: 900px) {
    .t-grid { grid-template-columns: repeat(auto-fill, minmax(155px, 1fr)); }
}
@media (max-width: 680px) {
    .t-sb { position: fixed; top: 0; left: 0; bottom: 0; transform: translateX(-100%); box-shadow: 4px 0 20px rgba(0,0,0,.12); }
    .t-sb.open { transform: translateX(0); }
    .t-grid { grid-template-columns: repeat(2, 1fr); gap: 10px; }
    .p-img { height: 80px; font-size: 36px; }
    .t-info { display: none; }
}
@media (max-width: 380px) { .t-grid { grid-template-columns: 1fr; } }

/* Paginación */
.t-pager { display: flex; justify-content: center; gap: 6px; padding: 16px 0; }
.t-pg {
    width: 36px; height: 36px; border-radius: 9px;
    border: 1.5px solid #e2e8f0; background: #fff;
    display: flex; align-items: center; justify-content: center;
    font-size: 13px; font-weight: 700; text-decoration: none; color: #64748b; transition: all .12s;
}
.t-pg:hover { border-color: #1a56db; color: #1a56db; }
.t-pg.on { background: #1a56db; border-color: #1a56db; color: #fff; }

/* Overlay sidebar móvil */
.sb-overlay { display: none; position: fixed; inset: 0; background: rgba(0,0,0,.4); z-index: 19; }
.sb-overlay.open { display: block; }

/* Empty */
.t-empty { text-align: center; padding: 80px 20px; color: #94a3b8; }
</style>
@endpush

@section('content')
@php
    $cur      = config('app_client.locale.currency_symbol','$');
    $nivelDef = config('tablet.precio_nivel_default', 1);
    $catIcons = config('tablet.categoria_icons', []);
    $nItems   = $carrito->items->count();
    $totCart  = $carrito->total;
@endphp

<div class="t-shell">

    {{-- Overlay sidebar móvil --}}
    <div class="sb-overlay" id="sbOverlay" onclick="closeSb()"></div>

    {{-- ── SIDEBAR ──────────────────────────────── --}}
    <aside class="t-sb" id="tSb">

        @if(!empty($categorias))
        <div class="t-sb-section">
            <span class="t-sb-title"><i class="fas fa-tag me-1"></i>Categoría</span>
            <a href="{{ request()->fullUrlWithQuery(['categoria'=>'','page'=>1]) }}"
               class="t-chip {{ empty($filters['categoria']) ? 'on' : '' }}">Todos</a>
            @foreach($categorias as $cv => $cl)
            @php $val = is_string($cv) ? $cv : $cl; @endphp
            <a href="{{ request()->fullUrlWithQuery(['categoria'=>$val,'page'=>1]) }}"
               class="t-chip {{ $filters['categoria']===$val ? 'on' : '' }}">{{ $cl }}</a>
            @endforeach
        </div>
        @endif

        @if(!empty($marcas))
        <div class="t-sb-section">
            <span class="t-sb-title"><i class="fas fa-layer-group me-1"></i>Marca / Línea</span>
            <a href="{{ request()->fullUrlWithQuery(['marca'=>'','page'=>1]) }}"
               class="t-chip {{ empty($filters['marca']) ? 'on' : '' }}">Todas</a>
            @foreach(array_slice($marcas,0,14) as $m)
            <a href="{{ request()->fullUrlWithQuery(['marca'=>$m,'page'=>1]) }}"
               class="t-chip {{ $filters['marca']===$m ? 'on' : '' }}">{{ $m }}</a>
            @endforeach
        </div>
        @endif

    </aside>

    {{-- ── MAIN ────────────────────────────────── --}}
    <div class="t-main">

        {{-- Barra búsqueda --}}
        <div class="t-bar">
            <button class="t-sb-toggle d-lg-none" onclick="toggleSb()">
                <i class="fas fa-sliders"></i>
            </button>
            <div class="t-search-wrap">
                <i class="fas fa-search t-search-ico"></i>
                <input type="text" id="searchInput" class="t-search"
                       placeholder="Buscar nombre, código o marca..."
                       value="{{ $filters['search'] }}" autocomplete="off">
            </div>
            <span class="t-info">{{ number_format($total,0,'.','.') }} artículos</span>
            <button class="t-cart-btn" onclick="toggleCart()">
                <i class="fas fa-shopping-cart"></i>
                <span class="d-none d-sm-inline">Carrito</span>
                <span class="t-cbadge" id="cartCount">{{ $nItems }}</span>
            </button>
        </div>

        {{-- Grid --}}
        <div class="t-grid-wrap">
            @if(empty($articulos))
            <div class="t-empty">
                <i class="fas fa-box-open fa-2x d-block mb-3 opacity-25"></i>
                <p style="font-size:15px;font-weight:700;">Sin artículos disponibles</p>
                <p style="font-size:13px;">Prueba con otro término o quita los filtros.</p>
            </div>
            @else
            <div class="t-grid">
                @foreach($articulos as $art)
                @php
                    $libre  = max(0,(float)($art['stock_actual']??0)-(float)($art['stock_com']??0));
                    $sc     = $libre<=0?'agotado':($libre<=3?'bajo':'ok');
                    $si     = $libre<=0?'fa-times-circle':($libre<=3?'fa-exclamation-circle':'fa-check-circle');
                    $emoji  = $catIcons[$art['categoria']??''] ?? ($catIcons['default']??'📦');
                    $precio = (float)($art["precio{$nivelDef}"]??$art['precio1']??0);
                @endphp
                <div class="p-card">
                    <div class="p-img">{{ $emoji }}</div>
                    <div class="p-body">
                        @if(!empty($art['linea']))
                        <div class="p-linea">{{ $art['linea'] }}</div>
                        @endif
                        <div class="p-nombre" title="{{ $art['descripcion'] }}">{{ $art['descripcion'] }}</div>
                        @if(!empty($art['modelo']))
                        <div class="p-modelo">{{ $art['modelo'] }}</div>
                        @endif
                        <div class="p-precio">{{ $cur }} {{ number_format($precio,2,',','.') }}</div>
                        <div class="p-stock {{ $sc }}">
                            <i class="fas {{ $si }}" style="font-size:9px;"></i>
                            @if($libre<=0) Sin stock
                            @elseif($libre<=3) {{ number_format($libre,0) }} uds (últimas)
                            @else {{ number_format($libre,0) }} disponibles
                            @endif
                        </div>
                    </div>
                    <div class="p-foot">
                        <button class="btn-add" onclick="addToCart('{{ $art['codigo'] }}')" {{ $libre<=0?'disabled':'' }}>
                            <i class="fas fa-plus" style="font-size:10px;"></i> Agregar
                        </button>
                        <button class="btn-inf" onclick="showFicha('{{ $art['codigo'] }}')" title="Ficha completa">
                            <i class="fas fa-expand-alt"></i>
                        </button>
                    </div>
                </div>
                @endforeach
            </div>

            @if($totalPages > 1)
            <div class="t-pager">
                @if($page>1)
                <a href="{{ request()->fullUrlWithQuery(['page'=>$page-1]) }}" class="t-pg">
                    <i class="fas fa-chevron-left" style="font-size:10px;"></i>
                </a>
                @endif
                @for($p=max(1,$page-2);$p<=min($totalPages,$page+2);$p++)
                <a href="{{ request()->fullUrlWithQuery(['page'=>$p]) }}"
                   class="t-pg {{ $p===$page?'on':'' }}">{{ $p }}</a>
                @endfor
                @if($page<$totalPages)
                <a href="{{ request()->fullUrlWithQuery(['page'=>$page+1]) }}" class="t-pg">
                    <i class="fas fa-chevron-right" style="font-size:10px;"></i>
                </a>
                @endif
            </div>
            @endif
            @endif
        </div>

    </div>
</div>

{{-- ══ CARRITO ════════════════════════════════════ --}}
<div class="cart-overlay" id="cartOverlay" onclick="toggleCart()"></div>
<div class="cart-panel" id="cartPanel">
    <div class="cart-hd">
        <h3><i class="fas fa-shopping-cart me-2"></i>Carrito</h3>
        <button class="cart-cls" onclick="toggleCart()"><i class="fas fa-times"></i></button>
    </div>
    <div class="cart-items" id="cartItems">
        @forelse($carrito->items as $item)
        @php $eI = $catIcons[$item->articulo_categoria??''] ?? ($catIcons['default']??'📦'); @endphp
        <div class="ci" id="ci-{{ $item->id }}">
            <div class="ci-emo">{{ $eI }}</div>
            <div class="ci-inf">
                <div class="ci-nom" title="{{ $item->articulo_descripcion }}">{{ $item->articulo_descripcion }}</div>
                <div class="ci-met">{{ $cur }} {{ number_format($item->precio_unitario,2,',','.') }} · P{{ $item->precio_nivel }}</div>
            </div>
            <div class="ci-qty">
                <button class="ci-qb" onclick="updQty({{ $item->id }},{{ $item->cantidad-1 }})">−</button>
                <span class="ci-qn" id="qn-{{ $item->id }}">{{ number_format($item->cantidad,0) }}</span>
                <button class="ci-qb" onclick="updQty({{ $item->id }},{{ $item->cantidad+1 }})">+</button>
            </div>
            <div class="ci-sub" id="sub-{{ $item->id }}">{{ $cur }} {{ number_format($item->subtotal,2,',','.') }}</div>
            <button class="ci-del" onclick="delItem({{ $item->id }})"><i class="fas fa-trash"></i></button>
        </div>
        @empty
        <div id="cartEmpty" style="text-align:center;padding:60px 20px;color:#94a3b8;">
            <i class="fas fa-shopping-cart fa-2x d-block mb-3 opacity-25"></i>
            <p style="font-size:14px;font-weight:700;">Carrito vacío</p>
            <p style="font-size:12px;">Agrega productos del catálogo</p>
        </div>
        @endforelse
    </div>
    <div class="cart-ft">
        <div class="cart-tot">
            <span class="cart-tot-lbl">Total estimado</span>
            <span class="cart-tot-val" id="cartTotal">{{ $cur }} {{ number_format($totCart,2,',','.') }}</span>
        </div>
        <div class="cart-cli">
            <input type="text" class="cart-inp" id="cliNombre"
                   value="{{ $carrito->cliente_nombre }}" placeholder="Nombre del cliente (opcional)">
            <input type="text" class="cart-inp" id="cliTel"
                   value="{{ $carrito->cliente_telefono }}" placeholder="Teléfono (opcional)">
        </div>
        <button class="btn-caja" id="btnCaja" onclick="sendToCaja()" {{ $nItems===0?'disabled':'' }}>
            <i class="fas fa-cash-register"></i> Enviar a Caja
        </button>
    </div>
</div>

{{-- ══ MODAL FICHA ════════════════════════════════ --}}
<div class="modal fade" id="modalFicha" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header" style="border:none;padding:20px 24px 8px;">
                <h5 class="modal-title fw-bold" id="fichaTitle" style="font-family:var(--font-display,'Sora',sans-serif);font-size:17px;">—</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="fichaBody" style="padding:0 24px 24px;">
                <div style="text-align:center;padding:40px;">
                    <i class="fas fa-spinner fa-spin fa-2x" style="color:#1a56db;"></i>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
(function(){
'use strict';
const CSRF   = document.querySelector('meta[name=csrf-token]')?.content ?? '';
const SYM    = '{{ $cur }}';
const ICONS  = {!! json_encode($catIcons) !!};
const fmtM   = n => `${SYM} ${parseFloat(n).toLocaleString('es-VE',{minimumFractionDigits:2,maximumFractionDigits:2})}`;

/* ── Sidebar móvil ── */
window.toggleSb  = () => { document.getElementById('tSb').classList.toggle('open'); document.getElementById('sbOverlay').classList.toggle('open'); };
window.closeSb   = () => { document.getElementById('tSb').classList.remove('open'); document.getElementById('sbOverlay').classList.remove('open'); };

/* ── Carrito toggle ── */
window.toggleCart = () => {
    document.getElementById('cartPanel').classList.toggle('open');
    document.getElementById('cartOverlay').classList.toggle('open');
};

/* ── Fetch helper ── */
async function jFetch(url, method='GET', body=null){
    const o = { method, headers:{'Content-Type':'application/json','X-CSRF-TOKEN':CSRF} };
    if(body) o.body = JSON.stringify(body);
    const r = await fetch(url, o);
    return r.json();
}

/* ── Agregar al carrito ── */
window.addToCart = async (codigo) => {
    const r = await jFetch('{{ route('tablet.carrito.agregar') }}','POST',{codigo,cantidad:1});
    if(!r.success){ toast(r.message,'err'); return; }
    setCount(r.carrito_items);
    setTotal(r.carrito_total);
    toast(r.message);
    // Insertar ítem en DOM si no existe
    if(!document.getElementById(`ci-${r.item?.id}`)) location.reload();
};

/* ── Actualizar cantidad ── */
window.updQty = async (id, qty) => {
    if(qty < 0) return;
    const r = await jFetch(`/tablet/carrito/item/${id}`,'PATCH',{cantidad:qty});
    if(!r.success) return;
    if(qty === 0){
        document.getElementById(`ci-${id}`)?.remove();
    } else {
        const qEl = document.getElementById(`qn-${id}`);
        const sEl = document.getElementById(`sub-${id}`);
        if(qEl) qEl.textContent = qty;
        if(sEl && r.items){
            const it = r.items.find(i=>i.id===id);
            if(it) sEl.textContent = fmtM(it.subtotal);
        }
    }
    setCount(r.carrito_items ?? document.querySelectorAll('.ci').length);
    setTotal(r.carrito_total);
    checkEmpty();
};

/* ── Eliminar ítem ── */
window.delItem = async (id) => {
    const r = await jFetch(`/tablet/carrito/item/${id}`,'DELETE');
    if(!r.success) return;
    document.getElementById(`ci-${id}`)?.remove();
    setCount(r.carrito_items);
    setTotal(r.carrito_total);
    checkEmpty();
};

/* ── Enviar a caja ── */
window.sendToCaja = async () => {
    const btn = document.getElementById('btnCaja');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Enviando...';
    const r = await jFetch('{{ route('tablet.carrito.enviar') }}','POST',{
        cliente_nombre:   document.getElementById('cliNombre')?.value ?? '',
        cliente_telefono: document.getElementById('cliTel')?.value ?? '',
    });
    if(r.success){ toast(`✅ ${r.message}`); setTimeout(()=>location.reload(),1600); }
    else {
        toast(r.message,'err');
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-cash-register"></i> Enviar a Caja';
    }
};

/* ── Ver ficha ── */
window.showFicha = async (codigo) => {
    const modal = new bootstrap.Modal(document.getElementById('modalFicha'));
    document.getElementById('fichaBody').innerHTML = '<div style="text-align:center;padding:40px;"><i class="fas fa-spinner fa-spin fa-2x" style="color:#1a56db;"></i></div>';
    modal.show();
    const r = await fetch(`/tablet/articulo/${encodeURIComponent(codigo)}`);
    const d = await r.json();
    if(d.error){ document.getElementById('fichaBody').innerHTML=`<p class="text-danger">${d.error}</p>`; return; }
    const a = d.articulo;
    document.getElementById('fichaTitle').textContent = a.descripcion;
    const libre = Math.max(0,(parseFloat(a.stock_actual)||0)-(parseFloat(a.stock_com)||0));
    const sc = libre<=0?'#dc2626':(libre<=3?'#d97706':'#059669');
    const em = ICONS[a.categoria]||ICONS['default']||'📦';
    const specs = (a.specs||[]).map(s=>`<div class="spec-chip"><strong>${s.label}:</strong> <em>${s.valor}</em></div>`).join('') || '<p style="color:#94a3b8;font-size:12px;">Sin especificaciones registradas.</p>';
    const precios = [1,2,3,4].map(n=>{
        const p=parseFloat(a[`precio${n}`]||0); if(!p) return '';
        return `<div style="text-align:center;padding:10px;border-radius:10px;background:#f8fafc;border:1.5px solid ${n===1?'#1a56db':'#e2e8f0'};">
            <div style="font-size:9.5px;color:#94a3b8;font-weight:700;text-transform:uppercase;margin-bottom:3px;">Precio ${n}</div>
            <div style="font-size:17px;font-weight:800;color:${n===1?'#1a56db':'#0f172a'};">${fmtM(p)}</div>
        </div>`;
    }).join('');
    document.getElementById('fichaBody').innerHTML = `
    <div class="row g-3">
        <div class="col-12 col-sm-4 text-center">
            <div style="font-size:72px;line-height:1;margin-bottom:10px;">${em}</div>
            <div style="font-size:11px;color:#1a56db;font-weight:700;text-transform:uppercase;">${a.linea||''}</div>
            <div style="font-size:13px;color:#64748b;">${a.categoria||''}</div>
            ${a.modelo?`<div style="font-size:12px;font-weight:600;margin-top:4px;">${a.modelo}</div>`:''}
            <div style="margin:10px 0;font-size:15px;font-weight:800;color:${sc};">${libre<=0?'❌ Sin stock':`✅ ${libre} disponibles`}</div>
            <div style="font-size:11px;color:#94a3b8;">Cód: ${a.codigo}</div>
            ${a.codigo_barras?`<div style="font-size:10.5px;color:#94a3b8;">Barras: ${a.codigo_barras}</div>`:''}
            ${a.proveedor_principal?`<div style="font-size:10.5px;color:#94a3b8;">Proveedor: ${a.proveedor_principal}</div>`:''}
        </div>
        <div class="col-12 col-sm-8">
            <p style="font-size:10.5px;font-weight:700;text-transform:uppercase;color:#94a3b8;margin-bottom:8px;"><i class="fas fa-microchip me-1"></i>Especificaciones</p>
            <div style="margin-bottom:14px;">${specs}</div>
            <p style="font-size:10.5px;font-weight:700;text-transform:uppercase;color:#94a3b8;margin-bottom:8px;"><i class="fas fa-tag me-1"></i>Precios</p>
            <div style="display:grid;grid-template-columns:repeat(2,1fr);gap:8px;margin-bottom:16px;">${precios}</div>
            <button onclick="addToCart('${a.codigo}');bootstrap.Modal.getInstance(document.getElementById('modalFicha')).hide();"
                    style="width:100%;padding:13px;border-radius:12px;border:none;background:#1a56db;color:#fff;font-size:15px;font-weight:700;cursor:pointer;"
                    ${libre<=0?'disabled':''}>
                <i class="fas fa-plus me-2"></i>Agregar al Carrito
            </button>
        </div>
    </div>`;
};

/* ── Helpers ── */
function setCount(n){ document.getElementById('cartCount').textContent=n; document.getElementById('btnCaja').disabled=(n==0); }
function setTotal(v){ const e=document.getElementById('cartTotal'); if(e) e.textContent=fmtM(v); }
function checkEmpty(){
    if(!document.querySelector('.ci')){
        document.getElementById('cartItems').innerHTML='<div id="cartEmpty" style="text-align:center;padding:60px 20px;color:#94a3b8;"><i class="fas fa-shopping-cart fa-2x d-block mb-3 opacity-25"></i><p style="font-size:14px;font-weight:700;">Carrito vacío</p></div>';
    }
}
function toast(msg,tipo=''){
    const e=document.createElement('div');
    e.className=`t-toast ${tipo}`;
    e.textContent=msg;
    document.body.appendChild(e);
    setTimeout(()=>e.remove(),3200);
}

/* ── Búsqueda debounce ── */
let timer;
document.getElementById('searchInput')?.addEventListener('input',function(){
    clearTimeout(timer);
    timer=setTimeout(()=>{
        const u=new URL(window.location.href);
        u.searchParams.set('search',this.value);
        u.searchParams.set('page','1');
        window.location.href=u.toString();
    },550);
});

})();
</script>
@endpush

@endsection
