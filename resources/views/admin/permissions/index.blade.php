@extends('layouts.app')

@section('title-page', 'Administración de Permisos')

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

    body {
        background: #f8f9fc;
    }

    /* ========================================
       HEADER PRINCIPAL
    ======================================== */
    .page-header-master {
        background: linear-gradient(135deg, #6f42c1 0%, #5a32a3 100%);
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
        top: -50%;
        right: -10%;
        width: 500px;
        height: 500px;
        background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
        border-radius: 50%;
    }

    .header-content {
        position: relative;
        z-index: 1;
    }

    .header-icon {
        width: 70px;
        height: 70px;
        background: rgba(255, 255, 255, 0.2);
        border-radius: 16px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 32px;
    }

    .header-title h1 {
        font-size: 26px;
        font-weight: 700;
        margin: 0 0 5px 0;
    }

    .header-subtitle {
        font-size: 13px;
        opacity: 0.95;
    }

    .btn-create-permission {
        background: white;
        color: #6f42c1;
        padding: 10px 25px;
        border-radius: 8px;
        font-weight: 700;
        font-size: 14px;
        border: none;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        transition: all 0.3s ease;
    }

    .btn-create-permission:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(0, 0, 0, 0.25);
        color: #6f42c1;
    }

    /* ========================================
       ALERTAS MEJORADAS
    ======================================== */
    .alert-enhanced {
        border-radius: 10px;
        border: none;
        padding: 18px 25px;
        margin-bottom: 25px;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        animation: slideInDown 0.5s ease-out;
    }

    @keyframes slideInDown {
        from {
            opacity: 0;
            transform: translateY(-20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    /* ========================================
       KPIs CON GRADIENTES
    ======================================== */
    .kpi-card-purple {
        border-radius: 12px;
        border: none;
        overflow: hidden;
        transition: all 0.3s ease;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
        height: 100%;
    }

    .kpi-card-purple:hover {
        transform: translateY(-8px) scale(1.02);
        box-shadow: 0 12px 30px rgba(0, 0, 0, 0.15);
    }

    .kpi-card-body {
        padding: 22px;
        position: relative;
        overflow: hidden;
    }

    .kpi-floating-icon {
        position: absolute;
        top: 50%;
        right: 15px;
        transform: translateY(-50%);
        width: 60px;
        height: 60px;
        border-radius: 12px;
        background: rgba(255, 255, 255, 0.2);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 28px;
        color: rgba(255, 255, 255, 0.8);
        animation: float 3s ease-in-out infinite;
    }

    @keyframes float {
        0%, 100% { transform: translateY(-50%) translateX(0); }
        50% { transform: translateY(-50%) translateX(-5px); }
    }

    .kpi-label {
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        color: rgba(255, 255, 255, 0.95);
        margin-bottom: 10px;
    }

    .kpi-value {
        font-size: 36px;
        font-weight: 700;
        color: white;
        margin: 0;
        line-height: 1;
    }

    .kpi-meta {
        font-size: 11px;
        color: rgba(255, 255, 255, 0.8);
        margin-top: 8px;
    }

    .kpi-gradient-purple { background: linear-gradient(135deg, #6f42c1, #5a32a3); }
    .kpi-gradient-blue { background: linear-gradient(135deg, #4e73df, #224abe); }
    .kpi-gradient-green { background: linear-gradient(135deg, #1cc88a, #13855c); }

    /* ========================================
       MÓDULOS DE PERMISOS
    ======================================== */
    .module-permissions-card {
        background: white;
        border-radius: 12px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
        margin-bottom: 25px;
        overflow: hidden;
        border-top: 4px solid #6f42c1;
        transition: all 0.3s ease;
    }

    .module-permissions-card:hover {
        box-shadow: 0 6px 20px rgba(111, 66, 193, 0.15);
        transform: translateY(-3px);
    }

    .module-header {
        background: white;
        border-bottom: 2px solid #f8f9fc;
        padding: 18px 20px;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .module-title {
        font-size: 15px;
        font-weight: 700;
        color: #2c3e50;
        margin: 0;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        display: flex;
        align-items: center;
    }

    .module-title i {
        color: #6f42c1;
        margin-right: 10px;
        font-size: 16px;
    }

    .module-count-badge {
        background: linear-gradient(135deg, #6f42c1, #5a32a3);
        color: white;
        padding: 4px 12px;
        border-radius: 12px;
        font-size: 11px;
        font-weight: 700;
        box-shadow: 0 2px 8px rgba(111, 66, 193, 0.3);
    }

    .module-body {
        padding: 0;
    }

    /* ========================================
       LISTA DE PERMISOS
    ======================================== */
    .permissions-list {
        padding: 0;
        margin: 0;
    }

    .permission-item {
        padding: 14px 20px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        border-bottom: 1px solid #f8f9fc;
        transition: all 0.2s ease;
    }

    .permission-item:last-child {
        border-bottom: none;
    }

    .permission-item:hover {
        background: linear-gradient(90deg, rgba(111, 66, 193, 0.03) 0%, transparent 100%);
        padding-left: 25px;
    }

    .permission-name-wrapper {
        display: flex;
        align-items: center;
        flex: 1;
    }

    .permission-icon {
        width: 36px;
        height: 36px;
        border-radius: 8px;
        background: rgba(111, 66, 193, 0.1);
        color: #6f42c1;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-right: 12px;
        font-size: 14px;
    }

    .permission-name {
        font-size: 14px;
        font-weight: 600;
        color: #2c3e50;
        letter-spacing: 0.3px;
    }

    .permission-actions {
        display: flex;
        gap: 6px;
        opacity: 0.3;
        transition: opacity 0.2s ease;
    }

    .permission-item:hover .permission-actions {
        opacity: 1;
    }

    .btn-permission-action {
        width: 32px;
        height: 32px;
        border-radius: 8px;
        border: 1px solid #e3e6f0;
        background: white;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: all 0.2s ease;
    }

    .btn-permission-action:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    }

    .btn-edit-permission:hover {
        background: #6f42c1;
        border-color: #6f42c1;
        color: white;
    }

    .btn-delete-permission:hover {
        background: #e74a3b;
        border-color: #e74a3b;
        color: white;
    }

    /* ========================================
       EMPTY STATE
    ======================================== */
    .empty-state-wrapper {
        text-align: center;
        padding: 80px 20px;
    }

    .empty-state-icon {
        width: 100px;
        height: 100px;
        border-radius: 50%;
        background: linear-gradient(135deg, rgba(111, 66, 193, 0.1), rgba(111, 66, 193, 0.05));
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 25px;
    }

    .empty-state-icon i {
        font-size: 48px;
        color: #6f42c1;
        opacity: 0.5;
    }

    .empty-state-title {
        font-size: 22px;
        font-weight: 700;
        color: #5a5c69;
        margin-bottom: 10px;
    }

    .empty-state-description {
        font-size: 14px;
        color: #858796;
        margin-bottom: 25px;
    }

    /* ========================================
       MODAL PERSONALIZADO
    ======================================== */
    .modal-custom {
        border-radius: 12px;
        border: none;
        overflow: hidden;
        box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
    }

    .modal-header-custom {
        background: linear-gradient(135deg, #6f42c1, #5a32a3);
        color: white;
        border: none;
        padding: 22px 25px;
    }

    .modal-title-custom {
        font-size: 18px;
        font-weight: 700;
        margin: 0;
        display: flex;
        align-items: center;
    }

    .modal-title-custom i {
        margin-right: 12px;
    }

    .modal-header-custom .close {
        color: white;
        opacity: 0.9;
        text-shadow: none;
    }

    .modal-body-custom {
        padding: 30px 25px;
        background: #fafbfc;
    }

    .info-alert-modal {
        background: rgba(111, 66, 193, 0.1);
        border: 1px solid rgba(111, 66, 193, 0.2);
        border-radius: 8px;
        padding: 14px 16px;
        margin-bottom: 25px;
        font-size: 13px;
        color: #5a32a3;
    }

    .info-alert-modal i {
        margin-right: 8px;
    }

    .info-alert-modal code {
        background: white;
        padding: 2px 6px;
        border-radius: 4px;
        color: #6f42c1;
        font-weight: 700;
    }

    .form-group-custom {
        margin-bottom: 20px;
    }

    .form-label-custom {
        font-size: 13px;
        font-weight: 700;
        color: #2c3e50;
        margin-bottom: 10px;
        display: flex;
        align-items: center;
    }

    .form-label-custom i {
        color: #6f42c1;
        margin-right: 8px;
    }

    .form-control-custom {
        border: 2px solid #e3e6f0;
        border-radius: 8px;
        padding: 12px 15px;
        font-size: 14px;
        transition: all 0.2s ease;
    }

    .form-control-custom:focus {
        border-color: #6f42c1;
        box-shadow: 0 0 0 0.2rem rgba(111, 66, 193, 0.15);
    }

    .form-hint {
        font-size: 12px;
        color: #858796;
        margin-top: 8px;
    }

    .modal-footer-custom {
        background: white;
        border: none;
        padding: 20px 25px;
        display: flex;
        justify-content: space-between;
    }

    .btn-modal-action {
        padding: 10px 25px;
        border-radius: 8px;
        font-weight: 700;
        font-size: 14px;
        border: none;
        transition: all 0.2s ease;
    }

    .btn-modal-cancel {
        background: #e3e6f0;
        color: #5a5c69;
    }

    .btn-modal-cancel:hover {
        background: #d1d3e2;
        transform: translateY(-1px);
    }

    .btn-modal-submit {
        background: linear-gradient(135deg, #6f42c1, #5a32a3);
        color: white;
        box-shadow: 0 4px 12px rgba(111, 66, 193, 0.3);
    }

    .btn-modal-submit:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(111, 66, 193, 0.4);
        color: white;
    }

    /* ========================================
       RESPONSIVE
    ======================================== */
    @media (max-width: 768px) {
        .page-header-master {
            padding: 20px;
        }

        .header-title h1 {
            font-size: 22px;
        }

        .kpi-value {
            font-size: 28px;
        }
    }
</style>
@endsection

@section('content')
<div class="container-fluid">
    <!-- HEADER PRINCIPAL -->
    <div class="page-header-master">
        <div class="header-content">
            <div class="d-flex justify-content-between align-items-start flex-wrap">
                <div class="d-flex align-items-center mb-2 mb-md-0">
                    <div class="header-icon mr-3">
                        <i class="fas fa-key"></i>
                    </div>
                    <div class="header-title">
                        <h1>{{ __('Diccionario de Permisos') }}</h1>
                        <p class="header-subtitle mb-0">
                            <i class="fas fa-shield-alt mr-2"></i>
                            Define las acciones específicas que los roles podrán ejecutar en el sistema
                        </p>
                    </div>
                </div>
                <div>
                    @can('seguridad.permisos.crear')
                        <button class="btn btn-create-permission" data-toggle="modal" data-target="#modalPermiso">
                            <i class="fas fa-plus mr-2"></i>Crear Permiso
                        </button>
                    @endcan
                </div>
            </div>
        </div>
    </div>

    <!-- ALERTAS -->
    @if(session('success'))
        <div class="alert alert-success alert-enhanced alert-dismissible fade show" role="alert">
            <div class="d-flex align-items-center">
                <i class="fas fa-check-circle fa-2x mr-3"></i>
                <div>
                    <strong>¡Operación Exitosa!</strong><br>
                    {{ session('success') }}
                </div>
            </div>
            <button type="button" class="close" data-dismiss="alert">
                <span>&times;</span>
            </button>
        </div>
    @endif

    <!-- KPIs -->
    <div class="row mb-4">
        <div class="col-xl-4 col-md-6 mb-4">
            <div class="card kpi-card-purple kpi-gradient-purple">
                <div class="kpi-card-body">
                    <div class="kpi-floating-icon">
                        <i class="fas fa-unlock-alt"></i>
                    </div>
                    <div class="kpi-label">
                        <i class="fas fa-list mr-1"></i>Total de Permisos
                    </div>
                    <div class="kpi-value">{{ $stats['total_permisos'] ?? 0 }}</div>
                    <div class="kpi-meta">Registrados en el sistema</div>
                </div>
            </div>
        </div>

        <div class="col-xl-4 col-md-6 mb-4">
            <div class="card kpi-card-purple kpi-gradient-blue">
                <div class="kpi-card-body">
                    <div class="kpi-floating-icon">
                        <i class="fas fa-cubes"></i>
                    </div>
                    <div class="kpi-label">
                        <i class="fas fa-folder mr-1"></i>Módulos Registrados
                    </div>
                    <div class="kpi-value">{{ $stats['total_modulos'] ?? 0 }}</div>
                    <div class="kpi-meta">Grupos de permisos</div>
                </div>
            </div>
        </div>

        <div class="col-xl-4 col-md-6 mb-4">
            <div class="card kpi-card-purple kpi-gradient-green">
                <div class="kpi-card-body">
                    <div class="kpi-floating-icon">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <div class="kpi-label">
                        <i class="fas fa-clock mr-1"></i>Nuevos (Últimos 7 días)
                    </div>
                    <div class="kpi-value">{{ $stats['recientes'] ?? 0 }}</div>
                    <div class="kpi-meta">Creados recientemente</div>
                </div>
            </div>
        </div>
    </div>

    <!-- GRID DE MÓDULOS -->
    <div class="row">
        @forelse ($groupedPermissions as $module => $perms)
            <div class="col-xl-4 col-md-6 mb-4">
                <div class="module-permissions-card">
                    <div class="module-header">
                        <h6 class="module-title">
                            <i class="fas fa-folder-open"></i>
                            {{ $module }}
                        </h6>
                        <span class="module-count-badge">{{ $perms->count() }}</span>
                    </div>
                    <div class="module-body">
                        <ul class="permissions-list">
                            @foreach($perms as $p)
                                <li class="permission-item">
                                    <div class="permission-name-wrapper">
                                        <div class="permission-icon">
                                            <i class="fas fa-cog"></i>
                                        </div>
                                        <span class="permission-name">{{ $p->name }}</span>
                                    </div>
                                    <div class="permission-actions">
                                        @can('seguridad.permisos.editar')
                                            <button class="btn-permission-action btn-edit-permission"
                                                    data-id="{{ $p->id }}"
                                                    data-name="{{ $p->name }}"
                                                    data-module="{{ $p->module }}"
                                                    title="Editar permiso">
                                                <i class="fas fa-pen fa-xs"></i>
                                            </button>
                                        @endcan
                                        @can('seguridad.permisos.eliminar')
                                            <form action="{{ route('admin.permissions.destroy', $p) }}"
                                                  method="POST"
                                                  class="form-delete-perm m-0">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit"
                                                        class="btn-permission-action btn-delete-permission"
                                                        title="Eliminar permiso">
                                                    <i class="fas fa-trash fa-xs"></i>
                                                </button>
                                            </form>
                                        @endcan
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-12">
                <div class="empty-state-wrapper">
                    <div class="empty-state-icon">
                        <i class="fas fa-key"></i>
                    </div>
                    <h5 class="empty-state-title">No hay permisos registrados</h5>
                    <p class="empty-state-description">
                        Comienza creando el primer permiso para tu sistema.<br>
                        Los permisos definen las acciones que pueden realizar los usuarios.
                    </p>
                    @can('seguridad.permisos.crear')
                        <button class="btn btn-create-permission" data-toggle="modal" data-target="#modalPermiso">
                            <i class="fas fa-plus mr-2"></i>Crear Primer Permiso
                        </button>
                    @endcan
                </div>
            </div>
        @endforelse
    </div>
</div>

<!-- MODAL DE CREACIÓN/EDICIÓN -->
<div class="modal fade" id="modalPermiso" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form id="formPermiso"
              action="{{ route('admin.permissions.store') }}"
              method="POST"
              class="modal-content modal-custom">
            @csrf
            <div id="methodField"></div>

            <div class="modal-header modal-header-custom">
                <h5 class="modal-title modal-title-custom" id="modalTitle">
                    <i class="fas fa-key"></i>
                    Nuevo Permiso
                </h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>

            <div class="modal-body modal-body-custom">
                <div class="info-alert-modal">
                    <i class="fas fa-info-circle"></i>
                    Se recomienda usar el formato <code>accion_modulo</code> (ejemplo: <strong>crear_usuarios</strong>)
                </div>

                <div class="form-group-custom">
                    <label class="form-label-custom">
                        <i class="fas fa-folder"></i>
                        Módulo (Grupo)
                    </label>
                    <input type="text"
                           name="module"
                           id="inputModule"
                           class="form-control form-control-custom text-uppercase"
                           placeholder="Ej: INVENTARIO"
                           required>
                    <div class="form-hint">
                        Agrupa los permisos bajo un mismo nombre para organizarlos en la vista
                    </div>
                </div>

                <div class="form-group-custom mb-0">
                    <label class="form-label-custom">
                        <i class="fas fa-tag"></i>
                        Nombre del Permiso
                    </label>
                    <input type="text"
                           name="name"
                           id="inputName"
                           class="form-control form-control-custom"
                           placeholder="ej: crear_item"
                           required>
                    <div class="form-hint">
                        Nombre único que identifica la acción que permite este permiso
                    </div>
                </div>
            </div>

            <div class="modal-footer modal-footer-custom">
                <button type="button"
                        class="btn btn-modal-action btn-modal-cancel"
                        data-dismiss="modal">
                    <i class="fas fa-times mr-2"></i>Cancelar
                </button>
                @can('seguridad.permisos.crear')
                    <button type="submit"
                            class="btn btn-modal-action btn-modal-submit"
                            id="btnSave">
                        <i class="fas fa-save mr-2"></i>Guardar Permiso
                    </button>
                @endcan
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {

    // EDITAR PERMISO
    $('.btn-edit-permission').on('click', function() {
        let id = $(this).data('id');
        let name = $(this).data('name');
        let module = $(this).data('module');

        $('#modalTitle').html('<i class="fas fa-edit mr-2"></i> Editar Permiso');
        $('#formPermiso').attr('action', `/admin/permisos/${id}`);
        $('#methodField').html('@method("PUT")');

        $('#inputName').val(name);
        $('#inputModule').val(module);

        $('#btnSave').html('<i class="fas fa-sync-alt mr-2"></i> Actualizar Permiso');

        $('#modalPermiso').modal('show');
    });

    // RESETEAR MODAL AL CERRAR
    $('#modalPermiso').on('hidden.bs.modal', function () {
        $('#modalTitle').html('<i class="fas fa-key mr-2"></i> Nuevo Permiso');
        $('#formPermiso').attr('action', "{{ route('admin.permissions.store') }}");
        $('#methodField').html('');

        $('#inputName, #inputModule').val('');
        $('#btnSave').html('<i class="fas fa-save mr-2"></i> Guardar Permiso');
    });

    // ELIMINAR CON CONFIRMACIÓN
    $('.form-delete-perm').on('submit', function(e) {
        e.preventDefault();
        let form = this;

        Swal.fire({
            title: '¿Eliminar este permiso?',
            html: '<p>Los roles que tengan este permiso asignado dejarán de tener acceso a esta función.</p>' +
                  '<p class="text-muted small">Esta acción no se puede deshacer.</p>',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#e74a3b',
            cancelButtonColor: '#858796',
            confirmButtonText: '<i class="fas fa-trash mr-2"></i>Sí, eliminar',
            cancelButtonText: '<i class="fas fa-times mr-2"></i>Cancelar',
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
