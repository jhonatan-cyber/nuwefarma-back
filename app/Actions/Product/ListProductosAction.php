<?php

declare(strict_types=1);

namespace App\Actions\Product;

use App\Models\Producto;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Validation\Rule;

class ListProductosAction
{
    /**
     * Get a paginated list of products with filtering.
     *
     * @param array<string, mixed> $filters
     * @return LengthAwarePaginator<Producto>
     */
    public function execute(array $filters = []): LengthAwarePaginator
    {
        $query = Producto::with(['categoria', 'proveedor']);

        $this->applyFilters($query, $filters);

        $this->applySorting($query, $filters);

        $perPage = min($filters['per_page'] ?? 15, 100);

        return $query->paginate($perPage);
    }

    /**
     * Apply filters to the query.
     *
     * @param mixed $query
     * @param array<string, mixed> $filters
     */
    private function applyFilters($query, array $filters): void
    {
        $query->when(!empty($filters['search']), function ($q) use ($filters) {
            $search = $filters['search'];
            $q->where('nombre', 'like', "%{$search}%")
              ->orWhere('codigo_barras', 'like', "%{$search}%")
              ->orWhere('sku', 'like', "%{$search}%")
              ->orWhere('codigo_interno', 'like', "%{$search}%")
              ->orWhere('laboratorio', 'like', "%{$search}%");
        });

        $query->when(!empty($filters['estado']), function ($q) use ($filters) {
            $q->where('estado', $filters['estado']);
        });

        $query->when(!empty($filters['categoria_id']), function ($q) use ($filters) {
            $q->where('categoria_id', $filters['categoria_id']);
        });

        $query->when(!empty($filters['proveedor_id']), function ($q) use ($filters) {
            $q->where('proveedor_id', $filters['proveedor_id']);
        });

        // Stock filters
        $query->when(isset($filters['stock_bajo']), function ($q) use ($filters) {
            if ($filters['stock_bajo']) {
                $q->whereColumn('stock_actual', '<=', 'stock_minimo');
            }
        });

        $query->when(isset($filters['proximos_vencer']), function ($q) use ($filters) {
            if ($filters['proximos_vencer']) {
                $q->where('fecha_vencimiento', '<=', now()->addDays(30))
                  ->where('fecha_vencimiento', '>', now());
            }
        });

        // Price range filter
        $query->when(isset($filters['precio_min']), function ($q) use ($filters) {
            $q->where('precio_venta', '>=', $filters['precio_min']);
        });

        $query->when(isset($filters['precio_max']), function ($q) use ($filters) {
            $q->where('precio_venta', '<=', $filters['precio_max']);
        });
    }

    /**
     * Apply sorting to the query.
     *
     * @param mixed $query
     * @param array<string, mixed> $filters
     */
    private function applySorting($query, array $filters): void
    {
        $sort = $filters['sort'] ?? 'nombre';
        $direction = $filters['direction'] ?? 'asc';

        $query->orderBy($sort, $direction);
    }
}
