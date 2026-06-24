{{--
    Página informativa acerca de LR-Indicadores, su propósito, módulos, y el perfil del desarrollador.
--}}
@extends('layouts.app')
@section('hide_daterange', true)
@section('title-page', 'Acerca de '. config('app_client.system.name')   .' v'. config('app_client.system.version'))

@section('breadcrumb')
    <span class="current">Acerca de <b>{{ config('app_client.system.name', 'ERP') }}</b> </span>
@endsection

@push('styles')
<style>
    /* ========================================
       VARIABLES GLOBALES - TEMA MORADO / CORPORATIVO
    ======================================== */
    :root {
        --purple-primary: #4e73df;
        --purple-dark: #5a32a3;
        --purple-light: #8458cf;
        --success: #1cc88a;
        --danger: #e74a3b;
        --warning: #f6c23e;
        --info: #36b9cc;
    }

    body { background: #f8f9fc; }

    /* ========================================
       HEADER PRINCIPAL
    ======================================== */
    .page-header-master {
        background: linear-gradient(135deg, var(--purple-primary) 0%, var(--purple-dark) 100%);
        color: white; padding: 30px; border-radius: 12px;
        margin-bottom: 40px; box-shadow: 0 6px 20px rgba(111, 66, 193, 0.3);
        position: relative; overflow: hidden; text-align: center;
    }
    .page-header-master::before {
        content: ''; position: absolute; top: -150%; left: -10%;
        width: 600px; height: 600px;
        background: radial-gradient(circle, rgba(255,255,255,0.08) 0%, transparent 70%);
        border-radius: 50%;
    }

    .logo-container {
        width: 100px; height: 100px; margin: 0 auto 15px;
        background: white; border-radius: 25px;
        display: flex; align-items: center; justify-content: center;
        box-shadow: 0 10px 25px rgba(0,0,0,0.2);
        position: relative; z-index: 2; transform: rotate(-5deg);
        transition: transform 0.3s ease;
    }
    .logo-container:hover { transform: rotate(0deg) scale(1.05); }
    .logo-container img { max-width: 65%; max-height: 65%; transform: rotate(5deg); }
    .logo-container:hover img { transform: rotate(0deg); }

    .header-title { font-size: 32px; font-weight: 800; letter-spacing: 1px; margin-bottom: 5px; position: relative; z-index: 2; }
    .header-subtitle { font-size: 16px; font-weight: 300; opacity: 0.9; position: relative; z-index: 2; }

    .version-badge {
        background: rgba(255,255,255,0.2); border: 1px solid rgba(255,255,255,0.3);
        padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 700;
        backdrop-filter: blur(4px); margin-left: 10px; vertical-align: middle;
    }

    /* ========================================
       TARJETAS DE INFORMACIÓN
    ======================================== */
    .about-card {
        background: white;
        border-radius: 15px;
        border: none;
        box-shadow: 0 4px 15px rgba(0,0,0,0.04);
        margin-bottom: 25px;
        transition: transform 0.3s ease, box-shadow 0.3s ease;
        height: auto;
    }
    .about-card:hover { transform: translateY(-5px); box-shadow: 0 10px 25px rgba(0,0,0,0.08); }

    .card-header-pro {
        background: transparent; border-bottom: 2px solid #f8f9fc;
        padding: 20px 25px; font-weight: 800; color: #4a5568; font-size: 16px;
        display: flex; align-items: center;
    }
    .card-header-pro i { color: var(--purple-primary); margin-right: 12px; font-size: 20px; }
    .card-body-pro { padding: 25px; }

    /* ========================================
       MÓDULOS DEL SISTEMA
    ======================================== */
    .module-grid {
        display: grid;
        /* Cambiamos auto-fit por auto-fill y aumentamos un poco el mínimo para que se vea más robusto */
        grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
        gap: 15px;
        width: 100%; /* Aseguramos que el contenedor use todo el ancho del card-body */
    }
    .module-item {
        background: #f8f9fc; border: 1px solid #eaecf4; border-radius: 10px;
        padding: 15px; display: flex; align-items: flex-start; transition: all 0.2s;
    }
    .module-item:hover { border-color: var(--purple-light); background: white; box-shadow: 0 4px 10px rgba(111, 66, 193, 0.08); }
    .module-icon {
        width: 40px; height: 40px; border-radius: 8px;
        background: rgba(111, 66, 193, 0.1); color: var(--purple-primary);
        display: flex; align-items: center; justify-content: center; font-size: 18px; margin-right: 15px; flex-shrink: 0;
    }
    .module-info h6 { font-weight: 700; color: #2c3e50; margin-bottom: 3px; font-size: 14px; }
    .module-info p { font-size: 12px; color: #858796; margin: 0; line-height: 1.4; }

    /* ========================================
       PERFIL DEL DESARROLLADOR & TECH STACK
    ======================================== */
    .dev-profile { text-align: center; padding: 10px; }
    .dev-avatar {
        width: 90px; height: 90px; border-radius: 50%;
        background: linear-gradient(135deg, #2c3e50, #4e73df); color: white;
        display: flex; align-items: center; justify-content: center; font-size: 32px; font-weight: 800;
        margin: 0 auto 15px; box-shadow: 0 5px 15px rgba(78, 115, 223, 0.3);
    }
    .dev-name { font-size: 18px; font-weight: 800; color: #2c3e50; margin-bottom: 2px; }
    .dev-role { font-size: 13px; color: var(--purple-primary); font-weight: 700; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 15px; }

    .tech-stack-container { display: flex; flex-wrap: wrap; gap: 8px; margin-top: 15px; }
    .tech-badge {
        background: white; border: 1px solid #e3e6f0; color: #5a5c69;
        padding: 6px 12px; border-radius: 20px; font-size: 12px; font-weight: 600;
        display: flex; align-items: center; transition: all 0.2s;
    }
    .tech-badge i { margin-right: 6px; font-size: 14px; }
    .tech-badge:hover { border-color: var(--purple-primary); color: var(--purple-primary); background: rgba(111, 66, 193, 0.05); }

    .btn-github {
        background: #24292e; color: white; font-weight: 600; border-radius: 8px;
        padding: 10px 20px; transition: all 0.2s; border: none; display: inline-flex; align-items: center;
    }
    .btn-github:hover { background: #1b1f23; transform: translateY(-2px); box-shadow: 0 5px 15px rgba(36, 41, 46, 0.3); color: white; }
</style>
@endpush

@section('content')
<div class="container-fluid pb-5">
    <div class="page-header-master">
        <div class="logo-container">
            <img src="{{ asset('img/favicon.png') }}" alt="{{ config('app_client.system.name') }} v{{ config('app_client.system.version') }}" style="width: 60px;">
        </div>
        <h1 class="header-title">{{ config('app_client.system.name') }}  <span class="version-badge">v{{ config('app_client.system.version') }} Enterprise</span></h1>
        <p class="header-subtitle">Panel Gerencial de Control y Analítica de Datos Comercial y de Inventario</p>
    </div>

    <div class="row align-items-start">
        <div class="col-xl-8 col-lg-7">

            <div class="about-card">
                <div class="card-header-pro">
                    <i class="fas fa-bullseye"></i> Propósito del Sistema
                </div>
                <div class="card-body-pro" style="font-size: 15px; color: #5a5c69; line-height: 1.7;">
                    <p>
                        <strong>{{ config('app_client.system.name') }} v{{ config('app_client.system.version') }}</strong> es una solución de inteligencia empresarial y analítica visual de alto nivel, diseñada de manera exclusiva para abstraer y procesar la información neurálgica operativa y comercial de la organización.
                    </p>
                    <p>
                        A diferencia de los paneles rígidos tradicionales, esta plataforma ofrece un ecosistema ágil y desacoplado que unifica el control gerencial. Permite auditar en tiempo real desde la facturación acumulada, el rendimiento individual de la fuerza de ventas, y la antigüedad de las cuentas por cobrar, hasta la fluctuación de los márgenes comerciales brutos y la rotación analítica de inventarios.
                    </p>


                    <div class="panel-card-body py-3" bis_skin_checked="1">
                        <div class="d-flex align-items-center justify-content-between flex-wrap gap-3" bis_skin_checked="1">
                            <div bis_skin_checked="1">
                                <p class="mb-1" style="font-size:13px;font-weight:700;color:var(--text-primary);">
                                    <i class="fas fa-link text-primary mr-2"></i>
                                    Filosofía Core de Desacoplamiento:
                                </p>
                                <p class="mb-0" style="font-size:12px;color:var(--text-secondary);">
                                    Toda la lógica de negocio se encuentra orquestada a través de interfaces rigurosas en Laravel. Esto asegura que la capa de visualización e informes gerenciales permanezca intacta e independiente del driver de base de datos o proveedor del ERP central.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="about-card">
                <div class="card-header-pro">
                    <i class="fas fa-layer-group"></i> Módulos y Alcance Gerencial
                </div>
                <div class="card-body-pro">
                    <!-- Reemplazamos module-grid por clases nativas de Bootstrap 5 -->
                    <div class="row row-cols-1 row-cols-md-2 row-cols-xl-3 g-3">

                        <!-- Elemento 1 -->
                        <div class="col">
                            <div class="module-item h-100"> <!-- h-100 para que todos tengan la misma altura -->
                                <div class="module-icon"><i class="fas fa-file-invoice-dollar"></i></div>
                                <div class="module-info">
                                    <h6>Módulo Cuentas por Cobrar (CxC)</h6>
                                    <p>Análisis de saldos vencidos, conciliación implícita de cobros mensuales vs. montos facturados y proyección de cartera corriente.</p>
                                </div>
                            </div>
                        </div>

                        <!-- Elemento 2 -->
                        <div class="col">
                            <div class="module-item h-100">
                                <div class="module-icon"><i class="fas fa-chart-line"></i></div>
                                <div class="module-info">
                                    <h6>Módulo de Márgenes y Ventas</h6>
                                    <p>Auditoría de rentabilidad bruta por artículo utilizando costos promedio unitarios reales, alertas automáticas por semáforos y cálculo dinámico de bonificaciones.</p>
                                </div>
                            </div>
                        </div>

                        <!-- Elemento 3 -->
                        <div class="col">
                            <div class="module-item h-100">
                                <div class="module-icon"><i class="fas fa-boxes"></i></div>
                                <div class="module-info">
                                    <h6>Módulo de Inventario Consolidado</h6>
                                    <p>Trazabilidad física de almacenes, auditoría analítica de stock crítico/bajo, flujos de entradas y monitoreo selectivo de salidas no comerciales.</p>
                                </div>
                            </div>
                        </div>

                        <!-- Elemento de Ancho Completo (Control de Acceso) -->
                        <div class="col-12 w-100">
                            <div class="module-item">
                                <div class="module-icon"><i class="fas fa-shield-alt"></i></div>
                                <div class="module-info">
                                    <h6>Seguridad y Capa de Datos Granular</h6>
                                    <p>Implementación de seguridad perimetral controlada por roles y permisos dinámicos. Restringe accesos según perfiles de usuario, garantizando confidencialidad extrema en KPIs financieros, listados de artículos estratégicos y datos de clientes.</p>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>

        </div>

        <div class="col-xl-4 col-lg-5">

            <div class="about-card">
                <div class="card-body-pro dev-profile">
                    <div class="dev-avatar">LR</div>
                    <h4 class="dev-name">{{ config('app_client.system.built_by') }}</h4>
                    <div class="dev-role">Senior Full-Stack & Systems Engineer</div>

                    <p class="text-muted small px-3 mb-4">
                        Arquitecto de software senior encargado de la concepción de la arquitectura, diseño relacional, modelado de contratos de software, scripts automatizados e implementación punta a punta de LR-Indicadores.
                    </p>

                    <a href="{{ config('app_client.system.support_url') }} " target="_blank" class="btn-github">
                        <i class="fab fa-github fa-lg mr-2"></i> Repositorio Oficial
                    </a>
                </div>
            </div>

            <div class="about-card">
                <div class="card-header-pro">
                    <i class="fas fa-microchip"></i> Motor Tecnológico
                </div>
                <div class="card-body-pro pt-2">
                    <p class="text-muted small mb-3">Construido utilizando estándares corporativos rígidos, herramientas robustas de código abierto y procesamiento nativo empresarial:</p>

                    <div class="tech-stack-container">
                        <span class="tech-badge" title="Framework Backend Principal"><i class="fab fa-laravel text-danger"></i> Laravel 10.x</span>
                        <span class="tech-badge" title="Lenguaje de Programación Servidor"><i class="fab fa-php text-primary"></i> PHP 8.2</span>
                        <span class="tech-badge" title="Motor del ERP Corporativo"><i class="fas fa-database text-info"></i> SQL Server / Profit Plus</span>
                        <span class="tech-badge" title="Motor de Reportes Analíticos Avanzados"><i class="fab fa-python text-success"></i> Python 3 (openpyxl)</span>
                        <span class="tech-badge" title="Entorno de Despliegue e Infraestructura"><i class="fab fa-docker text-primary" style="color:#0db7ed;"></i> Docker Containers</span>
                        <span class="tech-badge" title="Manejador de Base de Datos Local/Caché"><i class="fas fa-database text-secondary"></i> MySQL</span>
                        <span class="tech-badge" title="Framework Frontend Visual"><i class="fab fa-bootstrap" style="color:#563d7c;"></i> Bootstrap 4 (SB Admin 2)</span>
                        <span class="tech-badge" title="Capa de Interacción Dinámica Asíncrona"><i class="fab fa-js text-warning"></i> jQuery / Axios Ajax</span>
                        <span class="tech-badge" title="Sistema de Autorización Granular"><i class="fas fa-lock text-success"></i> Spatie Permissions</span>
                        <span class="tech-badge" title="Tablas Dinámicas del Lado del Cliente"><i class="fas fa-table text-dark"></i> DataTables JS</span>
                    </div>
                </div>
            </div>

            <div class="text-center mt-4">
                <p class="text-muted small font-weight-bold">
                    &copy; {{ date('Y') }} {{ config('app_client.system.name') }} v{{ config('app_client.system.version') }}.<br>
                    <span class="font-weight-normal">Todos los derechos reservados.</span>
                </p>
            </div>

        </div>
    </div>
</div>
@endsection
