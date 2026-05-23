@extends('layouts.app')

@section('title-page', 'Asignar Roles: ' . $user->name)

@section('styles')
<style>
    /* ========================================
       VARIABLES GLOBALES - TEMA MORADO
    ======================================== */
    :root {
        --purple-primary: #6f42c1;
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
        color: white; padding: 25px 30px; border-radius: 12px;
        margin-bottom: 25px; box-shadow: 0 6px 20px rgba(111, 66, 193, 0.3);
        position: relative; overflow: hidden;
    }
    .page-header-master::before {
        content: ''; position: absolute; top: -50%; right: -10%;
        width: 500px; height: 500px;
        background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
        border-radius: 50%;
    }
    .header-content { position: relative; z-index: 1; }
    .header-icon {
        width: 70px; height: 70px; border-radius: 16px;
        background: rgba(255, 255, 255, 0.2);
        display: flex; align-items: center; justify-content: center; font-size: 32px;
    }
    .header-title h1 { font-size: 26px; font-weight: 700; margin: 0 0 5px 0; }
    .header-subtitle { font-size: 13px; opacity: 0.95; margin:0; }

    /* ========================================
       TARJETA DE PERFIL (IZQUIERDA)
    ======================================== */
    .card-custom {
        border-radius: 12px; border: none; box-shadow: 0 4px 15px rgba(0,0,0,0.05);
        margin-bottom: 25px; overflow: hidden;
    }
    .card-header-custom {
        background: white; border-bottom: 2px solid #f8f9fc;
        padding: 20px 25px; display: flex; justify-content: space-between; align-items: center;
    }
    .card-title-custom { font-size: 16px; font-weight: 700; color: #2c3e50; margin: 0; display: flex; align-items: center; }
    .card-title-custom i { color: var(--purple-primary); margin-right: 10px; font-size: 18px; }

    .avatar-circle-lg {
        width: 80px; height: 80px; margin: 0 auto 15px;
        background: linear-gradient(135deg, var(--purple-primary) 0%, var(--purple-dark) 100%);
        color: white; display: flex; align-items: center; justify-content: center;
        border-radius: 50%; font-weight: 800; font-size: 28px;
        box-shadow: 0 6px 15px rgba(111, 66, 193, 0.3);
    }

    .btn-submit-master {
        background: linear-gradient(135deg, var(--success), #13855c);
        color: white; border: none; padding: 14px 20px; border-radius: 8px;
        font-weight: 700; font-size: 15px; box-shadow: 0 4px 15px rgba(28, 200, 138, 0.3);
        transition: all 0.3s; width: 100%; display: flex; justify-content: center; align-items: center;
    }
    .btn-submit-master:hover { transform: translateY(-2px); box-shadow: 0 8px 25px rgba(28, 200, 138, 0.4); color: white; }

    /* ========================================
       TARJETAS DE ROLES (SWITCH CARDS)
    ======================================== */
    .search-wrapper { position: relative; width: 300px; }
    .search-wrapper i { position: absolute; left: 15px; top: 50%; transform: translateY(-50%); color: #b7b9cc; }
    .search-wrapper input { padding-left: 40px; border-radius: 50px; }

    .role-card-item { margin-bottom: 15px; }
    .role-switch-wrapper {
        background: #f8f9fc; border: 2px solid #eaecf4; border-radius: 10px;
        padding: 15px; display: flex; justify-content: space-between; align-items: center;
        transition: all 0.2s ease; cursor: pointer; height: 100%;
    }
    .role-switch-wrapper:hover { border-color: var(--purple-light); background: white; box-shadow: 0 4px 10px rgba(0,0,0,0.03); }
    
    /* Estado Activo del Rol */
    .role-switch-wrapper.is-active {
        border-color: var(--purple-primary); background: rgba(111, 66, 193, 0.05);
    }
    .role-switch-wrapper.is-active .role-name { color: var(--purple-dark); font-weight: 800; }
    .role-switch-wrapper.is-active .role-icon-box { background: var(--purple-primary); color: white; }

    .role-info-container { display: flex; align-items: center; gap: 15px; flex: 1; }
    .role-icon-box { 
        width: 45px; height: 45px; border-radius: 10px; background: white; color: #b7b9cc;
        display: flex; align-items: center; justify-content: center; font-size: 18px;
        border: 1px solid #eaecf4; transition: all 0.2s;
    }
    .role-details { display: flex; flex-direction: column; }
    .role-name { font-size: 15px; font-weight: 700; color: #5a5c69; margin: 0; text-transform: uppercase; transition: color 0.2s; }
    .role-meta { font-size: 12px; color: #858796; margin-top: 2px; }

    /* Customizar el switch de Bootstrap */
    .custom-switch { padding-left: 2.5rem; }
    .custom-control-input:checked ~ .custom-control-label::before { border-color: var(--purple-primary); background-color: var(--purple-primary); }

    .info-alert-pro {
        background: rgba(54, 185, 204, 0.1); border-left: 4px solid var(--info);
        border-radius: 8px; padding: 15px 20px; color: #2c3e50; font-size: 14px;
    }
</style>
@endsection

@section('content')
<div class="container-fluid pb-5">

    <div class="page-header-master">
        <div class="header-content">
            <div class="d-flex justify-content-between align-items-center flex-wrap">
                <div class="d-flex align-items-center mb-2 mb-md-0">
                    <div class="header-icon mr-3">
                        <i class="fas fa-user-lock"></i>
                    </div>
                    <div class="header-title">
                        <h1>Matriz de Acceso de Usuario</h1>
                        <p class="header-subtitle">
                            <i class="fas fa-id-badge mr-2"></i> Asignando roles y privilegios en el sistema
                        </p>
                    </div>
                </div>
                <div>
                    <a href="{{ route('admin.users.index') }}" class="btn btn-light rounded-pill font-weight-bold shadow-sm px-4">
                        <i class="fas fa-arrow-left mr-2"></i>Volver al Directorio
                    </a>
                </div>
            </div>
        </div>
    </div>

    <form method="POST" action="{{ route('admin.users.update-roles', $user) }}" id="rolesForm">
        @csrf @method('PUT')

        <div class="row">
            <div class="col-xl-4 col-lg-5 mb-4">
                <div class="card-custom sticky-top" style="top: 20px;">
                    <div class="card-header-custom bg-white">
                        <h6 class="card-title-custom"><i class="fas fa-user-circle"></i> Ficha del Usuario</h6>
                    </div>
                    <div class="card-body p-4 bg-white text-center border-bottom">
                        <div class="avatar-circle-lg">
                            {{ strtoupper(substr($user->name, 0, 1)) }}{{ strtoupper(substr($user->last_name ?? '', 0, 1)) }}
                        </div>
                        <h4 class="font-weight-bold text-gray-800 mb-1">{{ $user->name }} {{ $user->last_name ?? '' }}</h4>
                        <p class="text-muted mb-3"><i class="fas fa-envelope mr-1"></i> {{ $user->email }}</p>
                        
                        <div class="d-flex justify-content-center gap-2 mb-2">
                            <span class="badge badge-light border text-muted px-3 py-2 shadow-sm">
                                ID: #{{ str_pad($user->id, 4, '0', STR_PAD_LEFT) }}
                            </span>
                        </div>
                    </div>
                    <div class="card-body p-4 bg-light">
                        <div class="d-flex justify-content-between align-items-center mb-4 p-3 bg-white rounded-lg shadow-sm border">
                            <span class="font-weight-bold text-gray-700 small text-uppercase">Roles Asignados:</span>
                            <span class="badge badge-primary badge-pill" id="counterSelected" style="font-size: 14px;">
                                {{ count($userRoles ?? []) }}
                            </span>
                        </div>

                        <button type="submit" class="btn-submit-master">
                            <i class="fas fa-check-circle mr-2"></i> Guardar Privilegios
                        </button>
                    </div>
                </div>
            </div>

            <div class="col-xl-8 col-lg-7">
                
                <div class="info-alert-pro mb-4 shadow-sm">
                    <div class="d-flex align-items-start">
                        <i class="fas fa-info-circle fa-2x text-info mr-3"></i>
                        <div>
                            <strong class="d-block mb-1">Herencia de Permisos</strong>
                            Al activar un rol, el usuario heredará automáticamente todos los permisos contenidos en dicho perfil. Si activa múltiples roles, los permisos se sumarán.
                        </div>
                    </div>
                </div>

                <div class="card-custom">
                    <div class="card-header-custom flex-wrap gap-3">
                        <h6 class="card-title-custom"><i class="fas fa-layer-group"></i> Roles Disponibles en el Sistema</h6>
                        
                        <div class="search-wrapper">
                            <i class="fas fa-search"></i>
                            <input type="text" id="filterRoles" class="form-control form-control-custom" placeholder="Buscar rol...">
                        </div>
                    </div>

                    <div class="card-body p-4 bg-white">
                        <div class="row" id="rolesGrid">
                            @foreach ($roles as $role)
                                @php
                                    // Comprobación de rol activo
                                    $isChecked = in_array($role->name, $userRoles);
                                    // Detectar si es el rol de Super Admin
                                    $isSuperAdmin = in_array(strtolower($role->name), ['super_admin', 'super_administrador']);
                                @endphp
                                
                                <div class="col-md-6 role-card-item">
                                    <label class="role-switch-wrapper {{ $isChecked ? 'is-active' : '' }}" for="role_{{ $role->id }}">
                                        
                                        <div class="role-info-container">
                                            <div class="role-icon-box">
                                                <i class="fas {{ $isSuperAdmin ? 'fa-crown text-warning' : 'fa-user-tag' }}"></i>
                                            </div>
                                            <div class="role-details">
                                                <span class="role-name">{{ str_replace('_', ' ', $role->name) }}</span>
                                                <span class="role-meta">
                                                    <i class="fas fa-shield-alt mr-1"></i> {{ $role->permissions->count() ?? 0 }} permisos
                                                </span>
                                            </div>
                                        </div>

                                        <div class="custom-control custom-switch">
                                            <input type="checkbox" name="roles[]" value="{{ $role->name }}" 
                                                   id="role_{{ $role->id }}" 
                                                   class="custom-control-input role-checkbox"
                                                   {{ $isChecked ? 'checked' : '' }}>
                                            <label class="custom-control-label" for="role_{{ $role->id }}"></label>
                                        </div>
                                    </label>
                                </div>
                            @endforeach
                        </div>

                        <div id="noResultsMsg" class="text-center py-5 d-none">
                            <i class="fas fa-search-minus fa-3x text-gray-300 mb-3"></i>
                            <h5 class="text-gray-500 font-weight-bold">No se encontró el rol</h5>
                            <p class="text-muted">Intenta buscar con otra palabra clave.</p>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
    $(document).ready(function() {
        
        // 1. Efecto Visual al hacer check (Iluminar Tarjeta)
        $('.role-checkbox').on('change', function() {
            let wrapper = $(this).closest('.role-switch-wrapper');
            if ($(this).is(':checked')) {
                wrapper.addClass('is-active');
            } else {
                wrapper.removeClass('is-active');
            }
            actualizarContador();
        });

        // 2. Buscador Inteligente
        $('#filterRoles').on('keyup', function() {
            let val = $(this).val().toLowerCase();
            let visibleCount = 0;

            $('.role-card-item').each(function() {
                let txt = $(this).find('.role-name').text().toLowerCase();
                if (txt.includes(val)) {
                    $(this).show();
                    visibleCount++;
                } else {
                    $(this).hide();
                }
            });

            // Mostrar mensaje si no hay resultados
            if (visibleCount === 0) {
                $('#noResultsMsg').removeClass('d-none');
            } else {
                $('#noResultsMsg').addClass('d-none');
            }
        });

        // 3. Función para actualizar contador lateral en tiempo real
        function actualizarContador() {
            let total = $('.role-checkbox:checked').length;
            $('#counterSelected').text(total);
        }
        
    });
</script>
@endpush