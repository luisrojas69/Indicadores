{{--
    Pagina para que el usuario pueda editar su perfil, cambiar su contraseña y gestionar sus preferencias de seguridad.
    Diseño limpio y moderno inspirado en SB Admin 2 optimizado para Bootstrap 5.
--}}
@extends('layouts.app')
@section('title-page', 'Mi Perfil y Configuración - ' . Auth::user()->fullname)

@section('breadcrumb')
    <a href="{{ route('admin.users.index') }}" class="text-decoration-none" style="color:var(--text-muted);">Usuarios</a>
    <span style="color:#cbd5e1; margin:0 4px;">/</span>
    <span class="current">Editar Perfil</span>
@endsection

@push('styles')
<style>
    /* ========================================
       VARIABLES GLOBALES - TEMA AZUL
    ======================================== */
    :root {
        --blue-primary: #4e73df;
        --blue-dark: #2e59d9;
        --blue-light: #eaecf4;
        --success: #1cc88a;
        --danger: #e74a3b;
        --warning: #f6c23e;
        --info: #36b9cc;
    }

    body { background: #f8f9fc; }

    /* ========================================
       COMPONENTE CARD PERSONALIZADO
    ======================================== */
    .card-custom {
        border-radius: 12px;
        border: none;
        box-shadow: 0 4px 15px rgba(0,0,0,0.05);
        margin-bottom: 25px;
        overflow: hidden;
    }
    .card-header-custom {
        background: white;
        border-bottom: 2px solid var(--blue-light);
        padding: 20px 25px;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    .card-title-custom {
        font-size: 16px;
        font-weight: 800;
        color: #2c3e50;
        margin: 0;
        display: flex;
        align-items: center;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    .card-title-custom i { color: var(--blue-primary); margin-right: 10px; font-size: 18px; }

    /* ========================================
       PERFIL DEL USUARIO (AVATAR)
    ======================================== */
    .profile-avatar-wrapper { text-align: center; padding: 30px 20px 10px; position: relative; }
    .avatar-circle-xl {
        width: 130px;
        height: 130px;
        margin: 0 auto 15px;
        background: linear-gradient(135deg, var(--blue-primary) 0%, var(--blue-dark) 100%);
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
        font-weight: 800;
        font-size: 45px;
        box-shadow: 0 8px 25px rgba(78, 115, 223, 0.3);
        border: 4px solid white;
    }
    .profile-name { font-size: 22px; font-weight: 800; color: #2c3e50; margin-bottom: 5px; }
    .profile-role-badge {
        background: rgba(78, 115, 223, 0.1);
        color: var(--blue-primary);
        padding: 5px 15px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 700;
        display: inline-block;
        margin: 2px;
        border: 1px solid rgba(78, 115, 223, 0.2);
    }

    .profile-info-list { list-style: none; padding: 0; margin: 0; }
    .profile-info-item {
        padding: 15px 20px;
        border-top: 1px dashed var(--blue-light);
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    .profile-info-label { font-size: 13px; font-weight: 700; color: #858796; }
    .profile-info-value { font-size: 14px; font-weight: 600; color: #2c3e50; }

    /* ========================================
       FORMULARIO Y CAMPOS (BOOSTRAP 5 OPTIMIZED)
    ======================================== */
    .form-section-title {
        font-size: 13px;
        font-weight: 800;
        color: #b7b9cc;
        text-transform: uppercase;
        letter-spacing: 1px;
        margin-bottom: 20px;
        padding-bottom: 10px;
        border-bottom: 1px solid var(--blue-light);
        display: flex;
        align-items: center;
    }
    .form-section-title i { margin-right: 8px; color: var(--blue-primary); }

    .form-group-custom { margin-bottom: 20px; }
    .form-label-custom { font-size: 13px; font-weight: 700; color: #4a5568; margin-bottom: 8px; display: flex; align-items: center; }

    .form-control-custom {
        border: 2px solid #e3e6f0;
        border-radius: 8px;
        padding: 12px 15px;
        font-size: 14px;
        color: #5a5c69;
        transition: all 0.2s;
    }
    .form-control-custom:focus {
        border-color: var(--blue-primary);
        box-shadow: 0 0 0 0.2rem rgba(78, 115, 223, 0.15);
    }
    .form-control-custom::placeholder { color: #b7b9cc; }

    /* Bootstrap 5 input groups do not use pre/append layout classes */
    .input-group-text-custom {
        background: transparent;
        border: 2px solid #e3e6f0;
        color: #b7b9cc;
        border-top-left-radius: 8px;
        border-bottom-left-radius: 8px;
        padding-left: 15px;
        padding-right: 15px;
    }
    .input-group .form-control-custom {
        border-top-left-radius: 0;
        border-bottom-left-radius: 0;
        border-left: none;
    }
    .input-group:focus-within .input-group-text-custom {
        border-color: var(--blue-primary);
        color: var(--blue-primary);
    }

    /* Manejo visual nativo para errores */
    .form-control-custom.is-invalid {
        border-color: var(--danger) !important;
        background-image: none; /* Quitamos el icono por defecto de BS para mantener la estetica clean */
    }
    .form-control-custom.is-invalid ~ .input-group-text-custom {
        border-color: var(--danger) !important;
    }

    .btn-toggle-pwd {
        border: 2px solid #e3e6f0;
        border-left: none;
        background: transparent;
        color: #a3a6b5;
        border-top-right-radius: 8px;
        border-bottom-right-radius: 8px;
        transition: all 0.2s;
    }
    .btn-toggle-pwd:hover {
        color: var(--blue-primary);
    }

    .btn-submit-master {
        background: linear-gradient(135deg, var(--blue-primary), var(--blue-dark));
        color: white;
        border: none;
        padding: 14px 25px;
        border-radius: 8px;
        font-weight: 700;
        font-size: 15px;
        box-shadow: 0 4px 15px rgba(78, 115, 223, 0.3);
        transition: all 0.3s;
    }
    .btn-submit-master:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(78, 115, 223, 0.4);
        color: white;
    }
</style>
@endpush

@section('content')
<div class="container-fluid pb-5">
    {{-- Encabezado superior --}}
    <div class="d-flex align-items-start justify-content-between flex-wrap gap-3 mb-4">
        <div>
            <h1 class="font-display mb-1" style="font-size:22px;font-weight:800;">
                Ajustes de Cuenta
            </h1>
            <p class="mb-0" style="font-size:13px; color:var(--text-muted);">
                <i class="fas fa-sliders-h me-2"></i> Gestiona tu información personal y preferencias de seguridad - <span class="text-warning fw-bold">{{ str_replace('_', ' ', strtoupper(Auth::user()->fullname)) }}</span>
            </p>
        </div>

        <div class="d-flex gap-2 align-items-center">
            @can('seguridad.usuarios.ver')
                <a href="{{ route('admin.users.index') }}" class="btn btn-sm btn-outline-secondary" style="border-radius:9px; font-size:12.5px;">
                    <i class="fas fa-arrow-left me-1"></i> Volver al Directorio
                </a>
            @endcan
        </div>
    </div>

    <div class="row">
        {{-- Tarjeta de Perfil Resumen (Izquierda) --}}
        <div class="col-xl-4 col-lg-5 mb-4 order-lg-1">
            <div class="card-custom bg-white sticky-top" style="top: 20px; z-index: 4;">
                <div class="profile-avatar-wrapper">
                    <div class="avatar-circle-xl">
                        {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}{{ strtoupper(substr(Auth::user()->last_name ?? '', 0, 1)) }}
                    </div>
                    <h4 class="profile-name">{{ Auth::user()->fullname }}</h4>
                    <p class="text-muted small mb-3"><i class="fas fa-envelope me-1"></i> {{ Auth::user()->email }}</p>

                    <div class="mb-4">
                        @forelse(Auth::user()->roles as $rol)
                            <span class="profile-role-badge">
                                <i class="fas fa-user-shield me-1"></i> {{ strtoupper(str_replace('_', ' ', $rol->name)) }}
                            </span>
                        @empty
                            <span class="profile-role-badge border-warning text-warning">
                                <i class="fas fa-exclamation-circle me-1"></i> SIN ROL
                            </span>
                        @endforelse
                    </div>
                </div>

                <ul class="profile-info-list bg-light">
                    <li class="profile-info-item">
                        <span class="profile-info-label">Estado de Cuenta</span>
                        <span class="profile-info-value text-successfw-bold"><i class="fas fa-check-circle me-1"></i> Activo</span>
                    </li>
                    <li class="profile-info-item">
                        <span class="profile-info-label">ID de Sistema</span>
                        <span class="profile-info-value fw-bold">#{{ str_pad(Auth::user()->id, 4, '0', STR_PAD_LEFT) }}</span>
                    </li>
                    <li class="profile-info-item">
                        <span class="profile-info-label">Miembro desde</span>
                        <span class="profile-info-value">{{ Auth::user()->created_at ? Auth::user()->created_at->format('M. Y') : 'N/A' }}</span>
                    </li>
                </ul>
            </div>
        </div>

        {{-- Formulario de Edición (Derecha) --}}
        <div class="col-xl-8 col-lg-7 order-lg-2">
            <div class="card-custom bg-white">
                <div class="card-header-custom">
                    <h6 class="card-title-custom"><i class="fas fa-edit"></i> Editar Información</h6>
                </div>

                <div class="card-body p-4 p-md-5">
                    <form method="POST" action="{{ route('profile.update') }}" autocomplete="off">
                        @csrf
                        @method('PUT')

                        {{-- SECCIÓN: DATOS PERSONALES --}}
                        <div class="form-section-title">
                            <i class="fas fa-id-card"></i> Información Personal
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group-custom">
                                    <label class="form-label-custom" for="name">Nombre <span class="text-danger ms-1">*</span></label>
                                    <div class="input-group">
                                        <span class="input-group-text input-group-text-custom"><i class="fas fa-user"></i></span>
                                        <input type="text" id="name" class="form-control form-control-custom @error('name') is-invalid @enderror" name="name" placeholder="Tu nombre" value="{{ old('name', Auth::user()->name) }}" required>
                                        @error('name')
                                            <div class="invalid-feedback d-block">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group-custom">
                                    <label class="form-label-custom" for="last_name">Apellido</label>
                                    <div class="input-group">
                                        <span class="input-group-text input-group-text-custom"><i class="fas fa-user"></i></span>
                                        <input type="text" id="last_name" class="form-control form-control-custom @error('last_name') is-invalid @enderror" name="last_name" placeholder="Tu apellido" value="{{ old('last_name', Auth::user()->last_name) }}">
                                        @error('last_name')
                                            <div class="invalid-feedback d-block">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="form-group-custom mb-5">
                            <label class="form-label-custom" for="email">Correo Electrónico <span class="text-danger ms-1">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text input-group-text-custom"><i class="fas fa-envelope"></i></span>
                                <input type="email" id="email" class="form-control form-control-custom @error('email') is-invalid @enderror" name="email" placeholder="correo@empresa.com" value="{{ old('email', Auth::user()->email) }}" required>
                                @error('email')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        {{-- SECCIÓN: SEGURIDAD --}}
                        <div class="form-section-title mt-5">
                            <i class="fas fa-lock"></i> Seguridad y Contraseña
                        </div>

                        <div class="alert alert-light border shadow-sm small text-muted mb-4 p-3 d-flex align-items-center" style="border-radius: 8px;">
                            <i class="fas fa-info-circle text-primary me-2 fs-5"></i>
                            <span>Si no deseas cambiar tu contraseña actual, deja los siguientes campos en blanco.</span>
                        </div>

                        <div class="form-group-custom">
                            <label class="form-label-custom" for="current_password">Contraseña Actual</label>
                            <div class="input-group">
                                <span class="input-group-text input-group-text-custom"><i class="fas fa-key"></i></span>
                                <input type="password" id="current_password" class="form-control form-control-custom @error('current_password') is-invalid @enderror" name="current_password" placeholder="Ingresa tu contraseña actual">
                                <button class="btn btn-toggle-pwd" type="button" onclick="togglePassword('current_password', this)">
                                    <i class="fas fa-eye"></i>
                                </button>
                                @error('current_password')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="row mb-4">
                            <div class="col-md-6">
                                <div class="form-group-custom">
                                    <label class="form-label-custom" for="new_password">Nueva Contraseña</label>
                                    <div class="input-group">
                                        <span class="input-group-text input-group-text-custom"><i class="fas fa-lock"></i></span>
                                        <input type="password" id="new_password" class="form-control form-control-custom @error('new_password') is-invalid @enderror" name="new_password" placeholder="Mínimo 8 caracteres">
                                        <button class="btn btn-toggle-pwd" type="button" onclick="togglePassword('new_password', this)">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        @error('new_password')
                                            <div class="invalid-feedback d-block">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group-custom">
                                    <label class="form-label-custom" for="confirm_password">Confirmar Contraseña</label>
                                    <div class="input-group">
                                        <span class="input-group-text input-group-text-custom"><i class="fas fa-lock"></i></span>
                                        <input type="password" id="confirm_password" class="form-control form-control-custom" name="password_confirmation" placeholder="Repite la nueva contraseña">
                                        <button class="btn btn-toggle-pwd" type="button" onclick="togglePassword('confirm_password', this)">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <hr class="my-4 text-muted opacity-25">

                        <div class="d-flex justify-content-end">
                            <button type="submit" class="btn-submit-master">
                                <i class="fas fa-save me-2"></i> Guardar Cambios
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    /**
     * Alterna la visibilidad del campo de contraseña especificado
     */
    function togglePassword(inputId, button) {
        const input = document.getElementById(inputId);
        const icon = button.querySelector('i');

        if (input.type === 'password') {
            input.type = 'text';
            icon.classList.remove('fa-eye');
            icon.classList.add('fa-eye-slash');
        } else {
            input.type = 'password';
            icon.classList.remove('fa-eye-slash');
            icon.classList.add('fa-eye');
        }
    }
</script>
@endsection
