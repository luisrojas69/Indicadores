<?php

declare(strict_types=1);

namespace App\Providers;

use App\Erp\Contracts\ErpConnectionInterface;
use App\Erp\Profit\ProfitErpConnection;
use Illuminate\Support\ServiceProvider;
use InvalidArgumentException;


/**
 * ErpServiceProvider
 *
 * Responsabilidad única: leer ERP_DRIVER desde config/app_client.php
 * y bindear la implementación concreta correcta al contrato ErpConnectionInterface.
 *
 * ¿Cómo agregar soporte para un nuevo ERP?
 *   1. Crear app/Erp/NuevoErp/NuevoErpConnection.php implementando ErpConnectionInterface.
 *   2. Agregar el case en el switch de abajo.
 *   3. Cambiar ERP_DRIVER en el .env del cliente nuevo.
 *   Fin. Cero cambios en controladores, servicios ni vistas.
 *
 * Registro en config/app.php → 'providers' array:
 *   App\Providers\ErpServiceProvider::class,
 */
class ErpServiceProvider extends ServiceProvider
{
    /**
     * Registra los bindings en el contenedor de servicios.
     */
    public function register(): void
    {
        $this->app->singleton(
            ErpConnectionInterface::class,
            function () {
                $driver = config('app_client.erp_driver', 'profit_plus_2k8');

                return match ($driver) {
                    'profit_plus_2k8' => new ProfitErpConnection(),

                    // ── Futuros drivers ────────────────────────────────────
                    // 'sap_b1'   => new \App\Erp\SapB1\SapB1ErpConnection(),
                    // 'siigo'    => new \App\Erp\Siigo\SiigoErpConnection(),
                    // 'adminpaq' => new \App\Erp\Adminpaq\AdminpaqErpConnection(),

                    default => throw new InvalidArgumentException(
                        "ERP driver [{$driver}] no está registrado en ErpServiceProvider. " .
                        "Opciones válidas: profit_plus_2k8"
                    ),
                };
            }
        );
    }

    /**
     * Boot — aquí irían macros, observers o configuraciones
     * que dependen de que todos los providers ya estén registrados.
     */
    public function boot(): void
    {
        //
    }
}
