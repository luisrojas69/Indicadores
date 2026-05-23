@extends('layouts.app')

@section('title-page', 'Gestión de Usuarios y Roles')

@section('styles')
<style>
    /* ========================================
       VARIABLES GLOBALES
    ======================================== */
    :root {
        --primary: #4e73df;
        --success: #1cc88a;
        --danger: #e74a3b;
        --warning: #f6c23e;
        --info: #36b9cc;
        --purple-admin: #6f42c1;
        --purple-dark: #5a32a3;
    }

    body {
        background: #f8f9fc;
    }

    /* ========================================
       HEADER PRINCIPAL - MORADO ADMINISTRATIVO
    ======================================== */
    .page-header-admin {
        background: linear-gradient(135deg, #6f42c1 0%, #5a32a3 100%);
        color: white;
        padding: 25px 30px;
        border-radius: 12px;
        margin-bottom: 25px;
        box-shadow: 0 6px 20px rgba(111, 66, 193, 0.25);
        position: relative;
        overflow: hidden;
    }

    .page-header-admin::before {
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

    .header-icon-admin {
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

    .btn-configure-roles {
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

    .btn-configure-roles:hover {
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
       KPIs ADMINISTRATIVOS
    ======================================== */
    .kpi-card-admin {
        border-radius: 12px;
        border: none;
        overflow: hidden;
        transition: all 0.3s ease;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
        height: 100%;
        position: relative;
    }

    .kpi-card-admin:hover {
        transform: translateY(-8px) scale(1.02);
        box-shadow: 0 12px 30px rgba(0, 0, 0, 0.15);
    }

    .kpi-card-body-admin {
        padding: 22px;
        position: relative;
        overflow: hidden;
    }

    .kpi-floating-icon-admin {
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

    .kpi-label-admin {
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        color: rgba(255, 255, 255, 0.95);
        margin-bottom: 10px;
        display: flex;
        align-items: center;
    }

    .kpi-value-admin {
        font-size: 36px;
        font-weight: 700;
        color: white;
        margin: 0;
        line-height: 1;
    }

    .kpi-admin-purple { background: linear-gradient(135deg, #6f42c1, #5a32a3); }
    .kpi-admin-danger { background: linear-gradient(135deg, #e74a3b, #be2617); }
    .kpi-admin-warning { background: linear-gradient(135deg, #f6c23e, #dda20a); }
    .kpi-admin-info { background: linear-gradient(135deg, #36b9cc, #258391); }

    /* ========================================
       TABLA DE USUARIOS
    ======================================== */
    .users-table-card {
        background: white;
        border-radius: 12px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
        overflow: hidden;
    }

    .users-table-header {
        background: white;
        border-bottom: 2px solid #f8f9fc;
        padding: 20px 25px;
    }

    .users-table-title {
        font-size: 16px;
        font-weight: 700;
        color: #2c3e50;
        margin: 0;
        display: flex;
        align-items: center;
    }

    .users-table-title i {
        color: #6f42c1;
        margin-right: 12px;
        font-size: 18px;
    }

    .users-table-body {
        padding: 25px;
    }

    .users-table {
        width: 100%;
        margin: 0;
    }

    .users-table thead th {
        background: #f8f9fc;
        border-bottom: 2px solid #e3e6f0;
        padding: 14px 12px;
        font-size: 10px;
        text-transform: uppercase;
        font-weight: 700;
        color: #6f42c1;
        letter-spacing: 0.8px;
    }

    .users-table tbody td {
        padding: 16px 12px;
        vertical-align: middle;
        border-bottom: 1px solid #f8f9fc;
    }

    .users-table tbody tr {
        transition: all 0.2s ease;
    }

    .users-table tbody tr:hover {
        background: #fafbfc;
        box-shadow: inset 4px 0 0 #6f42c1;
    }

    /* ========================================
       USUARIO CON AVATAR
    ======================================== */
    .user-display {
        display: flex;
        align-items: center;
    }

    .user-avatar {
        width: 50px;
        height: 50px;
        border-radius: 50%;
        background: linear-gradient(135deg, #6f42c1, #5a32a3);
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        font-size: 18px;
        margin-right: 14px;
        box-shadow: 0 4px 12px rgba(111, 66, 193, 0.3);
        position: relative;
    }

    .user-avatar-warning {
        background: linear-gradient(135deg, #f6c23e, #dda20a);
        box-shadow: 0 4px 12px rgba(246, 194, 62, 0.3);
    }

    .user-avatar-status {
        position: absolute;
        bottom: -2px;
        right: -2px;
        width: 16px;
        height: 16px;
        border-radius: 50%;
        background: #1cc88a;
        border: 2px solid white;
    }

    .user-avatar-status-warning {
        background: #f6c23e;
    }

    .user-details {
        flex: 1;
    }

    .user-name {
        font-size: 15px;
        font-weight: 700;
        color: #2c3e50;
        margin-bottom: 3px;
        display: block;
    }

    .user-email {
        font-size: 12px;
        color: #858796;
        display: flex;
        align-items: center;
    }

    .user-email i {
        margin-right: 6px;
        font-size: 11px;
    }

    /* ========================================
       BADGES DE ROLES
    ======================================== */
    .roles-container {
        display: flex;
        flex-wrap: wrap;
        gap: 6px;
    }

    .role-badge {
        padding: 6px 12px;
        border-radius: 20px;
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.3px;
        display: inline-flex;
        align-items: center;
    }

    .role-badge i {
        margin-right: 5px;
        font-size: 10px;
        opacity: 0.7;
    }

    .role-badge-admin {
        background: rgba(231, 74, 59, 0.15);
        color: #c92a2a;
        border: 1px solid rgba(231, 74, 59, 0.2);
    }

    .role-badge-medico {
        background: rgba(28, 200, 138, 0.15);
        color: #087f5b;
        border: 1px solid rgba(28, 200, 138, 0.2);
    }

    .role-badge-default {
        background: rgba(111, 66, 193, 0.15);
        color: #5a32a3;
        border: 1px solid rgba(111, 66, 193, 0.2);
    }

    .role-badge-warning {
        background: rgba(246, 194, 62, 0.15);
        color: #d97706;
        border: 1px dashed rgba(246, 194, 62, 0.4);
    }

    /* ========================================
       COLUMNA DE REGISTRO
    ======================================== */
    .registration-info {
        display: flex;
        flex-direction: column;
    }

    .registration-date {
        font-size: 14px;
        font-weight: 700;
        color: #2c3e50;
        margin-bottom: 3px;
    }

    .registration-id {
        font-size: 11px;
        color: #858796;
    }

    /* ========================================
       ACCIONES DROPDOWN
    ======================================== */
    .actions-cell {
        text-align: center;
    }

    .btn-actions-admin {
        width: 36px;
        height: 36px;
        border-radius: 8px;
        background: white;
        border: 1px solid #e3e6f0;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: all 0.2s ease;
        margin: 0 auto;
    }

    .btn-actions-admin:hover {
        background: #f8f9fc;
        border-color: #6f42c1;
    }

    .dropdown-menu-admin {
        border-radius: 10px;
        border: none;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.12);
        padding: 10px 0;
        min-width: 220px;
    }

    .dropdown-header-admin {
        padding: 10px 20px;
        font-size: 10px;
        text-transform: uppercase;
        font-weight: 700;
        color: #858796;
        letter-spacing: 0.5px;
        border-bottom: 1px solid #f8f9fc;
    }

    .dropdown-item-admin {
        padding: 10px 20px;
        font-size: 13px;
        color: #5a5c69;
        transition: all 0.2s ease;
        display: flex;
        align-items: center;
    }

    .dropdown-item-admin:hover {
        background: #f8f9fc;
        color: #6f42c1;
    }

    .dropdown-item-admin i {
        width: 20px;
        margin-right: 12px;
        font-size: 14px;
    }

    .dropdown-divider-admin {
        margin: 8px 0;
        border-top: 1px solid #e3e6f0;
    }

    .dropdown-item-danger {
        color: #e74a3b;
    }

    .dropdown-item-danger:hover {
        background: rgba(231, 74, 59, 0.1);
        color: #c92a2a;
    }

    /* ========================================
       DATATABLES PERSONALIZACIONES
    ======================================== */
    .dataTables_wrapper .dataTables_length select {
        border: 2px solid #e3e6f0;
        border-radius: 6px;
        padding: 6px 10px;
        margin: 0 8px;
    }

    .dataTables_wrapper .dataTables_filter input {
        border: 2px solid #e3e6f0;
        border-radius: 8px;
        padding: 8px 15px;
        margin-left: 8px;
        transition: all 0.2s ease;
    }

    .dataTables_wrapper .dataTables_filter input:focus {
        border-color: #6f42c1;
        outline: none;
        box-shadow: 0 0 0 0.2rem rgba(111, 66, 193, 0.15);
    }

    .dataTables_wrapper .dataTables_paginate .paginate_button {
        padding: 6px 12px;
        margin: 0 2px;
        border-radius: 6px;
    }

    .dataTables_wrapper .dataTables_paginate .paginate_button.current {
        background: linear-gradient(135deg, #6f42c1, #5a32a3) !important;
        border: none !important;
        color: white !important;
    }

    .dataTables_wrapper .dataTables_paginate .paginate_button:hover {
        background: #f8f9fc !important;
        border-color: #6f42c1 !important;
        color: #6f42c1 !important;
    }

    .dataTables_wrapper .dataTables_info {
        padding-top: 15px;
        font-size: 13px;
        color: #858796;
    }

    /* ========================================
       RESPONSIVE
    ======================================== */
    @media (max-width: 768px) {
        .page-header-admin {
            padding: 20px;
        }

        .header-title h1 {
            font-size: 22px;
        }

        .kpi-value-admin {
            font-size: 28px;
        }

        .user-display {
            flex-direction: column;
            align-items: flex-start;
        }

        .user-avatar {
            margin-bottom: 10px;
        }
    }
</style>
@endsection

@section('content')
<div class="container-fluid">
    <!-- HEADER PRINCIPAL -->
    <div class="page-header-admin">
        <div class="header-content">
            <div class="d-flex justify-content-between align-items-start flex-wrap">
                <div class="d-flex align-items-center mb-2 mb-md-0">
                    <div class="header-icon-admin mr-3">
                        <i class="fas fa-users-cog"></i>
                    </div>
                    <div class="header-title">
                        <h1>Gestión de Accesos y Roles</h1>
                        <p class="header-subtitle mb-0">
                            <i class="fas fa-shield-alt mr-2"></i>Administra los permisos y niveles de acceso del personal
                        </p>
                    </div>
                </div>
                <div>
                    <a href="{{ route('admin.roles.index') }}" class="btn btn-configure-roles">
                        <i class="fas fa-shield-alt mr-2"></i>Configurar Roles
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- ALERTAS MEJORADAS -->
    @if (session('success'))
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

    @if (session('error'))
        <div class="alert alert-danger alert-enhanced alert-dismissible fade show" role="alert">
            <div class="d-flex align-items-center">
                <i class="fas fa-exclamation-triangle fa-2x mr-3"></i>
                <div>
                    <strong>¡Error!</strong><br>
                    {{ session('error') }}
                </div>
            </div>
            <button type="button" class="close" data-dismiss="alert">
                <span>&times;</span>
            </button>
        </div>
    @endif

    <!-- KPIs ADMINISTRATIVOS -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card kpi-card-admin kpi-admin-purple">
                <div class="kpi-card-body-admin">
                    <div class="kpi-floating-icon-admin">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="kpi-label-admin">Total Usuarios</div>
                    <div class="kpi-value-admin">{{ $stats['total'] ?? 0 }}</div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card kpi-card-admin kpi-admin-danger">
                <div class="kpi-card-body-admin">
                    <div class="kpi-floating-icon-admin">
                        <i class="fas fa-user-shield"></i>
                    </div>
                    <div class="kpi-label-admin">Administradores</div>
                    <div class="kpi-value-admin">{{ $stats['admins'] ?? 0 }}</div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card kpi-card-admin kpi-admin-warning">
                <div class="kpi-card-body-admin">
                    <div class="kpi-floating-icon-admin">
                        <i class="fas fa-user-clock"></i>
                    </div>
                    <div class="kpi-label-admin">Pendientes por Rol</div>
                    <div class="kpi-value-admin">{{ $stats['sin_rol'] ?? 0 }}</div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card kpi-card-admin kpi-admin-info">
                <div class="kpi-card-body-admin">
                    <div class="kpi-floating-icon-admin">
                        <i class="fas fa-tags"></i>
                    </div>
                    <div class="kpi-label-admin">Roles Activos</div>
                    <div class="kpi-value-admin">{{ $stats['roles_activos'] ?? 0 }}</div>
                </div>
            </div>
        </div>
    </div>

    <!-- TABLA DE USUARIOS -->
    <div class="users-table-card">
        <div class="users-table-header">
            <h6 class="users-table-title">
                <i class="fas fa-list"></i>
                Directorio de Usuarios del Sistema
            </h6>
        </div>
        <div class="users-table-body">
            <div class="table-responsive">
                <table class="table users-table" id="dataTableUsers">
                    <thead>
                        <tr>
                            <th>Usuario</th>
                            <th>Roles Asignados</th>
                            <th>Registro</th>
                            <th class="text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($users as $user)
                        <tr>
                            <!-- USUARIO CON AVATAR -->
                            <td>
                                <div class="user-display">
                                    <div class="user-avatar {{ $user->roles->isEmpty() ? 'user-avatar-warning' : '' }}">
                                        {{ strtoupper(substr($user->name, 0, 1)) }}{{ strtoupper(substr($user->last_name ?? '', 0, 1)) }}
                                        <span class="user-avatar-status {{ $user->roles->isEmpty() ? 'user-avatar-status-warning' : '' }}"></span>
                                    </div>
                                    <div class="user-details">
                                        <span class="user-name">{{ $user->name }} {{ $user->last_name ?? '' }}</span>
                                        <span class="user-email">
                                            <i class="fas fa-envelope"></i>
                                            {{ $user->email }}
                                        </span>
                                    </div>
                                </div>
                            </td>

                            <!-- ROLES -->
                            <td>
                                <div class="roles-container">
                                    @forelse ($user->roles as $role)
                                        @php
                                            $badgeClass = 'role-badge-default';
                                            if(stripos($role->name, 'admin') !== false) {
                                                $badgeClass = 'role-badge-admin';
                                            } elseif(stripos($role->name, 'medico') !== false) {
                                                $badgeClass = 'role-badge-medico';
                                            }
                                        @endphp
                                        <span class="role-badge {{ $badgeClass }}">
                                            <i class="fas fa-tag"></i>
                                            {{ strtoupper($role->name) }}
                                        </span>
                                    @empty
                                        <span class="role-badge role-badge-warning">
                                            <i class="fas fa-exclamation-circle"></i>
                                            Sin Rol Asignado
                                        </span>
                                    @endforelse
                                </div>
                            </td>

                            <!-- REGISTRO -->
                            <td>
                                <div class="registration-info">
                                    <span class="registration-date">
                                        {{ $user->created_at ? $user->created_at->format('d/m/Y') : 'N/A' }}
                                    </span>
                                    <span class="registration-id">
                                        ID: #{{ str_pad($user->id, 4, '0', STR_PAD_LEFT) }}
                                    </span>
                                </div>
                            </td>

                            <!-- ACCIONES -->
                            <td class="actions-cell">
                                <div class="dropdown">
                                    <button class="btn-actions-admin" type="button" data-toggle="dropdown">
                                        <i class="fas fa-ellipsis-v text-gray-400"></i>
                                    </button>
                                    <div class="dropdown-menu dropdown-menu-right dropdown-menu-admin">
                                        <div class="dropdown-header-admin">Configuración</div>
                                        
                                        <a class="dropdown-item-admin" href="{{ route('admin.users.edit-roles', $user) }}">
                                            <i class="fas fa-user-shield text-purple"></i>
                                            Gestionar Roles
                                        </a>
                                        
                                        <a class="dropdown-item-admin" href="{{ route('admin.users.edit-roles', $user) }}">
                                            <i class="fas fa-edit text-info"></i>
                                            Editar Perfil
                                        </a>
                                        
                                        <div class="dropdown-divider-admin"></div>
                                        
                                        <form method="POST" 
                                              action="{{ route('admin.users.update-roles', $user) }}" 
                                              class="form-deactivate"
                                              data-user-name="{{ $user->name }} {{ $user->last_name ?? '' }}">
                                            @csrf
                                            @method('PUT')
                                            <button type="submit" class="dropdown-item-admin dropdown-item-danger">
                                                <i class="fas fa-user-slash"></i>
                                                Desactivar Acceso
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    // Inicializar DataTable
    $('#dataTableUsers').DataTable({
        language: {
            url: "//cdn.datatables.net/plug-ins/1.10.24/i18n/Spanish.json"
        },
        order: [[ 0, "asc" ]],
        pageLength: 15,
        responsive: true,
        columnDefs: [
            { orderable: false, targets: 3 }
        ],
        dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>' +
             '<"row"<"col-sm-12"tr>>' +
             '<"row"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>'
    });

    // Confirmación para desactivar acceso
    $('.form-deactivate').on('submit', function(e) {
        e.preventDefault();
        const form = this;
        const userName = $(form).data('user-name');
        
        Swal.fire({
            title: '¿Desactivar Acceso del Usuario?',
            html: `<p>Estás a punto de desactivar el acceso de <strong>${userName}</strong>.</p>` +
                  '<p class="text-muted small">Se eliminarán todos los roles y permisos asociados. Esta acción puede afectar el acceso al sistema.</p>',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#e74a3b',
            cancelButtonColor: '#858796',
            confirmButtonText: '<i class="fas fa-user-slash mr-2"></i>Sí, Desactivar',
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