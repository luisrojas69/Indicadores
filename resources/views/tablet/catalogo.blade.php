@extends('layouts.tablet')
@section('title', 'Catálogo')

@push('styles')
<style>
/* ── Reset padding del contenedor ── */
.t-content { padding: 0; }

/* ══ SHELL DEL CATÁLOGO ══════════════════════════════════════ */
.cat-shell {
    display: flex;
    flex-direction: column;
    height: 100%;
    overflow: hidden;
    background: #f1f5f9;
}

/* ── Barra superior ────────────────────────────────────────── */
.cat-bar {
    background: #fff;
    border-bottom: 1px solid #e2e8f0;
    padding: 10px 12px;
    display: flex;
    align-items: center;
    gap: 10px;
    flex-shrink: 0;
}

.cat-search-wrap { flex: 1; position: relative; }
.cat-search {
    width: 100%;
    padding: 10px 38px 10px 38px;
    border-radius: 10px;
    border: 1.5px solid #cbd5e1;
    font-size: 15px;
    background: #f8fafc;
    font-family: inherit;
    transition: border-color .15s;
}
.cat-search:focus {
    outline: none;
    border-color: #1a56db;
    background: #fff;
    box-shadow: 0 0 0 3px rgba(26,86,219,.08);
}
.cat-search-ico {
    position: absolute; left: 12px; top: 50%;
    transform: translateY(-50%);
    color: #94a3b8; font-size: 15px; pointer-events: none;
}
.cat-search-clear {
    position: absolute; right: 10px; top: 50%;
    transform: translateY(-50%);
    color: #94a3b8; font-size: 13px;
    display: none; cursor: pointer;
    width: 22px; height: 22px;
    background: #e2e8f0; border-radius: 50%;
    align-items: center; justify-content: center;
}
.cat-search-clear.visible { display: flex; }

/* Botón de filtros */
.btn-filter {
    position: relative;
    width: 44px; height: 44px;
    border-radius: 10px;
    border: 1.5px solid #e2e8f0;
    background: #fff;
    display: flex; align-items: center; justify-content: center;
    font-size: 16px; color: #64748b;
    flex-shrink: 0; cursor: pointer;
}
.btn-filter.has-filter {
    border-color: #1a56db; background: #eff6ff; color: #1a56db;
}
.filter-dot {
    position: absolute; top: 6px; right: 6px;
    width: 7px; height: 7px; border-radius: 50%;
    background: #1a56db; border: 2px solid #fff;
    display: none;
}
.btn-filter.has-filter .filter-dot { display: block; }

/* Botón carrito */
.btn-cart {
    position: relative;
    background: #1a56db; color: #fff; border: none;
    border-radius: 10px; padding: 10px 14px;
    font-size: 14px; font-weight: 700;
    display: flex; align-items: center; gap: 7px;
    flex-shrink: 0; cursor: pointer; white-space: nowrap;
}
.btn-cart:active { filter: brightness(.92); }
.cart-badge {
    background: #fff; color: #1a56db;
    font-size: 11px; font-weight: 800;
    min-width: 20px; height: 20px;
    border-radius: 20px; padding: 0 4px;
    display: flex; align-items: center; justify-content: center;
}

/* Chips activos de filtro (debajo de la barra) */
.active-filters {
    display: none;
    padding: 6px 12px;
    background: #fff;
    border-bottom: 1px solid #e2e8f0;
    gap: 6px;
    flex-wrap: wrap;
    align-items: center;
}
.active-filters.visible { display: flex; }
.filter-chip-active {
    display: inline-flex; align-items: center; gap: 5px;
    background: #eff6ff; color: #1a56db;
    border: 1px solid #bfdbfe;
    border-radius: 20px; padding: 4px 10px;
    font-size: 12px; font-weight: 600;
    text-decoration: none;
}
.filter-chip-active i { font-size: 10px; }

/* ══ GRID ════════════════════════════════════════════════════ */
.cat-grid-wrap {
    flex: 1; overflow-y: auto;
    padding: 10px;
    /* Scroll suave en iOS */
    -webkit-overflow-scrolling: touch;
}

.cat-grid {
    display: grid;
    /* 4 columnas en horizontal, 2 en vertical — adaptable */
    grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
    gap: 9px;
}

/* ── Tarjeta ── */
.p-card {
    background: #fff;
    border-radius: 12px;
    border: 1.5px solid #e2e8f0;
    display: flex; flex-direction: column;
    overflow: hidden;
    -webkit-tap-highlight-color: transparent;
    transition: transform .1s, box-shadow .1s;
    position: relative; /* Para poder fijar íconos encima */
}
.p-card:active { transform: scale(.97); box-shadow: 0 2px 12px rgba(26,86,219,.1); }

/* Ícono link admin sobre la imagen */
.btn-admin-link {
    position: absolute;
    top: 6px; right: 6px;
    width: 28px; height: 28px;
    background: rgba(255,255,255,.9);
    border: 1px solid #e2e8f0;
    border-radius: 6px;
    display: flex; align-items: center; justify-content: center;
    color: #64748b; font-size: 12px;
    cursor: pointer;
    text-decoration: none;
    z-index: 10;
}
.btn-admin-link:active { background: #e2e8f0; color: #1a56db; }

.p-img {
    height: 76px;
    display: flex; align-items: center; justify-content: center;
    font-size: 36px;
    background: linear-gradient(135deg, #f8fafc, #eef2ff);
    flex-shrink: 0;
}

.p-body { padding: 8px 9px; flex: 1; display: flex; flex-direction: column; gap: 2px; }

.p-marca {
    font-size: 9.5px; font-weight: 800;
    text-transform: uppercase; letter-spacing: .5px;
    color: #1a56db; opacity: .75;
    white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
}
.p-nombre {
    font-size: 12px; font-weight: 700; color: #0f172a;
    line-height: 1.25;
    /* Exactamente 2 líneas */
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
    min-height: 30px;
}
.p-precio {
    font-family: 'Sora', sans-serif;
    font-size: 15px; font-weight: 800; color: #0f172a;
    margin-top: 3px;
}
.p-stock {
    font-size: 10px; font-weight: 600;
    display: flex; align-items: center; gap: 3px;
    margin-top: auto; padding-top: 2px;
}
.p-stock.ok     { color: #16a34a; }
.p-stock.bajo   { color: #d97706; }
.p-stock.agotado{ color: #dc2626; }

.p-foot { padding: 7px 8px 9px; display: flex; gap: 6px; }
.btn-add {
    flex: 1; height: 38px;
    background: #1a56db; color: #fff; border: none;
    border-radius: 9px; font-size: 13px; font-weight: 700;
    display: flex; align-items: center; justify-content: center; gap: 5px;
    cursor: pointer;
    -webkit-tap-highlight-color: transparent;
}
.btn-add:active { filter: brightness(.9); }
.btn-add:disabled { background: #e2e8f0; color: #94a3b8; }
.btn-info-art {
    width: 38px; height: 38px; flex-shrink: 0;
    background: #f1f5f9; color: #64748b;
    border: 1.5px solid #e2e8f0; border-radius: 9px;
    display: flex; align-items: center; justify-content: center;
    font-size: 14px; cursor: pointer;
}
.btn-info-art:active { background: #e2e8f0; }

/* Paginación compacta */
.cat-pager {
    display: flex; justify-content: center;
    gap: 5px; padding: 10px 0 4px;
}
.pg-btn {
    width: 36px; height: 36px; border-radius: 9px;
    border: 1.5px solid #e2e8f0; background: #fff;
    display: flex; align-items: center; justify-content: center;
    font-size: 13px; font-weight: 700; text-decoration: none; color: #64748b;
}
.pg-btn.on { background: #1a56db; border-color: #1a56db; color: #fff; }

/* Empty */
.cat-empty {
    text-align: center; padding: 60px 20px; color: #94a3b8;
}

/* ══ BOTTOM SHEET DE FILTROS ═════════════════════════════════ */
.filters-overlay {
    display: none;
    position: fixed; inset: 0;
    background: rgba(15,23,42,.35);
    z-index: 400;
}
.filters-overlay.open { display: block; }

.filters-sheet {
    position: fixed;
    left: 0; right: 0; bottom: 0;
    background: #fff;
    border-radius: 20px 20px 0 0;
    box-shadow: 0 -8px 30px rgba(0,0,0,.12);
    z-index: 401;
    transform: translateY(100%);
    transition: transform .28s cubic-bezier(.4,0,.2,1);
    max-height: 70vh;
    display: flex; flex-direction: column;
}
.filters-sheet.open { transform: translateY(0); }

.fs-handle {
    width: 40px; height: 4px; border-radius: 2px;
    background: #e2e8f0; margin: 10px auto 0;
    flex-shrink: 0;
}
.fs-header {
    display: flex; align-items: center; justify-content: space-between;
    padding: 12px 20px; border-bottom: 1px solid #f1f5f9;
    flex-shrink: 0;
}
.fs-title { font-family: 'Sora', sans-serif; font-size: 15px; font-weight: 700; color: #0f172a; }
.fs-clear {
    font-size: 13px; font-weight: 600; color: #dc2626;
    text-decoration: none; padding: 4px 8px; border-radius: 6px;
}
.fs-clear:active { background: #fee2e2; }

.fs-body {
    overflow-y: auto; padding: 14px 16px 20px;
    -webkit-overflow-scrolling: touch;
}

.fs-section { margin-bottom: 18px; }
.fs-section-title {
    font-size: 10.5px; font-weight: 800; text-transform: uppercase;
    letter-spacing: .7px; color: #94a3b8; margin-bottom: 8px;
    display: flex; align-items: center; gap: 5px;
}

/* Chips de filtro en el bottom sheet */
.fs-chips {
    display: flex; flex-wrap: wrap; gap: 7px;
}
.fs-chip {
    padding: 8px 14px;
    border-radius: 20px; border: 1.5px solid #e2e8f0;
    background: #f8fafc; color: #475569;
    font-size: 13px; font-weight: 500;
    text-decoration: none; cursor: pointer;
    transition: all .1s;
    -webkit-tap-highlight-color: transparent;
    white-space: nowrap;
}
.fs-chip:active { background: #e2e8f0; }
.fs-chip.on {
    background: #1a56db; color: #fff;
    border-color: #1a56db; font-weight: 700;
}

/* ══ CARRITO PANEL ═══════════════════════════════════════════ */
.cart-overlay {
    display: none;
    position: fixed; inset: 0;
    background: rgba(15,23,42,.3);
    z-index: 500;
}
.cart-overlay.open { display: block; }

.cart-panel {
    position: fixed;
    top: 0; right: 0; bottom: 0;
    width: 370px; max-width: 92vw;
    background: #fff; z-index: 501;
    display: flex; flex-direction: column;
    transform: translateX(110%);
    transition: transform .26s cubic-bezier(.4,0,.2,1);
    box-shadow: -8px 0 30px rgba(0,0,0,.1);
}
.cart-panel.open { transform: translateX(0); }

.cart-hd {
    padding: 14px 16px; background: #1a56db; color: #fff;
    display: flex; align-items: center; justify-content: space-between;
    flex-shrink: 0;
}
.cart-hd h3 { font-size: 15px; font-weight: 800; margin: 0; }
.cart-cls {
    width: 34px; height: 34px; border-radius: 8px;
    background: rgba(255,255,255,.18); border: none;
    color: #fff; font-size: 16px;
    display: flex; align-items: center; justify-content: center;
    cursor: pointer;
}

.cart-items {
    flex: 1; overflow-y: auto; padding: 6px 12px;
    -webkit-overflow-scrolling: touch;
}

.ci {
    display: flex; align-items: center; gap: 8px;
    padding: 10px 0; border-bottom: 1px solid #f1f5f9;
    animation: ciIn .15s ease;
}
@keyframes ciIn { from { opacity:0; transform: translateX(8px); } to { opacity:1; } }
.ci:last-child { border-bottom: none; }

.ci-emo {
    width: 36px; height: 36px; border-radius: 8px;
    background: #f1f5f9; display: flex; align-items: center;
    justify-content: center; font-size: 20px; flex-shrink: 0;
}
.ci-inf { flex: 1; min-width: 0; }
.ci-nom {
    font-size: 12px; font-weight: 700; color: #0f172a;
    white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
}
.ci-met { font-size: 11px; color: #64748b; margin-top: 1px; }

.ci-qty {
    display: flex; align-items: center; gap: 1px;
    background: #f1f5f9; border-radius: 9px; padding: 2px;
    flex-shrink: 0;
}
.ci-qb {
    width: 34px; height: 34px; border-radius: 7px;
    border: none; background: #fff; font-size: 18px; font-weight: 700;
    color: #0f172a; display: flex; align-items: center; justify-content: center;
    cursor: pointer; box-shadow: 0 1px 3px rgba(0,0,0,.06);
    -webkit-tap-highlight-color: transparent;
}
.ci-qb:active { background: #f1f5f9; }
.ci-qn {
    width: 30px; text-align: center;
    font-size: 14px; font-weight: 700; color: #0f172a;
}

.ci-sub {
    font-size: 13px; font-weight: 800; color: #1a56db;
    min-width: 64px; text-align: right; flex-shrink: 0;
}
.ci-del {
    width: 32px; height: 32px; border-radius: 8px;
    border: none; background: transparent; color: #cbd5e1;
    display: flex; align-items: center; justify-content: center;
    font-size: 13px; cursor: pointer; flex-shrink: 0;
}
.ci-del:active { color: #dc2626; background: #fee2e2; border-radius: 8px; }

.cart-ft {
    padding: 12px 14px;
    border-top: 1px solid #e2e8f0;
    background: #f8fafc;
    flex-shrink: 0;
}
.cart-tot {
    display: flex; justify-content: space-between;
    align-items: baseline; margin-bottom: 10px;
}
.cart-tot-lbl { font-size: 12px; font-weight: 600; color: #64748b; }
.cart-tot-val {
    font-family: 'Sora', sans-serif;
    font-size: 22px; font-weight: 900; color: #0f172a;
}
.cart-cli { display: flex; flex-direction: column; gap: 6px; margin-bottom: 10px; }
.cart-inp {
    border: 1.5px solid #cbd5e1; border-radius: 9px;
    padding: 9px 12px; font-size: 13px; font-family: inherit;
    background: #fff;
}
.cart-inp:focus { outline: none; border-color: #1a56db; }

.btn-caja {
    width: 100%; padding: 13px;
    background: #16a34a; color: #fff; border: none;
    border-radius: 11px; font-size: 15px; font-weight: 700;
    display: flex; align-items: center; justify-content: center; gap: 8px;
    cursor: pointer;
}
.btn-caja:active { filter: brightness(.92); }
.btn-caja:disabled { background: #e2e8f0; color: #94a3b8; cursor: default; }

/* ══ MODAL FICHA ═════════════════════════════════════════════ */
#modalFicha .modal-content {
    border-radius: 16px; border: none;
    box-shadow: 0 20px 60px rgba(0,0,0,.18);
}
.spec-chip {
    display: inline-flex; align-items: center; gap: 4px;
    background: #f1f5f9; border-radius: 7px;
    padding: 5px 9px; font-size: 12px; font-weight: 600; margin: 2px;
}
.spec-chip em { font-style: normal; font-weight: 400; color: #64748b; }

/* ── Toast ── */
.t-toast {
    position: fixed; bottom: calc(var(--tnav, 56px) + 12px); left: 50%;
    transform: translateX(-50%);
    background: #15803d; color: #fff;
    padding: 10px 20px; border-radius: 10px;
    font-size: 13px; font-weight: 700;
    z-index: 9999; pointer-events: none;
    box-shadow: 0 8px 25px rgba(0,0,0,.15);
    animation: tUp .2s ease both; white-space: nowrap;
}
.t-toast.err { background: #b91c1c; }
@keyframes tUp {
    from { opacity:0; transform:translateX(-50%) translateY(8px); }
    to   { opacity:1; transform:translateX(-50%) translateY(0); }
}

@media (max-width: 500px) {
    .cat-grid { grid-template-columns: repeat(2, 1fr); }
    .p-img { height: 62px; font-size: 30px; }
}
</style>
@endpush

@section('content')
@php
    $cur      = config('app_client.locale.currency_symbol', '$');
    $nivelDef = config('tablet.precio_nivel_default', 1);
    $catIcons = config('tablet.categoria_icons', []);
    $nItems   = $carrito->items->count();
    $totCart  = $carrito->total;

    // ¿Hay filtros activos?
    $hayFiltros = !empty($filters['categoria']) || !empty($filters['marca']);

    // Verificamos si el usuario actual tiene permisos de ver ficha administrativa
    $canSeeAdmin = auth()->user()?->can('inventario.articulos.ver');
@endphp

<div class="cat-shell">

    {{-- ── Barra superior ────────────────────────────────────── --}}
    <div class="cat-bar">
        <div class="cat-search-wrap">
            <i class="fas fa-search cat-search-ico" onclick="executeSearch()" style="cursor: pointer;" title="Buscar"></i>
            
            <input type="text" id="searchInput" class="cat-search"
                placeholder="Buscar producto, código o marca..."
                value="{{ $filters['search'] ?? '' }}"
                autocomplete="off"
                enterkeyhint="search">
                
            <div class="cat-search-clear {{ !empty($filters['search']) ? 'visible' : '' }}"
                id="clearSearch" onclick="clearSearch()">
                <i class="fas fa-times"></i>
            </div>
        </div>

        {{-- Botón de filtros --}}
        <button class="btn-filter {{ $hayFiltros ? 'has-filter' : '' }}"
                onclick="openFilters()" title="Filtros">
            <i class="fas fa-sliders"></i>
            <span class="filter-dot"></span>
        </button>

        {{-- Carrito --}}
        <button class="btn-cart" onclick="toggleCart()">
            <i class="fas fa-shopping-cart"></i>
            <span class="d-none d-sm-inline">Carrito</span>
            <span class="cart-badge" id="cartCount">{{ $nItems }}</span>
        </button>
    </div>

    {{-- Chips de filtros activos --}}
    @if($hayFiltros)
    <div class="active-filters visible">
        @if(!empty($filters['categoria']))
        <a href="{{ request()->fullUrlWithQuery(['categoria'=>'','page'=>1]) }}"
           class="filter-chip-active">
            <i class="fas fa-tag"></i>
            {{ $filters['categoria'] }}
            <i class="fas fa-times"></i>
        </a>
        @endif
        @if(!empty($filters['marca']))
        <a href="{{ request()->fullUrlWithQuery(['marca'=>'','page'=>1]) }}"
           class="filter-chip-active">
            <i class="fas fa-layer-group"></i>
            {{ $filters['marca'] }}
            <i class="fas fa-times"></i>
        </a>
        @endif
        <span style="font-size:11px;color:#94a3b8;">{{ number_format($total,0,'.','.') }} resultados</span>
    </div>
    @endif

    {{-- ── Grid de productos ──────────────────────────────────── --}}
    <div class="cat-grid-wrap">
        @if(empty($articulos))
        <div class="cat-empty">
            <i class="fas fa-box-open fa-2x d-block mb-3 opacity-25"></i>
            <p style="font-size:15px;font-weight:700;">Sin artículos disponibles</p>
            <p style="font-size:13px;">Prueba con otro término o limpia los filtros.</p>
        </div>
        @else
        <div class="cat-grid">
            @foreach($articulos as $art)
            @php
                $libre  = max(0,(float)($art['stock_actual']??0)-(float)($art['stock_com']??0));
                $sc     = $libre<=0?'agotado':($libre<=3?'bajo':'ok');
                $si     = $libre<=0?'fa-times-circle':($libre<=3?'fa-exclamation-circle':'fa-check-circle');
                $emoji  = $catIcons[$art['categoria']??''] ?? ($catIcons['default']??'📦');
                $precio = (float)($art["precio{$nivelDef}"]??$art['precio1']??0);
            @endphp
            <div class="p-card">
                {{-- Botón Admin link directo si tiene permiso --}}
                @if($canSeeAdmin)
                <a href="{{ route('articulos.show', $art['codigo']) }}" target="_blank" class="btn-admin-link" title="Ver ficha administrativa 360°">
                    <i class="fas fa-external-link-alt"></i>
                </a>
                @endif

                <div class="p-img">{{ $emoji }}</div>
                <div class="p-body">
                    @if(!empty($art['linea']))
                    <div class="p-marca">{{ $art['linea'] }}</div>
                    @endif
                    <div class="p-nombre" title="{{ $art['descripcion'] }}">
                        {{ $art['descripcion'] }}
                    </div>
                    <div class="p-precio">{{ $cur }} {{ number_format($precio,2,',','.') }}</div>
                    <div class="p-stock {{ $sc }}">
                        <i class="fas {{ $si }}" style="font-size:9px;"></i>
                        @if($libre<=0) Sin stock
                        @elseif($libre<=3) {{ number_format($libre,0) }} ud
                        @else {{ number_format($libre,0) }} disp.
                        @endif
                    </div>
                </div>
                <div class="p-foot">
                    <button class="btn-add"
                            onclick="addToCart('{{ $art['codigo'] }}')"
                            {{ $libre<=0?'disabled':'' }}>
                        <i class="fas fa-plus" style="font-size:11px;"></i>
                        Agregar
                    </button>
                    <button class="btn-info-art"
                            onclick="showFicha('{{ $art['codigo'] }}')"
                            title="Ver detalles del producto">
                        <i class="fas fa-expand-alt"></i>
                    </button>
                </div>
            </div>
            @endforeach
        </div>

        {{-- Paginación --}}
        @if($totalPages > 1)
        <div class="cat-pager">
            @if($page>1)
            <a href="{{ request()->fullUrlWithQuery(['page'=>$page-1]) }}" class="pg-btn">
                <i class="fas fa-chevron-left" style="font-size:10px;"></i>
            </a>
            @endif
            @for($p=max(1,$page-2);$p<=min($totalPages,$page+2);$p++)
            <a href="{{ request()->fullUrlWithQuery(['page'=>$p]) }}"
               class="pg-btn {{ $p===$page?'on':'' }}">{{ $p }}</a>
            @endfor
            @if($page<$totalPages)
            <a href="{{ request()->fullUrlWithQuery(['page'=>$page+1]) }}" class="pg-btn">
                <i class="fas fa-chevron-right" style="font-size:10px;"></i>
            </a>
            @endif
        </div>
        @endif
        @endif
    </div>

</div>

{{-- ══ BOTTOM SHEET DE FILTROS ════════════════════════════════ --}}
<div class="filters-overlay" id="filtersOverlay" onclick="closeFilters()"></div>
<div class="filters-sheet" id="filtersSheet">
    <div class="fs-handle"></div>
    <div class="fs-header">
        <span class="fs-title">
            <i class="fas fa-sliders me-2" style="color:#1a56db;"></i>
            Filtros del Catálogo
        </span>
        <a href="{{ request()->fullUrlWithQuery(['categoria'=>'','marca'=>'','page'=>1]) }}"
           class="fs-clear">
            <i class="fas fa-times me-1"></i> Limpiar
        </a>
    </div>
    <div class="fs-body">

        {{-- Categorías --}}
        @if(!empty($categorias))
        <div class="fs-section">
            <div class="fs-section-title">
                <i class="fas fa-tag" style="color:#1a56db;"></i>
                Categoría
            </div>
            <div class="fs-chips">
                <a href="{{ request()->fullUrlWithQuery(['categoria'=>'','page'=>1]) }}"
                   class="fs-chip {{ empty($filters['categoria']) ? 'on' : '' }}">
                   Todas
                </a>
                @foreach($categorias as $cv => $cl)
                @php $val = is_string($cv) ? $cv : $cl; @endphp
                <a href="{{ request()->fullUrlWithQuery(['categoria'=>$val,'page'=>1]) }}"
                   class="fs-chip {{ $filters['categoria']===$val ? 'on' : '' }}">
                   {{ $cl }}
                </a>
                @endforeach
            </div>
        </div>
        @endif

        {{-- Marcas --}}
        @if(!empty($marcas))
        <div class="fs-section">
            <div class="fs-section-title">
                <i class="fas fa-layer-group" style="color:#7c3aed;"></i>
                Marca / Línea
            </div>
            <div class="fs-chips">
                <a href="{{ request()->fullUrlWithQuery(['marca'=>'','page'=>1]) }}"
                   class="fs-chip {{ empty($filters['marca']) ? 'on' : '' }}">
                   Todas
                </a>
                @foreach(array_slice($marcas,0,20) as $m)
                <a href="{{ request()->fullUrlWithQuery(['marca'=>$m,'page'=>1]) }}"
                   class="fs-chip {{ $filters['marca']===$m ? 'on' : '' }}">
                   {{ $m }}
                </a>
                @endforeach
            </div>
        </div>
        @endif

    </div>
</div>

{{-- ══ CARRITO PANEL ══════════════════════════════════════════ --}}
<div class="cart-overlay" id="cartOverlay" onclick="toggleCart()"></div>
<div class="cart-panel" id="cartPanel">
    <div class="cart-hd">
        <h3><i class="fas fa-shopping-cart me-2"></i>Pedido Actual</h3>
        <button class="cart-cls" onclick="toggleCart()"><i class="fas fa-times"></i></button>
    </div>

    <div class="cart-items" id="cartItems">
        @forelse($carrito->items as $item)
        @php $eI = $catIcons[$item->articulo_categoria??''] ?? ($catIcons['default']??'📦'); @endphp
        <div class="ci" id="ci-{{ $item->id }}">
            <div class="ci-emo">{{ $eI }}</div>
            <div class="ci-inf">
                <div class="ci-nom" title="{{ $item->articulo_descripcion }}">
                    {{ $item->articulo_descripcion }}
                </div>
                <div class="ci-met">
                    {{ $cur }} {{ number_format($item->precio_unitario,2,',','.') }}
                    · P{{ $item->precio_nivel }}
                </div>
            </div>
            <div class="ci-qty">
                <button class="ci-qb"
                        onclick="updQty({{ $item->id }},{{ $item->cantidad-1 }})">−</button>
                <span class="ci-qn" id="qn-{{ $item->id }}">
                    {{ number_format($item->cantidad,0) }}
                </span>
                <button class="ci-qb"
                        onclick="updQty({{ $item->id }},{{ $item->cantidad+1 }})">+</button>
            </div>
            <div class="ci-sub" id="sub-{{ $item->id }}">
                {{ $cur }} {{ number_format($item->subtotal,2,',','.') }}
            </div>
            <button class="ci-del" onclick="delItem({{ $item->id }})">
                <i class="fas fa-trash"></i>
            </button>
        </div>
        @empty
        <div id="cartEmpty" style="text-align:center;padding:60px 20px;color:#94a3b8;">
            <i class="fas fa-shopping-cart fa-2x d-block mb-3 opacity-25"></i>
            <p style="font-weight:700;font-size:14px;">El carrito está vacío</p>
            <p style="font-size:12px;">Agrega productos del catálogo</p>
        </div>
        @endforelse
    </div>

    <div class="cart-ft">
        <div class="cart-tot">
            <span class="cart-tot-lbl">Monto Estimado</span>
            <span class="cart-tot-val" id="cartTotal">
                {{ $cur }} {{ number_format($totCart,2,',','.') }}
            </span>
        </div>
        <div class="cart-cli">
            <input type="text" class="cart-inp" id="cliNombre"
                   value="{{ $carrito->cliente_nombre }}"
                   placeholder="Nombre del cliente (opcional)">
            <input type="text" class="cart-inp" id="cliTel"
                   value="{{ $carrito->cliente_telefono }}"
                   placeholder="Teléfono de contacto (opcional)">
        </div>
        <button class="btn-caja" id="btnCaja"
                onclick="sendToCaja()"
                {{ $nItems===0?'disabled':'' }}>
            <i class="fas fa-cash-register"></i>
            Enviar Pedido a Caja
        </button>
    </div>
</div>

{{-- ══ MODAL FICHA ════════════════════════════════════════════ --}}
<div class="modal fade" id="modalFicha" tabindex="-1">
    <div class="modal-dialog modal-md modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header" style="border:none;padding:16px 20px 4px;">
                <h5 class="modal-title fw-bold" id="fichaTitle"
                    style="font-size:16px;color:#0f172a;">—</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="fichaBody" style="padding:0 20px 20px;">
                <div class="text-center py-4">
                    <i class="fas fa-spinner fa-spin fa-2x text-primary"></i>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
(function(){
'use strict';
const CSRF  = document.querySelector('meta[name=csrf-token]')?.content ?? '';
const SYM   = '{{ $cur }}';
const ICONS = {!! json_encode($catIcons) !!};
const fmtM  = n => `${SYM} ${parseFloat(n).toLocaleString('es-VE',{minimumFractionDigits:2,maximumFractionDigits:2})}`;
const HAS_ADMIN_ROLE = {{ $canSeeAdmin ? 'true' : 'false' }};

/* ═══════════════════════════════════════════════
   FILTROS (Bottom Sheet)
═══════════════════════════════════════════════ */
window.openFilters = () => {
    document.getElementById('filtersSheet').classList.add('open');
    document.getElementById('filtersOverlay').classList.add('open');
};
window.closeFilters = () => {
    document.getElementById('filtersSheet').classList.remove('open');
    document.getElementById('filtersOverlay').classList.remove('open');
};

// Exponer para el closeAll del layout padre
window.closeCartAndFilters = () => {
    closeFilters();
    document.getElementById('cartPanel').classList.remove('open');
    document.getElementById('cartOverlay').classList.remove('open');
};

/* ═══════════════════════════════════════════════
   CARRITO
═══════════════════════════════════════════════ */
window.toggleCart = () => {
    document.getElementById('cartPanel').classList.toggle('open');
    document.getElementById('cartOverlay').classList.toggle('open');
    closeFilters();
};

/* ─ Fetch helper ─ */
async function jFetch(url, method='GET', body=null) {
    const opts = {
        method,
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF }
    };
    if (body) opts.body = JSON.stringify(body);
    const r = await fetch(url, opts);
    return r.json();
}

/* ─ Agregar al carrito — inserta en DOM sin reload ─ */
window.addToCart = async (codigo) => {
    const r = await jFetch('{{ route('tablet.carrito.agregar') }}', 'POST', { codigo, cantidad: 1 });
    if (!r.success) { toast(r.message, 'err'); return; }

    setCount(r.carrito_items);
    setTotal(r.carrito_total);
    toast('✅ ' + r.message);

    // Insertar ítem en el DOM del carrito sin recargar la página
    if (r.item) {
        const itemId = r.item.id;
        const existing = document.getElementById(`ci-${itemId}`);

        if (existing) {
            // Ya existe — actualizar cantidad y subtotal
            const qn  = document.getElementById(`qn-${itemId}`);
            const sub = document.getElementById(`sub-${itemId}`);
            if (qn)  qn.textContent  = Math.round(r.item.cantidad);
            if (sub) sub.textContent = fmtM(r.item.subtotal);
        } else {
            // Nuevo ítem — construir el HTML e insertar
            const emoji = ICONS[r.item.articulo_categoria] || ICONS['default'] || '📦';
            const html  = `
                <div class="ci" id="ci-${itemId}">
                    <div class="ci-emo">${emoji}</div>
                    <div class="ci-inf">
                        <div class="ci-nom">${r.item.articulo_descripcion}</div>
                        <div class="ci-met">${fmtM(r.item.precio_unitario)} · P${r.item.precio_nivel}</div>
                    </div>
                    <div class="ci-qty">
                        <button class="ci-qb" onclick="updQty(${itemId},${r.item.cantidad-1})">−</button>
                        <span class="ci-qn" id="qn-${itemId}">${Math.round(r.item.cantidad)}</span>
                        <button class="ci-qb" onclick="updQty(${itemId},${r.item.cantidad+1})">+</button>
                    </div>
                    <div class="ci-sub" id="sub-${itemId}">${fmtM(r.item.subtotal)}</div>
                    <button class="ci-del" onclick="delItem(${itemId})">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>`;

            const container = document.getElementById('cartItems');
            // Quitar empty state si existe
            document.getElementById('cartEmpty')?.remove();
            container.insertAdjacentHTML('beforeend', html);
        }
    }

    // Habilitar el botón de caja
    document.getElementById('btnCaja').disabled = false;
};

/* ─ Actualizar cantidad ─ */
window.updQty = async (id, qty) => {
    if (qty < 0) return;
    const r = await jFetch(`/tablet/carrito/item/${id}`, 'PATCH', { cantidad: qty });
    if (!r.success) return;

    if (qty === 0) {
        document.getElementById(`ci-${id}`)?.remove();
    } else {
        const qEl  = document.getElementById(`qn-${id}`);
        const sEl  = document.getElementById(`sub-${id}`);
        if (qEl) qEl.textContent = qty;
        if (sEl && r.items) {
            const it = r.items.find(i => i.id === id);
            if (it) sEl.textContent = fmtM(it.subtotal);
        }
    }

    setCount(r.carrito_items ?? document.querySelectorAll('.ci').length);
    setTotal(r.carrito_total);
    checkEmpty();
};

/* ─ Eliminar ítem ─ */
window.delItem = async (id) => {
    const r = await jFetch(`/tablet/carrito/item/${id}`, 'DELETE');
    if (!r.success) return;
    document.getElementById(`ci-${id}`)?.remove();
    setCount(r.carrito_items);
    setTotal(r.carrito_total);
    checkEmpty();
};

/* ─ Enviar a caja ─ */
window.sendToCaja = async () => {
    const btn = document.getElementById('btnCaja');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Procesando...';

    const r = await jFetch('{{ route('tablet.carrito.enviar') }}', 'POST', {
        cliente_nombre:   document.getElementById('cliNombre')?.value ?? '',
        cliente_telefono: document.getElementById('cliTel')?.value ?? '',
    });

    if (r.success) {
        toast('✅ ' + r.message);
        setTimeout(() => location.reload(), 1500);
    } else {
        toast(r.message, 'err');
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-cash-register"></i> Enviar Pedido a Caja';
    }
};

/* ═══════════════════════════════════════════════
   VER FICHA DEL ARTÍCULO
═══════════════════════════════════════════════ */
window.showFicha = async (codigo) => {
    // 1. Mostrar cargador y abrir modal
    const modal = new bootstrap.Modal(document.getElementById('modalFicha'));
    const body  = document.getElementById('fichaBody');
    const title = document.getElementById('fichaTitle');

    body.innerHTML = '<div class="text-center py-4"><i class="fas fa-spinner fa-spin fa-2x text-primary"></i></div>';
    modal.show();

    // 2. Fetch data
    const r = await fetch(`/tablet/articulo/${encodeURIComponent(codigo)}`);
    const d = await r.json();

    if (d.error) { body.innerHTML = `<p class="text-danger">${d.error}</p>`; return; }

    const a     = d.articulo;
    const libre = Math.max(0, (parseFloat(a.stock_actual)||0) - (parseFloat(a.stock_com)||0));
    const sc    = libre<=0 ? '#dc2626' : (libre<=3 ? '#d97706' : '#16a34a');
    const em    = ICONS[a.categoria] || ICONS['default'] || '📦';

    title.textContent = a.descripcion;

    const specs = (a.specs||[]).map(s =>
        `<div class="spec-chip"><strong>${s.label}:</strong> <em>${s.valor}</em></div>`
    ).join('') || '<p class="text-muted small mb-0">Sin especificaciones registradas.</p>';

    const precios = [1,2,3,4].map(n => {
        const p = parseFloat(a[`precio${n}`]||0);
        if (!p) return '';
        return `
            <div style="text-align:center;padding:8px;border-radius:9px;
                        background:#f8fafc;border:1.5px solid ${n===1?'#1a56db':'#e2e8f0'};">
                <div style="font-size:9px;color:#94a3b8;font-weight:700;text-transform:uppercase;">
                    Precio ${n}
                </div>
                <div style="font-size:15px;font-weight:800;color:${n===1?'#1a56db':'#0f172a'};">
                    ${fmtM(p)}
                </div>
            </div>`;
    }).join('');

    // Botón directo a la Ficha Administrativa 360°
    const adminLinkHtml = HAS_ADMIN_ROLE
        ? `<div class="mt-2 text-center">
             <a href="/articulos/${a.codigo}" target="_blank" class="text-primary text-decoration-none" style="font-size: 11px; font-weight: 700;">
                 <i class="fas fa-external-link-alt me-1"></i>Ver Ficha Administrativa 360°
             </a>
           </div>`
        : '';

    body.innerHTML = `
        <div class="row g-2">
            <div class="col-4 text-center border-end pe-3">
                <div style="font-size:58px;line-height:1;margin-bottom:8px;">${em}</div>
                <div style="font-size:10px;color:#1a56db;font-weight:700;text-transform:uppercase;margin-bottom:2px;">
                    ${a.linea||''}
                </div>
                <div style="font-size:11px;color:#64748b;margin-bottom:6px;">
                    ${a.categoria||''}
                    ${a.modelo ? `<br><strong>${a.modelo}</strong>` : ''}
                </div>
                <div style="font-size:14px;font-weight:800;color:${sc};margin-bottom:4px;">
                    ${libre<=0 ? '❌ Sin stock' : `✅ ${libre} Uds`}
                </div>
                <div style="font-size:10px;color:#94a3b8;">
                    Ref: ${a.codigo}
                    ${a.codigo_barras ? `<br>${a.codigo_barras}` : ''}
                </div>
                ${adminLinkHtml}
            </div>
            <div class="col-8 ps-3">
                <p style="font-size:10px;font-weight:800;text-transform:uppercase;color:#94a3b8;margin-bottom:6px;">
                    Especificaciones
                </p>
                <div class="mb-3">${specs}</div>
                <p style="font-size:10px;font-weight:800;text-transform:uppercase;color:#94a3b8;margin-bottom:6px;">
                    Precios
                </p>
                <div style="display:grid;grid-template-columns:repeat(2,1fr);gap:6px;margin-bottom:14px;">
                    ${precios}
                </div>
                <button onclick="addToCart('${a.codigo}');
                                 bootstrap.Modal.getInstance(document.getElementById('modalFicha')).hide();"
                        class="btn btn-primary w-100 py-2 fw-bold"
                        ${libre<=0?'disabled':''}>
                    <i class="fas fa-plus me-2"></i>Agregar al Pedido
                </button>
            </div>
        </div>`;
};

/* ═══════════════════════════════════════════════
   HELPERS GLOBALES
═══════════════════════════════════════════════ */
function setCount(n) {
    document.getElementById('cartCount').textContent = n;
    document.getElementById('btnCaja').disabled = (n == 0);
}

function setTotal(v) {
    const el = document.getElementById('cartTotal');
    if (el) el.textContent = fmtM(v);
}

function checkEmpty() {
    if (!document.querySelector('.ci')) {
        document.getElementById('cartItems').innerHTML = `
            <div id="cartEmpty" style="text-align:center;padding:60px 20px;color:#94a3b8;">
                <i class="fas fa-shopping-cart fa-2x d-block mb-3 opacity-25"></i>
                <p style="font-weight:700;font-size:14px;">El carrito está vacío</p>
            </div>`;
    }
}

function toast(msg, tipo='') {
    const e = document.createElement('div');
    e.className = `t-toast ${tipo}`;
    e.textContent = msg;
    document.body.appendChild(e);
    setTimeout(() => e.remove(), 2500);
}

/* ─ Limpiar Búsqueda — expuesta al window ─ */
window.clearSearch = () => {
    const u = new URL(window.location.href);
    u.searchParams.delete('search');
    u.searchParams.set('page', '1');
    window.location.href = u.toString();
};

// Nueva función centralizada para ejecutar la búsqueda
window.executeSearch = () => {
    const searchInput = document.getElementById('searchInput');
    const u = new URL(window.location.href);
    
    if (searchInput.value.trim() === '') {
        u.searchParams.delete('search');
    } else {
        u.searchParams.set('search', searchInput.value.trim());
    }
    
    u.searchParams.set('page', '1');
    window.location.href = u.toString();
};

window.clearSearch = () => {
    const u = new URL(window.location.href);
    u.searchParams.delete('search');
    u.searchParams.set('page', '1');
    window.location.href = u.toString();
};

const searchInput = document.getElementById('searchInput');
const clearBtn    = document.getElementById('clearSearch');

// El evento 'input' AHORA SOLO sirve para mostrar/ocultar la X, NO recarga la página
searchInput?.addEventListener('input', function() {
    clearBtn?.classList.toggle('visible', this.value.trim().length > 0);
});

// Buscar únicamente al presionar Enter en el teclado físico o táctil
searchInput?.addEventListener('keydown', function(e) {
    if (e.key === 'Enter') {
        e.preventDefault();
        executeSearch();
    }
});

})();
</script>
@endpush

@endsection
