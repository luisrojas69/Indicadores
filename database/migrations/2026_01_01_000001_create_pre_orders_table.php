<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
|--------------------------------------------------------------------------
| Tabla: pre_orders
|--------------------------------------------------------------------------
| Esta tabla vive en la BD LOCAL de Laravel (no en Profit).
| Guarda borradores de ventas creados desde el módulo tablet.
|
| Flujo de estados:
|   borrador → pendiente_caja → procesado
|                             → cancelado
|
| Nunca se escribe en Profit. El cajero usa el pre-pedido como
| guía para facturar manualmente en Profit Plus.
*/
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pre_orders', function (Blueprint $table) {
            $table->id();

            // ── Relaciones ──────────────────────────────────────────────
            $table->foreignId('vendedor_id')
                  ->constrained('users')
                  ->cascadeOnDelete();

            $table->foreignId('cajero_id')
                  ->nullable()
                  ->constrained('users')
                  ->nullOnDelete();

            // ── Estado ──────────────────────────────────────────────────
            $table->enum('estado', [
                'borrador',
                'pendiente_caja',
                'procesado',
                'cancelado',
            ])->default('borrador')->index();

            // ── Datos del cliente (opcionales, capturados en sala) ───────
            $table->string('cliente_nombre',  120)->nullable();
            $table->string('cliente_telefono', 30)->nullable();
            $table->string('cliente_codigo_profit', 30)->nullable(); // CO_CLI de Profit si se selecciona

            // ── Totales calculados (desnormalizados para consultas rápidas) ─
            $table->unsignedInteger('total_items')->default(0);
            $table->decimal('subtotal',   14, 2)->default(0);
            $table->decimal('total',      14, 2)->default(0);

            // ── Metadatos ────────────────────────────────────────────────
            $table->text('notas')->nullable();
            $table->string('numero_referencia', 20)->unique()->nullable(); // ej: PP-2026-0001
            $table->timestamp('enviado_a_caja_at')->nullable();
            $table->timestamp('procesado_at')->nullable();
            $table->timestamp('cancelado_at')->nullable();
            $table->string('motivo_cancelacion', 255)->nullable();

            $table->timestamps();

            // ── Índices para consultas frecuentes ────────────────────────
            $table->index(['estado', 'created_at']);
            $table->index('vendedor_id');
        });

        Schema::create('pre_order_items', function (Blueprint $table) {
            $table->id();

            $table->foreignId('pre_order_id')
                  ->constrained('pre_orders')
                  ->cascadeOnDelete();

            // ── Datos del artículo (copiados desde Profit en el momento) ─
            // Se copian para que el ítem sea estático aunque cambie en Profit
            $table->string('articulo_codigo', 30);   // CO_ART
            $table->string('articulo_descripcion', 120);
            $table->string('articulo_linea', 60)->nullable();     // CO_LIN (marca)
            $table->string('articulo_categoria', 60)->nullable(); // CO_CAT
            $table->string('articulo_modelo', 60)->nullable();    // CO_MOD

            // ── Precio y cantidad ────────────────────────────────────────
            $table->tinyInteger('precio_nivel')->default(1); // Precio de venta 1-4
            $table->decimal('precio_unitario', 14, 2);
            $table->decimal('cantidad', 10, 3)->default(1);
            $table->decimal('subtotal', 14, 2);

            // ── Stock al momento de agregar (referencia informativa) ──────
            $table->decimal('stock_al_agregar', 10, 3)->nullable();

            $table->timestamps();

            $table->index('pre_order_id');
            $table->index('articulo_codigo');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pre_order_items');
        Schema::dropIfExists('pre_orders');
    }
};
