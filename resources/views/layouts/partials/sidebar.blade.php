{{--
    layouts/partials/sidebar.blade.php
    Sidebar con navegación dinámica según:
      - config('app_client.modules.*')  → activa/desactiva secciones enteras
      - @can('permiso')                 → muestra/oculta items individuales (Spatie)
--}}

<aside class="app-sidebar" id="appSidebar">

    {{-- ── Brand ─────────────────────────────────────────────── --}}
    <a href="{{ route('dashboard.index') }}" class="sidebar-brand" title="{{ config('app_client.name') }}">
        <div class="sidebar-brand-logo">
            {{ strtoupper(substr(config('app_client.short_name', 'BI'), 0, 2)) }}
        </div>
        <div class="sidebar-brand-text">
            {{ config('app_client.short_name') }}
            <small>{{ config('app_client.system.name') }}</small>
        </div>
    </a>

    {{-- ── Navegación ─────────────────────────────────────────── --}}
    <nav class="sidebar-nav" aria-label="Navegación principal">

        {{-- ┌─────────────────────────────────────────────────────┐ --}}
        {{-- │  GERENCIA                                           │ --}}
        {{-- └─────────────────────────────────────────────────────┘ --}}
        @if(config('app_client.modules.dashboard') && auth()->user()?->can('gerencia.dashboard.ver'))
            <div class="nav-section-label">Gerencia</div>

            <a href="{{ route('dashboard.index') }}"
               class="nav-item-link {{ request()->routeIs('dashboard.*') ? 'active' : '' }}"
               data-tooltip="Dashboard">
                <span class="nav-icon"><i class="fas fa-chart-pie"></i></span>
                <span class="nav-label">Dashboard</span>
            </a>
        @endif

        {{-- ┌─────────────────────────────────────────────────────┐ --}}
        {{-- │  FINANCIERO                                         │ --}}
        {{-- └─────────────────────────────────────────────────────┘ --}}
        @if(config('app_client.modules.financiero'))
            @canany(['financiero.margenes.ver', 'financiero.reporte.bonos'])
                <div class="nav-section-label">Financiero</div>

                @can('financiero.margenes.ver')
                    <a href="{{ route('financiero.margenes') }}"
                       class="nav-item-link {{ request()->routeIs('financiero.margenes*') ? 'active' : '' }}"
                       data-tooltip="Márgenes">
                        <span class="nav-icon"><i class="fas fa-percentage"></i></span>
                        <span class="nav-label">Márgenes</span>
                    </a>
                @endcan

                @can('financiero.reporte.bonos')
                    <a href="{{route('financiero.bonos') }}"
                       class="nav-item-link {{ request()->routeIs('financiero.bonos*') ? 'active' : '' }}"
                       data-tooltip="Reporte de Bonos">
                        <span class="nav-icon"><i class="fas fa-award"></i></span>
                        <span class="nav-label">Reporte de Bonos</span>
                    </a>
                @endcan
            @endcanany
        @endif

        {{-- ┌─────────────────────────────────────────────────────┐ --}}
        {{-- │  INVENTARIO                                         │ --}}
        {{-- └─────────────────────────────────────────────────────┘ --}}
        @if(config('app_client.modules.inventario'))
            @canany(['inventario.stock.critico', 'inventario.entradas.ver', 'inventario.salidas.auditar'])
                <div class="nav-section-label">Inventario</div>

                @can('inventario.stock.critico')
                    <a href="{{ route('inventario.stock-critico') }}"
                       class="nav-item-link {{ request()->routeIs('inventario.stock*') ? 'active' : '' }}"
                       data-tooltip="Stock Crítico">
                        <span class="nav-icon"><i class="fas fa-exclamation-triangle"></i></span>
                        <span class="nav-label">Stock Crítico</span>
                    </a>
                @endcan

                @can('inventario.entradas.ver')
                    <a href="{{ route('inventario.entradas') }}"
                       class="nav-item-link {{ request()->routeIs('inventario.entradas*') ? 'active' : '' }}"
                       data-tooltip="Entradas vs Compras">
                        <span class="nav-icon"><i class="fas fa-boxes-stacked"></i></span>
                        <span class="nav-label">Entradas vs Compras</span>
                    </a>
                @endcan

                @can('inventario.salidas.auditar')
                    <a href="{{ route('inventario.salidas') }}"
                       class="nav-item-link {{ request()->routeIs('inventario.salidas*') ? 'active' : '' }}"
                       data-tooltip="Salidas No Comerciales">
                        <span class="nav-icon"><i class="fas fa-arrow-right-from-bracket"></i></span>
                        <span class="nav-label">Salidas No Comerciales</span>
                    </a>
                @endcan
            @endcanany
        @endif

        {{-- ┌─────────────────────────────────────────────────────┐ --}}
        {{-- │  ARTÍCULOS                                          │ --}}
        {{-- └─────────────────────────────────────────────────────┘ --}}
        @if(config('app_client.modules.articulos'))
            @can('inventario.articulos.ver')
            <div class="nav-section-label">Catálogo</div>

            <a href="{{ route('articulos.index') }}"
               class="nav-item-link {{ request()->routeIs('articulos.index*') ? 'active' : '' }}"
               data-tooltip="Artículos">
                <span class="nav-icon"><i class="fas fa-barcode"></i></span>
                <span class="nav-label">Artículos</span>
            </a>

            <a href="{{ route('articulos.rendimiento') }}"
               class="nav-item-link {{ request()->routeIs('articulos.rendimiento*') ? 'active' : '' }}"
               data-tooltip="Rendimiento">
                <span class="nav-icon"><i class="fas fa-chart-line"></i></span>
                <span class="nav-label">Rendimiento</span>
            </a>
            @endcan
        @endif

        {{-- ┌─────────────────────────────────────────────────────┐ --}}
        {{-- │  VENDEDORES                                         │ --}}
        {{-- └─────────────────────────────────────────────────────┘ --}}
        @if(config('app_client.modules.vendedores'))
            @can('gerencia.vendedores.ranking.ver')
                <div class="nav-section-label">Ventas</div>

                <a href="#"
                   class="nav-item-link {{ request()->routeIs('vendedores*') ? 'active' : '' }}"
                   data-tooltip="Vendedores">
                    <span class="nav-icon"><i class="fas fa-users"></i></span>
                    <span class="nav-label">Vendedores</span>
                </a>
            @endcan
        @endif

        {{-- ┌─────────────────────────────────────────────────────┐ --}}
        {{-- │  TABLET (Fase 5 — solo visible si módulo activo)   │ --}}
        {{-- └─────────────────────────────────────────────────────┘ --}}
        @if(config('app_client.modules.tablet'))
            @can('vendedor.catalogo.ver')
                <div class="nav-section-label">Modo Tablet</div>

                <a href="{{ route('tablet.catalogo') }}"
                   class="nav-item-link {{ request()->routeIs('tablet*') ? 'active' : '' }}"
                   data-tooltip="Catálogo Tablet">
                    <span class="nav-icon"><i class="fas fa-tablet-screen-button"></i></span>
                    <span class="nav-label">Catálogo Tablet</span>
                </a>
            @endcan
        @endif

        {{-- ┌─────────────────────────────────────────────────────┐ --}}
        {{-- │  SEGURIDAD                                          │ --}}
        {{-- └─────────────────────────────────────────────────────┘ --}}

            <div class="nav-section-label">Administración</div>

            <a href="{{ route('admin.users.index') }}"
               class="nav-item-link {{ request()->routeIs('admin.users*') ? 'active' : '' }}"
               data-tooltip="Usuarios">
                <span class="nav-icon"><i class="fas fa-user-shield"></i></span>
                <span class="nav-label">Usuarios</span>
            </a>

            <a href="{{ route('admin.roles.index') }}"
               class="nav-item-link {{ request()->routeIs('admin.roles*') ? 'active' : '' }}"
               data-tooltip="Roles">
                <span class="nav-icon"><i class="fas fa-key"></i></span>
                <span class="nav-label">Roles</span>
            </a>
            <a href="{{ route('admin.permissions.index') }}"
               class="nav-item-link {{ request()->routeIs('admin.permissions*') ? 'active' : '' }}"
               data-tooltip="Permisos">
                <span class="nav-icon"><i class="fas fa-shield-alt"></i></span>
                <span class="nav-label">Permisos</span>
            </a>


    </nav>

    {{-- ── User footer ─────────────────────────────────────────── --}}
    <div class="sidebar-footer">
        <div class="dropdown">
            <div class="sidebar-user" data-bs-toggle="dropdown" aria-expanded="false">
                <div class="user-avatar">
                    {{ strtoupper(substr(auth()->user()?->name ?? 'U', 0, 1)) }}
                </div>
                <div class="user-info">
                    <div class="user-name">{{ auth()->user()?->fullname }}</div>
                    <div class="user-role">
                        {{ auth()->user()?->roles?->first()?->name ?? 'Usuario' }}
                    </div>
                </div>
            </div>
            <ul class="dropdown-menu dropdown-menu-dark mb-2" style="min-width: 190px; border-radius: 10px;">
                <li>
                    <a class="dropdown-item" href="{{ route('profile') }}">
                        <i class="fas fa-user fa-sm me-2 opacity-50"></i> Mi Perfil
                    </a>
                </li>
                <li><hr class="dropdown-divider opacity-25"></li>
                <li>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="dropdown-item text-danger">
                            <i class="fas fa-right-from-bracket fa-sm me-2"></i> Cerrar Sesión
                        </button>
                    </form>
                </li>
            </ul>
        </div>
    </div>

</aside>
