{{--
    Pagina para asignar roles a los usuarios.
--}}
@extends('layouts.app')
@section('title-page', 'Asignar Roles: ' . $user->name)

@section('breadcrumb')
    <a href="{{ route('admin.users.index') }}" style="color:var(--text-muted);text-decoration:none;">Usuarios</a>
    <span style="color:#cbd5e1;margin:0 4px;">/</span>
    <span class="current">Asignar Roles</span>
@endsection

@push('styles')
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
    .card-title-custom i { color: var(--purple-primary); margin-left: 10px; font-size: 18px; }

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

    /* Personalización del interruptor en Bootstrap 5 */
    .form-switch .form-check-input:checked {
        background-color: var(--purple-primary);
        border-color: var(--purple-primary);
    }

    .info-alert-pro {
        background: rgba(54, 185, 204, 0.1); border-left: 4px solid var(--info);
        border-radius: 8px; padding: 15px 20px; color: #2c3e50; font-size: 14px;
    }
</style>
@endpush

@section('content')
<div class="container-fluid pb-5">
    <div class="d-flex align-items-start justify-content-between flex-wrap gap-3 mb-4">
        <div>
            <h1 class="font-display mb-1" style="font-size:22px;font-weight:800;">
                Asignando roles y privilegios en el sistema
            </h1>
            <p class="mb-0" style="font-size:13px;color:var(--text-muted);">
               Asignando roles y privilegios en el sistema al usuario: <span class="text-warning">{{ str_replace('_', ' ', strtoupper($user->fullname)) }}</span>
            </p>
        </div>

        <div class="d-flex gap-2 align-items-center">
            @can('seguridad.usuarios.ver')
            <a href="{{ route('admin.users.index') }}"
            class="btn btn-sm btn-outline-secondary" style="border-radius:9px;font-size:12.5px;">
                <i class="fas fa-arrow-left me-1"></i> Volver al Directorio
            </a>
            @endcan
        </div>
    </div>

    <form method="POST" action="{{ route('admin.users.update-roles', $user) }}" id="rolesForm">
        @csrf @method('PUT')

        <div class="row">
            <div class="col-xl-4 col-lg-5 mb-4 position-relative">
                <div class="card-custom sticky-top" style="top: 20px; z-index: 4;">
                    <div class="card-header-custom bg-white">
                        <h6 class="card-title-custom"><i class="fas fa-user-circle"></i> Ficha del Usuario</h6>
                    </div>
                    <div class="card-body p-4 bg-white text-center border-bottom">
                        <div class="avatar-circle-lg">
                            {{ strtoupper(substr($user->name, 0, 1)) }}{{ strtoupper(substr($user->last_name ?? '', 0, 1)) }}
                        </div>
                        <h4 class="fw-bold text-dark mb-1">{{ $user->name }} {{ $user->last_name ?? '' }}</h4>
                        <p class="text-muted mb-3"><i class="fas fa-envelope me-1"></i> {{ $user->email }}</p>

                        <div class="d-flex justify-content-center gap-2 mb-2">
                            <span class="badge bg-light border text-dark px-3 py-2 shadow-sm">
                                ID: #{{ str_pad($user->id, 4, '0', STR_PAD_LEFT) }}
                            </span>
                        </div>
                    </div>
                    <div class="card-body p-4 bg-light">
                        <div class="d-flex justify-content-between align-items-center mb-4 p-3 bg-white rounded shadow-sm border">
                            <span class="fw-bold text-secondary small text-uppercase">Roles Asignados:</span>
                            <span class="badge rounded-pill bg-primary" id="counterSelected" style="font-size: 14px;">{{ count($userRoles ?? []) }}</span>
                        </div>

                        <button type="submit" class="btn-submit-master">
                            <i class="fas fa-check-circle me-2"></i> Guardar Privilegios
                        </button>
                    </div>
                </div>
            </div>

            <div class="col-xl-8 col-lg-7">
                <div class="info-alert-pro mb-4 shadow-sm">
                    <div class="d-flex align-items-start">
                        <i class="fas fa-info-circle fa-2x text-info me-3"></i>
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
                            <input type="text" id="filterRoles" class="form-control" placeholder="Buscar rol...">
                        </div>
                    </div>

                    <div class="card-body p-4 bg-white">
                        <div class="row" id="rolesGrid">
                            @foreach ($roles as $role)
                                @php
                                    $isChecked = in_array($role->name, $userRoles);
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
                                                    <i class="fas fa-shield-alt me-1"></i> {{ $role->permissions->count() ?? 0 }} permisos
                                                </span>
                                            </div>
                                        </div>

                                        <div class="form-check form-switch mb-0">
                                            <input type="checkbox" name="roles[]" value="{{ $role->name }}"
                                                   id="role_{{ $role->id }}"
                                                   class="form-check-input role-checkbox"
                                                   {{ $isChecked ? 'checked' : '' }}>
                                        </div>
                                    </label>
                                </div>
                            @endforeach
                        </div>

                        <div id="noResultsMsg" class="text-center py-5 d-none">
                            <i class="fas fa-search-minus fa-3x text-muted mb-3"></i>
                            <h5 class="text-secondary fw-bold">No se encontró el rol</h5>
                            <p class="text-muted text-sm">Intenta buscar con otra palabra clave.</p>
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
