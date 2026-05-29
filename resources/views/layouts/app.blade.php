<!DOCTYPE html>
<html lang="{{ config('app_client.locale.language', 'es') }}" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description" content="{{ config('app_client.name') }} — Panel de Inteligencia de Negocios">

    <title>@yield('title', 'Dashboard') — {{ config('app_client.short_name') }}</title>

    {{-- Favicon --}}
    <link rel="icon" type="image/x-icon" href="{{ asset(config('app_client.favicon', 'favicon.ico')) }}">

    {{-- Google Fonts: Sora (display) + DM Sans (body) — elegante y legible para dashboards financieros --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Sora:wght@300;400;600;700;800&family=DM+Sans:ital,opsz,wght@0,9..40,300;0,9..40,400;0,9..40,500;0,9..40,600;1,9..40,400&display=swap" rel="stylesheet">

    {{-- Bootstrap 5 --}}
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">

    {{-- Font Awesome 6 --}}
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    {{-- Flatpickr — date range picker --}}
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/themes/material_blue.css">

    {{-- CSS Variables de Branding — inyectadas desde config/app_client.php --}}
    <style>
        :root {
            --brand-primary:      {{ config('app_client.brand.primary',      '#1a56db') }};
            --brand-primary-dark: {{ config('app_client.brand.primary_dark', '#1347bf') }};
            --brand-secondary:    {{ config('app_client.brand.secondary',    '#64748b') }};
            --brand-success:      {{ config('app_client.brand.success',      '#059669') }};
            --brand-warning:      {{ config('app_client.brand.warning',      '#d97706') }};
            --brand-danger:       {{ config('app_client.brand.danger',       '#dc2626') }};
            --brand-sidebar-bg:   {{ config('app_client.brand.sidebar_bg',   '#0f172a') }};
            --font-display:       'Sora', sans-serif;
            --font-body:          'DM Sans', sans-serif;

            /* Layout */
            --sidebar-width:      260px;
            --sidebar-collapsed:  72px;
            --topbar-height:      64px;
            --content-bg:         #f1f5f9;
            --card-bg:            #ffffff;
            --card-radius:        14px;
            --card-shadow:        0 1px 3px rgba(0,0,0,.06), 0 4px 16px rgba(0,0,0,.06);
            --card-shadow-hover:  0 4px 8px rgba(0,0,0,.08), 0 12px 32px rgba(0,0,0,.1);

            /* Typography */
            --text-primary:   #0f172a;
            --text-secondary: #64748b;
            --text-muted:     #94a3b8;

            /* Transitions */
            --transition-fast:   0.15s ease;
            --transition-normal: 0.25s ease;
        }

        *, *::before, *::after { box-sizing: border-box; }

        html, body {
            height: 100%;
            font-family: var(--font-body);
            background: var(--content-bg);
            color: var(--text-primary);
            font-size: 14px;
            line-height: 1.6;
            -webkit-font-smoothing: antialiased;
        }

        h1, h2, h3, h4, h5, h6,
        .font-display { font-family: var(--font-display); }

        /* ── Layout Shell ───────────────────────────────────── */
        .app-wrapper {
            display: flex;
            min-height: 100vh;
        }

        /* ── Sidebar ─────────────────────────────────────────── */
        .app-sidebar {
            width: var(--sidebar-width);
            min-height: 100vh;
            background: var(--brand-sidebar-bg);
            display: flex;
            flex-direction: column;
            position: fixed;
            top: 0; left: 0;
            z-index: 1030;
            transition: width var(--transition-normal);
            overflow: hidden;
        }

        .app-sidebar.collapsed {
            width: var(--sidebar-collapsed);
        }

        .sidebar-brand {
            height: var(--topbar-height);
            display: flex;
            align-items: center;
            padding: 0 20px;
            border-bottom: 1px solid rgba(255,255,255,.07);
            gap: 12px;
            flex-shrink: 0;
            text-decoration: none;
        }

        .sidebar-brand-logo {
            width: 32px; height: 32px;
            background: var(--brand-primary);
            border-radius: 8px;
            display: flex; align-items: center; justify-content: center;
            font-family: var(--font-display);
            font-weight: 800;
            font-size: 15px;
            color: #fff;
            flex-shrink: 0;
            letter-spacing: -0.5px;
        }

        .sidebar-brand-text {
            font-family: var(--font-display);
            font-weight: 700;
            font-size: 15px;
            color: #fff;
            white-space: nowrap;
            opacity: 1;
            transition: opacity var(--transition-normal);
            line-height: 1.2;
        }

        .sidebar-brand-text small {
            display: block;
            font-size: 10px;
            font-weight: 400;
            color: rgba(255,255,255,.45);
            font-family: var(--font-body);
            letter-spacing: .5px;
            text-transform: uppercase;
        }

        .app-sidebar.collapsed .sidebar-brand-text { opacity: 0; pointer-events: none; }

        /* Nav */
        .sidebar-nav {
            flex: 1;
            overflow-y: auto;
            overflow-x: hidden;
            padding: 12px 0;
            scrollbar-width: thin;
            scrollbar-color: rgba(255,255,255,.1) transparent;
        }

        .nav-section-label {
            font-size: 10px;
            font-weight: 600;
            letter-spacing: 1px;
            text-transform: uppercase;
            color: rgba(255,255,255,.3);
            padding: 16px 22px 6px;
            white-space: nowrap;
            overflow: hidden;
            transition: opacity var(--transition-normal);
        }

        .app-sidebar.collapsed .nav-section-label { opacity: 0; }

        .nav-item-link {
            display: flex;
            align-items: center;
            gap: 13px;
            padding: 10px 20px;
            color: rgba(255,255,255,.62);
            text-decoration: none;
            border-radius: 10px;
            margin: 1px 10px;
            transition: all var(--transition-fast);
            position: relative;
            white-space: nowrap;
        }

        .nav-item-link:hover {
            background: rgba(255,255,255,.08);
            color: rgba(255,255,255,.92);
        }

        .nav-item-link.active {
            background: var(--brand-primary);
            color: #fff;
            box-shadow: 0 2px 12px rgba(0,0,0,.25);
        }

        .nav-item-link .nav-icon {
            width: 20px;
            text-align: center;
            font-size: 15px;
            flex-shrink: 0;
        }

        .nav-item-link .nav-label {
            font-size: 13.5px;
            font-weight: 500;
            opacity: 1;
            transition: opacity var(--transition-normal);
        }

        .app-sidebar.collapsed .nav-item-link .nav-label { opacity: 0; }

        /* Tooltip en collapsed */
        .app-sidebar.collapsed .nav-item-link::after {
            content: attr(data-tooltip);
            position: absolute;
            left: calc(var(--sidebar-collapsed) + 8px);
            top: 50%; transform: translateY(-50%);
            background: #1e293b;
            color: #fff;
            font-size: 12px;
            font-weight: 500;
            padding: 5px 10px;
            border-radius: 6px;
            white-space: nowrap;
            opacity: 0;
            pointer-events: none;
            transition: opacity .15s;
            z-index: 9999;
        }
        .app-sidebar.collapsed .nav-item-link:hover::after { opacity: 1; }

        /* Sidebar footer */
        .sidebar-footer {
            padding: 14px;
            border-top: 1px solid rgba(255,255,255,.07);
            flex-shrink: 0;
        }

        .sidebar-user {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 8px 8px;
            border-radius: 10px;
            cursor: pointer;
            transition: background var(--transition-fast);
            text-decoration: none;
        }

        .sidebar-user:hover { background: rgba(255,255,255,.07); }

        .user-avatar {
            width: 34px; height: 34px;
            border-radius: 10px;
            background: var(--brand-primary);
            display: flex; align-items: center; justify-content: center;
            font-family: var(--font-display);
            font-weight: 700;
            font-size: 13px;
            color: #fff;
            flex-shrink: 0;
        }

        .user-info { overflow: hidden; }

        .user-name {
            font-size: 13px;
            font-weight: 600;
            color: #fff;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .user-role {
            font-size: 11px;
            color: rgba(255,255,255,.4);
            white-space: nowrap;
        }

        .app-sidebar.collapsed .user-info { display: none; }

        /* ── Main Content ─────────────────────────────────────── */
        .app-main {
            flex: 1;
            margin-left: var(--sidebar-width);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            transition: margin-left var(--transition-normal);
        }

        .app-main.sidebar-collapsed { margin-left: var(--sidebar-collapsed); }

        /* ── Topbar ──────────────────────────────────────────── */
        .app-topbar {
            height: var(--topbar-height);
            background: var(--card-bg);
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            align-items: center;
            padding: 0 24px;
            gap: 16px;
            position: sticky;
            top: 0;
            z-index: 1020;
            flex-shrink: 0;
        }

        .topbar-toggle {
            width: 36px; height: 36px;
            border: none; background: transparent;
            border-radius: 8px;
            display: flex; align-items: center; justify-content: center;
            color: var(--text-secondary);
            cursor: pointer;
            transition: all var(--transition-fast);
        }
        .topbar-toggle:hover { background: #f1f5f9; color: var(--text-primary); }

        .topbar-breadcrumb {
            flex: 1;
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 13px;
            color: var(--text-secondary);
        }

        .topbar-breadcrumb .current {
            font-family: var(--font-display);
            font-weight: 600;
            font-size: 15px;
            color: var(--text-primary);
        }

        .topbar-actions {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        /* Date range badge */
        .date-range-badge {
            display: flex;
            align-items: center;
            gap: 7px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 9px;
            padding: 6px 12px;
            font-size: 12.5px;
            font-weight: 500;
            color: var(--text-secondary);
            cursor: pointer;
            transition: all var(--transition-fast);
        }
        .date-range-badge:hover {
            border-color: var(--brand-primary);
            color: var(--brand-primary);
            background: #eff6ff;
        }

        /* ── Page Content ─────────────────────────────────────── */
        .app-content {
            flex: 1;
            padding: 24px;
        }

        /* ── KPI Cards ─────────────────────────────────────────── */
        .kpi-card {
            background: var(--card-bg);
            border-radius: var(--card-radius);
            padding: 22px 24px;
            box-shadow: var(--card-shadow);
            transition: all var(--transition-normal);
            border: 1px solid rgba(0,0,0,.04);
            position: relative;
            overflow: hidden;
        }

        .kpi-card:hover {
            box-shadow: var(--card-shadow-hover);
            transform: translateY(-2px);
        }

        .kpi-card::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0;
            height: 3px;
            background: var(--accent-color, var(--brand-primary));
            border-radius: var(--card-radius) var(--card-radius) 0 0;
        }

        .kpi-card.accent-success { --accent-color: var(--brand-success); }
        .kpi-card.accent-warning { --accent-color: var(--brand-warning); }
        .kpi-card.accent-danger  { --accent-color: var(--brand-danger);  }
        .kpi-card.accent-info    { --accent-color: #0ea5e9; }

        .kpi-icon-wrap {
            width: 44px; height: 44px;
            border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            font-size: 18px;
            background: color-mix(in srgb, var(--accent-color, var(--brand-primary)) 12%, transparent);
            color: var(--accent-color, var(--brand-primary));
            flex-shrink: 0;
        }

        .kpi-label {
            font-size: 11.5px;
            font-weight: 600;
            letter-spacing: .5px;
            text-transform: uppercase;
            color: var(--text-muted);
            margin-bottom: 4px;
        }

        .kpi-value {
            font-family: var(--font-display);
            font-size: 26px;
            font-weight: 700;
            color: var(--text-primary);
            line-height: 1.1;
            letter-spacing: -0.5px;
        }

        .kpi-delta {
            font-size: 12px;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 3px;
            padding: 2px 7px;
            border-radius: 20px;
            margin-top: 6px;
        }

        .kpi-delta.up   { background: #dcfce7; color: #15803d; }
        .kpi-delta.down { background: #fee2e2; color: #b91c1c; }
        .kpi-delta.flat { background: #f1f5f9; color: var(--text-secondary); }

        .kpi-period {
            font-size: 11px;
            color: var(--text-muted);
            margin-top: 2px;
        }

        /* ── Section Header ────────────────────────────────────── */
        .section-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 16px;
        }

        .section-title {
            font-family: var(--font-display);
            font-size: 15px;
            font-weight: 700;
            color: var(--text-primary);
            margin: 0;
        }

        .section-subtitle {
            font-size: 12px;
            color: var(--text-muted);
            margin-top: 2px;
        }

        /* ── Panel Card (wrapper para charts y tablas) ────────── */
        .panel-card {
            background: var(--card-bg);
            border-radius: var(--card-radius);
            box-shadow: var(--card-shadow);
            border: 1px solid rgba(0,0,0,.04);
            overflow: hidden;
        }

        .panel-card-header {
            padding: 18px 22px;
            border-bottom: 1px solid #f1f5f9;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
        }

        .panel-card-body { padding: 20px 22px; }

        /* ── CxC Aging Cards ──────────────────────────────────── */
        .cxc-card {
            background: var(--card-bg);
            border-radius: 12px;
            padding: 16px 18px;
            box-shadow: var(--card-shadow);
            border: 1px solid rgba(0,0,0,.04);
            border-left: 4px solid var(--accent, var(--brand-primary));
            transition: all var(--transition-normal);
        }
        .cxc-card:hover { transform: translateY(-1px); box-shadow: var(--card-shadow-hover); }
        .cxc-card.total     { --accent: var(--brand-primary); }
        .cxc-card.por-vencer{ --accent: #0ea5e9; }
        .cxc-card.v0-15     { --accent: var(--brand-warning); }
        .cxc-card.v16-30    { --accent: #f97316; }
        .cxc-card.v31-mas   { --accent: var(--brand-danger); }

        .cxc-amount {
            font-family: var(--font-display);
            font-size: 20px;
            font-weight: 700;
            color: var(--text-primary);
            letter-spacing: -.3px;
        }
        .cxc-label {
            font-size: 11.5px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: .4px;
            color: var(--text-muted);
            margin-top: 3px;
        }

        /* ── Tabla Vendedores ─────────────────────────────────── */
        .vendedores-table { width: 100%; border-collapse: separate; border-spacing: 0; }
        .vendedores-table thead th {
            font-size: 11px;
            font-weight: 700;
            letter-spacing: .6px;
            text-transform: uppercase;
            color: var(--text-muted);
            padding: 10px 14px;
            background: #f8fafc;
            border-bottom: 1px solid #e2e8f0;
        }
        .vendedores-table thead th:first-child { border-radius: 8px 0 0 0; }
        .vendedores-table thead th:last-child  { border-radius: 0 8px 0 0; }

        .vendedores-table tbody td {
            padding: 12px 14px;
            border-bottom: 1px solid #f1f5f9;
            font-size: 13px;
            color: var(--text-primary);
            vertical-align: middle;
        }

        .vendedores-table tbody tr:last-child td { border-bottom: none; }
        .vendedores-table tbody tr:hover td { background: #f8fafc; }

        .rank-badge {
            width: 26px; height: 26px;
            border-radius: 7px;
            display: inline-flex; align-items: center; justify-content: center;
            font-family: var(--font-display);
            font-size: 12px;
            font-weight: 700;
        }
        .rank-1 { background: #fef3c7; color: #92400e; }
        .rank-2 { background: #f1f5f9; color: #475569; }
        .rank-3 { background: #fdf2f8; color: #9d174d; }
        .rank-n { background: #f8fafc; color: var(--text-muted); }

        .cobranza-bar-wrap {
            display: flex; align-items: center; gap: 8px;
        }
        .cobranza-bar {
            flex: 1;
            height: 6px;
            background: #e2e8f0;
            border-radius: 99px;
            overflow: hidden;
        }
        .cobranza-bar-fill {
            height: 100%;
            border-radius: 99px;
            background: var(--brand-success);
            transition: width 1s ease;
        }

        /* ── Chart toggle buttons ─────────────────────────────── */
        .chart-toggle .btn {
            font-size: 12px;
            padding: 4px 10px;
            border-radius: 7px;
        }

        /* ── Loader skeleton ──────────────────────────────────── */
        @keyframes shimmer {
            0%   { background-position: -400px 0; }
            100% { background-position: 400px 0;  }
        }
        .skeleton {
            background: linear-gradient(90deg, #f1f5f9 25%, #e2e8f0 37%, #f1f5f9 63%);
            background-size: 400px 100%;
            animation: shimmer 1.4s infinite;
            border-radius: 6px;
        }

        /* ── Fade-in animation for content ───────────────────── */
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(12px); }
            to   { opacity: 1; transform: translateY(0); }
        }
        .animate-in {
            animation: fadeInUp .35s ease both;
        }
        .animate-in:nth-child(1) { animation-delay: .05s; }
        .animate-in:nth-child(2) { animation-delay: .10s; }
        .animate-in:nth-child(3) { animation-delay: .15s; }
        .animate-in:nth-child(4) { animation-delay: .20s; }
        .animate-in:nth-child(5) { animation-delay: .25s; }

        /* ── Responsive ──────────────────────────────────────── */
        @media (max-width: 992px) {
            .app-sidebar { transform: translateX(-100%); }
            .app-sidebar.mobile-open { transform: translateX(0); }
            .app-main { margin-left: 0 !important; }
            .sidebar-overlay {
                display: none;
                position: fixed; inset: 0;
                background: rgba(0,0,0,.4);
                z-index: 1029;
            }
            .sidebar-overlay.active { display: block; }
        }

        @media print {
            .app-sidebar, .app-topbar { display: none; }
            .app-main { margin-left: 0; }
            .kpi-card, .panel-card { box-shadow: none; border: 1px solid #e2e8f0; }
        }
    </style>

    @stack('styles')
</head>

<body>
<div class="app-wrapper" id="appWrapper">

    {{-- ── Sidebar ─────────────────────────────────────────────────── --}}
    @include('layouts.partials.sidebar')

    {{-- ── Overlay móvil ──────────────────────────────────────────── --}}
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    {{-- ── Main ────────────────────────────────────────────────────── --}}
    <main class="app-main" id="appMain">

        {{-- Topbar --}}
        @include('layouts.partials.topbar')

        {{-- Contenido de la página --}}
        <div class="app-content">

            {{-- Alertas flash --}}
            @if (session('success'))
                <div class="alert alert-success alert-dismissible fade show mb-4 border-0 shadow-sm" role="alert"
                     style="border-radius: 10px;">
                    <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            @if (session('error'))
                <div class="alert alert-danger alert-dismissible fade show mb-4 border-0 shadow-sm" role="alert"
                     style="border-radius: 10px;">
                    <i class="fas fa-exclamation-triangle me-2"></i>{{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            @yield('content')
        </div>

        {{-- Footer --}}
        <footer class="mt-auto px-4 py-3" style="border-top: 1px solid #e2e8f0;">
            <div class="d-flex align-items-center justify-content-between">
                <span style="font-size: 12px; color: var(--text-muted);">
                    {{ config('app_client.name') }} &copy; {{ date('Y') }}
                    &nbsp;·&nbsp;
                    {{ config('app_client.system.name') }} v{{ config('app_client.system.version') }}
                </span>
                <span style="font-size: 11px; color: var(--text-muted);">
                    Desarrollado por <strong>{{ config('app_client.system.built_by') }}</strong>
                </span>
            </div>
        </footer>

    </main>
</div>

{{-- Bootstrap 5 JS --}}
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
{{-- Flatpickr --}}
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/es.js"></script>
{{-- Chart.js --}}
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>

<script>
(function () {
    'use strict';

    // ── Sidebar toggle (desktop collapse / mobile slide) ─────────────────
    const sidebar  = document.getElementById('appSidebar');
    const main     = document.getElementById('appMain');
    const overlay  = document.getElementById('sidebarOverlay');
    const toggleBtns = document.querySelectorAll('[data-sidebar-toggle]');

    const COLLAPSED_KEY = 'sidebar_collapsed';
    const isMobile = () => window.innerWidth < 993;

    // Restore state
    if (!isMobile() && localStorage.getItem(COLLAPSED_KEY) === '1') {
        sidebar.classList.add('collapsed');
        main.classList.add('sidebar-collapsed');
    }

    toggleBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            if (isMobile()) {
                sidebar.classList.toggle('mobile-open');
                overlay.classList.toggle('active');
            } else {
                const isCollapsed = sidebar.classList.toggle('collapsed');
                main.classList.toggle('sidebar-collapsed', isCollapsed);
                localStorage.setItem(COLLAPSED_KEY, isCollapsed ? '1' : '0');
            }
        });
    });

    overlay.addEventListener('click', () => {
        sidebar.classList.remove('mobile-open');
        overlay.classList.remove('active');
    });

    // ── Auto-dismiss flash alerts ─────────────────────────────────────────
    document.querySelectorAll('.alert').forEach(el => {
        setTimeout(() => {
            const bsAlert = bootstrap.Alert.getOrCreateInstance(el);
            if (bsAlert) bsAlert.close();
        }, 5000);
    });

    // ── Chart.js defaults — tipografía coherente con el sistema ─────────
    Chart.defaults.font.family = "'DM Sans', sans-serif";
    Chart.defaults.font.size   = 12;
    Chart.defaults.color       = '#94a3b8';
    Chart.defaults.plugins.legend.labels.boxWidth = 12;
    Chart.defaults.plugins.legend.labels.padding  = 16;

})();
</script>

@stack('scripts')
</body>
</html>
