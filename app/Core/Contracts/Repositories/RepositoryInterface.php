<?php

namespace App\Core\Contracts\Repositories;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\Paginator;

/**
 * Base Repository Interface
 *
 * Defines contract for all repository implementations.
 */
interface RepositoryInterface
{
    /**
     * Find by ID
     */
    public function find(mixed $id): ?Model;

    /**
     * Find or fail by ID
     */
    public function findOrFail(mixed $id): Model;

    /**
     * Get all records
     */
    public function all(): Collection;

    /**
     * Paginate records
     */
    public function paginate(int $perPage = 15): Paginator;

    /**
     * Create a new record
     */
    public function create(array $data): Model;

    /**
     * Update a record
     */
    public function update(mixed $id, array $data): Model;

    /**
     * Delete a record
     */
    public function delete(mixed $id): bool;

    /**
     * Get the query builder instance
     */
    public function query();

    /**
     * Get the model class name
     */
    public function getModelClass(): string;
}
