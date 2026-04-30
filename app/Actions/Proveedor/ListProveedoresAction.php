<?php

declare(strict_types=1);

namespace App\Actions\Proveedor;

use App\Models\Proveedor;
use Illuminate\Pagination\LengthAwarePaginator;

class ListProveedoresAction
{
    /**
     * Get a paginated list of providers with filtering.
     *
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<Proveedor>
     */
    public function execute(array $filters = []): LengthAwarePaginator
    {
        $query = Proveedor::withCount('productos');

        $this->applyFilters($query, $filters);

        $this->applySorting($query, $filters);

        $perPage = min($filters['per_page'] ?? 15, 100);

        return $query->paginate($perPage);
    }

    /**
     * Apply filters to the query.
     *
     * @param  mixed  $query
     * @param  array<string, mixed>  $filters
     */
    private function applyFilters($query, array $filters): void
    {
        $query->when(! empty($filters['search']), function ($q) use ($filters) {
            $search = $filters['search'];
            $q->where('nombre', 'like', "%{$search}%")
                ->orWhere('nit', 'like', "%{$search}%")
                ->orWhere('contacto', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%");
        });

        $query->when(! empty($filters['estado']), function ($q) use ($filters) {
            $q->where('estado', $filters['estado']);
        });

        $query->when(! empty($filters['ruc'] ?? $filters['nit'] ?? null), function ($q) use ($filters) {
            $nit = $filters['ruc'] ?? ($filters['nit'] ?? null);
            $q->where('nit', $nit);
        });
    }

    /**
     * Apply sorting to the query.
     *
     * @param  mixed  $query
     * @param  array<string, mixed>  $filters
     */
    private function applySorting($query, array $filters): void
    {
        $sort = $filters['sort'] ?? 'nombre';
        $direction = $filters['direction'] ?? 'asc';

        $query->orderBy($sort, $direction);
    }
}
