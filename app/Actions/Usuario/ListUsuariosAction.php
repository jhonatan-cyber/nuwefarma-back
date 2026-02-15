<?php

declare(strict_types=1);

namespace App\Actions\Usuario;

use App\Models\Usuario;
use Illuminate\Pagination\LengthAwarePaginator;

class ListUsuariosAction
{
    /**
     * Get a paginated list of users with filtering.
     *
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<Usuario>
     */
    public function execute(array $filters = []): LengthAwarePaginator
    {
        $query = Usuario::with(['rol', 'sucursal']);

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
                ->orWhere('apellidos', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%")
                ->orWhere('ci', 'like', "%{$search}%");
        });

        $query->when(! empty($filters['estado']), function ($q) use ($filters) {
            $q->where('estado', $filters['estado']);
        });

        $query->when(! empty($filters['rol_id']), function ($q) use ($filters) {
            $q->where('rol_id', $filters['rol_id']);
        });

        $query->when(! empty($filters['sucursal_id']), function ($q) use ($filters) {
            $q->where('sucursal_id', $filters['sucursal_id']);
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
