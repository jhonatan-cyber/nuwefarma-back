<?php

declare(strict_types=1);

namespace App\Actions\Rol;

use App\Models\Rol;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Validation\Rule;

class ListRolesAction
{
    /**
     * Get a paginated list of roles with filtering.
     *
     * @param array<string, mixed> $filters
     * @return LengthAwarePaginator<Rol>
     */
    public function execute(array $filters = []): LengthAwarePaginator
    {
        $query = Rol::withCount('usuarios');

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
              ->orWhere('descripcion', 'like', "%{$search}%");
        });

        $query->when(!empty($filters['estado']), function ($q) use ($filters) {
            $q->where('estado', $filters['estado']);
        });

        // Filter by permissions
        $query->when(!empty($filters['permiso']), function ($q) use ($filters) {
            $q->whereJsonContains('permiso_id', $filters['permiso']);
        });

        // Filter by user count
        $query->when(isset($filters['con_usuarios']), function ($q) use ($filters) {
            if ($filters['con_usuarios']) {
                $q->whereHas('usuarios');
            } else {
                $q->whereDoesntHave('usuarios');
            }
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
