{{-- resources/views/errors/403.blade.php --}}
{{-- Mostrada cuando se intenta acceder a una ruta sin permisos --}}

@extends('layouts.app')
@section('title', '403 - Acción no permitida')
@section('hide_daterange', true)
@section('hide_breadcrumb', true)
@section('content')
<div class="container-fluid d-flex align-items-center justify-content-center" style="min-height: 70vh;">
    <div class="text-center" style="max-width: 480px;">

        {{-- Ícono --}}
        <div class="mb-4">
            <i class="fas fa-warning fa-4x text-warning opacity-75"></i>
        </div>

        {{-- Título --}}
        <h2 class="h3 fw-bold text-gray-800 mb-2">
            403 - Acción no permitida
        </h2>

        {{-- Mensaje --}}
        <p class="text-muted mb-4">
            No se pudo establecer conexión con <strong>{{ $erp_name ?? 'el sistema ERP' }}</strong>.
            Los datos del negocio no están accesibles en este momento.
        </p>
        <p class="text-muted mb-4">Parece que no tienes los permisos necesarios para acceder a esta sección o realizar esta acción. Si crees que esto es un error, por favor contacta al administrador del sistema.</p>

        {{-- Posibles causas --}}
        <div class="card border-left-warning shadow-sm mb-4 text-start">
            <div class="card-body py-3">
                <p class="small text-muted fw-semibold mb-2">Posibles causas:</p>
                <ul class="small text-muted mb-0 ps-3">
                    <li>No tienes permisos para acceder a esta sección.</li>
                    <li>Tu rol de usuario no tiene los privilegios necesarios.</li>
                    <li>La configuración de permisos del sistema ha cambiado.</li>
                    <li>Las credenciales de acceso han expirado.</li>
                    <li>El sistema ha sido actualizado y los permisos han cambiado.</li>
                    <li>Has intentado acceder a una función que no está habilitada para tu rol.</li>
                </ul>
            </div>
        </div>

        {{-- Acciones --}}
        <div class="d-flex gap-2 justify-content-center">
            <a href="{{ url()->previous() }}" class="btn btn-outline-secondary btn-sm">
                <i class="fas fa-arrow-left me-1"></i> Volver
            </a>
            <a href="{{ url()->current() }}" class="btn btn-primary btn-sm">
                <i class="fas fa-sync-alt me-1"></i> Reintentar
            </a>
            <a href="{{ route('about') }}" class="btn btn-outline-info btn-sm">
                <i class="fas fa-eye me-1"></i> Más información
            </a>
        </div>

        {{-- Timestamp --}}
        <p class="text-muted small mt-4">
            {{ now()->format('d/m/Y H:i:s') }} —
            Contacte al administrador del sistema si el problema persiste.
        </p>

    </div>
</div>
@endsection
