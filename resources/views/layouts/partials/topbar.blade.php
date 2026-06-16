{{--
    layouts/partials/topbar.blade.php
    ─────────────────────────────────────────────────────────────────────────
    Fusión de:
      • topbar.blade.php original  → Flatpickr, breadcrumb, sidebar toggle, ERP dot
      • menu_nav.blade.php premium → Lanzador de módulos, alertas, dropdown usuario

    Secciones:
      1. CSS inline exclusivo del topbar
      2. <header> con:
           A. Toggle sidebar
           B. Breadcrumb
           C. Selector de fechas (se oculta con @section('hide_daterange', true))
           D. Indicador ERP
           E. Lanzador de módulos  (@can por módulo)
           F. Centro de alertas    (@can seguridad.dashboard)
           G. Dropdown de usuario
      3. @push('scripts')  →  Flatpickr + Refresh cache
--}}

{{-- ═══════════════════════════════════════════════════════════════════ --}}
{{-- CSS EXCLUSIVO DEL TOPBAR                                           --}}
{{-- ═══════════════════════════════════════════════════════════════════ --}}
@once
<style>
    /* ── Layout ──────────────────────────────────────────────────────── */
    .app-topbar {
        height: var(--topbar-height, 64px);
        background: #ffffff;
        border-bottom: 1px solid #eaecf4;
        box-shadow: 0 4px 20px rgba(0,0,0,.03);
        display: flex;
        align-items: center;
        padding: 0 20px;
        gap: 10px;
        position: sticky;
        top: 0;
        z-index: 1020;
        flex-shrink: 0;
    }

    /* ── Toggle sidebar ──────────────────────────────────────────────── */
    .topbar-toggle {
        width: 36px; height: 36px;
        border: none; background: transparent;
        border-radius: 8px;
        display: flex; align-items: center; justify-content: center;
        color: #b7b9cc;
        cursor: pointer;
        transition: all .15s;
        flex-shrink: 0;
    }
    .topbar-toggle:hover { background: #f8f9fc; color: #4e73df; }

    /* ── Breadcrumb ──────────────────────────────────────────────────── */
    .topbar-breadcrumb {
        flex: 1;
        display: flex;
        align-items: center;
        gap: 6px;
        font-size: 13px;
        color: #b7b9cc;
        min-width: 0;
    }
    .topbar-breadcrumb .current {
        font-family: var(--font-display, 'Sora', sans-serif);
        font-weight: 600;
        font-size: 15px;
        color: #5a5c69;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    /* ── Selector de fechas ──────────────────────────────────────────── */
    .date-range-badge {
        display: flex;
        align-items: center;
        gap: 7px;
        background: #f8f9fc;
        border: 1px solid #eaecf4;
        border-radius: 9px;
        padding: 6px 12px;
        font-size: 12.5px;
        font-weight: 500;
        color: #858796;
        cursor: pointer;
        transition: all .2s;
        white-space: nowrap;
    }
    .date-range-badge:hover {
        border-color: #4e73df;
        color: #4e73df;
        background: #f0f4ff;
    }

    /* ── Separador vertical ──────────────────────────────────────────── */
    .topbar-sep {
        width: 1px; height: 22px;
        background: #eaecf4;
        flex-shrink: 0;
    }

    /* ── Indicador ERP ───────────────────────────────────────────────── */
    .erp-dot {
        width: 8px; height: 8px;
        border-radius: 50%;
        background: #1cc88a;
        box-shadow: 0 0 0 3px rgba(28,200,138,.15);
        flex-shrink: 0;
        cursor: default;
    }
    .erp-dot.offline { background: #e74a3b; box-shadow: 0 0 0 3px rgba(231,74,59,.15); }

    /* ── Dropdown base ───────────────────────────────────────────────── */
    .topbar-dropdown {
        position: relative;
    }
    .topbar-icon-btn {
        width: 36px; height: 36px;
        border-radius: 9px;
        display: flex; align-items: center; justify-content: center;
        color: #b7b9cc;
        cursor: pointer;
        transition: all .15s;
        background: transparent;
        border: none;
        position: relative;
        text-decoration: none;
    }
    .topbar-icon-btn:hover { background: #f8f9fc; color: #4e73df; }
    .topbar-icon-btn .badge-dot {
        position: absolute;
        top: 5px; right: 5px;
        width: 8px; height: 8px;
        border-radius: 50%;
        background: #e74a3b;
        border: 2px solid #fff;
    }
    .topbar-icon-btn .badge-count {
        position: absolute;
        top: 2px; right: 0px;
        background: #e74a3b;
        color: #fff;
        font-size: 9px;
        font-weight: 800;
        padding: 1px 4px;
        border-radius: 10px;
        border: 2px solid #fff;
        line-height: 1.2;
    }

    .dropdown-panel {
        display: none;
        position: absolute;
        right: 0; top: calc(100% + 10px);
        background: #fff;
        border-radius: 14px;
        box-shadow: 0 10px 40px rgba(0,0,0,.12);
        overflow: hidden;
        z-index: 9999;
        animation: dropIn .18s ease both;
    }
    .dropdown-panel.open { display: block; }

    @keyframes dropIn {
        from { opacity:0; transform: translateY(-6px) scale(.98); }
        to   { opacity:1; transform: translateY(0)   scale(1); }
    }

    .dp-header {
        background: linear-gradient(135deg, #4e73df 0%, #2e59d9 100%);
        color: #fff;
        padding: 12px 16px;
        font-size: 11.5px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .6px;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }

    /* ── Lanzador de módulos ─────────────────────────────────────────── */
    .module-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 8px;
        padding: 14px;
        width: 300px;
        background: #fff;
    }
    .module-item {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        padding: 13px 8px;
        border-radius: 10px;
        cursor: pointer;
        text-decoration: none !important;
        color: #5a5c69;
        transition: all .15s;
        border: 1.5px solid transparent;
    }
    .module-item:hover {
        background: #f8f9fc;
        border-color: #eaecf4;
        transform: translateY(-2px);
        color: #4e73df;
    }
    .module-item-icon {
        width: 38px; height: 38px;
        border-radius: 10px;
        display: flex; align-items: center; justify-content: center;
        font-size: 1rem;
        margin-bottom: 7px;
        color: #fff;
        box-shadow: 0 3px 8px rgba(0,0,0,.12);
    }
    .module-item span {
        font-size: 10.5px;
        font-weight: 700;
        text-align: center;
        line-height: 1.2;
    }

    /* ── Notificaciones ──────────────────────────────────────────────── */
    .notif-panel { width: 320px; }
    .notif-item {
        display: flex;
        align-items: flex-start;
        gap: 12px;
        padding: 12px 16px;
        border-bottom: 1px solid #f1f3f9;
        text-decoration: none !important;
        transition: background .15s;
    }
    .notif-item:hover { background: #f8f9fc; }
    .notif-item:last-of-type { border-bottom: none; }
    .notif-avatar {
        width: 34px; height: 34px;
        border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
        color: #fff;
        font-size: 0.8rem;
        flex-shrink: 0;
    }
    .notif-body p { font-size: 12px; color: #5a5c69; margin: 0; line-height: 1.4; }
    .notif-body strong { font-size: 12.5px; color: #3d3f4e; display: block; margin-bottom: 2px; }
    .notif-time { font-size: 10.5px; color: #b7b9cc; margin-top: 3px; display: block; }
    .notif-footer {
        text-align: center;
        padding: 10px;
        font-size: 12px;
        font-weight: 700;
        color: #4e73df;
        background: #f8f9fc;
        text-decoration: none;
        display: block;
        transition: background .15s;
    }
    .notif-footer:hover { background: #eef0fb; }

    /* ── Dropdown usuario ────────────────────────────────────────────── */
    .user-panel { width: 240px; }
    .user-panel-head {
        padding: 18px 16px 14px;
        background: #f8f9fc;
        border-bottom: 1px solid #eaecf4;
        text-align: center;
    }
    .user-avatar-lg {
        width: 48px; height: 48px;
        border-radius: 50%;
        background: linear-gradient(135deg, #4e73df 0%, #2e59d9 100%);
        color: #fff;
        display: flex; align-items: center; justify-content: center;
        font-size: 18px; font-weight: 800;
        margin: 0 auto 8px;
        box-shadow: 0 3px 10px rgba(78,115,223,.3);
    }
    .user-panel-head h6 { font-size: 13.5px; font-weight: 700; color: #3d3f4e; margin: 0; }
    .user-panel-head small { font-size: 11px; color: #858796; }
    .user-panel-head .role-badge {
        display: inline-block;
        margin-top: 5px;
        background: #4e73df;
        color: #fff;
        font-size: 9.5px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .5px;
        padding: 2px 8px;
        border-radius: 20px;
    }
    .user-menu-item {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 10px 16px;
        font-size: 13px;
        color: #5a5c69;
        text-decoration: none !important;
        transition: all .15s;
        cursor: pointer;
        border: none;
        background: transparent;
        width: 100%;
        text-align: left;
    }
    .user-menu-item:hover { background: #f0f4ff; color: #4e73df; }
    .user-menu-item i { width: 18px; text-align: center; color: #b7b9cc; font-size: 13px; flex-shrink: 0; }
    .user-menu-item:hover i { color: #4e73df; }
    .user-menu-item.danger { color: #e74a3b; }
    .user-menu-item.danger:hover { background: #fff5f5; color: #c0392b; }
    .user-menu-item.danger i { color: #e74a3b; }
    .user-menu-divider { height: 1px; background: #eaecf4; margin: 4px 0; }

    /* ── Avatar pequeño en topbar ────────────────────────────────────── */
    .topbar-avatar {
        width: 34px; height: 34px;
        border-radius: 50%;
        background: linear-gradient(135deg, #4e73df 0%, #2e59d9 100%);
        color: #fff;
        display: flex; align-items: center; justify-content: center;
        font-size: 13px; font-weight: 800;
        cursor: pointer;
        box-shadow: 0 2px 6px rgba(78,115,223,.3);
        transition: box-shadow .15s;
        flex-shrink: 0;
    }
    .topbar-avatar:hover { box-shadow: 0 4px 12px rgba(78,115,223,.4); }

    /* ── Nombre usuario en topbar (solo desktop) ─────────────────────── */
    .topbar-user-info {
        display: flex;
        flex-direction: column;
        align-items: flex-end;
        line-height: 1.2;
    }
    .topbar-user-name {
        font-size: 12.5px;
        font-weight: 700;
        color: #5a5c69;
        white-space: nowrap;
    }
    .topbar-user-role {
        font-size: 10px;
        font-weight: 700;
        color: #4e73df;
        text-transform: uppercase;
        letter-spacing: .4px;
    }

    @media (max-width: 768px) {
        .topbar-user-info { display: none; }
        .date-range-badge span { display: none; }
        .date-range-badge { padding: 6px 8px; }
    }
</style>
@endonce

{{-- ═══════════════════════════════════════════════════════════════════ --}}
{{-- TOPBAR HTML                                                        --}}
{{-- ═══════════════════════════════════════════════════════════════════ --}}
<header class="app-topbar">

    {{-- ── A. Toggle sidebar ───────────────────────────────────────── --}}
    <button class="topbar-toggle" data-sidebar-toggle aria-label="Abrir/cerrar menú">
        <i class="fas fa-bars" style="font-size:15px;"></i>
    </button>

    {{-- ── B. Breadcrumb ───────────────────────────────────────────── --}}
    <div class="topbar-breadcrumb">
        <a href="{{ route('dashboard.index') }}"
           style="color:#b7b9cc;text-decoration:none;">
            <i class="fas fa-home" style="font-size:11px;"></i>
        </a>
        <span style="color:#d1d3e2;font-size:11px;">/</span>
        @unless(View::hasSection('hide_breadcrumb'))
            @yield('breadcrumb', '<span class="current">Dashboard</span>')
        @endunless
    </div>


    {{-- ── C. Selector de fechas ───────────────────────────────────── --}}
    @unless(View::hasSection('hide_daterange'))
    <form method="GET" action="{{ url()->current() }}" id="dateRangeForm">
        @foreach(request()->except(['from', 'to']) as $key => $value)
            <input type="hidden" name="{{ $key }}" value="{{ $value }}">
        @endforeach

        <div class="date-range-badge" id="dateRangeTrigger" title="Seleccionar período">
            <i class="fas fa-calendar-alt" style="font-size:12px;color:#4e73df;"></i>
            <span id="dateRangeDisplay">
                {{ \Carbon\Carbon::parse(request('from', now()->startOfMonth()))->format(config('app_client.locale.date_format', 'd/m/Y')) }}
                &nbsp;—&nbsp;
                {{ \Carbon\Carbon::parse(request('to', now()))->format(config('app_client.locale.date_format', 'd/m/Y')) }}
            </span>
            <i class="fas fa-chevron-down" style="font-size:9px;"></i>
        </div>

        <input type="text"  id="flatpickrRange" name="_range" style="display:none;"
               value="{{ request('from', now()->startOfMonth()->toDateString()) }} to {{ request('to', now()->toDateString()) }}">
        <input type="hidden" id="inputFrom" name="from" value="{{ request('from', now()->startOfMonth()->toDateString()) }}">
        <input type="hidden" id="inputTo"   name="to"   value="{{ request('to', now()->toDateString()) }}">
    </form>
    @endunless

    {{-- ── D. Indicador ERP ────────────────────────────────────────── --}}
    <div class="erp-dot" id="erpStatusDot" title="ERP conectado"></div>

    <div class="topbar-sep"></div>

    {{-- ── E. Botón refresh caché ──────────────────────────────────── --}}
    @can('seguridad.dashboard')
    <button type="button" class="topbar-icon-btn" id="btnRefreshCache"
            title="Limpiar caché y refrescar datos">
        <i class="fas fa-sync-alt" id="refreshIcon" style="font-size:14px;"></i>
    </button>
    @endcan

    <div class="topbar-sep"></div>

    {{-- ══════════════════════════════════════════════════════════════ --}}
    {{-- ── F. LANZADOR DE MÓDULOS ─────────────────────────────────── --}}
    {{-- ══════════════════════════════════════════════════════════════ --}}
    <div class="topbar-dropdown" id="ddModules">
        <button class="topbar-icon-btn" onclick="toggleDropdown('ddModules')"
                title="Módulos del sistema">
            <i class="fas fa-th-large" style="font-size:15px;"></i>
        </button>

        <div class="dropdown-panel" id="ddModules-panel">
            <div class="dp-header">
                <span>Módulos</span>
                <i class="fas fa-rocket" style="font-size:12px;"></i>
            </div>
            <div class="module-grid">

                {{-- Dashboard --}}
                @can('gerencia.dashboard.ver')
                <a href="{{ route('dashboard.index') }}" class="module-item">
                    <div class="module-item-icon" style="background:linear-gradient(135deg,#4e73df,#2e59d9);">
                        <i class="fas fa-chart-pie"></i>
                    </div>
                    <span>Dashboard</span>
                </a>
                @endcan

                {{-- Financiero --}}
                @canany(['financiero.margenes.ver','financiero.reporte.bonos'])
                <a href="{{ route('financiero.margenes') }}" class="module-item">
                    <div class="module-item-icon" style="background:linear-gradient(135deg,#1cc88a,#13855c);">
                        <i class="fas fa-percentage"></i>
                    </div>
                    <span>Márgenes</span>
                </a>
                @endcanany

                @can('financiero.reporte.bonos')
                <a href="{{ route('financiero.bonos') }}" class="module-item">
                    <div class="module-item-icon" style="background:linear-gradient(135deg,#36b9cc,#258391);">
                        <i class="fas fa-award"></i>
                    </div>
                    <span>Bonos</span>
                </a>
                @endcan

                {{-- Inventario --}}
                @can('inventario.stock-critico')
                <a href="{{ route('inventario.stock-critico') }}" class="module-item">
                    <div class="module-item-icon" style="background:linear-gradient(135deg,#e74a3b,#c0392b);">
                        <i class="fas fa-triangle-exclamation"></i>
                    </div>
                    <span>Stock Crítico</span>
                </a>
                @endcan

                @can('inventario.entradas.ver')
                <a href="{{ route('inventario.entradas') }}" class="module-item">
                    <div class="module-item-icon" style="background:linear-gradient(135deg,#7c3aed,#5b21b6);">
                        <i class="fas fa-boxes-stacked"></i>
                    </div>
                    <span>Entradas</span>
                </a>
                @endcan

                @can('inventario.salidas.auditar')
                <a href="{{ route('inventario.salidas') }}" class="module-item">
                    <div class="module-item-icon" style="background:linear-gradient(135deg,#ea580c,#c2410c);">
                        <i class="fas fa-arrow-right-from-bracket"></i>
                    </div>
                    <span>Salidas</span>
                </a>
                @endcan

                {{-- Artículos --}}
                @can('inventario.articulos.ver')
                <a href="{{ route('articulos.index') }}" class="module-item">
                    <div class="module-item-icon" style="background:linear-gradient(135deg,#0891b2,#0e7490);">
                        <i class="fas fa-barcode"></i>
                    </div>
                    <span>Artículos</span>
                </a>
                @endcan
                @can('inventario.articulos.rendimiento')
                <a href="{{ route('articulos.rendimiento') }}" class="module-item">
                    <div class="module-item-icon" style="background:linear-gradient(135deg,#d97706,#b45309);">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <span>Rendimiento</span>
                </a>
                @endcan

                {{-- Seguridad (solo super_admin / con permiso) --}}
                @can('seguridad.usuarios.ver')
                <a href="{{ route('admin.users.index') }}" class="module-item">
                    <div class="module-item-icon" style="background:linear-gradient(135deg,#e74a3b,#c0392b);">
                        <i class="fas fa-user-shield"></i>
                    </div>
                    <span>Usuarios</span>
                </a>
                @endcan

                @can('seguridad.permisos.ver')
                <a href="{{ route('admin.permissions.index') }}" class="module-item">
                    <div class="module-item-icon" style="background:linear-gradient(135deg,#f63eed,#dd0a82);">
                        <i class="fas fa-shield-alt"></i>
                    </div>
                    <span>Permisos</span>
                </a>
                @endcan

                @can('seguridad.roles.ver')
                <a href="{{ route('admin.roles.index') }}" class="module-item">
                    <div class="module-item-icon" style="background:linear-gradient(135deg,#f6c23e,#dda20a);">
                        <i class="fas fa-key"></i>
                    </div>
                    <span>Roles</span>
                </a>
                @endcan
                <a href="{{ route('about') }}" class="module-item">
                    <div class="module-item-icon" style="background:linear-gradient(135deg,#3e94f6,#380add);">
                        <i class="fas fa-fw fa-hands-helping"></i>
                    </div>
                    <span>Acerca de:</span>
                </a>
                <a href="{{ route('profile') }}" class="module-item">
                    <div class="module-item-icon" style="background:linear-gradient(135deg,#3ef663,#0add5e);">
                        <i class="fas fa-id-badge"></i>
                    </div>
                    <span>Mi Perfil</span>
                </a>


                <form method="POST" action="{{ route('logout') }}" id="logoutForm">
                    @csrf
                    <button type="submit" class="module-item">
                        <div class="module-item-icon" style="background:linear-gradient(135deg,#f65a3e,#dd0a0a);">
                            <i class="fas fa-sign-out-alt"></i>
                        </div>
                        <span>Logout</span>
                    </button>
                </form>

            </div>
        </div>
    </div>

    {{-- ══════════════════════════════════════════════════════════════ --}}
    {{-- ── G. CENTRO DE ALERTAS ────────────────────────────────────── --}}
    {{-- ══════════════════════════════════════════════════════════════ --}}
    <div class="topbar-dropdown" id="ddAlerts">
        <button class="topbar-icon-btn" onclick="toggleDropdown('ddAlerts')"
                title="Notificaciones y alertas">
            <i class="fas fa-bell" style="font-size:15px;"></i>
            {{-- El badge solo aparece si hay alertas activas --}}
            @can('inventario.stock-critico')
                <span class="badge-dot" id="alertBadge"></span>
            @endcan
        </button>

        <div class="dropdown-panel notif-panel" id="ddAlerts-panel">
            <div class="dp-header">
                <span>Centro de Alertas</span>
                <i class="fas fa-shield-halved" style="font-size:12px;"></i>
            </div>
            <div>
                {{-- Alerta de Stock Crítico --}}
                @can('inventario.stock-critico')
                <a class="notif-item" href="{{ route('inventario.stock-critico') }}">
                    <div class="notif-avatar" style="background:#e74a3b;">
                        <i class="fas fa-triangle-exclamation"></i>
                    </div>
                    <div class="notif-body">
                        <strong>Stock Crítico</strong>
                        <p>Hay artículos con stock por debajo del mínimo. Revisión requerida.</p>
                        <span class="notif-time">
                            <i class="fas fa-clock me-1"></i>Actualizado hace
                            {{ config('cache_ttl.stock_critico', 120) / 60 }} min
                        </span>
                    </div>
                </a>
                @endcan

                {{-- Alerta de Márgenes negativos --}}
                @can('financiero.alertas.margen.ver')
                <a class="notif-item" href="{{ route('financiero.margenes') }}">
                    <div class="notif-avatar" style="background:#f6c23e;">
                        <i class="fas fa-percent" style="color:#5a5c69;"></i>
                    </div>
                    <div class="notif-body">
                        <strong>Alertas de Margen</strong>
                        <p>Revisa artículos con margen por debajo del umbral configurado.</p>
                        <span class="notif-time">
                            <i class="fas fa-clock me-1"></i>Módulo Financiero
                        </span>
                    </div>
                </a>
                @endcan

                {{-- Alerta de Salidas No Comerciales --}}
                @can('inventario.salidas.auditar')
                <a class="notif-item" href="{{ route('inventario.salidas') }}">
                    <div class="notif-avatar" style="background:#ea580c;">
                        <i class="fas fa-arrow-right-from-bracket"></i>
                    </div>
                    <div class="notif-body">
                        <strong>Salidas No Comerciales</strong>
                        <p>Monitoreo de ajustes de inventario activo este mes.</p>
                        <span class="notif-time">
                            <i class="fas fa-clock me-1"></i>Auditoría activa
                        </span>
                    </div>
                </a>
                @endcan

                {{-- Alerta de Entradas Pendientes --}}
                @can('inventario.entradas.ver')
                <a class="notif-item" href="{{ route('inventario.entradas') }}">
                    <div class="notif-avatar" style="background:#7c3aed;">
                        <i class="fas fa-boxes-stacked"></i>
                    </div>
                    <div class="notif-body">
                        <strong>Entradas vs Compras</strong>
                        <p>Verifica órdenes de compra pendientes de recepción.</p>
                        <span class="notif-time">
                            <i class="fas fa-clock me-1"></i>Módulo Inventario
                        </span>
                    </div>
                </a>
                @endcan

            </div>
            <a class="notif-footer" href="{{ route('inventario.index') }}">
                Ver todas las alertas
                <i class="fas fa-arrow-right ms-1" style="font-size:10px;"></i>
            </a>
        </div>
    </div>

    <div class="topbar-sep"></div>

    {{-- ══════════════════════════════════════════════════════════════ --}}
    {{-- ── H. DROPDOWN DE USUARIO ──────────────────────────────────── --}}
    {{-- ══════════════════════════════════════════════════════════════ --}}
    <div class="topbar-dropdown" id="ddUser">
        <div style="display:flex;align-items:center;gap:10px;cursor:pointer;"
             onclick="toggleDropdown('ddUser')">
            {{-- Nombre + rol (solo desktop) --}}
            <div class="topbar-user-info">
                <span class="topbar-user-name">
                    {{ Auth::user()?->full_name ?? 'Usuario' }}
                </span>
                <span class="topbar-user-role">
                    {{ Auth::user()?->roles?->first()?->name ?? 'Sin rol' }}
                </span>
            </div>
            {{-- Avatar --}}
            <div class="topbar-avatar">
                {{ strtoupper(substr(Auth::user()?->name ?? 'U', 0, 1)) }}
            </div>
        </div>

        <div class="dropdown-panel user-panel" id="ddUser-panel">

            {{-- Cabecera con datos del usuario --}}
            <div class="user-panel-head">
                <div class="user-avatar-lg">
                    {{ strtoupper(substr(Auth::user()?->name ?? 'U', 0, 1)) }}
                </div>
                <h6>{{ Auth::user()?->name ?? 'Usuario' }}</h6>
                <small>{{ Auth::user()?->email ?? '' }}</small>
                <div class="role-badge">
                    {{ Auth::user()?->roles?->first()?->name ?? 'Usuario' }}
                </div>
            </div>

            {{-- Menú del usuario --}}
            <div style="padding:6px 0;">

                {{-- Mi perfil --}}
                <a href="{{ route('profile') }}" class="user-menu-item">
                    <i class="fas fa-id-badge"></i>
                    Mi Perfil
                </a>

                {{-- Cambiar campo de costo (solo financiero) --}}
                @can('financiero.config.costo.editar')
                <a href="{{ route('financiero.margenes') }}" class="user-menu-item">
                    <i class="fas fa-sliders"></i>
                    Configurar Costos
                </a>
                @endcan

                {{-- Panel de Seguridad --}}
                @can('seguridad.dashboard')
                <div class="user-menu-divider"></div>
                <a href="{{ route('admin.users.index') }}" class="user-menu-item">
                    <i class="fas fa-user-shield"></i>
                    Usuarios
                </a>
                <a href="{{ route('admin.roles.index') }}" class="user-menu-item">
                    <i class="fas fa-key"></i>
                    Roles
                </a>
                <a href="{{ route('admin.permissions.index') }}" class="user-menu-item">
                    <i class="fas fa-shield-alt"></i>
                    Permisos
                </a>
                @endcan

                {{-- Reporte consolidado (solo auditor/admin) --}}
                @can('inventario.reporte.consolidado.ver')
                <div class="user-menu-divider"></div>
                <a href="{{ route('inventario.reporte') }}" class="user-menu-item">
                    <i class="fas fa-clipboard-list"></i>
                    Reporte de Inventario
                </a>
                @endcan

                <div class="user-menu-divider"></div>

                {{-- Acerca de --}}
                <a href="{{ route('about') }}" class="user-menu-item">
                    <i class="fas fa-hands-helping"></i>
                    Acerca de {{ config('app_client.system.name', 'ERP') }}
                </a>

                <div class="user-menu-divider"></div>

                {{-- Cerrar sesión --}}
                <form method="POST" action="{{ route('logout') }}" id="logoutForm">
                    @csrf
                    <button type="submit" class="user-menu-item danger">
                        <i class="fas fa-right-from-bracket"></i>
                        Cerrar Sesión
                    </button>
                </form>

            </div>
        </div>
    </div>

</header>

{{-- ═══════════════════════════════════════════════════════════════════ --}}
{{-- SCRIPTS                                                            --}}
{{-- ═══════════════════════════════════════════════════════════════════ --}}
@push('scripts')
<script>
(function () {
    'use strict';

    // ── Gestión de dropdowns custom ───────────────────────────────────────
    // Cierra todos y abre el solicitado (toggle)
    window.toggleDropdown = function (id) {
        const allPanels = document.querySelectorAll('.dropdown-panel');
        const target    = document.getElementById(id + '-panel');
        const isOpen    = target?.classList.contains('open');

        allPanels.forEach(p => p.classList.remove('open'));

        if (!isOpen && target) {
            target.classList.add('open');
        }
    };

    // Cerrar al hacer click fuera
    document.addEventListener('click', function (e) {
        const insideDropdown = e.target.closest('.topbar-dropdown');
        if (!insideDropdown) {
            document.querySelectorAll('.dropdown-panel').forEach(p => p.classList.remove('open'));
        }
    });

    // ── Flatpickr — Date range selector ──────────────────────────────────
    @unless(View::hasSection('hide_daterange'))
    const fromVal = '{{ request('from', now()->startOfMonth()->toDateString()) }}';
    const toVal   = '{{ request('to', now()->toDateString()) }}';

    if (typeof flatpickr !== 'undefined') {
        flatpickr('#flatpickrRange', {
            mode:          'range',
            dateFormat:    'Y-m-d',
            locale:        'es',
            defaultDate:   [fromVal, toVal],
            maxDate:       'today',
            disableMobile: true,

            onReady(_, __, instance) {
                document.getElementById('dateRangeTrigger')
                    ?.addEventListener('click', () => instance.open());
            },

            onChange(selectedDates) {
                if (selectedDates.length !== 2) return;
                const fmt  = d => d.toISOString().split('T')[0];
                const from = fmt(selectedDates[0]);
                const to   = fmt(selectedDates[1]);

                document.getElementById('inputFrom').value = from;
                document.getElementById('inputTo').value   = to;

                // Actualizar texto del badge
                const fmtDisplay = d => {
                    const [y, m, day] = d.split('-');
                    return `${day}/${m}/${y}`;
                };
                const display = document.getElementById('dateRangeDisplay');
                if (display) {
                    display.textContent = fmtDisplay(from) + ' — ' + fmtDisplay(to);
                }

                setTimeout(() => document.getElementById('dateRangeForm')?.submit(), 300);
            },
        });
    }
    @endunless

    // ── Refresh cache button ──────────────────────────────────────────────
    document.getElementById('btnRefreshCache')?.addEventListener('click', function () {
        const icon = document.getElementById('refreshIcon');
        icon?.classList.add('fa-spin');
        this.disabled = true;

        const url = new URL(window.location.href);
        url.searchParams.delete('bust');
        setTimeout(() => window.location.href = url.toString(), 600);
    });

    // ── ERP Health dot (ping silencioso cada 60s) ─────────────────────────
    function checkErpStatus() {
        const dot = document.getElementById('erpStatusDot');
        if (!dot) return;

        fetch('{{ route('dashboard.index') }}?ping=1', {
            method: 'HEAD',
            headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.content ?? '' }
        })
        .then(r => {
            dot.classList.toggle('offline', !r.ok);
            dot.title = r.ok ? 'ERP conectado ✓' : 'ERP sin respuesta';
        })
        .catch(() => {
            dot.classList.add('offline');
            dot.title = 'ERP sin respuesta';
        });
    }

    checkErpStatus();
    setInterval(checkErpStatus, 60_000);

})();
</script>
@endpush
