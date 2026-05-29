<?php

declare(strict_types=1);

namespace App\Erp\Profit;

use App\Erp\Contracts\ErpConnectionInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * ProfitErpConnection
 *
 * Implementación de ErpConnectionInterface para Profit Plus 2K8 (SQL Server).
 *
 * Todas las queries trabajan sobre la conexión 'profit' (sqlsrv) definida
 * en config/database.php. Esta clase NUNCA escribe en el ERP.
 *
 * Convención de métodos privados:
 *   - profitTable(string $key): string  → resuelve nombre físico de tabla desde config
 *   - con():  DB connection object  → shortcut a DB::connection(config('profit.connection'))
 */
class ProfitErpConnection implements ErpConnectionInterface
{
    /**
     * Nombre de la conexión DB activa para Profit.
     */
    private readonly string $connection;

    public function __construct()
    {
        $this->connection = config('profit.connection', 'profit');
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers Privados
    |--------------------------------------------------------------------------
    */

    /**
     * Retorna el nombre físico de una tabla desde config/profit.php.
     */
    private function profitTable(string $key): string
    {
        return config("profit.tables.{$key}", $key);
    }

    /**
     * Shortcut a la conexión DB de Profit.
     */
    private function con(): \Illuminate\Database\Connection
    {
        return DB::connection($this->connection);
    }

    /**
     * Ejecuta un callable y retorna su resultado.
     * En caso de excepción, loguea y retorna el $fallback.
     *
     * @template T
     * @param  callable(): T  $callback
     * @param  T              $fallback
     * @return T
     */
    private function safe(callable $callback, mixed $fallback): mixed
    {
        try {
            return $callback();
        } catch (Throwable $e) {
            Log::error('[ProfitErpConnection] Error en consulta', [
                'message' => $e->getMessage(),
                'trace'   => $e->getTraceAsString(),
            ]);

            return $fallback;
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Diagnóstico de Conexión
    |--------------------------------------------------------------------------
    */

    public function isHealthy(): bool
    {
        return $this->safe(function () {
            $this->con()->selectOne('SELECT 1 AS ping');
            return true;
        }, false);
    }

    public function getConnectionInfo(): array
    {
        return $this->safe(function () {
            $dbConfig = config("database.connections.{$this->connection}", []);

            return [
                'driver'     => $dbConfig['driver']   ?? 'sqlsrv',
                'host'       => $dbConfig['host']      ?? '—',
                'database'   => $dbConfig['database']  ?? '—',
                'erp'        => 'Profit Plus 2K8',
                'connection' => $this->connection,
                'status'     => $this->isHealthy() ? 'OK' : 'ERROR',
            ];
        }, [
            'driver'   => 'sqlsrv',
            'host'     => '—',
            'database' => '—',
            'erp'      => 'Profit Plus 2K8',
            'status'   => 'ERROR',
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Ventas / Facturación
    |--------------------------------------------------------------------------
    */

    public function getDashboardKpis(string $dateFrom, string $dateTo): array
    {
        $default = [
            'monto_facturado'          => 0.0,
            'monto_facturado_anterior' => 0.0,
            'cobranzas_mes'            => 0.0,
            'clientes_activos'         => 0,
            'clientes_nuevos'          => 0,
        ];

        return $this->safe(function () use ($dateFrom, $dateTo, $default) {
            $factura = $this->profitTable('factura_enc');

            // ── Período actual ──────────────────────────────────────────
            $actual = $this->con()->selectOne("
                SELECT
                    COALESCE(SUM(MTO_TOT), 0)                          AS monto_facturado,
                    COALESCE(SUM(MTO_COBR), 0)                         AS cobranzas_mes,
                    COUNT(DISTINCT CO_CLI)                             AS clientes_activos
                FROM [{$factura}]
                WHERE TIPO_DOC = 'FAC'
                  AND Esta_Doc  = 'P'
                  AND FECHA_FAC >= :from
                  AND FECHA_FAC <= :to
            ", ['from' => $dateFrom, 'to' => $dateTo]);

            // ── Período anterior (mismo número de días hacia atrás) ─────
            $days    = (int) round(
                (strtotime($dateTo) - strtotime($dateFrom)) / 86400
            );
            $prevTo   = date('Y-m-d', strtotime($dateFrom . ' -1 day'));
            $prevFrom = date('Y-m-d', strtotime($prevTo    . " -{$days} day"));

            $anterior = $this->con()->selectOne("
                SELECT COALESCE(SUM(MTO_TOT), 0) AS monto_facturado_anterior
                FROM [{$factura}]
                WHERE TIPO_DOC = 'FAC'
                  AND Esta_Doc  = 'P'
                  AND FECHA_FAC >= :from
                  AND FECHA_FAC <= :to
            ", ['from' => $prevFrom, 'to' => $prevTo]);

            // ── Clientes nuevos (primera factura dentro del período) ────
            $clientesNuevos = $this->con()->selectOne("
                SELECT COUNT(*) AS clientes_nuevos
                FROM (
                    SELECT CO_CLI, MIN(FECHA_FAC) AS primera_fac
                    FROM [{$factura}]
                    WHERE TIPO_DOC = 'FAC' AND Esta_Doc = 'P'
                    GROUP BY CO_CLI
                ) AS primeras
                WHERE primera_fac >= :from AND primera_fac <= :to
            ", ['from' => $dateFrom, 'to' => $dateTo]);

            return [
                'monto_facturado'          => (float) ($actual?->monto_facturado          ?? 0),
                'monto_facturado_anterior' => (float) ($anterior?->monto_facturado_anterior ?? 0),
                'cobranzas_mes'            => (float) ($actual?->cobranzas_mes             ?? 0),
                'clientes_activos'         => (int)   ($actual?->clientes_activos          ?? 0),
                'clientes_nuevos'          => (int)   ($clientesNuevos?->clientes_nuevos   ?? 0),
            ];
        }, $default);
    }

    public function getCuentasPorCobrarSummary(string $dateFrom, string $dateTo): array
    {
        $default = [
            'total_cxc'     => 0.0,
            'por_vencer'    => 0.0,
            'vencidas_0_15' => 0.0,
            'vencidas_16_30'=> 0.0,
            'vencidas_31_mas'=> 0.0,
        ];

        return $this->safe(function () use ($dateFrom, $dateTo, $default) {
            $factura = $this->profitTable('factura_enc');
            $hoy     = now()->toDateString();

            $result = $this->con()->selectOne("
                SELECT
                    COALESCE(SUM(SAL_DOC), 0)                                         AS total_cxc,
                    COALESCE(SUM(CASE WHEN FECHA_VEN >= :hoy  THEN SAL_DOC ELSE 0 END), 0) AS por_vencer,
                    COALESCE(SUM(CASE WHEN FECHA_VEN <  :hoy
                                       AND DATEDIFF(day, FECHA_VEN, :hoy2) BETWEEN 1  AND 15 THEN SAL_DOC ELSE 0 END), 0) AS vencidas_0_15,
                    COALESCE(SUM(CASE WHEN FECHA_VEN <  :hoy3
                                       AND DATEDIFF(day, FECHA_VEN, :hoy4) BETWEEN 16 AND 30 THEN SAL_DOC ELSE 0 END), 0) AS vencidas_16_30,
                    COALESCE(SUM(CASE WHEN FECHA_VEN <  :hoy5
                                       AND DATEDIFF(day, FECHA_VEN, :hoy6) > 30              THEN SAL_DOC ELSE 0 END), 0) AS vencidas_31_mas
                FROM [{$factura}]
                WHERE TIPO_DOC = 'FAC'
                  AND Esta_Doc  = 'P'
                  AND SAL_DOC   > 0
                  AND FECHA_FAC >= :from
                  AND FECHA_FAC <= :to
            ", [
                'hoy'  => $hoy, 'hoy2' => $hoy, 'hoy3' => $hoy,
                'hoy4' => $hoy, 'hoy5' => $hoy, 'hoy6' => $hoy,
                'from' => $dateFrom, 'to' => $dateTo,
            ]);

            return [
                'total_cxc'      => (float) ($result?->total_cxc      ?? 0),
                'por_vencer'     => (float) ($result?->por_vencer      ?? 0),
                'vencidas_0_15'  => (float) ($result?->vencidas_0_15   ?? 0),
                'vencidas_16_30' => (float) ($result?->vencidas_16_30  ?? 0),
                'vencidas_31_mas'=> (float) ($result?->vencidas_31_mas ?? 0),
            ];
        }, $default);
    }

    public function getTopProductos(string $dateFrom, string $dateTo, int $limit = 10): Collection
    {
        return $this->safe(function () use ($dateFrom, $dateTo, $limit) {
            $detalle  = $this->profitTable('factura_det');
            $encabezado = $this->profitTable('factura_enc');
            $articulo = $this->profitTable('articulo');

            $rows = $this->con()->select("
                SELECT TOP (:limit)
                    d.CO_ART                    AS codigo,
                    a.ART_DES                   AS descripcion,
                    COALESCE(a.CO_MARC, '')      AS marca,
                    SUM(d.CANT_FAC)             AS unidades,
                    SUM(d.PREC_FAC * d.CANT_FAC) AS monto
                FROM [{$detalle}]  d
                JOIN [{$encabezado}] e ON e.NUM_FAC = d.NUM_FAC
                LEFT JOIN [{$articulo}] a ON a.CO_ART = d.CO_ART
                WHERE e.TIPO_DOC  = 'FAC'
                  AND e.EstaDoc   = 'P'
                  AND e.FECHA_FAC >= :from
                  AND e.FECHA_FAC <= :to
                GROUP BY d.CO_ART, a.ART_DES, a.CO_MARC
                ORDER BY unidades DESC
            ", ['limit' => $limit, 'from' => $dateFrom, 'to' => $dateTo]);

            return collect($rows)->map(fn ($r) => [
                'codigo'      => $r->codigo,
                'descripcion' => $r->descripcion,
                'marca'       => $r->marca,
                'unidades'    => (float) $r->unidades,
                'monto'       => (float) $r->monto,
            ]);
        }, collect());
    }

    public function getRankingVendedores(string $dateFrom, string $dateTo): Collection
    {
        return $this->safe(function () use ($dateFrom, $dateTo) {
            $factura  = $this->profitTable('factura_enc');
            $vendedor = $this->profitTable('vendedor');

            $rows = $this->con()->select("
                SELECT
                    f.CO_VEN                                AS codigo,
                    COALESCE(v.VEN_DES, f.CO_VEN)           AS nombre,
                    SUM(f.MTO_TOT)                          AS monto_facturado,
                    SUM(f.MTO_COBR)                         AS cobranzas_mes,
                    CASE
                        WHEN SUM(f.MTO_TOT) = 0 THEN 0
                        ELSE ROUND(SUM(f.MTO_COBR) / SUM(f.MTO_TOT) * 100, 2)
                    END                                     AS porcentaje_cobranza
                FROM [{$factura}] f
                LEFT JOIN [{$vendedor}] v ON v.CO_VEN = f.CO_VEN
                WHERE f.TIPO_DOC  = 'FAC'
                  AND f.EstaDoc   = 'P'
                  AND f.FECHA_FAC >= :from
                  AND f.FECHA_FAC <= :to
                  AND f.CO_VEN   IS NOT NULL
                GROUP BY f.CO_VEN, v.VEN_DES
                ORDER BY monto_facturado DESC
            ", ['from' => $dateFrom, 'to' => $dateTo]);

            return collect($rows)->map(fn ($r) => [
                'codigo'              => $r->codigo,
                'nombre'              => $r->nombre,
                'monto_facturado'     => (float) $r->monto_facturado,
                'cobranzas_mes'       => (float) $r->cobranzas_mes,
                'porcentaje_cobranza' => (float) $r->porcentaje_cobranza,
            ]);
        }, collect());
    }

    /*
    |--------------------------------------------------------------------------
    | Módulo Financiero
    |--------------------------------------------------------------------------
    */

    public function getMargenesPorArticulo(string $dateFrom, string $dateTo, string $costField): Collection
    {
        // Whitelist de campos de costo permitidos — nunca interpolamos input sin validar
        $allowedCostFields = ['COS_PRO_UN', 'ULT_COS_UN', 'COS_PRO_OM', 'ULT_COS_OM'];
        if (! in_array($costField, $allowedCostFields, true)) {
            $costField = 'COS_PRO_UN';
        }

        return $this->safe(function () use ($dateFrom, $dateTo, $costField) {
            $detalle    = $this->profitTable('factura_det');
            $encabezado = $this->profitTable('factura_enc');
            $articulo   = $this->profitTable('articulo');

            // $costField ya está validado contra whitelist — interpolación segura
            $rows = $this->con()->select("
                SELECT
                    d.CO_ART                              AS codigo,
                    a.ART_DES                             AS descripcion,
                    AVG(d.PREC_FAC)                       AS precio_venta,
                    COALESCE(AVG(a.{$costField}), 0)      AS costo,
                    AVG(d.PREC_FAC) - COALESCE(AVG(a.{$costField}), 0) AS margen_monto,
                    CASE
                        WHEN AVG(d.PREC_FAC) = 0 THEN 0
                        ELSE ROUND(
                            (AVG(d.PREC_FAC) - COALESCE(AVG(a.{$costField}), 0))
                            / AVG(d.PREC_FAC) * 100, 2)
                    END                                   AS margen_pct,
                    SUM(d.CANT_FAC)                       AS unidades_vendidas
                FROM [{$detalle}]  d
                JOIN [{$encabezado}] e ON e.NUM_FAC = d.NUM_FAC
                LEFT JOIN [{$articulo}] a ON a.CO_ART = d.CO_ART
                WHERE e.TIPO_DOC  = 'FAC'
                  AND e.EstaDoc   = 'P'
                  AND e.FECHA_FAC >= :from
                  AND e.FECHA_FAC <= :to
                GROUP BY d.CO_ART, a.ART_DES
                ORDER BY unidades_vendidas DESC
            ", ['from' => $dateFrom, 'to' => $dateTo]);

            return collect($rows)->map(fn ($r) => [
                'codigo'           => $r->codigo,
                'descripcion'      => $r->descripcion,
                'precio_venta'     => (float) $r->precio_venta,
                'costo'            => (float) $r->costo,
                'margen_monto'     => (float) $r->margen_monto,
                'margen_pct'       => (float) $r->margen_pct,
                'unidades_vendidas'=> (float) $r->unidades_vendidas,
            ]);
        }, collect());
    }

    public function getResumenFinanciero(string $dateFrom, string $dateTo, string $costField): array
    {
        $allowedCostFields = ['COS_PRO_UN', 'ULT_COS_UN', 'COS_PRO_OM', 'ULT_COS_OM'];
        if (! in_array($costField, $allowedCostFields, true)) {
            $costField = 'COS_PRO_UN';
        }

        $default = [
            'total_facturado' => 0.0,
            'costo_total'     => 0.0,
            'ganancia_neta'   => 0.0,
            'margen_neto_pct' => 0.0,
        ];

        return $this->safe(function () use ($dateFrom, $dateTo, $costField, $default) {
            $detalle    = $this->profitTable('factura_det');
            $encabezado = $this->profitTable('factura_enc');
            $articulo   = $this->profitTable('articulo');

            $result = $this->con()->selectOne("
                SELECT
                    SUM(d.PREC_FAC * d.CANT_FAC)                              AS total_facturado,
                    SUM(COALESCE(a.{$costField}, 0) * d.CANT_FAC)             AS costo_total,
                    SUM(d.PREC_FAC * d.CANT_FAC)
                        - SUM(COALESCE(a.{$costField}, 0) * d.CANT_FAC)       AS ganancia_neta
                FROM [{$detalle}]  d
                JOIN [{$encabezado}] e ON e.NUM_FAC = d.NUM_FAC
                LEFT JOIN [{$articulo}] a ON a.CO_ART = d.CO_ART
                WHERE e.TIPO_DOC  = 'FAC'
                  AND e.EstaDoc   = 'P'
                  AND e.FECHA_FAC >= :from
                  AND e.FECHA_FAC <= :to
            ", ['from' => $dateFrom, 'to' => $dateTo]);

            $totalFacturado = (float) ($result?->total_facturado ?? 0);
            $costoTotal     = (float) ($result?->costo_total     ?? 0);
            $gananciaNeta   = (float) ($result?->ganancia_neta   ?? 0);

            return [
                'total_facturado' => $totalFacturado,
                'costo_total'     => $costoTotal,
                'ganancia_neta'   => $gananciaNeta,
                'margen_neto_pct' => $totalFacturado > 0
                    ? round($gananciaNeta / $totalFacturado * 100, 2)
                    : 0.0,
            ];
        }, $default);
    }

    /*
    |--------------------------------------------------------------------------
    | Inventario
    |--------------------------------------------------------------------------
    */

    public function getStockCritico(): Collection
    {
           /*
                SELECT
                    CO_ART                                   AS codigo,
                    ART_DES                                  AS descripcion,
                    STOCK_ACT                                AS stock_actual,
                    sto_min                                  AS stock_minimo,
                    STOCK_COM                                AS stock_comprometido,
                    sto_min - STOCK_ACT                      AS deficit
                FROM [{$articulo}]
                WHERE STOCK_ACT <= sto_min
                  AND sto_min    > 0
                ORDER BY deficit DESC
           */

        return $this->safe(function () {
            $articulo = $this->profitTable('art');
            $stock_almac = $this->profitTable('st_almac');



            $rows = $this->con()->select("
                SELECT
                    A.co_art AS codigo,
                    A.art_des AS descripcion,
                    A.stock_min AS stock_minimo,
                    A.stock_com AS stock_comprometido,
                    SUM(S.stock_act) AS stock_actual,
                    (A.stock_min -  SUM(S.stock_act)) AS deficit
                FROM [{$articulo}] AS A
                INNER JOIN [{$stock_almac}] AS S
                    ON A.co_art = S.co_art
                WHERE A.campo5 = 'critico'
                GROUP BY
                    A.co_art,
                    A.art_des,
                    A.stock_min,
                    A.stock_com
                HAVING SUM(S.stock_act) <= A.stock_min;
            ");


            return collect($rows)->map(fn ($r) => [
                'codigo'             => $r->codigo,
                'descripcion'        => $r->descripcion,
                'stock_actual'       => (float) $r->stock_actual,
                'stock_minimo'       => (float) $r->stock_minimo,
                'stock_comprometido' => (float) $r->stock_comprometido,
                'deficit'            => (float) $r->deficit,
            ]);
        }, collect());
    }

    public function getEntradasVsCompras(string $dateFrom, string $dateTo): Collection
    {
        return $this->safe(function () use ($dateFrom, $dateTo) {
            $compraEnc = $this->profitTable('compra_enc');
            $compraDet = $this->profitTable('compra_det');
            $stock     = $this->profitTable('stock_almacen');
            $articulo  = $this->profitTable('articulo');
            $proveedor = $this->profitTable('proveedor');

            $rows = $this->con()->select("
                SELECT
                    e.NUM_ORDE                              AS numero_orden,
                    COALESCE(p.PRO_DES, e.CO_PRO)          AS proveedor,
                    CONVERT(VARCHAR(10), e.FECHA_ORDE, 103) AS fecha,
                    d.CO_ART                               AS articulo_codigo,
                    COALESCE(a.ART_DES, d.CO_ART)          AS articulo_descripcion,
                    d.CANT_ORDE                             AS cantidad_ordenada,
                    COALESCE(d.CANT_REC, 0)                AS cantidad_recibida,
                    d.CANT_ORDE - COALESCE(d.CANT_REC, 0)  AS diferencia,
                    CASE
                        WHEN d.CANT_ORDE = COALESCE(d.CANT_REC, 0) THEN 'Completo'
                        WHEN COALESCE(d.CANT_REC, 0) = 0           THEN 'Sin entrada'
                        ELSE 'Parcial'
                    END                                    AS estado
                FROM [{$compraEnc}] e
                JOIN [{$compraDet}] d ON d.NUM_ORDE = e.NUM_ORDE
                LEFT JOIN [{$articulo}]  a ON a.CO_ART  = d.CO_ART
                LEFT JOIN [{$proveedor}] p ON p.CO_PRO  = e.CO_PRO
                WHERE e.FECHA_ORDE >= :from
                  AND e.FECHA_ORDE <= :to
                ORDER BY e.FECHA_ORDE DESC, diferencia DESC
            ", ['from' => $dateFrom, 'to' => $dateTo]);

            return collect($rows)->map(fn ($r) => [
                'numero_orden'          => $r->numero_orden,
                'proveedor'             => $r->proveedor,
                'fecha'                 => $r->fecha,
                'articulo_codigo'       => $r->articulo_codigo,
                'articulo_descripcion'  => $r->articulo_descripcion,
                'cantidad_ordenada'     => (float) $r->cantidad_ordenada,
                'cantidad_recibida'     => (float) $r->cantidad_recibida,
                'diferencia'            => (float) $r->diferencia,
                'estado'                => $r->estado,
            ]);
        }, collect());
    }

    public function getSalidasNoComerciales(string $dateFrom, string $dateTo): Collection
    {
        return $this->safe(function () use ($dateFrom, $dateTo) {
            $ajusteEnc = $this->profitTable('ajuste_inv_enc');
            $ajusteDet = $this->profitTable('ajuste_inv_det');
            $articulo  = $this->profitTable('articulo');
            $costField = config('app_client.business.cost_field', 'COS_PRO_UN');

            $rows = $this->con()->select("
                SELECT
                    e.NUM_AJ                               AS numero_ajuste,
                    CONVERT(VARCHAR(10), e.FECHA_AJ, 103)  AS fecha,
                    d.CO_ART                               AS articulo_codigo,
                    COALESCE(a.ART_DES, d.CO_ART)          AS articulo_descripcion,
                    COALESCE(e.CONCEP_AJ, 'Sin descripción') AS tipo_movimiento,
                    d.CANT_AJ                              AS cantidad,
                    ABS(d.CANT_AJ) * COALESCE(a.{$costField}, 0) AS costo_estimado
                FROM [{$ajusteEnc}] e
                JOIN [{$ajusteDet}] d ON d.NUM_AJ = e.NUM_AJ
                LEFT JOIN [{$articulo}] a ON a.CO_ART = d.CO_ART
                WHERE e.FECHA_AJ >= :from
                  AND e.FECHA_AJ <= :to
                  AND d.CANT_AJ  <  0
                ORDER BY e.FECHA_AJ DESC, costo_estimado DESC
            ", ['from' => $dateFrom, 'to' => $dateTo]);

            return collect($rows)->map(fn ($r) => [
                'numero_ajuste'        => $r->numero_ajuste,
                'fecha'                => $r->fecha,
                'articulo_codigo'      => $r->articulo_codigo,
                'articulo_descripcion' => $r->articulo_descripcion,
                'tipo_movimiento'      => $r->tipo_movimiento,
                'cantidad'             => (float) $r->cantidad,
                'costo_estimado'       => (float) $r->costo_estimado,
            ]);
        }, collect());
    }

    /*
    |--------------------------------------------------------------------------
    | Artículos / Catálogo
    |--------------------------------------------------------------------------
    */

    public function getArticulos(array $filters = [], int $perPage = 15, int $page = 1): array
    {
        return $this->safe(function () use ($filters, $perPage, $page) {
            $articulo = $this->profitTable('art');
            $offset   = ($page - 1) * $perPage;

            $where  = "WHERE 1=1";
            $params = [];

            if (! empty($filters['search'])) {
                $where .= " AND (ART_DES LIKE :search OR CO_ART LIKE :search2)";
                $params['search']  = '%' . $filters['search'] . '%';
                $params['search2'] = '%' . $filters['search'] . '%';
            }

            if (! empty($filters['categoria'])) {
                $where .= " AND CO_LIN = :categoria";
                $params['categoria'] = $filters['categoria'];
            }

            // Ejecutamos el conteo del total
            $totalResult = $this->con()->selectOne(
                "SELECT COUNT(*) AS total FROM [{$articulo}] {$where}",
                $params
            );

            $params['perPage'] = $perPage;
            $params['offset']  = $offset;

            // Ejecutamos la consulta de registros paginados
            $rows = $this->con()->select("
                SELECT
                    CO_ART     AS codigo,
                    ART_DES    AS descripcion,
                    CO_CAT     AS marca,
                    CO_LIN     AS categoria,
                    STOCK_ACT  AS stock_actual,
                    STOCK_COM  AS stock_comprometido,
                    stoCK_min  AS stock_minimo
                FROM [{$articulo}]
                {$where}
                ORDER BY ART_DES
                OFFSET :offset ROWS FETCH NEXT :perPage ROWS ONLY
            ", $params);

            // ── PURIFICACIÓN DE DATOS (Soluciona el error de stdClass) ──
            // Mapeamos la colección para transformar cada objeto stdClass en un array asociativo limpio.
            $cleanData = collect($rows)->map(function ($row) {
                return [
                    'codigo'             => trim((string) ($row->codigo ?? '')),
                    'descripcion'        => trim((string) ($row->descripcion ?? '')),
                    'marca'              => trim((string) ($row->marca ?? '')),
                    'categoria'          => trim((string) ($row->categoria ?? '')),
                    'stock_actual'       => (float) ($row->stock_actual ?? 0),
                    'stock_comprometido' => (float) ($row->stock_comprometido ?? 0),
                    'stock_minimo'       => (float) ($row->stock_minimo ?? 0),
                ];
            });

            return [
                'data'  => $cleanData, // Retorna la colección purificada de arrays planos
                'total' => (int) ($totalResult->total ?? 0),
            ];
        }, ['data' => collect(), 'total' => 0]);
    }

    public function getArticulosOLD(array $filters = [], int $perPage = 15, int $page = 1): array
    {
        return $this->safe(function () use ($filters, $perPage, $page) {
            $articulo = $this->profitTable('art');
            $offset   = ($page - 1) * $perPage;

            $where  = "WHERE 1=1";
            $params = [];

            if (! empty($filters['search'])) {
                $where .= " AND (ART_DES LIKE :search OR CO_ART LIKE :search2)";
                $params['search']  = '%' . $filters['search'] . '%';
                $params['search2'] = '%' . $filters['search'] . '%';
            }

            if (! empty($filters['categoria'])) {
                $where .= " AND CO_LIN = :categoria";
                $params['categoria'] = $filters['categoria'];
            }

            $total = $this->con()->selectOne(
                "SELECT COUNT(*) AS total FROM [{$articulo}] {$where}",
                $params
            );

            $params['perPage'] = $perPage;
            $params['offset']  = $offset;

            $rows = $this->con()->select("
                SELECT
                    CO_ART     AS codigo,
                    ART_DES    AS descripcion,
                    CO_CAT    AS marca,
                    CO_LIN     AS categoria,
                    STOCK_ACT  AS stock_actual,
                    STOCK_COM  AS stock_comprometido,
                    stoCK_min    AS stock_minimo
                FROM [{$articulo}]
                {$where}
                ORDER BY ART_DES
                OFFSET :offset ROWS FETCH NEXT :perPage ROWS ONLY
            ", $params);

            return [
                'data'  => collect($rows),
                'total' => (int) ($total?->total ?? 0),
            ];
        }, ['data' => collect(), 'total' => 0]);
    }

    public function getArticuloDetalle(string $codigo): ?array
    {
        return $this->safe(function () use ($codigo) {
            $articulo  = $this->profitTable('art');
            $facDetalle = $this->profitTable('factura_det');
            $facEnc     = $this->profitTable('factura_enc');
            $costField  = config('app_client.business.cost_field', 'COS_PRO_UN');

            $row = $this->con()->selectOne("
                SELECT
                    CO_ART, ART_DES, CO_CAT, CO_LIN,
                    STOCK_ACT, STOCK_COM, stock_min,
                    PREC_VTA1, PREC_VTA2, PREC_VTA3, PREC_VTA4,
                    COS_PRO_UN, ULT_COS_UN, COS_PRO_OM, ULT_COS_OM,
                    alm_prin, CO_PROV, ubicacion,
                    fec_ult_co , fec_ult_om, fecha_reg
                FROM art
                WHERE CO_ART = '31164255'
            ", ['codigo' => '31164255']);

            if (! $row) {
                return null;
            }

            // Stats de ventas
            $stats = $this->con()->selectOne("
                SELECT
                    SUM(CASE WHEN MONTH(e.FECHA_FAC) = MONTH(GETDATE())
                              AND YEAR(e.FECHA_FAC)  = YEAR(GETDATE())
                         THEN d.CANT_FAC ELSE 0 END)  AS ventas_mes,
                    SUM(CASE WHEN YEAR(e.FECHA_FAC)  = YEAR(GETDATE())
                         THEN d.CANT_FAC ELSE 0 END)  AS ventas_anio,
                    SUM(CASE WHEN MONTH(e.FECHA_FAC) = MONTH(DATEADD(MONTH,-1,GETDATE()))
                              AND YEAR(e.FECHA_FAC)  = YEAR(DATEADD(MONTH,-1,GETDATE()))
                         THEN d.CANT_FAC ELSE 0 END)  AS ventas_mes_anterior
                FROM [{$facDetalle}] d
                JOIN [{$facEnc}] e ON e.NUM_FAC = d.NUM_FAC
                WHERE d.CO_ART = :codigo
                  AND e.TIPO_DOC = 'FAC'
                  AND e.EstaDoc  = 'P'
            ", ['codigo' => $codigo]);

            $precioVenta = (float) ($row->PREC1 ?? 0);
            $costo       = (float) ($row->costo_activo ?? 0);

            return [
                'codigo'              => $row->CO_ART,
                'descripcion'         => $row->ART_DES,
                'marca'               => $row->CO_MARC ?? '',
                'categoria'           => $row->CO_LIN  ?? '',
                'stock_actual'        => (float) $row->STOCK_ACT,
                'stock_comprometido'  => (float) $row->STOCK_COM,
                'stock_minimo'        => (float) $row->sto_min,
                'precios'             => [
                    'venta1' => (float) ($row->PREC1 ?? 0),
                    'venta2' => (float) ($row->PREC2 ?? 0),
                    'venta3' => (float) ($row->PREC3 ?? 0),
                    'venta4' => (float) ($row->PREC4 ?? 0),
                ],
                'costo_activo'        => $costo,
                'costos_desglose'     => [
                    'COS_PRO_UN' => (float) ($row->COS_PRO_UN ?? 0),
                    'ULT_COS_UN' => (float) ($row->ULT_COS_UN ?? 0),
                    'COS_PRO_OM' => (float) ($row->COS_PRO_OM ?? 0),
                    'ULT_COS_OM' => (float) ($row->ULT_COS_OM ?? 0),
                ],
                'margen_pct'          => $precioVenta > 0
                    ? round(($precioVenta - $costo) / $precioVenta * 100, 2)
                    : 0.0,
                'almacen'             => $row->CO_ALM      ?? '',
                'proveedor_principal' => $row->CO_PROV_PRIN ?? '',
                'codigo_barras'       => $row->CO_BARR      ?? '',
                'ventas_mes'          => (float) ($stats?->ventas_mes          ?? 0),
                'ventas_anio'         => (float) ($stats?->ventas_anio         ?? 0),
                'ventas_mes_anterior' => (float) ($stats?->ventas_mes_anterior ?? 0),
                'fechas'              => [
                    'ultima_compra'       => $row->FECHA_U_COMP  ?? null,
                    'ultima_venta'        => $row->FECHA_U_VENTA ?? null,
                    'ultima_modificacion' => $row->FECHA_MODIF   ?? null,
                ],
            ];
        }, null);
    }

    public function getArticulosEvolucionMensual(array $codigos, int $year): Collection
    {
        if (empty($codigos)) {
            return collect();
        }

        return $this->safe(function () use ($codigos, $year) {
            $detalle    = $this->profitTable('factura_det');
            $encabezado = $this->profitTable('factura_enc');

            // Construir placeholders seguros para la cláusula IN
            $placeholders = implode(',', array_map(
                fn ($i) => ":cod_{$i}",
                range(0, count($codigos) - 1)
            ));

            $params = ['year' => $year];
            foreach ($codigos as $i => $codigo) {
                $params["cod_{$i}"] = $codigo;
            }

            $rows = $this->con()->select("
                SELECT
                    d.CO_ART                  AS codigo,
                    MONTH(e.FECHA_FAC)         AS mes,
                    SUM(d.CANT_FAC)            AS unidades
                FROM [{$detalle}]  d
                JOIN [{$encabezado}] e ON e.NUM_FAC = d.NUM_FAC
                WHERE e.TIPO_DOC  = 'FAC'
                  AND e.EstaDoc   = 'P'
                  AND YEAR(e.FECHA_FAC) = :year
                  AND d.CO_ART IN ({$placeholders})
                GROUP BY d.CO_ART, MONTH(e.FECHA_FAC)
                ORDER BY d.CO_ART, mes
            ", $params);

            // Transformar a [codigo => [1..12 => unidades]]
            $result = collect($codigos)->mapWithKeys(fn ($c) => [
                $c => array_fill_keys(range(1, 12), 0.0)
            ]);

            foreach ($rows as $row) {
                if (isset($result[$row->codigo])) {
                    $result[$row->codigo][(int) $row->mes] = (float) $row->unidades;
                }
            }

            return $result;
        }, collect());
    }
}
