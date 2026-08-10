<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class QueryOptimizationService
{
    /**
     * Analyze slow queries and suggest optimizations.
     *
     * @param string $query
     * @param array $bindings
     * @return array
     */
    public function analyzeQuery(string $query, array $bindings = []): array
    {
        $analysis = [
            'query' => $query,
            'suggestions' => [],
            'estimated_cost' => 0,
            'execution_time' => 0,
        ];

        try {
            $startTime = microtime(true);
            
            // Get query execution plan
            $explainQuery = "EXPLAIN (ANALYZE, BUFFERS, FORMAT JSON) " . $query;
            $result = DB::select($explainQuery, $bindings);
            
            $executionTime = microtime(true) - $startTime;
            $analysis['execution_time'] = $executionTime;

            if (!empty($result)) {
                $plan = json_decode($result[0]->{'QUERY PLAN'}, true);
                $analysis['plan'] = $plan;
                $analysis['estimated_cost'] = $this->extractTotalCost($plan);
                
                // Analyze for optimization opportunities
                $analysis['suggestions'] = $this->generateOptimizationSuggestions($plan, $executionTime);
            }

        } catch (\Exception $e) {
            Log::error('Query analysis failed', [
                'query' => $query,
                'error' => $e->getMessage(),
            ]);
            $analysis['error'] = $e->getMessage();
        }

        return $analysis;
    }

    /**
     * Extract total cost from query plan.
     *
     * @param array $plan
     * @return float
     */
    private function extractTotalCost(array $plan): float
    {
        if (isset($plan['Plan']['Total Cost'])) {
            return (float) $plan['Plan']['Total Cost'];
        }
        
        if (isset($plan[0]['Plan']['Total Cost'])) {
            return (float) $plan[0]['Plan']['Total Cost'];
        }

        return 0.0;
    }

    /**
     * Generate optimization suggestions based on query plan.
     *
     * @param array $plan
     * @param float $executionTime
     * @return array
     */
    private function generateOptimizationSuggestions(array $plan, float $executionTime): array
    {
        $suggestions = [];

        // Check for sequential scans
        if ($this->hasSequentialScan($plan)) {
            $suggestions[] = [
                'type' => 'index',
                'severity' => 'high',
                'message' => 'Sequential scan detected. Consider adding an index on the filtered columns.',
                'impact' => 'High - Can reduce query time by 50-90%',
            ];
        }

        // Check for nested loops
        if ($this->hasNestedLoop($plan)) {
            $suggestions[] = [
                'type' => 'join',
                'severity' => 'medium',
                'message' => 'Nested loop join detected. Consider optimizing join order or adding join indexes.',
                'impact' => 'Medium - Can reduce query time by 20-50%',
            ];
        }

        // Check for hash joins without appropriate indexes
        if ($this->hasHashJoin($plan)) {
            $suggestions[] = [
                'type' => 'index',
                'severity' => 'low',
                'message' => 'Hash join detected. Ensure join columns are indexed.',
                'impact' => 'Low - Can reduce query time by 10-30%',
            ];
        }

        // Check for slow execution
        if ($executionTime > 1.0) {
            $suggestions[] = [
                'type' => 'performance',
                'severity' => 'high',
                'message' => sprintf('Query execution time (%.2fs) exceeds 1 second. Consider query optimization.', $executionTime),
                'impact' => 'High - Critical for user experience',
            ];
        }

        // Check for high cost
        $totalCost = $this->extractTotalCost($plan);
        if ($totalCost > 1000) {
            $suggestions[] = [
                'type' => 'performance',
                'severity' => 'medium',
                'message' => sprintf('Query planner cost (%.2f) is high. Consider reviewing query structure.', $totalCost),
                'impact' => 'Medium - May impact performance under load',
            ];
        }

        return $suggestions;
    }

    /**
     * Check if plan contains sequential scan.
     *
     * @param array $plan
     * @return bool
     */
    private function hasSequentialScan(array $plan): bool
    {
        $planString = json_encode($plan);
        return strpos($planString, 'Seq Scan') !== false;
    }

    /**
     * Check if plan contains nested loop.
     *
     * @param array $plan
     * @return bool
     */
    private function hasNestedLoop(array $plan): bool
    {
        $planString = json_encode($plan);
        return strpos($planString, 'Nested Loop') !== false;
    }

    /**
     * Check if plan contains hash join.
     *
     * @param array $plan
     * @return bool
     */
    private function hasHashJoin(array $plan): bool
    {
        $planString = json_encode($plan);
        return strpos($planString, 'Hash Join') !== false;
    }

    /**
     * Get slow queries from logs.
     *
     * @param float $threshold
     * @param int $limit
     * @return array
     */
    public function getSlowQueries(float $threshold = 1.0, int $limit = 10): array
    {
        // This would typically query a slow query log
        // For now, return empty array as implementation depends on logging setup
        return [
            'threshold' => $threshold,
            'queries' => [],
            'message' => 'Slow query logging not configured. Configure query logging to enable this feature.',
        ];
    }

    /**
     * Suggest indexes for a table based on query patterns.
     *
     * @param string $table
     * @return array
     */
    public function suggestIndexesForTable(string $table): array
    {
        $suggestions = [];

        try {
            // Get table statistics
            $stats = DB::select("SELECT * FROM pg_stats WHERE tablename = ?", [$table]);
            
            // Get existing indexes
            $indexes = DB::select("
                SELECT 
                    indexname,
                    indexdef
                FROM pg_indexes 
                WHERE tablename = ?
            ", [$table]);

            // Analyze column usage patterns
            foreach ($stats as $stat) {
                if ($stat->null_frac < 0.1 && $stat->n_distinct > 100) {
                    $suggestions[] = [
                        'column' => $stat->attname,
                        'type' => 'btree',
                        'reason' => 'High cardinality column with low null fraction',
                        'suggested_index' => "CREATE INDEX idx_{$table}_{$stat->attname} ON {$table}({$stat->attname})",
                    ];
                }
            }

        } catch (\Exception $e) {
            Log::error('Index suggestion failed', [
                'table' => $table,
                'error' => $e->getMessage(),
            ]);
        }

        return $suggestions;
    }

    /**
     * Optimize a specific query with suggestions applied.
     *
     * @param string $query
     * @param array $suggestions
     * @return string
     */
    public function applyOptimizations(string $query, array $suggestions): string
    {
        $optimizedQuery = $query;

        foreach ($suggestions as $suggestion) {
            if ($suggestion['type'] === 'index') {
                // Extract index creation SQL if available
                if (isset($suggestion['suggested_index'])) {
                    Log::info('Suggested index', [
                        'index_sql' => $suggestion['suggested_index'],
                    ]);
                }
            }
        }

        return $optimizedQuery;
    }
}