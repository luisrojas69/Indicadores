<?php

declare(strict_types=1);

namespace App\Erp\Profit;

use App\Erp\Contracts\ErpConnectionInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
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
     * BLINDAJE: Lanza excepción si la tabla no está definida.
     */
    private function profitTable(string $key): string
    {
        $tableName = config("profit.tables.{$key}");

        if (empty($tableName)) {
            throw new InvalidArgumentException("CRÍTICO: La llave de tabla '{$key}' no existe en config/profit.php.");
        }

        return $tableName;
    }

    /**
     * Shortcut a la conexión DB de Profit.
     */
    private function con(): \Illuminate\Database\Connection
    {
        return DB::connection($this->connection);
    }

    private function safe(callable $callback, mixed $fallback): mixed
    {
        try {
            return $callback();
        } catch (Throwable $e) {
            Log::error('[ProfitErpConnection] Error en consulta', [
                'message' => $e->getMessage(),
                'file'    => $e->getFile(),
                'line'    => $e->getLine(),
            ]);

            return $fallback;
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Diagnóstico Avanzado de Conexión e Integridad (Súper Health-Check)
    |--------------------------------------------------------------------------
    */

    public function isHealthy(): bool
    {
        $info = $this->getConnectionInfo();
        // Es saludable si hay ping Y no falta ninguna tabla estructural
        return $info['status'] === 'OK' && empty($info['missing_tables']);
    }

    public function getConnectionInfo(): array
    {
        return $this->safe(function () {
            $dbConfig = config("database.connections.{$this->connection}", []);

            // 1. Verificamos Ping
            $this->con()->selectOne('SELECT 1 AS ping');

            // 2. Verificamos integridad de la suite de tablas
            $mappedTables = config('profit.tables', []);
            $tableNames = array_values($mappedTables);

            // Consultamos a SQL Server cuáles de estas tablas existen realmente
            $placeholders = implode(',', array_fill(0, count($tableNames), '?'));
            $existingTables = $this->con()->select("
                SELECT TABLE_NAME
                FROM INFORMATION_SCHEMA.TABLES
                WHERE TABLE_NAME IN ({$placeholders})
            ", $tableNames);

            $existingTableNames = array_column((array) $existingTables, 'TABLE_NAME');

            // SQL Server puede ser case-insensitive, así que normalizamos para comparar
            $existingLower = array_map('strtolower', $existingTableNames);
            $missingTables = [];

            foreach ($mappedTables as $key => $physicalName) {
                if (!in_array(strtolower($physicalName), $existingLower)) {
                    $missingTables[$key] = $physicalName;
                }
            }

            return [
                'driver'         => $dbConfig['driver']   ?? 'sqlsrv',
                'host'           => $dbConfig['host']     ?? '—',
                'database'       => $dbConfig['database'] ?? '—',
                'erp'            => 'Profit Plus 2K8',
                'connection'     => $this->connection,
                'status'         => 'OK',
                'missing_tables' => $missingTables, // Si está vacío, la suite está perfecta
            ];
        }, [
            'driver'         => 'sqlsrv',
            'host'           => '—',
            'database'       => '—',
            'erp'            => 'Profit Plus 2K8',
            'status'         => 'ERROR',
            'missing_tables' => ['unknown' => 'No se pudo conectar a la BD'],
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | Ventas / Facturación
    | Funciones para obtener KPIs, rankings y resúmenes financieros desde las tablas nativas de Profit 2K8.
    | Funcionando
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
            // Mapeo estricto de las tablas nativas de Profit 2K8
            $facturaEnc = $this->profitTable('factura_enc');
            $cobroEnc   = $this->profitTable('cobro_enc');
            $cobroDet   = $this->profitTable('cobro_det');

            // ── 1. CÁLCULO DEL PERÍODO ACTUAL ───────────────────────────────────
            // Monto Facturado Neto y Clientes Activos (desde encabezado de factura)
            $facturacionActual = $this->con()->selectOne("
                SELECT
                    COALESCE(SUM(TOT_NETO), 0) AS monto_facturado,
                    COUNT(DISTINCT CO_CLI)     AS clientes_activos
                FROM [{$facturaEnc}]
                WHERE STATUS = 0
                AND FEC_EMIS >= :from
                AND FEC_EMIS <= :to
            ", ['from' => $dateFrom, 'to' => $dateTo]);

            // Cobranzas del Mes por Abonos Aplicados (Neto cobrado real en los renglones)
            $cobranzaActual = $this->con()->selectOne("
                SELECT COALESCE(SUM(d.NETO), 0) AS cobranzas_mes
                FROM [{$cobroEnc}] e
                JOIN [{$cobroDet}] d ON d.cob_num = e.cob_num
                WHERE e.status = 0
                AND e.fec_cob >= :from
                AND e.fec_cob <= :to
            ", ['from' => $dateFrom, 'to' => $dateTo]);


            // ── 2. CÁLCULO DEL PERÍODO ANTERIOR (Mismo número de días) ─────────
            $days = (int) round((strtotime($dateTo) - strtotime($dateFrom)) / 86400);
            $prevTo   = date('Y-m-d', strtotime($dateFrom . ' -1 day'));
            $prevFrom = date('Y-m-d', strtotime($prevTo . " -{$days} day"));

            $facturacionAnterior = $this->con()->selectOne("
                SELECT COALESCE(SUM(TOT_NETO), 0) AS monto_facturado_anterior
                FROM [{$facturaEnc}]
                WHERE STATUS = 0
                AND FEC_EMIS >= :from
                AND FEC_EMIS <= :to
            ", ['from' => $prevFrom, 'to' => $prevTo]);


            // ── 3. CLIENTES NUEVOS ──────────────────────────────────────────────
            // Evaluamos la primera factura de la historia del cliente en Profit 2K8
            $clientesNuevos = $this->con()->selectOne("
                SELECT COUNT(*) AS clientes_nuevos
                FROM (
                    SELECT CO_CLI, MIN(FEC_EMIS) AS primera_fac
                    FROM [{$facturaEnc}]
                    WHERE STATUS = 0
                    GROUP BY CO_CLI
                ) AS primeras
                WHERE primeras.primera_fac >= :from
                AND primeras.primera_fac <= :to
            ", ['from' => $dateFrom, 'to' => $dateTo]);


            // ── 4. RETORNO DE RESULTADOS CONSOLIDADO ────────────────────────────
            return [
                'monto_facturado'          => (float) ($facturacionActual?->monto_facturado   ?? 0.0),
                'monto_facturado_anterior' => (float) ($facturacionAnterior?->monto_facturado_anterior ?? 0.0),
                'cobranzas_mes'            => (float) ($cobranzaActual?->cobranzas_mes       ?? 0.0),
                'clientes_activos'         => (int)   ($facturacionActual?->clientes_activos   ?? 0),
                'clientes_nuevos'          => (int)   ($clientesNuevos?->clientes_new          ?? $clientesNuevos?->clientes_nuevos ?? 0),
            ];
        }, $default);
    }

    //FUnción para obtener resumen de cuentas por cobrar con clasificación de antigüedad (Funcionando)
    public function getCuentasPorCobrarSummary(string $dateFrom, string $dateTo): array
    {
        $default = [
            'total_cxc'       => 0.0,
            'por_vencer'      => 0.0,
            'vencidas_0_15'   => 0.0,
            'vencidas_16_30'  => 0.0,
            'vencidas_31_mas' => 0.0,
        ];

        return $this->safe(function () use ($dateFrom, $dateTo, $default) {
            $documCc = $this->profitTable('cxc_docum');
            $rengCob = $this->profitTable('cobro_det');
            $cobros  = $this->profitTable('cobro_enc');
            $hoy     = now()->toDateString();

            // 1. Subconsulta para consolidar todos los abonos realizados por documento
            $subAbonos = "
                SELECT
                    rc.tp_doc_cob,
                    rc.doc_num,
                    SUM(rc.neto) AS total_abonado
                FROM [{$rengCob}] rc
                JOIN [{$cobros}] c ON c.cob_num = rc.cob_num
                WHERE c.anulado = 0
                GROUP BY rc.tp_doc_cob, rc.doc_num
            ";

            // 2. Consulta principal simulando la lógica "sxv" del reporte de Profit
            $rows = $this->con()->select("
                SELECT
                    -- Evaluamos el signo: FACT y NDB suman, NCR (Nota de crédito) resta a la cartera
                    CASE WHEN d.TIPO_DOC = 'NCR' THEN -1 ELSE 1 END * (d.MONTO_NET - COALESCE(a.total_abonado, 0)) AS saldo_calculado,
                    d.FEC_VENC
                FROM [{$documCc}] d
                LEFT JOIN ({$subAbonos}) a
                    ON a.tp_doc_cob = d.TIPO_DOC
                AND a.doc_num = d.NRO_DOC
                WHERE d.ANULADO = 0
                AND d.TIPO_DOC IN ('FACT', 'N/DB', 'N/CR', 'ADEL')
                AND d.FEC_EMIS >= :from
                AND d.FEC_EMIS <= :to
            ", ['from' => $dateFrom, 'to' => $dateTo]);

            if (empty($rows)) {
                return $default;
            }

            // 3. Clasificación de la antigüedad en la colección mediante PHP
            $totalCxc     = 0.0;
            $porVencer    = 0.0;
            $vencidas015  = 0.0;
            $vencidas1630 = 0.0;
            $vencidas31M  = 0.0;

            $dateTimeHoy = new \DateTime($hoy);

            foreach ($rows as $r) {
                $saldo = (float) $r->saldo_calculado;

                // Si el saldo calculado ya es cero o menor (documento totalmente pagado), se ignora
                if ($saldo <= 0.02) {
                    continue;
                }

                $totalCxc += $saldo;
                $fecVenc = new \DateTime($r->FEC_VENC);

                if ($fecVenc >= $dateTimeHoy) {
                    $porVencer += $saldo;
                } else {
                    $diasVencidos = $dateTimeHoy->diff($fecVenc)->days;

                    if ($diasVencidos <= 15) {
                        $vencidas015 += $saldo;
                    } elseif ($diasVencidos <= 30) {
                        $vencidas1630 += $saldo;
                    } else {
                        $vencidas31M += $saldo;
                    }
                }
            }

            return [
                'total_cxc'       => round($totalCxc, 2),
                'por_vencer'      => round($porVencer, 2),
                'vencidas_0_15'   => round($vencidas015, 2),
                'vencidas_16_30'  => round($vencidas1630, 2),
                'vencidas_31_mas' => round($vencidas31M, 2),
            ];
        }, $default);
    }

    // Función para obtener el Top 10 productos por margen porcentual, con validación de campo de costo (Funcionando)
    public function getTopProductos(string $dateFrom, string $dateTo, int $limit = 10): Collection
    {
        return $this->safe(function () use ($dateFrom, $dateTo, $limit) {
            $detalle    = $this->profitTable('factura_det');
            $encabezado = $this->profitTable('factura_enc');
            $articulo   = $this->profitTable('articulo');


            $rows = $this->con()->select("
                SELECT TOP (:limit)
                    d.CO_ART AS codigo,
                    a.ART_DES AS descripcion,
                    COALESCE(a.CO_CAT, '') AS marca,
                    SUM(d.total_art) AS unidades,
                    SUM(d.PREC_VTA * d.total_art) AS monto
                FROM [{$detalle}]  d
                JOIN [{$encabezado}] e ON e.FACT_NUM = d.FACT_NUM
                LEFT JOIN [{$articulo}] a ON a.CO_ART = d.CO_ART
                WHERE e.STATUS = 0
                  AND e.FEC_EMIS >= :from
                  AND e.FEC_EMIS <= :to
                GROUP BY
                    d.CO_ART,
                    a.ART_DES,
                    a.CO_CAT
                ORDER BY
                    SUM(d.total_art) DESC;
            ", ['limit' => $limit, 'from' => $dateFrom, 'to' => $dateTo]);

            return collect($rows)->map(fn ($r) => [
                'codigo'      => (string) $r->codigo, // <-- Forzar a String aquí
                'descripcion' => $r->descripcion,
                'marca'       => $r->marca,
                'unidades'    => (float) $r->unidades,
                'monto'       => (float) $r->monto,
            ]);
        }, collect());
    }


    //Probando funcion
    public function getRankingVendedores(string $dateFrom, string $dateTo): Collection
    {
        return $this->safe(function () use ($dateFrom, $dateTo) {
            // Mapeo dinámico y estricto de tablas de Profit 2K8
            $facturaEnc = $this->profitTable('factura_enc');
            $cobroEnc   = $this->profitTable('cobro_enc');
            $cobroDet   = $this->profitTable('cobro_det');
            $vendedor   = $this->profitTable('vendedor');


            // Limpieza de strings para inyección segura en entornos legacy
            $from = preg_replace('/[^0-9\- :]/', '', $dateFrom);
            $to   = preg_replace('/[^0-9\- :]/', '', $dateTo);

            // Consulta optimizada utilizando subconsultas en los LEFT JOIN
            // Se aplica LTRIM/RTRIM para neutralizar el comportamiento del tipo CHAR(6) en Profit
            $query =  "
                SELECT
                    LTRIM(RTRIM(v.CO_VEN)) AS codigo,
                    LTRIM(RTRIM(v.VEN_DES)) AS nombre,
                    COALESCE(f.mto_facturado, 0) AS monto_facturado,
                    COALESCE(c.mto_cobrado, 0) AS cobranzas_mes,
                    CASE
                        WHEN COALESCE(f.mto_facturado, 0) = 0 THEN 0
                        ELSE ROUND((COALESCE(c.mto_cobrado, 0) / f.mto_facturado) * 100, 2)
                    END AS porcentaje_cobranza
                FROM [{$vendedor}] v
                LEFT JOIN (
                    SELECT LTRIM(RTRIM(co_ven)) AS co_ven_clean, COALESCE(SUM(TOT_NETO), 0) AS mto_facturado
                    FROM [{$facturaEnc}]
                    WHERE STATUS = 0
                    AND FEC_EMIS >= '{$from}'
                    AND FEC_EMIS <= '{$to}'
                    GROUP BY co_ven
                ) f ON f.co_ven_clean = LTRIM(RTRIM(v.CO_VEN))
                LEFT JOIN (
                    SELECT LTRIM(RTRIM(d.co_ven)) AS co_ven_clean, COALESCE(SUM(d.NETO), 0) AS mto_cobrado
                    FROM [{$cobroEnc}] e
                    INNER JOIN [{$cobroDet}] d ON d.cob_num = e.cob_num
                    WHERE e.anulado = 0
                    AND e.fec_cob >= '{$from}'
                    AND e.fec_cob <= '{$to}'
                    GROUP BY d.co_ven
                ) c ON c.co_ven_clean = LTRIM(RTRIM(v.CO_VEN))
                WHERE (f.mto_facturado > 0 OR c.mto_cobrado > 0)
                ORDER BY monto_facturado DESC;
            ";

            $rows = $this->con()->select($query);

            // Retorno de colección con llaves normalizadas a minúsculas
            return collect($rows)->map(function ($r) {
                $data = array_change_key_case((array) $r, CASE_LOWER);

                return [
                    'codigo'              => (string) ($data['codigo'] ?? ''),
                    'nombre'              => (string) ($data['nombre'] ?? ''),
                    'monto_facturado'     => (float)  ($data['monto_facturado'] ?? 0.0),
                    'cobranzas_mes'       => (float)  ($data['cobranzas_mes'] ?? 0.0),
                    'porcentaje_cobranza' => (float)  ($data['porcentaje_cobranza'] ?? 0.0),
                ];
            });
        }, collect());
    }


    /*
    |--------------------------------------------------------------------------
    | Módulo Financiero
    |--------------------------------------------------------------------------
    */

    //FUncion para obtener márgenes por artículo, con caché y validación de campo de costo (Funcionando)
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
                    AVG(d.PREC_VTA)                       AS precio_venta,
                    COALESCE(AVG(a.{$costField}), 0)      AS costo,
                    AVG(d.PREC_VTA) - COALESCE(AVG(a.{$costField}), 0) AS margen_monto,
                    CASE
                        WHEN AVG(d.PREC_VTA) = 0 THEN 0
                        ELSE ROUND(
                            (AVG(d.PREC_VTA) - COALESCE(AVG(a.{$costField}), 0))
                            / AVG(d.PREC_VTA) * 100, 2)
                    END                                   AS margen_pct,
                    SUM(d.TOTAL_ART)                       AS unidades_vendidas
                FROM [{$detalle}]  d
                JOIN [{$encabezado}] e ON e.FACT_NUM = d.FACT_NUM
                LEFT JOIN [{$articulo}] a ON a.CO_ART = d.CO_ART
                WHERE e.STATUS = 0
                  AND e.FEC_EMIS >= :from
                  AND e.FEC_EMIS <= :to
                GROUP BY d.CO_ART, a.ART_DES
                ORDER BY unidades_vendidas DESC;
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

    //Función para obtener resumen financiero (total facturado, costo total, ganancia neta, margen neto %) con validación de campo de costo (Funcionando)
    //Funciona en Tinker
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
                    SUM(d.PREC_VTA * d.TOTAL_ART)                              AS total_facturado,
                    SUM(COALESCE(a.{$costField}, 0) * d.TOTAL_ART)             AS costo_total,
                    SUM(d.PREC_VTA * d.TOTAL_ART)
                        - SUM(COALESCE(a.{$costField}, 0) * d.TOTAL_ART)       AS ganancia_neta
                FROM [{$detalle}]  d
                JOIN [{$encabezado}] e ON e.FACT_NUM = d.FACT_NUM
                LEFT JOIN [{$articulo}] a ON a.CO_ART = d.CO_ART
                WHERE e.STATUS = 0
                  AND e.FEC_EMIS >= :from
                  AND e.FEC_EMIS <= :to
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
    | Función para obtener artículos con stock crítico (stock actual <= stock mínimo) con validación de campo de costo (Funcionando)
    | Funciona
    |--------------------------------------------------------------------------
    */

    public function getStockCritico(): Collection
    {
        return $this->safe(function () {
            $articulo    = $this->profitTable('articulo');
            $stock_almac = $this->profitTable('stock_almacen');


            // La query obtiene el stock actual sumando el stock de todos los almacenes para cada artículo, y luego filtra aquellos donde el stock actual es menor o igual al stock mínimo definido en la tabla de artículos.
            // Agregar la Clausula WHERE A.campo5 = 'critico' para filtrar solo los artículos que tengan el campo5 marcado como 'critico'
            // Query Funiconando
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

    //Funcion para obtener comparación entre entradas (recepciones) y compras (órdenes de compra) con validación de campo de costo (Funcionando)
    //Funcionando
    public function getEntradasVsCompras(string $dateFrom, string $dateTo): Collection
    {
        return $this->safe(function () use ($dateFrom, $dateTo) {
            // Ajuste estricto a las tablas de Órdenes de Compra de Profit 2K8
            $ordenEnc  = $this->profitTable('orden_compra_enc');
            $ordenDet  = $this->profitTable('orden_compra_det');
            $articulo  = $this->profitTable('articulo');
            $proveedor = $this->profitTable('proveedor');



            $rows = $this->con()->select("
                SELECT
                    e.FACT_NUM                               AS numero_orden,
                    COALESCE(p.PROV_DES, e.NOMBRE)           AS proveedor,
                    CONVERT(VARCHAR(10), e.FEC_EMIS, 103)    AS fecha,
                    d.CO_ART                                 AS articulo_codigo,
                    COALESCE(a.ART_DES, d.DES_ART)           AS articulo_descripcion,
                    d.TOTAL_ART                              AS cantidad_ordenada,
                    (d.TOTAL_ART - d.PENDIENTE)              AS cantidad_recibida,
                    d.PENDIENTE                              AS diferencia,
                    CASE
                        WHEN d.PENDIENTE = 0 THEN 'Completo'
                        WHEN d.PENDIENTE = d.TOTAL_ART THEN 'Sin entrada'
                        ELSE 'Parcial'
                    END                                      AS estado
                FROM [{$ordenEnc}] e
                JOIN [{$ordenDet}] d ON d.FACT_NUM = e.FACT_NUM
                LEFT JOIN [{$articulo}]  a ON a.CO_ART = d.CO_ART
                LEFT JOIN [{$proveedor}] p ON p.CO_PROV = e.CO_CLI
                WHERE e.STATUS = 0
                AND e.FEC_EMIS >= :from
                AND e.FEC_EMIS <= :to
                ORDER BY e.FEC_EMIS DESC, d.PENDIENTE DESC;
            ", ['from' => $dateFrom, 'to' => $dateTo]);

            return collect($rows)->map(fn ($r) => [
                // Mapeamos asegurando tipos para evitar fallos de strings/enteros en la UI
                'numero_orden'         => (string) $r->numero_orden,
                'proveedor'            => $r->proveedor,
                'fecha'                => $r->fecha,
                'articulo_codigo'      => (string) $r->articulo_codigo,
                'articulo_descripcion' => $r->articulo_descripcion,
                'cantidad_ordenada'    => (float) $r->cantidad_ordenada,
                'cantidad_recibida'    => (float) $r->cantidad_recibida,
                'diferencia'           => (float) $r->diferencia,
                'estado'               => $r->estado,
            ]);
        }, collect());
    }

    //Funcion para obtener salidas no comerciales (ajustes de inventario negativos) con validación de campo de costo (Funcionando)
    public function getSalidasNoComerciales(string $dateFrom, string $dateTo): Collection
    {
        return $this->safe(function () use ($dateFrom, $dateTo) {
            // Mapeo de tablas reales en Profit 2K8
            $ajusteEnc = $this->profitTable('ajuste_inv_enc');
            $ajusteDet = $this->profitTable('ajuste_inv_det');
            $tipoAju   = $this->profitTable('tipo_aju');
            $articulo  = $this->profitTable('articulo');
            $costField = config('app_client.business.cost_field', 'COS_PRO_UN');

            $rows = $this->con()->select("
                SELECT
                    e.AJUE_NUM                               AS numero_ajuste,
                    CONVERT(VARCHAR(10), e.FECHA, 103)       AS fecha,
                    d.CO_ART                                 AS articulo_codigo,
                    COALESCE(a.ART_DES, d.CO_ART)            AS articulo_descripcion,
                    COALESCE(t.DES_TIPO, e.MOTIVO)           AS tipo_movimiento,
                    d.TOTAL_ART                              AS cantidad,
                    (d.TOTAL_ART * COALESCE(a.{$costField}, 0)) AS costo_estimado
                FROM [{$ajusteEnc}] e
                JOIN [{$ajusteDet}] d ON d.AJUE_NUM = e.AJUE_NUM
                JOIN [{$tipoAju}]   t ON t.CO_TIPO = d.TIPO
                LEFT JOIN [{$articulo}] a ON a.CO_ART = d.CO_ART
                WHERE e.STATUS = 0
                AND t.TIPO_TRANS = 'S'                -- Filtramos estrictamente Salidas
                AND e.FECHA >= :from
                AND e.FECHA <= :to
                ORDER BY e.FECHA DESC, costo_estimado DESC;
            ", ['from' => $dateFrom, 'to' => $dateTo]);

            return collect($rows)->map(fn ($r) => [
                'numero_ajuste'        => (string) $r->numero_ajuste,
                'fecha'                => $r->fecha,
                'articulo_codigo'      => (string) $r->articulo_codigo,
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
    // Función para obtener listado de artículos con filtros de búsqueda, categoría, y paginación, incluyendo descripción de categoría y marca (color), con validación de campo de costo (Funcionando)
    // Funciona
    public function getArticulos(array $filters = [], int $perPage = 15, int $page = 1): array
    {
        return $this->safe(function () use ($filters, $perPage, $page) {
            $articulo   = $this->profitTable('articulo');
            $catTable   = $this->profitTable('categoria_articulo');
            $colorTable = $this->profitTable('color_articulo');

            $offset     = ($page - 1) * $perPage;

            // Alias 'a' para la tabla art principal
            $where  = "WHERE 1=1";
            $params = [];

            if (! empty($filters['search'])) {
                $where .= " AND (a.ART_DES LIKE :search OR a.CO_ART LIKE :search2)";
                $params['search']  = '%' . $filters['search'] . '%';
                $params['search2'] = '%' . $filters['search'] . '%';
            }

            if (! empty($filters['categoria'])) {
                // Filtramos por la Categoría (CO_CAT)
                $where .= " AND a.CO_CAT = :categoria";
                $params['categoria'] = $filters['categoria'];
            }

            // Ejecutamos el conteo del total
            $totalResult = $this->con()->selectOne(
                "SELECT COUNT(*) AS total FROM [{$articulo}] a {$where}",
                $params
            );

            $params['perPage'] = $perPage;
            $params['offset']  = $offset;

            // Ejecutamos la consulta de registros paginados con LEFT JOINs
            $rows = $this->con()->select("
                SELECT
                    a.CO_ART     AS codigo,
                    a.ART_DES    AS descripcion,
                    c.CAT_DES    AS categoria,
                    col.DES_COL  AS marca,
                    a.STOCK_ACT  AS stock_actual,
                    a.STOCK_COM  AS stock_comprometido,
                    a.stock_min  AS stock_minimo
                FROM [{$articulo}] a
                LEFT JOIN [{$catTable}] c   ON a.CO_CAT = c.CO_CAT
                LEFT JOIN [{$colorTable}] col ON a.co_color = col.co_col
                {$where}
                ORDER BY a.ART_DES
                OFFSET :offset ROWS FETCH NEXT :perPage ROWS ONLY
            ", $params);

            // ── PURIFICACIÓN DE DATOS ──
            $cleanData = collect($rows)->map(function ($row) {
                return [
                    'codigo'             => trim((string) ($row->codigo ?? '')),
                    'descripcion'        => trim((string) ($row->descripcion ?? '')),
                    'marca'              => trim((string) ($row->marca ?? '')), // Descripcion del Color
                    'categoria'          => trim((string) ($row->categoria ?? '')), // Descripcion de Categoria
                    'stock_actual'       => (float) ($row->stock_actual ?? 0),
                    'stock_comprometido' => (float) ($row->stock_comprometido ?? 0),
                    'stock_minimo'       => (float) ($row->stock_minimo ?? 0),
                ];
            });

            return [
                'data'  => $cleanData,
                'total' => (int) ($totalResult->total ?? 0),
            ];
        }, ['data' => collect(), 'total' => 0]);
    }

    //función para obtener detalle completo de un artículo, incluyendo estadísticas de ventas y última fecha de venta/compra, con validación de campo de costo (Funcionando)
    // Funciona
    public function getArticuloDetalle(string $codigo): ?array
    {
        return $this->safe(function () use ($codigo) {
            // Nombres dinámicos de las tablas del ERP
            $artTable     = $this->profitTable('articulo');
            $catTable     = $this->profitTable('categoria_articulo');
            $linTable     = $this->profitTable('linea_articulo');
            $sublTable    = $this->profitTable('sublinea_articulo');
            $colorTable   = $this->profitTable('color_articulo');
            $provTable    = $this->profitTable('proveedor');
            $facturaTable = $this->profitTable('factura_enc');
            $rengFacTable = $this->profitTable('factura_det');
            $comprasTable = $this->profitTable('factura_compra_enc');
            $rengComTable = $this->profitTable('factura_compra_det');

            // 1. Consulta Principal del Artículo con sus Tablas Relacionadas
            $row = $this->con()->selectOne("
                SELECT
                    a.CO_ART, a.ART_DES, a.CO_CAT, a.CO_LIN, a.co_color, a.modelo,
                    a.STOCK_ACT, a.STOCK_COM, a.stock_min,
                    a.PREC_VTA1, a.PREC_VTA2, a.PREC_VTA3, a.PREC_VTA4,
                    a.COS_PRO_UN, a.ULT_COS_UN, a.COS_PRO_OM, a.ULT_COS_OM,
                    a.alm_prin, a.CO_PROV, a.ubicacion,
                    a.fecha_reg, a.fe_us_mo, a.dis_cen, a.picture,
                    a.campo1, a.campo2, a.campo3, a.campo4, a.campo5,
                    a.CO_SUBL,
                    -- Campos de descripciones relacionadas
                    c.CAT_DES,
                    l.LIN_DES,
                    col.DES_COL,
                    p.PROV_DES,
                    s.SUBL_DES
                FROM [{$artTable}] a
                LEFT JOIN [{$catTable}] c   ON a.CO_CAT = c.CO_CAT
                LEFT JOIN [{$linTable}] l   ON a.CO_LIN = l.CO_LIN
                LEFT JOIN [{$colorTable}] col ON a.co_color = col.co_col
                LEFT JOIN [{$provTable}] p  ON a.CO_PROV = p.CO_PROV
                LEFT JOIN [{$sublTable}] s  ON a.CO_SUBL = s.CO_SUBL AND a.CO_LIN = s.CO_LIN
                WHERE a.CO_ART = :codigo
            ", ['codigo' => $codigo]);

            if (! $row) {
                return null;
            }

            // 2. Consulta de Estadísticas de Ventas (Optimizada y Sargable)
            $stats = $this->con()->selectOne("
                SELECT
                    SUM(CASE
                        WHEN e.fec_emis >= DATEADD(month, DATEDIFF(month, 0, GETDATE()), 0)
                        THEN d.total_art ELSE 0
                    END) AS ventas_mes,

                    SUM(CASE
                        WHEN e.fec_emis >= DATEADD(year, DATEDIFF(year, 0, GETDATE()), 0)
                        THEN d.total_art ELSE 0
                    END) AS ventas_anio,

                    SUM(CASE
                        WHEN e.fec_emis >= DATEADD(month, DATEDIFF(month, 0, GETDATE()) - 1, 0)
                         AND e.fec_emis < DATEADD(month, DATEDIFF(month, 0, GETDATE()), 0)
                        THEN d.total_art ELSE 0
                    END) AS ventas_mes_anterior
                FROM [{$facturaTable}] e
                INNER JOIN [{$rengFacTable}] d ON d.fact_num = e.fact_num
                WHERE d.co_art = :codigo
                  AND e.STATUS = 0
            ", ['codigo' => $codigo]);

            // 3. Cálculo de Última Venta y Última Compra desde renglones operacionales
            $fechasOperacionales = $this->con()->selectOne("
                SELECT
                    (SELECT MAX(v.fec_emis)
                     FROM [{$facturaTable}] v
                     INNER JOIN [{$rengFacTable}] rv ON rv.fact_num = v.fact_num
                     WHERE rv.co_art = :codigo_v AND v.STATUS = 0) AS ultima_venta,

                    (SELECT MAX(c.fec_emis)
                     FROM [{$comprasTable}] c
                     INNER JOIN [{$rengComTable}] rc ON rc.fact_num = c.fact_num
                     WHERE rc.co_art = :codigo_c AND c.STATUS = 0) AS ultima_compra
                ", [
                    'codigo_v' => $codigo,
                    'codigo_c' => $codigo
                ]);

            return [
                'codigo'              => trim($row->CO_ART),
                'descripcion'         => trim($row->ART_DES),
                'marca'               => trim($row->DES_COL ?? ''), // Mostramos la descripción del color/marca
                'almacen'             => trim($row->alm_prin ?? ''),
                'categoria'           => trim($row->CAT_DES ?? ''), // Mostramos la descripción de la categoría
                'linea'               => trim($row->LIN_DES ?? ''),
                'sublinea'            => trim($row->SUBL_DES ?? ''),
                'proveedor_principal' => trim($row->PROV_DES ?? ''), // Mostramos la descripción del proveedor
                'codigo_barras'       => trim($row->CO_ART),
                'ubicacion'           => trim($row->ubicacion ?? ''),

                'ventas_mes'          => (float) ($stats?->ventas_mes ?? 0),
                'ventas_anio'         => (float) ($stats?->ventas_anio ?? 0),
                'ventas_mes_anterior' => (float) ($stats?->ventas_mes_anterior ?? 0),

                'stock_actual'        => (float) $row->STOCK_ACT,
                'stock_comprometido'  => (float) $row->STOCK_COM,
                'stock_minimo'        => (float) $row->stock_min,

                'precios'             => [
                    'venta1' => (float) ($row->PREC_VTA1 ?? 0),
                    'venta2' => (float) ($row->PREC_VTA2 ?? 0),
                    'venta3' => (float) ($row->PREC_VTA3 ?? 0),
                    'venta4' => (float) ($row->PREC_VTA4 ?? 0),
                ],
                'fechas'              => [
                    'fecha_reg'           => $row->fecha_reg ? date('Y-m-d', strtotime($row->fecha_reg)) : null,
                    'ultima_venta'        => $fechasOperacionales?->ultima_venta ? date('Y-m-d', strtotime($fechasOperacionales->ultima_venta)) : null,
                    'ultima_compra'       => $fechasOperacionales?->ultima_compra ? date('Y-m-d', strtotime($fechasOperacionales->ultima_compra)) : null,
                    'ultima_modificacion' => $row->fe_us_mo ? date('Y-m-d', strtotime($row->fe_us_mo)) : null,
                ],
                'costos_desglose'     => [
                    'COS_PRO_UN' => (float) ($row->COS_PRO_UN ?? 0),
                    'ULT_COS_UN' => (float) ($row->ULT_COS_UN ?? 0),
                    'COS_PRO_OM' => (float) ($row->COS_PRO_OM ?? 0),
                    'ULT_COS_OM' => (float) ($row->ULT_COS_OM ?? 0),
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
            $tablaFactura = $this->profitTable('factura_enc');
            $tablaRenglon = $this->profitTable('factura_det');

            $placeholders = implode(',', array_map(fn ($i) => ":cod_{$i}", range(0, count($codigos) - 1)));
            $params = ['year' => $year];
            foreach ($codigos as $i => $codigo) {
                $params["cod_{$i}"] = $codigo;
            }

            $rows = $this->con()->select("
                SELECT
                    LTRIM(RTRIM(d.co_art)) AS codigo,
                    MONTH(e.fec_emis) AS mes,
                    SUM(d.total_art) AS unidades
                FROM [{$tablaFactura}] e
                INNER JOIN [{$tablaRenglon}] d ON e.fact_num = d.fact_num
                WHERE YEAR(e.fec_emis) = :year
                  AND e.STATUS = 0
                  AND d.co_art IN ({$placeholders})
                GROUP BY d.co_art, MONTH(e.fec_emis)
            ", $params);

            // Armamos un array donde la llave es explícitamente un STRING asegurado
            $resultArray = [];
            foreach ($codigos as $c) {
                $resultArray[(string) trim($c)] = array_fill_keys(range(1, 12), 0.0);
            }

            foreach ($rows as $row) {
                $codigoLimpio = (string) trim($row->codigo);
                if (isset($resultArray[$codigoLimpio])) {
                    $resultArray[$codigoLimpio][(int) $row->mes] = (float) $row->unidades;
                }
            }

            return collect($resultArray);
        }, collect());
    }

    /**
     * Obtiene todos los artículos con sus totales de ventas y meses activos para un año específico.
     * Diseñado estrictamente para alimentar RendimientoService.
     */
    public function getRendimientoGlobal(int $year): Collection
    {
        return $this->safe(function () use ($year) {
            $artTable     = $this->profitTable('articulo');
            $catTable     = $this->profitTable('categoria_articulo');
            $facturaTable = $this->profitTable('factura_enc');
            $rengFacTable = $this->profitTable('factura_det');

            $rows = $this->con()->select("
                SELECT
                    LTRIM(RTRIM(a.CO_ART)) AS codigo,
                    LTRIM(RTRIM(a.ART_DES)) AS descripcion,
                    COALESCE(c.CAT_DES, '') AS categoria,
                    COALESCE(v.total_unidades, 0) AS total_unidades,
                    COALESCE(v.meses_activo, 0) AS meses_activo
                FROM [{$artTable}] a
                LEFT JOIN [{$catTable}] c ON c.CO_CAT = a.CO_CAT
                LEFT JOIN (
                    SELECT
                        d.co_art,
                        SUM(d.total_art) AS total_unidades,
                        COUNT(DISTINCT MONTH(e.fec_emis)) AS meses_activo
                    FROM [{$facturaTable}] e
                    INNER JOIN [{$rengFacTable}] d ON d.fact_num = e.fact_num
                    WHERE YEAR(e.fec_emis) = :year
                      AND e.STATUS = 0
                    GROUP BY d.co_art
                ) v ON v.co_art = a.CO_ART
                -- Opcional: Filtrar solo artículos con movimiento o activos
                -- WHERE a.ANULADO = 0
                ORDER BY v.total_unidades DESC
            ", ['year' => $year]);

            return collect($rows)->map(fn ($r) => [
                'codigo'         => (string) $r->codigo,
                'descripcion'    => (string) $r->descripcion,
                'categoria'      => (string) $r->categoria,
                'total_unidades' => (float) $r->total_unidades,
                'meses_activo'   => (int) $r->meses_activo,
            ]);
        }, collect());
    }

}
