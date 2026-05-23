@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">{{ __('Crear Nuevo Rol') }}</h1>
    </div>

    <form method="POST" action="{{ route('admin.roles.store') }}">
        @csrf
        <div class="row">
            <div class="col-xl-4 col-lg-5">
                <div class="card shadow mb-4 border-left-success">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-success">{{ __('Configuración Inicial') }}</h6>
                    </div>
                    <div class="card-body">
                        <div class="form-group">
                            <label class="font-weight-bold small">{{ __('Nombre del Rol') }}</label>
                            <input type="text" name="name" class="form-control border-left-success" 
                                   placeholder="ej: supervisor_it" value="{{ old('name') }}" required>
                        </div>
                        <p class="text-muted small">Al crear el rol, podrá asignar los permisos inmediatamente.</p>
                        <button type="submit" class="btn btn-success btn-block shadow mt-3">
                            <i class="fas fa-save mr-1"></i> {{ __('Guardar Rol') }}
                        </button>
                    </div>
                </div>
            </div>

            <div class="col-xl-8 col-lg-7">
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">{{ __('Seleccionar Permisos') }}</h6>
                    </div>
                    <div class="card-body bg-light" style="max-height: 600px; overflow-y: auto;">
                        @foreach ($permissions->groupBy(fn($p) => $p->module ?: 'GLOBAL') as $module => $modulePermissions)
                            <div class="mb-4">
                                <p class="text-xs font-weight-bold text-primary text-uppercase mb-2 border-bottom">{{ $module }}</p>
                                <div class="row">
                                    @foreach ($modulePermissions as $permission)
                                        <div class="col-md-6 mb-2">
                                            <div class="custom-control custom-switch bg-white p-2 border rounded shadow-sm pl-5">
                                                <input type="checkbox" name="permissions[]" value="{{ $permission->name }}" 
                                                       id="perm_{{ $permission->id }}" class="custom-control-input">
                                                <label class="custom-control-label small cursor-pointer" for="perm_{{ $permission->id }}">
                                                    {{ str_replace('_', ' ', $permission->name) }}
                                                </label>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection