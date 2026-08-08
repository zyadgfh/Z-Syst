<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class QueryOptimizationService
{
    /**
     * Enable query logging for debugging
     */
    public function enableQueryLogging(): void
    {
        DB::enableQueryLog();
    }

    /**
     * Get executed queries
     */
    public function getExecutedQueries(): array
    {
        return DB::getQueryLog();
    }

    /**
     * Check for N+1 queries
     */
    public function detectNPlusOneQueries(array $queries): array
    {
        $queryCounts = [];
        $nPlusOneQueries = [];

        foreach ($queries as $query) {
            $queryHash = md5($query['query']);
            $queryCounts[$queryHash] = ($queryCounts[$queryHash] ?? 0) + 1;
        }

        foreach ($queryCounts as $hash => $count) {
            if ($count > 10) { // Threshold for potential N+1
                $nPlusOneQueries[] = [
                    'query' => $hash,
                    'count' => $count,
                ];
            }
        }

        return $nPlusOneQueries;
    }

    /**
     * Optimize query with eager loading
     */
    public function withEagerLoading(Builder $query, array $relations): Builder
    {
        return $query->with($relations);
    }

    /**
     * Optimize query with count loading
     */
    public function withCountLoading(Builder $query, array $relations): Builder
    {
        return $query->withCount($relations);
    }

    /**
     * Optimize query with eager loading constraints
     */
    public function withEagerLoadingConstraints(Builder $query, array $relations): Builder
    {
        foreach ($relations as $relation => $constraints) {
            $query->with([$relation => $constraints]);
        }

        return $query;
    }

    /**
     * Chunk large datasets for memory efficiency
     */
    public function chunkData(Builder $query, int $chunkSize, callable $callback): void
    {
        $query->chunk($chunkSize, $callback);
    }

    /**
     * Use cursor pagination for large datasets
     */
    public function cursorPaginate(Builder $query, int $perPage = 15)
    {
        return $query->cursorPaginate($perPage);
    }

    /**
     * Add select optimization
     */
    public function selectOnlyNeeded(Builder $query, array $columns): Builder
    {
        return $query->select($columns);
    }

    /**
     * Add index hints
     */
    public function withIndexHint(Builder $query, string $index): Builder
    {
        return $query->from(DB::raw("{$query->from()} USE INDEX ({$index})"));
    }

    /**
     * Prevent N+1 by checking relationships
     */
    public function suggestEagerLoading(Model $model, array $accessedRelations): array
    {
        $suggestions = [];
        
        foreach ($accessedRelations as $relation) {
            if (method_exists($model, $relation)) {
                $suggestions[] = [
                    'relation' => $relation,
                    'suggestion' => "Add ->with('{$relation}') to your query",
                ];
            }
        }

        return $suggestions;
    }

    /**
     * Monitor query performance
     */
    public function monitorQueryPerformance(): array
    {
        $queries = DB::getQueryLog();
        $slowQueries = [];
        $totalTime = 0;

        foreach ($queries as $query) {
            $totalTime += $query['time'];
            
            if ($query['time'] > 100) { // 100ms threshold
                $slowQueries[] = [
                    'query' => $query['query'],
                    'time' => $query['time'],
                    'bindings' => $query['bindings'],
                ];
            }
        }

        return [
            'total_queries' => count($queries),
            'total_time' => $totalTime,
            'average_time' => count($queries) > 0 ? $totalTime / count($queries) : 0,
            'slow_queries' => $slowQueries,
        ];
    }

    /**
     * Optimize common query patterns
     */
    public function optimizeCommonPatterns(): array
    {
        return [
            'sales' => [
                'relations' => ['items.product', 'party', 'business'],
                'count_relations' => ['items'],
            ],
            'purchases' => [
                'relations' => ['purchaseDetails.product', 'party', 'business'],
                'count_relations' => ['purchaseDetails'],
            ],
            'products' => [
                'relations' => ['category', 'business'],
                'count_relations' => ['sales', 'stocks'],
            ],
            'warehouses' => [
                'relations' => ['business', 'stocks.product'],
                'count_relations' => ['stocks'],
            ],
        ];
    }

    /**
     * Apply automatic optimization based on model
     */
    public function autoOptimize(Builder $query, string $modelClass): Builder
    {
        $patterns = $this->optimizeCommonPatterns();
        $modelName = class_basename($modelClass);
        
        if (isset($patterns[strtolower($modelName)])) {
            $pattern = $patterns[strtolower($modelName)];
            
            if (isset($pattern['relations'])) {
                $query = $query->with($pattern['relations']);
            }
            
            if (isset($pattern['count_relations'])) {
                $query = $query->withCount($pattern['count_relations']);
            }
        }

        return $query;
    }
}
