<?php

declare(strict_types=1);

namespace App\Actions\Caja;

use App\Models\Caja;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Validation\Rule;

class ListCajasAction
{
    /**
     * Get a paginated list of cash registers with filtering.
     *
     * @param array<string, mixed> $filters
     * @return LengthAwarePaginator<Caja>
     */
    public function execute(array $filters = []): LengthAwarePaginator
    {
        $query = Caja::with(['sucursal', 'gerente']);

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

        $query->when(!empty($filters['sucursal_id']), function ($q) use ($filters) {
            $q->where('sucursal_id', $filters['sucursal_id']);
        });

        $query->when(!empty($filters['gerente_id']), function ($q) use ($filters) {
            $q->where('gerente_id', $filters['gerente_id']);
        });

        // Balance range filter
        $query->when(isset($filters['saldo_min']), function ($q) use ($filters) {
            $q->where('saldo_actual', '>=', $filters['saldo_min']);
        });

        $query->when(isset($filters['saldo_max']), function ($q) use ($filters) {
            $q->where('saldo_actual', '<=', $filters['saldo_max']);
        });

        // Filter by state
        $query->when(isset($filters['abiertas']), function ($q) use ($filters) {
            if ($filters['abiertas']) {
                $q->where('estado', 'abierta');
            } else {
                $q->where('estado', 'cerrada');
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
