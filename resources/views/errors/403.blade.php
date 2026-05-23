@extends('layouts.app')

@section('content')
    <!-- Page Heading -->
    <h1 class="h3 mb-4 text-gray-800">{{ __('403 - ESTA ACCION NO ESTÁ AUTORIZADA') }}</h1>

   <div class="container-fluid">

                    <!-- 404 Error Text -->
                    <div class="text-center">
                        <div class="error mx-auto" data-text="403">403</div>
                        <p class="lead text-gray-800 mb-5">Accion no Permitida</p>
                        <p class="text-gray-500 mb-0">Parece que no tienes permiso para acceder a esta Pagina.</p>
                        <a href="{{ route('home') }}">&larr; Volver al Inicio</a>
                    </div>

                </div>

@endsection
