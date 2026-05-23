@extends('layouts.app')

@section('title-page', 'Configurar Rol: ' . $role->name)

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
    .card-title-custom i { color: var(--purple-primary); margin-right: 10px; font-size: 18px; }

    .form-control-custom {
        border: 2px solid #e3e6f0; border-radius: 8px; padding: 12px 15px; font-size: 14px; transition: all 0.2s;
    }
    .form-control-custom:focus { border-color: var(--purple-primary); box-shadow: 0 0 0 0.2rem rgba(111, 66, 193, 0.15); }

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

    /* Customizar el switch de Bootstrap */
    .custom-switch { padding-left: 2.5rem; }
    .custom-control-input:checked ~ .custom-control-label::before { border-color: var(--purple-primary); background-color: var(--purple-primary); }
</style>
@endsection

@section('content')
<div class="container-fluid pb-5">

    <div class="page-header-master">
        <div class="header-content">
            <div class="d-flex justify-content-between align-items-center flex-wrap">
                <div class="d-flex align-items-center mb-2 mb-md-0">
                    <div class="header-icon mr-3">
                        <i class="fas fa-user-edit"></i>
                    </div>
                    <div class="header-title">
                        <h1>Configurar Rol: <span class="text-warning">{{ str_replace('_', ' ', strtoupper($role->name)) }}</span></h1>
                        <p class="header-subtitle">
                            <i class="fas fa-sliders-h mr-2"></i> Ajusta las capacidades y accesos de este perfil
                        </p>
                    </div>
                </div>
                <div>
                    @can('seguridad.roles. ver')
                        <a href="{{ route('admin.roles.index') }}" class="btn btn-light rounded-pill font-weight-bold shadow-sm px-4">
                            <i class="fas fa-arrow-left mr-2"></i>Volver al Listado
                        </a>
                    @endcan
                </div>
            </div>
        </div>
    </div>

    <form method="POST" action="{{ route('admin.roles.update', $role) }}" id="roleForm">
        @csrf @method('PUT')

        <div class="row">
            @can('seguridad.roles.editar')
                <div class="col-xl-4 col-lg-5 mb-4">
                    <div class="card-custom sticky-top" style="top: 20px;">
                        <div class="card-header-custom">
                            <h6 class="card-title-custom"><i class="fas fa-info-circle"></i> Datos del Perfil</h6>
                        </div>
                        <div class="card-body p-4 bg-white">
                            
                            <div class="form-group mb-4">
                                <label class="font-weight-bold text-gray-700 small text-uppercase">Nombre del Rol</label>
                                <input type="text" name="name" class="form-control form-control-custom text-lowercase" 
                                       value="{{ old('name', $role->name) }}" 
                                       {{ in_array(strtolower($role->name), ['super_admin']) ? 'readonly' : 'required' }}>
                                @if(in_array(strtolower($role->name), ['super_admin']))
                                    <small class="text-danger mt-2 d-block"><i class="fas fa-lock mr-1"></i> El nombre de este rol de sistema no puede ser modificado.</small>
                                @else
                                    <small class="text-muted mt-2 d-block">Utilice formato snake_case (ej: medico_ocupacional).</small>
                                @endif
                            </div>

                            <div class="p-3 bg-light rounded-lg border mb-4">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="font-weight-bold text-gray-700 small">Usuarios Asignados:</span>
                                    <span class="badge badge-primary">{{ $role->users()->count() ?? 0 }}</span>
                                </div>
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="font-weight-bold text-gray-700 small">Permisos Actuales:</span>
                                    <span class="badge badge-success" id="counterSelected">{{ count($rolePermissions) }}</span>
                                </div>
                            </div>

                            <button type="submit" class="btn-submit-master">
                                <i class="fas fa-save mr-2"></i> Guardar Configuración
                            </button>
                        </div>
                    </div>
                </div>
                
                <div class="col-xl-8 col-lg-7">
                    <div class="card-custom">
                        <div class="card-header-custom flex-wrap gap-3">
                            <h6 class="card-title-custom"><i class="fas fa-key"></i> Matriz de Permisos</h6>
                            
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
                                        <h6 class="module-name">
                                            <i class="fas fa-folder text-warning mr-2"></i>{{ $module }}
                                            <span class="badge badge-light text-muted border ml-2">{{ $modulePermissions->count() }}</span>
                                        </h6>
                                        
                                        <button type="button" class="btn-toggle-all" data-target=".chk-{{ $modId }}">
                                            <i class="fas fa-check-double mr-1"></i> Seleccionar Todo
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
                                                    <div class="custom-control custom-switch">
                                                        <input type="checkbox" name="permissions[]" value="{{ $permission->name }}" 
                                                               id="perm_{{ $permission->id }}" 
                                                               class="custom-control-input perm-checkbox chk-{{ $modId }}"
                                                               {{ $isChecked ? 'checked' : '' }}>
                                                        <label class="custom-control-label" for="perm_{{ $permission->id }}"></label>
                                                    </div>
                                                </label>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endforeach

                            <div id="noResultsMsg" class="text-center py-5 d-none">
                                <i class="fas fa-search-minus fa-3x text-gray-300 mb-3"></i>
                                <h5 class="text-gray-500 font-weight-bold">No se encontraron permisos</h5>
                                <p class="text-muted">Intenta usar otras palabras clave.</p>
                            </div>

                        </div>
                    </div>
                </div>   
            </div>
        </form>
    @else
        <div class="col-xl-12 col-lg-7">
            <div class="card-custom">
                <div class="card-header-custom flex-wrap gap-3">
                    <h6 class="card-title-custom"><i class="fas fa-key"></i> Acceso Restringido</h6>
                </div>

                <div class="card-body">
                    <div class="text-center py-5">
                        <i class="fas fa-user-lock fa-3x text-gray-200"></i>
                        <p class="text-muted mt-2">Acceso no autorizado para realizar esta accion.</p>
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
                $(this).html('<i class="fas fa-square mr-1"></i> Desmarcar Todo');
            } else {
                $(this).html('<i class="fas fa-check-double mr-1"></i> Seleccionar Todo');
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