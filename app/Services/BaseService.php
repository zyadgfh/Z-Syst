<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

abstract class BaseService
{
    /**
     * Get paginated list of records.
     */
    protected function paginate(Model $model, int $perPage = 15, array $with = [], array $filters = []): LengthAwarePaginator
    {
        $query = $model->query()->with($with);

        foreach ($filters as $column => $value) {
            if ($value !== null && $value !== '') {
                if (is_array($value)) {
                    $query->whereIn($column, $value);
                } else {
                    $query->where($column, $value);
                }
            }
        }

        return $query->paginate($perPage);
    }

    /**
     * Get all records.
     */
    protected function getAll(Model $model, array $with = [], array $filters = []): Collection
    {
        $query = $model->query()->with($with);

        foreach ($filters as $column => $value) {
            if ($value !== null && $value !== '') {
                if (is_array($value)) {
                    $query->whereIn($column, $value);
                } else {
                    $query->where($column, $value);
                }
            }
        }

        return $query->get();
    }

    /**
     * Find a record by ID.
     */
    protected function findById(Model $model, int $id, array $with = []): ?Model
    {
        return $model->query()->with($with)->find($id);
    }

    /**
     * Find a record by ID or fail.
     */
    protected function findOrFail(Model $model, int $id, array $with = []): Model
    {
        return $model->query()->with($with)->findOrFail($id);
    }

    /**
     * Create a new record.
     */
    protected function create(Model $model, array $data): Model
    {
        return $model->query()->create($data);
    }

    /**
     * Update a record.
     */
    protected function update(Model $model, array $data): Model
    {
        $model->update($data);

        return $model->fresh();
    }

    /**
     * Delete a record.
     */
    protected function delete(Model $model): bool
    {
        return $model->delete();
    }

    /**
     * Toggle a boolean field.
     */
    protected function toggle(Model $model, string $field = 'is_active'): Model
    {
        $model->update([
            $field => !$model->{$field},
        ]);

        return $model->fresh();
    }
}