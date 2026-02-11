<?php

declare(strict_types=1);

namespace App\Actions\Proveedor;

use App\Models\Proveedor;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Validation\Rule;

class ListProveedoresAction
{
    /**
     * Get a paginated list of providers with filtering.
     *
     * @param array<string, mixed> $filters
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
     * @param mixed $query
     * @param array<string, mixed> $filters
     */
    private function applyFilters($query, array $filters): void
    {
        $query->when(!empty($filters['search']), function ($q) use ($filters) {
            $search = $filters['search'];
            $q->where('nombre', 'like', "%{$search}%")
              ->orWhere('ruc', 'like', "%{$search}%")
              ->orWhere('contacto_nombre', 'like', "%{$search}%")
              ->orWhere('email', 'like', "%{$search}%");
        });

        $query->when(!empty($filters['estado']), function ($q) use ($filters) {
            $q->where('estado', $filters['estado']);
        });

        $query->when(!empty($filters['ruc']), function ($q) use ($filters) {
            $q->where('ruc', $filters['ruc']);
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
