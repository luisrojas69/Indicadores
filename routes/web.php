<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Auth::routes();



Route::fallback(function () {
    return response()->view('errors.404', [], 404);
})->middleware('web'); // Apply the 'web' middleware


Route::middleware(['auth'])->group(function () {
    Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');
    Route::get('/dashboard', [App\Http\Controllers\HomeController::class, 'index']);
    // Rutas de Perfil/Configuración (ejemplo)
    Route::get('profile', function () { return view('profile'); })->name('profile');
    Route::put('/profile', [App\Http\Controllers\ProfileController::class, 'update'])->name('profile.update');
    Route::get('about', function () { return view('about'); })->name('about');
});




// CARGA DEL MÓDULO DE ADMINISTRACIÓN
require __DIR__.'/admin.php';
