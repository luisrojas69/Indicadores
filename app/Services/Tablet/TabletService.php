<?php

declare(strict_types=1);

namespace App\Services\Tablet;

use App\Models\PreOrder;
use App\Models\PreOrderItem;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * TabletService
 *
 * Toda la lógica del módulo tablet:
 *   1. Catálogo de artículos desde Profit (con campos reales)
 *   2. Categorías y filtros disponibles
 *   3. Detalle de artículo con specs técnicas (campos libres)
 *   4. Gestión de pre-pedidos (crear, agregar ítems, enviar a caja)
 *
 * IMPORTANTE: Las queries aquí usan la tabla 'art' según tu query real.
 * El nombre de tabla y campos se leen de config/tablet.php.
 */
class TabletService
{
    private string $tabla;
    private array  $fieldMap;
    private array  $camposLibres;
    private string $connection;

    public function __construct()
    {
        $this->tabla        = config('tablet.tabla_articulo', 'art');
        $this->fieldMap     = config('tablet.field_map', []);
        $this->camposLibres = config('tablet.campos_libres', []);
        $this->connection   = config('profit.connection', 'profit');
    }

    /*
    |--------------------------------------------------------------------------
    | Catálogo de Artículos
    |--------------------------------------------------------------------------
    */

    /**
     * Obtiene artículos paginados con todos los campos del catálogo.
     * Construye la SELECT dinámicamente desde config/tablet.php.
     *
     * @param  array{search?: string, categoria?: string, marca?: string, modelo?: string}  $filters
     * @return array{data: array, total: int, categorias: array, marcas: array}
     */
    public function getCatalogo(array $filters = [], int $perPage = 24, int $page = 1): array
    {
        try {
            $offset = ($page - 1) * $perPage;
            $fm     = $this->fieldMap;

            // ── SELECT con campos fijos + campos libres activos ────────────
            $camposLibresActivos = array_filter($this->camposLibres); // quita los null
            $selectCamposLibres  = '';
            foreach (array_keys($camposLibresActivos) as $campo) {
                $selectCamposLibres .= ", [{$campo}] AS {$campo}";
            }

            $select = "
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
                {$selectCamposLibres}
            ";

            // ── WHERE dinámico ────────────────────────────────────────────
            $where  = "WHERE {$fm['stock_actual']} > 0"; // Solo artículos con stock
            $params = [];

            if (! empty($filters['search'])) {
                $where .= " AND ({$fm['descripcion']} LIKE :search
                             OR  {$fm['codigo']} LIKE :search2
                             OR  {$fm['marca']} LIKE :search3
                             OR  {$fm['barras']} LIKE :searchExact
                             OR  {$fm['modelo']} LIKE :search5)";
                $term = '%' . $filters['search'] . '%';
                $params += ['search' => $term, 'search2' => $term, 'search3' => $term, 'searchExact' => $term , 'search5' => $term];
            }

            if (! empty($filters['categoria'])) {
                $where .= " AND {$fm['categoria']} = :categoria";
                $params['categoria'] = $filters['categoria'];
            }

            if (! empty($filters['marca'])) {
                $where .= " AND {$fm['marca']} = :marca";
                $params['marca'] = $filters['marca'];
            }

            if (! empty($filters['modelo'])) {
                $where .= " AND {$fm['modelo']} = :modelo";
                $params['modelo'] = $filters['modelo'];
            }

            // ── Count total ───────────────────────────────────────────────
            $totalRow = DB::connection($this->connection)->selectOne(
                "SELECT COUNT(*) AS total FROM [{$this->tabla}] {$where}",
                $params
            );
            $total = (int) ($totalRow?->total ?? 0);

            // ── Data paginada ─────────────────────────────────────────────
            $rows = DB::connection($this->connection)->select("
                SELECT {$select}
                FROM [{$this->tabla}]
                {$where}
                ORDER BY {$fm['descripcion']}
                OFFSET :offset ROWS FETCH NEXT :perPage ROWS ONLY
            ", array_merge($params, ['offset' => $offset, 'perPage' => $perPage]));

            return [
                'data'    => array_map(fn ($r) => (array) $r, $rows),
                'total'   => $total,
            ];

        } catch (Throwable $e) {
            Log::error('[TabletService::getCatalogo]', ['error' => $e->getMessage()]);
            return ['data' => [], 'total' => 0];
        }
    }

    /**
     * Detalle completo de un artículo (para la ficha expandida del catálogo).
     *
     * @return array<string, mixed>|null
     */
    public function getArticuloDetalle(string $codigo): ?array
    {
        try {
            $fm = $this->fieldMap;

            // Campos libres activos
            $camposLibresActivos = array_filter($this->camposLibres);
            $selectCL = '';
            foreach (array_keys($camposLibresActivos) as $campo) {
                $selectCL .= ", [{$campo}] AS {$campo}";
            }

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
                WHERE {$fm['codigo']} = :codigo
            ", ['codigo' => $codigo]);

            if (! $row) return null;

            $arr = (array) $row;

            // Enriquecer con labels de campos libres
            $specs = [];
            foreach ($camposLibresActivos as $campo => $label) {
                $valor = trim((string) ($arr[$campo] ?? ''));
                if ($valor !== '') {
                    $specs[] = ['campo' => $campo, 'label' => $label, 'valor' => $valor];
                }
            }

            $arr['specs'] = $specs;
            $arr['stock_libre'] = max(0, (float)($arr['stock_actual'] ?? 0) - (float)($arr['stock_com'] ?? 0));

            return $arr;

        } catch (Throwable $e) {
            Log::error('[TabletService::getArticuloDetalle]', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Obtiene las categorías únicas disponibles (para los filtros del catálogo).
     */
    public function getCategorias(): array
    {
        // Si están hardcodeadas en config, usarlas directamente
        $configCats = config('tablet.categorias', []);
        if (! empty($configCats)) {
            return $configCats;
        }

        // Si no, cargar dinámicamente desde Profit
        try {
            $fm   = $this->fieldMap;
            $rows = DB::connection($this->connection)->select("
                SELECT DISTINCT [{$fm['categoria']}] AS categoria
                FROM [{$this->tabla}]
                WHERE [{$fm['categoria']}] IS NOT NULL
                  AND [{$fm['categoria']}] <> ''
                  AND {$fm['stock_actual']} > 0
                ORDER BY categoria
            ");

            return collect($rows)
                ->pluck('categoria', 'categoria')
                ->filter()
                ->toArray();

        } catch (Throwable) {
            return [];
        }
    }

    /**
     * Obtiene las marcas (líneas) únicas disponibles.
     */
    public function getMarcas(): array
    {
        try {
            $fm   = $this->fieldMap;
            $rows = DB::connection($this->connection)->select("
                SELECT DISTINCT [{$fm['marca']}] AS marca
                FROM [{$this->tabla}]
                WHERE [{$fm['marca']}] IS NOT NULL
                  AND [{$fm['marca']}] <> ''
                  AND {$fm['stock_actual']} > 0
                ORDER BY marca
            ");

            return collect($rows)->pluck('marca')->filter()->toArray();

        } catch (Throwable) {
            return [];
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Gestión de Pre-Pedidos
    |--------------------------------------------------------------------------
    */

    /**
     * Obtiene o crea el borrador activo del vendedor.
     */
    public function getBorradorActivo(int $vendedorId): PreOrder
    {
        return PreOrder::firstOrCreate(
            ['vendedor_id' => $vendedorId, 'estado' => PreOrder::ESTADO_BORRADOR],
            ['cliente_nombre' => '', 'total_items' => 0, 'subtotal' => 0, 'total' => 0]
        );
    }

    /**
     * Agrega o incrementa un artículo en el carrito (borrador activo).
     *
     * @return array{success: bool, message: string, item: array|null}
     */
    public function agregarAlCarrito(
        PreOrder $preOrder,
        string $codigoArticulo,
        float $cantidad = 1,
        int $precioNivel = 1
    ): array {
        if (! $preOrder->esEditable()) {
            return ['success' => false, 'message' => 'El pre-pedido no está en estado borrador.', 'item' => null];
        }

        $articulo = $this->getArticuloDetalle($codigoArticulo);
        if (! $articulo) {
            return ['success' => false, 'message' => "Artículo {$codigoArticulo} no encontrado.", 'item' => null];
        }

        // Precio según nivel seleccionado
        $precioNivel   = max(1, min(4, $precioNivel));
        $precioUnitario = (float) ($articulo["precio{$precioNivel}"] ?? $articulo['precio1'] ?? 0);

        if ($precioUnitario <= 0) {
            return ['success' => false, 'message' => 'El artículo no tiene precio configurado.', 'item' => null];
        }

        // Si ya existe en el carrito, incrementar cantidad
        $item = $preOrder->items()
            ->where('articulo_codigo', $codigoArticulo)
            ->where('precio_nivel', $precioNivel)
            ->first();

        if ($item) {
            $item->update(['cantidad' => $item->cantidad + $cantidad]);
        } else {
            $item = $preOrder->items()->create([
                'articulo_codigo'       => $articulo['codigo'],
                'articulo_descripcion'  => $articulo['descripcion'],
                'articulo_linea'        => $articulo['linea']     ?? '',
                'articulo_categoria'    => $articulo['categoria'] ?? '',
                'articulo_modelo'       => $articulo['modelo']    ?? '',
                'precio_nivel'          => $precioNivel,
                'precio_unitario'       => $precioUnitario,
                'cantidad'              => $cantidad,
                'subtotal'              => $precioUnitario * $cantidad,
                'stock_al_agregar'      => $articulo['stock_libre'] ?? null,
            ]);
        }

        $preOrder->recalcularTotales();

        return [
            'success' => true,
            'message' => "'{$articulo['descripcion']}' agregado al carrito.",
            'item'    => $item->toArray(),
        ];
    }

    /**
     * Actualiza la cantidad de un ítem del carrito.
     */
    public function actualizarCantidad(PreOrder $preOrder, int $itemId, float $nuevaCantidad): array
    {
        if (! $preOrder->esEditable()) {
            return ['success' => false, 'message' => 'Pre-pedido no editable.'];
        }

        $item = $preOrder->items()->find($itemId);
        if (! $item) {
            return ['success' => false, 'message' => 'Ítem no encontrado.'];
        }

        if ($nuevaCantidad <= 0) {
            $item->delete();
        } else {
            $item->update(['cantidad' => $nuevaCantidad]);
        }

        $preOrder->recalcularTotales();
        return ['success' => true, 'message' => 'Cantidad actualizada.'];
    }

    /**
     * Elimina un ítem del carrito.
     */
    public function eliminarItem(PreOrder $preOrder, int $itemId): array
    {
        if (! $preOrder->esEditable()) {
            return ['success' => false, 'message' => 'Pre-pedido no editable.'];
        }

        $preOrder->items()->where('id', $itemId)->delete();
        $preOrder->recalcularTotales();

        return ['success' => true, 'message' => 'Ítem eliminado.'];
    }

    /**
     * Envía el borrador a caja (cambia estado a pendiente_caja).
     */
    public function enviarACaja(PreOrder $preOrder, array $datosCliente = []): array
    {
        if (! $preOrder->esEditable()) {
            return ['success' => false, 'message' => 'Solo se pueden enviar borradores a caja.'];
        }

        if ($preOrder->items()->count() === 0) {
            return ['success' => false, 'message' => 'El carrito está vacío.'];
        }

        $preOrder->update([
            'cliente_nombre'   => $datosCliente['nombre']   ?? $preOrder->cliente_nombre,
            'cliente_telefono' => $datosCliente['telefono'] ?? $preOrder->cliente_telefono,
        ]);

        $preOrder->enviarACaja();

        return [
            'success'    => true,
            'message'    => "Pre-pedido #{$preOrder->numero_referencia} enviado a caja.",
            'referencia' => $preOrder->numero_referencia,
        ];
    }
}
