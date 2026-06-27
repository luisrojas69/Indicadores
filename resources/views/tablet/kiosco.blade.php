<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Verificador de Precios | {{ config('app_client.short_name') }}</title>
    {{-- Favicon --}}
    <link rel="icon" type="image/x-icon" href="{{ asset(config('app_client.favicon', 'favicon.ico')) }}">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body, html {
            height: 100%;
            margin: 0;
            background-color: #0f172a; 
            color: #fff;
            font-family: 'Segoe UI', system-ui, sans-serif;
            overflow: hidden;
            user-select: none;
        }

        /* ── Input Invisible ── */
        #scannerInput {
            position: absolute;
            opacity: 0;
            top: -100px;
            z-index: -1;
        }

        /* ── Header Branding ── */
        .kiosco-header {
            position: absolute;
            top: 30px;
            left: 40px;
            display: flex;
            align-items: center;
            gap: 15px;
            z-index: 50;
        }
        .kiosco-header img {
            height: 60px; /* Ajusta este valor según tu logo */
            object-fit: contain;
        }
        .kiosco-header-info {
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        .kiosco-header-name {
            font-size: 20px;
            font-weight: 800;
            color: #fff;
            line-height: 1.1;
        }
        .kiosco-header-rif {
            font-size: 14px;
            color: #94a3b8;
            font-weight: 600;
        }

        /* ── Footer Branding (Sistema) ── */
        .kiosco-footer {
            position: absolute;
            bottom: 15px;
            right: 25px;
            font-size: 11px;
            color: #0157DE; /* Muy discreto */
            z-index: 50;
            text-align: right;
        }

        /* ── Clases Base de Pantallas ── */
        .pantalla-estado {
            height: 100vh;
            display: none; /* Ocultas por defecto */
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-align: center;
            padding: 40px;
        }

        /* ── ESTADO 1: Reposo ── */
        #pantallaReposo {
            display: flex; /* El reposo es el estado inicial */
        }
        .animacion-escaner {
            font-size: 100px;
            color: {{ config('app_client.brand.primary', '#45B2F3') }};
            margin-bottom: 30px;
            animation: flotar 3s ease-in-out infinite;
        }
        @keyframes flotar {
            0% { transform: translateY(0px); }
            50% { transform: translateY(-20px); }
            100% { transform: translateY(0px); }
        }

        /* ── ESTADO 2: Producto ── */
        #pantallaProducto {
            background-color: #ffffff;
            color: #0f172a;
            animation: fadeIn 0.3s ease;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: scale(0.98); }
            to { opacity: 1; transform: scale(1); }
        }
        .prod-marca { font-size: 24px; font-weight: 700; color: {{ config('app_client.brand.primary', '#45B2F3') }}; text-transform: uppercase; letter-spacing: 2px;}
        .prod-nombre { font-size: 55px; font-weight: 800; line-height: 1.1; margin-top: 10px;}
        .prod-precio { font-size: 120px; font-weight: 900; margin: 20px 0; line-height: 1;}
        .prod-stock { font-size: 30px; font-weight: 700; padding: 15px 30px; border-radius: 15px; display: inline-block; }
        
        .stock-ok { background: #dcfce7; color: #16a34a; }
        .stock-agotado { background: #fee2e2; color: #dc2626; }

        /* ── ESTADO 3: Error ── */
        #pantallaError {
            background-color: #0f172a;
            animation: shake 0.4s ease;
        }
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-10px); }
            75% { transform: translateX(10px); }
        }

        /* Loader */
        #kioscoLoader {
            position: fixed; inset: 0; background: rgba(15,23,42,0.95);
            display: none; align-items: center; justify-content: center;
            z-index: 1000; flex-direction: column; color: white; font-size: 24px;
        }
    </style>
</head>
<body>

    @php
        // Extraemos los íconos de la configuración
        $catIcons = config('tablet.categoria_icons', []);
    @endphp

    <input type="text" id="scannerInput" autocomplete="off" spellcheck="false" inputmode="none" autofocus>

    <div class="kiosco-header" id="kioscoHeader">
        <img src="{{ asset(config('app_client.logo-sidebar')) }}" alt="Logo">
        <div class="kiosco-header-info">
            <span class="kiosco-header-name">{{ config('app_client.name') }}</span>
            <span class="kiosco-header-rif">RIF: {{ config('app_client.rif') }}</span>
        </div>
    </div>

    <div id="pantallaReposo" class="pantalla-estado">
        <i class="fas fa-barcode animacion-escaner"></i>
        <h1 style="font-size: 60px; font-weight: 800;">VERIFICADOR DE PRECIOS</h1>
        <p style="font-size: 30px; color: #94a3b8; margin-top: 20px;">Pase el código de barras por el lector</p>
    </div>

    <div id="pantallaProducto" class="pantalla-estado" style="text-align: left; align-items: stretch; justify-content: center;">
        <div class="row w-100 align-items-center">
            <div class="col-8">
                <div class="prod-marca" id="kMarca">MARCA</div>
                <div class="prod-nombre" id="kNombre">Cargando producto...</div>
                <div class="prod-precio" id="kPrecio" style="color: {{ config('app_client.brand.success', '#1cc88a') }}">--</div>
                <div id="kStockContainer" class="prod-stock stock-ok">
                    <i class="fas fa-check-circle me-2"></i><span id="kStock">Consultando...</span>
                </div>
            </div>
            <div class="col-4 text-center">
                <div id="kIcono" style="font-size: 250px; line-height: 1;">📦</div>
            </div>
        </div>
        
        <div style="position: absolute; bottom: 30px; left: 40px; color: #94a3b8; font-size: 20px; font-weight: 600;">
            <i class="fas fa-clock me-2"></i> La pantalla se limpiará automáticamente...
        </div>
    </div>

    <div id="pantallaError" class="pantalla-estado">
        <i class="fas fa-exclamation-triangle animacion-escaner" style="color: {{ config('app_client.brand.danger', '#e74a3b') }}; animation: none;"></i>
        <h1 style="font-size: 60px; font-weight: 800; color: {{ config('app_client.brand.danger', '#e74a3b') }};">ARTÍCULO NO ENCONTRADO</h1>
        <p style="font-size: 30px; color: #94a3b8; margin-top: 20px;">Por favor, acérquese a un vendedor</p>
    </div>

    <div id="kioscoLoader">
        <i class="fas fa-circle-notch fa-spin" style="font-size: 80px; margin-bottom: 20px; color: {{ config('app_client.brand.primary', '#45B2F3') }};"></i>
        <strong>Buscando artículo...</strong>
    </div>

    <div class="kiosco-footer">
        <strong>{{ config('app_client.system.name') }}</strong> v{{ config('app_client.system.version') }}<br>
        Desarrollado por {{ config('app_client.system.built_by') }}
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        $(document).ready(function() {
            const SYM = '{{ config("app_client.locale.currency_symbol", "$") }}';
            const ICONS = {!! json_encode($catIcons) !!}; // Inyectamos los iconos de PHP a JS
            
            const $input = $('#scannerInput');
            let timerAccion = null; // Un solo temporizador maestro para todo

            // Watchdog de foco
            $(document).on('click', function() { $input.focus(); });
            $(window).on('focus', function() { $input.focus(); });

            // Captura de pistola
            $input.on('keypress', function(e) {
                if (e.which === 13) { 
                    e.preventDefault();
                    let codigo = $(this).val().trim();
                    if (codigo !== '') {
                        $(this).val(''); 
                        buscarArticulo(codigo);
                    }
                }
            });

            function ocultarTodasLasPantallas() {
                $('#pantallaReposo, #pantallaProducto, #pantallaError, #kioscoLoader').hide();
            }

            function buscarArticulo(codigo) {
                // BUG FIX: Limpiamos cualquier temporizador previo. 
                // Si alguien escanea a mitad de un error, el reloj se reinicia.
                if(timerAccion) clearTimeout(timerAccion);

                ocultarTodasLasPantallas();
                $('#kioscoLoader').css('display', 'flex');
                
                // Ocultamos el header blanco si vamos a mostrar un fondo blanco (para que no se pierda el logo)
                $('#kioscoHeader').show();

                $.ajax({
                    url: `/tablet/articulo/${codigo}`,
                    type: 'GET',
                    success: function(response) {
                        const art = response.articulo;
                        
                        const precio = parseFloat(art.precio1 || 0);
                        const libre = Math.max(0, (parseFloat(art.stock_actual) || 0) - (parseFloat(art.stock_com) || 0));
                        
                        // Emoji Dinámico
                        const emoji = ICONS[art.categoria] || ICONS['default'] || '📦';
                        $('#kIcono').text(emoji);

                        $('#kMarca').text(art.linea || '{{ config("app_client.name") }}');
                        $('#kNombre').text(art.descripcion);
                        $('#kPrecio').text(`${SYM} ${precio.toLocaleString('es-VE', {minimumFractionDigits:2, maximumFractionDigits:2})}`);
                        
                        const $stockBox = $('#kStockContainer');
                        if (libre <= 0) {
                            $stockBox.removeClass('stock-ok').addClass('stock-agotado');
                            $stockBox.html('<i class="fas fa-times-circle me-2"></i> AGOTADO');
                        } else {
                            $stockBox.removeClass('stock-agotado').addClass('stock-ok');
                            $stockBox.html(`<i class="fas fa-check-circle me-2"></i> DISPONIBLE: ${Math.round(libre)} Uds`);
                        }

                        ocultarTodasLasPantallas();
                        $('#kioscoHeader').hide(); // Ocultamos logo en pantalla producto (opcional, por contraste)
                        $('#pantallaProducto').css('display', 'flex');

                        // Temporizador maestro
                        timerAccion = setTimeout(resetPantalla, 3000);
                    },
                    error: function() {
                        ocultarTodasLasPantallas();
                        $('#pantallaError').css('display', 'flex');
                        
                        // Temporizador maestro (más corto para errores)
                        timerAccion = setTimeout(resetPantalla, 1000);
                    }
                });
            }

            function resetPantalla() {
                ocultarTodasLasPantallas();
                $('#kioscoHeader').show();
                $('#pantallaReposo').css('display', 'flex');
                $input.focus();
            }

            // ==========================================
            // 5. HEARTBEAT (Anti-Vencimiento de Sesión)
            // ==========================================
            // Ejecuta un "ping" silencioso cada 15 minutos (900,000 milisegundos)
            setInterval(function() {
                // Usamos el método HEAD para no descargar el HTML, solo tocar la puerta del servidor
                fetch(window.location.href, { method: 'HEAD' })
                    .then(() => console.log('Latido de sesión enviado.'))
                    .catch(err => console.error('Fallo en el latido de sesión', err));
            }, 900000);
        });
    </script>
</body>
</html>