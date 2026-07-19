<?php

namespace App\Core\Abstracts;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\Paginator;

/**
 * Base Repository Class
 * 
 * Provides common repository operations for all domain repositories.
 * Implements Data Access abstraction layer.
 */
abstract class AbstractRepository
{
    /**
     * The model instance
     */
    protected Model $model;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->model = $this->getModel();
    }

    /**
     * Get the model instance
     */
    abstract protected function getModel(): Model;

    /**
     * Find by ID
     */
    public function find(mixed $id): ?Model
    {
        return $this->model->find($id);
    }

    /**
     * Find or fail by ID
     */
    public function findOrFail(mixed $id): Model
    {
        return $this->model->findOrFail($id);
    }

    /**
     * Get all records
     */
    public function all(): Collection
    {
        return $this->model->all();
    }

    /**
     * Paginate records
     */
    public function paginate(int $perPage = 15): Paginator
    {
        return $this->model->paginate($perPage);
    }

    /**
     * Create a new record
     */
    public function create(array $data): Model
    {
        return $this->model->create($data);
    }

    /**
     * Update a record
     */
    public function update(mixed $id, array $data): Model
    {
        $record = $this->find($id);
        if ($record) {
            $record->update($data);
        }
        return $record;
    }

    /**
     * Delete a record
     */
    public function delete(mixed $id): bool
    {
        $record = $this->find($id);
        return $record ? $record->delete() : false;
    }

    /**
     * Get the query builder instance
     */
    public function query()
    {
        return $this->model->query();
    }

    /**
     * Get the model class name
     */
    public function getModelClass(): string
    {
        return get_class($this->model);
    }
}
