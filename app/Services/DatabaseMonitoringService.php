<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class DatabaseMonitoringService
{
    /**
     * Get database connection metrics.
     *
     * @return array
     */
    public function getConnectionMetrics(): array
    {
        try {
            $metrics = [
                'active_connections' => $this->getActiveConnections(),
                'max_connections' => $this->getMaxConnections(),
                'database_size' => $this->getDatabaseSize(),
                'cache_hit_ratio' => $this->getCacheHitRatio(),
                'transaction_count' => $this->getTransactionCount(),
                'timestamp' => now()->toIso8601String(),
            ];

            return $metrics;

        } catch (\Exception $e) {
            Log::error('Failed to get database metrics', [
                'error' => $e->getMessage(),
            ]);

            return [
                'error' => $e->getMessage(),
                'timestamp' => now()->toIso8601String(),
            ];
        }
    }

    /**
     * Get active connection count.
     *
     * @return int
     */
    private function getActiveConnections(): int
    {
        try {
            $result = DB::select("SELECT count(*) as count FROM pg_stat_activity WHERE state = 'active'");
            return (int) $result[0]->count;
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Get maximum connection limit.
     *
     * @return int
     */
    private function getMaxConnections(): int
    {
        try {
            $result = DB::select("SHOW max_connections");
            return (int) $result[0]->max_connections;
        } catch (\Exception $e) {
            return 100; // Default fallback
        }
    }

    /**
     * Get database size.
     *
     * @return string
     */
    private function getDatabaseSize(): string
    {
        try {
            $result = DB::select("SELECT pg_size_pretty(pg_database_size(current_database())) as size");
            return $result[0]->size;
        } catch (\Exception $e) {
            return 'Unknown';
        }
    }

    /**
     * Get cache hit ratio.
     *
     * @return float
     */
    private function getCacheHitRatio(): float
    {
        try {
            $result = DB::select("
                SELECT 
                    sum(blks_hit) / (sum(blks_hit) + sum(blks_read)) as ratio
                FROM pg_stat_database 
                WHERE datname = current_database()
            ");
            
            return $result[0]->ratio ? round($result[0]->ratio * 100, 2) : 0.0;
        } catch (\Exception $e) {
            return 0.0;
        }
    }

    /**
     * Get transaction count.
     *
     * @return int
     */
    private function getTransactionCount(): int
    {
        try {
            $result = DB::select("SELECT count(*) as count FROM pg_stat_activity WHERE state IN ('idle in transaction', 'active')");
            return (int) $result[0]->count;
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Get table size metrics.
     *
     * @return array
     */
    public function getTableMetrics(): array
    {
        try {
            $result = DB::select("
                SELECT 
                    schemaname,
                    tablename,
                    pg_size_pretty(pg_total_relation_size(schemaname||'.'||tablename)) as size,
                    pg_total_relation_size(schemaname||'.'||tablename) as size_bytes,
                    n_live_tup as row_count,
                    n_dead_tup as dead_row_count
                FROM pg_stat_user_tables 
                WHERE schemaname = 'public'
                ORDER BY pg_total_relation_size(schemaname||'.'||tablename) DESC
                LIMIT 20
            ");

            return array_map(function ($table) {
                return [
                    'schema' => $table->schemaname,
                    'table' => $table->tablename,
                    'size' => $table->size,
                    'size_bytes' => (int) $table->size_bytes,
                    'row_count' => (int) $table->n_live_tup,
                    'dead_row_count' => (int) $table->n_dead_tup,
                    'dead_row_ratio' => $table->n_live_tup > 0 
                        ? round(($table->n_dead_tup / $table->n_live_tup) * 100, 2) 
                        : 0,
                ];
            }, $result);

        } catch (\Exception $e) {
            Log::error('Failed to get table metrics', [
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }

    /**
     * Get long-running queries.
     *
     * @param int $thresholdSeconds
     * @return array
     */
    public function getLongRunningQueries(int $thresholdSeconds = 5): array
    {
        try {
            $result = DB::select("
                SELECT 
                    pid,
                    now() - query_start as duration,
                    state,
                    query
                FROM pg_stat_activity 
                WHERE state != 'idle' 
                AND now() - query_start > interval '? seconds'
                ORDER BY duration DESC
            ", [$thresholdSeconds]);

            return array_map(function ($query) {
                return [
                    'pid' => $query->pid,
                    'duration' => $query->duration,
                    'state' => $query->state,
                    'query' => substr($query->query, 0, 200), // Truncate long queries
                ];
            }, $result);

        } catch (\Exception $e) {
            Log::error('Failed to get long-running queries', [
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }

    /**
     * Get replication lag (if applicable).
     *
     * @return array
     */
    public function getReplicationLag(): array
    {
        try {
            $result = DB::select("SELECT * FROM pg_stat_replication");
            
            return array_map(function ($stat) {
                return [
                    'client_addr' => $stat->client_addr,
                    'state' => $stat->state,
                    'sync_state' => $stat->sync_state ?? null,
                    'lag' => $stat->lag_lag_seconds ?? null,
                ];
            }, $result);

        } catch (\Exception $e) {
            return [
                'error' => 'Replication not configured or query failed',
                'lag' => null,
            ];
        }
    }

    /**
     * Monitor database health and alert on issues.
     *
     * @return array
     */
    public function checkDatabaseHealth(): array
    {
        $health = [
            'status' => 'healthy',
            'issues' => [],
            'metrics' => $this->getConnectionMetrics(),
        ];

        $metrics = $health['metrics'];

        // Check connection usage
        if (isset($metrics['active_connections']) && isset($metrics['max_connections'])) {
            $usageRatio = $metrics['active_connections'] / $metrics['max_connections'];
            if ($usageRatio > 0.8) {
                $health['status'] = 'warning';
                $health['issues'][] = [
                    'type' => 'connection_usage',
                    'severity' => 'high',
                    'message' => sprintf('Connection usage at %.0f%% (%d/%d)', 
                        $usageRatio * 100, 
                        $metrics['active_connections'], 
                        $metrics['max_connections']
                    ),
                ];
            }
        }

        // Check cache hit ratio
        if (isset($metrics['cache_hit_ratio']) && $metrics['cache_hit_ratio'] < 90) {
            $health['status'] = 'warning';
            $health['issues'][] = [
                'type' => 'cache_performance',
                'severity' => 'medium',
                'message' => sprintf('Cache hit ratio low: %.2f%%', $metrics['cache_hit_ratio']),
            ];
        }

        // Check for long-running queries
        $longQueries = $this->getLongRunningQueries(10);
        if (!empty($longQueries)) {
            $health['status'] = 'warning';
            $health['issues'][] = [
                'type' => 'long_running_queries',
                'severity' => 'medium',
                'message' => sprintf('%d long-running queries detected', count($longQueries)),
                'details' => $longQueries,
            ];
        }

        return $health;
    }

    /**
     * Record database metrics for historical analysis.
     *
     * @return void
     */
    public function recordMetrics(): void
    {
        try {
            $metrics = $this->getConnectionMetrics();
            
            // Store in cache for short-term analysis
            Cache::put('db_metrics_latest', $metrics, now()->addMinutes(5));
            
            // Could also store in a metrics table for long-term analysis
            // DB::table('database_metrics')->insert([...]);

        } catch (\Exception $e) {
            Log::error('Failed to record database metrics', [
                'error' => $e->getMessage(),
            ]);
        }
    }
}