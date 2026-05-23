@extends('layouts.app')

@section('title-page', 'Administración de Roles')

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
        color: white;
        padding: 25px 30px;
        border-radius: 12px;
        margin-bottom: 25px;
        box-shadow: 0 6px 20px rgba(111, 66, 193, 0.3);
        position: relative;
        overflow: hidden;
    }
    .page-header-master::before {
        content: '';
        position: absolute;
        top: -50%; right: -10%;
        width: 500px; height: 500px;
        background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
        border-radius: 50%;
    }
    .header-content { position: relative; z-index: 1; }
    .header-icon {
        width: 70px; height: 70px;
        background: rgba(255, 255, 255, 0.2);
        border-radius: 16px;
        display: flex; align-items: center; justify-content: center;
        font-size: 32px;
    }
    .header-title h1 { font-size: 26px; font-weight: 700; margin: 0 0 5px 0; }
    .header-subtitle { font-size: 13px; opacity: 0.95; margin:0; }

    .btn-create-role {
        background: white; color: var(--purple-primary);
        padding: 10px 25px; border-radius: 8px;
        font-weight: 700; font-size: 14px; border: none;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        transition: all 0.3s ease;
    }
    .btn-create-role:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(0, 0, 0, 0.25);
        color: var(--purple-dark);
    }

    /* ========================================
       ALERTAS MEJORADAS
    ======================================== */
    .alert-enhanced {
        border-radius: 10px; border: none; padding: 18px 25px;
        margin-bottom: 25px; box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        animation: slideInDown 0.5s ease-out;
    }
    @keyframes slideInDown {
        from { opacity: 0; transform: translateY(-20px); }
        to { opacity: 1; transform: translateY(0); }
    }

    /* ========================================
       KPIs CON GRADIENTES
    ======================================== */
    .kpi-card-purple {
        border-radius: 12px; border: none; overflow: hidden;
        transition: all 0.3s ease; box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
        height: 100%;
    }
    .kpi-card-purple:hover {
        transform: translateY(-8px) scale(1.02);
        box-shadow: 0 12px 30px rgba(0, 0, 0, 0.15);
    }
    .kpi-card-body { padding: 22px; position: relative; overflow: hidden; }
    .kpi-floating-icon {
        position: absolute; top: 50%; right: 15px; transform: translateY(-50%);
        width: 60px; height: 60px; border-radius: 12px;
        background: rgba(255, 255, 255, 0.2);
        display: flex; align-items: center; justify-content: center;
        font-size: 28px; color: rgba(255, 255, 255, 0.8);
        animation: float 3s ease-in-out infinite;
    }
    @keyframes float {
        0%, 100% { transform: translateY(-50%) translateX(0); }
        50% { transform: translateY(-50%) translateX(-5px); }
    }
    .kpi-label { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: rgba(255, 255, 255, 0.95); margin-bottom: 10px; }
    .kpi-value { font-size: 36px; font-weight: 700; color: white; margin: 0; line-height: 1; }
    .kpi-meta { font-size: 11px; color: rgba(255, 255, 255, 0.8); margin-top: 8px; }

    .kpi-gradient-purple { background: linear-gradient(135deg, #6f42c1, #5a32a3); }
    .kpi-gradient-blue { background: linear-gradient(135deg, #4e73df, #224abe); }
    .kpi-gradient-green { background: linear-gradient(135deg, #1cc88a, #13855c); }

    /* ========================================
       TARJETAS DE ROLES (ROLE CARDS)
    ======================================== */
    .role-card {
        background: white; border-radius: 12px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        border: none; border-top: 4px solid var(--purple-primary);
        transition: all 0.3s ease; height: 100%; display: flex; flex-direction: column;
    }
    .role-card:hover {
        box-shadow: 0 8px 25px rgba(111, 66, 193, 0.15);
        transform: translateY(-5px);
    }

    /* Estilo Especial para Super Admin */
    .role-card.is-admin {
        border-top: 4px solid var(--danger);
        background: linear-gradient(to bottom, #fffafb, #ffffff);
    }
    .role-card.is-admin .role-icon { background: rgba(231, 74, 59, 0.1); color: var(--danger); }

    .role-header { padding: 20px; border-bottom: 1px solid #f8f9fc; display: flex; justify-content: space-between; align-items: flex-start; }
    .role-title-group { display: flex; align-items: center; }
    .role-icon {
        width: 45px; height: 45px; border-radius: 10px;
        background: rgba(111, 66, 193, 0.1); color: var(--purple-primary);
        display: flex; align-items: center; justify-content: center; font-size: 18px; margin-right: 15px;
    }
    .role-name { font-size: 18px; font-weight: 700; color: #2c3e50; margin: 0; text-transform: capitalize; }
    .role-users-badge {
        background: #f8f9fc; border: 1px solid #eaecf4; color: #5a5c69;
        padding: 5px 12px; border-radius: 20px; font-size: 12px; font-weight: 700;
        display: flex; align-items: center;
    }
    .role-users-badge i { color: var(--purple-primary); margin-right: 5px; }

    .role-body { padding: 20px; flex-grow: 1; }
    .role-subtitle { font-size: 12px; font-weight: 700; color: #858796; text-transform: uppercase; margin-bottom: 12px; letter-spacing: 0.5px; }

    .perm-pill-container { display: flex; flex-wrap: wrap; gap: 6px; }
    .perm-pill {
        background: #f1f3f9; color: #4a5568; padding: 4px 10px;
        border-radius: 6px; font-size: 11px; font-weight: 600; border: 1px solid #e2e8f0;
    }
    .perm-pill-more { background: rgba(111, 66, 193, 0.1); color: var(--purple-primary); border-color: rgba(111, 66, 193, 0.2); }
    .perm-pill-all { background: rgba(231, 74, 59, 0.1); color: var(--danger); border-color: rgba(231, 74, 59, 0.2); }

    .role-footer {
        padding: 15px 20px; background: #fafbfc; border-top: 1px solid #f8f9fc;
        border-radius: 0 0 12px 12px; display: flex; justify-content: space-between; align-items: center;
    }
    .btn-role-config {
        background: rgba(111, 66, 193, 0.1); color: var(--purple-primary);
        border: none; padding: 8px 15px; border-radius: 6px; font-weight: 700; font-size: 13px; transition: all 0.2s;
    }
    .btn-role-config:hover { background: var(--purple-primary); color: white; }

    .btn-role-delete { color: #858796; background: transparent; border: none; padding: 8px; border-radius: 6px; transition: all 0.2s; }
    .btn-role-delete:hover { background: rgba(231, 74, 59, 0.1); color: var(--danger); }

    /* ========================================
       MODAL Y EMPTY STATE
    ======================================== */
    .empty-state-wrapper { text-align: center; padding: 60px 20px; }
    .empty-state-icon {
        width: 100px; height: 100px; border-radius: 50%;
        background: linear-gradient(135deg, rgba(111, 66, 193, 0.1), rgba(111, 66, 193, 0.05));
        display: flex; align-items: center; justify-content: center; margin: 0 auto 25px;
    }
    .empty-state-icon i { font-size: 48px; color: var(--purple-primary); opacity: 0.5; }
    .empty-state-title { font-size: 22px; font-weight: 700; color: #5a5c69; margin-bottom: 10px; }
    .empty-state-description { font-size: 14px; color: #858796; margin-bottom: 25px; }

    /* Reutilizamos el modal de la vista anterior */
    .modal-custom { border-radius: 12px; border: none; overflow: hidden; box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2); }
    .modal-header-custom { background: linear-gradient(135deg, #6f42c1, #5a32a3); color: white; border: none; padding: 22px 25px; }
    .modal-title-custom { font-size: 18px; font-weight: 700; margin: 0; display: flex; align-items: center; }
    .modal-body-custom { padding: 30px 25px; background: #fafbfc; }
    .form-label-custom { font-size: 13px; font-weight: 700; color: #2c3e50; margin-bottom: 10px; display: flex; align-items: center; }
    .form-control-custom { border: 2px solid #e3e6f0; border-radius: 8px; padding: 12px 15px; font-size: 14px; transition: all 0.2s ease; }
    .form-control-custom:focus { border-color: var(--purple-primary); box-shadow: 0 0 0 0.2rem rgba(111, 66, 193, 0.15); }
    .modal-footer-custom { background: white; border: none; padding: 20px 25px; display: flex; justify-content: space-between; }
    .btn-modal-action { padding: 10px 25px; border-radius: 8px; font-weight: 700; font-size: 14px; border: none; transition: all 0.2s ease; }
    .btn-modal-cancel { background: #e3e6f0; color: #5a5c69; }
    .btn-modal-submit { background: linear-gradient(135deg, #6f42c1, #5a32a3); color: white; box-shadow: 0 4px 12px rgba(111, 66, 193, 0.3); }
</style>
@endsection

@section('content')
<div class="container-fluid">
    <div class="page-header-master">
        <div class="header-content">
            <div class="d-flex justify-content-between align-items-start flex-wrap">
                <div class="d-flex align-items-center mb-2 mb-md-0">
                    <div class="header-icon mr-3">
                        <i class="fas fa-user-shield"></i>
                    </div>
                    <div class="header-title">
                        <h1>{{ __('Administración de Roles') }}</h1>
                        <p class="header-subtitle">
                            <i class="fas fa-project-diagram mr-2"></i>
                            Gestiona los perfiles de acceso y agrupa los permisos del sistema
                        </p>
                    </div>
                </div>
                <div>
                    @can('seguridad.roles.crear')
                        <button class="btn btn-create-role" data-toggle="modal" data-target="#modalRol">
                            <i class="fas fa-plus mr-2"></i>Crear Nuevo Rol
                        </button>
                    @endcan
                </div>
            </div>
        </div>
    </div>

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

    <div class="row mb-4">
        <div class="col-xl-4 col-md-6 mb-4">
            <div class="card kpi-card-purple kpi-gradient-purple">
                <div class="kpi-card-body">
                    <div class="kpi-floating-icon"><i class="fas fa-id-badge"></i></div>
                    <div class="kpi-label"><i class="fas fa-list mr-1"></i>Total de Roles</div>
                    <div class="kpi-value">{{ $stats['total_roles'] ?? $roles->count() }}</div>
                    <div class="kpi-meta">Perfiles de acceso definidos</div>
                </div>
            </div>
        </div>
        <div class="col-xl-4 col-md-6 mb-4">
            <div class="card kpi-card-purple kpi-gradient-blue">
                <div class="kpi-card-body">
                    <div class="kpi-floating-icon"><i class="fas fa-users"></i></div>
                    <div class="kpi-label"><i class="fas fa-user-check mr-1"></i>Usuarios Asignados</div>
                    <div class="kpi-value">{{ $stats['usuarios_asignados'] ?? 0 }}</div>
                    <div class="kpi-meta">Personas con roles activos</div>
                </div>
            </div>
        </div>
        <div class="col-xl-4 col-md-6 mb-4">
            <div class="card kpi-card-purple kpi-gradient-green">
                <div class="kpi-card-body">
                    <div class="kpi-floating-icon"><i class="fas fa-key"></i></div>
                    <div class="kpi-label"><i class="fas fa-shield-alt mr-1"></i>Permisos Disponibles</div>
                    <div class="kpi-value">{{ $stats['promedio_permisos'] ?? 0 }}</div>
                    <div class="kpi-meta">Reglas de seguridad en DB</div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        @forelse ($roles as $role)
            @php
                // Detectar si es un rol de sistema intocable
                $isSuperAdmin = in_array(strtolower($role->name), ['super_admin', 'super_administrador']);
            @endphp
            <div class="col-xl-4 col-md-6 mb-4">
                <div class="role-card {{ $isSuperAdmin ? 'is-admin' : '' }}">

                    {{-- Cabecera del Rol --}}
                    <div class="role-header">
                        <div class="role-title-group">
                            <div class="role-icon">
                                <i class="fas {{ $isSuperAdmin ? 'fa-crown' : 'fa-user-tag' }}"></i>
                            </div>
                            <div>
                                <h3 class="role-name">{{ str_replace('_', ' ', $role->name) }}</h3>
                                <small class="text-muted">ID Sistema: #{{ $role->id }}</small>
                            </div>
                        </div>
                        <div class="role-users-badge" title="Usuarios con este rol">
                            <i class="fas fa-users"></i> {{ $role->users_count ?? $role->users->count() }}
                        </div>
                    </div>

                    {{-- Cuerpo (Permisos) --}}
                    <div class="role-body">
                        <div class="role-subtitle">Permisos Asignados ({{ $role->permissions->count() }})</div>
                        <div class="perm-pill-container">
                            @if($isSuperAdmin)
                                <span class="perm-pill perm-pill-all"><i class="fas fa-star mr-1"></i> ACCESO TOTAL A TODOS LOS MÓDULOS</span>
                            @elseif($role->permissions->count() > 0)
                                @foreach($role->permissions->take(6) as $perm)
                                    <span class="perm-pill">{{ $perm->name }}</span>
                                @endforeach

                                @if($role->permissions->count() > 6)
                                    <span class="perm-pill perm-pill-more">+ {{ $role->permissions->count() - 6 }} más...</span>
                                @endif
                            @else
                                <span class="perm-pill text-muted bg-light border-dashed"><i class="fas fa-exclamation-circle mr-1"></i> Sin permisos asignados</span>
                            @endif
                        </div>
                    </div>

                    {{-- Footer (Acciones) --}}
                    <div class="role-footer">
                        {{-- Idealmente esta ruta lleva a una vista donde se asignan los permisos con checkboxes --}}
                        @can('seguridad.roles.editar')
                            <a href="{{ route('admin.roles.edit', $role->id) }}" class="btn-role-config text-decoration-none">
                                <i class="fas fa-sliders-h mr-1"></i> Configurar Permisos
                            </a>
                        @endcan
                        <div class="d-flex gap-2">


                                @if(!$isSuperAdmin)
                                    @can('seguridad.roles.editar')
                                        <button class="btn-role-delete btn-edit-name" data-id="{{ $role->id }}" data-name="{{ $role->name }}" title="Editar Nombre">
                                            <i class="fas fa-pen"></i>
                                        </button>
                                    @endcan
                                    @can('seguridad.roles.eliminar')
                                    <form action="{{ route('admin.roles.destroy', $role->id) }}" method="POST" class="form-delete-role m-0 d-inline">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn-role-delete" title="Eliminar Rol">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                    @endcan
                                @else
                                    <button type="button" class="btn-role-delete" disabled title="Rol de Sistema (Protegido)">
                                        <i class="fas fa-lock opacity-50"></i>
                                    </button>
                                @endif
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-12">
                <div class="empty-state-wrapper bg-white rounded-lg shadow-sm border-0">
                    <div class="empty-state-icon">
                        <i class="fas fa-user-shield"></i>
                    </div>
                    <h5 class="empty-state-title">No hay roles definidos</h5>
                    <p class="empty-state-description">
                        Crea perfiles como "Médico", "Recursos Humanos" o "Gerente" para comenzar a agrupar los permisos.
                    </p>
                    @can('seguridad.roles.crear')
                        <button class="btn-create-role bg-purple text-white" data-toggle="modal" data-target="#modalRol" style="background:var(--purple-primary);">
                            <i class="fas fa-plus mr-2"></i>Crear Primer Rol
                        </button>
                    @endcan
                </div>
            </div>
        @endforelse
    </div>
</div>

<div class="modal fade" id="modalRol" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        @can('seguridad.roles.crear')
        <form id="formRol" action="{{ route('admin.roles.store') }}" method="POST" class="modal-content modal-custom">
            @csrf
            <div id="methodField"></div>

            <div class="modal-header modal-header-custom">
                <h5 class="modal-title modal-title-custom" id="modalTitle">
                    <i class="fas fa-user-tag mr-2"></i> Nuevo Perfil de Acceso
                </h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>

            <div class="modal-body modal-body-custom">
                <div class="form-group-custom mb-0">
                    <label class="form-label-custom">
                        <i class="fas fa-id-card"></i> Nombre del Rol
                    </label>
                    <input type="text" name="name" id="inputName" class="form-control form-control-custom text-lowercase" placeholder="ej: medico_laboral" required>
                    <div class="form-hint">
                        <i class="fas fa-info-circle text-primary"></i> Se recomienda usar minúsculas y guiones bajos (snake_case). Los permisos se configurarán en el siguiente paso.
                    </div>
                </div>
            </div>

            <div class="modal-footer modal-footer-custom">
                <button type="button" class="btn btn-modal-action btn-modal-cancel" data-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-modal-action btn-modal-submit" id="btnSave">
                    <i class="fas fa-save mr-2"></i>Guardar Rol
                </button>
            </div>
        </form>
        @else
            <div class="modal-content modal-custom">
                <div id="methodField"></div>

                <div class="modal-header modal-header-custom">
                    <h5 class="modal-title modal-title-custom">
                        <i class="fas fa-user-tag mr-2"></i> Acceso Restringindo
                    </h5>
                    <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                </div>

                <div class="modal-body modal-body-custom">
                    <div class="form-group-custom mb-0">
                        <div class="form-hint">
                            <i class="fas fa-info-circle text-primary"></i> No tienes permiso para realizar esta accion.
                        </div>
                    </div>
                </div>

                <div class="modal-footer modal-footer-custom">
                    <button type="button" class="btn btn-modal-action btn-modal-cancel" data-dismiss="modal">Cancelar</button>
                </div>
            </div>
        @endcan

    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {

    // EDITAR NOMBRE DEL ROL
    $('.btn-edit-name').on('click', function() {
        let id = $(this).data('id');
        let name = $(this).data('name');

        $('#modalTitle').html('<i class="fas fa-edit mr-2"></i> Editar Nombre del Rol');
        $('#formRol').attr('action', `/admin/roles/${id}`);
        $('#methodField').html('@method("PUT")');

        $('#inputName').val(name);
        $('#btnSave').html('<i class="fas fa-sync-alt mr-2"></i> Actualizar Nombre');

        $('#modalRol').modal('show');
    });

    // RESETEAR MODAL
    $('#modalRol').on('hidden.bs.modal', function () {
        $('#modalTitle').html('<i class="fas fa-user-tag mr-2"></i> Nuevo Perfil de Acceso');
        $('#formRol').attr('action', "{{ route('admin.roles.store') }}");
        $('#methodField').html('');
        $('#inputName').val('');
        $('#btnSave').html('<i class="fas fa-save mr-2"></i> Guardar Rol');
    });

    // ELIMINAR CON CONFIRMACIÓN
    $('.form-delete-role').on('submit', function(e) {
        e.preventDefault();
        let form = this;

        Swal.fire({
            title: '¿Eliminar este Rol?',
            html: '<p>Los usuarios que tengan este rol perderán inmediatamente todos los permisos asociados.</p>' +
                  '<p class="text-danger font-weight-bold small">Esta acción es irreversible.</p>',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#e74a3b',
            cancelButtonColor: '#858796',
            confirmButtonText: '<i class="fas fa-trash mr-2"></i>Sí, eliminar rol',
            cancelButtonText: 'Cancelar',
            reverseButtons: true
        }).then((result) => {
            if (result.isConfirmed) {
                form.submit();
            }
        });
    });
});
</script>
@endpush
