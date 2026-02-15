<?php

namespace App\Services\Performance;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

class OptimizationService
{
    public function __construct(
        private readonly Cache $cache,
        private readonly DB $db,
        private readonly Redis $redis
    ) {}

    /**
     * Optimize database queries with eager loading and selects
     */
    public function optimizeQuery(Builder $query, array $relations = [], array $selects = []): Builder
    {
        // Add eager loading for relations
        if (! empty($relations)) {
            $query->with($relations);
        }

        // Add specific selects to reduce payload
        if (! empty($selects)) {
            $query->select($selects);
        }

        // Add query optimization hints
        return $query->cacheFor(now()->addMinutes(30));
    }

    /**
     * Cache query results with intelligent invalidation
     */
    public function cacheQuery(string $key, callable $callback, int $ttl = 3600): mixed
    {
        return $this->cache->remember($key, $ttl, $callback);
    }

    /**
     * Bulk cache operations for better performance
     */
    public function bulkCache(array $items, string $prefix, int $ttl = 3600): void
    {
        $data = [];

        foreach ($items as $key => $value) {
            $data["{$prefix}:{$key}"] = $value;
        }

        $this->cache->putMany($data, $ttl);
    }

    /**
     * Clear cache patterns efficiently
     */
    public function clearCachePattern(string $pattern): void
    {
        if ($this->redis->isConnected()) {
            // Use Redis for pattern matching
            $keys = $this->redis->keys($pattern);

            if (! empty($keys)) {
                $this->redis->del($keys);
            }
        } else {
            // Fallback to file cache clearing
            $this->cache->flush();
        }
    }

    /**
     * Optimize database transactions
     */
    public function optimizedTransaction(callable $callback): mixed
    {
        return $this->db->transaction(function () use ($callback) {
            // Set transaction isolation level
            $this->db->statement('SET TRANSACTION ISOLATION LEVEL READ COMMITTED');

            $result = $callback($this->db);

            return $result;
        });
    }

    /**
     * Bulk database operations for better performance
     */
    public function bulkInsert(string $table, array $data): int
    {
        if (empty($data)) {
            return 0;
        }

        $chunks = array_chunk($data, 1000);
        $totalInserted = 0;

        foreach ($chunks as $chunk) {
            $totalInserted += $this->db->table($table)->insert($chunk);
        }

        return $totalInserted;
    }

    /**
     * Bulk update with single query
     */
    public function bulkUpdate(string $table, array $updates, string $keyColumn = 'id'): int
    {
        if (empty($updates)) {
            return 0;
        }

        $cases = [];
        $ids = [];
        $columns = [];

        // Prepare CASE statements
        foreach ($updates as $update) {
            $id = $update[$keyColumn];
            $ids[] = $id;

            foreach ($update as $column => $value) {
                if ($column === $keyColumn) {
                    continue;
                }

                $columns[$column] = true;
                $cases[$column][] = "WHEN {$keyColumn} = '{$id}' THEN '{$value}'";
            }
        }

        // Build the update query
        $sql = "UPDATE {$table} SET ";
        $setParts = [];

        foreach ($columns as $column => $true) {
            $setParts[] = "{$column} = CASE ".implode(' ', $cases[$column]).' END';
        }

        $sql .= implode(', ', $setParts);
        $sql .= " WHERE {$keyColumn} IN ('".implode("','", $ids)."')";

        return $this->db->statement($sql);
    }

    /**
     * Optimize pagination with cursor-based pagination
     */
    public function cursorPaginate(Builder $query, int $perPage = 15, ?string $cursor = null): array
    {
        if ($cursor) {
            $query->where('id', '>', $cursor);
        }

        $items = $query->orderBy('id')->limit($perPage + 1)->get();
        $hasMore = $items->count() > $perPage;

        if ($hasMore) {
            $items = $items->take($perPage);
        }

        return [
            'data' => $items,
            'next_cursor' => $hasMore ? $items->last()->id : null,
            'has_more' => $hasMore,
        ];
    }

    /**
     * Database query optimization hints
     */
    public function addQueryHints(Builder $query): Builder
    {
        // Add index hints for better performance
        $query->from($query->getModel()->getTable().' FORCE INDEX (PRIMARY, idx_estado, idx_categoria_id)');

        // Set query timeout
        $query->timeout(30);

        return $query;
    }

    /**
     * Memory optimization for large datasets
     */
    public function processLargeDataset(callable $processor, int $chunkSize = 1000): void
    {
        $this->db->table('productos')
            ->orderBy('id')
            ->chunk($chunkSize, $processor);
    }

    /**
     * Cache warming for frequently accessed data
     */
    public function warmCache(): void
    {
        // Warm up frequently accessed data
        $this->cacheQuery('productos_activos', function () {
            return \App\Models\Producto::activos()->with(['categoria:id,nombre', 'proveedor:id,nombre'])->get();
        }, 3600);

        $this->cacheQuery('categorias_all', function () {
            return \App\Models\Categoria::select('id', 'nombre')->get();
        }, 7200);

        $this->cacheQuery('proveedores_all', function () {
            return \App\Models\Proveedor::select('id', 'nombre')->get();
        }, 7200);
    }

    /**
     * Performance monitoring and metrics
     */
    public function getPerformanceMetrics(): array
    {
        return [
            'cache_stats' => $this->getCacheStats(),
            'database_stats' => $this->getDatabaseStats(),
            'memory_usage' => $this->getMemoryUsage(),
            'query_performance' => $this->getQueryPerformance(),
        ];
    }

    private function getCacheStats(): array
    {
        if ($this->redis->isConnected()) {
            $info = $this->redis->info('memory');

            return [
                'used_memory' => $info['used_memory'],
                'used_memory_human' => $info['used_memory_human'],
                'used_memory_rss' => $info['used_memory_rss'],
                'used_memory_peak' => $info['used_memory_peak'],
                'keyspace_hits' => $info['keyspace_hits'],
                'keyspace_misses' => $info['keyspace_misses'],
                'hit_rate' => $this->calculateHitRate($info['keyspace_hits'], $info['keyspace_misses']),
            ];
        }

        return ['status' => 'Redis not available'];
    }

    private function getDatabaseStats(): array
    {
        try {
            $stats = $this->db->select('
                SELECT 
                    COUNT(*) as total_connections,
                    SUM(CASE WHEN time > 5 THEN 1 ELSE 0 END) as slow_queries,
                    AVG(time) as avg_query_time
                FROM information_schema.processlist
            ')[0];

            return [
                'total_connections' => (int) $stats->total_connections,
                'slow_queries' => (int) $stats->slow_queries,
                'avg_query_time' => (float) $stats->avg_query_time,
            ];
        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }

    private function getMemoryUsage(): array
    {
        return [
            'memory_usage' => memory_get_usage(true),
            'memory_usage_mb' => round(memory_get_usage(true) / 1024 / 1024, 2),
            'peak_usage' => memory_get_peak_usage(true),
            'peak_usage_mb' => round(memory_get_peak_usage(true) / 1024 / 1024, 2),
        ];
    }

    private function getQueryPerformance(): array
    {
        return [
            'slow_query_log' => $this->db->select('SHOW VARIABLES LIKE "slow_query_log"')[0]->Value ?? 'OFF',
            'long_query_time' => $this->db->select('SHOW VARIABLES LIKE "long_query_time"')[0]->Value ?? '10',
            'query_cache_type' => $this->db->select('SHOW VARIABLES LIKE "query_cache_type"')[0]->Value ?? 'OFF',
        ];
    }

    private function calculateHitRate(int $hits, int $misses): float
    {
        $total = $hits + $misses;

        return $total > 0 ? round(($hits / $total) * 100, 2) : 0;
    }
}
