<?php

namespace App\Services;

use App\Enums\EstadoEnum;
use App\Models\Producto;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class ProductoService
{
    /**
     * Get products with advanced filtering and caching
     */
    public function getProductos(array $filters = [], array $sort = [], int $perPage = 15): LengthAwarePaginator
    {
        $cacheKey = $this->generateCacheKey($filters, $sort, $perPage);

        return Cache::remember($cacheKey, now()->addMinutes(30), function () use ($filters, $sort, $perPage) {
            $query = Producto::conRelacionesOptimizadas();

            // Apply filters
            $this->applyFilters($query, $filters);

            // Apply sorting
            $this->applySorting($query, $sort);

            return $query->paginate($perPage);
        });
    }

    /**
     * Get product by ID with caching
     */
    public function getProducto(string $id): ?Producto
    {
        return Cache::remember(
            "producto_{$id}",
            now()->addHours(2),
            fn () => Producto::conRelacionesOptimizadas()->find($id)
        );
    }

    /**
     * Create product with transaction and cache invalidation
     */
    public function crearProducto(array $data): Producto
    {
        return DB::transaction(function () use ($data) {
            $producto = Producto::create($data);

            // Clear related caches
            $this->clearProductosCache();

            // Cache the new product
            Cache::put(
                "producto_{$producto->id}",
                $producto->load(['categoria:id,nombre', 'proveedor:id,nombre']),
                now()->addHours(2)
            );

            return $producto;
        });
    }

    /**
     * Update product with transaction and cache invalidation
     */
    public function actualizarProducto(string $id, array $data): bool
    {
        return DB::transaction(function () use ($id, $data) {
            $producto = Producto::findOrFail($id);

            if ($producto->update($data)) {
                // Clear caches
                $this->clearProductosCache();
                Cache::forget("producto_{$id}");

                return true;
            }

            return false;
        });
    }

    /**
     * Delete product with transaction and cache invalidation
     */
    public function eliminarProducto(string $id): bool
    {
        return DB::transaction(function () use ($id) {
            $producto = Producto::findOrFail($id);

            if ($producto->delete()) {
                // Clear caches
                $this->clearProductosCache();
                Cache::forget("producto_{$id}");

                return true;
            }

            return false;
        });
    }

    /**
     * Get products with low stock
     */
    public function getProductosBajoStock(): Collection
    {
        return Cache::remember(
            'productos_bajo_stock',
            now()->addMinutes(15),
            fn () => Producto::conRelacionesOptimizadas()
                ->bajoStock()
                ->activos()
                ->get()
        );
    }

    /**
     * Get products expiring soon
     */
    public function getProductosProximosVencer(int $dias = 60): Collection
    {
        return Cache::remember(
            "productos_proximos_vencer_{$dias}",
            now()->addMinutes(15),
            fn () => Producto::conRelacionesOptimizadas()
                ->proximosAVencer($dias)
                ->activos()
                ->get()
        );
    }

    /**
     * Get products without stock
     */
    public function getProductosSinStock(): Collection
    {
        return Cache::remember(
            'productos_sin_stock',
            now()->addMinutes(15),
            fn () => Producto::conRelacionesOptimizadas()
                ->sinStock()
                ->activos()
                ->get()
        );
    }

    /**
     * Update product stock
     */
    public function actualizarStock(string $productoId, int $cantidad, string $operacion = 'agregar'): bool
    {
        return DB::transaction(function () use ($productoId, $cantidad, $operacion) {
            $producto = Producto::findOrFail($productoId);

            match ($operacion) {
                'agregar' => $producto->agregarStock($cantidad),
                'descontar' => $producto->descontarStock($cantidad),
                default => throw new \InvalidArgumentException("Operación no válida: {$operacion}")
            };

            // Clear related caches
            $this->clearProductosCache();
            Cache::forget("producto_{$productoId}");

            return true;
        });
    }

    /**
     * Get inventory statistics
     */
    public function getEstadisticasInventario(): array
    {
        return Cache::remember(
            'estadisticas_inventario',
            now()->addMinutes(10),
            function () {
                $productos = Producto::select([
                    DB::raw('COUNT(*) as total'),
                    DB::raw('SUM(CASE WHEN estado = ? THEN 1 ELSE 0 END) as activos', [EstadoEnum::ACTIVO->value]),
                    DB::raw('SUM(CASE WHEN estado = ? THEN 1 ELSE 0 END) as inactivos', [EstadoEnum::INACTIVO->value]),
                    DB::raw('SUM(stock_actual) as stock_total'),
                    DB::raw('SUM(precio_venta * stock_actual) as valor_total'),
                    DB::raw('SUM(CASE WHEN stock_actual <= stock_minimo THEN 1 ELSE 0 END) as bajo_stock'),
                    DB::raw('SUM(CASE WHEN fecha_vencimiento <= DATE_ADD(NOW(), INTERVAL 60 DAY) AND fecha_vencimiento IS NOT NULL THEN 1 ELSE 0 END) as proximos_vencer'),
                ])->first();

                $productosBajoStock = Producto::bajoStock()->activos()->count();
                $productosProximosVencer = Producto::proximosAVencer(60)->activos()->count();

                return [
                    'total_productos' => (int) $productos->total,
                    'activos' => (int) $productos->activos,
                    'inactivos' => (int) $productos->inactivos,
                    'stock_total' => (int) $productos->stock_total,
                    'valor_total' => (float) $productos->valor_total,
                    'productos_bajo_stock' => $productosBajoStock,
                    'productos_proximos_vencer' => $productosProximosVencer,
                    'valor_promedio' => $productos->total > 0 ? $productos->valor_total / $productos->total : 0,
                ];
            }
        );
    }

    /**
     * Search products with advanced search
     */
    public function buscarProductos(string $termino, array $filtrosAdicionales = []): Collection
    {
        $query = Producto::conRelacionesOptimizadas()
            ->busqueda($termino);

        // Apply additional filters
        if (! empty($filtrosAdicionales)) {
            $this->applyFilters($query, $filtrosAdicionales);
        }

        return $query->limit(50)->get();
    }

    /**
     * Get products by category
     */
    public function getProductosPorCategoria(string $categoriaId, int $limit = 20): Collection
    {
        return Cache::remember(
            "productos_categoria_{$categoriaId}_{$limit}",
            now()->addMinutes(30),
            fn () => Producto::conRelacionesOptimizadas()
                ->porCategoria($categoriaId)
                ->activos()
                ->limit($limit)
                ->get()
        );
    }

    /**
     * Get products by provider
     */
    public function getProductosPorProveedor(string $proveedorId): Collection
    {
        return Cache::remember(
            "productos_proveedor_{$proveedorId}",
            now()->addMinutes(30),
            fn () => Producto::conRelacionesOptimizadas()
                ->porProveedor($proveedorId)
                ->activos()
                ->get()
        );
    }

    /**
     * Apply filters to query
     */
    private function applyFilters(Builder $query, array $filters): void
    {
        foreach ($filters as $campo => $valor) {
            match ($campo) {
                'categoria_id' => $query->porCategoria($valor),
                'proveedor_id' => $query->porProveedor($valor),
                'estado' => $query->conEstado([$valor]),
                'con_stock' => $valor ? $query->conStock() : null,
                'bajo_stock' => $valor ? $query->bajoStock() : null,
                'proximos_vencer' => is_numeric($valor) ? $query->proximosAVencer($valor) : null,
                'refrigeracion' => $valor ? $query->conRefrigeracion() : null,
                'fraccionables' => $valor ? $query->fraccionables() : null,
                'precio_min' => isset($filters['precio_max']) ? $query->where('precio_venta', '>=', $valor) : null,
                'precio_max' => isset($filters['precio_min']) ? $query->where('precio_venta', '<=', $valor) : null,
                'laboratorio' => $query->porLaboratorio($valor),
                default => null,
            };
        }
    }

    /**
     * Apply sorting to query
     */
    private function applySorting(Builder $query, array $sort): void
    {
        $campo = $sort['campo'] ?? 'nombre';
        $direccion = $sort['direccion'] ?? 'asc';

        $allowedFields = [
            'nombre', 'created_at', 'updated_at', 'stock_actual',
            'precio_venta', 'precio_compra', 'laboratorio',
        ];

        if (in_array($campo, $allowedFields)) {
            $query->orderBy($campo, $direccion);
        }
    }

    /**
     * Generate cache key for filters
     */
    private function generateCacheKey(array $filters, array $sort, int $perPage): string
    {
        $filterHash = md5(serialize($filters));
        $sortHash = md5(serialize($sort));

        return "productos_list_{$filterHash}_{$sortHash}_{$perPage}";
    }

    /**
     * Clear product-related caches
     */
    private function clearProductosCache(): void
    {
        // Clear list caches
        $patterns = [
            'productos_list_*',
            'productos_bajo_stock',
            'productos_sin_stock',
            'productos_proximos_vencer_*',
            'estadisticas_inventario',
            'productos_categoria_*',
            'productos_proveedor_*',
        ];

        foreach ($patterns as $pattern) {
            Cache::forget($pattern);
        }
    }
}
