<?php

use App\Http\Controllers\Api\AiChatController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Rutas del AI Copilot — api_ai.php
|--------------------------------------------------------------------------
|
| Este archivo se registra en bootstrap/app.php o en RouteServiceProvider
| dentro del grupo 'api' existente:
|
|   // En routes/api.php, agregar al final:
|   require __DIR__.'/api_ai.php';
|
| Middleware aplicado:
|   - auth  → solo usuarios autenticados
|   - throttle:ai   → rate limit personalizado (definido en AppServiceProvider)
|
| Rate limit personalizado (agregar en App\Providers\AppServiceProvider::boot):
|
|   RateLimiter::for('ai', function (Request $request) {
|       return Limit::perMinute(30)->by($request->user()?->id ?: $request->ip());
|   });
|
*/


Route::middleware(['web', 'auth', 'throttle:ai'])
    ->prefix('ai')
    ->name('api.ai.')
    ->group(function () {

        /*
        |----------------------------------------------------------------------
        | POST /api/ai/chat
        |----------------------------------------------------------------------
        | Endpoint principal del copiloto.
        |
        | Body:
        |   message  string  required  Consulta del usuario (max 500 chars)
        |   history  array   optional  Historial de la conversación (max 20 turnos)
        |   context  array   optional  { from: 'Y-m-d', to: 'Y-m-d' }
        |
        | Respuesta:
        |   {
        |     "type":       "text|table|intent_pending|error",
        |     "content":    "...",
        |     "tool_used":  "get_dashboard_kpis|null",
        |     "confidence": 1.0,
        |     "meta":       {}
        |   }
        */
        Route::post('/chat', [AiChatController::class, 'chat'])
            ->name('chat');

        /*
        |----------------------------------------------------------------------
        | GET /api/ai/tools
        |----------------------------------------------------------------------
        | Lista las herramientas disponibles para el usuario autenticado.
        | Útil para que el frontend muestre sugerencias contextuales.
        |
        | Solo disponible para super_admin y gerentes.
        */
        Route::get('/tools', function () {
            $user  = request()->user();
            $tools = \App\Ai\ToolCatalog::forUser($user);

            return response()->json([
                'tools' => array_map(
                    fn($t) => [
                        'name'        => $t['function']['name'],
                        'description' => $t['function']['description'],
                    ],
                    $tools
                ),
            ]);
        })
            //->middleware('can:gerencia.dashboard.ver')
            ->name('tools');
    });
