@extends('layouts.app')

@section('content')
    <!-- Page Heading -->
    <h1 class="h3 mb-4 text-gray-800">{{ __('404 - Pagina no Encontrada') }}</h1>

   <div class="container-fluid">

                    <!-- 404 Error Text -->
                    <div class="text-center">
                        <div class="error mx-auto" data-text="404">404</div>
                        <p class="lead text-gray-800 mb-5">P&aacute;gina no Encontrada</p>
                        <p class="text-gray-500 mb-0">Parece que la pagina a la que intentas ingresar no existe.</p>
                        <a href="{{ route('home') }}">&larr; Volver al Inicio</a>
                    </div>

                </div>

@endsection
