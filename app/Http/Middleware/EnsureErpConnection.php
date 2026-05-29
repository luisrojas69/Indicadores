<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Erp\Contracts\ErpConnectionInterface;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * EnsureErpConnection
 *
 * Middleware que verifica que la conexión al ERP esté operativa antes de
 * permitir el acceso a rutas que dependen de datos del ERP.
 *
 * Comportamiento:
 *   - Si la conexión está OK → deja pasar la request normalmente.
 *   - Si la conexión falla   → redirige a una vista de error amigable
 *     (sin stack trace) o retorna JSON 503 si es una request AJAX/API.
 *
 * Uso en rutas (routes/web.php):
 *   Route::middleware(['auth', 'erp.connection'])->group(function () {
 *       Route::get('/dashboard', [DashboardController::class, 'index']);
 *       // ... todas las rutas que consultan el ERP
 *   });
 *
 * Registro en bootstrap/app.php (Laravel 11+):
 *   ->withMiddleware(function (Middleware $middleware) {
 *       $middleware->alias([
 *           'erp.connection' => \App\Http\Middleware\EnsureErpConnection::class,
 *       ]);
 *   })
 */
class EnsureErpConnection
{
    public function __construct(
        private readonly ErpConnectionInterface $erp
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        if (! $this->erp->isHealthy()) {
            Log::critical('[EnsureErpConnection] Conexión al ERP no disponible.', [
                'ip'   => $request->ip(),
                'url'  => $request->fullUrl(),
                'user' => $request->user()?->id,
            ]);

            // Respuesta JSON para peticiones AJAX / Livewire / API
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'El sistema ERP no está disponible en este momento. Intente más tarde.',
                    'code'    => 'ERP_UNAVAILABLE',
                ], Response::HTTP_SERVICE_UNAVAILABLE);
            }

            // Vista de error amigable para navegación normal
            return response()->view('errors.erp_unavailable', [
                'erp_name' => config('app_client.erp_driver', 'ERP'),
            ], Response::HTTP_SERVICE_UNAVAILABLE);
        }

        return $next($request);
    }
}
