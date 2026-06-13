
@extends('layouts.app')

@section('title', 'Pagina en construcción')

@section('content')
<div class="container-fluid d-flex align-items-center justify-content-center" style="min-height: 70vh;">
    <div class="text-center" style="max-width: 480px;">

        {{-- Ícono --}}
        <div class="mb-4">
            <i class="fas fa-gear fa-4x text-danger opacity-75"></i>
        </div>

        {{-- Título --}}
        <h2 class="h3 fw-bold text-gray-800 mb-2">
            Página en Construcción
        </h2>

        {{-- Mensaje --}}
        <p class="text-muted mb-4">
            Vista en construccion:
            Vuelva pronto para consultar el ranking de vendedores y sus métricas de desempeño.
        </p>

        {{-- Acciones --}}
        <div class="d-flex gap-2 justify-content-center">
            <a href="{{ url()->previous() }}" class="btn btn-outline-secondary btn-sm">
                <i class="fas fa-arrow-left me-1"></i> Volver
            </a>
            <a href="{{ url()->current() }}" class="btn btn-primary btn-sm">
                <i class="fas fa-sync-alt me-1"></i> Reintentar
            </a>
        </div>

        {{-- Timestamp --}}
        <p class="text-muted small mt-4">
            {{ now()->format('d/m/Y H:i:s') }} —
            Contacte al administrador del sistema para mayor información.
        </p>

    </div>
</div>
@endsection
