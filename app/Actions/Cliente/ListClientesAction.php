<?php

declare(strict_types=1);

namespace App\Actions\Cliente;

use App\Models\Cliente;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Validation\Rule;

class ListClientesAction
{
    /**
     * Get a paginated list of clients with filtering.
     *
     * @param array<string, mixed> $filters
     * @return LengthAwarePaginator<Cliente>
     */
    public function execute(array $filters = []): LengthAwarePaginator
    {
        $query = Cliente::withCount('ventas');

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
              ->orWhere('apellidos', 'like', "%{$search}%")
              ->orWhere('ci', 'like', "%{$search}%")
              ->orWhere('nit', 'like', "%{$search}%")
              ->orWhere('email', 'like', "%{$search}%");
        });

        $query->when(!empty($filters['estado']), function ($q) use ($filters) {
            $q->where('estado', $filters['estado']);
        });

        $query->when(!empty($filters['ci']), function ($q) use ($filters) {
            $q->where('ci', $filters['ci']);
        });

        $query->when(!empty($filters['nit']), function ($q) use ($filters) {
            $q->where('nit', $filters['nit']);
        });

        // Filter by debt status
        $query->when(isset($filters['con_deuda']), function ($q) use ($filters) {
            if ($filters['con_deuda']) {
                $q->whereHas('ventas', function ($subQuery) {
                    $subQuery->where('saldo_pendiente', '>', 0);
                });
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
