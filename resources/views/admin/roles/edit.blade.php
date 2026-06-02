{{--
    Vista de Edición de Roles - Panel de Administración

    Descripción:
    Esta vista permite a los administradores configurar los roles del sistema, asignando o revocando permisos específicos. Se presenta una interfaz moderna y amigable, con un diseño limpio y funcional que facilita la gestión de roles y sus permisos asociados.

    Características Destacadas:
    - Diseño responsivo y moderno con una paleta de colores morados.
    - Encabezado claro con iconografía relevante.
    - Formulario lateral para editar el nombre del rol y mostrar estadísticas rápidas.
    - Matriz de permisos agrupada por módulos, con tarjetas interactivas para cada permiso.
    - Funcionalidad de "Seleccionar Todo" por módulo para agilizar la asignación de permisos.
    - Buscador inteligente para filtrar permisos en tiempo real.
    - Indicadores visuales claros para los permisos activos.

     Requisitos:
     - El usuario debe tener el permiso 'seguridad.roles.editar' para acceder a la edición.
     - El nombre del rol 'super_admin' es inmutable por ser un rol de sistema crítico.

     Tecnologías Utilizadas:
     - Laravel Blade para la estructura de la vista.
     - Bootstrap 5 para estilos y componentes.
     - jQuery para interactividad y manipulación del DOM.
     - FontAwesome para iconografía.
--}}
@extends('layouts.app')
@section('title-page', 'Configurar Rol: ' . $role->name)

@section('breadcrumb')
    <a href="{{ route('admin.users.index') }}" style="color:var(--text-muted);text-decoration:none;">Usuarios</a>
    <span style="color:#cbd5e1;margin:0 4px;">/</span>
    <a href="{{ route('admin.roles.index') }}" style="color:var(--text-muted);text-decoration:none;">Roles</a>
    <span style="color:#cbd5e1;margin:0 4px;">/</span>
    <span class="current">Configurar Rol: {{ $role->name }}</span>
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
       COMPONENTES DEL FORMULARIO
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
    .card-title-custom i { color: var(--purple-primary); margin-secondary: 10px; font-size: 18px; }

    .form-control-custom {
        border: 2px solid #e3e6f0; border-radius: 8px; padding: 12px 15px; font-size: 14px; transition: all 0.2s;
    }
    .form-control-custom:focus { border-color: var(--purple-primary); box-shadow: 0 0 0 0.25rem rgba(111, 66, 193, 0.15); }

    .btn-submit-master {
        background: linear-gradient(135deg, var(--purple-primary), var(--purple-dark));
        color: white; border: none; padding: 14px 20px; border-radius: 8px;
        font-weight: 700; font-size: 15px; box-shadow: 0 4px 15px rgba(111, 66, 193, 0.3);
        transition: all 0.3s; width: 100%; display: flex; justify-content: center; align-items: center;
    }
    .btn-submit-master:hover { transform: translateY(-2px); box-shadow: 0 8px 25px rgba(111, 66, 193, 0.4); color: white; }

    .search-wrapper { position: relative; width: 300px; }
    .search-wrapper i { position: absolute; left: 15px; top: 50%; transform: translateY(-50%); color: #b7b9cc; }
    .search-wrapper input { padding-left: 40px; border-radius: 50px; }

    /* ========================================
       TARJETAS DE PERMISOS (SWITCH CARDS)
    ======================================== */
    .module-container {
        background: white; border: 1px solid #eaecf4; border-radius: 12px;
        padding: 20px; margin-bottom: 20px; border-top: 4px solid var(--purple-light);
        transition: all 0.3s ease;
    }
    .module-container:hover { box-shadow: 0 5px 15px rgba(0,0,0,0.05); }
    .module-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; border-bottom: 1px dashed #eaecf4; padding-bottom: 15px; }
    .module-name { font-size: 16px; font-weight: 800; color: #4a5568; margin: 0; text-transform: uppercase; letter-spacing: 0.5px; }

    .btn-toggle-all {
        font-size: 12px; font-weight: 700; color: var(--purple-primary); background: rgba(111, 66, 193, 0.1);
        border: none; padding: 6px 12px; border-radius: 20px; transition: all 0.2s; cursor: pointer;
    }
    .btn-toggle-all:hover { background: var(--purple-primary); color: white; }

    .perm-card { margin-bottom: 15px; }
    .perm-switch-wrapper {
        background: #f8f9fc; border: 2px solid #eaecf4; border-radius: 10px;
        padding: 12px 15px; display: flex; justify-content: space-between; align-items: center;
        transition: all 0.2s ease; cursor: pointer; height: 100%;
    }
    .perm-switch-wrapper:hover { border-color: var(--purple-light); background: white; }

    /* Estado Activo del Permiso */
    .perm-switch-wrapper.is-active {
        border-color: var(--purple-primary); background: rgba(111, 66, 193, 0.05);
    }
    .perm-switch-wrapper.is-active .perm-label { color: var(--purple-dark); font-weight: 700; }
    .perm-switch-wrapper.is-active .perm-icon { color: var(--purple-primary); }

    .perm-info { display: flex; align-items: center; gap: 10px; flex: 1; overflow: hidden; }
    .perm-icon { color: #b7b9cc; font-size: 14px; transition: color 0.2s; }
    .perm-label { font-size: 13px; font-weight: 600; color: #5a5c69; margin: 0; cursor: pointer; word-break: break-word; transition: all 0.2s; }

    /* Customizar el switch nativo de Bootstrap 5 */
    .form-switch .form-check-input {
        width: 2.5em; height: 1.25em; cursor: pointer;
    }
    .form-switch .form-check-input:checked {
        background-color: var(--purple-primary); border-color: var(--purple-primary);
    }
    .form-switch .form-check-input:focus {
        border-color: var(--purple-light); box-shadow: 0 0 0 0.25rem rgba(111, 66, 193, 0.15);
    }
</style>
@endpush

@section('content')
<div class="container-fluid pb-5">
    <div class="d-flex align-items-start justify-content-between flex-wrap gap-3 mb-4">
        <div>
            <h1 class="font-display mb-1" style="font-size:22px;font-weight:800;">
               Configurando Rol
            </h1>
            <p class="mb-0" style="font-size:13px;color:var(--text-muted);">
              <i class="fas fa-sliders-h me-2"></i> Ajusta las capacidades y accesos del rol <span class="text-warning">{{ str_replace('_', ' ', strtoupper($role->name)) }}</span> para controlar qué acciones pueden realizar los usuarios asignados a él.
            </p>
        </div>

        <div class="d-flex gap-2 align-items-center">
            @can('seguridad.roles.ver')
            <a href="{{ route('admin.roles.index') }}"
            class="btn btn-sm btn-outline-secondary" style="border-radius:9px;font-size:12.5px;">
                <i class="fas fa-arrow-left me-1"></i> Volver al Listado
            </a>
            @endcan
        </div>
    </div>

    @can('seguridad.roles.editar')
    <form method="POST" action="{{ route('admin.roles.update', $role) }}" id="roleForm">
        @csrf @method('PUT')

        <div class="row">
            <div class="col-xl-4 col-lg-5 mb-4">
                <div class="card-custom sticky-top" style="top: 20px;">
                    <div class="card-header-custom">
                        <h6 class="card-title-custom"><i class="fas fa-info-circle me-2"></i> Datos del Perfil</h6>
                    </div>
                    <div class="card-body p-4 bg-white">

                        <div class="form-group mb-4">
                            <label class="fw-bold text-secondary small text-uppercase mb-2">Nombre del Rol</label>
                            <input type="text" name="name" class="form-control form-control-custom text-lowercase"
                                   value="{{ old('name', $role->name) }}"
                                   {{ in_array(strtolower($role->name), ['super_admin']) ? 'readonly' : 'required' }}>
                            @if(in_array(strtolower($role->name), ['super_admin']))
                                <small class="text-danger mt-2 d-block"><i class="fas fa-lock me-1"></i> El nombre de este rol de sistema no puede ser modificado.</small>
                            @else
                                <small class="text-muted mt-2 d-block">Utilice formato snake_case (ej: medico_ocupacional).</small>
                            @endif
                        </div>

                        <div class="p-3 bg-light rounded border mb-4">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="fw-bold text-secondary small">Usuarios Asignados:</span>
                                <span class="badge bg-primary rounded-pill">{{ $role->users()->count() ?? 0 }}</span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="fw-bold text-secondary small">Permisos Actuales:</span>
                                <span class="badge bg-success rounded-pill" id="counterSelected">{{ count($rolePermissions) }}</span>
                            </div>
                        </div>

                        <button type="submit" class="btn-submit-master">
                            <i class="fas fa-save me-2"></i> Guardar Configuración
                        </button>
                    </div>
                </div>
            </div>

            <div class="col-xl-8 col-lg-7">
                <div class="card-custom">
                    <div class="card-header-custom flex-wrap gap-3">
                        <h6 class="card-title-custom"><i class="fas fa-key me-2"></i> Matriz de Permisos</h6>

                        <div class="search-wrapper">
                            <i class="fas fa-search"></i>
                            <input type="text" id="filterPerms" class="form-control form-control-custom" placeholder="Buscar permiso (ej: crear)...">
                        </div>
                    </div>

                    <div class="card-body p-4 bg-light">
                        @php
                            // Agrupamos por el campo 'module', si no existe o está vacío, va a 'OTROS'
                            $groupedPermissions = $permissions->groupBy(fn($p) => $p->module ?: 'OTROS');
                        @endphp

                        @foreach ($groupedPermissions as $module => $modulePermissions)
                            @php
                                // Identificador único limpio para el JS
                                $modId = Str::slug($module);
                            @endphp

                            <div class="module-container" id="module_{{ $modId }}">
                                <div class="module-header">
                                    <h6 class="module-name d-flex align-items-center">
                                        <i class="fas fa-folder text-warning me-2"></i>{{ $module }}
                                        <span class="badge bg-white text-muted border ms-2">{{ $modulePermissions->count() }}</span>
                                    </h6>

                                    <button type="button" class="btn-toggle-all" data-target=".chk-{{ $modId }}">
                                        <i class="fas fa-check-double me-1"></i> Seleccionar Todo
                                    </button>
                                </div>

                                <div class="row">
                                    @foreach ($modulePermissions as $permission)
                                        @php
                                            $isChecked = in_array($permission->name, $rolePermissions);
                                        @endphp
                                        <div class="col-md-6 perm-card">
                                            <label class="perm-switch-wrapper {{ $isChecked ? 'is-active' : '' }}" for="perm_{{ $permission->id }}">
                                                <div class="perm-info">
                                                    <i class="fas fa-shield-alt perm-icon"></i>
                                                    <span class="perm-label">{{ str_replace('_', ' ', $permission->name) }}</span>
                                                </div>
                                                <div class="form-check form-switch mb-0">
                                                    <input type="checkbox" name="permissions[]" value="{{ $permission->name }}"
                                                           id="perm_{{ $permission->id }}"
                                                           class="form-check-input perm-checkbox chk-{{ $modId }}"
                                                           {{ $isChecked ? 'checked' : '' }}>
                                                </div>
                                            </label>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach

                        <div id="noResultsMsg" class="text-center py-5 d-none">
                            <i class="fas fa-search-minus fa-3x text-muted mb-3"></i>
                            <h5 class="text-secondary fw-bold">No se encontraron permisos</h5>
                            <p class="text-muted">Intenta usar otras palabras clave.</p>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </form>
    @else
    <div class="row">
        <div class="col-12">
            <div class="card-custom">
                <div class="card-header-custom">
                    <h6 class="card-title-custom"><i class="fas fa-key me-2"></i> Acceso Restringido</h6>
                </div>

                <div class="card-body">
                    <div class="text-center py-5">
                        <i class="fas fa-user-lock fa-3x text-muted mb-3"></i>
                        <p class="text-muted mt-2">Acceso no autorizado para realizar esta acción.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endcan
</div>
@endsection

@push('scripts')
<script>
    $(document).ready(function() {

        // 1. Efecto Visual al hacer check (Iluminar Tarjeta)
        $('.perm-checkbox').on('change', function() {
            let wrapper = $(this).closest('.perm-switch-wrapper');
            if ($(this).is(':checked')) {
                wrapper.addClass('is-active');
            } else {
                wrapper.removeClass('is-active');
            }
            actualizarContador();
        });

        // 2. Botón "Seleccionar Todo" por módulo
        $('.btn-toggle-all').on('click', function(e) {
            e.preventDefault();
            let targetClass = $(this).data('target');
            let checkboxes = $(targetClass);

            // Verificamos si todos están marcados para desmarcarlos, o viceversa
            let allChecked = checkboxes.length === checkboxes.filter(':checked').length;

            checkboxes.prop('checked', !allChecked).trigger('change');

            // Cambiar texto del botón
            if(!allChecked) {
                $(this).html('<i class="fas fa-square me-1"></i> Desmarcar Todo');
            } else {
                $(this).html('<i class="fas fa-check-double me-1"></i> Seleccionar Todo');
            }
        });

        // 3. Buscador Inteligente
        $('#filterPerms').on('keyup', function() {
            let val = $(this).val().toLowerCase();
            let modulesVisible = 0;

            $('.module-container').each(function() {
                let moduleHasVisibleCards = false;

                // Buscar dentro de las tarjetas del módulo
                $(this).find('.perm-card').each(function() {
                    let txt = $(this).text().toLowerCase();
                    if (txt.includes(val)) {
                        $(this).show();
                        moduleHasVisibleCards = true;
                    } else {
                        $(this).hide();
                    }
                });

                // Ocultar el módulo completo si no tiene tarjetas visibles
                if (moduleHasVisibleCards) {
                    $(this).show();
                    modulesVisible++;
                } else {
                    $(this).hide();
                }
            });

            // Mostrar mensaje si no hay resultados
            if (modulesVisible === 0) {
                $('#noResultsMsg').removeClass('d-none');
            } else {
                $('#noResultsMsg').addClass('d-none');
            }
        });

        // Función para actualizar contador lateral en tiempo real
        function actualizarContador() {
            let total = $('.perm-checkbox:checked').length;
            $('#counterSelected').text(total);
        }

    });
</script>
@endpush
