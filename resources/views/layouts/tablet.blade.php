<!DOCTYPE html>
<html lang="{{ config('app_client.locale.language', 'es') }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="mobile-web-app-capable" content="yes">
    <title>@yield('title', 'Ventas') · {{ config('app_client.short_name') }}</title>

    <link rel="icon" type="image/x-icon" href="{{ asset(config('app_client.favicon', 'favicon.ico')) }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Sora:wght@400;600;700;800&family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    {{-- Corrección: Usar CDN para Bootstrap 5 --}}
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        :root {
            --tp: #1a56db;        /* primary */
            --tp-dk: #1347bf;     /* primary dark */
            --td: #0f172a;        /* dark text */
            --ts: #475569;        /* secondary text */
            --tm: #94a3b8;        /* muted text */
            --tb: #f8fafc;        /* background */
            --tbr: #e2e8f0;       /* border */
            --th: 52px;           /* header height */
            --tnav: 56px;         /* bottom nav height */
            --font-d: 'Sora', sans-serif;
            --font-b: 'DM Sans', sans-serif;
        }

        *, *::before, *::after { box-sizing: border-box; }

        html, body {
            margin: 0; padding: 0;
            width: 100%; height: 100%;
            overflow: hidden;
            background: var(--tb);
            font-family: var(--font-b);
            -webkit-text-size-adjust: 100%;
            -webkit-tap-highlight-color: transparent;
            touch-action: manipulation;
        }

        /* ══ SHELL ══════════════════════════════════════════════ */
        .t-app {
            display: flex;
            flex-direction: column;
            width: 100vw;
            height: 100vh;
            overflow: hidden;
        }

        /* ══ HEADER ══════════════════════════════════════════════
           Compacto. Solo logo, nombre de sesión y un menú de contexto.
           El vendedor no necesita más. */
        .t-header {
            height: var(--th);
            background: #fff;
            border-bottom: 1px solid var(--tbr);
            display: flex;
            align-items: center;
            padding: 0 14px;
            gap: 10px;
            flex-shrink: 0;
            position: relative;
            z-index: 100;
        }

        .t-brand {
            display: flex; align-items: center; gap: 9px;
            text-decoration: none; flex-shrink: 0;
        }
        .t-logo {
            width: 30px; height: 30px; border-radius: 7px;
            background: var(--tp); color: #fff;
            font-family: var(--font-d); font-weight: 800; font-size: 12px;
            display: flex; align-items: center; justify-content: center;
        }
        .t-brand-name {
            font-family: var(--font-d); font-size: 14px; font-weight: 700;
            color: var(--td); white-space: nowrap;
        }
        .t-brand-name small {
            font-size: 10px; font-weight: 400; color: var(--tm);
            display: block; line-height: 1;
        }

        /* Separador flex */
        .t-spacer { flex: 1; }

        /* Chip del vendedor */
        .t-user-chip {
            display: flex; align-items: center; gap: 6px;
            background: #f1f5f9; border-radius: 20px;
            padding: 5px 10px; font-size: 12px; font-weight: 600;
            color: var(--ts);
        }
        .t-user-dot {
            width: 7px; height: 7px; border-radius: 50%;
            background: #16a34a; flex-shrink: 0;
        }

        /* Botón de menú contextual (3 puntos) */
        .t-ctx-btn {
            width: 36px; height: 36px; border-radius: 9px;
            border: 1.5px solid var(--tbr); background: #fff;
            display: flex; align-items: center; justify-content: center;
            font-size: 16px; color: var(--ts); flex-shrink: 0;
            position: relative;
        }

        /* Dropdown del menú contextual */
        .t-ctx-menu {
            display: none;
            position: absolute;
            top: calc(var(--th) + 4px); right: 14px;
            background: #fff; border-radius: 12px;
            box-shadow: 0 8px 30px rgba(0,0,0,.14);
            border: 1px solid var(--tbr);
            min-width: 200px;
            z-index: 999;
            overflow: hidden;
        }
        .t-ctx-menu.open { display: block; }

        .t-ctx-item {
            display: flex; align-items: center; gap: 10px;
            padding: 13px 16px; font-size: 14px; font-weight: 500;
            color: var(--ts); text-decoration: none;
            border-bottom: 1px solid #f8fafc;
            transition: background .1s;
        }
        .t-ctx-item:last-child { border-bottom: none; }
        .t-ctx-item:active { background: #f1f5f9; }
        .t-ctx-item i { width: 18px; text-align: center; font-size: 15px; }
        .t-ctx-item.danger { color: #dc2626; }
        .t-ctx-item.danger i { color: #dc2626; }
        .t-ctx-sep { height: 1px; background: var(--tbr); margin: 2px 0; }

        /* ══ CONTENIDO ══════════════════════════════════════════ */
        .t-content {
            flex: 1;
            overflow: hidden;
            position: relative;
            /* Descuenta header + bottom nav */
            height: calc(100vh - var(--th) - var(--tnav));
        }

        /* ══ BOTTOM NAV ═════════════════════════════════════════
           Navegación del vendedor en la parte inferior — más natural
           para interacción con los pulgares en una tablet. */
        .t-nav {
            height: var(--tnav);
            background: #fff;
            border-top: 1px solid var(--tbr);
            display: flex;
            align-items: stretch;
            flex-shrink: 0;
        }

        .t-nav-item {
            flex: 1;
            display: flex; flex-direction: column;
            align-items: center; justify-content: center;
            gap: 3px;
            text-decoration: none;
            color: var(--tm);
            font-size: 10px; font-weight: 600;
            transition: all .1s;
            border-top: 2px solid transparent;
            position: relative;
        }
        .t-nav-item i { font-size: 18px; }
        .t-nav-item:active { background: #f8fafc; }
        .t-nav-item.active {
            color: var(--tp);
            border-top-color: var(--tp);
            background: #f8fcff;
        }

        /* Badge de cantidad en el nav del carrito */
        .t-nav-badge {
            position: absolute; top: 6px; left: 50%;
            transform: translateX(4px);
            background: #dc2626; color: #fff;
            font-size: 9px; font-weight: 800;
            min-width: 16px; height: 16px;
            border-radius: 20px; padding: 0 4px;
            display: flex; align-items: center; justify-content: center;
            border: 2px solid #fff;
        }

        /* ══ OVERLAY GLOBAL ════════════════════════════════════ */
        .t-overlay {
            display: none;
            position: fixed; inset: 0;
            background: rgba(15,23,42,.3);
            z-index: 300;
        }
        .t-overlay.open { display: block; }
    </style>

    @stack('styles')
</head>
<body>
<div class="t-app">

    {{-- ── HEADER ──────────────────────────────────────────── --}}
    <header class="t-header">
        <a href="{{ route('tablet.catalogo') }}" class="t-brand">
            <div class="t-logo">
                {{-- strtoupper(substr(config('app_client.short_name','BI'),0,2)) --}}
                <img src="{{ asset(config('app_client.logo-sidebar')) }}" alt="{{ config('app_client.short_name') }}" class="img-fluid">
            </div>
            <div class="t-brand-name">
                {{ config('app_client.short_name') }}
                <small>Terminal de Ventas</small>
            </div>
        </a>

        <div class="t-spacer"></div>

        {{-- Vendedor activo --}}
        <div class="t-user-chip">
            <span class="t-user-dot" title="Sesión activa"></span>
            <i class="far fa-user" style="font-size:11px;"></i>
            {{ auth()->user()?->name ?? 'Vendedor' }}
        </div>

        {{-- Menú contextual (3 puntos) — acciones secundarias --}}
        <button class="t-ctx-btn" id="ctxBtn" onclick="toggleCtx()" title="Más opciones">
            <i class="fas fa-ellipsis-vertical"></i>
        </button>
    </header>

    {{-- Menú contextual flotante --}}
    <div class="t-ctx-menu" id="ctxMenu">
        @can('vendedor.prepedido.gestionar')
        <a href="{{ route('tablet.mis_prepedidos') }}" class="t-ctx-item">
            <i class="fas fa-list-check" style="color:#1a56db;"></i>
            Mis Pre-Pedidos
        </a>
        @endcan
        @can('caja.prepedidos.ver')
        <a href="{{ route('caja.index') }}" class="t-ctx-item">
            <i class="fas fa-cash-register" style="color:#059669;"></i>
            Panel de Caja
        </a>
        @endcan
        <a href="{{ route('dashboard.index') }}" class="t-ctx-item">
            <i class="fas fa-chart-pie" style="color:#7c3aed;"></i>
            Dashboard
        </a>
        <div class="t-ctx-sep"></div>
        <form method="POST" action="{{ route('logout') }}" style="margin:0;">
            @csrf
            <button type="submit" class="t-ctx-item danger w-100"
                    style="border:none;background:none;width:100%;text-align:left;cursor:pointer;">
                <i class="fas fa-power-off"></i>
                Cerrar Sesión
            </button>
        </form>
    </div>

    {{-- ── CONTENIDO ────────────────────────────────────────── --}}
    <main class="t-content">
        @yield('content')
    </main>

    {{-- ── BOTTOM NAVIGATION ────────────────────────────────── --}}
    <nav class="t-nav">

        {{-- Catálogo --}}
        <a href="{{ route('tablet.catalogo') }}"
           class="t-nav-item {{ request()->routeIs('tablet.catalogo') ? 'active' : '' }}">
            <i class="fas fa-grid-2"></i>
            <span>Catálogo</span>
        </a>

        {{-- Mis Pre-Pedidos --}}
        @can('vendedor.prepedido.gestionar')
        <a href="{{ route('tablet.mis_prepedidos') }}"
           class="t-nav-item {{ request()->routeIs('tablet.mis_prepedidos') ? 'active' : '' }}">
            <i class="fas fa-list-check"></i>
            <span>Mis Pedidos</span>
        </a>
        @endcan

        {{-- Panel de Caja (si tiene permiso) --}}
        @can('caja.prepedidos.ver')
        <a href="{{ route('caja.index') }}"
           class="t-nav-item {{ request()->routeIs('caja.*') ? 'active' : '' }}">
            <i class="fas fa-cash-register"></i>
            <span>Caja</span>
            {{-- Badge de pendientes: se puede poblar vía JS o desde el controller --}}
            @if(isset($pendientesCaja) && $pendientesCaja > 0)
            <span class="t-nav-badge">{{ $pendientesCaja }}</span>
            @endif
        </a>
        @endcan

    </nav>

</div>

{{-- Overlay global (para cerrar menús al tocar fuera) --}}
<div class="t-overlay" id="tOverlay" onclick="closeAll()"></div>

{{-- Corrección: Usar CDN para JS de Bootstrap --}}
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
(function(){
    'use strict';

    /* ── Menú contextual de los 3 puntos ── */
    window.toggleCtx = () => {
        const menu = document.getElementById('ctxMenu');
        const overlay = document.getElementById('tOverlay');
        const isOpen = menu.classList.contains('open');
        closeAll();
        if (!isOpen) {
            menu.classList.add('open');
            overlay.classList.add('open');
        }
    };

    window.closeAll = () => {
        document.getElementById('ctxMenu')?.classList.remove('open');
        document.getElementById('tOverlay')?.classList.remove('open');
        // Cada vista puede exponer su propio closeAll extendido
        if (typeof window.closeCartAndFilters === 'function') {
            window.closeCartAndFilters();
        }
    };

})();
</script>

@stack('scripts')
</body>
</html>
