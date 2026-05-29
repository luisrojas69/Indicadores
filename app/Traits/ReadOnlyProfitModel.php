<?php

declare(strict_types=1);

namespace App\Traits;

use Illuminate\Database\Eloquent\Model;
use RuntimeException;

/**
 * ReadOnlyProfitModel
 *
 * Trait que deben usar todos los modelos Eloquent apuntados a la
 * conexión del ERP Profit Plus 2K8.
 *
 * Garantías:
 *  1. Conexión siempre forzada a config('profit.connection').
 *  2. Timestamps desactivados (Profit no tiene created_at/updated_at).
 *  3. Auto-increment desactivado (Profit usa PKs tipo Character).
 *  4. Cualquier intento de escritura lanza RuntimeException inmediatamente,
 *     tanto vía eventos Eloquent como vía métodos directos (save/update/delete).
 *
 * Uso:
 *   class Articulo extends Model
 *   {
 *       use ReadOnlyProfitModel;
 *       protected $table = 'saArticulo';   // nombre físico en Profit
 *   }
 */
trait ReadOnlyProfitModel
{
    /**
     * Registra los listeners de eventos Eloquent que bloquean escrituras.
     * Llamado automáticamente por Laravel mediante la convención boot{TraitName}.
     */
    public static function bootReadOnlyProfitModel(): void
    {
        $blockedEvents = ['creating', 'updating', 'saving', 'deleting', 'restoring', 'forceDeleting'];

        foreach ($blockedEvents as $event) {
            static::registerModelEvent(
                $event,
                static function (Model $model) use ($event): never {
                    throw new RuntimeException(
                        sprintf(
                            'Operación de escritura no permitida en modelos del ERP Profit. [Modelo: %s | Evento: %s]',
                            static::class,
                            $event
                        )
                    );
                }
            );
        }
    }

    /**
     * Inicializa propiedades del modelo en tiempo de instanciación.
     * Llamado automáticamente por Laravel mediante la convención initialize{TraitName}.
     */
    public function initializeReadOnlyProfitModel(): void
    {
        // Fuerza la conexión correcta aunque la subclase la sobreescriba por error.
        $this->connection = config('profit.connection', 'profit');

        // Profit Plus no maneja timestamps estándar de Laravel.
        $this->timestamps = false;

        // Las PKs de Profit son Character(30), no enteros auto-incrementales.
        $this->incrementing = false;
        $this->keyType      = 'string';
    }

    /**
     * Sobreescritura defensiva de save() — segunda capa de protección.
     *
     * @param  array<string, mixed>  $options
     */
    public function save(array $options = []): bool
    {
        throw new RuntimeException(
            'Operación de escritura no permitida en modelos del ERP Profit.'
        );
    }

    /**
     * Sobreescritura defensiva de delete() — segunda capa de protección.
     */
    public function delete(): bool|null
    {
        throw new RuntimeException(
            'Operación de escritura no permitida en modelos del ERP Profit.'
        );
    }

    /**
     * Sobreescritura defensiva de update() — segunda capa de protección.
     *
     * @param  array<string, mixed>  $attributes
     * @param  array<string, mixed>  $options
     */
    public function update(array $attributes = [], array $options = []): bool
    {
        throw new RuntimeException(
            'Operación de escritura no permitida en modelos del ERP Profit.'
        );
    }

    /**
     * Retorna siempre la conexión configurada para Profit.
     * Previene que subclases sobreescriban accidentalmente la conexión.
     */
    public function getConnectionName(): string
    {
        return config('profit.connection', 'profit');
    }
}
