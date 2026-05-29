{{--
    financiero/_partials/config_costo_modal.blade.php

    Modal de configuración del campo de costo activo y umbrales de alerta de margen.
    Solo visible para usuarios con permiso: financiero.config.costo.editar

    Uso en cualquier vista financiera:
      @include('financiero._partials.config_costo_modal')

    Requiere en el layout: Bootstrap 5 JS ya cargado.
    Activar el modal con: <button data-bs-toggle="modal" data-bs-target="#modalConfigCosto">

    Variables esperadas del controlador:
      $costField    — campo activo actual
      $margenConfig — array con keys: red, yellow, iva_rate, prices_include_iva
      $from, $to    — fechas actuales para preservar en el redirect
--}}

@can('financiero.config.costo.editar')

{{-- Botón de activación (puede copiarse donde se necesite) --}}
{{-- <button type="button" class="btn btn-sm btn-outline-secondary"
         data-bs-toggle="modal" data-bs-target="#modalConfigCosto">
    <i class="fas fa-sliders me-1"></i> Configurar
</button> --}}

<div class="modal fade" id="modalConfigCosto" tabindex="-1"
     aria-labelledby="modalConfigCostoLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width:520px;">
        <div class="modal-content" style="border-radius:16px;border:none;
             box-shadow:0 20px 60px rgba(0,0,0,.2);overflow:hidden;">

            {{-- Header --}}
            <div class="modal-header"
                 style="background:#0f172a;border:none;padding:20px 24px;">
                <div>
                    <h5 class="modal-title font-display"
                        id="modalConfigCostoLabel"
                        style="color:#fff;font-size:16px;font-weight:700;margin:0;">
                        <i class="fas fa-sliders me-2" style="color:var(--brand-primary);"></i>
                        Configuración de Márgenes
                    </h5>
                    <p style="font-size:11.5px;color:rgba(255,255,255,.4);margin:4px 0 0;">
                        Los cambios aplican a la sesión actual
                    </p>
                </div>
                <button type="button" class="btn-close btn-close-white"
                        data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>

            {{-- Body --}}
            <div class="modal-body" style="padding:24px;">

                {{-- ── Campo de costo activo ──────────────────────────── --}}
                <form method="POST" action="{{ route('financiero.set-cost-field') }}"
                      id="formCostField">
                    @csrf
                    <input type="hidden" name="from" value="{{ $from }}">
                    <input type="hidden" name="to"   value="{{ $to }}">

                    <p style="font-size:11px;font-weight:700;text-transform:uppercase;
                              letter-spacing:.6px;color:var(--text-muted);margin-bottom:10px;">
                        <i class="fas fa-coins me-1"></i>
                        Campo de Costo para Cálculo de Márgenes
                    </p>

                    <div class="row g-2 mb-4">
                        @php
                            $costFields = [
                                'COS_PRO_UN' => ['Costo Promedio',  'Moneda local (Bs.)',    'fa-scale-balanced'],
                                'ULT_COS_UN' => ['Último Costo',    'Moneda local (Bs.)',    'fa-clock-rotate-left'],
                                'COS_PRO_OM' => ['Costo Promedio',  'Otra moneda (USD)',     'fa-dollar-sign'],
                                'ULT_COS_OM' => ['Último Costo',    'Otra moneda (USD)',     'fa-clock-rotate-left'],
                            ];
                        @endphp
                        @foreach($costFields as $field => [$label, $sub, $icon])
                        <div class="col-6">
                            <label style="
                                display:block;padding:12px 14px;border-radius:10px;
                                border:2px solid {{ $costField === $field ? 'var(--brand-primary)' : '#e2e8f0' }};
                                background:{{ $costField === $field ? '#eff6ff' : '#f8fafc' }};
                                cursor:pointer;transition:all .15s;
                            " onclick="selectCostField('{{ $field }}', this)">
                                <input type="radio" name="cost_field"
                                       value="{{ $field }}"
                                       {{ $costField === $field ? 'checked' : '' }}
                                       style="display:none;">
                                <div style="display:flex;align-items:center;gap:8px;margin-bottom:4px;">
                                    <i class="fas {{ $icon }}"
                                       style="color:{{ $costField === $field ? 'var(--brand-primary)' : 'var(--text-muted)' }};
                                              font-size:13px;"></i>
                                    <span style="font-size:12.5px;font-weight:700;
                                                 color:{{ $costField === $field ? 'var(--brand-primary)' : 'var(--text-primary)' }};">
                                        {{ $label }}
                                    </span>
                                    @if($costField === $field)
                                    <i class="fas fa-check-circle ms-auto"
                                       style="color:var(--brand-primary);font-size:13px;"></i>
                                    @endif
                                </div>
                                <div style="font-size:10.5px;color:var(--text-muted);margin-left:21px;">
                                    {{ $sub }}
                                </div>
                                <div style="font-size:9.5px;font-weight:700;font-family:var(--font-display);
                                            color:var(--text-muted);margin-left:21px;margin-top:2px;">
                                    {{ $field }}
                                </div>
                            </label>
                        </div>
                        @endforeach
                    </div>

                    <button type="submit" class="btn btn-primary w-100"
                            style="border-radius:10px;font-size:13px;font-weight:600;padding:10px;">
                        <i class="fas fa-check me-2"></i>
                        Aplicar Campo de Costo
                    </button>
                </form>

                <hr style="border-color:#f1f5f9;margin:20px 0;">

                {{-- ── Umbrales de Alerta de Margen ────────────────────── --}}
                <p style="font-size:11px;font-weight:700;text-transform:uppercase;
                          letter-spacing:.6px;color:var(--text-muted);margin-bottom:12px;">
                    <i class="fas fa-traffic-light me-1"></i>
                    Umbrales de Semáforo de Margen
                </p>

                <div style="background:#f8fafc;border-radius:12px;padding:16px;margin-bottom:14px;">
                    <div class="row g-3 align-items-center">
                        <div class="col-6">
                            <label style="font-size:12px;font-weight:600;color:#dc2626;
                                          display:flex;align-items:center;gap:6px;margin-bottom:6px;">
                                <span style="width:10px;height:10px;border-radius:50%;background:#dc2626;"></span>
                                Alerta Roja (por debajo de)
                            </label>
                            <div style="position:relative;">
                                <input type="number"
                                       id="alertRedInput"
                                       value="{{ $margenConfig['red'] }}"
                                       min="0" max="50" step="1"
                                       class="form-control form-control-sm"
                                       style="border-radius:8px;padding-right:28px;border-color:#fca5a5;">
                                <span style="position:absolute;right:10px;top:50%;
                                             transform:translateY(-50%);font-size:11px;
                                             color:var(--text-muted);">%</span>
                            </div>
                        </div>
                        <div class="col-6">
                            <label style="font-size:12px;font-weight:600;color:#d97706;
                                          display:flex;align-items:center;gap:6px;margin-bottom:6px;">
                                <span style="width:10px;height:10px;border-radius:50%;background:#d97706;"></span>
                                Alerta Amarilla (por debajo de)
                            </label>
                            <div style="position:relative;">
                                <input type="number"
                                       id="alertYellowInput"
                                       value="{{ $margenConfig['yellow'] }}"
                                       min="0" max="100" step="1"
                                       class="form-control form-control-sm"
                                       style="border-radius:8px;padding-right:28px;border-color:#fcd34d;">
                                <span style="position:absolute;right:10px;top:50%;
                                             transform:translateY(-50%);font-size:11px;
                                             color:var(--text-muted);">%</span>
                            </div>
                        </div>
                    </div>

                    {{-- Preview del semáforo con los valores actuales --}}
                    <div style="display:flex;gap:8px;margin-top:12px;" id="semaforoPreview">
                        <div style="flex:1;padding:6px;border-radius:8px;background:#dcfce7;
                                    text-align:center;font-size:11px;font-weight:700;color:#15803d;">
                            ▲ Alto ≥ <span id="previewYellow">{{ $margenConfig['yellow'] }}</span>%
                        </div>
                        <div style="flex:1;padding:6px;border-radius:8px;background:#fef3c7;
                                    text-align:center;font-size:11px;font-weight:700;color:#92400e;">
                            ◆ Medio <span id="previewRed">{{ $margenConfig['red'] }}</span>–<span id="previewYellow2">{{ $margenConfig['yellow'] }}</span>%
                        </div>
                        <div style="flex:1;padding:6px;border-radius:8px;background:#fee2e2;
                                    text-align:center;font-size:11px;font-weight:700;color:#b91c1c;">
                            ▼ Bajo &lt; <span id="previewRed2">{{ $margenConfig['red'] }}</span>%
                        </div>
                    </div>

                    <p style="font-size:11px;color:var(--text-muted);margin:10px 0 0;">
                        <i class="fas fa-info-circle me-1"></i>
                        Estos valores se guardan en <code>.env</code>.
                        Requieren reinicio del servidor para persistir.
                        Por ahora aplican solo en sesión.
                    </p>
                </div>

            </div>

            {{-- Footer --}}
            <div class="modal-footer" style="border-top:1px solid #f1f5f9;padding:14px 24px;">
                <button type="button" class="btn btn-outline-secondary btn-sm"
                        data-bs-dismiss="modal" style="border-radius:8px;">
                    Cerrar
                </button>
                <div style="font-size:11px;color:var(--text-muted);margin-left:auto;">
                    Campo activo: <strong style="color:var(--brand-primary);">{{ $costField }}</strong>
                    · IVA: <strong>{{ $margenConfig['iva_rate'] }}%</strong>
                </div>
            </div>

        </div>
    </div>
</div>

@push('scripts')
<script>
(function () {
    'use strict';

    // ── Selección visual de campo de costo ───────────────────────────────
    window.selectCostField = function (field, labelEl) {
        // Deseleccionar todos
        document.querySelectorAll('#formCostField label').forEach(l => {
            l.style.borderColor = '#e2e8f0';
            l.style.background  = '#f8fafc';
            const ico = l.querySelector('.fa-check-circle');
            if (ico) ico.remove();
            const name = l.querySelector('span[style*="font-weight:700"]');
            if (name) name.style.color = 'var(--text-primary)';
            const icon = l.querySelector('.fas:not(.fa-check-circle)');
            if (icon) icon.style.color = 'var(--text-muted)';
        });

        // Marcar el seleccionado
        labelEl.style.borderColor = 'var(--brand-primary)';
        labelEl.style.background  = '#eff6ff';
        const name = labelEl.querySelector('span[style*="font-weight:700"]');
        if (name) name.style.color = 'var(--brand-primary)';
        const icon = labelEl.querySelector('.fas');
        if (icon) icon.style.color = 'var(--brand-primary)';

        // Seleccionar radio
        const radio = labelEl.querySelector('input[type=radio]');
        if (radio) radio.checked = true;
    };

    // ── Live preview del semáforo ────────────────────────────────────────
    const inputRed    = document.getElementById('alertRedInput');
    const inputYellow = document.getElementById('alertYellowInput');

    function updatePreview() {
        const r = inputRed?.value    || 0;
        const y = inputYellow?.value || 0;
        document.getElementById('previewRed')?.textContent   && (document.getElementById('previewRed').textContent    = r);
        document.getElementById('previewRed2')?.textContent  && (document.getElementById('previewRed2').textContent   = r);
        document.getElementById('previewYellow')?.textContent && (document.getElementById('previewYellow').textContent = y);
        document.getElementById('previewYellow2')?.textContent && (document.getElementById('previewYellow2').textContent = y);
    }

    inputRed?.addEventListener('input',    updatePreview);
    inputYellow?.addEventListener('input', updatePreview);

})();
</script>
@endpush

@endcan
