<?php

declare(strict_types=1);

namespace App\Erp\Contracts;

use Illuminate\Support\Collection;

/**
 * ErpConnectionInterface
 *
 * Contrato que toda implementación de ERP debe cumplir.
 *
 * El sistema central (controladores, servicios) solo conoce esta interfaz.
 * Si mañana cambiamos de Profit Plus a SAP B1 o Siigo, creamos una nueva
 * clase que implemente este contrato y cambiamos ERP_DRIVER en .env.
 * Cero cambios en el código de negocio.
 *
 * Nomenclatura de métodos:
 *   - Retornan Collection<int, array<string, mixed>> para listas.
 *   - Retornan array<string, mixed>|null para registros únicos.
 *   - Nunca lanzan excepciones de BD al controlador — las capturan internamente
 *     y retornan Collection vacía o null según corresponda.
 */
interface ErpConnectionInterface
{
    /*
    |--------------------------------------------------------------------------
    | Diagnóstico de Conexión
    |--------------------------------------------------------------------------
    */

    /**
     * Verifica que la conexión al ERP esté operativa.
     * Usado por el middleware EnsureErpConnection y el health check del panel.
     */
    public function isHealthy(): bool;

    /**
     * Retorna metadata de la conexión activa (driver, host, database, versión ERP).
     * Usado en el panel de administración para diagnóstico.
     *
     * @return array<string, string>
     */
    public function getConnectionInfo(): array;

    /*
    |--------------------------------------------------------------------------
    | Ventas / Facturación
    |--------------------------------------------------------------------------
    */

    /**
     * KPIs principales del dashboard para un período dado.
     *
     * @return array{
     *   monto_facturado: float,
     *   monto_facturado_anterior: float,
     *   cobranzas_mes: float,
     *   clientes_activos: int,
     *   clientes_nuevos: int,
     * }
     */
    public function getDashboardKpis(string $dateFrom, string $dateTo): array;

    /**
     * Resumen de Cuentas por Cobrar segmentadas por antigüedad.
     *
     * @return array{
     *   total_cxc: float,
     *   por_vencer: float,
     *   vencidas_0_15: float,
     *   vencidas_16_30: float,
     *   vencidas_31_mas: float,
     * }
     */
    public function getCuentasPorCobrarSummary(string $dateFrom, string $dateTo): array;

    /**
     * Top N productos más vendidos por unidades y monto en el período.
     *
     * @return Collection<int, array{
     *   codigo: string,
     *   descripcion: string,
     *   marca: string,
     *   unidades: float,
     *   monto: float,
     * }>
     */
    public function getTopProductos(string $dateFrom, string $dateTo, int $limit = 10): Collection;

    /**
     * Ranking de vendedores con monto facturado, cobranzas y porcentaje.
     *
     * @return Collection<int, array{
     *   codigo: string,
     *   nombre: string,
     *   monto_facturado: float,
     *   cobranzas_mes: float,
     *   porcentaje_cobranza: float,
     * }>
     */
    public function getRankingVendedores(string $dateFrom, string $dateTo): Collection;

    /*
    |--------------------------------------------------------------------------
    | Módulo Financiero
    |--------------------------------------------------------------------------
    */

    /**
     * Cálculo de márgenes por artículo para el período dado.
     * El campo de costo se toma de config('app_client.business.cost_field').
     *
     * @return Collection<int, array{
     *   codigo: string,
     *   descripcion: string,
     *   precio_venta: float,
     *   costo: float,
     *   margen_monto: float,
     *   margen_pct: float,
     *   unidades_vendidas: float,
     * }>
     */
    public function getMargenesPorArticulo(string $dateFrom, string $dateTo, string $costField): Collection;

    /**
     * Resumen financiero del período para cálculo de bono mensual.
     *
     * @return array{
     *   total_facturado: float,
     *   costo_total: float,
     *   ganancia_neta: float,
     *   margen_neto_pct: float,
     * }
     */
    public function getResumenFinanciero(string $dateFrom, string $dateTo, string $costField): array;

    /*
    |--------------------------------------------------------------------------
    | Inventario
    |--------------------------------------------------------------------------
    */

    /**
     * Artículos con stock actual por debajo del mínimo configurado.
     *
     * @return Collection<int, array{
     *   codigo: string,
     *   descripcion: string,
     *   stock_actual: float,
     *   stock_minimo: float,
     *   stock_comprometido: float,
     *   deficit: float,
     * }>
     */
    public function getStockCritico(): Collection;

    /**
     * Cruce de órdenes de compra vs entradas reales en inventario.
     *
     * @return Collection<int, array{
     *   numero_orden: string,
     *   proveedor: string,
     *   fecha: string,
     *   articulo_codigo: string,
     *   articulo_descripcion: string,
     *   cantidad_ordenada: float,
     *   cantidad_recibida: float,
     *   diferencia: float,
     *   estado: string,
     * }>
     */
    public function getEntradasVsCompras(string $dateFrom, string $dateTo): Collection;

    /**
     * Salidas de inventario que no corresponden a ventas (ajustes, mermas, etc).
     *
     * @return Collection<int, array{
     *   numero_ajuste: string,
     *   fecha: string,
     *   articulo_codigo: string,
     *   articulo_descripcion: string,
     *   tipo_movimiento: string,
     *   cantidad: float,
     *   costo_estimado: float,
     * }>
     */
    public function getSalidasNoComerciales(string $dateFrom, string $dateTo): Collection;

    /*
    |--------------------------------------------------------------------------
    | Artículos / Catálogo
    |--------------------------------------------------------------------------
    */

    /**
     * Lista paginada de artículos con sus stats de rendimiento.
     *
     * @param  array<string, mixed>  $filters  ['search', 'categoria', 'estado']
     * @return array{
     *   data: Collection,
     *   total: int,
     * }
     */
    public function getArticulos(array $filters = [], int $perPage = 15, int $page = 1): array;

    /**
     * Detalle completo de un artículo por su código.
     *
     * @return array<string, mixed>|null
     */
    public function getArticuloDetalle(string $codigo): ?array;

    /**
     * Evolución mensual de ventas de artículos para gráficos de rendimiento.
     *
     * @param  array<string, string>  $codigos  Lista de códigos de artículo
     * @return Collection<string, array<string, float>>  [codigo => [mes => unidades]]
     */
    public function getArticulosEvolucionMensual(array $codigos, int $year): Collection;
}
