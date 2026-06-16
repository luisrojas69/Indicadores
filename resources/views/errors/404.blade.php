{{-- resources/views/errors/404.blade.php --}}
{{-- Mostrada cuando se intenta acceder a una ruta que no existe --}}

@extends('layouts.app')
@section('title', '404 - Página no encontrada')
@section('hide_daterange', true)
@section('hide_breadcrumb', true)
@section('content')
<div class="container-fluid d-flex align-items-center justify-content-center" style="min-height: 70vh;">
    <div class="text-center" style="max-width: 480px;">

        {{-- Ícono --}}
        <div class="mb-4">
            <i class="fas fa-search fa-4x text-warning opacity-75"></i>
        </div>

        {{-- Título --}}
        <h2 class="h3 fw-bold text-gray-800 mb-2">
            404 - Página no encontrada
        </h2>

        {{-- Mensaje --}}
        <p class="text-muted mb-4">
            La página que estás buscando no existe o ha sido movida.
        </p>
        <p class="text-muted mb-4">Si crees que esto es un error, por favor contacta al administrador del sistema.</p>

        {{-- Posibles causas --}}
        <div class="card border-left-warning shadow-sm mb-4 text-start">
            <div class="card-body py-3">
                <p class="small text-muted fw-semibold mb-2">Posibles causas:</p>
                <ul class="small text-muted mb-0 ps-3">
                    <li>La página que estás buscando no existe.</li>
                    <li>La página ha sido movida o eliminada.</li>
                    <li>Has ingresado una URL incorrecta.</li>
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
