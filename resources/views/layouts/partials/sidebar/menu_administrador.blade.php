    <div class="sidebar-heading">
        {{ __('Configuraciones') }}
    </div>
    @can('seguridad.menu')
        @can('seguridad.roles.ver')
        <li class="nav-item {{ Nav::isRoute('admin.roles.index') }}">
            <a class="nav-link" href="{{ route('admin.roles.index') }}">
                <i class="fas fa-user-lock"></i>
                <span>{{ __('Roles') }}</span>
            </a>
        </li>
        @endcan
        @can('seguridad.permisos.ver')
        <li class="nav-item {{ Nav::isRoute('admin.permissions.index') }}">
            <a class="nav-link" href="{{ route('admin.permissions.index') }}">
                <i class="fas fa-user-shield"></i>
                <span>{{ __('Permisos') }}</span>
            </a>
        </li>
        @endcan
        @can('seguridad.usuarios.ver')
        <li class="nav-item {{ Nav::isRoute('admin.users.index') }}">
            <a class="nav-link" href="{{ route('admin.users.index') }}">
                <i class="fas fa-user-gear"></i>
                <span>{{ __('Usuarios') }}</span>
            </a>
        </li>
        @endcan
    @endcan
    <hr class="sidebar-divider d-none d-md-block">