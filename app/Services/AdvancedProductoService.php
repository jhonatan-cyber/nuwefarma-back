<?php

namespace App\Services;

use App\Enums\EstadoEnum;
use App\Models\Producto;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

class AdvancedProductoService
{
    /**
     * Laravel 12+ advanced service with dependency injection and type hints
     */
    public function __construct(
        private readonly Cache $cache,
        private readonly DB $db,
        private readonly Redis $redis
    ) {}

    /**
     * Advanced product search with multiple filters and caching
     */
    public function searchProductos(array $criteria): LengthAwarePaginator
    {
        $cacheKey = $this->generateAdvancedCacheKey($criteria);
        
        return $this->cache->remember($cacheKey, now()->addMinutes(30), function () use ($criteria) {
            $query = Producto::query()
                ->with(['categoria:id,nombre', 'proveedor:id,nombre'])
                ->applyFilters($criteria['filters'] ?? [])
                ->applySorting($criteria['sort'] ?? []);

            // Apply Laravel 12+ advanced scopes
            if (isset($criteria['search'])) {
                $query->searchInColumns($criteria['search'], ['nombre', 'descripcion', 'laboratorio']);
            }

            if (isset($criteria['full_text_search'])) {
                $query->fullTextSearch($criteria['full_text_search']);
            }

            if (isset($criteria['price_range'])) {
                $query->filterByNumericRange('precio_venta', 
                    $criteria['price_range']['min'] ?? null, 
                    $criteria['price_range']['max'] ?? null
                );
            }

            if (isset($criteria['date_range'])) {
                $query->filterByDateRange('created_at', 
                    $criteria['date_range']['from'] ?? null, 
                    $criteria['date_range']['to'] ?? null
                );
            }

            return $query->paginate($criteria['per_page'] ?? 15);
        });
    }

    /**
     * Laravel 12+ advanced analytics with window functions
     */
    public function getAdvancedAnalytics(): array
    {
        return $this->cache->remember('productos_analytics_advanced', now()->addMinutes(15), function () {
            return [
                'top_products' => $this->getTopProducts(),
                'price_distribution' => $this->getPriceDistribution(),
                'stock_analysis' => $this->getStockAnalysis(),
                'category_performance' => $this->getCategoryPerformance(),
                'trending_products' => $this->getTrendingProducts(),
                'inventory_turnover' => $this->getInventoryTurnover(),
            ];
        });
    }

    /**
     * Get top products with ranking
     */
    private function getTopProducts(): Collection
    {
        return Producto::query()
            ->select(['id', 'nombre', 'precio_venta', 'stock_actual'])
            ->withRanking('precio_venta', 'price_rank')
            ->withRanking('stock_actual', 'stock_rank')
            ->orderBy('price_rank')
            ->limit(10)
            ->get();
    }

    /**
     * Get price distribution analysis
     */
    private function getPriceDistribution(): array
    {
        $result = Producto::query()
            ->selectRaw('
                CASE 
                    WHEN precio_venta < 10 THEN "Menos de $10"
                    WHEN precio_venta BETWEEN 10 AND 50 THEN "$10 - $50"
                    WHEN precio_venta BETWEEN 50 AND 100 THEN "$50 - $100"
                    WHEN precio_venta > 100 THEN "Más de $100"
                END as price_range,
                COUNT(*) as count,
                AVG(precio_venta) as avg_price,
                SUM(stock_actual) as total_stock
            ')
            ->withGroupBy(['price_range'])
            ->orderBy('price_range')
            ->get();

        return $result->toArray();
    }

    /**
     * Advanced stock analysis with CTE
     */
    private function getStockAnalysis(): array
    {
        return Producto::query()
            ->withCTE('stock_analysis', '
                SELECT 
                    id,
                    nombre,
                    stock_actual,
                    stock_minimo,
                    stock_maximo,
                    CASE 
                        WHEN stock_actual <= stock_minimo THEN "bajo_stock"
                        WHEN stock_actual >= stock_maximo THEN "exceso_stock"
                        ELSE "optimal"
                    END as stock_status
                FROM productos
            ')
            ->from('stock_analysis')
            ->selectRaw('
                stock_status,
                COUNT(*) as count,
                AVG(stock_actual) as avg_stock,
                SUM(stock_actual) as total_stock
            ')
            ->withGroupBy(['stock_status'])
            ->get()
            ->toArray();
    }

    /**
     * Category performance with aggregations
     */
    private function getCategoryPerformance(): Collection
    {
        return Producto::query()
            ->join('categorias', 'productos.categoria_id', '=', 'categorias.id')
            ->select([
                'categorias.nombre as category_name',
                'categorias.id as category_id',
            ])
            ->withAggregations([
                'COUNT' => ['productos.id' => 'product_count'],
                'AVG' => ['productos.precio_venta' => 'avg_price'],
                'SUM' => ['productos.stock_actual' => 'total_stock'],
                'SUM' => ['productos.precio_venta * productos.stock_actual' => 'total_value']
            ])
            ->withGroupBy(['categorias.id', 'categorias.nombre'])
            ->orderBy('total_value', 'desc')
            ->get();
    }

    /**
     * Trending products based on recent activity
     */
    private function getTrendingProducts(): Collection
    {
        return Producto::query()
            ->withCTE('recent_activity', '
                SELECT 
                    producto_id,
                    COUNT(*) as activity_count,
                    MAX(created_at) as last_activity
                FROM activity_logs
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                    AND modulo = "productos"
                GROUP BY producto_id
            ')
            ->join('recent_activity', 'productos.id', '=', 'recent_activity.producto_id')
            ->select([
                'productos.id',
                'productos.nombre',
                'productos.precio_venta',
                'recent_activity.activity_count',
                'recent_activity.last_activity'
            ])
            ->orderBy('activity_count', 'desc')
            ->orderBy('last_activity', 'desc')
            ->limit(10)
            ->get();
    }

    /**
     * Inventory turnover calculation
     */
    private function getInventoryTurnover(): array
    {
        return Producto::query()
            ->selectRaw('
                AVG(stock_actual) as avg_stock,
                SUM(stock_actual * precio_venta) as total_inventory_value,
                COUNT(*) as total_products,
                SUM(CASE WHEN stock_actual <= stock_minimo THEN 1 ELSE 0 END) as low_stock_products,
                SUM(CASE WHEN fecha_vencimiento <= DATE_ADD(NOW(), INTERVAL 60 DAY) AND fecha_vencimiento IS NOT NULL THEN 1 ELSE 0 END) as expiring_products
            ')
            ->first()
            ->toArray();
    }

    /**
     * Laravel 12+ bulk operations with transactions
     */
    public function bulkUpdateStock(array $updates): bool
    {
        return $this->db->transaction(function () use ($updates) {
            foreach ($updates as $update) {
                Producto::where('id', $update['id'])
                    ->update([
                        'stock_actual' => $this->db->raw("stock_actual + {$update['quantity']}"),
                        'updated_at' => now(),
                    ]);

                // Log the stock update
                $this->logStockUpdate($update);
            }

            // Clear related caches
            $this->clearProductCaches();

            return true;
        });
    }

    /**
     * Advanced product recommendations
     */
    public function getRecommendations(string $productoId, int $limit = 5): Collection
    {
        $producto = Producto::findOrFail($productoId);

        return Producto::query()
            ->where('id', '!=', $productoId)
            ->where('categoria_id', $producto->categoria_id)
            ->where('estado', EstadoEnum::ACTIVO->value)
            ->where('stock_actual', '>', 0)
            ->select(['id', 'nombre', 'precio_venta', 'categoria_id'])
            ->orderByRaw('ABS(precio_venta - ?) ASC', [$producto->precio_venta])
            ->limit($limit)
            ->get();
    }

    /**
     * Laravel 12+ real-time inventory monitoring
     */
    public function getRealTimeInventory(): array
    {
        $key = 'real_time_inventory';
        
        // Check Redis for real-time data
        if ($this->redis->exists($key)) {
            return json_decode($this->redis->get($key), true);
        }

        $data = [
            'total_products' => Producto::count(),
            'active_products' => Producto::where('estado', EstadoEnum::ACTIVO->value)->count(),
            'low_stock_count' => Producto::whereColumn('stock_actual', '<=', 'stock_minimo')->count(),
            'out_of_stock_count' => Producto::where('stock_actual', '<=', 0)->count(),
            'total_inventory_value' => Producto::selectRaw('SUM(stock_actual * precio_venta)')->value('SUM(stock_actual * precio_venta)') ?? 0,
            'expiring_soon_count' => Producto::proximosAVencer(30)->count(),
            'last_updated' => now()->toISOString(),
        ];

        // Cache in Redis for 5 minutes
        $this->redis->setex($key, 300, json_encode($data));

        return $data;
    }

    /**
     * Laravel 12+ advanced filtering with JSON operations
     */
    public function filterByJsonAttributes(array $jsonFilters): Collection
    {
        $query = Producto::query();

        foreach ($jsonFilters as $column => $conditions) {
            foreach ($conditions as $operation => $value) {
                match ($operation) {
                    'contains' => $query->whereJsonContains($column, $value),
                    'contains_key' => $query->whereJsonContainsKey($column, $value),
                    'length' => $query->whereJsonLength($column, '>=', $value),
                    'path' => $query->whereJsonPath($column, $value),
                    'overlaps' => $query->whereJsonOverlaps($column, $value),
                    default => null,
                };
            }
        }

        return $query->get();
    }

    /**
     * Laravel 12+ performance monitoring
     */
    public function getPerformanceMetrics(): array
    {
        return [
            'query_performance' => $this->getQueryPerformanceMetrics(),
            'cache_performance' => $this->getCachePerformanceMetrics(),
            'database_performance' => $this->getDatabasePerformanceMetrics(),
        ];
    }

    private function getQueryPerformanceMetrics(): array
    {
        return [
            'avg_query_time' => $this->db->select('SELECT AVG(timer) as avg_time FROM information_schema.processlist')[0]->avg_time ?? 0,
            'slow_queries' => $this->db->select('SELECT COUNT(*) as count FROM information_schema.processlist WHERE time > 5')[0]->count ?? 0,
            'active_connections' => $this->db->select('SELECT COUNT(*) as count FROM information_schema.processlist')[0]->count ?? 0,
        ];
    }

    private function getCachePerformanceMetrics(): array
    {
        return [
            'hit_rate' => $this->redis->info('stats')['keyspace_hits'] / max(1, $this->redis->info('stats')['keyspace_misses']),
            'memory_usage' => $this->redis->info('memory')['used_memory'],
            'total_keys' => $this->redis->dbSize(),
        ];
    }

    private function getDatabasePerformanceMetrics(): array
    {
        return [
            'total_queries' => $this->db->select('SHOW STATUS LIKE "Com_select"')[0]->Value ?? 0,
            'slow_query_log' => $this->db->select('SHOW VARIABLES LIKE "slow_query_log"')[0]->Value ?? 'OFF',
            'innodb_buffer_pool' => $this->db->select('SHOW STATUS LIKE "Innodb_buffer_pool_read_requests"')[0]->Value ?? 0,
        ];
    }

    /**
     * Helper methods
     */
    private function generateAdvancedCacheKey(array $criteria): string
    {
        return 'productos_search_' . md5(json_encode($criteria));
    }

    private function logStockUpdate(array $update): void
    {
        // Implementation for logging stock updates
    }

    private function clearProductCaches(): void
    {
        $patterns = [
            'productos_search_*',
            'productos_analytics_*',
            'real_time_inventory',
        ];

        foreach ($patterns as $pattern) {
            $keys = $this->redis->keys($pattern);
            if (!empty($keys)) {
                $this->redis->del($keys);
            }
        }
    }
}
