<style>
    /* ========================================
       TOPBAR PREMIUM ESTILOS (TEMA AZUL)
    ======================================== */
    .topbar-premium {
        background-color: #ffffff;
        border-bottom: 1px solid #eaecf4;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.03) !important;
        height: 4.5rem;
    }

    /* Buscador Global Estilo MacOS / Notion */
    .global-search-form {
        position: relative;
        width: 350px;
        margin-left: 1rem;
    }
    .global-search-input {
        background: #f8f9fc;
        border: 1px solid #eaecf4;
        border-radius: 8px;
        padding: 0.5rem 1rem 0.5rem 2.5rem;
        font-size: 0.85rem;
        transition: all 0.3s;
        width: 100%;
    }
    .global-search-input:focus {
        background: #ffffff;
        border-color: #4e73df;
        box-shadow: 0 0 0 0.2rem rgba(78, 115, 223, 0.15);
        outline: none;
    }
    .global-search-icon {
        position: absolute;
        left: 12px;
        top: 50%;
        transform: translateY(-50%);
        color: #b7b9cc;
        font-size: 0.85rem;
    }
    .search-shortcut {
        position: absolute;
        right: 12px;
        top: 50%;
        transform: translateY(-50%);
        background: #eaecf4;
        padding: 2px 6px;
        border-radius: 4px;
        font-size: 0.7rem;
        color: #858796;
        font-weight: bold;
    }

    /* Dropdowns Premium */
    .dropdown-menu-premium {
        border: none;
        border-radius: 12px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        padding: 0;
        overflow: hidden;
        margin-top: 10px !important;
    }
    .dropdown-header-premium {
        background: linear-gradient(135deg, #4e73df 0%, #2e59d9 100%);
        color: white;
        font-weight: 700;
        padding: 1rem;
        font-size: 0.85rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        border-bottom: none;
    }

    /* Lanzador de Módulos (Grid) */
    .module-launcher-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 10px;
        padding: 15px;
        width: 320px;
    }
    .module-app-item {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        padding: 15px 10px;
        border-radius: 10px;
        transition: all 0.2s;
        text-decoration: none !important;
        color: #5a5c69;
    }
    .module-app-item:hover {
        background: #f8f9fc;
        transform: translateY(-2px);
    }
    .module-app-icon {
        width: 40px;
        height: 40px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.2rem;
        margin-bottom: 8px;
        color: white;
    }

    /* Notificaciones */
    .notification-item {
        padding: 12px 15px;
        border-bottom: 1px solid #eaecf4;
        display: flex;
        align-items: flex-start;
        transition: background 0.2s;
        text-decoration: none !important;
    }
    .notification-item:hover { background: #f8f9fc; }
    .notification-item:last-child { border-bottom: none; }
    .notif-icon-circle {
        width: 35px; height: 35px; border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
        color: white; flex-shrink: 0; margin-right: 12px; font-size: 0.8rem;
    }
    .notif-content p { font-size: 0.85rem; color: #5a5c69; margin: 0; line-height: 1.3; }
    .notif-time { font-size: 0.7rem; color: #b7b9cc; margin-top: 4px; display: block; }
    .notif-indicator { position: absolute; top: 15px; right: 8px; font-size: 0.55rem; border: 2px solid white; }

    /* Avatar y Usuario */
    .topbar-avatar-circle {
        width: 35px; height: 35px; border-radius: 50%;
        background: linear-gradient(135deg, #4e73df 0%, #2e59d9 100%);
        color: white; display: flex; align-items: center; justify-content: center;
        font-weight: 800; font-size: 14px; box-shadow: 0 2px 5px rgba(78, 115, 223, 0.3);
    }
    .user-dropdown-item {
        padding: 10px 15px; font-size: 0.85rem; color: #5a5c69; display: flex; align-items: center; transition: all 0.2s;
    }
    .user-dropdown-item i { width: 20px; color: #b7b9cc; transition: color 0.2s; }
    .user-dropdown-item:hover { background: rgba(78, 115, 223, 0.05); color: #4e73df; }
    .user-dropdown-item:hover i { color: #4e73df; }

    @media (max-width: 768px) {
        .global-search-form { display: none; }
    }
</style>

<nav class="navbar navbar-expand navbar-light bg-white topbar mb-4 static-top topbar-premium">

    <button id="sidebarToggleTop" class="btn btn-link d-md-none rounded-circle mr-3">
        <i class="fa fa-bars text-primary"></i>
    </button>

    <form class="global-search-form d-none d-sm-inline-block form-inline mr-auto my-2 my-md-0 mw-100 navbar-search">
        <i class="fas fa-search global-search-icon"></i>
        <input type="text" class="global-search-input" placeholder="Buscar pacientes, equipos, órdenes..." aria-label="Search">
        <span class="search-shortcut d-none d-lg-block">Ctrl+K</span>
    </form>

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

        <li class="nav-item dropdown no-arrow mx-1">
            <a class="nav-link dropdown-toggle" href="#" id="appsDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                <i class="fas fa-th fa-fw" style="font-size: 1.1rem; color: #b7b9cc;"></i>
            </a>
            <div class="dropdown-list dropdown-menu dropdown-menu-right dropdown-menu-premium animated--grow-in" aria-labelledby="appsDropdown">
                <h6 class="dropdown-header-premium d-flex justify-content-between align-items-center">
                    Aplicaciones Disponibles
                    <i class="fas fa-rocket"></i>
                </h6>
                <div class="module-launcher-grid bg-white">
                    @if(Auth::user() && Auth::user()->hasRole('super_admin'))
                        <a href="{{ route('admin.users.index') }}" class="module-app-item">
                            <div class="module-app-icon bg-gradient-danger shadow-sm"><i class="fas fa-user-gear"></i></div>
                            <span class="small font-weight-bold">Usuarios</span>
                        </a>
                        <a href="{{ route('admin.roles.index') }}" class="module-app-item">
                            <div class="module-app-icon bg-gradient-warning shadow-sm"><i class="fas fa-user-lock"></i></div>
                            <span class="small font-weight-bold">Roles</span>
                        </a>
                        <a href="{{ route('admin.permissions.index') }}" class="module-app-item">
                            <div class="module-app-icon bg-gradient-info shadow-sm"><i class="fas fa-user-shield"></i></div>
                            <span class="small font-weight-bold">Permisos</span>
                        </a>
                    @endif
                    <a href="{{ route('about') }}" class="module-app-item">
                        <div class="module-app-icon bg-gradient-primary shadow-sm"><i class="fas fa-fw fa-hands-helping"></i></div>
                        <span class="small font-weight-bold">Acerca de:</span>
                    </a>
                    <a href="{{ route('profile') }}" class="module-app-item">
                        <div class="module-app-icon bg-gradient-success shadow-sm"><i class="fas fa-id-badge"></i></div>
                        <span class="small font-weight-bold">MI Pefil</span>
                    </a>
                    <a href="#" class="module-app-item" data-toggle="modal" data-target="#logoutModal">
                        <div class="module-app-icon bg-gradient-danger shadow-sm"><i class="fas fa-sign-out-alt"></i></div>
                        <span class="small font-weight-bold">Logout</span>
                    </a>
                </div>
            </div>
        </li>

        <li class="nav-item dropdown no-arrow mx-1">
            <a class="nav-link dropdown-toggle" href="#" id="alertsDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                <i class="fas fa-bell fa-fw" style="font-size: 1.1rem; color: #b7b9cc;"></i>
                <span class="badge badge-danger badge-counter notif-indicator rounded-circle">3</span>
            </a>
            <div class="dropdown-list dropdown-menu dropdown-menu-right dropdown-menu-premium animated--grow-in" aria-labelledby="alertsDropdown" style="width: 320px;">
                <h6 class="dropdown-header-premium">
                    Centro de Alertas
                </h6>
                <div class="bg-white">
                    <a class="notification-item" href="#">
                        <div class="notif-icon-circle bg-success shadow-sm"><i class="fas fa-vial"></i></div>
                        <div class="notif-content">
                            <span class="font-weight-bold text-dark d-block" style="font-size: 0.8rem;">Resultados Listos</span>
                            <p>Los laboratorios de Juan Pérez ya están disponibles en el sistema.</p>
                            <span class="notif-time">Hace 10 min</span>
                        </div>
                    </a>
                    <a class="notification-item" href="#">
                        <div class="notif-icon-circle bg-warning shadow-sm"><i class="fas fa-exclamation-triangle"></i></div>
                        <div class="notif-content">
                            <span class="font-weight-bold text-dark d-block" style="font-size: 0.8rem;">Alerta de Mantenimiento</span>
                            <p>Tractor John Deere (JD-04) superó las horas límite de servicio.</p>
                            <span class="notif-time">Hace 2 horas</span>
                        </div>
                    </a>
                    <a class="dropdown-item text-center small text-primary font-weight-bold py-3 bg-light" href="#">Mostrar Todas las Alertas</a>
                </div>
            </div>
        </li>

        <div class="topbar-divider d-none d-sm-block"></div>

        <li class="nav-item dropdown no-arrow">
            <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                @auth
                    <div class="d-flex flex-column align-items-end mr-3 d-none d-lg-flex">
                        <span class="text-gray-800 font-weight-bold small mb-0">{{ Auth::user()->full_name }}</span>
                        <span class="text-primary" style="font-size: 0.65rem; font-weight: 700; text-transform: uppercase;">
                            {{ Auth::user()->roles->first()->name ?? 'Usuario' }}
                        </span>
                    </div>
                    <div class="topbar-avatar-circle">
                        {{ strtoupper(substr(Auth::user()->full_name, 0, 1)) }}
                    </div>
                @endauth
            </a>

            <div class="dropdown-menu dropdown-menu-right dropdown-menu-premium animated--grow-in" aria-labelledby="userDropdown" style="width: 250px;">
                <div class="px-4 py-3 bg-light border-bottom text-center">
                    <div class="topbar-avatar-circle mx-auto mb-2" style="width: 50px; height: 50px; font-size: 20px;">
                        {{ strtoupper(substr(Auth::user()->full_name ?? 'Usuario', 0, 1)) }}
                    </div>
                    <h6 class="font-weight-bold text-dark mb-0">{{ Auth::user()->full_name ?? 'Usuario' }}</h6>
                    <small class="text-muted">{{ Auth::user()->email ?? 'correo@ejemplo.com' }}</small>
                </div>

                <div class="py-2">
                    <a class="user-dropdown-item" href="{{ route('profile') }}">
                        <i class="fas fa-id-badge"></i> {{ __('Mi Perfil y Preferencias') }}
                    </a>
                    <a class="user-dropdown-item" href="#">
                        <i class="fas fa-shield-alt"></i> {{ __('Seguridad de la Cuenta') }}
                    </a>

                    @if(Auth::user() && Auth::user()->hasRole('super_admin'))
                    <div class="dropdown-divider"></div>
                    <a class="user-dropdown-item text-danger" href="#">
                        <i class="fas fa-cogs"></i> {{ __('Administración del Sistema') }}
                    </a>
                    @endif

                    <div class="dropdown-divider"></div>

                    <a class="user-dropdown-item" href="#" data-toggle="modal" data-target="#logoutModal">
                        <i class="fas fa-sign-out-alt"></i> {{ __('Cerrar Sesión') }}
                    </a>
                </div>
            </div>
        </li>

    </ul>

</nav>
