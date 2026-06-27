{{--
    layouts/partials/ai_chat.blade.php

    Chat Drawer del AI Copilot.
    Se incluye al final de layouts/app.blade.php, justo antes de </body>:

        @can('gerencia.dashboard.ver')
            @include('layouts.partials.ai_chat')
        @endcan

    Dependencias (ya presentes en el layout):
        - Bootstrap 5 (offcanvas)
        - Font Awesome 6
        - jQuery (para ai-chat.js)

    ai-chat.js se carga desde @push('scripts') al final de este partial.
--}}

{{-- ── Botón flotante de activación ──────────────────────────────────── --}}
<button
    id="aiCopilotBtn"
    type="button"
    data-bs-toggle="offcanvas"
    data-bs-target="#aiCopilotDrawer"
    aria-controls="aiCopilotDrawer"
    title="Abrir Copiloto BI"
>
    <i class="fas fa-robot" id="aiCopilotIcon"></i>
    <span class="ai-btn-label">Copiloto</span>
    {{-- Punto de actividad (pulsa cuando hay respuesta nueva) --}}
    <span class="ai-btn-dot" id="aiCopilotDot" style="display:none;"></span>
</button>

{{-- ── Offcanvas Drawer ────────────────────────────────────────────────── --}}
<div
    class="offcanvas offcanvas-end"
    tabindex="-1"
    id="aiCopilotDrawer"
    aria-labelledby="aiCopilotTitle"
    style="width: 420px; max-width: 96vw;"
>

    {{-- Header del drawer --}}
    <div class="ai-drawer-header">
        <div class="ai-drawer-brand">
            <div class="ai-avatar-icon">
                <i class="fas fa-robot"></i>
            </div>
            <div>
                <h5 class="ai-drawer-title" id="aiCopilotTitle">
                    Copiloto BI
                </h5>
                <span class="ai-status-chip" id="aiStatusChip">
                    <span class="ai-status-dot"></span>
                    Listo para consultas
                </span>
            </div>
        </div>
        <div class="d-flex align-items-center gap-2">
            {{-- Botón limpiar chat --}}
            <button type="button"
                    class="ai-icon-btn"
                    id="aiClearBtn"
                    title="Limpiar conversación">
                <i class="fas fa-trash-can"></i>
            </button>
            {{-- Cerrar --}}
            <button type="button"
                    class="ai-icon-btn"
                    data-bs-dismiss="offcanvas"
                    aria-label="Cerrar">
                <i class="fas fa-times"></i>
            </button>
        </div>
    </div>

    {{-- Cuerpo del chat --}}
    <div class="ai-drawer-body" id="aiChatBody">

        {{-- Mensaje de bienvenida (se oculta al primer mensaje) --}}
        <div id="aiWelcome" class="ai-welcome">
            <div class="ai-welcome-avatar">
                <i class="fas fa-robot"></i>
            </div>
            <h6 class="ai-welcome-title">¡Hola, soy tu Copiloto BI!</h6>
            <p class="ai-welcome-sub">
                Puedo consultarle datos reales a
                <strong>Profit Plus</strong> usando lenguaje natural.
                Prueba con alguna de estas preguntas:
            </p>

            {{-- Sugerencias rápidas --}}
            <div class="ai-suggestions" id="aiSuggestions">
                <button class="ai-suggestion" data-prompt="¿Cuánto facturamos este mes?">
                    <i class="fas fa-chart-bar"></i>
                    ¿Cuánto facturamos este mes?
                </button>
                <button class="ai-suggestion" data-prompt="¿Qué vendedor facturó más este período?">
                    <i class="fas fa-trophy"></i>
                    ¿Qué vendedor facturó más?
                </button>
                <button class="ai-suggestion" data-prompt="¿Cuáles son los productos con stock crítico?">
                    <i class="fas fa-triangle-exclamation"></i>
                    ¿Cuáles tienen stock crítico?
                </button>
                <button class="ai-suggestion" data-prompt="¿Cuánto tenemos pendiente por cobrar?">
                    <i class="fas fa-file-invoice-dollar"></i>
                    ¿Cuánto hay por cobrar?
                </button>
                <button class="ai-suggestion" data-prompt="¿Cuáles son los 5 productos más vendidos?">
                    <i class="fas fa-star"></i>
                    Top 5 productos más vendidos
                </button>
                <button class="ai-suggestion" data-prompt="¿Cuál fue la ganancia neta del mes?">
                    <i class="fas fa-coins"></i>
                    ¿Cuál fue la ganancia neta?
                </button>
            </div>
        </div>

        {{-- Historial de mensajes --}}
        <div id="aiMessages" class="ai-messages" style="display:none;"></div>

        {{-- Indicador "escribiendo..." --}}
        <div id="aiTyping" class="ai-typing-wrap" style="display:none;">
            <div class="ai-bubble ai-bubble-bot ai-typing">
                <div class="ai-avatar"><i class="fas fa-robot"></i></div>
                <div class="ai-typing-dots">
                    <span></span><span></span><span></span>
                </div>
            </div>
        </div>

    </div>

    {{-- Footer: input + enviar --}}
    <div class="ai-drawer-footer">

        {{-- Contexto de fechas activo --}}
        <div class="ai-context-bar" id="aiContextBar">
            <i class="fas fa-calendar-alt" style="color:var(--brand-primary);"></i>
            <span id="aiContextLabel">Período: cargando...</span>
            <span class="ai-context-edit" id="aiContextEditBtn" title="El período se toma del selector del dashboard">
                <i class="fas fa-link"></i> Sincronizado con dashboard
            </span>
        </div>

        {{-- Input principal --}}
        <div class="ai-input-wrap">
            <textarea
                id="aiInput"
                class="ai-input"
                placeholder="Escribe tu consulta..."
                rows="1"
                maxlength="500"
                autocomplete="off"
            ></textarea>
            <button type="button" class="ai-send-btn" id="aiSendBtn" disabled>
                <i class="fas fa-paper-plane"></i>
            </button>
        </div>

        <div class="ai-footer-meta">
            <span id="aiCharCount" class="ai-char-count">0 / 500</span>
            <span class="ai-footer-note">
                <i class="fas fa-shield-halved"></i>
                Los datos vienen directo de Profit — sin inventar cifras.
            </span>
        </div>

    </div>

</div>

{{-- ── Estilos exclusivos del Copiloto ────────────────────────────────── --}}
<style>
/* ── Botón flotante ─────────────────────────────────────────────────── */
#aiCopilotBtn {
    position: fixed;
    bottom: 28px;
    right: 28px;
    z-index: 1040;
    display: flex;
    align-items: center;
    gap: 8px;
    background: linear-gradient(135deg, var(--brand-primary, #1a56db), #1347bf);
    color: #fff;
    border: none;
    border-radius: 50px;
    padding: 11px 20px 11px 16px;
    font-size: 14px;
    font-weight: 700;
    font-family: var(--font-display, 'Sora', sans-serif);
    box-shadow: 0 6px 20px rgba(26,86,219,.35);
    cursor: pointer;
    transition: all .2s cubic-bezier(.4,0,.2,1);
}
#aiCopilotBtn:hover {
    transform: translateY(-3px);
    box-shadow: 0 10px 28px rgba(26,86,219,.45);
}
#aiCopilotBtn:active { transform: scale(.96); }
#aiCopilotIcon { font-size: 16px; }
.ai-btn-label { letter-spacing: .2px; }
.ai-btn-dot {
    width: 8px; height: 8px; border-radius: 50%;
    background: #4ade80;
    box-shadow: 0 0 0 2px rgba(74,222,128,.3);
    animation: dotPulse 1.5s infinite;
}
@keyframes dotPulse {
    0%,100% { box-shadow: 0 0 0 2px rgba(74,222,128,.3); }
    50%      { box-shadow: 0 0 0 5px rgba(74,222,128,.1); }
}

/* ── Drawer ─────────────────────────────────────────────────────────── */
#aiCopilotDrawer {
    border-left: none;
    box-shadow: -8px 0 40px rgba(15,23,42,.12);
    display: flex;
    flex-direction: column;
}

/* Header */
.ai-drawer-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 14px 18px;
    background: #fff;
    border-bottom: 1px solid #e2e8f0;
    flex-shrink: 0;
}
.ai-drawer-brand { display: flex; align-items: center; gap: 10px; }
.ai-avatar-icon {
    width: 38px; height: 38px; border-radius: 11px;
    background: linear-gradient(135deg, var(--brand-primary,#1a56db), #1347bf);
    color: #fff; display: flex; align-items: center; justify-content: center;
    font-size: 16px; flex-shrink: 0;
    box-shadow: 0 3px 8px rgba(26,86,219,.25);
}
.ai-drawer-title {
    font-family: var(--font-display,'Sora',sans-serif);
    font-size: 15px; font-weight: 800;
    color: var(--text-primary,#0f172a);
    margin: 0; line-height: 1;
}
.ai-status-chip {
    display: inline-flex; align-items: center; gap: 5px;
    font-size: 11px; font-weight: 600;
    color: var(--text-muted,#64748b); margin-top: 3px;
}
.ai-status-dot {
    width: 6px; height: 6px; border-radius: 50%;
    background: #4ade80; flex-shrink: 0;
    animation: dotPulse 2s infinite;
}
.ai-status-chip.thinking .ai-status-dot { background: #fbbf24; }
.ai-status-chip.thinking { color: #d97706; }
.ai-status-chip.error    .ai-status-dot { background: #f87171; animation: none; }
.ai-status-chip.error    { color: #dc2626; }

.ai-icon-btn {
    width: 32px; height: 32px; border-radius: 8px;
    border: 1px solid #e2e8f0; background: #f8fafc;
    color: var(--text-muted,#64748b); font-size: 13px;
    display: flex; align-items: center; justify-content: center;
    cursor: pointer; transition: all .12s;
}
.ai-icon-btn:hover { background: #fee2e2; color: #dc2626; border-color: #fca5a5; }

/* Cuerpo scrolleable */
.ai-drawer-body {
    flex: 1;
    overflow-y: auto;
    padding: 16px;
    background: #f8fafc;
    display: flex;
    flex-direction: column;
    gap: 0;
    scroll-behavior: smooth;
    -webkit-overflow-scrolling: touch;
}

/* Welcome */
.ai-welcome { text-align: center; padding: 20px 8px 8px; }
.ai-welcome-avatar {
    width: 56px; height: 56px; border-radius: 50%;
    background: linear-gradient(135deg,#eff6ff,#dbeafe);
    color: var(--brand-primary,#1a56db);
    display: flex; align-items: center; justify-content: center;
    font-size: 24px; margin: 0 auto 12px;
    box-shadow: 0 4px 12px rgba(26,86,219,.12);
}
.ai-welcome-title {
    font-family: var(--font-display,'Sora',sans-serif);
    font-size: 15px; font-weight: 800;
    color: var(--text-primary,#0f172a); margin-bottom: 6px;
}
.ai-welcome-sub {
    font-size: 12.5px; color: var(--text-muted,#64748b);
    line-height: 1.5; margin-bottom: 14px;
}

/* Sugerencias */
.ai-suggestions {
    display: flex; flex-direction: column; gap: 6px;
    text-align: left;
}
.ai-suggestion {
    display: flex; align-items: center; gap: 10px;
    padding: 10px 13px;
    background: #fff; border: 1.5px solid #e2e8f0;
    border-radius: 10px; font-size: 12.5px; font-weight: 500;
    color: var(--text-secondary,#475569);
    cursor: pointer; transition: all .12s; text-align: left;
    width: 100%;
}
.ai-suggestion i { color: var(--brand-primary,#1a56db); font-size: 13px; width: 16px; flex-shrink: 0; }
.ai-suggestion:hover {
    border-color: var(--brand-primary,#1a56db);
    background: #eff6ff; color: var(--brand-primary,#1a56db);
}
.ai-suggestion:active { transform: scale(.98); }

/* Mensajes */
.ai-messages { display: flex; flex-direction: column; gap: 12px; }

/* Burbuja base */
.ai-bubble {
    display: flex; align-items: flex-start; gap: 8px;
    animation: bubbleIn .2s ease both;
}
@keyframes bubbleIn {
    from { opacity:0; transform: translateY(6px); }
    to   { opacity:1; transform: translateY(0); }
}

/* Avatar del bot en burbujas */
.ai-avatar {
    width: 28px; height: 28px; border-radius: 8px; flex-shrink: 0;
    background: linear-gradient(135deg, var(--brand-primary,#1a56db), #1347bf);
    color: #fff; display: flex; align-items: center; justify-content: center;
    font-size: 12px; margin-top: 2px;
}

/* Burbuja del bot */
.ai-bubble-bot .ai-msg-content {
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 4px 14px 14px 14px;
    padding: 11px 14px;
    font-size: 13px; line-height: 1.6;
    color: var(--text-primary,#0f172a);
    box-shadow: 0 1px 4px rgba(0,0,0,.04);
    max-width: calc(100% - 40px);
}

/* Burbuja del usuario */
.ai-bubble-user {
    flex-direction: row-reverse;
}
.ai-bubble-user .ai-msg-content {
    background: linear-gradient(135deg, var(--brand-primary,#1a56db), #1347bf);
    color: #fff;
    border-radius: 14px 4px 14px 14px;
    padding: 11px 14px;
    font-size: 13px; line-height: 1.5;
    max-width: calc(100% - 40px);
    word-break: break-word;
}

/* Chip de fuente (tool usada) */
.ai-tool-chip {
    display: inline-flex; align-items: center; gap: 4px;
    font-size: 10px; font-weight: 700;
    background: #eff6ff; color: var(--brand-primary,#1a56db);
    border: 1px solid #bfdbfe; border-radius: 20px;
    padding: 2px 8px; margin-top: 6px;
}

/* Chip de intent pendiente */
.ai-intent-chip {
    display: inline-flex; align-items: center; gap: 5px;
    font-size: 10.5px; font-weight: 700;
    background: #fef9c3; color: #92400e;
    border: 1px solid #fde68a; border-radius: 20px;
    padding: 3px 9px; margin-top: 6px;
}

/* Chip de error */
.ai-error-chip {
    display: inline-flex; align-items: center; gap: 5px;
    font-size: 10.5px; font-weight: 700;
    background: #fee2e2; color: #b91c1c;
    border: 1px solid #fca5a5; border-radius: 20px;
    padding: 3px 9px; margin-top: 6px;
}

/* Tablas dentro de las respuestas */
.ai-msg-content .ai-result-table {
    font-size: 12px; margin-top: 8px; margin-bottom: 0;
    border-radius: 8px; overflow: hidden;
}
.ai-msg-content .ai-result-table thead th {
    background: #0f172a; color: rgba(255,255,255,.8);
    font-size: 10px; font-weight: 700;
    text-transform: uppercase; letter-spacing: .4px;
    padding: 7px 10px; border: none;
}
.ai-msg-content .ai-result-table tbody td {
    padding: 7px 10px; font-size: 11.5px;
    border-bottom: 1px solid #f1f5f9; border-top: none;
}
.ai-msg-content .ai-result-table tbody tr:hover td { background: #f8fafc; }
.ai-msg-content .table-responsive { margin: 0; border-radius: 8px; overflow: hidden; }

/* Typing indicator */
.ai-typing-wrap { display: flex; }
.ai-typing {
    display: flex; align-items: center; gap: 8px;
}
.ai-typing-dots {
    display: flex; align-items: center; gap: 4px;
    background: #fff; border: 1px solid #e2e8f0;
    border-radius: 4px 14px 14px 14px;
    padding: 12px 16px;
}
.ai-typing-dots span {
    width: 7px; height: 7px; border-radius: 50%;
    background: #94a3b8; display: inline-block;
    animation: typingBounce 1.2s infinite ease-in-out;
}
.ai-typing-dots span:nth-child(2) { animation-delay: .2s; }
.ai-typing-dots span:nth-child(3) { animation-delay: .4s; }
@keyframes typingBounce {
    0%,60%,100% { transform: translateY(0); }
    30%          { transform: translateY(-6px); background: var(--brand-primary,#1a56db); }
}

/* Footer */
.ai-drawer-footer {
    flex-shrink: 0;
    padding: 10px 14px 14px;
    background: #fff;
    border-top: 1px solid #e2e8f0;
}

/* Barra de contexto */
.ai-context-bar {
    display: flex; align-items: center; gap: 6px;
    font-size: 11px; font-weight: 600; color: var(--text-muted,#64748b);
    background: #f8fafc; border: 1px solid #e2e8f0;
    border-radius: 8px; padding: 5px 10px;
    margin-bottom: 8px;
}
.ai-context-edit {
    margin-left: auto; font-size: 10px;
    color: var(--brand-primary,#1a56db); font-weight: 600;
}

/* Input */
.ai-input-wrap {
    display: flex; align-items: flex-end; gap: 8px;
}
.ai-input {
    flex: 1;
    border: 1.5px solid #e2e8f0; border-radius: 11px;
    padding: 10px 13px;
    font-size: 13.5px; font-family: inherit;
    background: #f8fafc; resize: none;
    max-height: 120px; overflow-y: auto;
    transition: border-color .15s, box-shadow .15s;
    line-height: 1.5;
}
.ai-input:focus {
    outline: none;
    border-color: var(--brand-primary,#1a56db);
    background: #fff;
    box-shadow: 0 0 0 3px rgba(26,86,219,.08);
}
.ai-send-btn {
    width: 42px; height: 42px; border-radius: 11px; flex-shrink: 0;
    background: var(--brand-primary,#1a56db);
    border: none; color: #fff; font-size: 15px;
    display: flex; align-items: center; justify-content: center;
    cursor: pointer; transition: all .15s;
}
.ai-send-btn:hover:not(:disabled) { filter: brightness(1.1); }
.ai-send-btn:disabled {
    background: #e2e8f0; color: #94a3b8; cursor: default;
}

/* Meta del footer */
.ai-footer-meta {
    display: flex; align-items: center; justify-content: space-between;
    margin-top: 6px;
}
.ai-char-count { font-size: 10.5px; color: var(--text-muted,#94a3b8); font-weight: 600; }
.ai-char-count.warning { color: #d97706; }
.ai-char-count.danger  { color: #dc2626; }
.ai-footer-note {
    font-size: 10px; color: #94a3b8;
    display: flex; align-items: center; gap: 4px;
}

/* Responsive */
@media (max-width: 480px) {
    #aiCopilotBtn .ai-btn-label { display: none; }
    #aiCopilotBtn { padding: 12px; border-radius: 50%; }
}
</style>

@push('scripts')
<script src="{{ asset('js/ai-chat.js') }}"></script>
<script>
    // Inicializar con el contexto actual del dashboard
    $(document).ready(function () {
        alert('okok')
        AiChat.init({
            endpoint:    '{{ route('api.ai.chat') }}',
            csrf:        '{{ csrf_token() }}',
            contextFrom: '{{ request('from', now()->startOfMonth()->toDateString()) }}',
            contextTo:   '{{ request('to',   now()->toDateString()) }}',
            dateFormat:  '{{ config('app_client.locale.date_format', 'd/m/Y') }}',
            currency:    '{{ config('app_client.locale.currency_symbol', '$') }}',
        });
    });
</script>
@endpush
