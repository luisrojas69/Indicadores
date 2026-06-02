<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\PreOrder;
use App\Services\Tablet\TabletService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

/**
 * TabletController
 * Catálogo táctil + gestión del carrito del vendedor.
 */
class TabletController extends Controller
{
    public function __construct(
        private readonly TabletService $tablet
    ) {}

    // ── Catálogo principal ────────────────────────────────────────────────
    public function catalogo(Request $request): View
    {
        $filters = [
            'search'    => $request->input('search', ''),
            'categoria' => $request->input('categoria', ''),
            'marca'     => $request->input('marca', ''),
        ];

        $perPage = config('tablet.catalogo_per_page', 24);
        $page    = (int) $request->input('page', 1);

        $resultado  = $this->tablet->getCatalogo($filters, $perPage, $page);
        $articulos  = $resultado['data'];
        $total      = $resultado['total'];
        $totalPages = (int) ceil($total / $perPage);

        // Filtros disponibles (con caché de 30 min)
        $categorias = Cache::remember('tablet:categorias', 1800, fn () => $this->tablet->getCategorias());
        $marcas     = Cache::remember('tablet:marcas',     1800, fn () => $this->tablet->getMarcas());

        // Carrito activo del vendedor
        $carrito = $this->tablet->getBorradorActivo(auth()->id());
        $carrito->load('items');

        return view('tablet.catalogo', compact(
            'articulos', 'total', 'totalPages',
            'perPage', 'page', 'filters',
            'categorias', 'marcas',
            'carrito',
        ));
    }

    // ── Ficha expandida (modal / ajax) ────────────────────────────────────
    public function fichaArticulo(string $codigo): JsonResponse
    {
        $articulo = Cache::remember(
            "tablet:ficha:{$codigo}",
            1800,
            fn () => $this->tablet->getArticuloDetalle($codigo)
        );

        if (! $articulo) {
            return response()->json(['error' => 'Artículo no encontrado.'], 404);
        }

        return response()->json(['articulo' => $articulo]);
    }

    // ── Agregar al carrito ────────────────────────────────────────────────
    public function agregarAlCarrito(Request $request): JsonResponse
    {
        $request->validate([
            'codigo'       => 'required|string|max:30',
            'cantidad'     => 'numeric|min:0.001|max:9999',
            'precio_nivel' => 'integer|min:1|max:4',
        ]);

        $carrito   = $this->tablet->getBorradorActivo(auth()->id());
        $resultado = $this->tablet->agregarAlCarrito(
            $carrito,
            $request->input('codigo'),
            (float) $request->input('cantidad', 1),
            (int)   $request->input('precio_nivel', config('tablet.precio_nivel_default', 1))
        );

        $carrito->load('items');

        return response()->json(array_merge($resultado, [
            'carrito_items'    => $carrito->items->count(),
            'carrito_total'    => $carrito->total,
            'carrito_subtotal' => $carrito->subtotal,
        ]));
    }

    // ── Actualizar cantidad ───────────────────────────────────────────────
    public function actualizarCantidad(Request $request, int $itemId): JsonResponse
    {
        $request->validate(['cantidad' => 'required|numeric|min:0']);

        $carrito   = $this->tablet->getBorradorActivo(auth()->id());
        $resultado = $this->tablet->actualizarCantidad($carrito, $itemId, (float) $request->input('cantidad'));

        $carrito->load('items');

        return response()->json(array_merge($resultado, [
            'carrito_total' => $carrito->total,
            'items'         => $carrito->items->toArray(),
        ]));
    }

    // ── Eliminar ítem ─────────────────────────────────────────────────────
    public function eliminarItem(int $itemId): JsonResponse
    {
        $carrito   = $this->tablet->getBorradorActivo(auth()->id());
        $resultado = $this->tablet->eliminarItem($carrito, $itemId);

        $carrito->load('items');

        return response()->json(array_merge($resultado, [
            'carrito_total' => $carrito->total,
            'carrito_items' => $carrito->items->count(),
        ]));
    }

    // ── Vista del carrito ─────────────────────────────────────────────────
    public function carrito(): View
    {
        $carrito = $this->tablet->getBorradorActivo(auth()->id());
        $carrito->load('items');

        return view('tablet.carrito', compact('carrito'));
    }

    // ── Enviar a caja ─────────────────────────────────────────────────────
    public function enviarACaja(Request $request): JsonResponse
    {
        $request->validate([
            'cliente_nombre'   => 'nullable|string|max:120',
            'cliente_telefono' => 'nullable|string|max:30',
        ]);

        $carrito   = $this->tablet->getBorradorActivo(auth()->id());
        $resultado = $this->tablet->enviarACaja($carrito, [
            'nombre'   => $request->input('cliente_nombre'),
            'telefono' => $request->input('cliente_telefono'),
        ]);

        return response()->json($resultado, $resultado['success'] ? 200 : 422);
    }

    // ── Historial de pre-pedidos del vendedor ─────────────────────────────
    public function misPrePedidos(): View
    {
        $prePedidos = PreOrder::with('items')
            ->where('vendedor_id', auth()->id())
            ->whereIn('estado', [PreOrder::ESTADO_PENDIENTE_CAJA, PreOrder::ESTADO_PROCESADO, PreOrder::ESTADO_CANCELADO])
            ->latest()
            ->paginate(20);

        return view('tablet.mis_prepedidos', compact('prePedidos'));
    }
}
