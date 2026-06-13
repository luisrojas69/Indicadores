{{-- articulos/index.blade.php --}}
@extends('layouts.app')
@section('title', 'Catálogo deArtículos')

@section('breadcrumb')
    <span class="current">Artículos</span>
@endsection

@section('hide_daterange', true)

@push('styles')
<style>
    .art-card {
        background: var(--card-bg);
        border-radius: 12px;
        border: 1px solid #e2e8f0;
        padding: 16px;
        transition: all .2s ease;
        text-decoration: none;
        display: block;
        color: inherit;
        position: relative;
        overflow: hidden;
    }
    .art-card:hover {
        transform: translateY(-3px);
        box-shadow: var(--card-shadow-hover);
        border-color: var(--brand-primary);
        color: inherit;
    }
    .art-card::before {
        content: '';
        position: absolute; top:0; left:0; right:0; height:3px;
        background: var(--brand-primary); opacity:0;
        transition: opacity .2s;
    }
    .art-card:hover::before { opacity: 1; }

    .art-placeholder {
        width:100%; height:90px;
        border-radius:8px;
        display:flex; align-items:center; justify-content:center;
        font-size:28px; margin-bottom:12px;
        font-family: var(--font-display);
        font-weight:800;
        letter-spacing:-1px;
    }

    .stock-micro {
        height:4px; border-radius:99px;
        background:#e2e8f0; overflow:hidden; margin-top:6px;
    }
    .stock-micro-fill { height:100%; border-radius:99px; }

    /* Paginación */
    .pagination-wrap { display:flex; align-items:center; gap:8px; flex-wrap:wrap; }
    .page-btn {
        width:34px; height:34px; border-radius:8px; border:1px solid #e2e8f0;
        display:flex; align-items:center; justify-content:center;
        font-size:12.5px; font-weight:600; text-decoration:none;
        color:var(--text-secondary); background:#fff; transition:all .15s;
    }
    .page-btn:hover { border-color:var(--brand-primary); color:var(--brand-primary); }
    .page-btn.active { background:var(--brand-primary); border-color:var(--brand-primary); color:#fff; }
    .page-btn.disabled { opacity:.4; pointer-events:none; }
</style>
@endpush

@section('content')

<div class="d-flex align-items-start justify-content-between flex-wrap gap-3 mb-4">
    <div>
        <h1 class="font-display mb-1" style="font-size:22px;font-weight:800;">Catálogo de Artículos</h1>
        <p class="mb-0" style="font-size:13px;color:var(--text-muted);">
            {{ number_format($total, 0, '.', '.') }} artículos en el ERP
        </p>
    </div>
    <a href="{{ route('articulos.rendimiento') }}"
       class="btn btn-sm btn-primary d-flex align-items-center gap-2"
       style="border-radius:9px;font-size:12.5px;">
        <i class="fas fa-chart-line"></i> Ver Rendimiento
    </a>
</div>

{{-- Barra de búsqueda --}}
<form method="GET" action="{{ route('articulos.index') }}" id="searchForm">
<div class="panel-card mb-4 p-3">
    <div class="row g-2 align-items-end">
        <div class="col-12 col-md-6">
            <div style="position:relative;">
                <input type="text" name="search"
                       value="{{ $filters['search'] }}"
                       placeholder="Buscar por código o descripción..."
                       class="form-control"
                       style="padding-left:38px;border-radius:10px;font-size:13px;"
                       autocomplete="off">
                <i class="fas fa-search" style="position:absolute;left:12px;top:50%;transform:translateY(-50%);
                   color:var(--text-muted);font-size:13px;"></i>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <input type="text" name="categoria"
                   value="{{ $filters['categoria'] }}"
                   placeholder="Categoría / Línea..."
                   class="form-control"
                   style="border-radius:10px;font-size:13px;">
        </div>
        <div class="col-6 col-md-3 d-flex gap-2">
            <button type="submit" class="btn btn-primary flex-fill" style="border-radius:10px;font-size:13px;">
                <i class="fas fa-filter me-1"></i> Filtrar
            </button>
            @if($filters['search'] || $filters['categoria'])
            <a href="{{ route('articulos.index') }}" class="btn btn-outline-secondary" style="border-radius:10px;">
                <i class="fas fa-times"></i>
            </a>
            @endif
        </div>
    </div>
</div>
</form>

{{-- Grid de artículos --}}
@if($articulos->isEmpty())
<div style="text-align:center;padding:80px 20px;color:var(--text-muted);">
    <i class="fas fa-box-open fa-3x d-block mb-4 opacity-25"></i>
    <p style="font-size:15px;font-weight:600;">No se encontraron artículos</p>
    <p style="font-size:13px;">Intenta con otro término de búsqueda.</p>
</div>
@else

<div class="row g-3 mb-4">
    @foreach($articulos as $art)
    @php
        $stockPct = ($art['stock_minimo'] ?? 0) > 0
            ? min(($art['stock_actual'] / $art['stock_minimo']) * 100, 100)
            : 100;
        $stockColor = $stockPct <= 20 ? '#dc2626' : ($stockPct <= 80 ? '#d97706' : '#059669');

        // Color de placeholder basado en el código
        $colors = ['#1a56db','#059669','#d97706','#7c3aed','#0891b2','#ea580c'];
        $colorIdx = crc32($art['codigo']) % count($colors);
        $placeholderColor = $colors[abs($colorIdx)];
        $initials = strtoupper(substr(str_replace(' ','',($art['descripcion'] ?? $art['codigo'])), 0, 2));
    @endphp
    <div class="col-12 col-sm-6 col-md-4 col-xl-3 animate-in">
        <a href="{{ route('articulos.show', $art['codigo']) }}" class="art-card">

            {{-- Placeholder visual --}}
            <div class="art-placeholder"
                 style="background:{{ $placeholderColor }}12;color:{{ $placeholderColor }};">
                {{ $initials }}
            </div>

            {{-- Código y marca --}}
            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:4px;">
                <span style="font-size:11px;font-weight:700;color:{{ $placeholderColor }};
                             font-family:var(--font-display);">
                    {{ $art['codigo'] }}
                </span>
                @if(!empty($art['marca']))
                <span style="font-size:10px;background:#f1f5f9;color:var(--text-muted);
                             padding:1px 7px;border-radius:6px;font-weight:600;">
                    {{ $art['marca'] }}
                </span>
                @endif
            </div>

            {{-- Descripción --}}
            <p style="font-size:12.5px;font-weight:600;color:var(--text-primary);
                      margin-bottom:10px;line-height:1.4;
                      display:-webkit-box;-webkit-line-clamp:2;
                      -webkit-box-orient:vertical;overflow:hidden;">
                {{ $art['descripcion'] }}
            </p>

            {{-- Stock --}}
            <div style="display:flex;justify-content:space-between;font-size:11px;margin-bottom:4px;">
                <span style="color:var(--text-muted);">Stock disponible</span>
                <span style="font-weight:700;color:{{ $stockColor }};">
                    {{ number_format($art['stock_actual'], 0, '.', '.') }} uds.
                </span>
            </div>
            <div class="stock-micro">
                <div class="stock-micro-fill"
                     style="width:{{ $stockPct }}%;background:{{ $stockColor }};"></div>
            </div>

        </a>
    </div>
    @endforeach
</div>

{{-- Paginación --}}
@if($totalPages > 1)
<div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
    <p style="font-size:12.5px;color:var(--text-muted);margin:0;">
        Mostrando {{ (($page-1)*$perPage)+1 }}–{{ min($page*$perPage, $total) }}
        de {{ number_format($total,0,'.','.') }} artículos
    </p>
    <div class="pagination-wrap">
        <a href="{{ request()->fullUrlWithQuery(['page' => max(1, $page-1)]) }}"
           class="page-btn {{ $page <= 1 ? 'disabled' : '' }}">
            <i class="fas fa-chevron-left" style="font-size:10px;"></i>
        </a>

        @for($p = max(1, $page-2); $p <= min($totalPages, $page+2); $p++)
        <a href="{{ request()->fullUrlWithQuery(['page' => $p]) }}"
           class="page-btn {{ $p === $page ? 'active' : '' }}">{{ $p }}</a>
        @endfor

        <a href="{{ request()->fullUrlWithQuery(['page' => min($totalPages, $page+1)]) }}"
           class="page-btn {{ $page >= $totalPages ? 'disabled' : '' }}">
            <i class="fas fa-chevron-right" style="font-size:10px;"></i>
        </a>
    </div>
</div>
@endif

@endif

@endsection
