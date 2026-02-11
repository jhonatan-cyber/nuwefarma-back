<?php

declare(strict_types=1);

namespace App\Actions\Sucursal;

use App\Models\Sucursal;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Validation\Rule;

class ListSucursalesAction
{
    /**
     * Get a paginated list of branches with filtering.
     *
     * @param array<string, mixed> $filters
     * @return LengthAwarePaginator<Sucursal>
     */
    public function execute(array $filters = []): LengthAwarePaginator
    {
        $query = Sucursal::withCount(['usuarios', 'cajas']);

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
              ->orWhere('direccion', 'like', "%{$search}%")
              ->orWhere('ciudad', 'like', "%{$search}%")
              ->orWhere('departamento', 'like', "%{$search}%")
              ->orWhere('email', 'like', "%{$search}%");
        });

        $query->when(!empty($filters['estado']), function ($q) use ($filters) {
            $q->where('estado', $filters['estado']);
        });

        $query->when(!empty($filters['ciudad']), function ($q) use ($filters) {
            $q->where('ciudad', $filters['ciudad']);
        });

        $query->when(!empty($filters['departamento']), function ($q) use ($filters) {
            $q->where('departamento', $filters['departamento']);
        });

        $query->when(!empty($filters['pais']), function ($q) use ($filters) {
            $q->where('pais', $filters['pais']);
        });

        $query->when(!empty($filters['gerente_id']), function ($q) use ($filters) {
            $q->where('gerente_id', $filters['gerente_id']);
        });

        // Capacity filter
        $query->when(isset($filters['capacidad_min']), function ($q) use ($filters) {
            $q->where('capacidad_maxima', '>=', $filters['capacidad_min']);
        });

        $query->when(isset($filters['capacidad_max']), function ($q) use ($filters) {
            $q->where('capacidad_maxima', '<=', $filters['capacidad_max']);
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
