<?php

namespace App\Services\Tablet;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class BarcodeLookupService
{
    protected string $connection;
    protected string $tabla;
    protected array $fieldMap;
    protected array $camposLibres;

    public function __construct()
    {
        // Heredamos la configuración central que ya manejas para tu entorno Profit
        $this->connection   = config('tablet.connection', 'profit');
        $this->tabla        = config('tablet.tabla_articulos', 'art');
        $this->fieldMap     = config('tablet.field_map', []);
        $this->camposLibres = config('tablet.campos_libres', []);
    }

    /**
     * Motor polimórfico de búsqueda: Agnóstico al hardware de entrada.
     * * @param string $search El input capturado (teclado, pistola, cámara)
     * @return array<string, mixed>|null
     */
    public function find(string $search): ?array
    {
        // 1. Normalización estricta: Limpieza de espacios y mayúsculas para Profit Plus
        $search = strtoupper(trim($search));

        if (empty($search)) {
            return null;
        }

        try {
            // 2. Prioridad de búsqueda 1: Código principal (co_art)
            $codigoArticulo = $this->fieldMap['codigo'] ?? 'co_art';
            $articulo = $this->executeQuery($codigoArticulo, $search);

            // 3. Prioridad de búsqueda 2: Si no lo encontró, buscamos por referencia/barras (ref)
            if (! $articulo && !empty($this->fieldMap['barras'])) {
                $articulo = $this->executeQuery($this->fieldMap['barras'], $search);
            }

            return $articulo;

        } catch (Throwable $e) {
            Log::error('[BarcodeLookupService::find] Fallo crítico en la búsqueda', [
                'search' => $search,
                'error'  => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Ejecuta la consulta SQL asegurando el uso del índice en la base de datos
     */
    protected function executeQuery(string $campoSql, string $valor): ?array
    {
        $fm = $this->fieldMap;

        // Construcción dinámica de campos libres activos
        $camposLibresActivos = array_filter($this->camposLibres);
        $selectCL = '';
        foreach (array_keys($camposLibresActivos) as $campo) {
            $selectCL .= ", [{$campo}] AS {$campo}";
        }

        // Búsqueda paramétrica exacta (=) para forzar un 'Index Seek' en SQL Server
        $row = DB::connection($this->connection)->selectOne("
            SELECT
                {$fm['codigo']}       AS codigo,
                {$fm['descripcion']}  AS descripcion,
                {$fm['marca']}        AS linea,
                {$fm['sublinea']}     AS sublinea,
                {$fm['categoria']}    AS categoria,
                {$fm['modelo']}       AS modelo,
                {$fm['color']}        AS color,
                {$fm['precio1']}      AS precio1,
                {$fm['precio2']}      AS precio2,
                {$fm['precio3']}      AS precio3,
                {$fm['precio4']}      AS precio4,
                {$fm['stock_actual']} AS stock_actual,
                {$fm['stock_min']}    AS stock_min,
                {$fm['stock_com']}    AS stock_com,
                {$fm['barras']}       AS codigo_barras,
                {$fm['proveedor']}    AS proveedor_principal
                {$selectCL}
            FROM [{$this->tabla}]
            WHERE {$campoSql} = :valor
        ", ['valor' => $valor]);

        if (! $row) {
            return null;
        }

        return $this->enrichData((array) $row, $camposLibresActivos);
    }

    /**
     * Enriquecimiento de la data: Formateo de especificaciones y cálculo de stock libre
     */
    protected function enrichData(array $arr, array $camposLibresActivos): array
    {
        $specs = [];
        
        foreach ($camposLibresActivos as $campo => $label) {
            $valor = trim((string) ($arr[$campo] ?? ''));
            if ($valor !== '') {
                $specs[] = ['campo' => $campo, 'label' => $label, 'valor' => $valor];
            }
        }

        $arr['specs'] = $specs;
        
        // Cálculo matemático del stock disponible en caliente
        $arr['stock_libre'] = max(0, (float)($arr['stock_actual'] ?? 0) - (float)($arr['stock_com'] ?? 0));

        return $arr;
    }
}