@extends('layouts.app')

@section('title-page', 'Acerca de LR-Indicadores')

@section('styles')
<style>
    /* ========================================
       VARIABLES GLOBALES - TEMA MORADO
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
        height: auto; /* Permite que el card crezca según su contenido */
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
    .module-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; }
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
        background: linear-gradient(135deg, #2c3e50, #3498db); color: white;
        display: flex; align-items: center; justify-content: center; font-size: 32px; font-weight: 800;
        margin: 0 auto 15px; box-shadow: 0 5px 15px rgba(52, 152, 219, 0.3);
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
@endsection

@section('content')
<div class="container-fluid pb-5">
    @if(session('success'))
        <div class="alert alert-success alert-enhanced alert-dismissible fade show" role="alert">
            <div class="d-flex align-items-center">
                <i class="fas fa-check-circle fa-2x mr-3 text-success"></i>
                <div>
                    <strong>¡Operación Exitosa!</strong><br>
                    {{ session('success') }}
                </div>
            </div>
            <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
        </div>
    @endif

    <div class="page-header-master">
        <div class="logo-container">
            <img src="{{ asset('img/favicon.png') }}" alt="LR-Indicadores Logo" style="width: 60px;">
        </div>
        <h1 class="header-title">LR-Indicadores <span class="version-badge">v2.0 Enterprise</span></h1>
        <p class="header-subtitle">Plataforma Tecnológica Integral desarrollada para JellCell C.A.</p>
    </div>

    <div class="row align-items-start">
        <div class="col-xl-8 col-lg-7">

            <div class="about-card">
                <div class="card-header-pro">
                    <i class="fas fa-bullseye"></i> Propósito del Sistema
                </div>
                <div class="card-body-pro" style="font-size: 15px; color: #5a5c69; line-height: 1.7;">
                    <p>
                        <strong>LR-Indicadores</strong> no es un simple software, es el ecosistema digital diseñado exclusivamente para resolver los desafíos operativos y administrativos de <strong>JellCell</strong>.
                    </p>
                    <p>
                        Su objetivo principal es centralizar la gestión de la información, permitiendo la trazabilidad en tiempo real de los procesos clave: desde la salud integral de nuestros trabajadores, pasando por la seguridad industrial, hasta el control meticuloso de la maquinaria y operaciones agrícolas.
                    </p>
                    <div class="alert alert-info border-0 shadow-sm mt-4 bg-light text-dark">
                        <i class="fas fa-tractor text-primary mr-2"></i> <strong>Gestión Crítica de Cosecha:</strong> LR-Indicadores integra un control estricto de tareas post-quema y cosecha, monitoreando horómetros y controlando al personal directo y contratistas para optimizar la edad de la caña.
                    </div>
                </div>
            </div>

            <div class="about-card">
                <div class="card-header-pro">
                    <i class="fas fa-layer-group"></i> Módulos y Alcance
                </div>
                <div class="card-body-pro">
                    <div class="module-grid">

                        <div class="module-item">
                            <div class="module-icon"><i class="fas fa-user-md"></i></div>
                            <div class="module-info">
                                <h6>Modulo de CXP</h6>
                                <p>Expedientes digitales, CXP, consultas y emisión de reportes.</p>
                            </div>
                        </div>

                        <div class="module-item">
                            <div class="module-icon"><i class="fas fa-hard-hat"></i></div>
                            <div class="module-info">
                                <h6>Modulo de CXC</h6>
                                <p>Expedientes digitales, CXC, consultas y emisión de reportes.</p>
                            </div>
                        </div>

                        <div class="module-item">
                            <div class="module-icon"><i class="fas fa-cogs"></i></div>
                            <div class="module-info">
                                <h6>Modulo de Inventario</h6>
                                <p>Expedientes digitales, Inventario, consultas y emisión de reportes.</p>
                            </div>
                        </div>

                        <div class="module-item" style="grid-column: 1 / -1;">
                            <div class="module-icon"><i class="fas fa-shield-alt"></i></div>
                            <div class="module-info">
                                <h6>Control de Acceso Avanzado</h6>
                                <p>Arquitectura de seguridad basada en Roles y Permisos granulares, garantizando que cada departamento opere con la información adecuada bajo estrictos estándares de confidencialidad.</p>
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
                    <h4 class="dev-name">Ing. Luis Rojas</h4>
                    <div class="dev-role">Full-Stack Lead Developer</div>

                    <p class="text-muted small px-3 mb-4">
                        Arquitecto de software encargado del diseño, desarrollo e implementación total de LR-Indicadores.
                    </p>

                    <a href="https://github.com/luisrojas69" target="_blank" class="btn-github">
                        <i class="fab fa-github fa-lg mr-2"></i> Repositorio Oficial
                    </a>
                </div>
            </div>

            <div class="about-card">
                <div class="card-header-pro">
                    <i class="fas fa-code"></i> Motor Tecnológico
                </div>
                <div class="card-body-pro pt-2">
                    <p class="text-muted small mb-3">Construido utilizando estándares de la industria y herramientas de código abierto de alto rendimiento:</p>

                    <div class="tech-stack-container">
                        <span class="tech-badge"><i class="fab fa-laravel text-danger"></i> Laravel 10.x</span>
                        <span class="tech-badge"><i class="fab fa-php text-primary"></i> PHP 8.x</span>
                        <span class="tech-badge"><i class="fas fa-database text-info"></i> MySQL</span>
                        <span class="tech-badge"><i class="fab fa-bootstrap text-purple" style="color:#563d7c;"></i> Bootstrap 4 (SB Admin)</span>
                        <span class="tech-badge"><i class="fab fa-js text-warning"></i> jQuery / Ajax</span>
                        <span class="tech-badge"><i class="fas fa-lock text-success"></i> Spatie Permissions</span>
                        <span class="tech-badge"><i class="fas fa-file-pdf text-danger"></i> Snappy PDF</span>
                        <span class="tech-badge"><i class="fas fa-table text-secondary"></i> DataTables</span>
                    </div>
                </div>
            </div>

            <div class="text-center mt-4">
                <p class="text-muted small font-weight-bold">
                    &copy; {{ date('Y') }} LR Indicadores.<br>
                    <span class="font-weight-normal">Todos los derechos reservados.</span>
                </p>
            </div>

        </div>
    </div>
</div>
@endsection
