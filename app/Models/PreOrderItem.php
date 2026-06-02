<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * PreOrderItem
 * Línea de artículo dentro de un pre-pedido.
 */
class PreOrderItem extends Model
{
    protected $fillable = [
        'pre_order_id',
        'articulo_codigo', 'articulo_descripcion',
        'articulo_linea', 'articulo_categoria', 'articulo_modelo',
        'precio_nivel', 'precio_unitario', 'cantidad', 'subtotal',
        'stock_al_agregar',
    ];

    protected $casts = [
        'precio_unitario'   => 'decimal:2',
        'cantidad'          => 'decimal:3',
        'subtotal'          => 'decimal:2',
        'stock_al_agregar'  => 'decimal:3',
    ];

    public function preOrder(): BelongsTo
    {
        return $this->belongsTo(PreOrder::class);
    }

    // Recalcula subtotal antes de guardar
    protected static function booted(): void
    {
        static::saving(function (self $item) {
            $item->subtotal = round(
                (float) $item->precio_unitario * (float) $item->cantidad,
                2
            );
        });
    }
}
