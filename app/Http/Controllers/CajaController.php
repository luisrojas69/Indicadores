<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\PreOrder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * CajaController
 * Panel del cajero: lista pre-pedidos pendientes y los marca como procesados.
 */
class CajaController extends Controller
{
    // ── Panel principal de caja ───────────────────────────────────────────
    public function index(): View
    {
        $pendientes = PreOrder::with(['items', 'vendedor'])
            ->where('estado', PreOrder::ESTADO_PENDIENTE_CAJA)
            ->latest('enviado_a_caja_at')
            ->get();

        $procesadosHoy = PreOrder::where('estado', PreOrder::ESTADO_PROCESADO)
            ->whereDate('procesado_at', today())
            ->count();

        return view('tablet.caja', compact('pendientes', 'procesadosHoy'));
    }

    // ── Detalle de un pre-pedido ──────────────────────────────────────────
    public function detalle(PreOrder $preOrder): JsonResponse
    {
        $preOrder->load(['items', 'vendedor']);

        return response()->json([
            'pre_order' => [
                'id'                => $preOrder->id,
                'numero_referencia' => $preOrder->numero_referencia,
                'estado'            => $preOrder->estado,
                'estado_info'       => $preOrder->estadoInfo(),
                'cliente_nombre'    => $preOrder->cliente_nombre,
                'cliente_telefono'  => $preOrder->cliente_telefono,
                'vendedor'          => $preOrder->vendedor?->name,
                'total_items'       => $preOrder->total_items,
                'total'             => $preOrder->total,
                'notas'             => $preOrder->notas,
                'enviado_a_caja_at' => $preOrder->enviado_a_caja_at?->format('d/m/Y H:i'),
                'items'             => $preOrder->items->map(fn ($i) => [
                    'id'                   => $i->id,
                    'articulo_codigo'      => $i->articulo_codigo,
                    'articulo_descripcion' => $i->articulo_descripcion,
                    'articulo_linea'       => $i->articulo_linea,
                    'precio_nivel'         => $i->precio_nivel,
                    'precio_unitario'      => $i->precio_unitario,
                    'cantidad'             => $i->cantidad,
                    'subtotal'             => $i->subtotal,
                ])->toArray(),
            ],
        ]);
    }

    // ── Marcar como procesado (el cajero ya facturó en Profit) ────────────
    public function procesar(Request $request, PreOrder $preOrder): JsonResponse
    {
        if (! $preOrder->esPendienteCaja()) {
            return response()->json(['success' => false, 'message' => 'Solo se pueden procesar pre-pedidos pendientes en caja.'], 422);
        }

        $preOrder->procesar(auth()->id());

        return response()->json([
            'success' => true,
            'message' => "Pre-pedido #{$preOrder->numero_referencia} marcado como procesado.",
        ]);
    }

    // ── Cancelar un pre-pedido desde caja ─────────────────────────────────
    public function cancelar(Request $request, PreOrder $preOrder): JsonResponse
    {
        $request->validate(['motivo' => 'nullable|string|max:255']);

        if ($preOrder->esProcesado()) {
            return response()->json(['success' => false, 'message' => 'No se puede cancelar un pre-pedido ya procesado.'], 422);
        }

        $preOrder->cancelar($request->input('motivo', ''));

        return response()->json([
            'success' => true,
            'message' => "Pre-pedido #{$preOrder->numero_referencia} cancelado.",
        ]);
    }
}
