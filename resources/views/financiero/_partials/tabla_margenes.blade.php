{{--
    financiero/_partials/tabla_margenes.blade.php

    Componente reutilizable de tabla de márgenes.
    Puede incluirse en margenes.blade.php o en cualquier vista que necesite
    mostrar una colección de márgenes ya enriquecida por MargenService.

    Variables esperadas:
      $margenesFiltradas  — Collection ya enriquecida por MargenService::enriquecerMargenes()
      $excluirIva         — bool
      $costField          — string (para el header de la columna precio)
      $margenService      — instancia de MargenService (para los totales del footer)

    Opcional (para el header de la tabla):
      $showHeader  — bool (default true) — muestra/oculta el encabezado de búsqueda
      $tableId     — string (default 'margenesTable')
      $maxHeight   — string CSS (default '60vh')
--}}

@php
    $showHeader = $showHeader ?? true;
    $tableId    = $tableId    ?? 'margenesTable';
    $maxHeight  = $maxHeight  ?? '60vh';
    $currency   = config('app_client.locale.currency_symbol');
    $dec        = config('app_client.locale.decimal_sep');
    $thou       = config('app_client.locale.thousands_sep');
    $fmt        = fn(float $v) => $currency . ' ' . number_format($v, 2, $dec, $thou);
@endphp

@if($showHeader)
<div class="panel-card-header">
    <div>
        <h3 class="section-title mb-0">
            Detalle de Márgenes por Artículo
            @if(isset($filtroSemaforo) && $filtroSemaforo !== 'todos')
                <span class="sem-badge sem-{{ $filtroSemaforo }}" style="font-size:10px;margin-left:6px;">
                    Filtro: {{ ucfirst($filtroSemaforo) }}
                </span>
            @endif
        </h3>
        <p class="section-subtitle mb-0">
            {{ $margenesFiltradas->count() }} artículos
            @if($excluirIva)
                · <span style="color:var(--brand-danger);font-size:11px;">
                    Precio sin IVA ({{ config('app_client.business.iva_rate') }}%)
                  </span>
            @endif
        </p>
    </div>
    <div style="position:relative;">
        <input type="text"
               id="{{ $tableId }}Search"
               placeholder="Buscar artículo..."
               class="form-control form-control-sm"
               style="padding-left:32px;border-radius:9px;font-size:12.5px;width:210px;border-color:#e2e8f0;">
        <i class="fas fa-search"
           style="position:absolute;left:10px;top:50%;transform:translateY(-50%);
                  font-size:11px;color:var(--text-muted);"></i>
    </div>
</div>
@endif

<div style="overflow:auto;max-height:{{ $maxHeight }};">
    <table class="margenes-table" id="{{ $tableId }}">
        <thead>
            <tr>
                <th data-col="0">Código <span class="sort-icon fas fa-sort"></span></th>
                <th data-col="1" style="min-width:220px;">Descripción <span class="sort-icon fas fa-sort"></span></th>
                <th data-col="2" style="text-align:right;">
                    Precio {{ $excluirIva ? 's/IVA' : 'Venta' }}
                    <span class="sort-icon fas fa-sort"></span>
                </th>
                <th data-col="3" style="text-align:right;">Costo <span class="sort-icon fas fa-sort"></span></th>
                <th data-col="4" style="text-align:right;">Margen $ <span class="sort-icon fas fa-sort"></span></th>
                <th data-col="5" style="text-align:center;min-width:130px;">Margen % <span class="sort-icon fas fa-sort"></span></th>
                <th data-col="6" style="text-align:right;">Uds. Vendidas <span class="sort-icon fas fa-sort"></span></th>
                <th data-col="7" style="text-align:center;">Alerta</th>
            </tr>
        </thead>
        <tbody>
            @forelse($margenesFiltradas as $item)
            @php
                $rowClass   = $item['es_negativo'] ? 'row-negativo' : 'row-' . $item['semaforo'];
                $barPct     = min(abs($item['margen_pct']), 100);
                $barColor   = match($item['semaforo']) {
                    'verde'    => '#059669',
                    'amarillo' => '#d97706',
                    default    => '#dc2626',
                };
                if($item['es_negativo']) $barColor = '#7c3aed';
            @endphp
            <tr class="{{ $rowClass }}">
                <td style="font-family:var(--font-display);font-size:11.5px;font-weight:600;">
                    {{ $item['codigo'] }}
                </td>
                <td style="max-width:280px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"
                    title="{{ $item['descripcion'] }}">
                    {{ $item['descripcion'] }}
                </td>
                <td style="text-align:right;font-weight:500;">
                    {{ $fmt($item['precio_calculo']) }}
                </td>
                <td style="text-align:right;color:var(--text-secondary);">
                    {{ $fmt($item['costo']) }}
                </td>
                <td style="text-align:right;font-weight:700;
                           color:{{ $item['es_negativo'] ? '#7c3aed' : ($item['margen_monto'] >= 0 ? '#059669' : '#dc2626') }};">
                    {{ $fmt($item['margen_monto']) }}
                </td>
                <td>
                    <div class="margin-bar-wrap">
                        <div class="margin-bar">
                            <div class="margin-bar-fill"
                                 style="width:{{ $barPct }}%;background:{{ $barColor }};"></div>
                        </div>
                        <span style="font-size:12px;font-weight:700;min-width:46px;
                                     text-align:right;color:{{ $barColor }};">
                            {{ number_format($item['margen_pct'], 1, $dec, '') }}%
                        </span>
                    </div>
                </td>
                <td style="text-align:right;">
                    {{ number_format($item['unidades_vendidas'], 0, '.', $thou) }}
                </td>
                <td style="text-align:center;">
                    @if($item['es_negativo'])
                        <span class="sem-badge sem-negativo">⬛ Neg.</span>
                    @else
                        <span class="sem-badge sem-{{ $item['semaforo'] }}">
                            {{ match($item['semaforo']) {
                                'verde'    => '▲ Alto',
                                'amarillo' => '◆ Medio',
                                default    => '▼ Bajo',
                            } }}
                        </span>
                    @endif
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="8" style="text-align:center;padding:40px;color:var(--text-muted);">
                    <i class="fas fa-box-open fa-2x d-block mb-3 opacity-25"></i>
                    Sin artículos con ventas en el período o con el filtro aplicado.
                </td>
            </tr>
            @endforelse
        </tbody>

        {{-- Footer de totales --}}
        @if($margenesFiltradas->isNotEmpty())
        @php
            $totFac = $margenesFiltradas->sum(fn($i) => $i['precio_calculo'] * $i['unidades_vendidas']);
            $totCos = $margenesFiltradas->sum(fn($i) => $i['costo']          * $i['unidades_vendidas']);
            $totGan = $totFac - $totCos;
            $totPct = $totFac > 0 ? round($totGan / $totFac * 100, 2) : 0;
            $semTot = $margenService->semaforo($totPct);
        @endphp
        <tfoot>
            <tr>
                <td colspan="2">TOTALES ({{ $margenesFiltradas->count() }} artículos)</td>
                <td style="text-align:right;">{{ $fmt($totFac) }}</td>
                <td style="text-align:right;">{{ $fmt($totCos) }}</td>
                <td style="text-align:right;
                           color:{{ $totGan >= 0 ? '#059669' : '#dc2626' }};">
                    {{ $fmt($totGan) }}
                </td>
                <td style="text-align:center;font-weight:700;
                           color:{{ $semTot === 'verde' ? '#059669' : ($semTot === 'amarillo' ? '#d97706' : '#dc2626') }};">
                    {{ number_format($totPct, 1) }}%
                </td>
                <td colspan="2"></td>
            </tr>
        </tfoot>
        @endif
    </table>
</div>

{{-- JS de búsqueda y ordenamiento para esta instancia de la tabla --}}
@once
@push('scripts')
<script>
(function () {
    'use strict';

    // Búsqueda client-side — se inicializa para cada tabla con data-search-target
    function initTableSearch(tableId) {
        const input = document.getElementById(tableId + 'Search');
        const tbody = document.querySelector('#' + tableId + ' tbody');
        if (!input || !tbody) return;

        input.addEventListener('input', function () {
            const t = this.value.toLowerCase().trim();
            tbody.querySelectorAll('tr').forEach(row => {
                row.style.display = !t || row.textContent.toLowerCase().includes(t) ? '' : 'none';
            });
        });
    }

    // Ordenamiento de columnas
    function initTableSort(tableId) {
        document.querySelectorAll('#' + tableId + ' thead th[data-col]').forEach(th => {
            th.addEventListener('click', function () {
                const col   = parseInt(this.dataset.col);
                const tbody = document.querySelector('#' + tableId + ' tbody');
                const rows  = Array.from(tbody.querySelectorAll('tr'));
                const isAsc = this.classList.contains('sorted-asc');

                document.querySelectorAll('#' + tableId + ' thead th').forEach(h => {
                    h.classList.remove('sorted-asc','sorted-desc');
                    const ico = h.querySelector('.sort-icon');
                    if (ico) ico.className = 'sort-icon fas fa-sort';
                });

                const dir = isAsc ? -1 : 1;
                this.classList.add(isAsc ? 'sorted-desc' : 'sorted-asc');
                const ico = this.querySelector('.sort-icon');
                if (ico) ico.className = `sort-icon fas fa-sort-${isAsc ? 'down' : 'up'}`;

                rows.sort((a, b) => {
                    const at = a.cells[col]?.textContent.replace(/[^0-9.\-,]/g,'').replace(',','.') || '';
                    const bt = b.cells[col]?.textContent.replace(/[^0-9.\-,]/g,'').replace(',','.') || '';
                    const av = parseFloat(at) || at;
                    const bv = parseFloat(bt) || bt;
                    if (typeof av === 'number' && typeof bv === 'number') return (av - bv) * dir;
                    return av.toString().localeCompare(bv.toString()) * dir;
                });
                rows.forEach(r => tbody.appendChild(r));
            });
        });
    }

    // Animar barras
    function animateBars() {
        requestAnimationFrame(() => {
            document.querySelectorAll('.margin-bar-fill').forEach(bar => {
                const w = bar.style.width;
                bar.style.width = '0';
                setTimeout(() => bar.style.width = w, 100);
            });
        });
    }

    // Inicializar todas las tablas de márgenes presentes en la página
    document.querySelectorAll('.margenes-table').forEach(table => {
        const id = table.id;
        if (id) { initTableSearch(id); initTableSort(id); }
    });
    animateBars();
})();
</script>
@endpush
@endonce
