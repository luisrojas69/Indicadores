<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * PreOrder
 *
 * Borrador de venta generado desde el módulo tablet.
 * Vive en la BD local de Laravel — nunca toca Profit.
 */
class PreOrder extends Model
{
    protected $fillable = [
        'vendedor_id', 'cajero_id', 'estado',
        'cliente_nombre', 'cliente_telefono', 'cliente_codigo_profit',
        'total_items', 'subtotal', 'total', 'notas',
        'numero_referencia',
        'enviado_a_caja_at', 'procesado_at', 'cancelado_at', 'motivo_cancelacion',
    ];

    protected $casts = [
        'subtotal'          => 'decimal:2',
        'total'             => 'decimal:2',
        'enviado_a_caja_at' => 'datetime',
        'procesado_at'      => 'datetime',
        'cancelado_at'      => 'datetime',
    ];

    // ── Estados ──────────────────────────────────────────────────────────
    const ESTADO_BORRADOR        = 'borrador';
    const ESTADO_PENDIENTE_CAJA  = 'pendiente_caja';
    const ESTADO_PROCESADO       = 'procesado';
    const ESTADO_CANCELADO       = 'cancelado';

    public static function estados(): array
    {
        return [
            self::ESTADO_BORRADOR       => ['label' => 'Borrador',         'color' => '#64748b', 'bg' => '#f1f5f9', 'icon' => 'fa-pencil'],
            self::ESTADO_PENDIENTE_CAJA => ['label' => 'Pendiente en Caja','color' => '#d97706', 'bg' => '#fef3c7', 'icon' => 'fa-clock'],
            self::ESTADO_PROCESADO      => ['label' => 'Procesado',        'color' => '#059669', 'bg' => '#dcfce7', 'icon' => 'fa-check-circle'],
            self::ESTADO_CANCELADO      => ['label' => 'Cancelado',        'color' => '#dc2626', 'bg' => '#fee2e2', 'icon' => 'fa-times-circle'],
        ];
    }

    public function estadoInfo(): array
    {
        return self::estados()[$this->estado] ?? self::estados()[self::ESTADO_BORRADOR];
    }

    public function esBorrador(): bool        { return $this->estado === self::ESTADO_BORRADOR; }
    public function esPendienteCaja(): bool   { return $this->estado === self::ESTADO_PENDIENTE_CAJA; }
    public function esProcesado(): bool       { return $this->estado === self::ESTADO_PROCESADO; }
    public function esCancelado(): bool       { return $this->estado === self::ESTADO_CANCELADO; }
    public function esEditable(): bool        { return $this->esBorrador(); }

    // ── Relaciones ────────────────────────────────────────────────────────
    public function vendedor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'vendedor_id');
    }

    public function cajero(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cajero_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(PreOrderItem::class);
    }

    // ── Acciones de estado ────────────────────────────────────────────────
    public function enviarACaja(): void
    {
        $this->update([
            'estado'             => self::ESTADO_PENDIENTE_CAJA,
            'enviado_a_caja_at'  => now(),
        ]);
    }

    public function procesar(int $cajeroId): void
    {
        $this->update([
            'estado'       => self::ESTADO_PROCESADO,
            'cajero_id'    => $cajeroId,
            'procesado_at' => now(),
        ]);
    }

    public function cancelar(string $motivo = ''): void
    {
        $this->update([
            'estado'             => self::ESTADO_CANCELADO,
            'cancelado_at'       => now(),
            'motivo_cancelacion' => $motivo,
        ]);
    }

    // ── Recalcular totales desde los ítems ────────────────────────────────
    public function recalcularTotales(): void
    {
        $this->load('items');
        $this->update([
            'total_items' => $this->items->count(),
            'subtotal'    => $this->items->sum('subtotal'),
            'total'       => $this->items->sum('subtotal'),
        ]);
    }

    // ── Número de referencia legible ──────────────────────────────────────
    protected static function booted(): void
    {
        static::creating(function (self $order) {
            if (! $order->numero_referencia) {
                $order->numero_referencia = 'PP-' . date('Y') . '-' . str_pad(
                    (string) (static::whereYear('created_at', date('Y'))->count() + 1),
                    4, '0', STR_PAD_LEFT
                );
            }
        });
    }
}
