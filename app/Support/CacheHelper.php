<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * CacheHelper
 *
 * Wrapper centralizado para operaciones de caché del sistema.
 *
 * PROBLEMA QUE RESUELVE:
 * Laravel serializa los objetos tal como están al guardarlos en caché (file/redis).
 * Si se guarda una Collection de Illuminate, al deserializarla en una nueva request
 * el autoloader puede no haber cargado aún la clase, resultando en __PHP_Incomplete_Class.
 *
 * REGLA DE ORO:
 *   - Lo que ENTRA a caché: siempre array plano (json_encode internamente)
 *   - Lo que SALE de caché: siempre array plano
 *   - La conversión array → Collection ocurre DESPUÉS de leer caché, en el controller
 *
 * USO:
 *   // En el controller — retorna array, luego conviertes a Collection
 *   $data = CacheHelper::rememberArray('key', 300, fn() => $this->erp->getTopProductos(...));
 *   $collection = collect($data);
 *
 *   // Para datos escalares (KPIs, resúmenes)
 *   $kpis = CacheHelper::rememberArray('kpis', 300, fn() => $this->erp->getDashboardKpis(...));
 */
class CacheHelper
{
    /**
     * Guarda en caché garantizando que el valor es array plano.
     * Si el callback retorna una Collection, la convierte automáticamente.
     * Usa JSON internamente para evitar problemas de serialización PHP.
     *
     * @param  string    $key
     * @param  int       $ttlSeconds
     * @param  callable  $callback   Debe retornar array|Collection
     * @return array
     */
    public static function rememberArray(string $key, int $ttlSeconds, callable $callback): array
    {
        $jsonKey = "json:{$key}";

        $cached = Cache::get($jsonKey);

        if ($cached !== null) {
            $decoded = json_decode($cached, true);
            // Si el JSON es válido, retornar. Si no, ignorar y recalcular.
            if (json_last_error() === JSON_ERROR_NONE) {
                return $decoded ?? [];
            }
        }

        // Ejecutar callback y normalizar a array
        $result = $callback();

        $array = match (true) {
            $result instanceof Collection => $result->values()->toArray(),
            is_array($result)            => $result,
            default                      => [],
        };

        // Guardar como JSON — nunca como objeto PHP serializado
        Cache::put($jsonKey, json_encode($array, JSON_UNESCAPED_UNICODE), $ttlSeconds);

        return $array;
    }

    /**
     * Versión para arrays asociativos simples (KPIs, resúmenes, configuración).
     * Igual que rememberArray pero semánticamente más claro en los controllers.
     *
     * @param  string    $key
     * @param  int       $ttlSeconds
     * @param  callable  $callback   Debe retornar array<string, mixed>
     * @return array<string, mixed>
     */
    public static function rememberAssoc(string $key, int $ttlSeconds, callable $callback): array
    {
        return self::rememberArray($key, $ttlSeconds, $callback);
    }

    /**
     * Invalida una clave de caché (agrega el prefijo json: automáticamente).
     */
    public static function forget(string $key): bool
    {
        return Cache::forget("json:{$key}");
    }

    /**
     * Invalida múltiples claves por prefijo.
     * Útil para limpiar todo el caché de un módulo (ej: 'dashboard:').
     * Requiere driver Redis con soporte de SCAN.
     */
    public static function forgetByPrefix(string $prefix): void
    {
        try {
            // Intentar con Redis (soporta pattern matching)
            $redis = Cache::getRedis();
            $keys  = $redis->keys("*json:{$prefix}*");
            if (!empty($keys)) {
                $redis->del($keys);
            }
        } catch (\Throwable) {
            // Si no hay Redis o falla, simplemente ignorar
            // En file cache no hay forma eficiente de limpiar por prefijo
        }
    }
}
