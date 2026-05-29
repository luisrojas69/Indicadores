<?php

declare(strict_types=1);

namespace App\Erp\Contracts;

use Illuminate\Support\Collection;

/**
 * ErpRepositoryInterface
 *
 * Contrato para repositorios de entidades específicas del ERP.
 *
 * Diferencia clave con ErpConnectionInterface:
 *   - ErpConnectionInterface  → operaciones de negocio / KPIs (queries compuestas)
 *   - ErpRepositoryInterface  → acceso CRUD-like a una entidad concreta (Artículo, Cliente, etc.)
 *
 * Los repositorios concretos (ProfitArticuloRepository, ProfitClienteRepository, etc.)
 * implementan esta interfaz. Los controladores y servicios solo la conocen a ella.
 *
 * @template TEntity  El tipo de entidad que maneja el repositorio (array<string,mixed>)
 */
interface ErpRepositoryInterface
{
    /**
     * Retorna todos los registros de la entidad.
     * Usar con precaución en tablas grandes — siempre preferir findPaginated().
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function findAll(): Collection;

    /**
     * Busca un registro por su clave primaria.
     *
     * @return array<string, mixed>|null
     */
    public function findById(string|int $id): ?array;

    /**
     * Búsqueda con filtros libres y paginación.
     *
     * @param  array<string, mixed>  $filters
     * @return array{ data: Collection<int, array<string,mixed>>, total: int }
     */
    public function findPaginated(array $filters = [], int $perPage = 15, int $page = 1): array;

    /**
     * Búsqueda rápida por texto (para autocomplete, buscadores en UI).
     * Implementar con LIKE o Full-Text Search según capacidad del ERP.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function search(string $term, int $limit = 20): Collection;

    /**
     * Verifica si un registro existe por su ID.
     */
    public function exists(string|int $id): bool;
}
