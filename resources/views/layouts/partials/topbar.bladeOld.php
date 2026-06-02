{{--
    layouts/partials/topbar.blade.php
    Topbar con:
      - Toggle de sidebar (desktop collapse + mobile slide)
      - Breadcrumb dinámico por sección
      - Selector de rango de fechas global (Flatpickr) que hace submit del form
      - Indicador de última actualización de datos
--}}

<header class="app-topbar">

    {{-- Toggle sidebar --}}
    <button class="topbar-toggle" data-sidebar-toggle aria-label="Toggle menú">
        <i class="fas fa-bars"></i>
    </button>

    {{-- Breadcrumb --}}
    <div class="topbar-breadcrumb">
        <a href="{{ route('dashboard.index') }}"
           style="color: var(--text-muted); text-decoration: none; font-size: 13px;">
            <i class="fas fa-home" style="font-size: 11px;"></i>
        </a>
        <span style="color: #cbd5e1; font-size: 11px;">/</span>
        @yield('breadcrumb', '<span class="current">Dashboard</span>')
    </div>

    {{-- ── Acciones del topbar ──────────────────────────────────────── --}}
    <div class="topbar-actions">

        {{-- Selector de rango de fechas global --}}
        {{-- Se renderiza en todas las páginas que lo necesiten; en las que no, se puede suprimir con @section('hide_daterange', true) --}}
        @unless(View::hasSection('hide_daterange'))
        <form method="GET" action="{{ url()->current() }}" id="dateRangeForm">
            {{-- Preservar otros query params que pudiera haber --}}
            @foreach(request()->except(['from', 'to']) as $key => $value)
                <input type="hidden" name="{{ $key }}" value="{{ $value }}">
            @endforeach

            <div class="date-range-badge" id="dateRangeTrigger" title="Seleccionar período">
                <i class="fas fa-calendar-alt" style="font-size: 12px; color: var(--brand-primary);"></i>
                <span id="dateRangeDisplay">
                    {{ \Carbon\Carbon::parse(request('from', now()->startOfMonth()))->format(config('app_client.locale.date_format', 'd/m/Y')) }}
                    &nbsp;—&nbsp;
                    {{ \Carbon\Carbon::parse(request('to', now()))->format(config('app_client.locale.date_format', 'd/m/Y')) }}
                </span>
                <i class="fas fa-chevron-down" style="font-size: 10px;"></i>
            </div>

            {{-- Input oculto que Flatpickr usa como rango --}}
            <input type="text"
                   id="flatpickrRange"
                   name="_range"
                   style="display: none;"
                   value="{{ request('from', now()->startOfMonth()->toDateString()) }} to {{ request('to', now()->toDateString()) }}">

            {{-- Campos reales que se envían --}}
            <input type="hidden" id="inputFrom" name="from" value="{{ request('from', now()->startOfMonth()->toDateString()) }}">
            <input type="hidden" id="inputTo"   name="to"   value="{{ request('to', now()->toDateString()) }}">
        </form>
        @endunless

        {{-- Separador --}}
        <div style="width: 1px; height: 22px; background: #e2e8f0;"></div>

        {{-- Botón de refresco manual de caché (solo super_admin) --}}
        @can('seguridad.dashboard')
            <button type="button"
                    class="topbar-toggle"
                    id="btnRefreshCache"
                    title="Limpiar caché y refrescar datos"
                    style="position: relative;">
                <i class="fas fa-sync-alt" id="refreshIcon"></i>
            </button>
        @endcan

        {{-- Indicador ERP status --}}
        <div id="erpStatusDot"
             title="Conexión ERP activa"
             style="
                width: 8px; height: 8px;
                border-radius: 50%;
                background: var(--brand-success);
                box-shadow: 0 0 0 2px rgba(5,150,105,.2);
             ">
        </div>
        {{-- Separador --}}
        <div style="width: 1px; height: 22px; background: #e2e8f0;"></div>
sf
        <ul class="navbar-nav ml-auto align-items-center">
            <li class="nav-item dropdown no-arrow d-sm-none">
                <a class="nav-link dropdown-toggle" href="#" id="searchDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                    <i class="fas fa-search fa-fw"></i>
                </a>
                <div class="dropdown-menu dropdown-menu-right p-3 shadow animated--grow-in" aria-labelledby="searchDropdown">
                    <form class="form-inline mr-auto w-100 navbar-search">
                        <div class="input-group">
                            <input type="text" class="form-control bg-light border-0 small" placeholder="Buscar..." aria-label="Search">
                            <div class="input-group-append">
                                <button class="btn btn-primary" type="button"><i class="fas fa-search fa-sm"></i></button>
                            </div>
                        </div>
                    </form>
                </div>
            </li>
        </ul>
    </div>
</header>

@push('scripts')
<script>
(function () {
    'use strict';

    // ── Flatpickr — Date range selector ──────────────────────────────────
    const dateFormat    = '{{ config('app_client.locale.date_format_js', 'dd/mm/yyyy') }}';
    const fromVal       = '{{ request('from', now()->startOfMonth()->toDateString()) }}';
    const toVal         = '{{ request('to', now()->toDateString()) }}';

    const fp = flatpickr('#flatpickrRange', {
        mode:          'range',
        dateFormat:    'Y-m-d',
        altInput:      false,
        locale:        'es',
        defaultDate:   [fromVal, toVal],
        maxDate:       'today',
        disableMobile: true,

        onReady(_, __, instance) {
            // Abrir el picker al hacer click en el badge visual
            document.getElementById('dateRangeTrigger')
                ?.addEventListener('click', () => instance.open());
        },

        onChange(selectedDates) {
            if (selectedDates.length === 2) {
                const fmt  = d => d.toISOString().split('T')[0];
                const from = fmt(selectedDates[0]);
                const to   = fmt(selectedDates[1]);

                document.getElementById('inputFrom').value = from;
                document.getElementById('inputTo').value   = to;

                // Actualizar el badge visual
                const formatDisplay = d => {
                    const [y, m, day] = d.split('-');
                    return `${day}/${m}/${y}`;
                };
                document.getElementById('dateRangeDisplay').textContent =
                    formatDisplay(from) + ' — ' + formatDisplay(to);

                // Submit automático con pequeño delay para que el usuario vea el cambio
                setTimeout(() => document.getElementById('dateRangeForm').submit(), 300);
            }
        },
    });

    // ── Refresh cache button ──────────────────────────────────────────────
    const btnRefresh = document.getElementById('btnRefreshCache');
    const refreshIcon = document.getElementById('refreshIcon');

    if (btnRefresh) {
        btnRefresh.addEventListener('click', async () => {
            refreshIcon.classList.add('fa-spin');
            btnRefresh.disabled = true;

            try {
                const res = await fetch('{{ route('dashboard.index') }}?bust=1&from={{ request('from', now()->startOfMonth()->toDateString()) }}&to={{ request('to', now()->toDateString()) }}', {
                    headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content }
                });

                // Recargar la página limpiando el parámetro bust
                const url = new URL(window.location.href);
                url.searchParams.delete('bust');
                window.location.href = url.toString();
            } catch (e) {
                refreshIcon.classList.remove('fa-spin');
                btnRefresh.disabled = false;
            }
        });
    }

})();
</script>
@endpush
