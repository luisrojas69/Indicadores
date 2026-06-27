/**
 * ai-chat.js
 * Lógica jQuery del Chat Drawer del AI Copilot.
 *
 * Uso: incluir después de jQuery y Bootstrap en el layout.
 * Se inicializa desde ai_chat.blade.php con AiChat.init({ ... })
 *
 * Responsabilidades:
 *   - Gestión del historial en memoria (array JS)
 *   - Envío de mensajes al endpoint POST /api/ai/chat
 *   - Renderizado de burbujas: texto, tabla HTML, intent_pending, error
 *   - Auto-resize del textarea
 *   - Sincronización del contexto de fechas con el dashboard
 *   - Sugerencias rápidas
 *   - Limpiar conversación
 */

'use strict';

const AiChat = (function ($) {

    // ── Configuración (se recibe desde .blade.php) ────────────────────────
    let cfg = {
        endpoint:    '/api/ai/chat',
        csrf:        '',
        contextFrom: '',
        contextTo:   '',
        dateFormat:  'd/m/Y',
        currency:    '$',
    };

    // ── Estado interno ────────────────────────────────────────────────────
    let history  = [];   // [{ role: 'user'|'assistant', content: '...' }]
    let thinking = false;

    // ── Selectores cacheados ──────────────────────────────────────────────
    let $body, $messages, $welcome, $typing,
        $input, $sendBtn, $charCount,
        $statusChip, $contextLabel, $dot;

    // ─────────────────────────────────────────────────────────────────────
    // INIT
    // ─────────────────────────────────────────────────────────────────────
    function init(options) {
        cfg = $.extend(cfg, options);

        // Cachear selectores
        $body         = $('#aiChatBody');
        $messages     = $('#aiMessages');
        $welcome      = $('#aiWelcome');
        $typing       = $('#aiTyping');
        $input        = $('#aiInput');
        $sendBtn      = $('#aiSendBtn');
        $charCount    = $('#aiCharCount');
        $statusChip   = $('#aiStatusChip');
        $contextLabel = $('#aiContextLabel');
        $dot          = $('#aiCopilotDot');

        _updateContextLabel();
        _bindEvents();
        _syncContextWithDashboard();
    }

    // ─────────────────────────────────────────────────────────────────────
    // BIND EVENTS
    // ─────────────────────────────────────────────────────────────────────
    function _bindEvents() {

        // ── Enviar con botón ──────────────────────────────────────────────
        $sendBtn.on('click', function () {
            _handleSend();
        });

        // ── Enviar con Enter (Shift+Enter = salto de línea) ───────────────
        $input.on('keydown', function (e) {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                _handleSend();
            }
        });

        // ── Auto-resize del textarea + contador de caracteres ─────────────
        $input.on('input', function () {
            // Auto-resize
            this.style.height = 'auto';
            this.style.height = Math.min(this.scrollHeight, 120) + 'px';

            // Contador
            const len  = this.value.length;
            const max  = parseInt(this.getAttribute('maxlength')) || 500;
            $charCount.text(`${len} / ${max}`);
            $charCount.removeClass('warning danger');
            if (len >= max * 0.9) $charCount.addClass('danger');
            else if (len >= max * 0.7) $charCount.addClass('warning');

            // Habilitar/deshabilitar botón
            $sendBtn.prop('disabled', this.value.trim().length < 2 || thinking);
        });

        // ── Sugerencias rápidas ───────────────────────────────────────────
        $(document).on('click', '.ai-suggestion', function () {
            const prompt = $(this).data('prompt');
            if (prompt) {
                $input.val(prompt).trigger('input');
                _handleSend();
            }
        });

        // ── Limpiar conversación ──────────────────────────────────────────
        $('#aiClearBtn').on('click', function () {
            _clearChat();
        });

        // ── Sincronizar fechas cuando el Flatpickr del topbar cambia ─────
        // El topbar actualiza #inputFrom y #inputTo antes de hacer submit,
        // pero también podemos escuchar el evento change de esos inputs.
        $(document).on('change', '#inputFrom, #inputTo', function () {
            cfg.contextFrom = $('#inputFrom').val() || cfg.contextFrom;
            cfg.contextTo   = $('#inputTo').val()   || cfg.contextTo;
            _updateContextLabel();
        });
    }

    // ─────────────────────────────────────────────────────────────────────
    // ENVIAR MENSAJE
    // ─────────────────────────────────────────────────────────────────────
    function _handleSend() {
        const message = $input.val().trim();
        if (!message || message.length < 2 || thinking) return;

        // Mostrar mensaje del usuario inmediatamente
        _appendUserBubble(message);

        // Agregar al historial
        history.push({ role: 'user', content: message });

        // Limpiar input
        $input.val('').css('height', 'auto').trigger('input');

        // Mostrar typing indicator
        _setThinking(true);

        // Llamar al endpoint
        $.ajax({
            url:         cfg.endpoint,
            method:      'POST',
            contentType: 'application/json',
            headers: {
                'X-CSRF-TOKEN': cfg.csrf,
            },
            data: JSON.stringify({
                message: message,
                history: history.slice(-10), // Últimos 10 turnos
                context: {
                    from: cfg.contextFrom,
                    to:   cfg.contextTo,
                },
            }),
            success: function (response) {
                _setThinking(false);
                _handleResponse(response);
            },
            error: function (xhr) {
                _setThinking(false);

                let errorMsg = 'Error de conexión. Verifica tu acceso a internet.';

                if (xhr.status === 422) {
                    // Error de validación de Laravel
                    const errors = xhr.responseJSON?.errors;
                    if (errors) {
                        const first = Object.values(errors)[0];
                        errorMsg = Array.isArray(first) ? first[0] : first;
                    }
                } else if (xhr.status === 429) {
                    errorMsg = 'Demasiadas consultas. Espera un momento antes de continuar.';
                } else if (xhr.status === 401 || xhr.status === 403) {
                    errorMsg = 'Tu sesión expiró o no tienes permisos. Recarga la página.';
                } else if (xhr.status === 500) {
                    errorMsg = 'Error interno del servidor. Contacta al administrador.';
                }

                _appendBotBubble({
                    type:    'error',
                    content: errorMsg,
                });
            },
        });
    }

    // ─────────────────────────────────────────────────────────────────────
    // PROCESAR RESPUESTA
    // ─────────────────────────────────────────────────────────────────────
    function _handleResponse(response) {
        // Guardar en historial para contexto futuro
        history.push({
            role:    'assistant',
            content: typeof response.content === 'string'
                ? response.content
                : JSON.stringify(response.content),
        });

        // Activar el punto de notificación en el botón flotante
        // (útil si el drawer está cerrado)
        if (!$('#aiCopilotDrawer').hasClass('show')) {
            $dot.show();
        }

        _appendBotBubble(response);
    }

    // ─────────────────────────────────────────────────────────────────────
    // RENDERIZADO DE BURBUJAS
    // ─────────────────────────────────────────────────────────────────────

    function _appendUserBubble(message) {
        _showMessages();

        const escaped = _escapeHtml(message).replace(/\n/g, '<br>');

        const html = `
            <div class="ai-bubble ai-bubble-user">
                <div class="ai-msg-content">${escaped}</div>
            </div>`;

        $messages.append(html);
        _scrollToBottom();
    }

    function _appendBotBubble(response) {
        _showMessages();

        const { type, content, tool_used, confidence } = response;

        let innerHtml = '';

        // ── Contenido según tipo ──────────────────────────────────────────
        switch (type) {

            case 'text':
                innerHtml = _renderMarkdown(content);
                break;

            case 'table':
                // El backend ya retorna HTML de tabla — sanitizamos atributos peligrosos
                innerHtml = _sanitizeTableHtml(content);
                break;

            case 'intent_pending':
                innerHtml = `
                    ${_renderMarkdown(content)}
                    <div class="ai-intent-chip mt-2">
                        <i class="fas fa-clock"></i>
                        Consulta fuera del alcance actual
                    </div>`;
                break;

            case 'error':
                innerHtml = `
                    <div class="ai-error-chip">
                        <i class="fas fa-triangle-exclamation"></i>
                        ${_escapeHtml(content)}
                    </div>`;
                break;

            default:
                innerHtml = _renderMarkdown(content || 'Respuesta no reconocida.');
        }

        // ── Chip de fuente (si vino de un tool) ───────────────────────────
        let toolChip = '';
        if (tool_used) {
            const toolLabel = _toolLabel(tool_used);
            toolChip = `
                <div class="ai-tool-chip">
                    <i class="fas fa-database"></i>
                    Fuente: ${toolLabel}
                </div>`;
        }

        const html = `
            <div class="ai-bubble ai-bubble-bot">
                <div class="ai-avatar"><i class="fas fa-robot"></i></div>
                <div class="ai-msg-content">
                    ${innerHtml}
                    ${toolChip}
                </div>
            </div>`;

        $messages.append(html);
        _scrollToBottom();
    }

    // ─────────────────────────────────────────────────────────────────────
    // ESTADO THINKING
    // ─────────────────────────────────────────────────────────────────────
    function _setThinking(active) {
        thinking = active;
        $sendBtn.prop('disabled', active);
        $typing.toggle(active);

        if (active) {
            $statusChip
                .removeClass('error')
                .addClass('thinking')
                .html('<span class="ai-status-dot"></span> Consultando Profit...');
            _scrollToBottom();
        } else {
            $statusChip
                .removeClass('thinking error')
                .html('<span class="ai-status-dot"></span> Listo para consultas');
        }
    }

    function _setError() {
        $statusChip
            .removeClass('thinking')
            .addClass('error')
            .html('<span class="ai-status-dot"></span> Error de conexión');
    }

    // ─────────────────────────────────────────────────────────────────────
    // VISIBILIDAD DE SECCIONES
    // ─────────────────────────────────────────────────────────────────────
    function _showMessages() {
        if ($welcome.is(':visible')) {
            $welcome.fadeOut(150, function () {
                $messages.fadeIn(150);
            });
        } else {
            $messages.show();
        }
    }

    function _clearChat() {
        history = [];
        $messages.empty().hide();
        $typing.hide();
        $welcome.fadeIn(200);
        $dot.hide();
        $statusChip
            .removeClass('thinking error')
            .html('<span class="ai-status-dot"></span> Listo para consultas');
        thinking = false;
        $sendBtn.prop('disabled', true);
    }

    // ─────────────────────────────────────────────────────────────────────
    // SCROLL
    // ─────────────────────────────────────────────────────────────────────
    function _scrollToBottom() {
        // Pequeño delay para esperar que el DOM se actualice
        setTimeout(function () {
            $body.scrollTop($body[0].scrollHeight);
        }, 60);
    }

    // ─────────────────────────────────────────────────────────────────────
    // CONTEXTO DE FECHAS
    // ─────────────────────────────────────────────────────────────────────
    function _updateContextLabel() {
        if (!cfg.contextFrom || !cfg.contextTo) return;

        const from = _formatDate(cfg.contextFrom);
        const to   = _formatDate(cfg.contextTo);
        $contextLabel.text(`Período: ${from} — ${to}`);
    }

    /**
     * Intenta leer las fechas activas del selector del topbar (Flatpickr).
     * Si existen los inputs #inputFrom e #inputTo, los usa.
     */
    function _syncContextWithDashboard() {
        const from = $('#inputFrom').val();
        const to   = $('#inputTo').val();

        if (from) cfg.contextFrom = from;
        if (to)   cfg.contextTo   = to;

        _updateContextLabel();
    }

    // ─────────────────────────────────────────────────────────────────────
    // HELPERS DE RENDERIZADO
    // ─────────────────────────────────────────────────────────────────────

    /**
     * Markdown básico → HTML.
     * Soporta: **negrita**, *cursiva*, `código`, saltos de línea.
     * No usamos una librería externa para mantener el bundle liviano.
     */
    function _renderMarkdown(text) {
        if (!text || typeof text !== 'string') return '';

        return _escapeHtml(text)
            // Negrita
            .replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>')
            // Cursiva
            .replace(/\*(.+?)\*/g, '<em>$1</em>')
            // Código inline
            .replace(/`(.+?)`/g, '<code style="background:#f1f5f9;padding:1px 5px;border-radius:4px;font-size:11.5px;">$1</code>')
            // Saltos de línea dobles → párrafo
            .replace(/\n\n/g, '</p><p style="margin:6px 0 0;">')
            // Saltos de línea simples
            .replace(/\n/g, '<br>');
    }

    /**
     * Sanitiza el HTML de tabla que viene del backend.
     * Solo permite tags seguros — elimina scripts y event handlers.
     */
    function _sanitizeTableHtml(html) {
        if (!html || typeof html !== 'string') return '';

        // Crear un elemento temporal y dejar que el browser parsee el HTML
        const $tmp  = $('<div>').html(html);

        // Eliminar cualquier script
        $tmp.find('script').remove();

        // Eliminar atributos de eventos peligrosos
        $tmp.find('*').each(function () {
            const attrs = this.attributes;
            const toRemove = [];
            for (let i = 0; i < attrs.length; i++) {
                if (attrs[i].name.startsWith('on')) {
                    toRemove.push(attrs[i].name);
                }
            }
            toRemove.forEach(attr => this.removeAttribute(attr));
        });

        // Agregar clases Bootstrap a la tabla si no las tiene
        $tmp.find('table').addClass('table table-sm table-hover ai-result-table');

        return $tmp.html();
    }

    /**
     * Escapa HTML para prevenir XSS en el contenido del usuario.
     */
    function _escapeHtml(str) {
        if (!str) return '';
        return String(str)
            .replace(/&/g,  '&amp;')
            .replace(/</g,  '&lt;')
            .replace(/>/g,  '&gt;')
            .replace(/"/g,  '&quot;')
            .replace(/'/g,  '&#039;');
    }

    /**
     * Formatea una fecha YYYY-MM-DD al formato local configurado.
     * Soporta d/m/Y y Y-m-d.
     */
    function _formatDate(dateStr) {
        if (!dateStr) return '';
        try {
            const [y, m, d] = dateStr.split('-');
            const fmt = cfg.dateFormat || 'd/m/Y';
            return fmt
                .replace('d', d)
                .replace('m', m)
                .replace('Y', y);
        } catch (e) {
            return dateStr;
        }
    }

    /**
     * Convierte el nombre interno de la tool a una etiqueta legible.
     */
    function _toolLabel(toolName) {
        const labels = {
            'get_dashboard_kpis':          'KPIs del Dashboard',
            'get_ranking_vendedores':       'Ranking de Vendedores',
            'get_top_productos':            'Top Productos',
            'get_cxc_summary':              'Cuentas por Cobrar',
            'get_margenes_por_articulo':    'Márgenes por Artículo',
            'get_resumen_financiero':       'Resumen Financiero',
            'get_stock_critico':            'Stock Crítico',
            'get_entradas_vs_compras':      'Entradas vs Compras',
            'get_salidas_no_comerciales':   'Salidas No Comerciales',
        };
        return labels[toolName] || toolName;
    }

    // ─────────────────────────────────────────────────────────────────────
    // EVENTO: Drawer abierto → limpiar dot de notificación
    // ─────────────────────────────────────────────────────────────────────
    $(document).on('shown.bs.offcanvas', '#aiCopilotDrawer', function () {
        $dot.hide();
        // Re-sincronizar fechas por si el usuario cambió el período
        _syncContextWithDashboard();
        // Focus en el input
        setTimeout(() => $input.focus(), 200);
    });

    // ─────────────────────────────────────────────────────────────────────
    // API PÚBLICA
    // ─────────────────────────────────────────────────────────────────────
    return {
        init,
        /**
         * Permite al código externo actualizar el contexto de fechas.
         * Útil si el Flatpickr del topbar está en otro archivo JS.
         *
         * Uso: AiChat.updateContext('2026-06-01', '2026-06-26');
         */
        updateContext: function (from, to) {
            cfg.contextFrom = from;
            cfg.contextTo   = to;
            _updateContextLabel();
        },
        /**
         * Envía un mensaje programáticamente desde otro componente.
         * Útil para integrar sugerencias contextuales del dashboard.
         *
         * Uso: AiChat.ask('¿Cuánto facturamos este mes?');
         */
        ask: function (message) {
            $input.val(message).trigger('input');
            _handleSend();
        },
        clearHistory: _clearChat,
    };

})(jQuery);
